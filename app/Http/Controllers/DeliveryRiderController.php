<?php

namespace App\Http\Controllers;

use App\Models\delivery_area;
use App\Models\qtap_clients_brunchs;
use App\Models\delivery_rider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class DeliveryRiderController extends Controller
{

    public function index()
    {
        $delivery_riders = delivery_rider::all();
        return response()->json([
            'success' => true,
            'delivery_riders' => $delivery_riders
        ]);
    }


    public function store(Request $request)
    {
        try {


            $validate = Validator::make($request->all(), [
                'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
                'delivery_areas_id' => 'required|integer|exists:delivery_areas,id',
                'name' => 'required|string',
                'phone' => 'required|string',
                'pin' => 'required|string',
                'orders' => 'required|integer',
                'status' => 'required|in:Available,Busy',
            ]);

            if ($validate->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validate->errors()
                ]);
            }

            $data = $request->all();



            $brunch_id = qtap_clients_brunchs::find($data['brunch_id']);
            if (!$brunch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'brunch not found'
                ]);
            }


            $delivery_areas_id = delivery_area::find($data['delivery_areas_id']);
            if (!$delivery_areas_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'delivery_area not found'
                ]);
            }

            $delivery_rider = delivery_rider::create($data);
            return response()->json([
                'success' => true,
                'delivery_rider' => $delivery_rider
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

            $delivery_rider = delivery_rider::find($id);
            if (!$delivery_rider) {
                return response()->json([
                    'success' => false,
                    'message' => 'delivery_rider not found'
                ]);
            }

            $data = $request->validate([
                'brunch_id' => 'required|integer|max:255',
                'delivery_areas_id' => 'required|integer|max:255',
                'name' => 'required|string',
                'phone' => 'required|string',
                'pin' => 'required|string',
                'orders' => 'required|integer',
                'status' => 'required|in:Available,Busy',
            ]);

            $brunch_id = qtap_clients_brunchs::find($data['brunch_id']);
            if (!$brunch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'brunch not found'
                ]);
            }

            $delivery_areas_id = delivery_area::find($data['delivery_areas_id']);
            if (!$delivery_areas_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'delivery_area not found'
                ]);
            }

            $delivery_rider->update($data);
            return response()->json([
                'success' => true,
                'delivery_rider' => $delivery_rider
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
        try {

            $delivery_rider = delivery_rider::find($id);
            if (!$delivery_rider) {
                return response()->json([
                    'success' => false,
                    'message' => 'delivery_rider not found'
                ]);
            }
            $delivery_rider->delete();
            return response()->json([
                'success' => true,
                'message' => 'delivery_rider deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
