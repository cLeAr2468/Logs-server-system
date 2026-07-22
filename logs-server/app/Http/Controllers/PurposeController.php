<?php

namespace App\Http\Controllers;

use App\Models\Purpose;
use Illuminate\Http\Request;

class PurposeController extends Controller
{
    /**
     * Display a listing of all purposes (for admin and dropdowns)
     */
    public function index()
    {
        $purposes = Purpose::orderBy('name', 'asc')->get();
        
        return response()->json([
            'purposes' => $purposes
        ], 200);
    }

    /**
     * Store a newly created purpose
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:purposes,name',
        ]);

        $purpose = Purpose::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Purpose created successfully',
            'purpose' => $purpose
        ], 201);
    }

    /**
     * Display the specified purpose
     */
    public function show($id)
    {
        $purpose = Purpose::find($id);

        if (!$purpose) {
            return response()->json([
                'message' => 'Purpose not found'
            ], 404);
        }

        return response()->json([
            'purpose' => $purpose
        ], 200);
    }

    /**
     * Update the specified purpose
     */
    public function update(Request $request, $id)
    {
        $purpose = Purpose::find($id);

        if (!$purpose) {
            return response()->json([
                'message' => 'Purpose not found'
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:purposes,name,' . $id,
        ]);

        $purpose->name = $request->name;
        $purpose->save();

        return response()->json([
            'message' => 'Purpose updated successfully',
            'purpose' => $purpose
        ], 200);
    }

    /**
     * Remove the specified purpose
     */
    public function destroy($id)
    {
        $purpose = Purpose::find($id);

        if (!$purpose) {
            return response()->json([
                'message' => 'Purpose not found'
            ], 404);
        }

        // Check if purpose is being used in transactions
        $transactionCount = \App\Models\Transaction::where('purpose', $purpose->name)->count();
        
        if ($transactionCount > 0) {
            return response()->json([
                'message' => "Cannot delete purpose '{$purpose->name}' because it is being used in {$transactionCount} transaction(s). Consider deactivating it instead."
            ], 400);
        }

        $purpose->delete();

        return response()->json([
            'message' => 'Purpose deleted successfully'
        ], 200);
    }


}
