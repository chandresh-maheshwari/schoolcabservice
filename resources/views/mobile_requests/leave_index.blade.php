@extends('admin_layout.index')

@section('content')
@php
    $dashboardRoute = $panel['is_school_panel']
        ? route('school.dashboard', ['schoolSlug' => $panel['school_slug']])
        : route('admin_layout.index');
@endphp

        <div class="section-breadcrumb">
    <div class="breadcrumb-wrapper pb-0">
        <div class="container">
            <nav aria-label="breadcrumb-nav">
                <ol class="breadcrumb breadcrumb-style-2 my-20">
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ $dashboardRoute }}">Dashboard</a></li>
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">{{ $pageTitle }}</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h4 class="about-us-listing-header">{{ $pageTitle }}</h4>
                </div>
        <div class="card-body">
            @php
                $isSchoolPanel = request()->route('schoolSlug') !== null;
                $DatbleVariable['TableHader'] = '';
                $DatbleVariable['TableId'] = 'leaveRequestsTable';
                $DatbleVariable['TableCreateRoute'] = '';
                $DatbleVariable['TableDeleteRoute'] = '';
                $DatbleVariable['TableRestoreRoute'] = '';

                $DatbleVariable['TableColumnName'] = $isSchoolPanel
                    ? ['Sr No.', 'Student Name', 'Parent Name', 'Reason', 'From Date', 'To Date', 'Submitted', 'Actions']
                    : ['Sr No.', 'School Name', 'Student Name', 'Parent Name', 'Reason', 'From Date', 'To Date', 'Submitted', 'Actions'];
                $DatbleVariable['rightActionButton'] = ['toolbarSpacer'];

            @endphp
            <x-datatable :tablevar="$DatbleVariable" class="w-100" />
        </div>
    </div>
</div>

<script src="{{ asset('js/datatables_cherrypik.js') }}?v={{ filemtime(public_path('js/datatables_cherrypik.js')) }}"></script>
<script>
    $(document).ready(function() {
        let tableId = "#leaveRequestsTable";
        let route = '{{ route('leaveRequests.list') }}';
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
            multiDelete = false,
            deleteRoute = "leaveRequests",
            numberOfActivePost = 0,
        );
    });
</script>
@endsection
