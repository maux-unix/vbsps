<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LiveParkingSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Rentang waktu: 19 Maret 2025 06:00 sampai 17 Mei 2025 21:00
        $start = Carbon::create(2025, 3, 19, 6, 0, 0);
        $end   = Carbon::create(2025, 5, 17, 21, 0, 0);

        $current = $start->copy();
        $batch = [];

        while ($current->lte($end)) {
            $hari = $current->format('l');
            $jam  = intval($current->format('H'));

            // Hitung jumlah slot kosong
            $freeslot = $this->getJumlahKosong($hari, $jam);

            // Buat array slot 0 dan 1
            $zeros = array_fill(0, $freeslot, 0);
            $ones  = array_fill(0, 16 - $freeslot, 1);
            $slots = array_merge($zeros, $ones);
            shuffle($slots);
            $slotString = implode(',', $slots);

            $batch[] = [
                'date'       => $current->format('Y-m-d'),
                'time'       => $current->format('H:i:s'),
                'slot'       => $slotString,
                'freeslot'   => $freeslot,
                'created_at' => $current->toDateTimeString(),
                'updated_at' => $current->toDateTimeString(),
            ];

            // Chunk insert setiap 1000 record untuk performa
            if (count($batch) >= 1000) {
                DB::table('live_parking_slot')->insert($batch);
                $batch = [];
            }

            $current->addSeconds(5);
        }

        // Insert sisa
        if (!empty($batch)) {
            DB::table('live_parking_slot')->insert($batch);
        }
    }

    /**
     * Dapatkan jumlah slot kosong berdasarkan hari dan jam
     */
    private function getJumlahKosong(string $hari, int $jam): int
    {
        $max = 16;
        switch ($hari) {
            case 'Saturday':
            case 'Sunday':
                return rand(8, $max);
            case 'Friday':
                if ($jam >= 12) {
                    return rand(4, 8);
                }
                break;
        }

        if ($jam >= 6 && $jam < 7) {
            return rand(12, $max);
        } elseif ($jam < 9) {
            return rand(8, 12);
        } elseif ($jam < 11) {
            return rand(4, 8);
        } elseif ($jam < 13) {
            return rand(0, 4);
        } elseif ($jam < 15) {
            return rand(2, 6);
        } elseif ($jam < 17) {
            return rand(6, 10);
        } elseif ($jam < 18) {
            return rand(10, 14);
        }

        return rand(4, 12);
    }
}
