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
                            <a class="breadcrumbLink" href="{{ route('msbAppSection.index') }}">MSB APP List</a>
                        </li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active">
                            Edit MSB APP Detail
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit MSB APP Details</h4>
            </div>

            <div class="card-body">
                <form id="msbAppForm">
                    @csrf
                    <input type="hidden" id="msb_id" value="{{ $msbApp->id }}">

                    {{-- ICON --}}
                    <div class="form-group">
                        <label style="font-weight: bold;">Icon <span style="color:red;">*</span></label>
                        <div class="input-group" style="max-width: 400px;">
                            <span class="input-group-text bg-white" id="icon-preview-1">
                                <i class="{{ $msbApp->icon }}"></i>
                            </span>
                            <input type="text" class="form-control" id="icon" name="icon"
                                   value="{{ $msbApp->icon }}"
                                   placeholder="Select an icon...">
                            <button type="button" class="btn btn-outline-secondary" role="iconpicker"
                                    data-iconset="fontawesome5"
                                    data-input="icon"
                                    data-preview="icon-preview-1">
                                <i class="fas fa-icons"></i>
                            </button>
                        </div>
                    </div>

                    {{-- NAME --}}
                    <div class="form-group">
                        <label>Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="name" name="name"
                               value="{{ $msbApp->name }}">
                    </div>

                    {{-- DESCRIPTION --}}
                    <div class="form-group">
                        <label>Description <span style="color:red;">*</span></label>
                        <textarea class="form-control" id="description"
                                  name="description">{{ $msbApp->description }}</textarea>
                    </div>

                    {{-- BUTTON NAME --}}
                    <div class="form-group">
                        <label>Button Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="button_name" name="button_name"
                               value="{{ $msbApp->button_name }}">
                    </div>

                    {{-- BUTTON LINK --}}
                    <div class="form-group">
                        <label>Button Link <span style="color:red;">*</span></label>
                        <input type="url" class="form-control" id="button_link" name="button_link"
                               value="{{ $msbApp->button_link }}">
                    </div>

                    <button type="button" class="btn btn-primary" id="updateBtn">Update</button>
                    <a href="{{ route('msbAppSection.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script src="{{ asset('js/common-iconpicker.js') }}"></script>

    <script>
        CKEDITOR.replace('description');

        // preload CKEditor content
        CKEDITOR.instances.description.setData(`{!! addslashes($msbApp->description) !!}`);

        $('#updateBtn').on('click', function () {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('msbAppForm'));
            formData.set('description', CKEDITOR.instances.description.getData());
            let id = $('#msb_id').val();
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('icon')) {
                $('#icon').closest('.form-group').append(
                    '<span class="error-message" style="color:red;">Icon is required.</span>'
                );
                isValid = false;
            }

            if (!formData.get('name')) showError('#name', 'Name is required');

            if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color:red;">Description is required.</span>'
                );
                isValid = false;
            }

            if (!formData.get('button_name')) showError('#button_name', 'Button Name is required');
            if (!formData.get('button_link')) showError('#button_link', 'Button Link is required');

            if (!isValid) return;

            Swal.fire({
                title: 'Updating...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

           formData.append('_method', 'PUT');

            fetch('{{ route('api.msbAppSection.update', $msbApp->id) }}', {
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
                    notify('success', 'MSB App updated successfully!');
                    setTimeout(() => window.location.href = '{{ route('msbAppSection.index') }}', 1200);
                } else {
                    notify('error', data.message || 'Something went wrong');
                }
            });
        });

        /* REAL-TIME ERROR REMOVE */
        $(document).on('input change', 'input, select', function () {
            $(this).next('.error-message').remove();
        });

        document.getElementById('icon').addEventListener('input', function () {
            $(this).closest('.form-group').find('.error-message').remove();
        });

        CKEDITOR.instances.description.on('change', function () {
            $('#description').next('.cke').next('.error-message').remove();
        });
    </script>
@endsection
