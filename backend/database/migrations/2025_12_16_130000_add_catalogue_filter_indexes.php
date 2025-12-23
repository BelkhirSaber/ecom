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
        Schema::table('products', function (Blueprint $table) {
            $table->index(['category_id', 'is_active'], 'products_category_is_active_index');
            $table->index(['is_active', 'stock_status'], 'products_is_active_stock_status_index');
            $table->index(['type', 'is_active'], 'products_type_is_active_index');
            $table->index(['is_active', 'price'], 'products_is_active_price_index');
            $table->index('stock_quantity', 'products_stock_quantity_index');
            $table->index('created_at', 'products_created_at_index');
            $table->index('name', 'products_name_index');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->index(['product_id', 'is_active'], 'product_variants_product_is_active_index');
            $table->index(['product_id', 'stock_status'], 'product_variants_product_stock_status_index');
            $table->index(['product_id', 'price'], 'product_variants_product_price_index');
            $table->index(['product_id', 'stock_quantity'], 'product_variants_product_stock_quantity_index');
            $table->index(['product_id', 'created_at'], 'product_variants_product_created_at_index');
            $table->index(['product_id', 'name'], 'product_variants_product_name_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex('product_variants_product_is_active_index');
            $table->dropIndex('product_variants_product_stock_status_index');
            $table->dropIndex('product_variants_product_price_index');
            $table->dropIndex('product_variants_product_stock_quantity_index');
            $table->dropIndex('product_variants_product_created_at_index');
            $table->dropIndex('product_variants_product_name_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_category_is_active_index');
            $table->dropIndex('products_is_active_stock_status_index');
            $table->dropIndex('products_type_is_active_index');
            $table->dropIndex('products_is_active_price_index');
            $table->dropIndex('products_stock_quantity_index');
            $table->dropIndex('products_created_at_index');
            $table->dropIndex('products_name_index');
        });
    }
};
