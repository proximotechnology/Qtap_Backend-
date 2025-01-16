<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\QtapAdminsController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\QtapAffiliateController;
use App\Http\Controllers\CampaignsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\homeController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\TicketSupportController;
use App\Http\Controllers\WeServController;
use App\Http\Controllers\QtapClientsController;
use App\Http\Controllers\CustomerInfoController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TablesController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


//-----------------------------------------AUTH------------------------
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);


//------------------------------------------WEBSITE---------------------------------------
Route::get('home', [homeController::class, 'index']);


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});





//-------------------------------------------ADMIN OR CLIENT-------------------------------

Route::middleware('admin_or_client')->group(function () {
    Route::resource('feedback', FeedbackController::class);
    Route::resource('ticket', TicketSupportController::class);



    Route::post('clients_update_profile/{id}', [QtapClientsController::class, 'update_profile']);
    Route::post('clients_update_menu/{id}', [QtapClientsController::class, 'update_menu']);
});

//-------------------------------------------ADMIN OR AFFILIATE-----------------------------

Route::middleware('admin_or_affiliate')->group(function () {
    Route::post('qtap_affiliate/{id}', [QtapAffiliateController::class, 'update']);
});



//---------------------------------------------ADMIN----------------------------------------

Route::middleware('auth:qtap_admins')->group(function () {



    //-------------affiliate--------

    Route::get('affiliate', [QtapAffiliateController::class, 'index']);


    //-------------clinet--------
    Route::delete('qtap_clients/{id}', [QtapClientsController::class, 'destroy']);
    Route::post('qtap_clients/{id}', [QtapClientsController::class, 'update']);


    //-------------website--------
    Route::get('we_serv', [WeServController::class, 'index']);
    Route::post('we_serv', [WeServController::class, 'store']);
    Route::post('we_serv/{id}', [WeServController::class, 'update']);
    Route::delete('we_serv/{id}', [WeServController::class, 'destroy']);



    //-------------dashboard--------
    Route::resource('note', NoteController::class);
    Route::resource('pricing', PricingController::class);
    Route::resource('currency', CurrencyController::class);
    Route::resource('products', ProductsController::class);
    Route::resource('campaigns', CampaignsController::class);
    Route::post('products/update/{products}', [ProductsController::class, 'update']);
    Route::post('dashboard', [QtapAdminsController::class, 'dashboard'])->name('dashboard');
    Route::prefix('settings')->group(function () {
        Route::post('content', [SettingsController::class, 'createSettingContent']);
        Route::post('content/{id}', [SettingsController::class, 'updateSettingContent']);
        Route::get('content', [SettingsController::class, 'getSettingContent']);
        Route::delete('content/{id}', [SettingsController::class, 'deleteSettingContent']);



        Route::post('faq', [SettingsController::class, 'createSettingFaq']);
        Route::post('faq/{id}', [SettingsController::class, 'updateSettingFaq']);
        Route::get('faq', [SettingsController::class, 'getSettingFaq']);
        Route::delete('faq/{id}', [SettingsController::class, 'deleteSettingFaq']);

        Route::post('features', [SettingsController::class, 'createSettingFeatures']);
        Route::post('features/{id}', [SettingsController::class, 'updateSettingFeatures']);
        Route::get('features', [SettingsController::class, 'getSettingFeatures']);
        Route::delete('features/{id}', [SettingsController::class, 'deleteSettingFeatures']);

        Route::post('our-clients', [SettingsController::class, 'createSettingOurClients']);
        Route::post('our-clients/{id}', [SettingsController::class, 'updateSettingOurClients']);
        Route::get('our-clients', [SettingsController::class, 'getSettingOurClients']);
        Route::delete('our-clients/{id}', [SettingsController::class, 'deleteSettingOurClients']);

        Route::post('payment', [SettingsController::class, 'createSettingPayment']);
        Route::post('payment/{id}', [SettingsController::class, 'updateSettingPayment']);
        Route::get('payment', [SettingsController::class, 'getSettingPayment']);
        Route::delete('payment/{id}', [SettingsController::class, 'deleteSettingPayment']);

        Route::post('videos', [SettingsController::class, 'createSettingVideos']);
        Route::post('videos/{id}', [SettingsController::class, 'updateSettingVideos']);
        Route::get('videos', [SettingsController::class, 'getSettingVideos']);
        Route::delete('videos/{id}', [SettingsController::class, 'deleteSettingVideos']);
    });




    //-------------chat--------
    Route::resource('customer_info', CustomerInfoController::class);

    // Route::get('qtap_clients', [QtapClientsController::class, 'index']);


    //-------------admin--------
    Route::resource('qtap_admins', QtapAdminsController::class);
});



//------------------------------------------------PUBLIC API------------------------------------------------------------

//------------------chat---------------------
Route::resource('chat', ChatController::class);


//------------------CLIENT -------------------
Route::middleware('auth:qtap_clients')->group(function () {
    Route::resource('payment', PaymentController::class);
    Route::resource('tables', TablesController::class);

    Route::get('area', [TablesController::class, 'get_area']);
    Route::post('area', [TablesController::class, 'store_area']);
    Route::post('area/{id}', [TablesController::class, 'update_area']);
    Route::delete('area/{id}', [TablesController::class, 'delete_area']);
});


//------------------add qtap_affiliate--------
Route::post('add_qtap_affiliate', [QtapAffiliateController::class, 'store']);


//------------------affiliate-----------------
Route::middleware('auth:qtap_affiliate')->group(function () {});


//------------------add qtap_clients--------
Route::post('qtap_clients', [QtapClientsController::class, 'store']);
