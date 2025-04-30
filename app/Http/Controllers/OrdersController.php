<?php

namespace App\Http\Controllers;

use App\Models\orders;
use App\Models\qtap_clients_brunchs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\tables;
use App\Models\meals;
use App\Models\note;

use App\Events\notify_msg;

use Illuminate\Support\Facades\Validator;

class OrdersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($brunch_id)
    {

        $brunch = qtap_clients_brunchs::where('status', 'active')->find($brunch_id);

        if (!$brunch) {
            return response()->json([
                'message' => 'Brunch not active',
            ]);
        }

        $orders = orders::with(
            'orders_processing' ,
            'orders_processing.user' ,


        )->where('brunch_id', $brunch_id)->get();

        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    }


    public function store(Request $request)
    {
        DB::beginTransaction();





        try {
            // التحقق من البيانات العامة
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:255',
                'phone' => 'required|max:255',
                'comments' => 'nullable|max:2000',

                'city' => 'nullable|max:255',
                'address' => 'nullable|max:255',
                'type' => 'required|in:dinein,takeaway,delivery',

                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',

                'table_id' => 'nullable|integer|exists:tables,id',
                'tax' => 'required|numeric',
                'total_price' => 'required|numeric',

                'payment_way' => 'required|in:cash,wallet',
                'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',

                'meals' => 'required|array|min:1',
                'meals.*.meal_id' => 'required|integer|exists:meals,id',
                'meals.*.quantity' => 'required|integer|min:1',
                'meals.*.discount_code' => 'nullable|string',
                'meals.*.variants' => 'nullable|array',
                'meals.*.variants.*' => 'integer|exists:meals_variants,id',
                'meals.*.extras' => 'nullable|array',
                'meals.*.extras.*' => 'integer|exists:meals_extras,id',
                'meals.*.size' => 'nullable|string|in:s,m,l',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // التحقق من الفرع
            $brunch = qtap_clients_brunchs::with('client')->where('status', 'active')->find($request->brunch_id);
            if (!$brunch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Brunch not active.',
                ]);
            }


            // حساب عدد الطلبات لهذا الفرع خلال هذا الشهر
            // $currentMonthOrdersCount = orders::where('brunch_id', $brunch->id)
            //     ->whereYear('created_at', now()->year)
            //     ->whereMonth('created_at', now()->month)
            //     ->count();



            // $limit = $brunch->pricing->limit;


            // if ($currentMonthOrdersCount >= $limit) {

            //     $notify = note::create([
            //         'title' => 'Order limit reached',
            //         'content' => 'You have reached the limit of orders for this month.',
            //     ]);

            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'resturant reached the limit of orders ',
            //     ]);
            // }

            // إنشاء الطلب الرئيسي
            $order = orders::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'comments' => $request->comments,
                'type' => $request->type,
                'tax' => $request->tax,
                'total_price' => $request->total_price,
                'payment_way' => $request->payment_way,
                'brunch_id' => $request->brunch_id,
                'city' => $request->city,
                'address' => $request->address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'table_id' => $request->table_id,


                // البيانات المستخرجة من أول عنصر في مصفوفة meals
                'meal_id' => json_encode(collect($request->meals)->pluck('meal_id')),
                'quantity' => json_encode(collect($request->meals)->pluck('quantity')),
                'discount_code' => json_encode(collect($request->meals)->pluck('discount_code')),
                'size' => json_encode(collect($request->meals)->pluck('size')),
                'variants' => json_encode(collect($request->meals)->pluck('variants')),
                'extras' => json_encode(collect($request->meals)->pluck('extras')),

            ]);




            // معالجة الدفع إن كان wallet
            if ($request->payment_way === 'wallet') {

                $total_price = ceil($request->total_price);
                $firstMeal = meals::find($request->meals[0]['meal_id']); // أول وجبة لعرض الاسم

                $userData = [
                    'first_name' => $request->name,
                    'last_name' => $request->name,
                    'email' => $brunch->client->email,
                    'phone_number' => $request->phone,
                    'order_id' => $order->id
                ];

                $orderData = [
                    'total' => $total_price,
                    'currency' => 'EGP',
                    'service_name' => $firstMeal ? $firstMeal->name : 'Order',
                    'items' => [
                        [
                            'name' => $firstMeal ? $firstMeal->name : 'Meal',
                            'amount_cents' => intval($total_price) * 100,
                            'description' => 'Qtap Order',
                            'quantity' => 1
                        ]
                    ]
                ];

                $paymobController = new PaymobController();
                $response = $paymobController->processPayment_orders($orderData, $userData);

                if ($response['status'] == 'success') {
                    DB::commit();

                    $type = 'add_order';

                    event(new notify_msg($order, $type));

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Order created successfully',
                        'payment_url' => $response['payment_url']
                    ], 201);
                } else {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'response' => $response
                    ], 500);
                }
            } else {
                DB::commit();

                $type = 'add_order';
                event(new notify_msg($order, $type));

                return response()->json([
                    'success' => true,
                    'order' => $order
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    // public function store(Request $request)
    // {

    //     DB::beginTransaction(); // بدء المعاملة

    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'name' => 'required|max:255',
    //             'phone' => 'required|max:255',
    //             'comments' => 'nullable|max:2000',


    //             'city' => 'nullable|max:255',
    //             'address' => 'nullable|max:255',

    //             'type' => 'required|in:dinein,takeaway,delivery',

    //             'latitude' => 'nullable|numeric|between:-90,90',
    //             'longitude' => 'nullable|numeric|between:-180,180',

    //             'table_id' => 'nullable|integer|exists:tables,id',
    //             'discount_code' => 'nullable|string',
    //             'tax' => 'nullable|numeric',
    //             'total_price' => 'required|numeric',

    //             'payment_way' => 'required|in:cash,wallet',

    //             'meal_id' => 'required|integer|exists:meals,id',

    //             'extras' => 'nullable|array',
    //             'extras.*.id' => 'nullable|integer|exists:extras,id',

    //             'variants' => 'nullable|array',
    //             'variants.*.id' => 'nullable|integer|exists:variants,id',

    //             'size_id' => 'nullable|integer|exists:sizes,id',
    //             'quantity' => 'required|integer',

    //             'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json(['errors' => $validator->errors()], 422);
    //         }

    //         $brunch = qtap_clients_brunchs::with('client')->where('status', 'active')->find($request->brunch_id);

    //         if (!$brunch) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Brunch not active.',
    //             ]);
    //         }



    //         $meal = meals::find($request->meal_id);

    //         if (!$meal) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Meal not found.',
    //             ]);
    //         }


    //         // تجهيز البيانات وإصلاح مشكلة المصفوفات
    //         $data = $request->all();
    //         if (isset($data['variants'])) {
    //             $data['variants'] = json_encode($data['variants']);
    //         }


    //         if (isset($data['extras'])) {
    //             $data['extras'] = json_encode($data['extras']);
    //         }

    //         $orders = orders::create($data);

    //         if ($request->payment_way == 'wallet') {

    //             $brunch_id = $request->brunch_id;

    //             $total_price = ceil($request->total_price);

    //             $quantity = $request->quantity;



    //             $userData = [
    //                 'first_name' => $request->name,
    //                 'last_name' => $request->name,
    //                 'email' => $brunch->client->email,
    //                 // 'password' => $request->password,
    //                 'phone_number' => $request->phone,
    //                 'order_id' => $orders->id
    //             ];

    //             // بيانات الطلب الخاص برسوم التسجيل
    //             $orderData = [

    //                 'total' =>  $total_price,
    //                 'currency' => 'EGP',
    //                 'service_name' =>  $meal->name,

    //                 'items' => [
    //                     [
    //                         'name' =>   $meal->name,
    //                         "amount_cents" => intval($total_price) * 100,
    //                         "description" => "Qtap Client Registration",
    //                         "quantity" => $quantity
    //                     ]
    //                 ]
    //             ];


    //             // استدعاء كنترولر الدفع
    //             $paymobController = new PaymobController();
    //             $response = $paymobController->processPayment_orders($orderData, $userData);


    //             if ($response['status'] == 'success') {

    //                 DB::commit(); // تأكيد العملية

    //                 return response()->json([
    //                     'status' => 'success',
    //                     'message' => 'order created successfully',
    //                     'payment_url' => $response['payment_url']
    //                 ], 201);
    //             } else {

    //                 DB::rollBack(); // إلغاء جميع العمليات عند الفشل

    //                 return response()->json([
    //                     'status' => 'error',
    //                     'response' => $response

    //                 ], 201);
    //             }
    //         } elseif ($request->payment_way == 'cash') {
    //             DB::commit(); // تأكيد العملية

    //             return response()->json([
    //                 'success' => true,
    //                 'orders' => $orders
    //             ]);
    //         } else {
    //             DB::rollBack(); // إلغاء جميع العمليات عند الفشل

    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Payment way not found'
    //             ]);
    //         }
    //     } catch (\Exception $e) {
    //         DB::rollBack(); // إلغاء جميع العمليات عند حدوث خطأ غير متوقع
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Something went wrong.',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }




    public function destroy($id)
    {
        $orders = orders::find($id);

        if (!$orders) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ]);
        }

        $orders->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully'
        ]);
    }
}
