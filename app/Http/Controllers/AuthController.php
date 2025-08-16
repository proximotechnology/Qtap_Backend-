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

use App\Mail\OTPMail;

use Illuminate\Support\Facades\Mail;



use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth; // أضف هذا لضمان عمل Auth بشكل صحيح
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * تسجيل مستخدم جديد
     */


    public function resendOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'user_type' => 'required|in:qtap_clients,qtap_affiliate,qtap_admins'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'بيانات غير صالحة: ' . $validator->errors()->first(),
                'status' => false
            ], 422);
        }

        // تحديد نموذج المستخدم بناءً على النوع
        $userModel = match($request->user_type) {
            'qtap_admins' => qtap_admins::class,
            'qtap_affiliate' => qtap_affiliate::class,
            'qtap_clients' => qtap_clients::class,
        };

        // البحث عن المستخدم
        $user = $userModel::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'المستخدم غير موجود',
                'status' => false
            ], 404);
        }

        try {
            // إنشاء OTP جديد
            $newOTP = rand(100000, 999999);

            // تحديث OTP في قاعدة البيانات
            $user->update(['otp' => $newOTP]);

            // إرسال البريد الإلكتروني
            Mail::to($user->email)->send(new OTPMail($newOTP, 'كود التحقق الجديد'));

            return response()->json([
                'message' => 'تم إعادة إرسال كود التحقق بنجاح',
                'status' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء إعادة إرسال الكود: ' . $e->getMessage(),
                'status' => false
            ], 500);
        }
    }
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

            $data['img'] = 'storage/' . $data['img'];
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

            $otp = rand(100000, 999999);
            $user->update(['otp' => $otp]);
            Mail::to($user->email)->send(new OTPMail($otp, 'تأكيد البريد الإلكتروني'));

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


    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
            'pin' => 'sometimes|string',
            'brunch_id' => 'sometimes|integer|exists:qtap_clients_brunchs,id',
            'user_type' => 'required|in:qtap_admins,qtap_clients,qtap_affiliates',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->user_type != 'qtap_clients') {
            $credentials = $request->only('email', 'password', 'user_type');
            $user = null;

            if ($token = Auth::guard('qtap_admins')->attempt($credentials)) {
                $user = Auth::guard('qtap_admins')->user();
            } elseif ($token = Auth::guard('qtap_affiliate')->attempt($credentials)) {
                $user = Auth::guard('qtap_affiliate')->user();

                if ($user->status !== 'active') {
                    return response()->json(['error' => 'User is not active'], 401);
                }

                affiliate_log::create([
                    'affiliate_id' => $user->id,
                    'status' => 'active',
                ]);
            } else {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        } else {
            $user = restaurant_user_staff::where('pin', $request->pin)
                ->where('email', $request->email)
                ->where('brunch_id', $request->brunch_id)
                ->where('user_type', $request->user_type)
                ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['error' => 'Unauthorized - Invalid pin or password or phone'], 401);
            }

            $token = Auth::guard('restaurant_user_staff')->login($user);

            if ($user->role == 'delivery_rider' && $request['phone'] && $request['phone'] != $user->phone) {
                return response()->json(['error' => 'Unauthorized - Invalid phone'], 401);
            }

            users_logs::create([
                'user_id' => $user->id,
                'brunch_id' => $user->brunch_id,
                'status' => 'active',
            ]);
        }

        $response = response()->json([
            'token' => $token,
            'user' => $user,
        ]);

        return $response->cookie(
            'qutap_auth',
            $token,
            60 * 24 * 7, // 7 أيام
            '/',
            null, // للعمل على جميع النطاقات المحلية
            false, // secure
            false, // httpOnly
            false,
            'lax'
        );
    }

    public function checkAuth(Request $request)
    {
        try {
            if (!$token = $request->cookie('qutap_auth')) {
                return response()->json(['authenticated' => false], 401);
            }

            // تحديد الجارد بناءً على نوع المستخدم
            $user = Auth::guard('restaurant_user_staff')->setToken($token)->user();

            if (!$user) {
                return response()->json([
                    'authenticated' => true,
                    'user' => false
                ]);
            }

            return response()->json([
                'authenticated' => true,
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'authenticated' => false,
                'message' => 'Authentication error'
            ], 401);
        }
    }

    public function logout(Request $request)
    {
        if (auth()->check()) {
            if (auth()->user()->user_type == 'qtap_clients') {
                users_logs::create([
                    'user_id' => auth()->user()->id,
                    'brunch_id' => auth()->user()->brunch_id,
                    'action' => 'inactive',
                ]);
            } elseif (auth()->user()->user_type == 'qtap_affiliates') {
                affiliate_log::create([
                    'user_id' => auth()->user()->id,
                    'action' => 'inactive',
                ]);
            }

            JWTAuth::invalidate(JWTAuth::getToken());
            Auth::logout();

            return response()->json(['success' => true, 'message' => 'Logout successful'])
                ->cookie('qutap_auth', null, -1);
        }

        return response()->json(['success' => false, 'message' => 'No user logged in']);
    }


















































































































































    //---------------------------API RESET PASSWORD & VERIFY EMAIL----------------------------------------

    public function sendOTP(Request $request)
    {

        $otp = rand(100000, 999999);

        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'user_type' => 'required|in:qtap_clients,qtap_affiliate,qtap_admins'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'حدث خطاء اثناء التسجيل: ' . $validator->errors(),
                'status' => false
            ]);
        }

        if ($request->user_type == 'qtap_admins') {
            $user = qtap_admins::where('email', $request->email)->first();
        } elseif ($request->user_type == 'qtap_affiliate') {
            $user = qtap_affiliate::where('email', $request->email)->first();
        } elseif ($request->user_type == 'qtap_clients') {
            $user = qtap_clients::where('email', $request->email)->first();
        }

        if (!$user) {
            return response()->json([
                'message' => 'حدث خطاء اثناء التسجيل: ' . $validator->errors(),
                'status' => false
            ]);
        }



        $user->update(['otp' => $otp]);


        $data['otp'] = $otp;

        Mail::to($request->email)->send(new OTPMail($otp, 'test'));

        return response()->json([
            'message' => 'تم ارسال الكود بنجاح',
            'status' => true
        ]);
    }


    public function receiveOTP(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'otp' => 'required|digits:6',
            'user_type' => 'required|in:qtap_clients,qtap_affiliate,qtap_admins'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'حدث خطاء اثناء التسجيل: ' . $validator->errors(),
                'status' => false
            ]);
        }

        $otp_user = $request->otp;

        if ($request->user_type == 'qtap_admins') {
            $user = qtap_admins::where('otp', $otp_user)->first();
        } elseif ($request->user_type == 'qtap_affiliate') {
            $user = qtap_affiliate::where('otp', $otp_user)->first();
        } elseif ($request->user_type == 'qtap_clients') {
            $user = qtap_clients::where('otp', $otp_user)->first();
        }

        if (!$user) {
            return response()->json([
                'message' => 'الكود غير صحيح',
                'status' => false
            ]);
        }


        return response()->json([
            'message' => 'تم التحقق من الكود بنجاح',
            'status' => true
        ]);
    }


    public function resetpassword(Request $request)
    {

        $validator = validator($request->all(), [
            'password' => 'sometimes|confirmed',
            'otp' => 'required',
            'user_type' => 'required|in:qtap_clients,qtap_affiliate,qtap_admins'

        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'حدث خطاء اثناء التسجيل: ' . $validator->errors(),
                'status' => false
            ]);
        }

        if ($request->user_type == 'qtap_admins') {

            $user = qtap_admins::where('otp', $request->otp)->first();
        } elseif ($request->user_type == 'qtap_affiliate') {

            $user = qtap_affiliate::where('otp', $request->otp)->first();
        } elseif ($request->user_type == 'qtap_clients') {

            $user = qtap_clients::where('otp', $request->otp)->first();
            $staff = restaurant_user_staff::where('user_id', $user->id)->get();

        }


        if (!$user) {
            return response()->json([
                'message' => 'حدث خطاء اثناء التسجيل: ' . $validator->errors(),
                'status' => false
            ]);
        }
        // dd($request->all());

        $user->update([

            'password' => Hash::make($request->password),
            'otp' => null
        ]);

        if ($request->user_type == 'qtap_clients') {
            foreach ($staff as $item) {
                $item->update([
                    'password' => Hash::make($request->password),
                ]);
            }
        }


        return response()->json([
            'message' => 'تم تغيير كلمة المرور بنجاح',
            'status' => true
        ]);
    }

    public function verfiy_email(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'otp' => 'required|digits:6',
            'user_type' => 'required|in:qtap_clients,qtap_affiliate,qtap_admins'

        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'حدث خطاء اثناء التسجيل: ' . $validator->errors(),
                'status' => false
            ]);
        }

        $otp_user = $request->otp;


        if ($request->user_type == 'qtap_admins') {
            $user = qtap_admins::where('otp', $otp_user)->first();
        } elseif ($request->user_type == 'qtap_affiliate') {
            $user = qtap_affiliate::where('otp', $otp_user)->first();
        } elseif ($request->user_type == 'qtap_clients') {
            $user = qtap_clients::where('otp', $otp_user)->first();
        }



        if (!$user) {
            return response()->json([
                'message' => 'الكود غير صحيح',
                'status' => false
            ]);
        }




        $user->update([
            'email_verified_at' => now(),
            'otp' => null
        ]);

        return response()->json([
            'message' => 'تم التحقق من الكود بنجاح',
            'status' => true
        ]);
    }
}
