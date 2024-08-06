<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Session\SessionManager;
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
    public $output;
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
        'numberingInput' => 'required|unique:output_rfts,kode_numbering|unique:output_defects,kode_numbering|unique:output_rejects,kode_numbering',
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
        'numberingInput.unique' => 'Kode qr sudah discan.',
        // 'productType.required' => 'Harap tentukan tipe produk.',
        'defectType.required' => 'Harap tentukan jenis defect.',
        'defectArea.required' => 'Harap tentukan area defect.',
        'defectAreaPositionX.required' => "Harap tentukan posisi defect area dengan mengklik tombol 'gambar' di samping 'select product type'.",
        'defectAreaPositionY.required' => "Harap tentukan posisi defect area dengan mengklik tombol 'gambar' di samping 'select product type'.",
    ];

    protected $listeners = [
        'setDefectAreaPosition' => 'setDefectAreaPosition',
        'updateWsDetailSizes' => 'updateWsDetailSizes',
        'setAndSubmitInputDefect' => 'setAndSubmitInput',
        'toInputPanel' => 'resetError'
    ];

    public function mount(SessionManager $session, $orderWsDetailSizes)
    {
        $this->orderWsDetailSizes = $orderWsDetailSizes;
        $session->put('orderWsDetailSizes', $orderWsDetailSizes);
        $this->output = 0;
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

        if ($panel == 'defect') {
            $this->emit('qrInputFocus', 'defect');
        }
    }

    public function updateOutput()
    {
        $this->output = DB::connection('mysql_sb')->table('output_defects')->
            where('master_plan_id', $this->orderInfo->id)->
            where('defect_status', 'defect')->
            count();

        $this->defect = DB::connection('mysql_sb')->table('output_defects')->
            where('master_plan_id', $this->orderInfo->id)->
            where('defect_status', 'defect')->
            whereRaw("DATE(updated_at) = '".date('Y-m-d')."'")->
            get();
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
                $this->emit('alert', 'success', 'Product Time : '.$this->productTypeAdd.' berhasil ditambahkan.');

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

    public function preSubmitInput()
    {
        if ($this->numberingInput) {
            if (str_contains($this->numberingInput, 'WIP')) {
                $numberingData = DB::connection("mysql_nds")->table("stocker_numbering")->where("kode", $this->numberingInput)->first();
            } else {
                $numberingData = DB::connection("mysql_nds")->table("month_count")->selectRaw("month_count.*, month_count.id_month_year no_cut_size")->where("id_month_year", $this->numberingInput)->first();
            }

            if ($numberingData) {
                $this->sizeInput = $numberingData->so_det_id;
                $this->sizeInputText = $numberingData->size;
                $this->noCutInput = $numberingData->no_cut_size;
            }
        }

        $validation = Validator::make([
            'sizeInput' => $this->sizeInput,
            'noCutInput' => $this->noCutInput,
            'numberingInput' => $this->numberingInput
        ], [
            'sizeInput' => 'required',
            'noCutInput' => 'required',
            'numberingInput' => 'required|unique:output_rfts,kode_numbering|unique:output_defects,kode_numbering|unique:output_rejects,kode_numbering'
        ], [
            'sizeInput.required' => 'Harap scan qr.',
            'noCutInput.required' => 'Harap scan qr.',
            'numberingInput.required' => 'Harap scan qr.',
            'numberingInput.unique' => 'Kode qr sudah discan.',
        ]);

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

        if ($this->orderWsDetailSizes->where('so_det_id', $this->sizeInput)->count() > 0) {
            $insertDefect = DefectModel::create([
                'master_plan_id' => $this->orderInfo->id,
                'no_cut_size' => $this->noCutInput,
                'kode_numbering' => $this->numberingInput,
                'so_det_id' => $this->sizeInput,
                // 'product_type_id' => $this->productType,
                'defect_type_id' => $this->defectType,
                'defect_area_id' => $this->defectArea,
                'defect_area_x' => $this->defectAreaPositionX,
                'defect_area_y' => $this->defectAreaPositionY,
                'status' => 'NORMAL',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            if ($insertDefect) {
                $type = DefectType::select('defect_type')->find($this->defectType);
                $area = DefectArea::select('defect_area')->find($this->defectArea);
                $getSize = DB::table('so_det')
                    ->select('id', 'size')
                    ->where('id', $this->sizeInput)
                    ->first();

                $this->emit('alert', 'success', "1 output DEFECT berukuran ".$getSize->size." dengan jenis defect : ".$type->defect_type." dan area defect : ".$area->defect_area." berhasil terekam.");
                $this->emit('hideModal', 'defect', 'regular');

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
    }

    public function setAndSubmitInput($scannedNumbering, $scannedSize, $scannedSizeText, $scannedNoCut) {
        $this->numberingInput = $scannedNumbering;
        $this->sizeInput = $scannedSize;
        $this->sizeInputText = $scannedSizeText;
        $this->noCutInput = $scannedNoCut;

        $this->preSubmitInput();
    }

    public function pushRapidDefect($numberingInput, $sizeInput, $sizeInputText) {
        $exist = false;

        foreach ($this->rapidDefect as $item) {
            if (($numberingInput && $item['numberingInput'] == $numberingInput)) {
                $exist = true;
            }
        }

        if (!$exist) {
            if ($numberingInput) {
                $this->rapidDefectCount += 1;

                if (str_contains($numberingInput, 'WIP')) {
                    $numberingData = DB::connection("mysql_nds")->table("stocker_numbering")->where("kode", $numberingInput)->first();
                } else {
                    $numberingData = DB::connection("mysql_nds")->table("month_count")->selectRaw("month_count.*, month_count.id_month_year no_cut_size")->where("id_month_year", $numberingInput)->first();
                }

                if ($numberingData) {
                    $sizeInput = $numberingData->so_det_id;
                    $sizeInputText = $numberingData->size;
                    $noCutInput = $numberingData->no_cut_size;

                    array_push($this->rapidDefect, [
                        'numberingInput' => $numberingInput,
                        'sizeInput' => $sizeInput,
                        'sizeInputText' => $sizeInputText,
                        'noCutInput' => $noCutInput,
                    ]);
                }
            }
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
                        'updated_at' => Carbon::now()
                    ]);

                    $success += 1;
                } else {
                    $fail += 1;
                }
            }
        }

        $rapidDefectInsert = DefectModel::insert($rapidDefectFiltered);

        $this->emit('alert', 'success', $success." output berhasil terekam. ");
        $this->emit('alert', 'error', $fail." output gagal terekam.");
        $this->emit('hideModal', 'defect', 'rapid');

        $this->rapidDefect = [];
        $this->rapidDefectCount = 0;
    }

    public function render(SessionManager $session)
    {
        $this->orderInfo = $session->get('orderInfo', $this->orderInfo);
        $this->orderWsDetailSizes = $session->get('orderWsDetailSizes', $this->orderWsDetailSizes);

        // Get total output
        $this->output = DB::connection('mysql_sb')->table('output_defects')->
            where('master_plan_id', $this->orderInfo->id)->
            where('defect_status', 'defect')->
            count();

        // Defect types
        $this->productTypes = ProductType::orderBy('product_type')->get();

        // Defect types
        $this->defectTypes = DefectType::orderBy('defect_type')->get();

        // Defect areas
        $this->defectAreas = DefectArea::orderBy('defect_area')->get();

        // Defect
        $this->defect = DB::connection('mysql_sb')->table('output_defects')->
            where('master_plan_id', $this->orderInfo->id)->
            where('defect_status', 'defect')->
            whereRaw("DATE(updated_at) = '".date('Y-m-d')."'")->
            get();

        return view('livewire.defect');
    }
}
