<?php

namespace App\Http\Controllers;

use App\Models\meals_special_offers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MealsSpecialOffersController extends Controller
{
    public function index(Request $request)
    {
        $brunch_id = $request->input('brunch_id');
        $special_offers = meals_special_offers::where('brunch_id', $brunch_id)->get();
        return response()->json($special_offers);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'discount' => 'required|string|min:0|max:100',
            'before_discount' => 'required|numeric',
            'after_discount' => 'required|numeric',
            'meals_id' => 'required|integer|exists:meals,id',
            'brunch_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $special_offer = meals_special_offers::create($request->all());
        return response()->json(['message' => 'Special offer created successfully', 'data' => $special_offer], 201);
    }

    public function show($id)
    {
        $special_offer = meals_special_offers::find($id);
        if (!$special_offer) {
            return response()->json(['message' => 'Special offer not found'], 404);
        }
        return response()->json($special_offer);
    }

    public function update(Request $request, $id)
    {
        $special_offer = meals_special_offers::find($id);
        if (!$special_offer) {
            return response()->json(['message' => 'Special offer not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'discount' => 'required|string|min:0|max:100',
            'before_discount' => 'required|numeric',
            'after_discount' => 'required|numeric',
            'meals_id' => 'required|integer|exists:meals,id',
            'brunch_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $special_offer->update($request->all());
        return response()->json(['message' => 'Special offer updated successfully', 'data' => $special_offer], 200);
    }

    public function destroy($id)
    {
        $special_offer = meals_special_offers::find($id);
        if (!$special_offer) {
            return response()->json(['message' => 'Special offer not found'], 404);
        }

        $special_offer->delete();
        return response()->json(['message' => 'Special offer deleted successfully'], 200);
    }
}
