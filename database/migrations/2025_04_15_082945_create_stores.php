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
        if (!Schema::hasTable('stores')) {
            Schema::create('stores', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->boolean('is_active')->default(false);
                $table->string('shopify_domain');
                $table->string('shopify_client_id');
                $table->text('shopify_client_secret');
                $table->text('shopify_access_token');
                $table->string('bc_sku');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('store_user')) {
            Schema::create('store_user', function (Blueprint $table) {
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('store_id')->constrained()->cascadeOnDelete();
                $table->primary(['user_id', 'store_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
