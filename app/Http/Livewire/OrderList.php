<?php

namespace App\Http\Livewire;

use App\Models\SignalBit\MasterPlan;
use App\Models\SignalBit\TemporaryOutput;
use Illuminate\Support\Facades\Auth;
use Illuminate\Session\SessionManager;
use Livewire\Component;
use DB;

class OrderList extends Component
{
    public $orders;
    public $search = '';
    public $date = '';
    public $temporaryOutput;

    public $baseUrl;

    public $listeners = ['setDate'];

    public function mount(SessionManager $session)
    {
        $session->forget('orderInfo');
        $session->forget('orderWsDetails');
        $this->date = date('Y-m-d');
        $this->baseUrl = url('/');
    }

    public function setDate($date)
    {
        $this->date = $date;
    }

    public function render()
    {
        $masterPlanBefore = MasterPlan::selectRaw("max(id) as id")->leftJoin(DB::raw("(SELECT master_plan_id, COUNT(id) total FROM output_defects WHERE defect_status = 'defect' and kode_numbering is not null GROUP BY master_plan_id) defects"), "defects.master_plan_id", "=", "master_plan.id")->where("sewing_line", strtoupper(Auth::user()->line->username))->where("master_plan.cancel", "N")->where("tgl_plan", "<", $this->date)->where("defects.total", ">", "0")->groupBy("master_plan.id_ws", "master_plan.color")->orderBy("tgl_plan", "desc")->limit(1)->get();

        $additionalQuery = "";
        if ($masterPlanBefore) {
            $masterPlanBeforeIds = implode("' , '", $masterPlanBefore->pluck("id")->toArray());

            $additionalQuery = "OR master_plan.id IN ('".$masterPlanBeforeIds."')";
        }

        $this->orders = DB::table('master_plan')
            ->selectRaw("
                MIN(master_plan.id) as id,
                master_plan.id_ws as id_ws,
                master_plan.tgl_plan as plan_date,
                act_costing.kpno as ws_number,
                mastersupplier.supplier as buyer_name,
                act_costing.styleno as style_name,
                output.progress as progress,
                plan.target as target,
                defects.total as total_defect,
                CONCAT(masterproduct.product_group, ' - ', masterproduct.product_item) as product_type
            ")
            ->leftJoin('act_costing', 'act_costing.id', '=', 'master_plan.id_ws')
            ->leftJoin('mastersupplier', 'mastersupplier.id_supplier', '=', 'act_costing.id_buyer')
            ->leftJoin('masterproduct', 'masterproduct.id', '=', 'act_costing.id_product')
            ->leftJoin(DB::raw("(SELECT master_plan_id, COUNT(id) total FROM output_defects WHERE defect_status = 'defect' and kode_numbering is not null GROUP BY master_plan_id) defects"), "defects.master_plan_id", "=", "master_plan.id")
            ->leftJoin(
                DB::raw("
                    (
                        select
                            master_plan.tgl_plan,
                            master_plan.id_ws,
                            count(output_rfts.id) as progress
                        from
                            master_plan
                        left join
                            output_rfts on output_rfts.master_plan_id = master_plan.id
                        where
                            master_plan.sewing_line = '".strtoupper(Auth::user()->line->username)."' AND
                            output_rfts.updated_at between '".$this->date." 00:00:00' AND '".$this->date." 23:59:59' AND
                            master_plan.cancel = 'N'
                        group by
                            master_plan.id_ws,
                            master_plan.tgl_plan
                    ) output"
                ),
                function ($join) {
                    $join->on("output.id_ws", "=", "master_plan.id_ws");
                    $join->on("output.tgl_plan", "=", "master_plan.tgl_plan");
                }
            )
            ->leftJoin(
                DB::raw("
                    (
                        select
                            id_ws,
                            tgl_plan,
                            sum(plan_target) as target
                        from
                            master_plan
                        where
                            sewing_line = '".strtoupper(Auth::user()->line->username)."' AND
                            tgl_plan = '".$this->date."' AND
                            master_plan.cancel = 'N'
                        group by
                            id_ws,
                            tgl_plan
                    ) plan"
                ),
                function ($join) {
                    $join->on("plan.id_ws", "=", "master_plan.id_ws");
                    $join->on("plan.tgl_plan", "=", "master_plan.tgl_plan");
                }
            )
            ->where('master_plan.sewing_line', strtoupper(Auth::user()->line->username))
            ->where('master_plan.cancel', 'N')
            ->where('master_plan.tgl_plan', $this->date)
            ->whereRaw("
                master_plan.tgl_plan = '".$this->date."'
                ".$additionalQuery."
            ")
            ->whereRaw("
                (
                    act_costing.kpno LIKE '%".$this->search."%'
                    OR
                    mastersupplier.supplier LIKE '%".$this->search."%'
                    OR
                    act_costing.styleno LIKE '%".$this->search."%'
                    OR
                    master_plan.color LIKE '%".$this->search."%'
                )
            ")
            ->groupBy(
                'master_plan.id_ws',
                'master_plan.tgl_plan',
                'act_costing.kpno',
                'mastersupplier.supplier',
                'act_costing.styleno',
                'product_type',
                'output.progress',
                'plan.target',
            )
            ->orderBy('master_plan.tgl_plan', 'desc')
            ->get();

        $this->temporaryOutput = TemporaryOutput::where("line_id", Auth::user()->line_id)->
            whereRaw('(DATE(temporary_output.created_at) = "'.$this->date.'" OR DATE(temporary_output.updated_at) = "'.$this->date.'")')->
            where('tipe_output', 'rft')->
            orWhere('tipe_output', 'rework')->
            count();

        return view('livewire.order-list');
    }
}
