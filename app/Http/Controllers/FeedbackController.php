<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FeedbackController extends Controller
{
    public function index()
    {
        try {
            $feedbacks = Feedback::all();
            return response()->json($feedbacks);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while fetching feedbacks.'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'client_id' => 'required|integer',
                'star' => 'required|integer|min:1|max:5',
                'emoji' => 'required|in:very happy,happy,said',
                'your_goals' => 'required|in:yes,no',
                'missing_Q-tap_Menus' => 'required|string',
                'comment' => 'required|string',
            ]);

            $feedback = Feedback::create($request->all());

            return response()->json($feedback, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while storing the feedback.'], 500);
        }
    }

    public function update(Request $request, Feedback $feedback)
    {
        try {
            $request->validate([
                'client_id' => 'nullable|integer',
                'star' => 'nullable|integer|min:1|max:5',
                'emoji' => 'nullable|in:very happy,happy,said',
                'your_goals' => 'nullable|in:yes,no',
                'publish' => 'nullable|in:yes,no',
                'missing_Q-tap_Menus' => 'nullable|string',
                'comment' => 'nullable|string',
            ]);

            $feedback->update($request->all());

            return response()->json($feedback);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Feedback not found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while updating the feedback.'], 500);
        }
    }

    public function destroy(Feedback $feedback)
    {
        try {
            $feedback->delete();

            return response()->json(['message' => 'Feedback deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while deleting the feedback.'], 500);
        }
    }
}
