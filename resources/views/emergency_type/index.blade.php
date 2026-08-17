@extends('admin_layout.index')

@section('content')
<div class="section-breadcrumb">
    <div class="breadcrumb-wrapper pb-0">
        <div class="container">
            <nav aria-label="breadcrumb-nav">
                <ol class="breadcrumb breadcrumb-style-2 my-20">
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Emergency Type</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4 class="about-us-listing-header">Emergency Type Listing</h4>
        </div>
        <div class="card-body">
            @php
                $isSchoolPanel = request()->route('schoolSlug') !== null;
                $DatbleVariable['TableHader'] = '';
                $DatbleVariable['TableId'] = 'emergencyTypeTable';
                $DatbleVariable['TableCreateRoute'] = $isSchoolPanel ? 'school.emergencyType.create' : 'emergencyType.create';
                $DatbleVariable['TableDeleteRoute'] = '';
                $DatbleVariable['TableRestoreRoute'] = '';
                $DatbleVariable['TableColumnName'] = ['Sr No.', 'Emergency Type', 'Actions'];
                $DatbleVariable['rightActionButton'] = ['createButton'];
            @endphp
            <x-datatable :tablevar="$DatbleVariable" class="w-100" />
        </div>
    </div>
</div>

<script src="{{ asset('js/datatables_cherrypik.js') }}?v={{ filemtime(public_path('js/datatables_cherrypik.js')) }}"></script>

<script>
    $(document).ready(function() {
        let tableId = "#emergencyTypeTable";
        let route = '{{ route('emergencyType.list') }}';
        let method = "POST";
        let leftActionButton = true;
        let searching = true;
        let pagination = true;
        let distance = null;

        DatatableRenderFunction(
            tableId,
            route,
            method,
            leftActionButton,
            searching,
            distance,
            location,
            lenghtDropdown = true,
            bottomInfo = true,
            pagination,
            multiDelete = true,
            deleteRoute = "emergencyType",
            numberOfActivePost = 10,
        );
    });
</script>
@endsection
