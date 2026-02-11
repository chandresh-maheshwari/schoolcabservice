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
                            Add MSB APP Detail
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add MSB APP Details</h4>
            </div>

            <div class="card-body">
                <form id="msbAppForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>Title </label>
                        <input type="text" class="form-control" id="title" name="title" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Short Desc </label>
                        <input type="text" class="form-control" id="short_desc" name="short_desc" autocomplete="off">
                    </div>
                    <div class="form-group">
                            <label for="icon" style="font-weight: bold;">Icon  <span
                                    style="color: red;">*</span></label>
                            <div class="input-group" style="max-width: 400px;">
                                <span class="input-group-text bg-white" id="icon-preview-1"
                                    style="padding: 0 12px; border-right: 0; min-width: 40px; display: flex; align-items: center; justify-content: center; height: 40px;"></span>
                                <input type="text" class="form-control" id="icon" name="icon" required
                                    placeholder="Select an icon..." aria-describedby="icon-preview-1"
                                    style="height: 40px;">
                                <button type="button" class="btn btn-outline-secondary" role="iconpicker"
                                    data-iconset="fontawesome5" data-input="icon"
                                    data-preview="icon-preview-1"
                                    style="height: 40px; border-left: 0; margin-top: 0px; border: 1px solid #ced4da;"><i
                                        class="fas fa-icons"></i></button>
                            </div>
                        </div>
                    <div class="form-group">
                        <label>Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Description <span style="color:red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                     <div class="form-group">
                        <label>Button Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="button_name" name="button_name" autocomplete="off">
                    </div>
                     <div class="form-group">
                        <label>Button Link <span style="color:red;">*</span></label>
                        <input type="url" class="form-control" id="button_link" name="button_link" autocomplete="off">
                    </div>
                    <button type="button" class="btn btn-primary" id="submitBtn">Submit</button>
                    <a href="{{ route('msbAppSection.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
     <script src="{{ asset('js/common-iconpicker.js') }}"></script>
    <script>
          CKEDITOR.replace('description');
        $('#submitBtn').on('click', function() {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('msbAppForm'));
             formData.set('description', CKEDITOR.instances.description.getData());
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

             if (!formData.get('icon')) {
                $('#icon').closest('.form-group').append(
                    '<span class="error-message" style="color: red; display: block; margin-top: 5px;">Icon  is required.</span>'
                );
                isValid = false;
            }
             if (!formData.get('name')) showError('#name', 'Button Name is required');
              if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
                isValid = false;
            }
          
             if (!formData.get('button_name')) showError('#button_name', 'Button Name is required');
            if (!formData.get('button_link')) showError('#button_link', 'Button Link is required');

            function isValidPositive(value) {
                return /^[a-zA-Z0-9]+$/.test(value);
            }

            if (!isValid) return;

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route('api.msbAppSection.store') }}', {
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
                        notify('success', 'MSB App created successfully!');
                        setTimeout(() => window.location.href = '{{ route('msbAppSection.index') }}', 1500);
                    } else {
                        notify('error', data.message || 'Something went wrong');
                    }
                });
        });

        /* REAL-TIME ERROR REMOVE */
        $(document).on('input change', 'input, select', function() {
            $(this).next('.error-message').remove();
        });
         document.getElementById('icon').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
          CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });
         document.getElementById('button_name').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
         document.getElementById('button_link').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
    </script>
@endsection
