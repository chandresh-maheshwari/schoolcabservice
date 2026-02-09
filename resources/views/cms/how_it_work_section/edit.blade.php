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
                            <a class="breadcrumbLink" href="{{ route('howItWorks.index') }}">How It Works</a>
                        </li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active">
                            Edit How It Works Section Detail
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit How It Works Section Details</h4>
            </div>

            <div class="card-body">
                <form id="howItWorkForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="how_it_work_id" value="{{ $howItWork->id }}">

                    {{-- TITLE --}}
                    <div class="form-group">
                        <label>Title <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                               value="{{ $howItWork->title }}" autocomplete="off">
                    </div>

                    {{-- NAME --}}
                    <div class="form-group">
                        <label>Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="name" name="name"
                               value="{{ $howItWork->name }}" autocomplete="off">
                    </div>

                    {{-- DESCRIPTION --}}
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" id="description" name="description"
                                  rows="3">{{ $howItWork->description }}</textarea>
                    </div>

                    {{-- BUTTON 1 --}}
                    <div class="form-group">
                        <label>Button Name 1 <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="button_name_1" name="button_name_1"
                               value="{{ $howItWork->button_name_1 }}" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label>Button Link 1 <span style="color:red;">*</span></label>
                        <input type="url" class="form-control" id="button_link_1" name="button_link_1"
                               value="{{ $howItWork->button_link_1 }}" autocomplete="off">
                    </div>

                    {{-- BUTTON 2 --}}
                    <div class="form-group">
                        <label>Button Name 2 <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="button_name_2" name="button_name_2"
                               value="{{ $howItWork->button_name_2 }}" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label>Button Link 2 <span style="color:red;">*</span></label>
                        <input type="url" class="form-control" id="button_link_2" name="button_link_2"
                               value="{{ $howItWork->button_link_2 }}" autocomplete="off">
                    </div>

                    <button type="button" class="btn btn-primary" id="updateBtn">Update</button>
                    <a href="{{ route('howItWorks.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
          CKEDITOR.replace('description');
        $('#updateBtn').on('click', function () {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('howItWorkForm'));
               formData.set('description', CKEDITOR.instances.description.getData());
            let id = $('#how_it_work_id').val();
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('title')) showError('#title', 'Title is required');
            if (!formData.get('name')) showError('#name', 'Name is required');
            if (!formData.get('button_name_1')) showError('#button_name_1', 'Button Name 1 is required');
            if (!formData.get('button_link_1')) showError('#button_link_1', 'Button Link 1 is required');
            if (!formData.get('button_name_2')) showError('#button_name_2', 'Button Name 2 is required');
            if (!formData.get('button_link_2')) showError('#button_link_2', 'Button Link 2 is required');

             if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
                isValid = false;
            }
            if (!isValid) return;

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

           formData.append('_method', 'PUT');

            fetch('{{ route('api.howItWorks.update', $howItWork->id) }}', {
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
                    notify('success', 'How It Works Section updated successfully!');
                    setTimeout(() => {
                        window.location.href = '{{ route('howItWorks.index') }}';
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
         CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });
    </script>
@endsection
