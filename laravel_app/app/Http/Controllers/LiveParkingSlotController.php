<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\LiveParkingSlot;
use App\Models\CheckSumAnalysis;

class LiveParkingSlotController extends Controller
{
    /**
     * Tampilkan halaman parkingslot (bila dibuka via browser/web).
     */
    public function index()
    {
        $latest = DB::table('live_parking_slot')
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->first();

        if (! $latest) {
            $date = date('Y-m-d');
            $time = date('H:i:s');
            $slot = array_fill(0, 16, 0);
        } else {
            $date = $latest->date;
            $time = $latest->time;
	        $freeslot = $latest->freeslot;
            $slot = array_map('intval', explode(',', $latest->slot));
        }

        return view('parkingslot', compact('freeslot','date', 'time', 'slot'));
    }

    /**
     * Terima data dari ESP32 (API endpoint) dan hitung statistik.
     */
    // public function store(Request $request)
    // {
    //     // Manual validation agar tidak redirect
    //     $validator = Validator::make($request->all(), [
    //         'slot'     => 'required|string',
    //         'checksum' => 'required|string',
    //     ]);

    //     if ($validator->fails()) {
    //         Cache::increment('live_parking.checksum_mismatch');
    //         $counts = $this->getCounts();
    //         return response()->json([
    //             'error'  => $validator->errors()->first(),
    //             'counts' => $counts,
    //         ], 422);
    //     }

    //     // Ubah slot string ke array
    //     $slotArray = explode(',', str_replace(' ', '', $request->slot));
    //     if (count($slotArray) !== 16) {
    //         Cache::increment('live_parking.checksum_mismatch');
    //         $counts = $this->getCounts();
    //         return response()->json([
    //             'error'  => 'Slot data must contain exactly 16 values.',
    //             'counts' => $counts,
    //         ], 422);
    //     }

    //     // Packing 16 bools ke 2 bytes
    //     $packed = $this->packBoolsToBytes($slotArray);

    //     // Hitung CRC32-C (Castagnoli)
    //     $calculated = $this->crc32CastagnoliEncode($packed);

    //     // Parse checksum client (hex) -> int
    //     $cs = strtolower($request->checksum);
    //     if (str_starts_with($cs, '0x')) {
    //         $cs = substr($cs, 2);
    //     }
    //     $clientChecksum = intval($cs, 16);
        
        
        
    //     //untuk pengecekan
    //     // Data untuk CheckSumAnalysis
    //     $slotStringUntukPengecekan = $request->slot;
    //     $awsCrcUntukPengecekan = $calculated;
    //     $esp32CrcUntukPengecekan = $clientChecksum;

    //     // Simpan ke tabel CheckSumAnalysis
    //     CheckSumAnalysis::create([
    //         'slot_parkir'    => $slotStringUntukPengecekan,
    //         'checksum_aws'   => $awsCrcUntukPengecekan,
    //         'checksum_esp32' => $esp32CrcUntukPengecekan,
    //         'status'         => $awsCrcUntukPengecekan === $esp32CrcUntukPengecekan,
    //     ]); 
        
        
        
    //     // Cek kecocokan checksum
    //     if ($calculated !== $clientChecksum) {
    //         Cache::increment('live_parking.checksum_mismatch');
    //         $counts = $this->getCounts();
    //         return response()->json([
    //             'error'  => 'Checksum mismatch. Data rejected.',
    //             'counts' => $counts,
    //         ], 422);
    //     }

    //     // Jika valid, simpan dan hitung sukses
    //     $freeSlot = count(array_filter($slotArray, fn($s) => $s === '0'));
    //     $now      = Carbon::now();

    //     //data esp32
    //     $esp32Data = implode(',', $slotArray);
    //     $esp32Crc  = $clientChecksum;

    //     //data yang dikirimkan ke aws
    //     $awsData = implode(',', $slotArray);
    //     $awsCrc = $calculated;



    //     // 2) Simpan semua sekaligus di create()
    //     $record = LiveParkingSlot::create([
    //         'date'        => $now->toDateString(),
    //         'time'        => $now->toTimeString(),
    //         'slot'        => implode(',', $slotArray),
    //         'freeslot'    => $freeSlot,
    //         'esp32_data'  => $esp32Data,
    //         'esp32_crc'   => $esp32Crc,
    //         'aws_data'    => $awsData,
    //         'aws_crc'     => $awsCrc,
    //     ]);

        

    //     Cache::increment('live_parking.checksum_success');
    //     $counts = $this->getCounts();


    //     return response()->json([
    //         'message' => 'Data successfully stored',
    //         'data'    => $record,
    //         'counts'  => $counts,
    //     ], 201);
    // }

