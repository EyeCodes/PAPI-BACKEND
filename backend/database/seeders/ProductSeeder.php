<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $merchants = Merchant::all();

        $products = [
            [
                'name' => 'Smartphone X',
                'description' => 'Latest smartphone with advanced features.',
                'price' => 15000.00,
                'currency' => 'PHP',
                'stock' => 50,
                'external_id' => 'PROD001',
                'source' => 'API',
            ],
            [
                'name' => 'Designer Jacket',
                'description' => 'Stylish jacket for all seasons.',
                'price' => 2500.00,
                'currency' => 'PHP',
                'stock' => 20,
                'external_id' => 'PROD002',
                'source' => 'Manual',
            ],
            [
                'name' => 'Wireless Earbuds',
                'description' => 'High-quality wireless earbuds.',
                'price' => 3000.00,
                'currency' => 'PHP',
                'stock' => 100,
                'external_id' => 'PROD003',
                'source' => 'API',
            ],
            [
                'name' => 'Bestseller Novel',
                'description' => 'Latest novel by a renowned author.',
                'price' => 800.00,
                'currency' => 'PHP',
                'stock' => 200,
                'external_id' => 'PROD004',
                'source' => 'Manual',
            ],
            [
                'name' => 'Organic Coffee',
                'description' => 'Premium organic coffee beans.',
                'price' => 500.00,
                'currency' => 'PHP',
                'stock' => 150,
                'external_id' => 'PROD005',
                'source' => 'API',
            ],
        ];

        foreach ($merchants as $index => $merchant) {
            $productData = $products[$index % count($products)];
            $productData['merchant_id'] = $merchant->id;
            Product::create($productData);
        }
    }
}
