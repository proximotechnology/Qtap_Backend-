<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('client_pricings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')->references('id')->on('qtap_clients')->onDelete('cascade');


            $table->unsignedBigInteger('pricing_id');
            $table->foreign('pricing_id')->references('id')->on('pricings');

            $table->string('ramin_order');
            $table->string('payment_methodes');
            $table->string('pricing_way');
            $table->string('status')->default('pending');
            $table->string('image')->nullable();

            $table->string('expired_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_pricings');
    }
};
