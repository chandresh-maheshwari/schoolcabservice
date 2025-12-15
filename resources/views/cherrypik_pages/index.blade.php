{{-- @extends('admin_layout.index')

@section('content')
<div class="section-breadcrumb">
    <div class="breadcrumb-wrapper pb-0">
        <div class="container">
            <nav aria-label="breadcrumb-nav">
                <ol class="breadcrumb breadcrumb-style-2 my-20">
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Cherrypik Pages</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Pages</h4>
            <a href="{{ route('cherrypik_pages.create') }}" class="btn btn-primary">Add Page</a>
        </div>
        <div class="card-body">
            <table class="table table-bordered" id="cherrypikPagesTable">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Template</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function(){
        if ($.fn.DataTable) {
            $('#cherrypikPagesTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '{{ route('cherrypik_pages.list') }}',
                    dataSrc: 'data'
                },
                columns: [
                    { data: 'id' },
                    { data: 'title' },
                    { data: 'slug' },
                    { data: 'template', render: d => d || '-' },
                    { data: 'status', render: d => d || '-' },
                    { data: null, orderable: false, searchable: false, render: function(row){
                        return `<a href="{{ url('admin/cherrypik_pages') }}/${row.id}/edit" class="btn btn-sm btn-secondary">Edit</a>`;
                    }}
                ]
            });
        }
    });
</script>
@endsection

 --}}

{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
    {{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: #e3e6f0;">
        <div class="card-header" style="background-color: #a9b5df; color: white; padding: 10px 15px 1px;">
            <h2 class="about-us-listing-header" style="text-align: center; font-size: 1.5em; margin-bottom: 6px; font-weight: bold; color: #2d336b;">About Us Listing</h2>
        </div> --}}
    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">cherrypik page Listing
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-listing-header">Cherrypik Page Listing</h4>
            </div>
            <div class="card-body">
                @php
                    $DatbleVariable['TableHader'] = '';
                    $DatbleVariable['TableId'] = 'cherrypikPagesTable';
                    $DatbleVariable['TableCreateRoute'] = 'cherrypik_pages.create';
                    $DatbleVariable['TableDeleteRoute'] = '';
                    $DatbleVariable['TableRestoreRoute'] = '';

                    $DatbleVariable['TableColumnName'] = [
                        'Sr No.',
                        'Title',
                        'Image',
                        'Template Name',
                        'Description',
                        'Status',
                        // 'inner_page_status',
                        'Actions',
                    ];
                    $DatbleVariable['rightActionButton'] = ['createButton'];
                @endphp
                <x-datatable :tablevar="$DatbleVariable" class="w-100" />
            </div>
        </div>
    </div>

    {{-- <script src="{{ asset('js/datatables.js') }}"></script> --}}
    <script src="{{ asset('js/datatables_cherrypik.js') }}"></script>

    <script>
        $(document).ready(function() {
            let tableId = "#cherrypikPagesTable";
            let route = '{{ route('cherrypik_pages.list') }}';
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
                deleteRoute = "cherrypik_pages",
            );
        });
    </script>
@endsection
