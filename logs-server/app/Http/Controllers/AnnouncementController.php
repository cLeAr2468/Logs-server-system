<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    /**
     * Get all announcements (with filters)
     */
    public function index(Request $request)
    {
        $query = Announcement::with('user:id,fname,mname,lname,email')
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
        $announcement = Announcement::with('user:id,fname,mname,lname,email')
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
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'required|in:draft,published,archive',
        ]);

        // Get user_id - handle both staff and default admin
        $userId = null;
        if ($request->user()) {
            // Staff user authenticated via Sanctum
            $userId = $request->user()->id;
        } else {
            // Default admin - use a default user_id (assuming ID 1 is admin)
            // Or set to null if user_id can be nullable
            $userId = 1; // Default admin user_id
        }

        $data = [
            'user_id' => $userId,
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
        $announcement->load('user:id,fname,mname,lname,email');

        return response()->json([
            'message' => $request->status === 'published' 
                ? 'Announcement published successfully' 
                : 'Announcement saved as draft',
            'announcement' => $announcement
        ], 201);
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
        $announcement->load('user:id,fname,mname,lname,email');

        return response()->json([
            'message' => 'Announcement updated successfully',
            'announcement' => $announcement
        ]);
    }

    /**
     * Delete announcement
     */
    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id);

        // Delete image if exists
        if ($announcement->cover_image) {
            Storage::disk('public')->delete($announcement->cover_image);
        }

        $announcement->delete();

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
