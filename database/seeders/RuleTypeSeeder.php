<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RuleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        DB::table('rule_types')->insert([
            [
            'name'=> 'purhcase_based',
            'label'=>'Purchase Based',
            'created_at'=>$now,
            'updated_at'=>$now,
        ],
        [
            'name'=> 'referral',
            'label'=>'Referral',
            'created_at'=>$now,
            'updated_at'=>$now,
        ],
        [
            'name'=> 'bonus',
            'label'=>'Bonus',
            'created_at'=>$now,
            'updated_at'=>$now,
        ],
    ]);
    }
}
