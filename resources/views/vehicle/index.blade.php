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

                $DatbleVariable['TableColumnName'] = ['Sr No.', 'School', 'Vehicle Number','Vehicle Type', 'Rc Number',' Insurance Number','Actions'];
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

    window.toggleVehicleEmergencyStatus = async function (vehicleId, markEmergency, vehicleNumber) {
        const normalizedVehicleId = Number(vehicleId || 0);
        if (!normalizedVehicleId) {
            if (window.Swal?.fire) {
                window.Swal.fire('Error', 'Invalid vehicle selected.', 'error');
            } else {
                alert('Invalid vehicle selected.');
            }
            return;
        }

        let emergencyNote = '';
        if (markEmergency) {
            if (window.Swal?.fire) {
                const modalResult = await window.Swal.fire({
                    title: 'Mark Vehicle as Suspended',
                    html: `
                        <div style="text-align:left;">
                            <div style="margin-bottom:8px; font-weight:600; color:#1f2937;">
                                Vehicle: ${String(vehicleNumber || '').replace(/</g, '&lt;').replace(/>/g, '&gt;')}
                            </div>
                            <div style="margin-bottom:10px; color:#6b7280; font-size:14px;">
                                Add the suspension reason so this vehicle cannot be used for trip assignment or trip start.
                            </div>
                            <textarea id="vehicleEmergencyNote" class="swal2-textarea" placeholder="Example: puncture, breakdown, maintenance issue" style="display:flex; min-height:120px; resize:vertical; margin:0; width:100%;"></textarea>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Mark Suspended',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    focusConfirm: false,
                    customClass: {
                        popup: 'vehicle-emergency-swal',
                    },
                    preConfirm: () => {
                        const noteValue = document.getElementById('vehicleEmergencyNote')?.value?.trim() || '';
                        if (!noteValue) {
                            window.Swal.showValidationMessage('Emergency note is required.');
                            return false;
                        }
                        return noteValue;
                    },
                });

                if (!modalResult.isConfirmed) {
                    return;
                }

                emergencyNote = String(modalResult.value || '').trim();
            } else {
                emergencyNote = window.prompt(`Enter emergency note for vehicle ${vehicleNumber}:`, '');
                if (emergencyNote === null) {
                    return;
                }
                emergencyNote = emergencyNote.trim();
            }

            if (!emergencyNote) {
                if (window.Swal?.fire) {
                    window.Swal.fire('Warning', 'Emergency note is required.', 'warning');
                } else {
                    alert('Emergency note is required.');
                }
                return;
            }
        } else {
            if (window.Swal?.fire) {
                const confirmAvailable = await window.Swal.fire({
                    title: 'Mark Vehicle as Available',
                    text: `Vehicle ${vehicleNumber} will become available again for route assignment and trip start.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Mark Available',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#15803d',
                    cancelButtonColor: '#6b7280',
                });

                if (!confirmAvailable.isConfirmed) {
                    return;
                }
            } else {
                const confirmAvailable = window.confirm(`Mark vehicle ${vehicleNumber} as available again?`);
                if (!confirmAvailable) {
                    return;
                }
            }
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const endpoint = `{{ route('api.vehicle.toggleEmergencyStatus', ['id' => '__ID__']) }}`.replace('__ID__', String(normalizedVehicleId));

        try {
            if (window.Swal?.fire) {
                window.Swal.fire({
                    title: 'Updating vehicle status...',
                    allowOutsideClick: false,
                    didOpen: () => window.Swal.showLoading(),
                });
            }

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    mark_emergency: !!markEmergency,
                    emergency_note: emergencyNote,
                }),
            });

            const result = await response.json().catch(() => ({}));
            if (!response.ok || !result.success) {
                if (window.Swal?.close) {
                    window.Swal.close();
                }
                if (window.Swal?.fire) {
                    window.Swal.fire('Error', result.message || 'Unable to update vehicle emergency status.', 'error');
                } else {
                    alert(result.message || 'Unable to update vehicle emergency status.');
                }
                return;
            }

            $('#vehicleTable').DataTable().ajax.reload(null, false);
            if (window.Swal?.fire) {
                window.Swal.fire('Success', result.message || 'Vehicle emergency status updated successfully.', 'success');
            } else {
                alert(result.message || 'Vehicle emergency status updated successfully.');
            }
        } catch (error) {
            if (window.Swal?.close) {
                window.Swal.close();
            }
            if (window.Swal?.fire) {
                window.Swal.fire('Error', 'Unable to update vehicle emergency status right now.', 'error');
            } else {
                alert('Unable to update vehicle emergency status right now.');
            }
        }
    };
</script>
@endsection
