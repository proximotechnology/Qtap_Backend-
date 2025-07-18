<?php

namespace App\Http\Controllers;

use App\Models\ClientPricing;
use App\Models\qtap_clients;
use App\Models\pricing;
use App\Models\SubscriptionChangeRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientPricingController extends Controller
{
    /**
     * عرض جميع الاشتراكات مع إمكانية الفلترة
     */
    public function index(Request $request)
    {
        $query = ClientPricing::with(['client', 'pricing'])
            ->latest();

        // تطبيق الفلاتر إذا وجدت
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('pricing_id')) {
            $query->where('pricing_id', $request->pricing_id);
        }

        if ($request->has('payment_methodes')) {
            $query->where('payment_methodes', $request->payment_methodes);
        }

        if ($request->has('pricing_way')) {
            $query->where('pricing_way', $request->pricing_way);
        }

        $subscriptions = $query->paginate(10);

        // بيانات إضافية للفلترة
        $clients = qtap_clients::select('id', 'name')->get();
        $pricings = pricing::select('id', 'name')->get();
        $statuses = ['active', 'inactive', 'expired', 'pending'];
        $paymentMethods = ['cash', 'credit_card', 'bank_transfer']; // تعديل حسب طرق الدفع المتاحة
        $pricingWays = ['monthly_price', 'yearly_price'];

        return response()->json([
            'success' => true,
            'subscriptions' => $subscriptions,
            'filters' => [
                'clients' => $clients,
                'pricings' => $pricings,
                'statuses' => $statuses,
                'payment_methods' => $paymentMethods,
                'pricing_ways' => $pricingWays
            ]
        ]);
    }



    public function clientSubscriptions(Request $request)
    {
        try {
            // جلب العميل المسجل من نظام المصادقة باستخدام الجارد الصحيح
            $user = Auth::guard('restaurant_user_staff')->user();

            // التحقق من وجود المستخدم المصادق عليه
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - Please login first'
                ], 401);
            }

            // البحث عن العميل في جدول qtap_clients باستخدام user_id
            $client = qtap_clients::where('email', $user->email)->first();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }

            $query = ClientPricing::with(['pricing'])
                        ->where('client_id', $client->id)
                        ->latest();

            // تطبيق الفلاتر
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('pricing_id')) {
                $query->where('pricing_id', $request->pricing_id);
            }

            if ($request->has('payment_methodes')) {
                $query->where('payment_methodes', $request->payment_methodes);
            }

            if ($request->has('pricing_way')) {
                $query->where('pricing_way', $request->pricing_way);
            }

            $subscriptions = $query->paginate(10);

            // بيانات الفلترة
            $pricings = pricing::select('id', 'name')->get();
            $statuses = ['active', 'inactive', 'expired', 'pending'];
            $paymentMethods = ['cash', 'credit_card', 'bank_transfer'];
            $pricingWays = ['monthly_price', 'yearly_price'];

            return response()->json([
                'success' => true,
                'subscriptions' => $subscriptions,
                'filters' => [
                    'pricings' => $pricings,
                    'statuses' => $statuses,
                    'payment_methods' => $paymentMethods,
                    'pricing_ways' => $pricingWays
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show_C($id)
    {
        try {
            // جلب العميل المسجل من نظام المصادقة
            $user = Auth::guard('restaurant_user_staff')->user();

            // التحقق من وجود المستخدم المصادق عليه
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - Please login first'
                ], 401);
            }

            // البحث عن العميل في جدول qtap_clients
            $client = qtap_clients::where('email', $user->email)->first();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }

            // جلب الاشتراك مع التحقق أنه يخص هذا العميل فقط
            $subscription = ClientPricing::with(['pricing'])
                            ->where('id', $id)
                            ->where('client_id', $client->id)
                            ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found or does not belong to you'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'subscription' => $subscription
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function activateSubscription($id)
    {
        $subscription = ClientPricing::findOrFail($id);

        // حساب تاريخ الانتهاء بناءً على pricing_way
        $expiredAt = null;
        if ($subscription->pricing_way === 'monthly_price') {
            $expiredAt = Carbon::now()->addMonth();
        } elseif ($subscription->pricing_way === 'yearly_price') {
            $expiredAt = Carbon::now()->addYear();
        }

        $subscription->update([
            'status' => 'active',
            'expired_at' => $expiredAt
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تفعيل الاشتراك بنجاح',
            'subscription' => $subscription
        ]);
    }

    /**
     * عرض اشتراك معين
     */
    public function show($id)
    {
        $subscription = ClientPricing::with(['client', 'pricing'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'subscription' => $subscription
        ]);
    }

    /**
     * تحديث بيانات الاشتراك
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'pricing_id' => 'sometimes|exists:pricings,id',
            'status' => 'sometimes|in:Active,Inactive,Expired,Pending',
            'payment_methodes' => 'sometimes|string',
            'pricing_way' => 'sometimes|in:monthly_price,yearly_price',
            'ramin_order' => 'sometimes|integer'
        ]);

        $subscription = ClientPricing::findOrFail($id);
        $subscription->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الاشتراك بنجاح',
            'subscription' => $subscription
        ]);
    }

    /**
     * حذف اشتراك
     */
    public function destroy($id)
    {
        $subscription = ClientPricing::findOrFail($id);
        $subscription->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الاشتراك بنجاح'
        ]);
    }



    public function requestSubscriptionChange(Request $request, $clinet_pricing_id)
    {
        $user = Auth::guard('restaurant_user_staff')->user();

        // التحقق من وجود المستخدم المصادق عليه
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Please login first'
            ], 401);
        }

        // البحث عن العميل في جدول qtap_clients
            $client = qtap_clients::where('email', $user->email)->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found'
            ], 404);
        }

        // جلب الاشتراك مع التحقق أنه يخص هذا العميل فقط
        $subscription = ClientPricing::with(['pricing'])
                        ->where('id', $clinet_pricing_id)
                        ->where('client_id', $client->id)
                        ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found or does not belong to you'
            ], 404);
        }

        // التحقق من وجود طلب معلق لنفس الاشتراك
        $existingRequest = SubscriptionChangeRequest::where('client_pricing_id', $clinet_pricing_id)
                            ->where('status', 'pending')
                            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending request for this subscription. Please wait for admin approval.',
                'existing_request_id' => $existingRequest->id
            ], 400);
        }

        $request->validate([
            'action_type' => 'required|in:renew,upgrade',
            'new_pricing_id' => 'required_if:action_type,upgrade,downgrade|exists:pricings,id',
            'pricing_way' => 'required|in:monthly_price,yearly_price',
            'pricing_way' => 'required|in:cash,wallet',

        ]);

        // إنشاء طلب معلق بانتظار الموافقة
        $changeRequest = SubscriptionChangeRequest::create([
            'client_pricing_id' => $clinet_pricing_id,
            'action_type' => $request->action_type,
            'new_pricing_id' => $request->new_pricing_id ?? null,
            'pricing_way' => $request->pricing_way,
            'payment_method' => $request->payment_method,

            'status' => 'pending',
            'requested_at' => now()
        ]);

        // إرسال إشعار للأدمن
        // Admin::all()->each->notify(new NewSubscriptionChangeRequest($changeRequest));

        return response()->json([
            'success' => true,
            'message' => 'Change request submitted. Waiting for admin approval.',
            'request_id' => $changeRequest->id
        ]);
    }


    public function getPendingChangeRequests(Request $request)
    {
        try {
            // التحقق من المصادقة
            $user = Auth::guard('restaurant_user_staff')->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }

            // جلب العميل مع التحقق من وجوده
            $client = qtap_clients::where('email', $user->email)->first();
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client account not found'
                ], 404);
            }

            // بناء الاستعلام مع العلاقات
            $requests = SubscriptionChangeRequest::with([
                    'ClientPricing.pricing', // الاشتراك الحالي وباقته
                    'Pricing' // الباقة الجديدة (إذا وجدت)
                ])
                ->whereHas('ClientPricing', function($query) use ($client) {
                    $query->where('client_id', $client->id);
                })
                ->where('status', 'pending')
                ->latest('requested_at')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'requests' => $requests,
                'message' => 'Pending requests retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function cancelPendingRequest(Request $request, $requestId)
    {
        try {
            // التحقق من المصادقة
            $user = Auth::guard('restaurant_user_staff')->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }

            // جلب العميل - التعديل هنا لاستخدام user_id بدلاً من البريد
            $client = qtap_clients::find($user->user_id);
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client account not found'
                ], 404);
            }

            // البحث عن الطلب مع التحقق أنه يخص العميل وأنه pending
            $changeRequest = SubscriptionChangeRequest::with(['ClientPricing'])
                ->where('id', $requestId)
                ->where('status', 'pending')
                ->whereHas('ClientPricing', function($query) use ($client) {
                    $query->where('client_id', $client->id);
                })
                ->first();

            if (!$changeRequest) {
                // إضافة لوج للتحقق من الخطأ
                Log::error('Failed to find request', [
                    'requestId' => $requestId,
                    'clientId' => $client->id,
                    'existingRequests' => SubscriptionChangeRequest::where('id', $requestId)->get()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Request not found, already processed, or does not belong to you'
                ], 404);
            }

            // التعديل هنا: استخدام soft delete إذا كان مفعلاً أو حذف نهائي
            $changeRequest->delete();

            return response()->json([
                'success' => true,
                'message' => 'Request cancelled successfully',
                'cancelled_request' => $changeRequest
            ]);

        } catch (\Exception $e) {
            Log::error('Error cancelling request: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel request',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    public function getPendingChangeRequestsAdmin(Request $request)
    {
        try {

            // بناء الاستعلام مع العلاقات
            $requests = SubscriptionChangeRequest::with([
                    'ClientPricing.pricing', // الاشتراك الحالي وباقته
                    'Pricing' // الباقة الجديدة (إذا وجدت)
                ])
                ->latest('requested_at')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'requests' => $requests,
                'message' => 'Pending requests retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function showPendingRequestAdmin($id)
    {
        try {
            // البحث عن الطلب مع العلاقات المرتبطة
            $request = SubscriptionChangeRequest::with([
                'ClientPricing.client',  // الاشتراك الحالي والعميل
                'ClientPricing.pricing', // الباقة الحالية
                'Pricing'                // الباقة الجديدة (إذا وجدت)
            ])->find($id);

            if (!$request) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'request' => $request,
                'message' => 'Request details retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve request details',
                'error' => $e->getMessage()
            ], 500);
        }
    }




















    public function updateRequestStatus(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            // التحقق من صحة البيانات
            $request->validate([
                'status' => 'required|in:approved,rejected',
            ]);

            // جلب طلب التغيير مع العلاقات
            $changeRequest = SubscriptionChangeRequest::with([
                'ClientPricing.pricing',
                'Pricing'
            ])->findOrFail($id);

            // التحقق من أن الطلب معلق
            if ($changeRequest->status != 'pending') {
                throw new \Exception('Request already processed', 400);
            }

            // تحديث حالة الطلب
            $changeRequest->update([
                'status' => $request->status,
            ]);

            // إذا تم القبول، تنفيذ التغيير
            if ($request->status == 'approved') {
                $currentSub = $changeRequest->ClientPricing;
                $currentPricing = $currentSub->pricing;
                $newPricing = $changeRequest->Pricing;

                // التحقق من أن الباقة فعالة
                if ($changeRequest->action_type == 'renew' && (!$currentPricing || $currentPricing->is_active != 'active')) {
                    throw new \Exception('Current pricing plan is inactive', 400);
                }

                if ($changeRequest->action_type == 'upgrade' && (!$newPricing || $newPricing->is_active != 'active')) {
                    throw new \Exception('New pricing plan is inactive', 400);
                }

                // معالجة التجديد
                if ($changeRequest->action_type == 'renew') {
                    $isExpired = $currentSub->status == 'expired';
                    $remainingDays = !$isExpired ? max(0, Carbon::now()->diffInDays($currentSub->expired_at, false)) : 0;

                    $currentSub->update([
                        'ramin_order' => $isExpired
                            ? $currentPricing->orders_limit
                            : $currentSub->ramin_order + $currentPricing->orders_limit,
                        'expired_at' => $currentSub->pricing_way == 'monthly_price'
                            ? now()->addMonth()->addDays($remainingDays)
                            : now()->addYear()->addDays($remainingDays),
                        'status' => 'active',
                        'payment_methodes' => $changeRequest->payment_methodes,
                        'pricing_way' => $changeRequest->pricing_way
                    ]);
                }
                // معالجة الترقية - تم التعديل هنا
                elseif ($changeRequest->action_type == 'upgrade') {
                    $isExpired = $currentSub->status == 'expired';
                    $remainingValue = !$isExpired ? $this->calculateRemainingValue($currentSub) : 0;

                    // إنشاء اشتراك جديد
                    $newSubscription = ClientPricing::create([
                        'client_id' => $currentSub->client_id,
                        'pricing_id' => $newPricing->id,
                        'status' => 'active',
                        'ramin_order' => $newPricing->orders_limit + $remainingValue,
                        'expired_at' => $changeRequest->pricing_way == 'monthly_price'
                            ? now()->addMonth()
                            : now()->addYear(),
                        'payment_methodes' => $changeRequest->payment_methodes,
                        'pricing_way' => $changeRequest->pricing_way,
                        'previous_subscription_id' => $currentSub->id
                    ]);

                    // حذف الاشتراك القديم بدلاً من تعطيله
                    $currentSub->delete(); // تم التغيير هنا من update إلى delete
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Request processed successfully',
                'data' => $changeRequest->fresh(['ClientPricing', 'Pricing'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $statusCode = in_array($e->getCode(), [400, 403]) ? $e->getCode() : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $statusCode == 500 ? $e->getMessage() : null
            ], $statusCode);
        }
    }

    protected function calculateRemainingValue($subscription)
    {
        $totalPeriod = $subscription->pricing_way == 'monthly_price' ? 30 : 365;
        $remainingDays = max(0, Carbon::now()->diffInDays($subscription->expired_at, false));

        return (int) round(($remainingDays / $totalPeriod) * $subscription->pricing->orders_limit);
    }
}
