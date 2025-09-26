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
        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->string('shopify_id')->unique();
                $table->string('sku');
                $table->string('title');
                $table->unsignedInteger('quantity');
                $table->unsignedInteger('tax_amount')->default(0);
                $table->decimal('tax_rate')->default(0);
                $table->unsignedInteger('price')->default(0);
                $table->unsignedInteger('discount')->default(0);
                $table->unsignedInteger('total');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
