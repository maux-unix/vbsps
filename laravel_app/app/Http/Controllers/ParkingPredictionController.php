<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParkingPredictionController extends Controller
{
    public function index()
    {
        // 1) Tentukan tanggal hari ini
        $today = date('Y-m-d');

        // 2) Coba ambil record untuk tanggal hari ini
        $record = DB::table('parking_prediction')
            ->where('date', $today)
            ->first();

        if ($record) {
            // 3a) Jika ada, gunakan data yang ada
            $date = $record->date;
            $freeslots = array_map('intval', explode(',', $record->freeslots));
        } else {
            // 3b) Jika belum ada, default semua slot = 0
            $date = $today;
            $freeslots = array_fill(0, 15, 0);
        }

        // 4) Kirim ke view
        return view('parkingprediction', [
            'date'      => $date,
            'freeslots' => $freeslots,
        ]);
    }
}
