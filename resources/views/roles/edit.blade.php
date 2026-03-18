{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
{{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: #e3e6f0;">
        <div class="card-header" style="background-color: #a9b5df; color: white; padding: 10px 15px 1px;">
            <h2 class="user-listing-header" style="text-align: center; font-size: 1.5em; margin-bottom: 6px; font-weight: bold; color: #2d336b;">Edit Role</h2>
        </div> --}}
        <div class="section-breadcrumb">
    <div class="breadcrumb-wrapper pb-0">
        <div class="container">
            <nav aria-label="breadcrumb-nav">
                <ol class="breadcrumb breadcrumb-style-2 my-20">
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a></li> 
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('roles.index') }}">Roles</a></li>
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit Role</li>
                </ol>
            </nav>
        </div>
    </div>
</div>
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h4 class="user-listing-header">Edit Role</h4>
                </div>
        <div class="card-body">
            <form id="roleForm">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="name" style="font-weight: bold;">Role Name <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ $role->name }}" required>
                </div>
                
                <div class="form-group">
                    <label style="font-weight: bold;">Permissions</label>
                    <div id="permissionsTree" class="permissions-grid" style="display: flex; flex-wrap: wrap;">
                        @php
                            $actionOrder = [
                                'index' => 1,
                                'create' => 2,
                                'store' => 3,
                                'show' => 4,
                                'edit' => 5,
                                'update' => 6,
                                'destroy' => 7,
                                'trash' => 8,
                                'restore' => 9,
                            ];

                            $groupedPermissions = $permissions
                                ->groupBy(function ($permission) {
                                    $parts = explode('.', $permission->name);
                                    return $parts[0] ?? 'other';
                                })
                                ->sortKeys();
                        @endphp

                        @foreach($groupedPermissions as $group => $perms)
                            @php
                                $safeGroupId = \Illuminate\Support\Str::slug($group, '-');
                                $sortedPermissions = $perms->sortBy(function ($permission) use ($actionOrder) {
                                    $parts = explode('.', $permission->name);
                                    $action = end($parts);
                                    return ($actionOrder[$action] ?? 999) . '-' . $permission->name;
                                });
                            @endphp
                            <div class="permission-group" style="margin-right: 20px; margin-bottom: 20px; width: 220px;">
                                <input type="checkbox" class="main-checkbox" id="main-{{ $safeGroupId }}">
                                <label for="main-{{ $safeGroupId }}">{{ ucfirst($group) }}</label>
                                <div class="sub-permissions" style="margin-left: 20px;">
                                    @foreach($sortedPermissions as $permission)
                                        @php
                                            $parts = explode('.', $permission->name);
                                            $displayName = count($parts) > 1 ? end($parts) : $permission->name;
                                        @endphp
                                        <div>
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="sub-checkbox main-{{ $safeGroupId }}" {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}>
                                            {{ $displayName }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <button type="button" class="btn btn-primary" id="updateBtn" style="background-color: #2C9DD4;">Update</button>
                <a href="{{ route('roles.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
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

    document.getElementById('updateBtn').addEventListener('click', function() {
        var nameInput = document.getElementById('name');
        var formData = new FormData(document.getElementById('roleForm'));
        var errorMessage = document.getElementById('error-message');

        if (!nameInput.value.trim()) {
            if (!errorMessage) {
                errorMessage = document.createElement('div');
                errorMessage.id = 'error-message';
                errorMessage.style.color = 'red';
                errorMessage.textContent = 'Role Name is required.';
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
        fetch('{{ route('api.roles.update', $role->id) }}', {
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
                notify('success', 'Role updated Successfully!');
                Swal.close();
                setTimeout(function() {
                        window.location.href = '{{ route('roles.index') }}';
                    }, 1500);
            } else {
                Swal.close();
                notify('error', 'There was an error updating the role.');
            }
        })
        .catch(error => {
            Swal.close();
            notify('error', 'An unexpected error occurred.');
        });
    });

    document.querySelectorAll('.main-checkbox').forEach(mainCheckbox => {
        mainCheckbox.addEventListener('change', function() {
            const group = this.id.replace('main-', '');
            const subCheckboxes = document.querySelectorAll(`.sub-checkbox.main-${group}`);
            subCheckboxes.forEach(subCheckbox => {
                subCheckbox.checked = this.checked;
            });
        });
    });

    document.querySelectorAll('.sub-checkbox').forEach(subCheckbox => {
        subCheckbox.addEventListener('change', function() {
            const group = this.classList[1].replace('main-', '');
            const mainCheckbox = document.getElementById(`main-${group}`);
            const subCheckboxes = document.querySelectorAll(`.sub-checkbox.main-${group}`);
            const allChecked = Array.from(subCheckboxes).every(checkbox => checkbox.checked);
            mainCheckbox.checked = allChecked;
        });
    });

    document.querySelectorAll('.main-checkbox').forEach(mainCheckbox => {
        const group = mainCheckbox.id.replace('main-', '');
        const subCheckboxes = document.querySelectorAll(`.sub-checkbox.main-${group}`);
        const allChecked = Array.from(subCheckboxes).every(checkbox => checkbox.checked);
        mainCheckbox.checked = allChecked;
    });
</script>
@endsection
