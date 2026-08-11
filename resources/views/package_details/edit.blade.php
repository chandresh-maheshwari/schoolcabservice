@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')

    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item">
                            <a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active">
                            Edit Package Detail
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit Package Details</h4>
            </div>

            <div class="card-body">
                <form id="packageDetailForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label>School Name <span style="color:red;">*</span></label>
                        @if (!empty($isSchoolUser) && !empty($defaultSchoolId))
                            <input type="hidden" name="school_id" id="school_id" value="{{ $defaultSchoolId }}">
                            <input type="text" class="form-control" value="{{ $defaultSchoolName ?? 'School' }}" disabled>
                        @else
                            @php
                                $allSchoolsOptionValue = '__all_schools__';
                                $isAllSchoolsPackage = in_array(
                                    strtolower(str_replace(['-', '_'], ' ', trim((string) ($package->school_id ?? '')))),
                                    ['all schools', 'all', 'global'],
                                    true
                                );
                                $selectedSchoolIds = collect(explode(',', (string) old('school_ids', $package->school_id ?? '')))
                                    ->map(fn ($id) => is_numeric(trim((string) $id)) ? (int) trim((string) $id) : trim((string) $id))
                                    ->filter(fn ($id) => (is_int($id) && $id > 0) || $id === $allSchoolsOptionValue)
                                    ->unique()
                                    ->all();
                                if ($isAllSchoolsPackage) {
                                    $selectedSchoolIds[] = $allSchoolsOptionValue;
                                }
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
                        <input type="text" class="form-control" id="package_name" name="package_name"
                            value="{{ $package->package_name ?? '' }}">
                    </div>

                    {{-- Package Type --}}
                    <div class="form-group">
                        <label>Package Type <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="package_type" name="package_type"
                            value="{{ $package->package_type ?? '' }}">
                    </div>

                    {{-- Booking Type --}}
                    <div class="form-group">
                        <label>Booking Type <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="booking_type" name="booking_type"
                            value="{{ $package->booking_type ?? '' }}">
                    </div>

                    {{-- Price --}}
                    <div class="form-group">
                        <label>Price <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="price" name="price"
                            value="{{ $package->price ?? '' }}">
                    </div>

                    {{-- Validity Days --}}
                    <div class="form-group">
                        <label>Validity Days <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="validity_days" name="validity_days"
                            value="{{ old('validity_days', $package->validity_days) }}" min="1" step="1"
                            required autocomplete="off" oninput="this.value = this.value < 1 ? 1 : this.value">
                    </div>

                    {{-- Short Description --}}
                    <div class="form-group">
                        <label>Short Description <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="short_description" name="short_description"
                            value="{{ $package->short_description ?? '' }}">
                    </div>

                    {{-- Description --}}
                    <div class="form-group">
                        <label>Description <span style="color:red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3">{{ $package->description ?? '' }}</textarea>
                    </div>

                    <button type="button" class="btn btn-primary" id="updateBtn">Update</button>
                    <a href="{{ request()->route('schoolSlug') ? route('school.packageDetails.index', ['schoolSlug' => request()->route('schoolSlug')]) : route('packageDetails.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
        CKEDITOR.replace('description');
        $('#updateBtn').on('click', function() {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('packageDetailForm'));
              formData.set('description', CKEDITOR.instances.description.getData());
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!@json(!empty($isSchoolUser) && !empty($defaultSchoolId)) && formData.getAll('school_ids[]').length === 0) {
                showError('#school_id', 'Please select at least one school');
            }
            if (!formData.get('package_name')) showError('#package_name', 'Package Name is required');
            if (!formData.get('package_type')) showError('#package_type', 'Package Type is required');
            if (!formData.get('booking_type')) showError('#booking_type', 'Booking Type is required');
            if (!formData.get('price')) showError('#price', 'Price is required');
            if (!formData.get('validity_days')) showError('#validity_days', 'Validity Days is required');
            if (!formData.get('short_description')) showError('#short_description',
                'Short Description is required');
            if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
                isValid = false;
            }

            if (!isValid) return;

            document.getElementById('validity_days').addEventListener('input', function() {
                if (this.value < 1) {
                    this.value = 1;
                }
            });

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            formData.append('_method', 'PUT');

            fetch('{{ route('api.packageDetails.update', $package->id) }}', {
                    method: 'POST', // Laravel method spoofing
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
                        notify('success', 'Package Details updated successfully!');
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
        document.getElementById('package_type').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('booking_type').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('price').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('validity_days').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('short_description').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
       CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });

        // real-time typing + paste validation
        $('#price').on('input paste', function() {
            const value = $(this).val();
            if (value && !/^\d*\.?\d*$/.test(value)) {
                $(this).val(value.slice(0, -1));
            }
        });
    </script>
@endsection
