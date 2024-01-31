<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Session\SessionManager;
use App\Models\SignalBit\Reject as RejectModel;
use Carbon\Carbon;
use DB;

class Reject extends Component
{
    public $orderInfo;
    public $orderWsDetailSizes;
    public $output;
    public $sizeInput;
    public $sizeInputText;
    public $numberingInput;
    public $reject;

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
        'updateWsDetailSizes' => 'updateWsDetailSizes'
    ];

    public function mount(SessionManager $session, $orderWsDetailSizes)
    {
        $this->orderWsDetailSizes = $orderWsDetailSizes;
        $session->put('orderWsDetailSizes', $orderWsDetailSizes);
        $this->sizeInput = null;
    }

    public function updateWsDetailSizes($panel)
    {
        $this->sizeInput = null;
        $this->sizeInputText = null;
        $this->numberingInput = null;

        $this->orderInfo = session()->get('orderInfo', $this->orderInfo);
        $this->orderWsDetailSizes = session()->get('orderWsDetailSizes', $this->orderWsDetailSizes);

        if ($panel == 'reject') {
            $this->emit('renderQrScanner', 'reject');
        }
    }

    public function updateOutput()
    {
        // Get total output
        $this->output = RejectModel::
            where('master_plan_id', $this->orderInfo->id)->
            count();

        // Reject
        $this->reject = RejectModel::
            where('master_plan_id', $this->orderInfo->id)->
            whereRaw("DATE(updated_at) = '".date('Y-m-d')."'")->
            get();
    }

    public function clearInput()
    {
        $this->sizeInput = null;
        $this->numberingInput = null;
    }

    public function submitInput(SessionManager $session)
    {
        $this->emit('renderQrScanner', 'reject');

        $validatedData = $this->validate();

        if ($this->orderWsDetailSizes->where('size', $this->sizeInputText)->count() > 0) {
            $insertReject = RejectModel::create([
                'master_plan_id' => $this->orderInfo->id,
                'so_det_id' => $this->sizeInput,
                'kode_numbering' => $this->numberingInput,
                'status' => 'NORMAL',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            if ($insertReject) {
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

        $this->emit('renderQrScanner', 'reject');
    }

    public function render(SessionManager $session)
    {
        $this->orderInfo = $session->get('orderInfo', $this->orderInfo);
        $this->orderWsDetailSizes = $session->get('orderWsDetailSizes', $this->orderWsDetailSizes);

        // Get total output
        $this->output = RejectModel::
            where('master_plan_id', $this->orderInfo->id)->
            count();

        // Reject
        $this->reject = RejectModel::
            where('master_plan_id', $this->orderInfo->id)->
            whereRaw("DATE(updated_at) = '".date('Y-m-d')."'")->
            get();

        return view('livewire.reject');
    }

    public function dehydrate()
    {
        $this->resetValidation();
        $this->resetErrorBag();
    }
}
