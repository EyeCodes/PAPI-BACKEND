<?php

namespace Database\Seeders;

use App\Models\BusinessType;
use Illuminate\Database\Seeder;

class BusinessTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businessTypes = [
            ['type' => 'Restaurant', 'description' => 'Food service and dining establishments'],
            ['type' => 'Information Technology (IT)', 'description' => 'Software development, IT services, and technology solutions'],
            ['type' => 'Retail Store', 'description' => 'Shops selling goods directly to consumers'],
            ['type' => 'Construction', 'description' => 'Building, renovation, and construction services'],
            ['type' => 'Healthcare', 'description' => 'Medical services, clinics, and health facilities'],
            ['type' => 'Automotive', 'description' => 'Car dealerships, repair shops, and automotive services'],
            ['type' => 'Beauty & Wellness', 'description' => 'Salons, spas, and wellness centers'],
            ['type' => 'Education & Training', 'description' => 'Schools, training centers, and educational services'],
            ['type' => 'Real Estate', 'description' => 'Property sales, rentals, and real estate services'],
            ['type' => 'Manufacturing', 'description' => 'Production and manufacturing of goods'],
            ['type' => 'Transportation & Logistics', 'description' => 'Shipping, delivery, and transportation services'],
            ['type' => 'Financial Services', 'description' => 'Banking, insurance, and financial consulting'],
            ['type' => 'Marketing & Advertising', 'description' => 'Marketing agencies and advertising services'],
            ['type' => 'Legal Services', 'description' => 'Law firms and legal consulting'],
            ['type' => 'Consulting', 'description' => 'Business and professional consulting services'],
            ['type' => 'E-commerce', 'description' => 'Online retail and digital commerce'],
            ['type' => 'Agriculture', 'description' => 'Farming, livestock, and agricultural services'],
            ['type' => 'Entertainment', 'description' => 'Event planning, entertainment venues, and media'],
            ['type' => 'Hospitality', 'description' => 'Hotels, resorts, and accommodation services'],
            ['type' => 'Fitness & Sports', 'description' => 'Gyms, sports facilities, and fitness services'],
            ['type' => 'Photography', 'description' => 'Photography studios and professional photography services'],
            ['type' => 'Cleaning Services', 'description' => 'Residential and commercial cleaning services'],
            ['type' => 'Security Services', 'description' => 'Security agencies and protection services'],
            ['type' => 'Telecommunications', 'description' => 'Phone, internet, and communication services'],
            ['type' => 'Travel & Tourism', 'description' => 'Travel agencies and tourism services'],
            ['type' => 'Printing & Publishing', 'description' => 'Printing services and publishing companies'],
            ['type' => 'Pet Services', 'description' => 'Veterinary clinics, pet grooming, and pet care'],
            ['type' => 'Home Services', 'description' => 'Plumbing, electrical, and home maintenance services'],
            ['type' => 'Grocery & Supermarket', 'description' => 'Food retail and grocery stores'],
            ['type' => 'Pharmacy', 'description' => 'Pharmaceutical retail and medical supplies'],
        ];

        foreach ($businessTypes as $businessType) {
            BusinessType::create($businessType);
        }
    }
}
