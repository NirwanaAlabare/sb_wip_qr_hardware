<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\SignalBit\RftPacking;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;

class SewingService
{
    public static function insertRftPacking($masterPlanId, $soDetId, $noCutSize, $kodeNumbering)
    {
        ini_set('max_execution_time', 360000);

        // Insert RFT for Finishing
        if (Auth::user()->is_sample) {
            $insertRftPacking = RftPacking::create([
                'master_plan_id' => $masterPlanId,
                'so_det_id' => $soDetId,
                'no_cut_size' => $noCutSize,
                'kode_numbering' => $kodeNumbering,
                'status' => 'NORMAL',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'created_by' => Auth::user() && Auth::user()->line ? Auth::user()->line->username : ''
            ]);

            return $insertRftPacking;
        }

        return "Not Sample";
    }
}
