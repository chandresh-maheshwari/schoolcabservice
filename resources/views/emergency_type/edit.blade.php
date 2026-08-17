@extends('admin_layout.index')

@section('content')
    @php
        $isSchoolPanel = request()->route('schoolSlug') !== null;
        $dashboardRoute = $isSchoolPanel ? route('school.dashboard', ['schoolSlug' => request()->route('schoolSlug')]) : route('admin_layout.index');
        $indexRoute = $isSchoolPanel ? route('school.emergencyType.index', ['schoolSlug' => request()->route('schoolSlug')]) : route('emergencyType.index');
    @endphp
    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ $dashboardRoute }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ $indexRoute }}">Emergency Type</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit Emergency Type</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="hero-edit-header">Edit Emergency Type</h4>
            </div>
            <div class="card-body">
                <form id="editEmergencyTypeForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="emergency_type" style="font-weight: bold;">Emergency Type <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="emergency_type" name="emergency_type" value="{{ $emergencyType->emergency_type }}">
                    </div>
                    <button type="button" class="btn btn-primary" id="submitBtn" style="background-color: #2C9DD4; color: white;">Update</button>
                    <a href="{{ $indexRoute }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('submitBtn').addEventListener('click', function() {
            var formData = new FormData(document.getElementById('editEmergencyTypeForm'));

            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });

            var isValid = true;
            if (!formData.get('emergency_type')) {
                document.getElementById('emergency_type').nextElementSibling.textContent = 'Emergency Type is required.';
                isValid = false;
            } else if (!/^(?=.*[A-Za-z])[A-Za-z\s]+$/.test(formData.get('emergency_type').trim())) {
                document.getElementById('emergency_type').nextElementSibling.textContent = 'Emergency Type must contain letters only. Digits are not allowed.';
                isValid = false;
            }

            if (!isValid) {
                return;
            }

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route('api.emergencyType.update', $emergencyType->id) }}', {
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
                        notify('success', 'Emergency Type updated successfully!');
                        setTimeout(function() {
                            window.location.href = '{{ $indexRoute }}';
                        }, 1500);
                    } else {
                        notify('error', data.message || 'There was an error updating the Emergency Type.');
                    }
                })
                .catch(() => {
                    Swal.close();
                    notify('error', 'An unexpected error occurred.');
                });
        });

        document.querySelectorAll('.form-control').forEach(function(input) {
            if (!input.classList.contains('select2-hidden-accessible')) {
                var errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.style.color = 'red';
                input.parentNode.appendChild(errorSpan);
            }
        });

        document.getElementById('emergency_type').addEventListener('input', function() {
            this.value = this.value.replace(/[^A-Za-z\s]/g, '');
            $(this).closest('.form-group').find('.error-message').remove();
        });
    </script>
@endsection
