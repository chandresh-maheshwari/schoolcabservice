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
        <div class="card-body leave-requests-list">
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
            <style>
                .leave-requests-list .table-responsive,
                .leave-requests-list .table-wrapper,
                .leave-requests-list .section-wrapper {
                    min-width: 0;
                    width: 100%;
                }
                #leaveRequestsTable {
                    table-layout: fixed;
                    width: 100% !important;
                    min-width: 0 !important;
                }
                #leaveRequestsTable th,
                #leaveRequestsTable td {
                    min-width: 0 !important;
                    white-space: normal !important;
                    overflow-wrap: anywhere;
                    vertical-align: middle;
                    padding: 10px 8px;
                    box-sizing: border-box;
                }
                #leaveRequestsTable td:last-child .btn {
                    margin: 2px;
                }
                @foreach ($isSchoolPanel ? [5, 18, 20, 20, 9, 9, 12, 7] : [5, 14, 15, 18, 16, 8, 8, 10, 6] as $width)
                    #leaveRequestsTable th:nth-child({{ $loop->iteration }}),
                    #leaveRequestsTable td:nth-child({{ $loop->iteration }}) {
                        width: {{ $width }}% !important;
                    }
                @endforeach
            </style>
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
            multiDelete = true,
            deleteRoute = "leaveRequests",
            numberOfActivePost = 0,
        );
    });
</script>
@endsection
