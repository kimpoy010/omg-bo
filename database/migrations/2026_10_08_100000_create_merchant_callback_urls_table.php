<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Single-row by design (one OMG merchant per deployment): the callback
    // endpoints the merchant registers for OMG to call.
    public function up(): void
    {
        Schema::create('merchant_callback_urls', function (Blueprint $table) {
            $table->id();
            $table->string('verify_session_url', 500)->nullable();
            $table->string('get_balance_url', 500)->nullable();
            $table->string('change_balance_url', 500)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_callback_urls');
    }
};
