<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryIds = Category::pluck('id');

        if ($categoryIds->isEmpty()) {
            $categoryIds = collect([null]);
        }

        Product::factory()
            ->count(15)
            ->make()
            ->each(function (Product $product) use ($categoryIds) {
                $product->category_id = $categoryIds->random();
                $product->save();
            });
    }
}
