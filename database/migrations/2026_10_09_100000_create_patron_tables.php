<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Our record of the nurture/kill controls the merchant has pushed to OMG.
        Schema::create('patron_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('players')->cascadeOnDelete();
            $table->string('kind', 10);                          // nurture | kill
            $table->unsignedTinyInteger('patron_type')->nullable(); // 0 fast, 1 slow, 2 ultimate (nurture only)
            $table->decimal('amount', 18, 4);
            $table->string('status', 10)->default('active');     // active | cancelled
            $table->foreignId('set_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'user_id']);
        });

        // Single-row: new-player auto-nurturing switch.
        Schema::create('patron_auto_configs', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->decimal('amount', 18, 4)->nullable();
            $table->unsignedTinyInteger('patron_type')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patron_auto_configs');
        Schema::dropIfExists('patron_controls');
    }
};
