@extends('admin_layout.index')

@section('content')
@php
    $schoolSlug = request()->route('schoolSlug');
    $isSchoolPanel = $schoolSlug !== null;
    $dashboardRoute = $isSchoolPanel
        ? route('school.dashboard', ['schoolSlug' => $schoolSlug])
        : route('admin_layout.index');
    $schoolIndexRoute = $isSchoolPanel
        ? route('school.school.index', ['schoolSlug' => $schoolSlug])
        : route('school.index');
@endphp
<div class="section-breadcrumb">
    <div class="breadcrumb-wrapper pb-0">
        <div class="container">
            <nav aria-label="breadcrumb-nav">
                <ol class="breadcrumb breadcrumb-style-2 my-20">
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ $dashboardRoute }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ $schoolIndexRoute }}">School</a></li>
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Deleted Schools</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center justify-content-between">
                <h4 class="about-us-listing-header mb-0">Deleted School Listing</h4>
                <a href="{{ $schoolIndexRoute }}" class="btn btn-primary btn-sm" title="Back to School Listing" style="background-color: #2d336b;">
                    <i class="fa fa-arrow-left"></i>
                </a>
            </div>
        </div>
        <div class="card-body">
            @php
                $DatbleVariable['TableHader'] = '';
                $DatbleVariable['TableId'] = 'schoolTrashTable';
                $DatbleVariable['TableCreateRoute'] = '';
                $DatbleVariable['TableDeleteRoute'] = '';
                $DatbleVariable['TableRestoreRoute'] = '';

                $DatbleVariable['TableColumnName'] = ['Sr No.', 'School Name', 'School Code', 'Phone', 'City', 'State', 'Actions'];
                $DatbleVariable['rightActionButton'] = [];
            @endphp
            <x-datatable :tablevar="$DatbleVariable" class="w-100" />
        </div>
    </div>
</div>

<script src="{{ asset('js/datatables_cherrypik.js') }}?v={{ filemtime(public_path('js/datatables_cherrypik.js')) }}"></script>
<script>
    $(document).ready(function() {
        let tableId = "#schoolTrashTable";
        let route = '{{ route('school.deleted-list') }}';
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
            deleteRoute = "school",
            numberOfActivePost = 0,
        );
    });
</script>
@endsection

