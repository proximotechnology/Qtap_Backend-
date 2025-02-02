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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('menu')->default(1);
            $table->integer('users')->default(1);
            $table->integer('orders')->default(1);
            $table->integer('wallet')->default(1);
            $table->integer('setting')->default(1);
            $table->integer('support')->default(1);
            $table->integer('dashboard')->default(1);
            $table->integer('customers_log')->default(1);

            // Foreign key for branches
            $table->unsignedBigInteger('brunch_id');
            $table->foreign('brunch_id')->references('id')->on('qtap_clients_brunchs')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
