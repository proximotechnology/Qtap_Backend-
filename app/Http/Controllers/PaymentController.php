<?php

namespace App\Http\Controllers;

use App\Models\payment;
use App\Models\qtap_clients_brunchs;
use Illuminate\Http\Request;

class PaymentController extends Controller
{

    public function index()
    {
        $payments = payment::all();
        return response()->json([
            'success' => true,
            'payments' => $payments
        ]);
    }


    public function store(Request $request)
    {
        try {

            $data = $request->validate([
                'API_KEY' => 'required|string',
                'Token1' => 'required|string',
                'Token2' => 'required|string',
                'Ifram' => 'required|string',
                'brunch_id' => 'required|integer|max:255',
            ]);


            $brunch_id = qtap_clients_brunchs::find($data['brunch_id']);
            if (!$brunch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'brunch not found'
                ]);
            }

            $payment = payment::create($data);
            return response()->json([
                'success' => true,
                'payment' => $payment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }


    public function update(Request $request, $id)
    {
        try {

            $payment = payment::find($id);
            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'payment not found'
                ]);
            }

            $data = $request->validate([
                'API_KEY' => 'required|string',
                'Token1' => 'required|string',
                'Token2' => 'required|string',
                'Ifram' => 'required|string',
                'brunch_id' => 'required|integer|max:255',
            ]);

            $brunch_id = qtap_clients_brunchs::find($data['brunch_id']);
            if (!$brunch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'brunch not found'
                ]);
            }

            $payment->update($data);
            return response()->json([
                'success' => true,
                'payment' => $payment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }


    public function destroy($id)
    {
        $payment = payment::find($id);
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'payment not found'
            ]);
        }
        $payment->delete();
        return response()->json([
            'success' => true,
            'message' => 'payment deleted successfully'
        ]);
    }
}
