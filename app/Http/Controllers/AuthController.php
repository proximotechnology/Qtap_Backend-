<?php

namespace App\Http\Controllers;

use App\Models\qtap_admins;
use App\Models\qtap_affiliate;
use App\Models\clients_logs;
use App\Models\User;
use App\Models\qtap_clients;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

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
                $user = qtap_affiliate::create($data);
            } else {
                throw new \Exception("نوع المستخدم غير صالح.");
            }

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'status' => 'success',
                'message' => 'تم تسجيل المستخدم بنجاح.',
                'token' => $token,
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
         
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');
        $user = null;

        if ($token = Auth::guard('qtap_admins')->attempt($credentials)) {
            $user = Auth::guard('qtap_admins')->user();
        } elseif ($token = Auth::guard('qtap_clients')->attempt($credentials)) {
            $user = Auth::guard('qtap_clients')->user();
            if ($user->status !== 'active') {
                return response()->json(['error' => 'User is not active'], 401);
            }


            clients_logs::create([
                'client_id' => $user->id,
                'action' => 'login',
            ]);
        } elseif ($token = Auth::guard('qtap_affiliate')->attempt($credentials)) {
            
            $user = Auth::guard('qtap_affiliate')->user();
           
            if ($user->status !== 'active') {
                return response()->json(['error' => 'User is not active'], 401);
            }
        }

        if ($user) {
            return response()->json([
                'token' => $token,
                'user' => $user,
            ]);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }


    public function logout(Request $request)
    {

        clients_logs::create([
            'user_id' => Auth::id(),
            'action' => 'logout',
        ]);

        Auth::logout();
        return response()->json(['success' => true, 'message' => 'Logout successful']);
    }
}