    public function store(Request $request)
    {
        // Manual validation agar tidak redirect
        $validator = Validator::make($request->all(), [
            'slot'     => 'required|string',
            'checksum' => 'required|string',
        ]);

        if ($validator->fails()) {
            Cache::increment('live_parking.checksum_mismatch');
            $counts = $this->getCounts();
            return response()->json([
                'error'  => $validator->errors()->first(),
                'counts' => $counts,
            ], 422);
        }

        // Bersihkan tanda kutip dari slot string
        $slotString = trim($request->slot, '"\'');
        
        // Ubah slot string ke array
        $slotArray = explode(',', str_replace(' ', '', $slotString));
        
        if (count($slotArray) !== 16) {
            Cache::increment('live_parking.checksum_mismatch');
            $counts = $this->getCounts();
            return response()->json([
                'error'  => 'Slot data must contain exactly 16 values.',
                'counts' => $counts,
            ], 422);
        }

        // Normalisasi data slot untuk memastikan format yang konsisten
        $slotArray = array_map(function($value) {
            $value = trim($value, '"\''); // Bersihkan tanda kutip pada setiap elemen
            return ($value === '0' || $value === 0 || $value === '') ? '0' : '1';
        }, $slotArray);

        // Packing 16 bools ke 2 bytes
        $packed = $this->packBoolsToBytes($slotArray);

        // Hitung CRC32-C (Castagnoli)
        $calculated = $this->crc32CastagnoliEncode($packed);

        // Parse checksum client (hex) -> int
        $cs = strtolower($request->checksum);
        if (str_starts_with($cs, '0x')) {
            $cs = substr($cs, 2);
        }
        $clientChecksum = intval($cs, 16);
        
        // Data untuk CheckSumAnalysis
        $slotStringUntukPengecekan = implode(',', $slotArray); // Gunakan array yang sudah dinormalisasi
        $awsCrcUntukPengecekan = $calculated;
        $esp32CrcUntukPengecekan = $clientChecksum;

        // Simpan ke tabel CheckSumAnalysis
        CheckSumAnalysis::create([
            'slot_parkir'    => $slotStringUntukPengecekan,
            'checksum_aws'   => $awsCrcUntukPengecekan,
            'checksum_esp32' => $esp32CrcUntukPengecekan,
            'status'         => $awsCrcUntukPengecekan === $esp32CrcUntukPengecekan,
        ]); 
        
        // Cek kecocokan checksum
        if ($calculated !== $clientChecksum) {
            Cache::increment('live_parking.checksum_mismatch');
            $counts = $this->getCounts();
            return response()->json([
                'error'  => 'Checksum mismatch. Data rejected.',
                'counts' => $counts,
            ], 422);
        }

        // Jika valid, simpan dan hitung sukses
        $freeSlot = count(array_filter($slotArray, fn($s) => $s === '0'));
        $now = Carbon::now();

        // Data ESP32 - Kita simpan dalam format konsisten tanpa tanda kutip tambahan
        $esp32Data = implode(',', $slotArray);
        $esp32Crc = $clientChecksum;

        // Data yang dikirimkan ke AWS
        $awsData = implode(',', $slotArray);
        $awsCrc = $calculated;

        // Simpan semua sekaligus di create()
        $record = LiveParkingSlot::create([
            'date'        => $now->toDateString(),
            'time'        => $now->toTimeString(),
            'slot'        => implode(',', $slotArray), // Gunakan data yang sudah dibersihkan
            'freeslot'    => $freeSlot,
            'esp32_data'  => $esp32Data,
            'esp32_crc'   => $esp32Crc,
            'aws_data'    => $awsData,
            'aws_crc'     => $awsCrc,
        ]);

        Cache::increment('live_parking.checksum_success');
        $counts = $this->getCounts();

        return response()->json([
            'message' => 'Data successfully stored',
            'data'    => $record,
            'counts'  => $counts,
        ], 201);
    }

    /**
     * Ambil statistik hitung checksum
     */
    private function getCounts(): array
    {
        return [
            'success' => Cache::get('live_parking.checksum_success', 0),
            'mismatch' => Cache::get('live_parking.checksum_mismatch', 0),
        ];
    }

    /**
     * Helper: CRC32-C Castagnoli
     */
    private function crc32CastagnoliEncode(string $input): int
    {
        $crc  = 0xFFFFFFFF;
        $poly = 0x82F63B78;

        foreach (unpack('C*', $input) as $byte) {
            $crc ^= $byte;
            for ($i = 0; $i < 8; $i++) {
                if ($crc & 1) {
                    $crc = (($crc >> 1) ^ $poly) & 0xFFFFFFFF;
                } else {
                    $crc = ($crc >> 1) & 0xFFFFFFFF;
                }
            }
        }

        return (~$crc) & 0xFFFFFFFF;
    }

    /**
     * Helper: pack 16 bools ('0'/'1') ke 2-byte string
     */
    private function packBoolsToBytes(array $bools): string
    {
        $byte1 = 0;
        $byte2 = 0;
        for ($i = 0; $i < 16; $i++) {
            $bit = ($bools[$i] === '1') ? 1 : 0;
            if ($i < 8) {
                $byte1 |= ($bit << (7 - $i));
            } else {
                $byte2 |= ($bit << (15 - $i));
            }
        }
        return chr($byte1) . chr($byte2);
    }
    public function indexingChecksum()
    {
        $data = CheckSumAnalysis::orderBy('id', 'desc')->get();
        return view('checksumanalysis', compact('data'));
    }
}
