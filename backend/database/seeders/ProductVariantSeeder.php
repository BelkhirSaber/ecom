<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();

        if ($products->isEmpty()) {
            return;
        }

        $products->each(function (Product $product) {
            ProductVariant::factory()
                ->count(random_int(1, 3))
                ->make()
                ->each(function (ProductVariant $variant) use ($product) {
                    $variant->product_id = $product->id;
                    $variant->save();
                });
        });
    }
}
