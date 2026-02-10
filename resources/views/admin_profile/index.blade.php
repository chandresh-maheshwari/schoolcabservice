{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('admin_layout.index') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Profile</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="row  d-flex justify-content-center">
        <div class="col-5 profile">
            <div class="card">
                <div class="card-body">
                    {{-- <div class="row d-flex justify-content-center"> --}}
                    {{-- <div class="col-lg-5 "> --}}
                    <div class="border-bottom text-center pb-4">
                        <img src="{{ Auth::user()->photo ? asset('storage/' . Auth::user()->photo) : asset('assets/images/person.jpg') }}"
                            alt="profile" class="img-lg rounded-circle mb-3" />
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('profile.edit', ['profile' => Auth::user()->id]) }}"
                                class="btn btn-success">Edit Profile</a>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                data-bs-target="#changePhotoModal">Change Photo</button>
                        </div>
                    </div>
                    <div class="py-4">
                        <p class="clearfix">
                            <span class="float-start"> Status </span>
                            <span class="float-end text-muted"> Active </span>
                        </p>
                        <p class="clearfix">
                            <span class="float-start"> Name </span>
                            <span class="float-end text-muted"> {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                            </span>
                        </p>
                        <p class="clearfix">
                            <span class="float-start"> Phone </span>
                            <span class="float-end text-muted"> {{ Auth::user()->mobile }} </span>
                        </p>
                        <p class="clearfix">
                            <span class="float-start"> Mail </span>
                            <span class="float-end text-muted"> {{ Auth::user()->email }} </span>
                        </p>
                    </div>
                    {{-- </div> --}}

                    {{-- </div> --}}
                </div>
            </div>
        </div>
    </div>


    <!-- Change Photo Modal -->
    <div class="modal fade" id="changePhotoModal" tabindex="-1" aria-labelledby="changePhotoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePhotoModalLabel">Change Profile Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="photoUploadForm" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group text-center mb-30">
                            <img id="photoPreview"
                                src="{{ Auth::user()->photo ? asset('storage/' . Auth::user()->photo) : asset('assets/images/person.jpg') }}"
                                class="rounded-circle" style="width:100px;height:100px;">

                            <input type="file" class="custom-file-input" id="photo" name="photo" accept="image/*"
                                style="display: none;">
                            <button type="button" class="btn btn-primary"
                                onclick="document.getElementById('photo').click();">Update Profile Picture</button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="uploadPhotoBtn">Upload Photo</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Keep existing edit profile JavaScript
        $(document).on('click', '#edit', function() {
            let userId = $(this).data('id');

            $.ajax({
                url: `/api/admin_layout/${userId}/edit`,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        const user = response.data;
                        $('#first_name').val(user.first_name);
                        $('#last_name').val(user.last_name);
                        $('#mobile').val(user.mobile);
                        $('#email').val(user.email);
                        $('#submitBtn').text('Update');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load user data.'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An unexpected error occurred.'
                    });
                }
            });
        });

        // Add new photo upload JavaScript
        document.getElementById('photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('photoPreview');
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('uploadPhotoBtn').addEventListener('click', function() {
            const formData = new FormData(document.getElementById('photoUploadForm'));

            // Only append photo if a new one is selected
            if (document.getElementById('photo').files.length > 0) {
                formData.append('photo', document.getElementById('photo').files[0]);
            }

            Swal.fire({
                title: 'Uploading...',
                text: 'Please wait while we upload your photo.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '/api/profile/' + {{ Auth::user()->id }} + '/update-photo',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        Swal.close();
                        notify('success', 'Profile photo updated Successfully!');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        Swal.close();
                        notify('error', response.message || 'Failed to update photo.');
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    let errorMessage = 'An unexpected error occurred.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = Object.values(xhr.responseJSON.errors).flat().join('\n');
                    }
                    notify('error', errorMessage);
                }
            });
        });
    </script>
@endsection
