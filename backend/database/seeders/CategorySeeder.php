<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $trees = [
            [
                'name' => 'Electronics',
                'children' => ['Smartphones', 'Laptops', 'Accessories'],
            ],
            [
                'name' => 'Fashion',
                'children' => ['Men', 'Women', 'Kids'],
            ],
            [
                'name' => 'Home & Living',
                'children' => ['Furniture', 'Kitchen', 'Decor'],
            ],
        ];

        foreach ($trees as $index => $tree) {
            $root = Category::updateOrCreate(
                ['slug' => Str::slug($tree['name'])],
                [
                    'name' => $tree['name'],
                    'description' => "{$tree['name']} category",
                    'is_active' => true,
                    'position' => $index * 10,
                    'meta_title' => $tree['name'],
                    'meta_description' => "Shop {$tree['name']} products",
                    'meta_keywords' => implode(',', explode(' ', strtolower($tree['name']))),
                ]
            );

            foreach ($tree['children'] as $childIndex => $childName) {
                Category::updateOrCreate(
                    ['slug' => Str::slug($childName)],
                    [
                        'parent_id' => $root->id,
                        'name' => $childName,
                        'description' => "{$childName} products",
                        'is_active' => true,
                        'position' => $childIndex,
                        'meta_title' => $childName,
                        'meta_description' => "Shop {$childName}",
                        'meta_keywords' => implode(',', explode(' ', strtolower($childName))),
                    ]
                );
            }
        }
    }
}
