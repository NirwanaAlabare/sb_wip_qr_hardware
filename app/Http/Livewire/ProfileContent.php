<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\SignalBit\Rft;
use App\Models\SignalBit\Defect;
use App\Models\SignalBit\Reject;
use App\Models\SignalBit\Rework;
use App\Models\SignalBit\MasterPlan;
use Livewire\WithPagination;
use DB;

class ProfileContent extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $masterPlan;
    public $dateFrom;
    public $dateTo;

    public function mount()
    {
        $masterPlan = session()->get('orderInfo');
        $this->masterPlan = $masterPlan ? $masterPlan->id : null;
        $this->dateFrom = $this->dateFrom ? $this->dateFrom : date('Y-m-d');
        $this->dateTo = $this->dateTo ? $this->dateTo : date('Y-m-d');
    }

    public function render()
    {
        $masterPlan = session()->get('orderInfo');
        $this->masterPlan = $masterPlan ? $masterPlan->id : null;

        $totalRftSql = Rft::select('output_rfts.*')->
            leftJoin('master_plan', 'master_plan.id', '=', 'output_rfts.master_plan_id')->
            where('master_plan.sewing_line', Auth::user()->line->username);
            if ($this->masterPlan) {
                $totalRftSql->where('master_plan.id', $this->masterPlan);
            }
        $totalRft = $totalRftSql->whereRaw("DATE(output_rfts.created_at) >= '".$this->dateFrom."'")->
            where("status", "NORMAL")->
            whereRaw("DATE(output_rfts.created_at) <= '".$this->dateTo."'")->
            count();

        $totalDefectSql = Defect::select('output_defects.*')->
            leftJoin('master_plan', 'master_plan.id', '=', 'output_defects.master_plan_id')->
            where('output_defects.defect_status', 'defect')->
            where('master_plan.sewing_line', Auth::user()->line->username)->
            where('output_defects.defect_status', 'defect');
            if ($this->masterPlan) {
                $totalDefectSql->where('master_plan.id', $this->masterPlan);
            }
        $totalDefect = $totalDefectSql->whereRaw("DATE(output_defects.created_at) >= '".$this->dateFrom."'")->
            whereRaw("DATE(output_defects.created_at) <= '".$this->dateTo."'")->
            count();

        $totalRejectSql = Reject::select('output_rejects.*')->
            leftJoin('master_plan', 'master_plan.id', '=', 'output_rejects.master_plan_id')->
            where('master_plan.sewing_line', Auth::user()->line->username);
            if ($this->masterPlan) {
                $totalRejectSql->where('master_plan.id', $this->masterPlan);
            }
        $totalReject = $totalRejectSql->whereRaw("DATE(output_rejects.created_at) >= '".$this->dateFrom."'")->
            whereRaw("DATE(output_rejects.created_at) <= '".$this->dateTo."'")->
            count();

        $totalReworkSql = Rework::select('output_reworks.*')->
            leftJoin('output_defects', 'output_defects.id', '=', 'output_reworks.defect_id')->
            leftJoin('master_plan', 'master_plan.id', '=', 'output_defects.master_plan_id')->
            where('master_plan.sewing_line', Auth::user()->line->username);
            if ($this->masterPlan) {
                $totalReworkSql->where('master_plan.id', $this->masterPlan);
            }
        $totalRework = $totalReworkSql->whereRaw("DATE(output_reworks.created_at) >= '".$this->dateFrom."'")->
            whereRaw("DATE(output_reworks.created_at) <= '".$this->dateTo."'")->
            count();

        return view('livewire.profile-content', [
            'totalRft' => $totalRft,
            'totalDefect' => $totalDefect,
            'totalReject' => $totalReject,
            'totalRework' => $totalRework,
        ]);
    }
}
