<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            $table->string('purchasable_type');
            $table->unsignedBigInteger('purchasable_id');

            $table->string('sku')->nullable();
            $table->string('name');

            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('line_total', 10, 2);

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['purchasable_type', 'purchasable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
