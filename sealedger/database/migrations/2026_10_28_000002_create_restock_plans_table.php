<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restock_plans', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->date('plan_date');
            $table->time('plan_time');
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('Pending');
            $table->timestamps();
        });

        Schema::create('restock_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restock_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restock_plan_items');
        Schema::dropIfExists('restock_plans');
    }
};
