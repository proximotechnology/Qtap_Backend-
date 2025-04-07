<?php

namespace App\Http\Controllers;

use App\Models\qtap_affiliate;
use App\Models\affiliate_payment_info;
use App\Models\affiliate_transactions;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\Hash;


class QtapAffiliateController extends Controller
{

    public function index()
    {
        $affiliates_active = qtap_affiliate::where('status', 'active')->get();
        $affiliates_inactive = qtap_affiliate::where('status', 'inactive')->get();

        return response()->json([
            'success' => true,
            'affiliates_active' => $affiliates_active,
            'affiliates_inactive' => $affiliates_inactive
        ]);
    }


    public function store(Request $request)
    {
        try {
            qtap_affiliate::where('email', $request->email)->where('status', 'inactive')->delete();

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'mobile' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:qtap_affiliates,email',
                'password' => 'required|string|min:1',
                'country' => 'nullable|string|max:255',
                'birth_date' => 'nullable|date',
                'user_type' => 'nullable|in:qtap_affiliates',
                'payment_way' => 'nullable|in:bank_account,wallet,credit_card',
            ]);


            $validatedData['password'] = Hash::make($validatedData['password']);
            $affiliate = qtap_affiliate::create($validatedData);


            $paymentInfo_data = $request->all();

            $paymentInfo_data['affiliate_id'] = $affiliate->id;

            $paymentInfo = affiliate_payment_info::create($paymentInfo_data);




            return response()->json(['status' => 'success', 'message' => 'تمت الإضافة بنجاح.', 'data' => compact('affiliate', 'paymentInfo')], 201);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'بيانات غير صحيحة.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'حدث خطأ.', 'error' => $e->getMessage()], 500);
        }
    }



    public function affiliate_transactions(){

        $transactions = affiliate_transactions::with('affiliate')->where('created_at', '>=', now()->subDays(30))->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'transactions' => $transactions
        ]);
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




    public function get_affiliate_info($id)
    {

        $affiliate = qtap_affiliate::with('payment_info')->find($id);

        if (!$affiliate) {
            return response()->json([
                'error' => 'affiliate not found'
            ], 404);
        }


        return response()->json([
            'success' => true,
            'affiliate' => $affiliate
        ]);
    }


    public function destroy($id)
    {
        try {
            $qtap_affiliate = qtap_affiliate::find($id);

            if (!$qtap_affiliate) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'البيانات غير موجودة.',
                ], 404);
            }
            $qtap_affiliate->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'تم حذف البيانات بنجاح.',
            ], 200);
        } catch (\Exception $e) {
            // في حالة حدوث أي خطأ آخر، نعرض رسالة الخطاء بالتفصيل
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطاء اثناء حذف البيانات.',
                'error_details' => $e->getMessage(),
            ], 500);
        }
    }
}
