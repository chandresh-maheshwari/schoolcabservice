{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
{{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: #e3e6f0;">
        <div class="card-header" style="background-color: #a9b5df; color: white; padding: 10px 15px 1px;">
            <h2 class="user-listing-header" style="text-align: center; font-size: 1.5em; margin-bottom: 6px; font-weight: bold; color: #2d336b;">Edit FAQ Category</h2>
        </div> --}}
        <div class="section-breadcrumb">
    <div class="breadcrumb-wrapper pb-0">
        <div class="container">
            <nav aria-label="breadcrumb-nav">
                <ol class="breadcrumb breadcrumb-style-2 my-20">
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a></li> 
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('faq_categories.index') }}">FAQ Categories</a></li>
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit FAQ Category</li>
                </ol>
            </nav>
        </div>
    </div>
</div>
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h4 class="cms-category-edit-header">Edit CMS Category</h4>
                </div>
        
        <div class="card-body">
            <form id="faqCategoryForm">
                @csrf
                <div class="form-group">
                    <label for="name" style="font-weight: bold;">Category Name <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ $category->name }}" required>
                </div>
                <div id="name-error" style="color: red; display: none;">Please enter a category name.</div>
                <button type="button" class="btn btn-primary" id="submitBtn" style="background-color: #2C9DD4; color: white;">Update</button>
                <a href="{{ route('faq_categories.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('submitBtn').addEventListener('click', function() {
        var nameContent = document.getElementById('name').value.trim();
        if (!nameContent) {
            document.getElementById('name-error').style.display = 'block';
            return;
        }
        document.getElementById('name-error').style.display = 'none';
        var formData = new FormData(document.getElementById('faqCategoryForm'));

        Swal.fire({
            title: 'Please wait...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        fetch('{{ route('api.faq_categories.update', $category->id) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'X-HTTP-Method-Override': 'PUT'
            }
        })
        .then(response => response.json())
        .then(data => {
            Swal.close();
            if (data.success) {
                notify('success', 'FAQ Category updated Successfully!');
                setTimeout(function() {
                    window.location.href = '{{ route('faq_categories.index') }}';
                }, 1500);
            } else {
                notify('error', 'There was an error updating the FAQ category.');
            }
        })
        .catch(error => {
            Swal.close();
            notify('error', 'An unexpected error occurred.');
        });
    });

    document.getElementById('name').addEventListener('input', function() {
        var nameContent = document.getElementById('name').value.trim();
        if (nameContent) {
            document.getElementById('name-error').style.display = 'none';
        }
    });
</script>
@endsection 