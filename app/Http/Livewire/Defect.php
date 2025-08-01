<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Auth;
use App\Models\SignalBit\MasterPlan;
use App\Models\SignalBit\ProductType;
use App\Models\SignalBit\Defect as DefectModel;
use App\Models\SignalBit\DefectType;
use App\Models\SignalBit\DefectArea;
use App\Models\SignalBit\Rft;
use App\Models\SignalBit\Reject;
use App\Models\Nds\Numbering;
use Carbon\Carbon;
use Validator;
use DB;

class Defect extends Component
{
    use WithFileUploads;

    public $orderInfo;
    public $orderWsDetailSizes;
    public $sizeInput;
    public $sizeInputText;
    public $noCutInput;
    public $numberingInput;
    public $defect;

    public $defectTypes;
    public $defectAreas;
    public $productTypes;

    public $defectType;
    public $defectArea;
    public $productType;

    public $defectTypeAdd;
    public $defectAreaAdd;
    public $productTypeAdd;

    public $productTypeImageAdd;
    public $defectAreaPositionX;
    public $defectAreaPositionY;

    public $rapidDefect;
    public $rapidDefectCount;

    protected $rules = [
        'sizeInput' => 'required',
        'noCutInput' => 'required',
        'numberingInput' => 'required',
        // 'productType' => 'required',
        'defectType' => 'required',
        'defectArea' => 'required',
        'defectAreaPositionX' => 'required',
        'defectAreaPositionY' => 'required',
    ];

    protected $messages = [
        'sizeInput.required' => 'Harap scan qr.',
        'noCutInput.required' => 'Harap scan qr.',
        'numberingInput.required' => 'Harap scan qr.',
        // 'productType.required' => 'Harap tentukan tipe produk.',
        'defectType.required' => 'Harap tentukan jenis defect.',
        'defectArea.required' => 'Harap tentukan area defect.',
        'defectAreaPositionX.required' => "Harap tentukan posisi defect area dengan mengklik tombol 'gambar' di samping 'select product type'.",
        'defectAreaPositionY.required' => "Harap tentukan posisi defect area dengan mengklik tombol 'gambar' di samping 'select product type'.",
    ];

    protected $listeners = [
        'setDefectAreaPosition' => 'setDefectAreaPosition',
        'updateWsDetailSizes' => 'updateWsDetailSizes',
        'updateOutputDefect' => 'updateOutput',
        'setAndSubmitInputDefect' => 'setAndSubmitInput',
        'toInputPanel' => 'resetError'
    ];

    private function checkIfNumberingExists($numberingInput = null): bool
    {
        if (DB::table('output_rfts')->where('kode_numbering', ($numberingInput ?? $this->numberingInput))->exists()) {
            $this->addError('numberingInput', 'Kode QR sudah discan di RFT.');
            return true;
        }

        if (DB::table('output_defects')->where('kode_numbering', ($numberingInput ?? $this->numberingInput))->exists()) {
            $this->addError('numberingInput', 'Kode QR sudah discan di Defect.');
            return true;
        }

        if (DB::table('output_rejects')->where('kode_numbering', ($numberingInput ?? $this->numberingInput))->exists()) {
            $this->addError('numberingInput', 'Kode QR sudah discan di Reject.');
            return true;
        }

        return false;
    }

    public function mount(SessionManager $session, $orderWsDetailSizes)
    {
        $this->orderWsDetailSizes = $orderWsDetailSizes;
        $session->put('orderWsDetailSizes', $orderWsDetailSizes);
        $this->sizeInput = null;
        $this->sizeInputText = null;
        $this->noCutInput = null;
        $this->numberingInput = null;

        $this->defectType = null;
        $this->defectArea = null;
        $this->productType = null;

        $this->defectAreaPositionX = null;
        $this->defectAreaPositionY = null;

        $this->rapidDefect = [];
        $this->rapidDefectCount = 0;
    }

