{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
{{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: #e3e6f0;">
        <div class="card-header" style="background-color: #a9b5df; color: white; padding: 10px 15px 1px;">
            <h2 class="user-listing-header" style="text-align: center; font-size: 1.5em; margin-bottom: 6px; font-weight: bold; color: #2d336b;">User Listing</h2>
        </div> --}}

        <div class="section-breadcrumb">
    <div class="breadcrumb-wrapper pb-0">
        <div class="container">
            <nav aria-label="breadcrumb-nav">
                <ol class="breadcrumb breadcrumb-style-2 my-20">
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Users</li>
                </ol>
            </nav>
        </div>
    </div>
</div>
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h4 class="user-listing-header mb-0">User Listing</h4>
                </div>

        <div class="card-body">
            <!-- <a href="{{ route('users.create') }}" class="btn btn-primary mb-3">Add User</a> -->

            @php
                $DatbleVariable['TableHader'] = '';
                $DatbleVariable['TableId'] = 'usersTable';
                $DatbleVariable['TableCreateRoute'] = 'users.create';
                $DatbleVariable['TableDeleteRoute'] = '';
                $DatbleVariable['TableRestoreRoute'] = '';

                $DatbleVariable['TableColumnName'] = [ 'Sr No.', 'Profile Picture','First Name', 'Last Name', 'Mobile', 'Email', 'Actions'];
                $DatbleVariable['rightActionButton'] = ['createButton'];
            @endphp
            <x-datatable :tablevar="$DatbleVariable" class="w-100" />
        </div>
    </div>
</div>

<script src="{{ asset('js/datatables_cherrypik.js') }}?v={{ filemtime(public_path('js/datatables_cherrypik.js')) }}"></script>

<script>
    $(document).ready(function() {
        let tableId = "#usersTable";
        let route = '{{ route('api.userlist') }}';
        let id = null;
        let method = "POST";
        let leftActionButton = true;
        let searching = true;
        let deleteRoute = true;
        let graphRoute = null;
        let pagination = true;
        let restoreRoute = false;
        let distance = null;

        let inActiveVal = null;
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
            numberOfActivePost = "",
        );

        const deletedUsersButton = `
            <a href="{{ route('users.deleted-list') }}"
                class="dt-add-btn btn btn-primary btn-sm"
                title="Deleted Users"
                style="background-color: #2d336b;">
                <i class="fa fa-undo"></i>
            </a>`;

        const $actionFilter = $('#action_filter_usersTable');
        if ($actionFilter.length && !$actionFilter.find('.deleted-users-btn').length) {
            $actionFilter.append($(deletedUsersButton).addClass('deleted-users-btn'));
        }
    });

    $(document).on('click', '#edit', function() {
        let userId = $(this).data('id');

        $.ajax({
            url: `/api/users/${userId}/edit`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const user = response.data;
                    $('#first_name').val(user.first_name);
                    $('#last_name').val(user.last_name);
                    $('#mobile').val(user.mobile);
                    $('#email').val(user.email);

                    if (user.photo) {
                        $('#image-preview').attr('src', `/storage/${user.photo}`).show();
                    } else {
                        $('#image-preview').hide();
                    }

                    // Set form action to update
                    $('#userForm').attr('action', `/api/users/${userId}`);
                    $('#submitBtn').text('Update');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load user data.'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An unexpected error occurred.'
                });
            }
        });
    });
</script>
@endsection
