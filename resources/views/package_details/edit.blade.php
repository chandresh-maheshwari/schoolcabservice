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
                    <a href="{{ route('packageDetails.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
        $('#updateBtn').on('click', function() {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('packageDetailForm'));
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('package_name')) showError('#package_name', 'Package Name is required');
            if (!formData.get('package_type')) showError('#package_type', 'Package Type is required');
            if (!formData.get('booking_type')) showError('#booking_type', 'Booking Type is required');
            if (!formData.get('price')) showError('#price', 'Price is required');
            if (!formData.get('validity_days')) showError('#validity_days', 'Validity Days is required');
            if (!formData.get('short_description')) showError('#short_description',
                'Short Description is required');
            if (!formData.get('description')) showError('#description', 'Description is required');

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

            fetch('{{ route('api.packageDetails.update', $package->_id) }}', {
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
                        setTimeout(() => window.location.href = '{{ route('packageDetails.index') }}', 1500);
                    } else {
                        notify('error', data.message || 'Something went wrong');
                    }
                });
        });

        /* REAL-TIME ERROR REMOVE */
        $(document).on('input change', 'input, select', function() {
            $(this).next('.error-message').remove();
        });


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
        document.getElementById('description').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
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
