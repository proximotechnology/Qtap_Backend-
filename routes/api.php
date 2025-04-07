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
use App\Http\Controllers\MealsCategoriesController;
use App\Http\Controllers\MealsController;
use App\Http\Controllers\MealsDiscountController;
use App\Http\Controllers\MealsExtraController;
use App\Http\Controllers\MealsSizeController;
use App\Http\Controllers\MealsSpecialOffersController;
use App\Http\Controllers\MealsVariantsController;
use App\Http\Controllers\RestaurantUsersController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RestaurantStaffController;
use App\Http\Controllers\DiscountController;


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



    Route::get('get_client_info/{id}', [QtapClientsController::class, 'get_client_info']);
    Route::get('get_affiliate_info/{id}', [QtapAffiliateController::class, 'get_affiliate_info']);



    Route::post('clients_update_profile/{id}', [QtapClientsController::class, 'update_profile']);
    Route::post('clients_update_menu/{id}', [QtapAffiliateController::class, 'update_menu']);
});

//-------------------------------------------ADMIN OR AFFILIATE-----------------------------

Route::middleware('admin_or_affiliate')->group(function () {
    Route::post('qtap_affiliate/{id}', [QtapAffiliateController::class, 'update']);
});

Route::get('pricing', [PricingController::class, 'index'])->name('pricing');

Route::get('discount', [DiscountController::class, 'index']);



//---------------------------------------------ADMIN----------------------------------------

Route::middleware('auth:qtap_admins')->group(function () {


    //-------------discount--------
    Route::post('discount', [DiscountController::class, 'store']);
    Route::put('discount/{id}', [DiscountController::class, 'update']);
    Route::delete('discount/{id}', [DiscountController::class, 'destroy']);






    //-------------chat--------
    Route::get('customer_info', [CustomerInfoController::class, 'index']);
    Route::put('customer_info/{id}', [CustomerInfoController::class, 'update']);
    Route::delete('customer_info/{id}', [CustomerInfoController::class, 'delete']);

    //-------------affiliate--------

    Route::get('affiliate', [QtapAffiliateController::class, 'index']);
    Route::delete('affiliate/{id}', [QtapAffiliateController::class, 'destroy']);


    //-------------clinet--------
    Route::delete('qtap_clients/{id}', [QtapClientsController::class, 'destroy']);
    Route::post('qtap_clients/{id}', [QtapClientsController::class, 'update']);


    //-------------website--------
    Route::get('we_serv', [WeServController::class, 'index']);
    Route::post('we_serv', [WeServController::class, 'store']);
    Route::post('we_serv/{id}', [WeServController::class, 'update']);
    Route::delete('we_serv/{id}', [WeServController::class, 'destroy']);


    // Route::resource('pricing', PricingController::class);

    // Route::get('pricing', [PricingController::class, 'pricing'])->name('pricing');
    Route::post('pricing', [PricingController::class, 'store'])->name('pricing');
    Route::put('pricing/{id}', [PricingController::class, 'update'])->name('pricing');
    Route::delete('pricing/{id}', [PricingController::class, 'destroy'])->name('pricing');



    //-------------dashboard--------
    Route::resource('note', NoteController::class);
    Route::resource('currency', CurrencyController::class);
    Route::resource('products', ProductsController::class);
    Route::resource('campaigns', CampaignsController::class);
    Route::post('products/update/{products}', [ProductsController::class, 'update']);


    Route::post('dashboard', [QtapAdminsController::class, 'dashboard'])->name('dashboard');
    Route::post('Sales/{id}', [QtapAdminsController::class, 'Sales'])->name('Sales');
    Route::post('Sales_by_days/{id}', [QtapAdminsController::class, 'Sales_by_days'])->name('Sales_by_days');
    Route::post('Performance/{startYear}-{endYear}', [QtapAdminsController::class, 'Performance'])->name('Performance');


    Route::post('wallet/{year}', [QtapAdminsController::class, 'wallet'])->name('wallet');
    Route::get('Deposits/{startDate}/{endDate}', [QtapAdminsController::class, 'Deposits'])->name('Deposits');

    Route::get('affiliate_transactions', [QtapAffiliateController::class, 'affiliate_transactions'])->name('affiliate_transactions');




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





    Route::get('qtap_clients', [QtapClientsController::class, 'index']);


    //-------------admin--------
    Route::resource('qtap_admins', QtapAdminsController::class);
});






//------------------------------------------------PUBLIC API------------------------------------------------------------

//-------------chat--------
Route::resource('chat', ChatController::class);
Route::post('customer_info', [CustomerInfoController::class, 'store']);


Route::post('add_affiliate', [QtapAffiliateController::class, 'store'])->name('add_affiliate');




//------------------CLIENT -------------------
Route::middleware('auth:qtap_clients')->group(function () {

    Route::post('logout', [AuthController::class, 'logout']);


    //-----------get_info------------------------------

    Route::get('get_info', [QtapClientsController::class, 'get_info']);




    //------------restaurant_staff-----
    Route::resource('restaurant_staff', RestaurantStaffController::class);


    //------------roles-----
    Route::resource('roles', RoleController::class);

    //------------users_staff-----
    Route::resource('restaurant_users', RestaurantUsersController::class);



    Route::resource('meals_categories', MealsCategoriesController::class);

    Route::resource('meals_discount', MealsDiscountController::class);

    Route::post('meals/{id}', [MealsController::class, 'update']);
    Route::resource('meals', MealsController::class);

    Route::resource('meals_size', MealsSizeController::class);
    Route::resource('meals_special_offers', MealsSpecialOffersController::class);
    Route::resource('meals_variants', MealsVariantsController::class);
    Route::resource('meals_extra', MealsExtraController::class);




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
