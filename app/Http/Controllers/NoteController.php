<?php

namespace App\Http\Controllers;

use App\Models\note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Events\PostCreated;


class NoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $notes = note::all();
        return response()->json([
            'success' => true,
            'notes' => $notes
        ]);
    }

    public function store(Request $request)
    {
        // Create a Validator instance
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Create a new note
        $note = note::create($validator->validated());
        event(new PostCreated($note));
        return response()->json([
            'success' => true,
            'note' => $note
        ]);
    }

    public function update(Request $request, note $note)
    {
        // Create a Validator instance
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Update the existing note
        $note->update($validator->validated());
        event(new PostCreated($note));

        return response()->json([
            'success' => true,
            'note' => $note
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(note $note)
    {
        $note->delete();
        return response()->json([
            'success' => true,
            'message' => 'Note deleted successfully'
        ]);
    }
}
