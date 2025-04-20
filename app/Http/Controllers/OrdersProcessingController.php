<?php

namespace App\Http\Controllers;

use App\Models\orders_processing;
use App\Models\delivery_rider;
use App\Models\restaurant_user_staff;
use App\Models\orders;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

class OrdersProcessingController extends Controller
{

    public function order_map($order_id)
    {

        $order_map = orders_processing::with('user')->where('order_id', $order_id)->get();

        if (!$order_map) {
            return response()->json([
                'success' => false,
                'message' => 'Order not accepted yet',
            ]);
        }

        return response()->json([
            'success' => true,
            'order_map' => $order_map,
        ]);
    }


    public function get_proccessing_orders($id)
    {

        $orders = orders_processing::find($id);

        if (!$orders) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ]);
        }

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }



    public function accept_order(Request $request)
    {

        $request['user_id'] = auth()->user()->id;
        $request['brunch_id'] = auth()->user()->brunch_id;
        $request['stage'] = 'chef';

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:restaurant_user_staffs,id',
            'brunch_id' => 'required|exists:qtap_clients_brunchs,id',
            'status' => 'required|in:accepted,rejected',
            'stage' => 'required|in:chef',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }


        $orders_processing = orders_processing::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Order accepted successfully',
            'orders_processing' => $orders_processing,
        ]);
    }



    public function order_prepared(Request $request)
    {


        $request['user_id'] = auth()->user()->id;
        $request['brunch_id'] = auth()->user()->brunch_id;
        $request['stage'] = 'chef';

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:restaurant_user_staffs,id',
            'brunch_id' => 'required|exists:qtap_clients_brunchs,id',
            'status' => 'required|in:prepared,rejected',
            'stage' => 'required|in:chef',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }


        $orders_processing = orders_processing::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Order prepared successfully',
            'orders_processing' => $orders_processing,
        ]);
    }


    public function get_new_orders()
    {
        // استرجاع الـ brunch_id الخاص بالمستخدم الحالي
        $brunch_id = auth()->user()->brunch_id;

        // استعلام لاسترجاع الطلبات الجديدة التي لم يتم معالجتها بعد
        $orders = orders::with('meal')
            ->whereNotIn('id', function ($query) {
                $query->select('order_id')
                    ->from('orders_processings');
            })
            ->where('brunch_id', $brunch_id)
            ->where('status', 'pending')
            ->get();

        // إرجاع الاستجابة
        return response()->json([
            'success' => true,
            'new_orders' => $orders,
        ]);
    }



    public function payment_received(Request $request)
    {

        $request['user_id'] = auth()->user()->id;
        $request['brunch_id'] = auth()->user()->brunch_id;
        $request['stage'] = 'cashier';

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:restaurant_user_staffs,id',
            'brunch_id' => 'required|exists:qtap_clients_brunchs,id',
            'status' => 'required|in:payment_received,rejected',
            'stage' => 'required|in:cashier',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $order = orders::find($request->order_id);
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ]);
        }

        $order->update([
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);


        $orders_processing = orders_processing::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Order payment_received successfully',
            'orders_processing' => $orders_processing,
        ]);
    }


    public function get_accepted_orders()
    {


        $brunch_id = auth()->user()->brunch_id;

        $orders = orders_processing::with(['order' => function ($query) {
            $query->where('type', '!=', 'delivery');
        }])->where('brunch_id', $brunch_id)->where('status', 'prepared')->get();

        return response()->json([
            'success' => true,
            'accepted_orders' => $orders,
        ]);
    }



    public function order_served(Request $request)
    {

        $request['user_id'] = auth()->user()->id;
        $request['brunch_id'] = auth()->user()->brunch_id;
        $request['stage'] = 'waiter';

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:restaurant_user_staffs,id',
            'brunch_id' => 'required|exists:qtap_clients_brunchs,id',
            'status' => 'required|in:served,rejected',
            'stage' => 'required|in:waiter',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }


        $orders_processing = orders_processing::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Order served successfully',
            'orders_processing' => $orders_processing,
        ]);
    }



    public function choose_delivery(Request $request)
    {

        $request['user_id'] = auth()->user()->id;
        $request['brunch_id'] = auth()->user()->brunch_id;
        $request['stage'] = 'delivery';

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:restaurant_user_staffs,id',
            'delivery_rider_id' => 'required|exists:restaurant_user_staffs,id',
            'brunch_id' => 'required|exists:qtap_clients_brunchs,id',
            'status' => 'required|in:delivery,rejected',
            'stage' => 'required|in:delivery',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }


        $orders_processing = orders_processing::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Order assigned successfully',
            'orders_processing' => $orders_processing,
        ]);
    }



    public function get_prepared_orders()
    {

        $brunch_id = auth()->user()->brunch_id;
        $orders = orders_processing::with(['order' => function ($query) {
            $query->where('type', '!=', 'delivery');
        }])->where('brunch_id', $brunch_id)->where('status', 'prepared')->get();


        return response()->json([
            'success' => true,
            'prepared_orders' => $orders,
        ]);
    }


    public function get_prepared_orders_delivery()
    {
        $user = auth()->user();

        $brunch_id = auth()->user()->brunch_id;

        if ($user->status_rider == 'Busy') {
            return response()->json([
                'success' => false,
                'message' => 'You are not available',
            ]);
        }

        $orders = orders_processing::with(['order' => function ($query) {
            $query->where('type', 'delivery');
        }])->where('brunch_id', $brunch_id)->where('status', 'prepared')->where('delivery_rider_id', auth()->user()->id)->get();




        return response()->json([
            'success' => true,
            'prepared_orders' => $orders,
        ]);
    }


    public function get_served_orders()
    {
        $brunch_id = auth()->user()->brunch_id;
        $orders = orders_processing::where('status', 'served')->where('brunch_id', $brunch_id)->get();

        return response()->json([
            'success' => true,
            'served_orders' => $orders,
        ]);
    }


    public function order_done(Request $request)
    {

        $request['user_id'] = auth()->user()->id;
        $request['brunch_id'] = auth()->user()->brunch_id;
        $request['stage'] = 'done';

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:restaurant_user_staffs,id',
            'brunch_id' => 'required|exists:qtap_clients_brunchs,id',
            'status' => 'required|in:done,rejected',
            'stage' => 'required|in:done',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }


        $orders_processing = orders_processing::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Order done successfully',
            'orders_processing' => $orders_processing,
        ]);
    }



    public function order_delivered(Request $request)
    {
        $request['user_id'] = auth()->user()->id;
        $request['brunch_id'] = auth()->user()->brunch_id;
        $request['stage'] = 'delivery';


        $user = auth()->user();

        if ($user->status_rider == 'Busy') {
            return response()->json([
                'success' => false,
                'message' => 'You are not available',
            ]);
        }

        $order = orders_processing::where('order_id', $request->order_id)->where('status', 'delivered')->first();
        if ($order) {
            return response()->json([
                'success' => false,
                'message' => 'Order already delivered',
            ]);
        }


        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:restaurant_user_staffs,id',
            'brunch_id' => 'required|exists:qtap_clients_brunchs,id',
            'status' => 'required|in:delivered,rejected',
            'stage' => 'required|in:delivery',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        if ($request->status == 'delivered') {

            $order = orders::where('id', $request->order_id)->first();

            if ($order->payment_status == 'unpaid') {
                $order->update(['payment_status' => 'paid']);
            }

            $order->update(['status' => 'delivered']);

            $rider = restaurant_user_staff::where('id', $request->user_id)->first();

            $rider->update(['orders' => $rider->orders + 1]);
        } else {

            $order = orders::where('id', $request->order_id)->first();

            $order->update(['status' => 'cancelled']);
        }




        $orders_processing = orders_processing::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Order delivered successfully',
            'orders_processing' => $orders_processing,
        ]);
    }

    public function get_delivered_orders()
    {
        $brunch_id = auth()->user()->brunch_id;
        $orders = orders_processing::where('status', 'delivered')->where('brunch_id', $brunch_id)->get();

        return response()->json([
            'success' => true,
            'delivered_orders' => $orders,
        ]);
    }
}
