<?php

namespace App\Http\Controllers;

use App\Models\clients_logs;

use App\Models\qtap_affiliate;
use App\Models\qtap_admins;
use App\Models\qtap_clients;
use Illuminate\Http\Request;

class QtapAdminsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clients_active = qtap_clients::where('status', 'active');
        $clients_inactive = qtap_clients::where('status', 'inactive');
        return response()->json([
            "success" => true,
            "clients_active" => $clients_active,
            "clients_inactive" => $clients_inactive
        ]);
    }



    public function dashboard()
    {

        $clients_active = qtap_clients::with('logs')->where('status', 'active')->get();
        $qtap_affiliate = qtap_affiliate::where('status', 'active')->get();


        $clients_inactive = qtap_clients::where('status', 'inactive')->get();



        return response()->json([
            "success" => true,
            "clients_active" => $clients_active,
            "affiliate" => $qtap_affiliate,
            "clients_inactive" => $clients_inactive
        ]);
    }



    public function qtap_clients()
    {

        $clients_active = qtap_clients::where('status', 'active');
        $clients_inactive = qtap_clients::where('status', 'inactive');
        return response()->json([
            "success" => true,
            "clients_active" => $clients_active,
            "clients_inactive" => $clients_inactive
        ]);
    }




    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(qtap_admins $qtap_admins)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(qtap_admins $qtap_admins)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, qtap_admins $qtap_admins)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(qtap_admins $qtap_admins)
    {
        //
    }
}
