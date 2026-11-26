<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('vendor_name');
            $table->string('invoice_number', 100);
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 30);
            $table->date('payment_date');
            $table->text('remarks')->nullable();

            // Attachment lives on the private disk; only the path is stored here.
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();

            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('vendor_name');
            $table->index('invoice_number');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};
