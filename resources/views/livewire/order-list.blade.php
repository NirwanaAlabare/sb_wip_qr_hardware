<div>
    <div class="row mb-3">
        <div class="col-md-12 col-lg-10">
            <div class="input-group mb-3">
                <input type="hidden" wire:model='date'>
                <input type="text" class="form-control" wire:model='search' placeholder="Search Order...">
                <button class="btn btn-sb" type="button" id="button-search-order"><i class="fa-regular fa-magnifying-glass"></i></button>
            </div>
        </div>
        <div class="col-md-12 col-lg-2">
            <a href="{{ url('/production-panel/universal/') }}" class="btn btn-sb w-100">
                <h5 class="mb-0"><i class="fa-solid fa-globe"></i></h5>
            </a href="/production-panel/universal/">
        </div>
    </div>

    <div class="w-100" wire:loading wire:target='search, date'>
        <div class="loading-container">
            <div class="loading"></div>
        </div>
        <p class="text-center text-sb fw-bold mt-3 mb-0">
            Mohon Tunggu...
        </p>
    </div>

    <div class="order-list row row-gap-3 mb-3 h-100" wire:loading.remove wire:target='search, date'>
        @if ($orders->isEmpty())
            {{-- <h5 class="text-center text-muted mt-3"><i class="fa-solid fa-circle-exclamation"></i> Order tidak ditemukan</h5> --}}
        @else
            @foreach ($orders as $order)
                <a href="{{ url('/production-panel/index/'.$order->id) }}" class="order col-md-6 h-100">
                    <div class="card h-100">
                        <div class="card-body justify-content-start">
                            <table class="table table-responsive mb-1">
                                <tr>
                                    <td class="text-nowrap">Buyer</td>
                                    <td class="text-nowrap">:</td>
                                    <td class="fw-bold">{{ ucwords($order->buyer_name) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-nowrap">WS Number</td>
                                    <td class="text-nowrap">:</td>
                                    <td class="fw-bold">{{ $order->ws_number }}</td>
                                </tr>
                                <tr>
                                    <td class="text-nowrap">Product Type</td>
                                    <td class="text-nowrap">:</td>
                                    <td class="fw-bold">{{ $order->product_type }}</td>
                                </tr>
                                <tr>
                                    <td class="text-nowrap">Style</td>
                                    <td class="text-nowrap">:</td>
                                    <td class="fw-bold">{{ ucwords($order->style_name) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-nowrap">Plan Date</td>
                                    <td class="text-nowrap">:</td>
                                    <td class="fw-bold">{{ $order->plan_date }}</td>
                                </tr>
                            </table>
                            <div class="mx-2">
                                <div class="d-flex justify-content-between w-100">
                                    <p class="mb-1">Output : <b>{{ $order->progress }}</b></p>
                                    <p class="mb-1">Target : <b>{{ $order->target }}</b></p>
                                </div>
                                <div class="progress" role="progressbar" aria-valuenow="{{ $order->progress }}" aria-valuemin="0" aria-valuemax="{{ $order->target }}" style="height: 15px">
                                    @php
                                        $outputProgress = $order->progress > 0 ? floatval($order->progress)/floatval($order->target) * 100 : 0;
                                    @endphp
                                    <div class="progress-bar fw-bold {{ $outputProgress > 100 ? 'bg-rft' : 'bg-sb' }}" style="width:{{  $outputProgress }}%">{{ $outputProgress > 100 ? 'TARGET TERLAMPAUI' : '' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a href="/production-panel/index/{{ $order->id }}">
            @endforeach
        @endif
        <a href="{{ url('/production-panel/temporary/') }}" class="order col-md-6 h-100">
            <div class="card h-100">
                <div class="card-body justify-content-start">
                    <div class="mx-2">
                        <div class="d-flex justify-content-between align-items-center h-100">
                            <h1 class="fw-bold mb-0">{{ $temporaryOutput }}</h1>
                            <h5 class="text-sb fw-bold mb-0">OUTPUT TEMPORARY</h5>
                        </div>
                    </div>
                </div>
            </div>
        </a href="/production-panel/temporary/">
    </div>

    <div class="w-100 mt-3">
        <p class="text-center opacity-50"><small><i>{{ date('Y') }} &copy; Nirwana Digital Solution</i></small></p>
    </div>
</div>
