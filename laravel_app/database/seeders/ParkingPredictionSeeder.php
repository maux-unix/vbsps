<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class ParkingPredictionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Generate 5 contoh data prediksi
        for ($i = 0; $i < 5; $i++) {
            $date = now()->addDays($i)->toDateString();

            // Generate 15 angka freeslots max 16 dipisah koma
            $freeslots = [];
            for ($j = 0; $j < 15; $j++) {
                $freeslots[] = rand(0, 16);
            }
            $freeslotsString = implode(',', $freeslots);

            DB::table('parking_prediction')->insert([
                'date' => $date,
                'freeslots' => $freeslotsString,
            ]);
        }
    }
}
