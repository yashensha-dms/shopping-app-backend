<?php

namespace Database\Seeders;

use App\Enums\OrderEnum;
use App\Models\OrderStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $orderStatus = [
            [
                'name' => OrderEnum::PENDING,
                'system_reserve' => 1,
                'sequence' => '1'
            ],
            [
                'name' => OrderEnum::PACKED,
                'system_reserve' => 1,
                'sequence' => '2'
            ],
            [
                'name' => OrderEnum::OUT_FOR_DELIVERY,
                'system_reserve' => 1,
                'sequence' => '3'
            ],
            [
                'name' => OrderEnum::DELIVERED,
                'system_reserve' => 1,
                'sequence' => '4'
            ],
            [
                'name' => OrderEnum::RETURNED,
                'system_reserve' => 1,
                'sequence' => '5'
            ],
            [
                'name' => OrderEnum::CANCELLED,
                'system_reserve' => 1,
                'sequence' => '6'
            ],
            [
                'name' => OrderEnum::PROCESSING,
                'system_reserve' => 1,
                'sequence' => '7'
            ],
            [
                'name' => OrderEnum::SHIPPED,
                'system_reserve' => 1,
                'sequence' => '8'
            ]
        ];

        // Temporarily shift existing sequences to avoid unique constraint violations
        $existingStatuses = OrderStatus::all();
        foreach ($existingStatuses as $status) {
            $status->update(['sequence' => $status->sequence + 100]);
        }

        foreach ($orderStatus as $status) {
            $existingStatus = OrderStatus::where('name', $status['name'])->first();
            if (!$existingStatus) {
                OrderStatus::create([
                    'name' => $status['name'],
                    'system_reserve' =>  $status['system_reserve'],
                    'sequence' => $status['sequence']
                ]);
            } else {
                $existingStatus->update(['sequence' => $status['sequence']]);
            }
        }

        DB::table('seeders')->updateOrInsert([
            'name' => 'OrderStatusSeeder',
            'is_completed' => true
        ]);
    }
}
