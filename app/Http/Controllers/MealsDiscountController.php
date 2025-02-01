<?php

namespace App\Http\Controllers;

use App\Models\meals_discount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MealsDiscountController extends Controller
{
    public function index(Request $request)
    {
        $brunch_id = $request->input('brunch_id');
        $discounts = meals_discount::where('brunch_id', $brunch_id)->get();
        return response()->json(['discounts' => $discounts]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:255', Rule::unique('meals_discounts', 'code')],
            'discount' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
            'brunch_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $discount = meals_discount::create($request->all());
        return response()->json(['message' => 'Discount created successfully', 'data' => $discount], 201);
    }

    public function update(Request $request, $id)
    {
        // استرجاع كائن الخصم باستخدام id
        $meals_discount = meals_discount::find($id);

        if (!$meals_discount) {
            return response()->json(['message' => 'Discount not found'], 404);
        }


        $validator = Validator::make($request->all(), [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('meals_discounts', 'code')->ignore($meals_discount->id)
            ],
            'discount' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
            'brunch_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $meals_discount->update($request->all());
        return response()->json(['message' => 'Discount updated successfully', 'data' => $meals_discount], 200);
    }

    public function destroy($id)
    {
        $meals_discount = meals_discount::find($id);

        if (!$meals_discount) {
            return response()->json(['message' => 'Discount not found'], 404);
        }


        $meals_discount->delete();
        return response()->json(['message' => 'Discount deleted successfully'], 200);
    }
}
