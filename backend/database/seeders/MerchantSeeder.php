<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Database\Seeder;

class MerchantSeeder extends Seeder
{
    public function run()
    {
        $merchants = [
            [
                'name' => 'SM Supermarket',
                'emails' => [['value' => 'contact@sm-supermarket.ph']],
                'phones' => [['value' => '+63 2 8470 2222']],
                'addresses' => [['value' => 'SM Mall of Asia, Pasay City, Metro Manila, Philippines']],
                'social_media' => [['value' => 'https://facebook.com/smsupermarket']],
                'website' => [['value' => 'https://www.smsupermarket.ph']],
                'external_id' => 'SM001',
                'source' => 'API',
                'is_active' => true,
            ],
            [
                'name' => 'Jollibee Foods Corporation',
                'emails' => [['value' => 'info@jollibee.com.ph']],
                'phones' => [['value' => '+63 2 8890 0000']],
                'addresses' => [['value' => 'Jollibee Plaza, Ortigas Center, Pasig City, Metro Manila, Philippines']],
                'social_media' => [['value' => 'https://instagram.com/jollibee']],
                'website' => [['value' => 'https://www.jollibee.com.ph']],
                'external_id' => 'JFC001',
                'source' => 'API',
                'is_active' => true,
            ],
            [
                'name' => 'Ayala Malls',
                'emails' => [['value' => 'customerservice@ayalamalls.com.ph']],
                'phones' => [['value' => '+63 2 8555 8888']],
                'addresses' => [['value' => 'Ayala Triangle Gardens Tower 2, Makati City, Metro Manila, Philippines']],
                'social_media' => [['value' => 'https://twitter.com/ayalamalls']],
                'website' => [['value' => 'https://www.ayalamalls.com.ph']],
                'external_id' => 'AYALA001',
                'source' => 'Manual',
                'is_active' => true,
            ],
            [
                'name' => 'Puregold Price Club',
                'emails' => [['value' => 'info@puregold.com.ph']],
                'phones' => [['value' => '+63 2 8888 8888']],
                'addresses' => [['value' => 'Puregold Corporate Center, Quezon City, Metro Manila, Philippines']],
                'social_media' => [['value' => 'https://facebook.com/puregoldph']],
                'website' => [['value' => 'https://www.puregold.com.ph']],
                'external_id' => 'PURE001',
                'source' => 'API',
                'is_active' => true,
            ],
        ];

        foreach ($merchants as $merchantData) {
            $merchant = Merchant::create($merchantData);
            $this->createProductsForMerchant($merchant);
        }
    }

