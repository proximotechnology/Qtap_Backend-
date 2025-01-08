<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CurrencyController extends Controller
{
    public function index()
    {
        $currencies = Currency::all();
        return response()->json([
            'success' => true,
            'currencies' => $currencies,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'symbol' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $currency = Currency::create([
            'name' => $request->name,
            'symbol' => $request->symbol,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Currency created successfully',
            'data' => $currency,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $currency = Currency::find($id);

        if (!$currency) {
            return response()->json([
                'status' => 'error',
                'message' => 'Currency not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'symbol' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $currency->update($request->only(['name', 'symbol']));

        return response()->json([
            'status' => 'success',
            'message' => 'Currency updated successfully',
            'data' => $currency,
        ], 200);
    }

    public function destroy($id)
    {
        $currency = Currency::find($id);

        if (!$currency) {
            return response()->json([
                'status' => 'error',
                'message' => 'Currency not found',
            ], 404);
        }

        $currency->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Currency deleted successfully',
        ], 200);
    }
}
