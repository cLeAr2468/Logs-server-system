<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    /**
     * Get all announcements (with filters)
     */
    public function index(Request $request)
    {
        $query = Announcement::with('staff:id,staff_id,fname,mname,lname,email')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $announcements = $query->paginate(10);

        return response()->json($announcements);
    }

    /**
     * Get published announcements (for public view)
     */
    public function getPublished(Request $request)
    {
        $query = Announcement::published()
            ->orderBy('published_at', 'desc');

        $announcements = $query->paginate(10);

        return response()->json($announcements);
    }

    /**
     * Get single announcement
     */
    public function show($id)
    {
        $announcement = Announcement::with('staff:id,staff_id,fname,mname,lname,email')
            ->findOrFail($id);

        return response()->json([
            'announcement' => $announcement
        ]);
    }

    /**
     * Create new announcement
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'status' => 'required|in:draft,published,archive',
            ]);

            // Get the authenticated user (admin or staff)
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - User not authenticated',
                ], 401);
            }

            // Determine staff_id based on user type
            $staffId = null;
            
            if ($user instanceof \App\Models\Admin) {
                // For Admin users, create or get a staff record
                $staffRecord = \App\Models\Staff::firstOrCreate(
                    ['email' => $user->email],
                    [
                        'staff_id' => 'ADMIN-' . str_pad($user->id, 3, '0', STR_PAD_LEFT),
                        'fname' => $user->fname,
                        'mname' => $user->mname,
                        'lname' => $user->lname,
                        'email' => $user->email,
                        'password' => $user->password,
                        'status' => 'Active',
                    ]
                );
                $staffId = $staffRecord->id;
            } elseif ($user instanceof \App\Models\Staff) {
                // For Staff users, use their ID directly
                $staffId = $user->id;
            }

            if (!$staffId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to determine staff ID',
                ], 500);
            }

            $data = [
                'staff_id' => $staffId,
                'title' => $request->title,
                'content' => $request->content,
                'status' => $request->status,
            ];

            // Handle image upload
            if ($request->hasFile('cover_image')) {
                $path = $request->file('cover_image')->store('announcements', 'public');
                $data['cover_image'] = $path;
            }

            // Set published_at if publishing
            if ($request->status === 'published') {
                $data['published_at'] = now();
            }

            $announcement = Announcement::create($data);
            $announcement->load('staff:id,staff_id,fname,mname,lname,email');

            // Log activity
            ActivityLog::create([
                'user_type' => $user instanceof \App\Models\Admin ? 'admin' : 'staff',
                'user_id' => $user instanceof \App\Models\Admin ? $user->admin_id : $user->staff_id,
                'user_name' => trim($user->fname . ' ' . $user->lname),
                'action' => 'created',
                'module' => 'Announcement',
                'description' => 'Created announcement: ' . $announcement->title,
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $request->status === 'published' 
                    ? 'Announcement published successfully' 
                    : 'Announcement saved as draft',
                'announcement' => $announcement
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Create announcement error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create announcement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update announcement
     */
    public function update(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'sometimes|required|in:draft,published,archive',
        ]);

        // Update fields
        if ($request->has('title')) {
            $announcement->title = $request->title;
        }
        if ($request->has('content')) {
            $announcement->content = $request->content;
        }

        // Handle image upload
        if ($request->hasFile('cover_image')) {
            // Delete old image if exists
            if ($announcement->cover_image) {
                Storage::disk('public')->delete($announcement->cover_image);
            }
            $path = $request->file('cover_image')->store('announcements', 'public');
            $announcement->cover_image = $path;
        }

        // Update status and published_at
        if ($request->has('status')) {
            $oldStatus = $announcement->status;
            $announcement->status = $request->status;
            
            // Set published_at when changing from draft to published
            if ($oldStatus === 'draft' && $request->status === 'published') {
                $announcement->published_at = now();
            }
        }

        $announcement->save();
        $announcement->load('staff:id,staff_id,fname,mname,lname,email');

        // Log activity
        $user = $request->user();
        ActivityLog::create([
            'user_type' => $user instanceof \App\Models\Admin ? 'admin' : 'staff',
            'user_id' => $user instanceof \App\Models\Admin ? $user->admin_id : $user->staff_id,
            'user_name' => trim($user->fname . ' ' . $user->lname),
            'action' => 'updated',
            'module' => 'Announcement',
            'description' => 'Updated announcement: ' . $announcement->title,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Announcement updated successfully',
            'announcement' => $announcement
        ]);
    }

    /**
     * Delete announcement
     */
    public function destroy(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);
        $title = $announcement->title;

        // Delete image if exists
        if ($announcement->cover_image) {
            Storage::disk('public')->delete($announcement->cover_image);
        }

        $announcement->delete();

        // Log activity
        $user = $request->user();
        ActivityLog::create([
            'user_type' => $user instanceof \App\Models\Admin ? 'admin' : 'staff',
            'user_id' => $user instanceof \App\Models\Admin ? $user->admin_id : $user->staff_id,
            'user_name' => trim($user->fname . ' ' . $user->lname),
            'action' => 'deleted',
            'module' => 'Announcement',
            'description' => 'Deleted announcement: ' . $title,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Announcement deleted successfully'
        ]);
    }

    /**
     * Publish a draft announcement
     */
    public function publish($id)
    {
        $announcement = Announcement::findOrFail($id);
        
        $announcement->status = 'published';
        $announcement->published_at = now();
        $announcement->save();

        return response()->json([
            'message' => 'Announcement published successfully',
            'announcement' => $announcement
        ]);
    }

    /**
     * Unpublish an announcement (revert to draft)
     */
    public function unpublish($id)
    {
        $announcement = Announcement::findOrFail($id);
        
        $announcement->status = 'draft';
        $announcement->save();

        return response()->json([
            'message' => 'Announcement reverted to draft',
            'announcement' => $announcement
        ]);
    }
}
