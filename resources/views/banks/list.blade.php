@extends('layouts.app2')

@section('content')

<div class="layout-px-spacing">
    <div class="row  layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-md-12 col-12 ">
            <nav class="breadcrumb-two" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
                    <li class="breadcrumb-item"><a href="/banks">Contabilidade</a></li>
                    <li class="breadcrumb-item active"><a href="/banks">Bancos</a></li>
                    <li class="breadcrumb-item" aria-current="page"><a href="">Listagem</a></li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-md-12 col-12 layout-spacing">
            <div class="widget-content-area br-4">
                <div class="widget-one">

                    <h5>Bancos da instituição</h5>

                    <p class="">Página do Sistema Cofrs destinada ao gerenciamento das contas bancárias.</p>
                    <br />
                    <div class="row">
                        <div class="col-md-12 text-right">
                            @include('banks.modal.create')
                            @include('banks.modal.edit')
                        </div>
                    </div>
                    <hr />
                    <div class="row">
                        <div class="col-12">
                            <table class="table table-hover" id="table">
                                <thead>
                                    <th>Banco</th>
                                    <th>Cod. Febraban</th>
                                    <th>Ação</th>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script src="{{ URL::asset('/assets/js/banks/custom.js') }}"></script>
@endpush