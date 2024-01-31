<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Session\SessionManager;
use App\Models\SignalBit\MasterPlan;
use App\Models\SignalBit\ProductType;
use App\Models\SignalBit\DefectType;
use App\Models\SignalBit\DefectArea;
use App\Models\SignalBit\Defect as DefectModel;
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

    protected $rules = [
        'sizeInput' => 'required',
        'numberingInput' => 'required|unique:output_rfts,kode_numbering|unique:output_defects,kode_numbering|unique:output_rejects,kode_numbering',
        // 'productType' => 'required',
        'defectType' => 'required',
        'defectArea' => 'required',
        'defectAreaPositionX' => 'required',
        'defectAreaPositionY' => 'required',
    ];

    protected $messages = [
        'sizeInput.required' => 'Harap scan qr.',
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
    ];

    public function mount(SessionManager $session, $orderWsDetailSizes)
    {
        $this->orderWsDetailSizes = $orderWsDetailSizes;
        $session->put('orderWsDetailSizes', $orderWsDetailSizes);
        $this->output = 0;
        $this->sizeInput = null;
        $this->sizeInputText = null;
        $this->numberingInput = null;

        $this->defectType = null;
        $this->defectArea = null;
        $this->productType = null;

        $this->defectAreaPositionX = null;
        $this->defectAreaPositionY = null;
    }

    public function dehydrate()
    {
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function updateWsDetailSizes($panel)
    {
        $this->sizeInput = null;
        $this->sizeInputText = null;
        $this->numberingInput = null;

        $this->orderInfo = session()->get('orderInfo', $this->orderInfo);
        $this->orderWsDetailSizes = session()->get('orderWsDetailSizes', $this->orderWsDetailSizes);

        if ($panel == 'defect') {
            $this->emit('renderQrScanner', 'defect');
        }
    }

    public function updateOutput()
    {
        $this->output = DefectModel::
            where('master_plan_id', $this->orderInfo->id)->
            where('defect_status', 'defect')->
            count();

        $this->defect = DefectModel::
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
        $masterPlan = MasterPlan::select('gambar')->find($this->orderInfo->id);

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
        $validation = Validator::make([
            'sizeInput' => $this->sizeInput,
            'numberingInput' => $this->numberingInput
        ], [
            'sizeInput' => 'required',
            'numberingInput' => 'required|unique:output_rfts,kode_numbering|unique:output_defects,kode_numbering|unique:output_rejects,kode_numbering'
        ], [
            'sizeInput.required' => 'Harap scan qr.',
            'numberingInput.required' => 'Harap scan qr.',
            'numberingInput.unique' => 'Kode qr sudah discan.',
        ]);

        if ($validation->fails()) {
            $this->emit('renderQrScanner', 'defect');

            $validation->validate();
        } else {
            if ($this->orderWsDetailSizes->where('size', $this->sizeInputText)->count() > 0) {
                $this->emit('clearSelectDefectAreaPoint');

                $this->defectType = null;
                $this->defectArea = null;
                $this->productType = null;
                $this->defectAreaPositionX = null;
                $this->defectAreaPositionY = null;

                $this->validateOnly('sizeInput');

                $this->emit('showModal', 'defect');
            } else {
                $this->emit('renderQrScanner', 'defect');

                $this->emit('alert', 'error', "Terjadi kesalahan. QR tidak sesuai.");
            }
        }
    }

    public function submitInput(SessionManager $session)
    {
        $validatedData = $this->validate();

        if ($this->orderWsDetailSizes->where('size', $this->sizeInputText)->count() > 0) {
            $insertDefect = DefectModel::create([
                'master_plan_id' => $this->orderInfo->id,
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
                $this->emit('hideModal', 'defect');

                $this->sizeInput = '';
                $this->sizeInputText = '';
            } else {
                $this->emit('alert', 'error', "Terjadi kesalahan. Output tidak berhasil direkam.");
            }

            $this->emit('renderQrScanner', 'defect');
        } else {
            $this->emit('alert', 'error', "Terjadi kesalahan. QR tidak sesuai.");
        }
    }

    public function render(SessionManager $session)
    {
        $this->orderInfo = $session->get('orderInfo', $this->orderInfo);
        $this->orderWsDetailSizes = $session->get('orderWsDetailSizes', $this->orderWsDetailSizes);

        // Get total output
        $this->output = DefectModel::
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
        $this->defect = DefectModel::
            where('master_plan_id', $this->orderInfo->id)->
            where('defect_status', 'defect')->
            whereRaw("DATE(updated_at) = '".date('Y-m-d')."'")->
            get();

        return view('livewire.defect');
    }
}
