<?php

namespace App\Http\Controllers;

use App\Models\qtap_clients;
use App\Models\qtap_clients_brunchs;
use App\Models\payment_services;
use App\Models\contact_info;
use App\Models\serving_ways;
use App\Models\workschedule;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class QtapClientsController extends Controller
{
    public function index()
    {
        $qtap_clients = qtap_clients::all();
        return response()->json([
            'success' => true,
            'qtap_clients' => $qtap_clients
        ]);
    }

    // public function store(Request $request)
    // {


    //     try {

    //         $validatedData = $request->validate([
    //             'name' => 'required|string|max:255',
    //             'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    //             'country' => 'nullable|string|max:255',
    //             'mobile' => 'required|string|max:255',
    //             'birth_date' => 'nullable|date',
    //             'email' => 'required|string|email|max:255|unique:qtap_clients,email',
    //             'status' => 'nullable|in:active,inactive',
    //             'password' => 'required|string|min:8',
    //             'user_type' => 'nullable|in:qtap_clients',
    //             'payment_way' => 'nullable|in:bank_account,wallet,credit_card',
    //         ]);

    //         $validatedData['password'] = Hash::make($request->password);

    //         if ($request->hasFile('img')) {
    //             $imagePath = $request->file('img')->store('uploads/clients', 'public');
    //             $validatedData['img'] = $imagePath;
    //         }

    //         $new_client = qtap_clients::create([
    //             'name' => $validatedData['name'],
    //             'img' => $validatedData['img'] ?? null,
    //             'country' => $validatedData['country'] ?? null,
    //             'mobile' => $validatedData['mobile'],
    //             'birth_date' => $validatedData['birth_date'] ?? null,
    //             'email' => $validatedData['email'],
    //             'password' => $validatedData['password'],
    //             'user_type' => $validatedData['user_type'] ?? null,
    //         ]);

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'add new client successfully but is not active',
    //             'data' => $new_client,
    //         ], 201);
    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Some data is incomplete or incorrect.',
    //             'errors' => $e->errors(),
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'An error occurred while adding data.',
    //             'error_details' => $e->getMessage(),
    //         ], 500);
    //     }
    // }




    public function store(Request $request)
    {
        try {
            // التحقق من صحة البيانات الأساسية للعميل
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'country' => 'nullable|string|max:255',
                'mobile' => 'required|string|max:255',
                'birth_date' => 'nullable|date',
                'email' => 'required|string|email|max:255|unique:qtap_clients,email',
                'status' => 'nullable|in:active,inactive',
                'password' => 'required|string|min:1',
                'user_type' => 'nullable|in:qtap_clients',
                'contact_info' => 'sometimes|array',
            ]);

            $validatedData['password'] = Hash::make($request->password);

            if ($request->hasFile('img')) {
                $imagePath = $request->file('img')->store('uploads/clients', 'public');
                $validatedData['img'] = $imagePath;
            }

            // إنشاء العميل الجديد
            $new_client = qtap_clients::create([
                'name' => $validatedData['name'],
                'img' => $validatedData['img'] ?? null,
                'country' => $validatedData['country'] ?? null,
                'mobile' => $validatedData['mobile'],
                'birth_date' => $validatedData['birth_date'] ?? null,
                'email' => $validatedData['email'],
                'password' => $validatedData['password'],
                'user_type' => $validatedData['user_type'] ?? null,
                'payment_method' => $branchData['payment_method'] ?? null,

            ]);

            // معالجة الفروع وإضافتها
            $branches = collect($request->all())->filter(function ($value, $key) {
                return Str::startsWith($key, 'brunch'); // البحث عن المفاتيح التي تبدأ بـ "brunch"
            });

            foreach ($branches as $branchData) {



                $branch = qtap_clients_brunchs::create([
                    'client_id' => $new_client->id,
                    'currency_id' => $branchData['currency_id'] ?? null,
                    'pricing_id' => $branchData['pricing_id'] ?? null,
                    'discount_id' => $branchData['discount_id'] ?? null,
                    'business_name' => $branchData['business_name'] ?? null,
                    'business_country' => $branchData['business_country'] ?? null,
                    'business_city' => $branchData['business_city'] ?? null,
                    'latitude' => $branchData['latitude'] ?? null,
                    'longitude' => $branchData['longitude'] ?? null,
                    'business_format' => $branchData['business_format'] ?? null,
                    'menu_design' => $branchData['menu_design'] ?? null,
                    'default_mode' => $branchData['default_mode'] ?? null,
                    'payment_time' => $branchData['payment_time'] ?? null,
                    'call_waiter' => $branchData['call_waiter'] ?? null,
                ]);


                // تحديث معلومات الاتصال
                if (isset($branchData['contact_info'])) {

                    // الحصول على معلومات الاتصال من الطلب
                    $info = isset($branchData['contact_info']) ? $branchData['contact_info'] : [];

                    if (!empty($info)) {
                        // معالجة القيم لتجنب الأخطاء
                        $contactData = [
                            'brunch_id' => $branch->id,
                            'business_phone' => is_array($info['business_phone']) ? implode(', ', $info['business_phone']) : $info['business_phone'],
                            'business_email' => is_array($info['business_email']) ? implode(', ', $info['business_email']) : $info['business_email'],
                            'website' => is_array($info['website']) ? implode(', ', $info['website']) : $info['website'],
                            'facebook' => is_array($info['facebook']) ? implode(', ', $info['facebook']) : $info['facebook'],
                            'twitter' => is_array($info['twitter']) ? implode(', ', $info['twitter']) : $info['twitter'],
                            'instagram' => is_array($info['instagram']) ? implode(', ', $info['instagram']) : $info['instagram'],
                            'address' => is_array($info['address']) ? implode(', ', $info['address']) : $info['address'],
                        ];

                        // إنشاء سجل جديد بمعلومات الاتصال
                        $contact = contact_info::create($contactData);
                    }
                }






                // تخزين طرق العمل (workschedules) إذا كانت موجودة
                if (isset($branchData['workschedules'])) {
                    foreach ($branchData['workschedules'] as $day => $times) {
                        workschedule::create([
                            'brunch_id' => $branch->id,
                            'day' => $day,
                            'opening_time' => $times[0] ?? null,
                            'closing_time' => $times[1] ?? null,
                        ]);
                    }
                }



                // تخزين طرق التقديم (serving_ways)
                if (isset($branchData['serving_ways'])) {
                    foreach ($branchData['serving_ways'] as $servingWay) {
                        $data = ['brunch_id' => $branch->id, 'name' => $servingWay];
                        if ($servingWay === 'dine_in') {
                            $data['tables_number'] = $branchData['tables_number'] ?? null;
                        }
                        serving_ways::create($data);
                    }
                }



                // تخزين وسائل الدفع (payment_services)
                if (isset($branchData['payment_services'])) {
                    foreach ($branchData['payment_services'] as $payment_services) {
                        $data = ['brunch_id' => $branch->id, 'name' => $payment_services];

                        payment_services::create($data);
                    }
                }
            }


            return response()->json([
                'status' => 'success',
                'message' => 'Client and branches added successfully.',
                'data' => $new_client,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some data is incomplete or incorrect.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while adding data.',
                'error_details' => $e->getMessage(),
            ], 500);
        }
    }






    public function update_profile(Request $request, $id)
    {
        try {

            $brunch_id = $request->brunch_id;

            // التحقق من وجود الفرع
            $brunch = qtap_clients_brunchs::find($brunch_id);
            if (!$brunch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch not found.',
                ]);
            }

            // التحقق من وجود العميل
            $client = qtap_clients::find($id);
            if (!$client) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Client not found.',
                ]);
            }

            // التحقق من صحة البيانات
            $validatedData = $request->validate([

                'name' => 'sometimes|string|max:255',
                'mobile' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:qtap_clients,email,' . $id,
                'birth_date' => 'sometimes|date',
                'country' => 'sometimes|string|max:255',
                'password' => 'sometimes|string|min:1',
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',


                'business_name' => 'sometimes|string|max:255',
                'business_country' => 'sometimes|string|max:255',
                'business_city' => 'sometimes|string|max:255',
                'longitude' => 'sometimes|numeric',
                'latitude' => 'sometimes|numeric',
                'currency_id' => 'sometimes|integer',
                'business_format' => 'sometimes|string|max:255',
                'tables_number' => 'sometimes|integer',


                'contact_info' => 'sometimes|array',
            ]);

            // تشفير كلمة المرور إذا تم تقديمها
            if ($request->has('password')) {
                $validatedData['password'] = Hash::make($request->password);
            }

            // حفظ الصورة إذا تم رفعها
            if ($request->hasFile('img')) {
                $imagePath = $request->file('img')->store('uploads/clients/' . $id, 'public');
                $validatedData['img'] = 'uploads/clients/' . $id . '/' . basename($imagePath);
            }

            // تحديث بيانات العميل
            $client->update($validatedData);

            // تحديث بيانات الفرع
            $brunch->update($validatedData);

            // تحديث معلومات الاتصال
            if ($request->has('contact_info')) {
                // حذف السجلات القديمة للفرع
                contact_info::where('brunch_id', $brunch_id)->delete();

                // الحصول على معلومات الاتصال من الطلب
                $info = $request->input('contact_info', []);

                if (!empty($info)) {
                    // معالجة القيم لتجنب الأخطاء
                    $contactData = [
                        'brunch_id' => $brunch_id,
                        'business_phone' => is_array($info['business_phone']) ? implode(', ', $info['business_phone']) : $info['business_phone'],
                        'business_email' => is_array($info['business_email']) ? implode(', ', $info['business_email']) : $info['business_email'],
                        'website' => is_array($info['website']) ? implode(', ', $info['website']) : $info['website'],
                        'facebook' => is_array($info['facebook']) ? implode(', ', $info['facebook']) : $info['facebook'],
                        'twitter' => is_array($info['twitter']) ? implode(', ', $info['twitter']) : $info['twitter'],
                        'instagram' => is_array($info['instagram']) ? implode(', ', $info['instagram']) : $info['instagram'],
                        'address' => is_array($info['address']) ? implode(', ', $info['address']) : $info['address'],
                    ];

                    // إنشاء سجل جديد بمعلومات الاتصال
                    $contact = contact_info::create($contactData);

                    // تحقق من نجاح العملية
                    if (!$contact) {
                        // استعادة السجل إذا فشلت عملية الإنشاء
                        contact_info::where('brunch_id', $brunch_id)->onlyTrashed()->restore();
                    } else {
                        // حذف السجلات المحذوفة بشكل دائم إذا نجحت العملية
                        contact_info::where('brunch_id', $brunch_id)->onlyTrashed()->forceDelete();
                    }
                }
            }




            return response()->json([
                'status' => 'success',
                'message' => 'Client and branch updated successfully.',
                'client_data' => $client,
                'contact_business_data' => $contact,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some data is incomplete or incorrect.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating data.',
                'error_details' => $e->getMessage(),
            ], 500);
        }
    }



    public function update_menu(Request $request, $id)
    {
        try {

            $brunch_id = $request->brunch_id;

            $brunch = qtap_clients_brunchs::find($brunch_id);

            if (!$brunch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Brunch not found.',
                ], 404);
            }

            $validatedData = $request->validate([
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'cover' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'default_mode' => 'sometimes|string|max:255',
                'menu_design' => 'sometimes|string|max:255',
                'tables_number' => 'sometimes|numeric|max:255',
                'serving_ways' => 'sometimes|array',
                'workschedules' => 'sometimes|array',
                'payment_services' => 'sometimes|array',
                'call_waiter' => 'sometimes|in:active,inactive',
                'payment_time' => 'sometimes|in:before,after',
            ]);

            // معالجة الصور
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('uploads/client' . $id . '/brunch' . $brunch_id, 'public');
                $validatedData['logo'] = $logoPath;
            }

            if ($request->hasFile('cover')) {
                $coverPath = $request->file('cover')->store('uploads/client' . $id . '/brunch' . $brunch_id, 'public');
                $validatedData['cover'] = $coverPath;
            }

            // تحديث بيانات الفرع
            $brunch->update($validatedData);

            // تحديث طرق التقديم
            if ($request->has('serving_ways')) {
                $serving_ways = [];
                $created_serving_ways = []; // مصفوفة لحفظ السجلات التي تم إنشاؤها
                serving_ways::where('brunch_id', $brunch_id)->delete();

                foreach ($validatedData['serving_ways'] as $servingWay) {
                    $data = ['brunch_id' => $brunch_id, 'name' => $servingWay];
                    if ($servingWay === 'dine_in') {
                        $data['tables_number'] = $validatedData['tables_number'] ?? null;
                    }

                    // إنشاء السجل وإضافته إلى المصفوفة
                    $created_record = serving_ways::create($data);
                    if ($created_record) {
                        $created_serving_ways[] = $created_record; // إضافة السجل إلى المصفوفة
                    }
                }

                // تحقق من نجاح العملية
                if (empty($created_serving_ways)) {
                    // استعادة السجل إذا فشلت عملية الإنشاء
                    serving_ways::where('brunch_id', $brunch_id)->onlyTrashed()->restore();
                } else {
                    // حذف السجلات المحذوفة بشكل دائم إذا نجحت العملية
                    serving_ways::where('brunch_id', $brunch_id)->onlyTrashed()->forceDelete();
                }
            }

            // تحديث جداول العمل
            if ($request->has('workschedules')) {
                $workschedule = [];
                $created_workschedules = []; // مصفوفة لحفظ السجلات التي تم إنشاؤها

                workschedule::where('brunch_id', $brunch_id)->delete();

                foreach ($validatedData['workschedules'] as $day => $times) {
                    $data = [
                        'brunch_id' => $brunch_id,
                        'day' => $day,
                        'opening_time' => $times[0] ?? null,
                        'closing_time' => $times[1] ?? null,
                    ];

                    // إنشاء السجل وإضافته إلى المصفوفة
                    $created_record = workschedule::create($data);
                    if ($created_record) {
                        $created_workschedules[] = $created_record; // إضافة السجل إلى المصفوفة
                    }
                }

                // تحقق من نجاح العملية
                if (empty($created_workschedules)) {
                    // استعادة السجل إذا فشلت عملية الإنشاء
                    workschedule::where('brunch_id', $brunch_id)->onlyTrashed()->restore();
                } else {
                    // حذف السجلات المحذوفة بشكل دائم إذا نجحت العملية
                    workschedule::where('brunch_id', $brunch_id)->onlyTrashed()->forceDelete();
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Client and branches updated successfully.',
                'workschedule' => $created_workschedules,
                'serving_ways' => $created_serving_ways,
                'brunch' => $brunch,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some data is incomplete or incorrect.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating data.',
                'error_details' => $e->getMessage(),
            ], 500);
        }
    }




    public function update_payment(Request $request, $id)
    {
        try {


            $brunch_id = $request->brunch_id;

            $brunch = qtap_clients_brunchs::find($brunch_id);

            if (!$brunch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'brunch not found.',
                ]);
            }




            $client = qtap_clients::find($id);

            if (!$client) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Client not found.',
                ]);
            }


            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'mobile' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:qtap_clients,email,' . $id,
                'birth_date' => 'sometimes|date',
                'country' => 'sometimes|string|max:255',
                'password' => 'sometimes|string|min:1',
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',



                'business_name' => 'sometimes|string|max:255',
                'business_phone' => 'sometimes|string|max:255',
                'business_email' => 'sometimes|string|email|max:255',
                'country' => 'sometimes|string|max:255',
                'city' => 'sometimes|string|max:255',
                'longitude' => 'sometimes|decimal|max:255',
                'longitude' => 'sometimes|decimal|max:255',
                'currency_id' => 'sometimes|number|max:255',
                'tables_number' => 'sometimes|number|max:255',



                'business_format' => 'sometimes|string|max:255',
                'menu_design' => 'sometimes|string|max:255',
                'default_mode' => 'sometimes|string|max:255',
                'payment_time' => 'sometimes|string|max:255',
                'call_waiter' => 'sometimes|string|max:255',
                'status' => 'sometimes|in:active,inactive',
            ]);



            if ($request->has('password')) {
                $validatedData['password'] = Hash::make($request->password);
            }

            if ($request->hasFile('img')) {
                $imagePath = $request->file('img')->store('uploads/clients', 'public');
                $validatedData['img'] = $imagePath;
            }

            // تحديث بيانات العميل
            $client->update($validatedData);









            // تحديث الفروع إذا تم إرسالها
            $branches = collect($request->all())->filter(function ($value, $key) {
                return Str::startsWith($key, 'brunch'); // البحث عن المفاتيح التي تبدأ بـ "brunch"
            });

            foreach ($branches as $branchId => $branchData) {
                preg_match('/\d+/', $branchId, $matches);
                $number = $matches[0] ?? null;

                if ($number) {
                    $branch = qtap_clients_brunchs::find($number);

                    if ($branch) {
                        // تحديث بيانات الفرع
                        $branch->update([
                            'currency_id' => $branchData['currency_id'] ?? $branch->currency_id,
                            // 'pricing_id' => $branchData['pricing_id'] ?? $branch->pricing_id,
                            // 'discount_id' => $branchData['discount_id'] ?? $branch->discount_id,
                            'business_name' => $branchData['business_name'] ?? $branch->business_name,
                            'country' => $branchData['country'] ?? $branch->country,
                            'city' => $branchData['city'] ?? $branch->city,
                            'latitude' => $branchData['latitude'] ?? $branch->latitude,
                            'longitude' => $branchData['longitude'] ?? $branch->longitude,
                            'business_format' => $branchData['business_format'] ?? $branch->business_format,
                            'menu_design' => $branchData['menu_design'] ?? $branch->menu_design,
                            'default_mode' => $branchData['default_mode'] ?? $branch->default_mode,
                            'payment_time' => $branchData['payment_time'] ?? $branch->payment_time,
                            'call_waiter' => $branchData['call_waiter'] ?? $branch->call_waiter,
                        ]);


                        // حذف البيانات المرتبطة بالفرع إذا كانت موجودة
                        contact_info::where('brunch_id', $branch->id)->forcedelete();
                        workschedule::where('brunch_id', $branch->id)->forcedelete();
                        serving_ways::where('brunch_id', $branch->id)->forcedelete();
                        payment_services::where('brunch_id', $branch->id)->forcedelete();


                        // إضافة بيانات الاتصال
                        contact_info::create([
                            'brunch_id' => $branch->id,
                            'phone' => $branchData['business_phone'] ?? null,
                            'email' => $branchData['business_email'] ?? null,
                            'website' => $branchData['website'] ?? null,
                            'facebook' => $branchData['facebook'] ?? null,
                            'twitter' => $branchData['twitter'] ?? null,
                            'instagram' => $branchData['instagram'] ?? null,
                            'address' => $branchData['address'] ?? null,
                        ]);

                        // إضافة الجدول الزمني للعمل
                        if (isset($branchData['workschedules'])) {
                            foreach ($branchData['workschedules'] as $day => $times) {
                                workschedule::create([
                                    'brunch_id' => $branch->id,
                                    'day' => $day,
                                    'opening_time' => $times[0] ?? null,
                                    'closing_time' => $times[1] ?? null,
                                ]);
                            }
                        }

                        // إضافة طرق التقديم
                        if (isset($branchData['serving_ways'])) {
                            foreach ($branchData['serving_ways'] as $servingWay) {
                                $data = ['brunch_id' => $branch->id, 'name' => $servingWay];
                                if ($servingWay === 'dine_in') {
                                    $data['tables_number'] = $branchData['tables_number'] ?? null;
                                }
                                serving_ways::create($data);
                            }
                        }

                        // إضافة وسائل الدفع
                        if (isset($branchData['payment_services'])) {
                            foreach ($branchData['payment_services'] as $paymentService) {
                                payment_services::create([
                                    'brunch_id' => $branch->id,
                                    'name' => $paymentService,
                                ]);
                            }
                        }
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Client and branches updated successfully.',
                'data' => $client,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some data is incomplete or incorrect.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating data.',
                'error_details' => $e->getMessage(),
            ], 500);
        }
    }



    public function update_business_info(Request $request, $id)
    {
        try {

            // البحث عن العميل بواسطة المعرف
            $client = qtap_clients::find($id);

            if (!$client) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Client not found.',
                ]);
            }

            // التحقق من البيانات المرسلة فقط
            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'country' => 'sometimes|string|max:255',
                'mobile' => 'sometimes|string|max:255',
                'birth_date' => 'sometimes|date',
                'email' => 'sometimes|string|email|max:255|unique:qtap_clients,email,' . $id,
                'status' => 'sometimes|in:active,inactive',
                'password' => 'sometimes|string|min:1',
                'user_type' => 'sometimes|in:qtap_clients',
            ]);

            if ($request->has('password')) {
                $validatedData['password'] = Hash::make($request->password);
            }

            if ($request->hasFile('img')) {
                $imagePath = $request->file('img')->store('uploads/clients', 'public');
                $validatedData['img'] = $imagePath;
            }

            // تحديث بيانات العميل
            $client->update($validatedData);

            // تحديث الفروع إذا تم إرسالها
            $branches = collect($request->all())->filter(function ($value, $key) {
                return Str::startsWith($key, 'brunch'); // البحث عن المفاتيح التي تبدأ بـ "brunch"
            });

            foreach ($branches as $branchId => $branchData) {
                preg_match('/\d+/', $branchId, $matches);
                $number = $matches[0] ?? null;

                if ($number) {
                    $branch = qtap_clients_brunchs::find($number);

                    if ($branch) {
                        // تحديث بيانات الفرع
                        $branch->update([
                            'currency_id' => $branchData['currency_id'] ?? $branch->currency_id,
                            // 'pricing_id' => $branchData['pricing_id'] ?? $branch->pricing_id,
                            // 'discount_id' => $branchData['discount_id'] ?? $branch->discount_id,
                            'business_name' => $branchData['business_name'] ?? $branch->business_name,
                            'country' => $branchData['country'] ?? $branch->country,
                            'city' => $branchData['city'] ?? $branch->city,
                            'latitude' => $branchData['latitude'] ?? $branch->latitude,
                            'longitude' => $branchData['longitude'] ?? $branch->longitude,
                            'business_format' => $branchData['business_format'] ?? $branch->business_format,
                            'menu_design' => $branchData['menu_design'] ?? $branch->menu_design,
                            'default_mode' => $branchData['default_mode'] ?? $branch->default_mode,
                            'payment_time' => $branchData['payment_time'] ?? $branch->payment_time,
                            'call_waiter' => $branchData['call_waiter'] ?? $branch->call_waiter,
                        ]);


                        // حذف البيانات المرتبطة بالفرع إذا كانت موجودة
                        contact_info::where('brunch_id', $branch->id)->forcedelete();
                        workschedule::where('brunch_id', $branch->id)->forcedelete();
                        serving_ways::where('brunch_id', $branch->id)->forcedelete();
                        payment_services::where('brunch_id', $branch->id)->forcedelete();


                        // إضافة بيانات الاتصال
                        contact_info::create([
                            'brunch_id' => $branch->id,
                            'phone' => $branchData['business_phone'] ?? null,
                            'email' => $branchData['business_email'] ?? null,
                            'website' => $branchData['website'] ?? null,
                            'facebook' => $branchData['facebook'] ?? null,
                            'twitter' => $branchData['twitter'] ?? null,
                            'instagram' => $branchData['instagram'] ?? null,
                            'address' => $branchData['address'] ?? null,
                        ]);

                        // إضافة الجدول الزمني للعمل
                        if (isset($branchData['workschedules'])) {
                            foreach ($branchData['workschedules'] as $day => $times) {
                                workschedule::create([
                                    'brunch_id' => $branch->id,
                                    'day' => $day,
                                    'opening_time' => $times[0] ?? null,
                                    'closing_time' => $times[1] ?? null,
                                ]);
                            }
                        }

                        // إضافة طرق التقديم
                        if (isset($branchData['serving_ways'])) {
                            foreach ($branchData['serving_ways'] as $servingWay) {
                                $data = ['brunch_id' => $branch->id, 'name' => $servingWay];
                                if ($servingWay === 'dine_in') {
                                    $data['tables_number'] = $branchData['tables_number'] ?? null;
                                }
                                serving_ways::create($data);
                            }
                        }

                        // إضافة وسائل الدفع
                        if (isset($branchData['payment_services'])) {
                            foreach ($branchData['payment_services'] as $paymentService) {
                                payment_services::create([
                                    'brunch_id' => $branch->id,
                                    'name' => $paymentService,
                                ]);
                            }
                        }
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Client and branches updated successfully.',
                'data' => $client,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some data is incomplete or incorrect.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating data.',
                'error_details' => $e->getMessage(),
            ], 500);
        }
    }






    public function update(Request $request, $id)
    {
        try {
            // البحث عن العميل بواسطة المعرف
            $client = qtap_clients::find($id);

            if (!$client) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Client not found.',
                ]);
            }

            // التحقق من البيانات المرسلة فقط
            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'country' => 'sometimes|string|max:255',
                'mobile' => 'sometimes|string|max:255',
                'birth_date' => 'sometimes|date',
                'email' => 'sometimes|string|email|max:255|unique:qtap_clients,email,' . $id,
                'status' => 'sometimes|in:active,inactive',
                'password' => 'sometimes|string|min:1',
                'user_type' => 'sometimes|in:qtap_clients',
            ]);

            if ($request->has('password')) {
                $validatedData['password'] = Hash::make($request->password);
            }

            if ($request->hasFile('img')) {
                $imagePath = $request->file('img')->store('uploads/clients', 'public');
                $validatedData['img'] = $imagePath;
            }

            // تحديث بيانات العميل
            $client->update($validatedData);

            // تحديث الفروع إذا تم إرسالها
            $branches = collect($request->all())->filter(function ($value, $key) {
                return Str::startsWith($key, 'brunch'); // البحث عن المفاتيح التي تبدأ بـ "brunch"
            });

            foreach ($branches as $branchId => $branchData) {
                preg_match('/\d+/', $branchId, $matches);
                $number = $matches[0] ?? null;

                if ($number) {
                    $branch = qtap_clients_brunchs::find($number);

                    if ($branch) {
                        // تحديث بيانات الفرع
                        $branch->update([
                            'currency_id' => $branchData['currency_id'] ?? $branch->currency_id,
                            // 'pricing_id' => $branchData['pricing_id'] ?? $branch->pricing_id,
                            // 'discount_id' => $branchData['discount_id'] ?? $branch->discount_id,
                            'business_name' => $branchData['business_name'] ?? $branch->business_name,
                            'country' => $branchData['country'] ?? $branch->country,
                            'city' => $branchData['city'] ?? $branch->city,
                            'latitude' => $branchData['latitude'] ?? $branch->latitude,
                            'longitude' => $branchData['longitude'] ?? $branch->longitude,
                            'business_format' => $branchData['business_format'] ?? $branch->business_format,
                            'menu_design' => $branchData['menu_design'] ?? $branch->menu_design,
                            'default_mode' => $branchData['default_mode'] ?? $branch->default_mode,
                            'payment_time' => $branchData['payment_time'] ?? $branch->payment_time,
                            'call_waiter' => $branchData['call_waiter'] ?? $branch->call_waiter,
                        ]);


                        // حذف البيانات المرتبطة بالفرع إذا كانت موجودة
                        contact_info::where('brunch_id', $branch->id)->forcedelete();
                        workschedule::where('brunch_id', $branch->id)->forcedelete();
                        serving_ways::where('brunch_id', $branch->id)->forcedelete();
                        payment_services::where('brunch_id', $branch->id)->forcedelete();


                        // إضافة بيانات الاتصال
                        contact_info::create([
                            'brunch_id' => $branch->id,
                            'phone' => $branchData['business_phone'] ?? null,
                            'email' => $branchData['business_email'] ?? null,
                            'website' => $branchData['website'] ?? null,
                            'facebook' => $branchData['facebook'] ?? null,
                            'twitter' => $branchData['twitter'] ?? null,
                            'instagram' => $branchData['instagram'] ?? null,
                            'address' => $branchData['address'] ?? null,
                        ]);

                        // إضافة الجدول الزمني للعمل
                        if (isset($branchData['workschedules'])) {
                            foreach ($branchData['workschedules'] as $day => $times) {
                                workschedule::create([
                                    'brunch_id' => $branch->id,
                                    'day' => $day,
                                    'opening_time' => $times[0] ?? null,
                                    'closing_time' => $times[1] ?? null,
                                ]);
                            }
                        }

                        // إضافة طرق التقديم
                        if (isset($branchData['serving_ways'])) {
                            foreach ($branchData['serving_ways'] as $servingWay) {
                                $data = ['brunch_id' => $branch->id, 'name' => $servingWay];
                                if ($servingWay === 'dine_in') {
                                    $data['tables_number'] = $branchData['tables_number'] ?? null;
                                }
                                serving_ways::create($data);
                            }
                        }

                        // إضافة وسائل الدفع
                        if (isset($branchData['payment_services'])) {
                            foreach ($branchData['payment_services'] as $paymentService) {
                                payment_services::create([
                                    'brunch_id' => $branch->id,
                                    'name' => $paymentService,
                                ]);
                            }
                        }
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Client and branches updated successfully.',
                'data' => $client,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some data is incomplete or incorrect.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating data.',
                'error_details' => $e->getMessage(),
            ], 500);
        }
    }













    // public function update(Request $request, $id)
    // {
    //     $validatedData = $request->validate([
    //         'name' => 'nullable|string|max:255',
    //         'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    //         'country' => 'nullable|string|max:255',
    //         'mobile' => 'nullable|string|max:255',
    //         'birth_date' => 'nullable|date',
    //         'status' => 'nullable|in:active,inactive',
    //         'email' => 'nullable|string|email|max:255',
    //         'password' => 'nullable|string|min:1',
    //         'user_type' => 'nullable|in:qtap_clients',
    //         'payment_way' => 'nullable|in:bank_account,wallet,credit_card',
    //     ]);

    //     try {
    //         $qtap_affiliate = qtap_clients::find($id);
    //         if (!$qtap_affiliate) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'المستخدم غير موجود.',
    //             ], 404);
    //         }

    //         if ($request->hasFile('img')) {
    //             $imagePath = $request->file('img')->store('uploads/clients', 'public');
    //             $validatedData['img'] = 'public/storage/' . $imagePath;
    //         }

    //         if ($request->filled('password')) {
    //             $validatedData['password'] = Hash::make($request->password);
    //         }

    //         $qtap_affiliate->update(array_filter($validatedData));

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Data updated successfully.',
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'An error occurred while adding data.',
    //             'error_details' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function destroy($id)
    {
        $qtap_clients = qtap_clients::find($id);

        if (!$qtap_clients) {
            return response()->json([
                'status' => 'error',
                'message' => 'Client not found',
            ], 404);
        }

        $qtap_clients->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Client deleted successfully.',
        ], 200);
    }
}
