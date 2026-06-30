@extends('admin_layout.index')

@section('content')
<div class="section-breadcrumb">
    <div class="breadcrumb-wrapper pb-0">
        <div class="container">
            <nav aria-label="breadcrumb-nav">
                <ol class="breadcrumb breadcrumb-style-2 my-20">
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('users.index') }}">Users</a></li>
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Deleted Users</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center justify-content-between">
                <h4 class="user-listing-header mb-0">Deleted User Listing</h4>
                <a href="{{ route('users.index') }}" class="btn btn-primary btn-sm" title="Back to User Listing"
                    style="background-color: #2d336b;">
                    <i class="fa fa-arrow-left"></i>
                </a>
            </div>
        </div>

        <div class="card-body">
            @php
                $DatbleVariable['TableHader'] = '';
                $DatbleVariable['TableId'] = 'userTrashTable';
                $DatbleVariable['TableCreateRoute'] = '';
                $DatbleVariable['TableDeleteRoute'] = '';
                $DatbleVariable['TableRestoreRoute'] = '';
                $DatbleVariable['TableColumnName'] = ['', 'Profile Picture', 'First Name', 'Last Name', 'Mobile', 'Email', 'Status', 'Actions'];
                $DatbleVariable['rightActionButton'] = [];
            @endphp
            <x-datatable :tablevar="$DatbleVariable" class="w-100" />
        </div>
    </div>
</div>

<script src="{{ asset('js/datatables_cherrypik.js') }}?v={{ filemtime(public_path('js/datatables_cherrypik.js')) }}"></script>
<script>
    $(document).ready(function() {
        let tableId = "#userTrashTable";
        let route = '{{ route('api.users.deleted-list') }}';
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
            deleteRoute = "users",
            numberOfActivePost = 0,
        );
    });
</script>
@endsection
