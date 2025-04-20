<?php

namespace App\Http\Controllers;

use App\Models\qtap_admins;
use App\Models\qtap_affiliate;
use App\Models\clients_logs;
use App\Models\User;
use App\Models\qtap_clients_brunchs;
use App\Models\restaurant_user_staff;
use App\Models\qtap_clients;
use App\Models\restaurant_staff;
use App\Models\restaurant_users;
use App\Models\users_logs;
use App\Models\affiliate_log;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

use Illuminate\Support\Str;



use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth; // أضف هذا لضمان عمل Auth بشكل صحيح

class AuthController extends Controller
{
    /**
     * تسجيل مستخدم جديد
     */




    public function register(Request $request)
    {

        qtap_affiliate::where('email', $request->email)->where('status', 'inactive')->delete();


        qtap_clients::where('email', $request->email)->where('status', 'inactive')->delete();


        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'mobile' => 'required|string|max:255|unique:qtap_clients|unique:qtap_admins|unique:qtap_affiliates',
            'birth_date' => 'required|date',
            'email' => 'required|string|email|max:255|unique:qtap_clients|unique:qtap_admins|unique:qtap_affiliates',
            'password' => 'required|string|min:1',
            'user_type' => 'required|in:qtap_admins,qtap_clients,qtap_affiliates',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'بعض البيانات غير مكتملة أو غير صحيحة.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->all();

        if ($request->hasFile('img')) {
            $image = $request->file('img');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

            $uploadPath = match ($request->user_type) {
                'qtap_admins' => 'uploads/qtap_admins',
                'qtap_clients' => 'uploads/qtap_clients',
                'qtap_affiliates' => 'uploads/qtap_affiliate',
                default => 'uploads/others',
            };

            $image->move(public_path($uploadPath), $imageName);
            $data['img'] = $uploadPath . '/' . $imageName;
        }

        try {
            if ($request->user_type === 'qtap_admins') {
                $user = qtap_admins::create($data);
            } elseif ($request->user_type === 'qtap_clients') {
                $user = qtap_clients::create($data);
            } elseif ($request->user_type === 'qtap_affiliates') {


                // $data['code'] = strtoupper(Str::random(8));


                $user = qtap_affiliate::create($data);
            } else {
                throw new \Exception("نوع المستخدم غير صالح.");
            }

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'status' => 'success',
                'message' => 'تم تسجيل المستخدم بنجاح.',
                // 'token' => $token,
                'user' => $user,
            ], 201);
        } catch (QueryException $e) {
            // التحقق إذا كان الخطأ هو انتهاك القيد الفريد
            if ($e->getCode() == 23000) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'رقم الهاتف أو البريد الإلكتروني مستخدم مسبقًا.',
                ], 409);  // استخدام كود الحالة 409 تعني تضارب
            }

            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ غير متوقع.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }





    /**
     * تسجيل الدخول
     */


    public function login(Request $request)
    {



        $validator = Validator::make($request->all(), ([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'pin' => 'sometimes|string',
            'brunch_id' => 'sometimes|integer|exists:qtap_clients_brunchs,id',
            'user_type' => 'required|in:qtap_admins,qtap_clients,qtap_affiliates',
        ]));

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        if ($request->user_type != 'qtap_clients') {
            // dd($request->all());

            $credentials = $request->only('email', 'password',  'user_type');  // التحقق من الـ email و password
            $user = null;

            if ($token = Auth::guard('qtap_admins')->attempt($credentials)) {

                $user = Auth::guard('qtap_admins')->user();

                // dd($user);

                if ($user) {
                    return response()->json([
                        'token' => $token,
                        'user' => $user,
                    ]);
                }
            } elseif ($token = Auth::guard('qtap_affiliate')->attempt($credentials)) {

                $user = Auth::guard('qtap_affiliate')->user();

                if ($user->status !== 'active') {
                    return response()->json(['error' => 'User is not active'], 401);
                }


                affiliate_log::create([
                    'affiliate_id' => $user->id,
                    'status' => 'active',
                ]);


                if ($user) {
                    return response()->json([
                        'token' => $token,
                        'user' => $user,
                    ]);
                }
            } else {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        } else {


            $user =  restaurant_user_staff::where('pin', $request->pin)->where('email', $request->email)
                ->where('brunch_id', $request->brunch_id)->where('role', $request->role)->where('user_type', $request->user_type)
                ->first();


            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['error' => 'Unauthorized - Invalid pin or password or phone'], 401);
            }

            // إصدار التوكن
            $token = Auth::guard('restaurant_user_staff')->login($user);

            // تحقق إضافي من رقم الهاتف إذا كان role = delivery_rider
            if ($user->role == 'delivery_rider' && $request['phone'] && $request['phone'] != $user->phone) {
                return response()->json(['error' => 'Unauthorized - Invalid phone'], 401);
            }

            // جلب الفروع وربما إضافتها للرد
            $brunches = qtap_clients_brunchs::where('client_id', $user->user_id)->get();

            // سجل الدخول في logs
            users_logs::create([
                'user_id' => $user->id,
                'brunch_id' => $user->brunch_id,
                'status' => 'active',
            ]);

            return response()->json([
                'token' => $token,
                'user' => $user,
            ]);
        }
    }




    public function logout(Request $request)
    {


        if (auth()->check()) {


            if (auth()->user()->user_type == 'qtap_clients') {

                // تسجيل السجل عند تسجيل الخروج
                users_logs::create([
                    'user_id' => auth()->user()->id,
                    'brunch_id' => auth()->user()->brunch_id,
                    'action' => 'inactive',
                ]);
            } else if (auth()->user()->user_type == 'qtap_affiliates') {

                // تسجيل السجل عند تسجيل الخروج
                affiliate_log::create([
                    'user_id' => auth()->user()->id,
                    'action' => 'inactive',
                ]);
            }

            // إبطال التوكن الحالي (إذا كنت تستخدم JWT)
            JWTAuth::invalidate(JWTAuth::getToken());

            // تسجيل الخروج
            Auth::logout();

            return response()->json(['success' => true, 'message' => 'Logout successful']);
        }

        return response()->json(['success' => false, 'message' => 'No user logged in']);
    }
}
