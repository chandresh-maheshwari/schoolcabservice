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
                            Add Price Detail
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add Price Details</h4>
            </div>

            <div class="card-body">
                <form id="priceForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>Title <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="icon" style="font-weight: bold;">Plan Icon <span
                                style="color: red;">*</span></label>
                        <div class="input-group" style="max-width: 400px;">
                            <span class="input-group-text bg-white" id="icon-preview-1"
                                style="padding: 0 12px; border-right: 0; min-width: 40px; display: flex; align-items: center; justify-content: center; height: 40px;"></span>
                            <input type="text" class="form-control" id="plan_icon" name="plan_icon" required
                                placeholder="Select an icon..." aria-describedby="icon-preview-1" style="height: 40px;">
                            <button type="button" class="btn btn-outline-secondary" role="iconpicker"
                                data-iconset="fontawesome5" data-input="plan_icon" data-preview="icon-preview-1"
                                style="height: 40px; border-left: 0; margin-top: 0px; border: 1px solid #ced4da;"><i
                                    class="fas fa-icons"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="icon" style="font-weight: bold;">Currency Icon <span
                                style="color: red;">*</span></label>
                        <div class="input-group" style="max-width: 400px;">
                            <span class="input-group-text bg-white" id="icon-preview-1"
                                style="padding: 0 12px; border-right: 0; min-width: 40px; display: flex; align-items: center; justify-content: center; height: 40px;"></span>
                            <input type="text" class="form-control" id="currency_icon" name="currency_icon" required
                                placeholder="Select an icon..." aria-describedby="icon-preview-2" style="height: 40px;">
                            <button type="button" class="btn btn-outline-secondary" role="iconpicker"
                                data-iconset="fontawesome5" data-input="currency_icon" data-preview="icon-preview-2"
                                style="height: 40px; border-left: 0; margin-top: 0px; border: 1px solid #ced4da;"><i
                                    class="fas fa-icons"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Amount <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="amount" name="amount" required autocomplete="off"
                            oninput="this.value = this.value < 1 ? '' : this.value">
                    </div>
                    <div class="form-group">
                        <label>Period <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="period" name="period" autocomplete="off"
                            oninput="this.value = this.value.replace(/[^a-zA-Z0-9 ]/g, '')">
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
                        <input type="url" class="form-control" id="button_link" name="button_link"
                            autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Is Most Popular</label>
                        <select name="is_most_popular" class="form-control">
                            <option value="no">No</option>
                            <option value="yes">Yes</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" id="submitBtn">Submit</button>
                    <a href="{{ route('priceSection.index') }}" class="btn btn-secondary">Cancel</a>
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
            let formData = new FormData(document.getElementById('priceForm'));
             formData.set('description', CKEDITOR.instances.description.getData());
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }
            if (!formData.get('title')) showError('#title', 'Title is required');
            if (!formData.get('plan_icon')) {
                $('#plan_icon').closest('.form-group').append(
                    '<span class="error-message" style="color: red; display: block; margin-top: 5px;">Plan Icon  is required.</span>'
                );
                isValid = false;
            }
            if (!formData.get('currency_icon')) {
                $('#currency_icon').closest('.form-group').append(
                    '<span class="error-message" style="color: red; display: block; margin-top: 5px;">Currency Icon  is required.</span>'
                );
                isValid = false;
            }
            const amount = formData.get('amount');

            if (!amount) {
                showError('#amount', 'Amount is required');
            } else if (isNaN(amount) || Number(amount) <= 0) {
                showError('#amount', 'Please enter a valid positive amount');
            }
            if (!formData.get('period')) showError('#period', 'Period is required');
            if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
                isValid = false;
            }

        // Answer validation (CKEditor)
        if (!CKEDITOR.instances.description.getData().trim()) {
            if ($('#description').next('.cke').next('.error-message').length === 0) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color:red;">Description is required.</span>'
                );
            }
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

            fetch('{{ route('api.priceSection.store') }}', {
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
                        notify('success', 'Price Section created successfully!');
                        setTimeout(() => window.location.href = '{{ route('priceSection.index') }}', 1500);
                    } else {
                        notify('error', data.message || 'Something went wrong');
                    }
                });
        });

        /* REAL-TIME ERROR REMOVE */
        $(document).on('input change', 'input, select', function() {
            $(this).next('.error-message').remove();
        });
        document.getElementById('plan_icon').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('currency_icon').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
         CKEDITOR.instances.description.on('change', function () {
        $('#description').next('.cke').next('.error-message').remove();
    });
    </script>
@endsection
