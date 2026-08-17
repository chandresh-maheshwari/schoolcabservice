@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')
    @php
        $schoolSlug = request()->route('schoolSlug');
        $dashboardRoute = $schoolSlug ? route('school.dashboard', ['schoolSlug' => $schoolSlug]) : route('admin_layout.index');
        $indexRoute = $schoolSlug ? route('school.emergency.index', ['schoolSlug' => $schoolSlug]) : route('emergency.index');
    @endphp

    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item">
                            <a class="breadcrumbLink" href="{{ $dashboardRoute }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active">
                            Edit Emergency
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Emergency Details</h4>
            </div>

            <div class="card-body">
                <form id="emergencyForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    {{-- Driver Name --}}
                    <div class="form-group">
                        <label> Driver Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control"
                            value="{{ optional($drivers->firstWhere('id', $emergency->driver_id))->driver_name ?? '-' }}"
                            readonly>
                    </div>

                    {{-- Vehicle Number --}}
                    <div class="form-group">
                        <label>Vehicle Number <span style="color:red;">*</span></label>
                        <input type="text" class="form-control"
                            value="{{ optional($vehicles->firstWhere('id', $emergency->vehicle_id))->vehicle_number ?? '-' }}"
                            readonly>
                    </div>

                    {{-- Reported By --}}
                    <div class="form-group">
                        <label>Reported By <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" value="{{ ucfirst($emergency->reported_by ?? '-') }}" readonly>
                    </div>

                    {{-- Emergency Type --}}
                    <div class="form-group">
                        <label>Emergency Type <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" value="{{ $emergency->emergency_type ?? '-' }}" readonly>
                    </div>

                    {{-- Description --}}
                    <div class="form-group">
                        <label>Description <span style="color:red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3">{{ $emergency->description ?? '' }}</textarea>
                    </div>

                    {{-- Contact Number --}}
                    <div class="form-group">
                        <label>Contact Number <span style="color:red;">*</span></label>
                        <input type="tel" class="form-control" id="contact_number" name="contact_number"
                            value="{{ old('contact_number', $emergency->contact_number) }}" minlength="10" maxlength="11"
                            pattern="[0-9]{10,11}" required autocomplete="off" readonly>
                    </div>

                    <div class="form-group">
                        <label>Emergency Status <span style="color:red;">*</span></label>
                        <select class="form-control" id="status" name="status">
                            <option value="1" {{ (int) ($emergency->status ?? 0) === 1 ? 'selected' : '' }}>In Process</option>
                            <option value="0" {{ (int) ($emergency->status ?? 0) === 0 ? 'selected' : '' }}>Resolved</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Additional Comment</label>
                        <textarea class="form-control" id="additional_comment" name="additional_comment" rows="4"
                            placeholder="Enter additional comment">{{ old('additional_comment', $emergency->additional_comment ?? '') }}</textarea>
                    </div>

                    <button type="button" class="btn btn-primary" id="updateBtn">Update</button>
                    <a href="{{ $indexRoute }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
        CKEDITOR.replace('description', {
            readOnly: true
        });

        $('#updateBtn').on('click', function() {
            $('.error-message').remove();

            let formData = new FormData(document.getElementById('emergencyForm'));
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('status')) {
                showError('#status', 'Emergency Status is required');
            }

            if (!isValid) {
                return;
            }

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route('api.emergency.update', $emergency->id) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                        'Accept': 'application/json',
                        'X-HTTP-Method-Override': 'PUT'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        notify('success', data.message || 'Emergency status updated successfully!');
                        setTimeout(() => window.location.href = '{{ $indexRoute }}', 1200);
                    } else {
                        notify('error', data.message || 'Something went wrong');
                    }
                })
                .catch(() => {
                    Swal.close();
                    notify('error', 'Something went wrong');
                });
        });

        $('#status').on('change', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });

        $('#additional_comment').on('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
    </script>
@endsection
