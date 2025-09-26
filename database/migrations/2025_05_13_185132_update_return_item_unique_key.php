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
        if (Schema::hasTable('order_return_items')) {
            Schema::table('order_return_items', function (Blueprint $table) {
                $table->dropUnique('order_return_items_shopify_id_unique');
                $table->unique(['return_id', 'shopify_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
