{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')
    {{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: #e3e6f0;">
        <div class="card-header" style="background-color: #a9b5df; color: white; padding: 10px 15px 1px;">
            <h2 class="about-us-create-header" style="text-align: center; font-size: 1.5em; margin-bottom: 6px; font-weight: bold; color: #2d336b;">Update Service</h2>
        </div> --}}
    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a
                                class="breadcrumbLink"href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('call_to_action.index') }}">Call To Action</a>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit Call To Action Data
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit Call To Action Data Details</h4>
            </div>
            <div class="card-body">
                <form id="heroSectionForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="badge_title" style="font-weight: bold;">Budge Title</label>
                        <input type="text" class="form-control" id="badge_title" name="badge_title" value="{{ $call_to_action->badge_title }}">
                    </div>

                    <div class="form-group">
                        <label for="badge_icon" style="font-weight: bold;"> Budge Icon </label>
                        <div class="input-group" style="max-width: 400px;">
                            <span class="input-group-text bg-white" id="badge_icon_preview"
                                style="padding: 0 12px; border-right: 0; min-width: 40px; display: flex; align-items: center; justify-content: center; height: 40px;"></span>
                            <input type="text" class="form-control" id="badge_icon" name="badge_icon"
                                placeholder="Select an icon..." value="{{ $call_to_action->badge_icon }}" aria-describedby="badge_icon_preview" style="height: 40px;">
                            <button type="button" class="btn btn-outline-secondary" role="iconpicker"
                                data-iconset="fontawesome5" data-input="badge_icon" data-preview="badge_icon_preview"
                                style="height: 40px; border-left: 0; margin-top: 0px; border: 1px solid #ced4da;"><i
                                    class="fas fa-icons"></i></button>
                        </div>
                    </div>
                    <div class="feature_part" style="border: #2C9DD4 1px solid; padding: 10px; border-radius: 5px;">
                        <div class="form-group">
                            <h5 style="font-weight: bold; margin-top: 20px; margin-bottom: 15px; color: #2d336b;">Feature
                                Items</h5>
                        </div>
                        <div class="form-group">
                            <label for="feature_1" style="font-weight: bold;">Feature 1 <span
                                    style="color: red;">*</span></label>
                            <input type="text" class="form-control" id="feature_1" name="feature_1"  value="{{ $call_to_action->feature_1 }}"  required>
                        </div>
                        <div class="form-group">
                            <label for="feature_2" style="font-weight: bold;">Feature 2 <span
                                    style="color: red;">*</span></label>
                            <input type="text" class="form-control" id="feature_2" name="feature_2"  value="{{ $call_to_action->feature_2 }}" required>
                        </div>
                        <div class="form-group">
                            <label for="feature_3" style="font-weight: bold;">Feature 3 <span
                                    style="color: red;">*</span></label>
                            <input type="text" class="form-control" id="feature_3" name="feature_3"  value="{{ $call_to_action->feature_3 }}" required>
                        </div>
                        <div class="form-group">
                            <label for="feature_4" style="font-weight: bold;">Feature 4 <span
                                    style="color: red;">*</span></label>
                            <input type="text" class="form-control" id="feature_4" name="feature_4"  value="{{ $call_to_action->feature_4 }}" required>
                        </div>
                    </div>
                    <div class="state_item_1 mt-1" style="border: #2C9DD4 1px solid; padding: 10px; border-radius: 5px;">
                        <div class="form-group">
                            <h5 style="font-weight: bold; margin-top: 20px; margin-bottom: 15px; color: #2d336b;">Stat Items
                                1</h5>
                        </div>
                        <div class="form-group">
                            <label for="stat_count_1" style="font-weight: bold;">Stat Count 1</label>
                            <input type="text" class="form-control" id="stat_count_1" name="stat_count_1"  
                            min="1"  oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/^0+/, '');" value="{{ $call_to_action->stat_count_1 }}">
                        </div>
                        <div class="form-group">
                            <label for="stat_text_1" style="font-weight: bold;">Stat Text 1</label>
                            <input type="text" class="form-control" id="stat_text_1" name="stat_text_1"  value="{{ $call_to_action->stat_text_1 }}">
                        </div>
                        <div class="form-group">
                            <label for="stat_icon_1" style="font-weight: bold;"> Stat Icon 1 </label>
                            <div class="input-group" style="max-width: 400px;">
                                <span class="input-group-text bg-white" id="stat_icon_preview_1"
                                    style="padding: 0 12px; border-right: 0; min-width: 40px; display: flex; align-items: center; justify-content: center; height: 40px;"></span>
                                <input type="text" class="form-control" id="stat_icon_1" name="stat_icon_1"
                                    placeholder="Select an icon..."   value="{{ $call_to_action->stat_icon_1 }}" aria-describedby="stat_icon_preview_1"
                                    style="height: 40px;">
                                <button type="button" class="btn btn-outline-secondary" role="iconpicker"
                                    data-iconset="fontawesome5" data-input="stat_icon_1"
                                    data-preview="stat_icon_preview_1"
                                    style="height: 40px; border-left: 0; margin-top: 0px; border: 1px solid #ced4da;"><i
                                        class="fas fa-icons"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="state_item_2 mt-1" style="border: #2C9DD4 1px solid; padding: 10px; border-radius: 5px;">
                        <div class="form-group">
                            <h5 style="font-weight: bold; margin-top: 20px; margin-bottom: 15px; color: #2d336b;">Stat
                                Items 2</h5>
                        </div>
                        <div class="form-group">
                            <label for="stat_count_2" style="font-weight: bold;">Stat Count 2</label>
                            <input type="text" class="form-control" id="stat_count_2" name="stat_count_2"
                            min="1"  oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/^0+/, '');" value="{{ $call_to_action->stat_count_2 }}" required>
                        </div>
                        <div class="form-group">
                            <label for="stat_text_2" style="font-weight: bold;">Stat Text 2</label>
                            <input type="text" class="form-control" id="stat_text_2" name="stat_text_2" value="{{ $call_to_action->stat_text_2 }}" required>
                        </div>
                        <div class="form-group">
                            <label for="stat_icon_2" style="font-weight: bold;"> Stat Icon 2 </label>
                            <div class="input-group" style="max-width: 400px;">
                                <span class="input-group-text bg-white" id="stat_icon_preview_2"
                                    style="padding: 0 12px; border-right: 0; min-width: 40px; display: flex; align-items: center; justify-content: center; height: 40px;"></span>
                                <input type="text" class="form-control" id="stat_icon_2" name="stat_icon_2"
                                    placeholder="Select an icon..."  value="{{ $call_to_action->stat_icon_2 }}" aria-describedby="stat_icon_preview_2"
                                    style="height: 40px;">
                                <button type="button" class="btn btn-outline-secondary" role="iconpicker"
                                    data-iconset="fontawesome5" data-input="stat_icon_2"
                                    data-preview="stat_icon_preview_2"
                                    style="height: 40px; border-left: 0; margin-top: 0px; border: 1px solid #ced4da;"><i
                                        class="fas fa-icons"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="button_data mt-1" style="border: #2C9DD4 1px solid; padding: 10px; border-radius: 5px;">
                        <div class="form-group">
                            <h5 style="font-weight: bold; margin-top: 20px; margin-bottom: 15px; color: #2d336b;">Button
                                Deatils</h5>
                        </div>
                        <div class="form-group">
                            <label for="button_title" style="font-weight: bold;">Button Title<span
                                    style="color: red;">*</span></label>
                            <input type="text" class="form-control" id="button_title" name="button_title" value="{{ $call_to_action->button_title }}" required>
                        </div>
                        <div class="form-group">
                            <label for="button_link" style="font-weight: bold;">Button Link<span
                                    style="color: red;">*</span></label>
                            <input type="text" class="form-control" id="button_link" name="button_link" value="{{ $call_to_action->button_link }}" required>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary" id="submitBtn"
                        style="background-color: #2C9DD4; color: white;">Submit</button>
                    <a href="{{ route('call_to_action.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <!-- Include common icon picker JS -->
    <script src="{{ asset('js/common-iconpicker.js') }}"></script>

    <script>
        // Clear previous error messages
        document.querySelectorAll('.error-message').forEach(function(el) {
            el.textContent = '';
        });
        document.getElementById('submitBtn').addEventListener('click', function() {
            var formData = new FormData(document.getElementById('heroSectionForm'));
            // Validate form (only required fields in this form)
            var isValid = true;
            if (!formData.get('feature_1')) {
                document.getElementById('feature_1').nextElementSibling.textContent = 'Feature 1 is required.';
                isValid = false;
            }
            if (!formData.get('feature_2')) {
                document.getElementById('feature_2').nextElementSibling.textContent = 'Feature 2 is required.';
                isValid = false;
            }
            if (!formData.get('feature_3')) {
                document.getElementById('feature_3').nextElementSibling.textContent = 'Feature 3 is required.';
                isValid = false;
            }
            if (!formData.get('feature_4')) {
                document.getElementById('feature_4').nextElementSibling.textContent = 'Feature 4 is required.';
                isValid = false;
            }

            if (!formData.get('button_title')) {
                $('#button_title').next('.error-message').text('Button Title is required.');
                isValid = false;
            }
            // if (!formData.get('button_link')) {
            //     $('#button_link').next('.error-message').text('Button Link is required.');
            //     isValid = false;
            // }
            const fieldData = formData.get('button_link');
            const urlRegex = /^(https?:\/\/[a-zA-Z0-9.-]+\/[^\s]*)$/i;
            if (!fieldData) {
                document.getElementById('button_link').nextElementSibling.textContent = "Link is required";
                isValid = false;
            } else if (!urlRegex.test(fieldData)) {
                document.getElementById('button_link').nextElementSibling.textContent = 'Please enter a valid URL.';
                isValid = false;
            } else {
                document.getElementById('button_link').nextElementSibling.textContent = '';
            }


            if (!isValid) {
                return;
            }

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

           fetch('{{ route('api.call_to_action.update', $call_to_action->id) }}', {
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
                        notify('success', 'Call To Action Section Details Updated Successfully!');
                        setTimeout(function() {
                            window.location.href = '{{ route('call_to_action.index') }}';
                        }, 1500);

                    } else {
                        notify('error', data.message || 'There was an error updating the Call To Action Section Detail page.');
                    }
                })
                .catch(error => {
                    Swal.close();
                    notify('error', 'An unexpected error occurred.');

                });
        });

        // Update error message spans for regular inputs
        document.querySelectorAll('.form-control').forEach(function(input) {
            if (!input.classList.contains('select2-hidden-accessible')) { // Exclude Select2
                var errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.style.color = 'red';
                input.parentNode.appendChild(errorSpan);
            }
        });

        document.getElementById('feature_1').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });
        document.getElementById('feature_2').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });
        document.getElementById('feature_3').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });
        document.getElementById('feature_4').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });


        document.getElementById('button_title').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });
        document.getElementById('button_link').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });



        // Removed select2 initializations for non-existent fields
    </script>
@endsection
