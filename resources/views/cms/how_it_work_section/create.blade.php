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
                            Add How It Works Section Detail
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add How It Works Section Details</h4>
            </div>

            <div class="card-body">
                <form id="howItWorkForm" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="form-group">
                        <label>Title <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label> Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" autocomplete="off">
                 
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Button Name 1 <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="button_name_1" name="button_name_1" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Button Link 1 <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="button_link_1" name="button_link_1" autocomplete="off">
                    </div>
                    <div class="form-group"></div>
                        <label>Button Name 2 <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="button_name_2" name="button_name_2" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Button Link 2 <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="button_link_2" name="button_link_2" autocomplete="off">
                    </div>
                    <button type="button" class="btn btn-primary" id="submitBtn">Submit</button>
                    <a href="{{ route('howItWorks.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
        $('#submitBtn').on('click', function() {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('howItWorkForm'));
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('name')) showError('#name', 'Name is required');
            if (!formData.get('title')) showError('#title', 'Title is required');
            if (!formData.get('button_name_1')) showError('#button_name_1', 'Button Name 1 is required');
            if (!formData.get('button_link_1')) showError('#button_link_1', 'Button Link 1 is required');
            if (!formData.get('button_name_2')) showError('#button_name_2', 'Button Name 2 is required');
            if (!formData.get('button_link_2')) showError('#button_link_2', 'Button Link 2 is required');
            

            function isValidPositive(value) {
                return /^[a-zA-Z0-9]+$/.test(value);
            }

          

            if (!isValid) return;

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route('api.howItWorks.store') }}', {
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
                        notify('success', 'How It Works Section created successfully!');
                        setTimeout(() => window.location.href = '{{ route('howItWorks.index') }}', 1500);
                    } else {
                        notify('error', data.message || 'Something went wrong');
                    }
                });
        });

        /* REAL-TIME ERROR REMOVE */
        $(document).on('input change', 'input, select', function() {
            $(this).next('.error-message').remove();
        });

    </script>
@endsection
