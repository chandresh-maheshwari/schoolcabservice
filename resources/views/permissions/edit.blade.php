{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')


@section('content')
{{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: #e3e6f0;">
        <div class="card-header" style="background-color: #a9b5df; color: white; padding: 10px 15px 1px;">
            <h2 class="user-listing-header" style="text-align: center; font-size: 1.5em; margin-bottom: 6px; font-weight: bold; color: #2d336b;">Edit Permission</h2>
        </div> --}}
        <div class="section-breadcrumb">
    <div class="breadcrumb-wrapper pb-0">
        <div class="container">
            <nav aria-label="breadcrumb-nav">
                <ol class="breadcrumb breadcrumb-style-2 my-20">
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a></li> 
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('permissions.index') }}">Permissions</a></li>
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit Permission</li>
                </ol>
            </nav>
        </div>
    </div>
</div>
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h2 class="user-listing-header">Edit Permission</h2>
                </div>
        <div class="card-body">
            <form id="permissionForm">
                @csrf
                <div class="form-group">
                    <label for="name" style="font-weight: bold;">Permission Name <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ $permission->name }}" required>
                </div>
                <button type="button" class="btn btn-primary" id="submitBtn" style="background-color: #2C9DD4; color: white;">Update</button>
                <a href="{{ route('permissions.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('name').addEventListener('input', function() {
        var errorMessage = document.getElementById('error-message');
        if (this.value.trim() && errorMessage) {
            errorMessage.remove();
        }
    });

    document.getElementById('submitBtn').addEventListener('click', function() {
        var nameInput = document.getElementById('name');
        var formData = new FormData(document.getElementById('permissionForm'));
        var errorMessage = document.getElementById('error-message');

        if (!nameInput.value.trim()) {
            if (!errorMessage) {
                errorMessage = document.createElement('div');
                errorMessage.id = 'error-message';
                errorMessage.style.color = 'red';
                errorMessage.textContent = 'Permission Name is required.';
                nameInput.parentNode.insertBefore(errorMessage, nameInput.nextSibling);
            }
            return;
        } else if (errorMessage) {
            errorMessage.remove();
        }

        Swal.fire({
            title: 'Please wait...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        fetch('{{ route('api.permissions.update', $permission->id) }}', {
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
                Swal.close();
                notify('success', 'Permission updated Successfully!');
                setTimeout(function() {
                        window.location.href = '{{ route('permissions.index') }}';
                    }, 1500);
            } else {
                notify('error', 'There was an error updating the permission.');
            }
        })
        .catch(error => {
            Swal.close();
            notify('error', 'An unexpected error occurred.');
        });
    });
</script>
@endsection
