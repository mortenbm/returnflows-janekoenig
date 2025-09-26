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
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('shopify_id')->unique();
                $table->foreignId('store_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('email');
                $table->string('status');
                $table->string('currency');
                $table->unsignedInteger('subtotal');
                $table->unsignedInteger('discount')->default(0);
                $table->unsignedInteger('shipping_amount')->default(0);
                $table->unsignedInteger('total');
                $table->json('tags')->nullable();
                $table->string('risk_level')->nullable();
                $table->string('client_ip')->nullable();
                $table->text('customer_note')->nullable();
                $table->string('bc_id')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
