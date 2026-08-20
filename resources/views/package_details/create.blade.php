@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')
    @php
        $selectedPackageType = old('package_type', '');
        $selectedValidity = old('validity_days', '');
        $validityBaseDateTime = now('Asia/Kolkata')->format('c');
    @endphp

    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item">
                            <a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active">
                            Add Driver Detail
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add Package Details</h4>
            </div>

            <div class="card-body">
                <form id="packageDetailForm" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label>School Name <span style="color:red;">*</span></label>
                        @if (!empty($isSchoolUser) && !empty($defaultSchoolId))
                            <input type="hidden" name="school_id" id="school_id" value="{{ $defaultSchoolId }}">
                            <input type="text" class="form-control" value="{{ $defaultSchoolName ?? 'School' }}" disabled>
                        @else
                            @php
                                $allSchoolsOptionValue = '__all_schools__';
                                $selectedSchoolIds = collect(old('school_ids', []))
                                    ->map(fn ($id) => is_numeric($id) ? (int) $id : trim((string) $id))
                                    ->filter(fn ($id) => (is_int($id) && $id > 0) || $id === $allSchoolsOptionValue)
                                    ->all();
                            @endphp
                            <select class="form-control" name="school_ids[]" id="school_id" multiple data-placeholder="Select School">
                                <option value="{{ $allSchoolsOptionValue }}" {{ in_array($allSchoolsOptionValue, $selectedSchoolIds, true) ? 'selected' : '' }}>
                                    All Schools
                                </option>
                                @foreach ($schoolData ?? [] as $school)
                                    <option value="{{ $school->id }}" {{ in_array((int) $school->id, $selectedSchoolIds, true) ? 'selected' : '' }}>
                                        {{ $school->school_name }}
                                    </option>
                                @endforeach
                            </select>
                            <small style="color:#6c757d;">You can select more than one school.</small>
                        @endif
                    </div>

                    {{-- Package Name --}}
                    <div class="form-group">
                        <label>Package Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="package_name" name="package_name" autocomplete="off">
                    </div>

                    {{-- Package Type --}}
                    <div class="form-group">
                        <label>Package Type <span style="color:red;">*</span></label>
                        <select class="form-control" id="package_type" name="package_type">
                            <option value="">Select Package Type</option>
                            <option value="Daily" {{ $selectedPackageType === 'Daily' ? 'selected' : '' }}>Daily</option>
                            <option value="Monthly" {{ $selectedPackageType === 'Monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="Quarterly" {{ $selectedPackageType === 'Quarterly' ? 'selected' : '' }}>Quarterly</option>
                            <option value="Yearly" {{ $selectedPackageType === 'Yearly' ? 'selected' : '' }}>Yearly</option>
                        </select>
                    </div>


                    {{-- Booking Type --}}
                    <div class="form-group">
                        <label>Booking Type <span style="color:red;">*</span></label>
                        <select class="form-control" id="booking_type" name="booking_type">
                            <option value="">Select Booking Type</option>
                            <option value="Pickup Only">Pickup Only</option>
                            <option value="Drop Only">Drop Only</option>
                            <option value="Pickup &amp; Drop">Pickup &amp; Drop</option>
                        </select>
                    </div>
                    {{-- Price Phone --}}
                    <div class="form-group">
                        <label>Price <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="price" name="price" autocomplete="off">
                    </div>

                    {{-- Validity --}}
                    <div class="form-group">
                        <label>Validity <span style="color:red;">*</span></label>
                        <input type="hidden" id="validity_days" name="validity_days" value="{{ $selectedValidity }}">
                        <input type="text" class="form-control" id="validity_display"
                            value="{{ $selectedValidity ? $selectedValidity . ' days' : '' }}" readonly
                            placeholder="Select Package Type to calculate validity">
                    </div>


                    {{--  Short Description --}}
                    <div class="form-group">
                        <label>Short Description <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="short_description" name="short_description"
                            autocomplete="off">
                    </div>
                    {{-- Description --}}
                    <div class="form-group">
                        <label>Description <span style="color:red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>

                    <button type="button" class="btn btn-primary" id="submitBtn">Submit</button>
                    <a href="{{ request()->route('schoolSlug') ? route('school.packageDetails.index', ['schoolSlug' => request()->route('schoolSlug')]) : route('packageDetails.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
           CKEDITOR.replace('description');
        const validityBaseDateTime = @json($validityBaseDateTime);
        $('#submitBtn').on('click', function() {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('packageDetailForm'));
                 formData.set('description', CKEDITOR.instances.description.getData());
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('package_name')) showError('#package_name', 'Package Name is required');
            if (!@json(!empty($isSchoolUser) && !empty($defaultSchoolId)) && formData.getAll('school_ids[]').length === 0) {
                showError('#school_id', 'Please select at least one school');
            }
            if (!formData.get('package_type')) showError('#package_type', 'Package Type is required');
            if (!formData.get('booking_type')) showError('#booking_type', 'Booking Type is required');
            if (!formData.get('price')) showError('#price', 'Price is required');
            if (!formData.get('validity_days')) showError('#validity_days', 'Validity is required');
            if (!formData.get('short_description')) showError('#short_description',
            'Short Description is required');
           if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
                isValid = false;
            }

        // Answer validation (CKEditor)
        if (!CKEDITOR.instances.description.getData().trim()) {
            if ($('#description').next('.cke').next('.error-message').length === 0) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color:red;">Description is required.</span>'
                );
            }
            isValid = false;
        }

            function isValidPositive(value) {
                return /^[a-zA-Z0-9]+$/.test(value);
            }
            if (!isValid) return;


            document.getElementById('validity_days').addEventListener('input', function() {
                if (this.value < 1) {
                    this.value = '';
                }
            });

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route('api.packageDetails.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        notify('success', 'Package Details created successfully!');
                        setTimeout(() => window.location.href = @json(request()->route('schoolSlug') ? route('school.packageDetails.index', ['schoolSlug' => request()->route('schoolSlug')]) : route('packageDetails.index')), 1500);
                    } else {
                        notify('error', data.message || 'Something went wrong');
                    }
                });
        });

        /* REAL-TIME ERROR REMOVE */
        $(document).on('input change', 'input, select', function() {
            $(this).next('.error-message').remove();
        });


        document.getElementById('school_id')?.addEventListener('change', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        if (typeof window.initializeSelect2Dropdowns === 'function') {
            window.initializeSelect2Dropdowns(document.getElementById('school_id'));
        }
        document.getElementById('package_name').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        function syncValidityFromPackageType() {
            const packageTypeField = document.getElementById('package_type');
            const validityField = document.getElementById('validity_days');
            const validityDisplayField = document.getElementById('validity_display');

            if (!packageTypeField || !validityField || !validityDisplayField) {
                return;
            }

            const packageType = (packageTypeField.value || '').trim();
            const baseDate = new Date(validityBaseDateTime);
            const expiryDate = new Date(baseDate.getTime());
            let validityDays = '';

            if (packageType === 'Daily') {
                validityDays = 1;
                expiryDate.setHours(23, 59, 0, 0);
                validityField.value = validityDays;
                validityDisplayField.value = `${validityDays} day (Valid till ${formatDisplayDateTime(expiryDate)})`;
                return;
            }

            if (packageType === 'Monthly') {
                expiryDate.setMonth(expiryDate.getMonth() + 1);
            } else if (packageType === 'Quarterly') {
                expiryDate.setMonth(expiryDate.getMonth() + 3);
            } else if (packageType === 'Yearly') {
                expiryDate.setFullYear(expiryDate.getFullYear() + 1);
            } else {
                validityField.value = '';
                validityDisplayField.value = '';
                return;
            }

            const diffMs = expiryDate.getTime() - baseDate.getTime();
            validityDays = Math.max(1, Math.round(diffMs / (1000 * 60 * 60 * 24)));
            expiryDate.setHours(23, 59, 0, 0);
            validityField.value = validityDays;
            validityDisplayField.value = `${validityDays} days (Valid till ${formatDisplayDateTime(expiryDate)})`;
        }

        function formatDisplayDateTime(date) {
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            let hours = date.getHours();
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const meridiem = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            return `${day}/${month}/${year} ${String(hours).padStart(2, '0')}:${minutes} ${meridiem}`;
        }

        $('#package_type').on('change', function() {
            syncValidityFromPackageType();
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('booking_type').addEventListener('change', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('price').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('validity_display').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('short_description').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        CKEDITOR.instances.description.on('change', function () {
        $('#description').next('.cke').next('.error-message').remove();
    });

        // real-time typing + paste validation
        $('#price').on('input paste', function() {
            const value = $(this).val();
            if (value && !/^\d*\.?\d*$/.test(value)) {
                $(this).val(value.slice(0, -1));
            }
        });

        $(document).ready(function() {
            syncValidityFromPackageType();
        });
    </script>
@endsection
