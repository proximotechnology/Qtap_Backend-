<?php

namespace App\Http\Controllers;

use App\Models\pricing;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class PricingController extends Controller
{
    public function index()
    {
        try {
            $pricings = Pricing::all();
            return response()->json($pricings);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong while fetching data'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'specific_number' => 'required|integer',
                'feature' => 'required|array',
                'monthly_price' => 'required|numeric',
                'yearly_price' => 'required|numeric',
                'is_active' => 'required|in:active,inactive',
            ]);

            $request->merge([
                'feature' => json_encode($request->feature)
            ]);

            $pricing = Pricing::create($request->all());

            return response()->json($pricing, 201);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Database error: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong while saving data'], 500);
        }
    }



    public function update(Request $request, Pricing $pricing)
    {
        try {
            if (!$pricing) {
                return response()->json(['error' => 'Pricing not found'], 404);
            }

            $request->validate([
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'specific_number' => 'nullable|integer',
                'feature' => 'nullable|array',
                'monthly_price' => 'nullable|numeric',
                'yearly_price' => 'nullable|numeric',
                'is_active' => 'nullable|in:active,inactive',
            ]);

            $pricing->update($request->all());

            return response()->json($pricing);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Database error: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong while updating data'], 500);
        }
    }

    public function destroy(Pricing $pricing)
    {
        try {
            if (!$pricing) {
                return response()->json(['error' => 'Pricing not found'], 404);
            }

            $pricing->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong while deleting data'], 500);
        }
    }
}
