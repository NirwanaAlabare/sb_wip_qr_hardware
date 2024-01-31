<div>
    <div class="loading-container-fullscreen" wire:loading wire:target="submitInput, updateOrder">
        <div class="loading-container">
            <div class="loading"></div>
        </div>
    </div>
    {{-- Production Input --}}
    <div class="production-input row row-gap-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center bg-rft text-light">
                    <p class="mb-0 fs-5">Scan QR</p>
                </div>
                <div class="card-body" wire:ignore.self>
                    @error('numberingInput')
                        <div class="alert alert-danger alert-dismissible fade show mb-0 rounded-0" role="alert">
                            <strong>Error</strong> {{$message}}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @enderror
                    {{-- <div id="rft-reader" width="600px"></div> --}}
                    <input type="text" class="qty-input" id="scannedItemRft" name="scannedItemRft" onkeyup="submit(this,e)">
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center bg-rft text-light">
                    <p class="mb-0 fs-5">Size</p>
                    <div class="d-flex justify-content-end align-items-center gap-1">
                        <div class="d-flex align-items-center gap-3 me-3">
                            <p class="mb-1 fs-5">RFT</p>
                            <p class="mb-1 fs-5">:</p>
                            <p id="rft-qty" class="mb-1 fs-5">{{ $output }}</p>
                        </div>
                        <button class="btn btn-dark" wire:click="$emit('preSubmitUndo', 'rft')">
                            <i class="fa-regular fa-rotate-left"></i>
                        </button>
                        {{-- <button class="btn btn-dark">
                            <i class="fa-regular fa-gear"></i>
                        </button> --}}
                    </div>
                </div>
                @error('sizeInput')
                    <div class="alert alert-danger alert-dismissible fade show mb-0 rounded-0" role="alert">
                        <strong>Error</strong> {{$message}}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @enderror
                <div class="card-body">
                    <div class="loading-container hidden" id="loading-rft">
                        <div class="loading mx-auto"></div>
                    </div>
                    <div class="row h-100 row-gap-3" id="content-rft">
                        @foreach ($orderWsDetailSizes as $order)
                            <div class="col-md-4">
                                <div class="bg-rft text-white w-100 h-100 py-auto rounded-3 d-flex flex-column justify-content-center align-items-center">
                                    <p class="fs-3 mb-0">{{ $order->size }}</p>
                                    <p class="fs-5 mb-0">{{ $rft->where('so_det_id', $order->so_det_id)->count() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <p class="text-center opacity-50 my-0"><small><i>{{ date('Y') }} &copy; Nirwana Digital Solution</i></small></p>
    </div>

    {{-- Footer --}}
    <footer class="footer fixed-bottom py-3">
        <div class="container-fluid">
            <div class="d-flex justify-content-end">
                <button class="btn btn-dark btn-lg ms-auto fs-3" wire:click='submitInput' {{ $submitting ? 'disabled' : ''}}>SELESAI</button>
            </div>
        </div>
    </footer>
</div>

@push('scripts')
    <script>
        // Scan QR
        // if (document.getElementById("rft-reader")) {
        //     function onScanSuccess(decodedText, decodedResult) {
        //         // handle the scanned code as you like, for example:

        //         // break decoded text
        //         let breakDecodedText = decodedText.split('-');

        //         console.log(breakDecodedText);

        //         // set kode_numbering
        //         @this.numberingInput = breakDecodedText[3];

        //         // set so_det_id
        //         @this.sizeInput = breakDecodedText[4];

        //         // set size
        //         @this.sizeInputText = breakDecodedText[5];

        //         // submit
        //         @this.submitInput();

        //         clearRftScan();
        //     }

        //     Livewire.on('renderQrScanner', async (type) => {
        //         if (type == 'rft') {
        //             document.getElementById('back-button').disabled = true;
        //             await refreshRftScan(onScanSuccess);
        //             document.getElementById('back-button').disabled = false;
        //         }
        //     });

        //     Livewire.on('toInputPanel', async (type) => {
        //         if (type == 'rft') {
        //             document.getElementById('back-button').disabled = true;
        //             await @this.updateOutput();
        //             await initRftScan(onScanSuccess);
        //             document.getElementById('back-button').disabled = false;
        //         }
        //     });

        //     Livewire.on('fromInputPanel', () => {
        //         clearRftScan();
        //     });
        // }

        var scannedItemRftInput = document.getElementById("scannedItemRft");

        scannedItemRftInput.addEventListener("change", function () {
            // break decoded text
            let breakDecodedText = this.value.split('-');

            console.log(breakDecodedText);

            // set kode_numbering
            @this.numberingInput = breakDecodedText[3];

            // set so_det_id
            @this.sizeInput = breakDecodedText[4];

            // set size
            @this.sizeInputText = breakDecodedText[5];

            // submit
            @this.submitInput();

            this.value = '';
        });

        Livewire.on('renderQrScanner', async (type) => {
            if (type == 'rft') {
                scannedItemRftInput.focus();
            }
        });

        Livewire.on('toInputPanel', async (type) => {
            if (type == 'rft') {
                scannedItemRftInput.focus();
            }
        });

        Livewire.on('fromInputPanel', () => {
            clearRftScan();
        });
    </script>
@endpush
