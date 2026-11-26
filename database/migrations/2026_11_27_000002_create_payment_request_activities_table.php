<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_request_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('action', 20);
            $table->json('changes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['payment_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_request_activities');
    }
};
