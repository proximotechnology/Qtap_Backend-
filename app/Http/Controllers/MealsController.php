<?php

namespace App\Http\Controllers;

use App\Models\meals;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class MealsController extends Controller
{
    public function index(Request $request)
    {
        $brunch_id = $request->input('brunch_id');
        $meals = meals::where('brunch_id', $brunch_id)->get();
        return response()->json($meals);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('meals', 'name')
            ],
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'Brief' => 'nullable|string',
            'Description' => 'nullable|string',
            'Ingredients' => 'nullable|string',
            'Calories' => 'nullable|integer',
            'Time' => 'nullable|string',
            'Tax' => 'nullable|numeric',
            'price' => 'required|numeric',
            'discount_id' => 'nullable|integer',
            'categories_id' => 'required|integer',
            'brunch_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();

        if ($request->hasFile('img')) {
            $path = $request->file('img')->store('images', 'public');
            $data['img'] = 'storage/public/' . $path;
        }

        $meal = meals::create($data);
        return response()->json(['message' => 'Meal extra created successfully', 'data' => $meal], 201);
    }

    public function show($id)
    {
        $meal = meals::find($id);
        if (!$meal) {
            return response()->json(['message' => 'Meal not found'], 404);
        }
        return response()->json($meal);
    }

    public function update(Request $request, $id)
    {

        
        $meal = meals::find($id);
        if (!$meal) {
            return response()->json(['message' => 'Meal not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('meals', 'name')->ignore($meal->id)
            ],
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'Brief' => 'nullable|string',
            'Description' => 'nullable|string',
            'Ingredients' => 'nullable|string',
            'Calories' => 'nullable|integer',
            'Time' => 'nullable|string',
            'Tax' => 'nullable|numeric',
            'price' => 'required|numeric',
            'discount_id' => 'nullable|integer',
            'categories_id' => 'required|integer',
            'brunch_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();

        if ($request->hasFile('img')) {
            if ($meal->img) {
                Storage::disk('public')->delete($meal->img);
            }
            $path = $request->file('img')->store('images', 'public');
            $data['img'] = 'storage/public/' . $path;
        }

        $meal->update($data);
        return response()->json(['message' => 'Meal updated successfully', 'data' => $meal], 200);
    }

    public function destroy($id)
    {
        $meal = meals::find($id);
        if (!$meal) {
            return response()->json(['message' => 'Meal not found'], 404);
        }

        if ($meal->img) {
            Storage::disk('public')->delete($meal->img);
        }

        $meal->forceDelete();
        return response()->json(['message' => 'Meal deleted successfully'], 200);
    }
}
