<?php

namespace App\Http\Controllers;

use App\Models\qtap_affiliate;
use App\Models\affiliate_payment_info;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\Hash;


class QtapAffiliateController extends Controller
{

    public function index()
    {
        //
    }


    public function store(Request $request)
    {
        try {
            // التحقق من البيانات المدخلة
            $request->validate([
                'name' => 'required|string|max:255',
                'country' => 'nullable|string|max:255',
                'mobile' => 'required|string|max:255',
                'birth_date' => 'nullable|date',
                'email' => 'required|string|email|max:255|unique:qtap_affiliates,email',
                'password' => 'required|string|min:1',
                'user_type' => 'nullable|in:qtap_affiliates',
                'payment_way' => 'nullable|in:bank_account,wallet,credit_card',
            ]);

            // تشفير كلمة المرور
            $password = Hash::make($request->password);

            // إنشاء بيانات المستخدم
            $qtap_affiliate = qtap_affiliate::create([
                'name' => $request->name,
                'country' => $request->country,
                'mobile' => $request->mobile,
                'birth_date' => $request->birth_date,
                'email' => $request->email,
                'password' => $password,
                'user_type' => $request->user_type,
            ]);

            // بيانات طرق الدفع
            $paymentData = $request->only([
                'payment_way',
                'bank_name',
                'bank_account_number',
                'bank_account_name',
                'wallet_provider',
                'wallet_number',
                'credit_card_number',
                'credit_card_holder_name',
                'credit_card_expiration_date'
            ]);

            // إنشاء بيانات طرق الدفع المرتبطة بالمستخدم
            if (!empty($paymentData['payment_way'])) {
                affiliate_payment_info::create(array_merge($paymentData, [
                    'affiliate_id' => $qtap_affiliate->id,
                ]));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'تم إضافة البيانات بنجاح.',
                'data' => $qtap_affiliate,
            ], 201);
        } catch (\Exception $e) {
            // في حالة حدوث أي خطأ آخر، نعرض رسالة الخطأ بالتفصيل
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء إضافة البيانات.',
                'error_details' => $e->getMessage(),
            ], 500);
        } catch (ValidationException $e) {
            // إذا كانت الأخطاء بسبب التحقق من صحة البيانات
            return response()->json([
                'status' => 'error',
                'message' => 'بعض البيانات غير مكتملة أو غير صحيحة.',
                'errors' => $e->errors(),
            ], 422);
        }
    }





    public function update(Request $request, $id)
    {
        try {
          
            $qtap_affiliate = qtap_affiliate::find($id);

            // التحقق من البيانات المدخلة
            $request->validate([
                'name' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'mobile' => 'nullable|string|max:255',
                'birth_date' => 'nullable|date',
                'status' => 'nullable|in:active,inactive',
                'email' => 'nullable|string|email|max:255',
                'password' => 'nullable|string|min:1',
                'user_type' => 'nullable|in:qtap_affiliates',
                'payment_way' => 'nullable|in:bank_account,wallet,credit_card',
            ]);

            // إذا تم تقديم كلمة مرور جديدة، نقوم بتشفيرها
            if ($request->password) {
                $request->merge(['password' => Hash::make($request->password)]);
            }

            // تحديث بيانات المستخدم
            $qtap_affiliate->update($request->all());

            // تحديث أو إنشاء بيانات طرق الدفع
            $paymentData = $request->only([
                'payment_way',
                'bank_name',
                'bank_account_number',
                'bank_account_name',
                'wallet_provider',
                'wallet_number',
                'credit_card_number',
                'credit_card_holder_name',
                'credit_card_expiration_date'
            ]);

            $affiliatePaymentInfo = affiliate_payment_info::where('affiliate_id', $qtap_affiliate->id)->first();

            if ($affiliatePaymentInfo) {
                $affiliatePaymentInfo->update($paymentData);
            } else {


                affiliate_payment_info::create(array_merge($paymentData, [
                    'affiliate_id' => $qtap_affiliate->id,
                ]));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'تم تحديث البيانات بنجاح.',
            ], 200);
        } catch (\Exception $e) {
            // في حالة حدوث أي خطأ آخر، نعرض رسالة الخطأ بالتفصيل
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء تحديث البيانات.',
                'error_details' => $e->getMessage(),
            ], 500);
        } catch (ValidationException $e) {
            // إذا كانت الأخطاء بسبب التحقق من صحة البيانات
            return response()->json([
                'status' => 'error',
                'message' => 'بعض البيانات غير مكتملة أو غير صحيحة.',
                'errors' => $e->errors(),
            ], 422);
        }
    }







    public function destroy(qtap_affiliate $qtap_affiliate)
    {
        //
    }
}