    public function dehydrate()
    {
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function resetError() {
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function updateWsDetailSizes($panel)
    {
        $this->sizeInput = null;
        $this->sizeInputText = null;
        $this->noCutInput = null;
        $this->numberingInput = null;

        $this->orderInfo = session()->get('orderInfo', $this->orderInfo);
        $this->orderWsDetailSizes = session()->get('orderWsDetailSizes', $this->orderWsDetailSizes);
        $this->selectedColor = $this->orderInfo->id;
        $this->selectedColorName = $this->orderInfo->color;

        $this->emit('setSelectedSizeSelect2', $this->selectedColor);

        if ($panel == 'defect') {
            $this->emit('qrInputFocus', 'defect');
        }
    }

    public function updateOutput()
    {
        $this->defect = collect(DB::select("select output_defects.*, so_det.size, COUNT(output_defects.id) output from `output_defects` left join `so_det` on `so_det`.`id` = `output_defects`.`so_det_id` where `master_plan_id` = '".$this->orderInfo->id."' and `defect_status` = 'defect' group by so_det.id"));
    }

    public function updatedproductTypeImageAdd()
    {
        $this->validate([
            'productTypeImageAdd' => 'image',
        ]);
    }

    public function submitProductType()
    {
        if ($this->productTypeAdd && $this->productTypeImageAdd) {

            $productTypeImageAddName = md5($this->productTypeImageAdd . microtime()).'.'.$this->productTypeImageAdd->extension();
            $this->productTypeImageAdd->storeAs('public/images', $productTypeImageAddName);

            $createProductType = ProductType::create([
                'product_type' => $this->productTypeAdd,
                'image' => $productTypeImageAddName,
            ]);

            if ($createProductType) {
                $this->emit('alert', 'success', 'Product Type : '.$this->productTypeAdd.' berhasil ditambahkan.');

                $this->productTypeAdd = null;
                $this->productTypeImageAdd = null;
            } else {
                $this->emit('alert', 'error', 'Terjadi kesalahan.');
            }
        } else {
            $this->emit('alert', 'error', 'Harap tentukan nama tipe produk beserta gambarnya');
        }
    }

    public function submitDefectType()
    {
        if ($this->defectTypeAdd) {
            $createDefectType = DefectType::create([
                'defect_type' => $this->defectTypeAdd
            ]);

            if ($createDefectType) {
                $this->emit('alert', 'success', 'Defect type : '.$this->defectTypeAdd.' berhasil ditambahkan.');

                $this->defectTypeAdd = '';
            } else {
                $this->emit('alert', 'error', 'Terjadi kesalahan.');
            }
        } else {
            $this->emit('alert', 'error', 'Harap tentukan nama defect type');
        }
    }

    public function submitDefectArea()
    {
        if ($this->defectAreaAdd) {

            $createDefectArea = DefectArea::create([
                'defect_area' => $this->defectAreaAdd,
            ]);

            if ($createDefectArea) {
                $this->emit('alert', 'success', 'Defect area : '.$this->defectAreaAdd.' berhasil ditambahkan.');

                $this->defectAreaAdd = null;
            } else {
                $this->emit('alert', 'error', 'Terjadi kesalahan.');
            }
        } else {
            $this->emit('alert', 'error', 'Harap tentukan nama defect area');
        }
    }

    public function clearInput()
    {
        $this->sizeInput = '';
    }

    public function selectDefectAreaPosition()
    {
        $masterPlan = DB::connection('mysql_sb')->table('master_plan')->select('gambar')->find($this->orderInfo->id);

        if ($masterPlan) {
            $this->emit('showSelectDefectArea', $masterPlan->gambar);
        } else {
            $this->emit('alert', 'error', 'Harap pilih tipe produk terlebih dahulu');
        }
    }

    public function setDefectAreaPosition($x, $y)
    {
        $this->defectAreaPositionX = $x;
        $this->defectAreaPositionY = $y;
    }

    public function preSubmitInput($value)
    {
        $this->emit('qrInputFocus', 'defect');

        $numberingInput = $value;

        if ($numberingInput) {
            // if (str_contains($numberingInput, 'WIP')) {
            //     $numberingData = DB::connection("mysql_nds")->table("stocker_numbering")->where("kode", $numberingInput)->first();
            // } else {
            //     $numberingCodes = explode('_', $numberingInput);

            //     if (count($numberingCodes) > 2) {
            //         $numberingInput = substr($numberingCodes[0],0,4)."_".$numberingCodes[1]."_".$numberingCodes[2];
            //         $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $numberingInput)->first();
            //     } else {
            //         $numberingData = DB::connection("mysql_nds")->table("month_count")->selectRaw("month_count.*, month_count.id_month_year no_cut_size")->where("id_month_year", $numberingInput)->first();
            //     }
            // }

            // One Straight Format
            $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $numberingInput)->first();

            if ($numberingData) {
                $this->sizeInput = $numberingData->so_det_id;
                $this->sizeInputText = $numberingData->size;
                $this->noCutInput = $numberingData->no_cut_size;
                $this->numberingInput = $numberingInput;
            }
        }

        $validation = Validator::make([
            'sizeInput' => $this->sizeInput,
            'noCutInput' => $this->noCutInput,
            'numberingInput' => $numberingInput
        ], [
            'sizeInput' => 'required',
            'noCutInput' => 'required',
            'numberingInput' => 'required'
        ], [
            'sizeInput.required' => 'Harap scan qr.',
            'noCutInput.required' => 'Harap scan qr.',
            'numberingInput.required' => 'Harap scan qr.'
        ]);

        if ($this->checkIfNumberingExists($numberingInput)) {
            return;
        }

        if ($validation->fails()) {
            $this->emit('qrInputFocus', 'defect');

            $validation->validate();
        } else {
            if ($this->orderWsDetailSizes->where('so_det_id', $this->sizeInput)->count() > 0) {
                $this->emit('clearSelectDefectAreaPoint');

                $this->defectType = null;
                $this->defectArea = null;
                $this->productType = null;
                $this->defectAreaPositionX = null;
                $this->defectAreaPositionY = null;

                $this->numberingInput = $numberingInput;

                $this->validateOnly('sizeInput');

                $this->emit('showModal', 'defect', 'regular');
            } else {
                $this->emit('qrInputFocus', 'defect');

                $this->emit('alert', 'error', "Terjadi kesalahan. QR tidak sesuai.");
            }
        }
    }

    public function submitInput(SessionManager $session)
    {
        $validatedData = $this->validate();

        if ($this->checkIfNumberingExists($validatedData["numberingInput"])){
            return;
        }

        if ($this->orderInfo->tgl_plan == Carbon::now()->format('Y-m-d')) {
            $currentData = $this->orderWsDetailSizes->where('so_det_id', $validatedData["sizeInput"])->first();
            if ($currentData && $this->orderInfo && ($currentData['color'] == $this->orderInfo->color)) {
                $insertDefect = DefectModel::create([
                    'master_plan_id' => $this->orderInfo->id,
                    'no_cut_size' => $validatedData['noCutInput'],
                    'kode_numbering' => $validatedData["numberingInput"],
                    'so_det_id' => $validatedData['sizeInput'],
                    // 'product_type_id' => $validatedData['productType'],
                    'defect_type_id' => $validatedData['defectType'],
                    'defect_area_id' => $validatedData['defectArea'],
                    'defect_area_x' => $validatedData['defectAreaPositionX'],
                    'defect_area_y' => $validatedData['defectAreaPositionY'],
                    'status' => 'NORMAL',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'created_by' => Auth::user()->id
                ]);

                if ($insertDefect) {
                    $type = DefectType::select('defect_type')->find($this->defectType);
                    $area = DefectArea::select('defect_area')->find($this->defectArea);
                    $getSize = DB::table('so_det')
                        ->select('id', 'size')
                        ->where('id', $validatedData['sizeInput'])
                        ->first();

                    $this->emit('alert', 'success', "1 output DEFECT berukuran ".$getSize->size." dengan jenis defect : ".$type->defect_type." dan area defect : ".$area->defect_area." berhasil terekam.");
                    $this->emit('hideModal', 'defect', 'regular');
                    $this->emit('triggerDashboard', Auth::user()->line->username, Carbon::now()->format('Y-m-d'));

                    $this->sizeInput = '';
                    $this->sizeInputText = '';
                    $this->noCutInput = '';
                    $this->numberingInput = '';
                } else {
                    $this->emit('alert', 'error', "Terjadi kesalahan. Output tidak berhasil direkam.");
                }

                $this->emit('qrInputFocus', 'defect');
            } else {
                $this->emit('alert', 'error', "Terjadi kesalahan. QR tidak sesuai.");
            }
        } else {
            $this->emit('alert', 'error', "Tidak dapat input backdate. Harap refresh browser anda.");
        }
    }

    public function setAndSubmitInput($scannedNumbering, $scannedSize, $scannedSizeText, $scannedNoCut) {
        $this->numberingInput = $scannedNumbering;
        $this->sizeInput = $scannedSize;
        $this->sizeInputText = $scannedSizeText;
        $this->noCutInput = $scannedNoCut;

        $this->preSubmitInput($scannedNumbering);
    }

    public function pushRapidDefect($numberingInput, $sizeInput, $sizeInputText) {
        $exist = false;

        if (count($this->rapidDefect) < 100) {
            foreach ($this->rapidDefect as $item) {
                if (($numberingInput && $item['numberingInput'] == $numberingInput)) {
                    $exist = true;
                } else {
                    // if (str_contains($numberingInput, 'WIP')) {
                    //     $numberingData = DB::connection('mysql_nds')->table('stocker_numbering')->where("kode", $numberingInput)->first();
                    // } else {
                    //     $numberingCodes = explode('_', $numberingInput);

                    //     if (count($numberingCodes) > 2) {
                    //         $numberingInput = substr($numberingCodes[0],0,4)."_".$numberingCodes[1]."_".$numberingCodes[2];
                    //         $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $numberingInput)->first();
                    //     } else {
                    //         $numberingData = DB::connection("mysql_nds")->table("month_count")->selectRaw("month_count.*, month_count.id_month_year no_cut_size")->where("id_month_year", $numberingInput)->first();
                    //     }
                    // }

                    // One Straight Format
                    $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $numberingInput)->first();

                    if ($numberingData) {
                        if ($item['masterPlanId'] && $item['masterPlanId'] != $this->orderWsDetailSizes->where("so_det_id", $numberingData->so_det_id)->first()['master_plan_id']) {
                            $exist = true;
                        }
                    }
                }
            }

            if (!$exist) {
                $this->rapidDefectCount += 1;

                if ($numberingInput) {
                    // if (str_contains($numberingInput, 'WIP')) {
                    //     $numberingData = DB::connection('mysql_nds')->table('stocker_numbering')->where("kode", $numberingInput)->first();
                    // } else {
                    //     $numberingCodes = explode('_', $numberingInput);

                    //     if (count($numberingCodes) > 2) {
                    //         $numberingInput = substr($numberingCodes[0],0,4)."_".$numberingCodes[1]."_".$numberingCodes[2];
                    //         $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $numberingInput)->first();
                    //     } else {
                    //         $numberingData = DB::connection("mysql_nds")->table("month_count")->selectRaw("month_count.*, month_count.id_month_year no_cut_size")->where("id_month_year", $numberingInput)->first();
                    //     }
                    // }

                    // One Straight Format
                    $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $numberingInput)->first();

                    if ($numberingData) {
                        $sizeInput = $numberingData->so_det_id;
                        $sizeInputText = $numberingData->size;
                        $noCutInput = $numberingData->no_cut_size;
                        $masterPlanId = $this->orderWsDetailSizes->where("so_det_id", $sizeInput)->first() ? $this->orderWsDetailSizes->where("so_det_id", $sizeInput)->first()['master_plan_id'] : null;

                        array_push($this->rapidDefect, [
                            'numberingInput' => $numberingInput,
                            'sizeInput' => $sizeInput,
                            'sizeInputText' => $sizeInputText,
                            'noCutInput' => $noCutInput,
                            'masterPlanId' => $masterPlanId
                        ]);
                    }
                }

                $this->sizeInput = $sizeInput;
            }
        } else {
            $this->emit('alert', 'error', "Anda sudah mencapai batas rapid scan. Harap klik selesai dahulu.");
        }
    }

    public function preSubmitRapidInput()
    {
        $this->defectType = null;
        $this->defectArea = null;
        $this->productType = null;
        $this->defectAreaPositionX = null;
        $this->defectAreaPositionY = null;

        $this->emit('showModal', 'defect', 'rapid');
    }

    public function submitRapidInput() {
        $rapidDefectFiltered = [];
        $success = 0;
        $fail = 0;

        if ($this->rapidDefect && count($this->rapidDefect) > 0) {
            for ($i = 0; $i < count($this->rapidDefect); $i++) {
                if (((DB::connection('mysql_sb')->table('output_defects')->where('kode_numbering', $this->rapidDefect[$i]['numberingInput'])->count() + DB::connection('mysql_sb')->table('output_rfts')->where('kode_numbering', $this->rapidDefect[$i]['numberingInput'])->count() + DB::connection('mysql_sb')->table('output_rejects')->where('kode_numbering', $this->rapidDefect[$i]['numberingInput'])->count()) < 1) && ($this->orderWsDetailSizes->where('so_det_id', $this->rapidDefect[$i]['sizeInput'])->count() > 0)) {
                    array_push($rapidDefectFiltered, [
                        'master_plan_id' => $this->orderInfo->id,
                        'so_det_id' => $this->rapidDefect[$i]['sizeInput'],
                        'no_cut_size' => $this->rapidDefect[$i]['noCutInput'],
                        'kode_numbering' => $this->rapidDefect[$i]['numberingInput'],
                        'defect_type_id' => $this->defectType,
                        'defect_area_id' => $this->defectArea,
                        'defect_area_x' => $this->defectAreaPositionX,
                        'defect_area_y' => $this->defectAreaPositionY,
                        'status' => 'NORMAL',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'created_by' => Auth::user()->id
                    ]);

                    $success += 1;
                } else {
                    $fail += 1;
                }
            }
        }

        $rapidDefectInsert = DefectModel::insert($rapidDefectFiltered);

        if ($success > 0) {
            $this->emit('alert', 'success', $success." output berhasil terekam. ");

            $this->emit('triggerDashboard', Auth::user()->line->username, Carbon::now()->format('Y-m-d'));
        }

        if ($fail > 0) {
            $this->emit('alert', 'error', $fail." output gagal terekam.");
        }

        $this->emit('hideModal', 'defect', 'rapid');

        $this->rapidDefect = [];
        $this->rapidDefectCount = 0;
    }

    public function render(SessionManager $session)
    {
        // if (isset($this->errorBag->messages()['numberingInput']) && collect($this->errorBag->messages()['numberingInput'])->contains(function ($message) {return Str::contains($message, 'Kode QR sudah discan');})) {
        //     foreach ($this->errorBag->messages()['numberingInput'] as $message) {
        //         $this->emit('alert', 'warning', $message);
        //     }
        // } else if ((isset($this->errorBag->messages()['numberingInput']) && collect($this->errorBag->messages()['numberingInput'])->contains("Harap scan qr.")) || (isset($this->errorBag->messages()['sizeInput']) && collect($this->errorBag->messages()['sizeInput'])->contains("Harap scan qr."))) {
        //     $this->emit('alert', 'error', "Harap scan QR.");
        // }
        if (isset($this->errorBag->messages()['numberingInput'])) {
            foreach ($this->errorBag->messages()['numberingInput'] as $message) {
                $this->emit('alert', 'error', $message);
            }
        }

        $this->orderInfo = $session->get('orderInfo', $this->orderInfo);
        $this->orderWsDetailSizes = $session->get('orderWsDetailSizes', $this->orderWsDetailSizes);

        $this->selectedColor = $this->orderInfo->id;
        $this->selectedColorName = $this->orderInfo->color;

        $this->emit('setSelectedSizeSelect2', $this->selectedColor);

        // Defect types
        $this->productTypes = ProductType::orderBy('product_type')->get();

        // Defect types
        $this->defectTypes = DefectType::whereRaw("(hidden IS NULL OR hidden != 'Y')")->orderBy('defect_type')->get();

        // Defect areas
        $this->defectAreas = DefectArea::orderBy('defect_area')->get();

        // Defect
        $this->defect = collect(DB::select("select output_defects.*, so_det.size, COUNT(output_defects.id) output from `output_defects` left join `so_det` on `so_det`.`id` = `output_defects`.`so_det_id` where `master_plan_id` = '".$this->orderInfo->id."' and `defect_status` = 'defect' group by so_det.id"));

        return view('livewire.defect');
    }
}
