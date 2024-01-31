<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Session\SessionManager;
use App\Models\SignalBit\Rft as RftModel;
use App\Models\SignalBit\Rework;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;

class Rft extends Component
{
    public $orderInfo;
    public $orderWsDetailSizes;
    public $output;
    public $sizeInput;
    public $sizeInputText;
    public $numberingInput;
    public $rft;

    protected $rules = [
        'sizeInput' => 'required',
        'numberingInput' => 'required|unique:output_rfts,kode_numbering|unique:output_defects,kode_numbering|unique:output_rejects,kode_numbering',
    ];

    protected $messages = [
        'sizeInput.required' => 'Harap scan qr.',
        'numberingInput.required' => 'Harap scan qr.',
        'numberingInput.unique' => 'Kode qr sudah discan.',
    ];

    protected $listeners = [
        'updateWsDetailSizes' => 'updateWsDetailSizes',
    ];

    public function mount(SessionManager $session, $orderWsDetailSizes)
    {
        $this->orderWsDetailSizes = $orderWsDetailSizes;
        $session->put('orderWsDetailSizes', $orderWsDetailSizes);
        $this->output = 0;
        $this->sizeInput = null;
        $this->sizeInputText = null;
        $this->numberingInput = null;
        $this->submitting = false;
    }

    public function updateWsDetailSizes($panel)
    {
        $this->sizeInput = null;
        $this->sizeInputText = null;
        $this->numberingInput = null;

        $this->orderInfo = session()->get('orderInfo', $this->orderInfo);
        $this->orderWsDetailSizes = session()->get('orderWsDetailSizes', $this->orderWsDetailSizes);

        if ($panel == 'rft') {
            $this->emit('renderQrScanner', 'rft');
        }
    }

    public function updateOutput()
    {
        $this->output = RftModel::
            where('master_plan_id', $this->orderInfo->id)->
            where('status', 'NORMAL')->
            count();

        $this->rft = RftModel::
            where('master_plan_id', $this->orderInfo->id)->
            where('status', 'NORMAL')->
            whereRaw("DATE(updated_at) = '".date('Y-m-d')."'")->
            get();
    }

    public function clearInput()
    {
        $this->sizeInput = null;
    }

    public function submitInput()
    {
        $this->emit('renderQrScanner', 'rft');

        $validatedData = $this->validate();

        if ($this->orderWsDetailSizes->where('size', $this->sizeInputText)->count() > 0) {
            $insertRft = RftModel::create([
                'master_plan_id' => $this->orderInfo->id,
                'so_det_id' => $this->sizeInput,
                'kode_numbering' => $this->numberingInput,
                'status' => 'NORMAL',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            if ($insertRft) {
                $getSize = DB::table('so_det')
                    ->select('id', 'size')
                    ->where('id', $this->sizeInput)
                    ->first();

                $this->emit('alert', 'success', "1 output berukuran ".$getSize->size." berhasil terekam.");

                $this->sizeInput = '';
                $this->sizeInputText = '';
            } else {
                $this->emit('alert', 'error', "Terjadi kesalahan. Output tidak berhasil direkam.");
            }
        } else {
            $this->emit('alert', 'error', "Terjadi kesalahan. QR tidak sesuai.");
        }

        $this->emit('renderQrScanner', 'rft');
    }

    public function render(SessionManager $session)
    {
        $this->orderInfo = $session->get('orderInfo', $this->orderInfo);
        $this->orderWsDetailSizes = $session->get('orderWsDetailSizes', $this->orderWsDetailSizes);

        // Get total output
        $this->output = RftModel::
            where('master_plan_id', $this->orderInfo->id)->
            where('status', 'normal')->
            count();

        // Rft
        $this->rft = RftModel::
            where('master_plan_id', $this->orderInfo->id)->
            where('status', 'NORMAL')->
            whereRaw("DATE(updated_at) = '".date('Y-m-d')."'")->
            get();

        return view('livewire.rft');
    }

    public function dehydrate()
    {
        $this->resetValidation();
        $this->resetErrorBag();
    }
}
