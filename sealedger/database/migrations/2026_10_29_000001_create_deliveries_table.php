<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 100);
            $table->string('contact', 100)->nullable();   // Messenger name / phone
            $table->string('address', 255);
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('status', 30)->default('Pending')->index();
            $table->dateTime('estimated_at')->nullable();
            $table->text('notes')->nullable();

            // Filled in when the order is marked Delivered.
            $table->dateTime('delivered_at')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('proof_note', 500)->nullable();

            $table->timestamps();
        });

        Schema::create('delivery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_items');
        Schema::dropIfExists('deliveries');
    }
};
