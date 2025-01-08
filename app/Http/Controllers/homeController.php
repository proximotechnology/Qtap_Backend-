<?php

namespace App\Http\Controllers;
use App\Models\setting_features;
use App\Models\setting_content;
use App\Models\setting_faq;
use App\Models\setting_our_clients;
use App\Models\setting_videos;
use App\Models\pricing;
use App\Models\feedback;

use Illuminate\Http\Request;

class homeController extends Controller
{
    public function index(){

        $features = setting_features::all();
        $content = setting_content::all();
        $faq = setting_faq::all();
        $clients = setting_our_clients::all();
        $videos = setting_videos::all();
        $pricing = pricing::where('is_active', 'active')->get();
        $feedback = feedback::with('client')->where('publish', 'yes')->get();

       return response([
           'features' => $features,
           'content' => $content,
           'faq' => $faq,
           'clients' => $clients,
           'pricing' => $pricing,
           'feedback' => $feedback,
       ]);

    }
}
