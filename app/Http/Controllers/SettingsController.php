<?php

namespace App\Http\Controllers;

use App\Models\setting_content;
use App\Models\setting_faq;
use App\Models\setting_features;
use App\Models\setting_our_clients;
use App\Models\setting_payment;
use App\Models\setting_videos;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function createSettingContent(Request $request)
    {
        try {
            $data = $request->validate([
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'titles' => 'required|string',
                'descriptions' => 'required|string',
                'features' => 'required|string',
            ]);

            // Handle image upload
            if ($request->hasFile('img')) {
                $image = $request->file('img');
                $imagePath = $image->store('images/Content', 'public'); // Store in public/images
                $data['img'] = $imagePath;
            }

            $setting = setting_content::create($data);
            return response()->json($setting, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function updateSettingContent(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'titles' => 'nullable|string',
                'descriptions' => 'nullable|string',
                'features' => 'nullable|string',
            ]);

            $setting = setting_content::findOrFail($id);

            // Handle image upload if file is present
            if ($request->hasFile('img')) {
                // Delete old image if it exists
                if ($setting->img && file_exists(storage_path('app/public/' . $setting->img))) {
                    unlink(storage_path('app/public/' . $setting->img));
                }

                // Upload the new image
                $image = $request->file('img');
                $imagePath = $image->store('images/Content', 'public');
                $data['img'] = $imagePath;
            }

            $setting->update($data);
            return response()->json($setting);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function getSettingContent()
    {
        try {
            $settings = setting_content::all();
            return response()->json($settings);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function deleteSettingContent($id)
    {
        try {
            $setting = setting_content::findOrFail($id);
            // Delete image if exists
            if ($setting->img && file_exists(storage_path('app/public/' . $setting->img))) {
                unlink(storage_path('app/public/' . $setting->img));
            }
            $setting->delete();
            return response()->json(['message' => 'Deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function createSettingFaq(Request $request)
    {
        try {
            $data = $request->validate([
                'question' => 'required|string',
                'answer' => 'required|string',
            ]);

            $setting = setting_faq::create($data);
            return response()->json($setting, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function updateSettingFaq(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'question' => 'nullable|string',
                'answer' => 'nullable|string',
            ]);

            $setting = setting_faq::findOrFail($id);
            $setting->update($data);
            return response()->json($setting);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function getSettingFaq()
    {
        try {
            $settings = setting_faq::all();
            return response()->json($settings);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function deleteSettingFaq($id)
    {
        try {
            $setting = setting_faq::findOrFail($id);
            $setting->delete();
            return response()->json(['message' => 'Deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function createSettingFeatures(Request $request)
    {
        try {
            $data = $request->validate([
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'titles' => 'required|string',
                'descriptions' => 'required|string',
                'features' => 'required|string',
            ]);

            // Handle image upload
            if ($request->hasFile('img')) {
                $image = $request->file('img');
                $imagePath = $image->store('images/Features', 'public'); // Store in public/images
                $data['img'] = $imagePath;
            }

            $setting = setting_features::create($data);
            return response()->json($setting, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function updateSettingFeatures(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'titles' => 'nullable|string',
                'descriptions' => 'nullable|string',
                'features' => 'nullable|string',
            ]);

            $setting = setting_features::findOrFail($id);

            // Handle image upload if file is present
            if ($request->hasFile('img')) {
                // Delete old image if it exists
                if ($setting->img && file_exists(storage_path('app/public/' . $setting->img))) {
                    unlink(storage_path('app/public/' . $setting->img));
                }

                // Upload the new image
                $image = $request->file('img');
                $imagePath = $image->store('images/Features', 'public');
                $data['img'] = $imagePath;
            }

            $setting->update($data);
            return response()->json($setting);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function getSettingFeatures()
    {
        try {
            $settings = setting_features::all();
            return response()->json($settings);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function deleteSettingFeatures($id)
    {
        try {
            $setting = setting_features::findOrFail($id);
            // Delete image if exists
            if ($setting->img && file_exists(storage_path('app/public/' . $setting->img))) {
                unlink(storage_path('app/public/' . $setting->img));
            }
            $setting->delete();
            return response()->json(['message' => 'Deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function createSettingOurClients(Request $request)
    {
        try {
            $data = $request->validate([
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'title' => 'required|string',
            ]);

            // Handle image upload
            if ($request->hasFile('img')) {
                $image = $request->file('img');
                $imagePath = $image->store('images/OurClients', 'public'); // Store in public/images
                $data['img'] = $imagePath;
            }

            $setting = setting_our_clients::create($data);
            return response()->json($setting, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function updateSettingOurClients(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'title' => 'nullable|string',
            ]);

            $setting = setting_our_clients::findOrFail($id);

            // Handle image upload if file is present
            if ($request->hasFile('img')) {
                // Delete old image if it exists
                if ($setting->img && file_exists(storage_path('app/public/' . $setting->img))) {
                    unlink(storage_path('app/public/' . $setting->img));
                }

                // Upload the new image
                $image = $request->file('img');
                $imagePath = $image->store('images/OurClients', 'public');
                $data['img'] = $imagePath;
            }

            $setting->update($data);
            return response()->json($setting);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function getSettingOurClients()
    {
        try {
            $settings = setting_our_clients::all();
            return response()->json($settings);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function deleteSettingOurClients($id)
    {
        try {
            $setting = setting_our_clients::findOrFail($id);
            // Delete image if exists
            if ($setting->img && file_exists(storage_path('app/public/' . $setting->img))) {
                unlink(storage_path('app/public/' . $setting->img));
            }
            $setting->delete();
            return response()->json(['message' => 'Deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function createSettingPayment(Request $request)
    {
        try {
            $data = $request->validate([
                'API_KEY' => 'required|string',
                'Token1' => 'required|string',
                'Token2' => 'required|string',
                'Ifram' => 'required|string',
            ]);

            $setting = setting_payment::create($data);
            return response()->json($setting, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function updateSettingPayment(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'API_KEY' => 'nullable|string',
                'Token1' => 'nullable|string',
                'Token2' => 'nullable|string',
                'Ifram' => 'nullable|string',
            ]);

            $setting = setting_payment::findOrFail($id);
            $setting->update($data);
            return response()->json($setting);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function getSettingPayment()
    {
        try {
            $settings = setting_payment::all();
            return response()->json($settings);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function deleteSettingPayment($id)
    {
        try {
            $setting = setting_payment::findOrFail($id);
            $setting->delete();
            return response()->json(['message' => 'Deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function createSettingVideos(Request $request)
    {
        try {
            $data = $request->validate([
                'video' => 'required|string',
            ]);

            $setting = setting_videos::create($data);
            return response()->json($setting, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function updateSettingVideos(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'video' => 'nullable|string',
            ]);

            $setting = setting_videos::findOrFail($id);
            $setting->update($data);
            return response()->json($setting);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function getSettingVideos()
    {
        try {
            $settings = setting_videos::all();
            return response()->json($settings);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function deleteSettingVideos($id)
    {
        try {
            $setting = setting_videos::findOrFail($id);
            $setting->delete();
            return response()->json(['message' => 'Deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }
}
