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
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Vehicle </li>
                </ol>
            </nav>
        </div>
    </div>
</div>

        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h4 class="about-us-listing-header">Vehicle Listing</h4>
                </div>
        <div class="card-body">
            @php
                $DatbleVariable['TableHader'] = '';
                $DatbleVariable['TableId'] = 'vehicleTable';
                $DatbleVariable['TableCreateRoute'] = 'vehicle.create';
                $DatbleVariable['TableDeleteRoute'] = '';
                $DatbleVariable['TableRestoreRoute'] = '';

                $DatbleVariable['TableColumnName'] = ['Sr No.', 'Vehicle Number','Vehicle Type', 'Rc Number',' Insurance Number','Actions'];
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
        let tableId = "#vehicleTable";
        let route = '{{ route('vehicle.list') }}';
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
            deleteRoute = "vehicle",
            numberOfActivePost = 5,
        );
    });

    function toggleVehicleEmergencyStatus(vehicleId, vehicleNumber, isEmergencyMarked) {
        const targetStatus = isEmergencyMarked ? 'available' : 'emergency';
        const title = isEmergencyMarked ? 'Mark Vehicle Available' : 'Mark Vehicle Suspended';
        const confirmText = isEmergencyMarked ? 'Yes, mark available' : 'Yes, mark suspended';
        const noteRequired = !isEmergencyMarked;

        const openDialog = function () {
            if (typeof window.Swal === 'undefined' || !window.Swal.fire) {
                const fallbackNote = noteRequired ? window.prompt(`Enter suspension reason for vehicle ${vehicleNumber}:`) : '';
                if (noteRequired && !fallbackNote) {
                    return;
                }
                submitVehicleEmergencyStatus(vehicleId, targetStatus, fallbackNote || '');
                return;
            }

            window.Swal.fire({
                title: title,
                text: isEmergencyMarked
                    ? `Vehicle ${vehicleNumber} will become available again.`
                    : `Vehicle ${vehicleNumber} will be marked as suspended and cannot be used in route assignment.`,
                input: noteRequired ? 'text' : undefined,
                inputLabel: noteRequired ? 'Suspension reason' : undefined,
                inputPlaceholder: noteRequired ? 'Enter emergency reason' : undefined,
                inputValidator: noteRequired ? function (value) {
                    if (!String(value || '').trim()) {
                        return 'Reason is required';
                    }
                    return null;
                } : undefined,
                icon: noteRequired ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: confirmText,
                confirmButtonColor: noteRequired ? '#dc2626' : '#15803d',
                cancelButtonText: 'Cancel',
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                submitVehicleEmergencyStatus(vehicleId, targetStatus, result.value || '');
            });
        };

        openDialog();
    }

    function submitVehicleEmergencyStatus(vehicleId, markAs, note) {
        $.ajax({
            url: `/api/vehicle/${encodeURIComponent(vehicleId)}/emergency-status`,
            method: 'POST',
            data: {
                mark_as: markAs,
                note: note,
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                if (typeof window.notify === 'function') {
                    window.notify('success', response.message || 'Vehicle status updated successfully');
                }
                $('#vehicleTable').DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                const message = xhr?.responseJSON?.message || 'Vehicle status update failed';
                if (typeof window.notify === 'function') {
                    window.notify('error', message);
                } else {
                    window.alert(message);
                }
            }
        });
    }
</script>
@endsection
