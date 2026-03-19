{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
{{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: #e3e6f0;">
        <div class="card-header" style="background-color: #a9b5df; color: white; padding: 10px 15px 1px;">
            <h2 class="permissions-listing-header" style="text-align: center; font-size: 1.5em; margin-bottom: 6px; font-weight: bold; color: #2d336b;">Permissions Listing</h2>
        </div> --}}
        <div class="section-breadcrumb">
    <div class="breadcrumb-wrapper pb-0">
        <div class="container">
            <nav aria-label="breadcrumb-nav">
                <ol class="breadcrumb breadcrumb-style-2 my-20">
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a></li> 
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Permissions</li>
                </ol>
            </nav>
        </div>
    </div>
</div>
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h4 class="permissions-listing-header">Permissions Listing</h4>
                </div>
        
        <div class="card-body">
            @php
                $DatbleVariable['TableHader'] = '';
                $DatbleVariable['TableId'] = 'permissionsTable';
                $DatbleVariable['TableCreateRoute'] = 'permissions.create';
                $DatbleVariable['TableDeleteRoute'] = '';
                $DatbleVariable['TableRestoreRoute'] = '';

                $DatbleVariable['TableColumnName'] = ['Sr No.', 'Name', 'Actions'];
                $DatbleVariable['rightActionButton'] = ['createButton'];
            @endphp
            <x-datatable :tablevar="$DatbleVariable" class="w-100" />
        </div>
    </div>
</div>

{{-- <script src="{{ asset('js/datatables.js') }}"></script> --}}
<script src="{{ asset('js/datatables_cherrypik.js') }}?v={{ filemtime(public_path('js/datatables_cherrypik.js')) }}"></script>


<script>
     $(document).ready(function() {
        let tableId = "#permissionsTable";
        let route = '{{ route('api.permissions.list') }}';
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
            deleteRoute = "hero",
            numberOfActivePost = 1,
        );
    });

    // $(document).on('click', '#edit', function() {
    //     let permissionId = $(this).data('id');

    //     $.ajax({
    //         url: `/api/permissions/${permissionId}/edit`,
    //         type: 'GET',
    //         success: function(response) {
    //             if (response.success) {
    //                 const permission = response.data;
    //                 $('#name').val(permission.name);
    //                 $('#permissionForm').attr('action', `/api/permissions/${permissionId}`);
    //                 $('#submitBtn').text('Update');
    //             } else {
    //                 Swal.fire({
    //                     icon: 'error',
    //                     title: 'Error',
    //                     text: 'Failed to load permission data.'
    //                 });
    //             }
    //         },
    //         error: function() {
    //             Swal.fire({
    //                 icon: 'error',
    //                 title: 'Error',
    //                 text: 'An unexpected error occurred.'
    //             });
    //         }
    //     });
    // });
</script>
@endsection