    private function createProductsForMerchant(Merchant $merchant)
    {
        $products = [];

        switch ($merchant->name) {
            case 'SM Supermarket':
                $products = [
                    // Groceries
                    ['name' => 'Nestle Milo 200g', 'description' => 'Chocolate malt drink powder', 'price' => 89.50, 'stock' => 150],
                    ['name' => 'Lucky Me Pancit Canton', 'description' => 'Instant noodles with sauce', 'price' => 12.75, 'stock' => 300],
                    ['name' => 'Mang Tomas All Purpose Sauce', 'description' => 'Traditional Filipino lechon sauce', 'price' => 45.00, 'stock' => 80],
                    ['name' => 'San Miguel Beer Pale Pilsen', 'description' => 'Premium lager beer 330ml', 'price' => 35.00, 'stock' => 200],
                    ['name' => 'Datu Puti Soy Sauce', 'description' => 'Premium soy sauce 385ml', 'price' => 28.50, 'stock' => 120],
                    ['name' => 'Bear Brand Powdered Milk', 'description' => 'Fortified milk powder 150g', 'price' => 65.00, 'stock' => 100],
                    ['name' => 'Gardenia White Bread', 'description' => 'Soft white bread 400g', 'price' => 42.00, 'stock' => 75],
                    ['name' => 'Century Tuna Flakes in Oil', 'description' => 'Tuna flakes in vegetable oil 155g', 'price' => 38.75, 'stock' => 90],
                    ['name' => 'Kopiko 78 Degrees', 'description' => 'Coffee candy 100 pieces', 'price' => 15.50, 'stock' => 250],
                    ['name' => 'Palmolive Naturals Shampoo', 'description' => 'Natural shampoo 180ml', 'price' => 95.00, 'stock' => 60],
                    ['name' => 'Colgate Toothpaste', 'description' => 'Fresh mint toothpaste 200g', 'price' => 78.50, 'stock' => 85],
                    ['name' => 'Downy Fabric Conditioner', 'description' => 'Lavender fabric conditioner 1.8L', 'price' => 145.00, 'stock' => 45],
                    ['name' => 'Tide Powder Detergent', 'description' => 'Laundry detergent powder 1.5kg', 'price' => 165.00, 'stock' => 55],
                    ['name' => 'Pampers Baby Diapers', 'description' => 'Size 4 baby diapers 44 pieces', 'price' => 425.00, 'stock' => 30],
                    ['name' => 'Johnson\'s Baby Soap', 'description' => 'Gentle baby soap 75g', 'price' => 35.00, 'stock' => 70],
                ];
                break;

            case 'Jollibee Foods Corporation':
                $products = [
                    // Fast Food Items
                    ['name' => 'Jolly Spaghetti', 'description' => 'Sweet-style spaghetti with hotdog and cheese', 'price' => 65.00, 'stock' => 500],
                    ['name' => 'Chickenjoy', 'description' => 'Crispy fried chicken with gravy', 'price' => 89.00, 'stock' => 400],
                    ['name' => 'Yumburger', 'description' => 'Classic beef burger with lettuce and mayo', 'price' => 45.00, 'stock' => 600],
                    ['name' => 'Chickenjoy Bucket', 'description' => '6 pieces crispy fried chicken with sides', 'price' => 299.00, 'stock' => 200],
                    ['name' => 'Jolly Hotdog', 'description' => 'Hotdog with bun and condiments', 'price' => 55.00, 'stock' => 350],
                    ['name' => 'Champ Burger', 'description' => 'Double beef patty burger with cheese', 'price' => 125.00, 'stock' => 300],
                    ['name' => 'Chicken Sandwich', 'description' => 'Grilled chicken sandwich with vegetables', 'price' => 75.00, 'stock' => 250],
                    ['name' => 'Jolly Crispy Fries', 'description' => 'Crispy potato fries', 'price' => 35.00, 'stock' => 800],
                    ['name' => 'Peach Mango Pie', 'description' => 'Sweet peach mango pie dessert', 'price' => 25.00, 'stock' => 400],
                    ['name' => 'Jolly Hotdog with Fries', 'description' => 'Hotdog meal with fries and drink', 'price' => 95.00, 'stock' => 300],
                    ['name' => 'Chickenjoy with Rice', 'description' => 'Crispy chicken with steamed rice', 'price' => 105.00, 'stock' => 350],
                    ['name' => 'Spaghetti with Chickenjoy', 'description' => 'Spaghetti meal with crispy chicken', 'price' => 145.00, 'stock' => 200],
                    ['name' => 'Jolly Kiddie Meal', 'description' => 'Kids meal with toy and drink', 'price' => 85.00, 'stock' => 150],
                    ['name' => 'Jolly Crispy Fries Large', 'description' => 'Large serving of crispy fries', 'price' => 55.00, 'stock' => 400],
                    ['name' => 'Chickenjoy Family Pack', 'description' => '8 pieces chicken with sides for family', 'price' => 399.00, 'stock' => 100],
                ];
                break;

            case 'Ayala Malls':
                $products = [
                    // Fashion & Lifestyle
                    ['name' => 'Uniqlo Basic T-Shirt', 'description' => 'Cotton crew neck t-shirt', 'price' => 590.00, 'stock' => 120],
                    ['name' => 'H&M Denim Jeans', 'description' => 'Classic blue denim jeans', 'price' => 1, 299.00, 'stock' => 80],
                    ['name' => 'Zara Blazer Jacket', 'description' => 'Professional blazer for office wear', 'price' => 2, 990.00, 'stock' => 45],
                    ['name' => 'Nike Air Max Sneakers', 'description' => 'Comfortable running shoes', 'price' => 4, 995.00, 'stock' => 60],
                    ['name' => 'Adidas Sports Shorts', 'description' => 'Athletic shorts for workouts', 'price' => 1, 200.00, 'stock' => 90],
                    ['name' => 'Puma Backpack', 'description' => 'Durable school/work backpack', 'price' => 1, 800.00, 'stock' => 75],
                    ['name' => 'Levi\'s Denim Jacket', 'description' => 'Classic denim jacket', 'price' => 2, 500.00, 'stock' => 40],
                    ['name' => 'Converse Chuck Taylor', 'description' => 'Iconic canvas sneakers', 'price' => 2, 800.00, 'stock' => 55],
                    ['name' => 'Vans Old Skool', 'description' => 'Skateboarding shoes', 'price' => 3, 200.00, 'stock' => 50],
                    ['name' => 'Tommy Hilfiger Polo', 'description' => 'Classic polo shirt', 'price' => 1, 899.00, 'stock' => 65],
                    ['name' => 'Calvin Klein Underwear Set', 'description' => 'Cotton underwear 3-pack', 'price' => 1, 500.00, 'stock' => 100],
                    ['name' => 'Ralph Lauren Dress Shirt', 'description' => 'Formal dress shirt', 'price' => 2, 800.00, 'stock' => 35],
                    ['name' => 'Lacoste Polo Shirt', 'description' => 'Premium cotton polo', 'price' => 2, 200.00, 'stock' => 45],
                    ['name' => 'New Balance Running Shoes', 'description' => 'Professional running shoes', 'price' => 3, 800.00, 'stock' => 40],
                    ['name' => 'Under Armour Sports Bra', 'description' => 'High-impact sports bra', 'price' => 1, 600.00, 'stock' => 70],
                ];
                break;

            case 'Puregold Price Club':
                $products = [
                    // Wholesale & Bulk Items
                    ['name' => 'Coca-Cola 1.5L Bottle', 'description' => 'Refreshing cola drink', 'price' => 65.00, 'stock' => 200],
                    ['name' => 'Pepsi Max 2L Bottle', 'description' => 'Sugar-free cola drink', 'price' => 75.00, 'stock' => 150],
                    ['name' => 'Sprite 1.5L Bottle', 'description' => 'Lemon-lime soft drink', 'price' => 60.00, 'stock' => 180],
                    ['name' => 'Royal Tru Orange 1.5L', 'description' => 'Orange soft drink', 'price' => 55.00, 'stock' => 160],
                    ['name' => 'Mountain Dew 1.5L', 'description' => 'Citrus-flavored soft drink', 'price' => 70.00, 'stock' => 120],
                    ['name' => 'C2 Green Tea 1L', 'description' => 'Refreshing green tea drink', 'price' => 45.00, 'stock' => 100],
                    ['name' => 'Gatorade Sports Drink', 'description' => 'Electrolyte sports drink 500ml', 'price' => 35.00, 'stock' => 250],
                    ['name' => 'Red Bull Energy Drink', 'description' => 'Energy drink 250ml', 'price' => 85.00, 'stock' => 300],
                    ['name' => 'Monster Energy Drink', 'description' => 'High-energy drink 355ml', 'price' => 95.00, 'stock' => 200],
                    ['name' => 'Sting Energy Drink', 'description' => 'Energy drink 250ml', 'price' => 25.00, 'stock' => 400],
                    ['name' => 'Cobra Energy Drink', 'description' => 'Energy drink 350ml', 'price' => 30.00, 'stock' => 350],
                    ['name' => 'Powerade Sports Drink', 'description' => 'Sports drink 500ml', 'price' => 40.00, 'stock' => 180],
                    ['name' => 'Sarsi Root Beer', 'description' => 'Filipino root beer 1.5L', 'price' => 50.00, 'stock' => 90],
                    ['name' => 'Mirinda Orange 1.5L', 'description' => 'Orange-flavored soft drink', 'price' => 65.00, 'stock' => 110],
                    ['name' => '7-Up 1.5L Bottle', 'description' => 'Lemon-lime soft drink', 'price' => 60.00, 'stock' => 130],
                ];
                break;
        }

        foreach ($products as $productData) {
            Product::create([
                'name' => $productData['name'],
                'description' => $productData['description'],
                'price' => $productData['price'],
                'currency' => 'PHP',
                'stock' => $productData['stock'],
                'merchant_id' => $merchant->id,
                'external_id' => strtoupper(substr($merchant->name, 0, 3)) . '_' . uniqid(),
                'source' => 'Manual',
            ]);
        }
    }
}
