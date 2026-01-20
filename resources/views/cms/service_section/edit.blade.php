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
                        <li class="breadcrumb-item">
                            <a class="breadcrumbLink" href="{{ route('service.index') }}">Service</a>
                        </li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active">
                            Edit Service Detail
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit Service Details</h4>
            </div>

            <div class="card-body">
                <form id="serviceForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="service_id" value="{{ $service->_id ?? $service->id }}">
                    <div class="form-group">
                            <label for="icon" style="font-weight: bold;"> Icon  <span
                                    style="color: red;">*</span></label>
                            <div class="input-group" style="max-width: 400px;">
                                <span class="input-group-text bg-white" id="icon-preview-1"
                                    style="padding: 0 12px; border-right: 0; min-width: 40px; display: flex; align-items: center; justify-content: center; height: 40px;"></span>
                                <input type="text" class="form-control" id="icon" name="icon"
                                    value="{{ $service->icon }}" required placeholder="Select an icon..."
                                    aria-describedby="icon-preview-1" style="height: 40px;">
                                <button type="button" class="btn btn-outline-secondary" role="iconpicker"
                                    data-iconset="fontawesome5" data-input="icon"
                                    data-preview="icon-preview-1"
                                    style="height: 40px; border-left: 0; margin-top: 0px; border: 1px solid #ced4da;"><i
                                        class="fas fa-icons"></i></button>
                            </div>
                        </div>

                    {{-- NAME --}}
                    <div class="form-group">
                        <label>Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="name" name="name"
                            value="{{ $service->name }}" autocomplete="off">
                    </div>

                    {{-- DESCRIPTION --}}
                    <div class="form-group">
                        <label>Description <span style="color:red;">*</span></label>
                        <textarea class="form-control" id="description" name="description"
                            rows="3">{{ $service->description }}</textarea>
                    </div>

                    <button type="button" class="btn btn-primary" id="updateBtn">Update</button>
                    <a href="{{ route('service.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script src="{{ asset('js/common-iconpicker.js') }}"></script>

    <script>
        $('#updateBtn').on('click', function () {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('serviceForm'));
            let serviceId = $('#service_id').val();
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('icon')) {
                $('#icon').closest('.form-group').append(
                    '<span class="error-message" style="color:red; display:block; margin-top:5px;">Icon is required.</span>'
                );
                isValid = false;
            }

            if (!formData.get('name')) showError('#name', 'Name is required');
            if (!formData.get('description')) showError('#description', 'Description is required');

            if (!isValid) return;

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            formData.append('_method', 'PUT');

            fetch('{{ route('api.service.update', $service->id) }}', {
                    method: 'POST', // Use POST with _method=PUT for file uploads in Laravel
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
                    notify('success', 'Service updated successfully!');
                    setTimeout(() => {
                        window.location.href = '{{ route('service.index') }}';
                    }, 1500);
                } else {
                    notify('error', data.message || 'Something went wrong');
                }
            });
        });

        /* REAL-TIME ERROR REMOVE */
        $(document).on('input change', 'input, textarea, select', function () {
            $(this).next('.error-message').remove();
        });

        document.getElementById('icon').addEventListener('input', function () {
            $(this).closest('.form-group').find('.error-message').remove();
        });
    </script>
@endsection
