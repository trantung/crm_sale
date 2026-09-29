<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['code' => 'KH-IELTS-FOUND', 'name' => 'IELTS Foundation', 'price' => 8900000, 'sort_order' => 1],
            ['code' => 'KH-IELTS-55', 'name' => 'IELTS 5.5 Target', 'price' => 12900000, 'sort_order' => 2],
            ['code' => 'KH-IELTS-65', 'name' => 'IELTS 6.5 Intensive', 'price' => 16900000, 'sort_order' => 3],
            ['code' => 'KH-IELTS-70', 'name' => 'IELTS 7.0+ Advanced', 'price' => 21900000, 'sort_order' => 4],
            ['code' => 'KH-SPEAKING', 'name' => 'Lớp Speaking 1-1', 'price' => 4500000, 'sort_order' => 5],
        ];

        foreach ($products as $product) {
            Product::query()->updateOrCreate(
                ['code' => $product['code']],
                [
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'sort_order' => $product['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
