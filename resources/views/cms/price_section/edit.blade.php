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
                            <a class="breadcrumbLink" href="{{ route('priceSection.index') }}">Price List</a>
                        </li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active">
                            Edit Price Detail
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit Price Details</h4>
            </div>

            <div class="card-body">
                <form id="priceForm">
                    @csrf
                    <input type="hidden" id="price_id" value="{{ $price->id }}">

                    <div class="form-group">
                        <label>Title <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                            value="{{ $price->title }}">
                    </div>

                    {{-- Plan Icon --}}
                    <div class="form-group">
                        <label style="font-weight: bold;">Plan Icon <span style="color:red;">*</span></label>
                        <div class="input-group" style="max-width: 400px;">
                            <span class="input-group-text bg-white" id="icon-preview-plan">
                                <i class="{{ $price->plan_icon }}"></i>
                            </span>
                            <input type="text" class="form-control" id="plan_icon" name="plan_icon"
                                value="{{ $price->plan_icon }}">
                            <button type="button" class="btn btn-outline-secondary" role="iconpicker"
                                data-iconset="fontawesome5" data-input="plan_icon" data-preview="icon-preview-plan">
                                <i class="fas fa-icons"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Currency Icon --}}
                    <div class="form-group">
                        <label style="font-weight: bold;">Currency Icon <span style="color:red;">*</span></label>
                        <div class="input-group" style="max-width: 400px;">
                            <span class="input-group-text bg-white" id="icon-preview-currency">
                                <i class="{{ $price->currency_icon }}"></i>
                            </span>
                            <input type="text" class="form-control" id="currency_icon" name="currency_icon"
                                value="{{ $price->currency_icon }}">
                            <button type="button" class="btn btn-outline-secondary" role="iconpicker"
                                data-iconset="fontawesome5" data-input="currency_icon" data-preview="icon-preview-currency">
                                <i class="fas fa-icons"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Amount <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="amount" name="amount" min="1"
                            step="0.01" value="{{ $price->amount }}" required autocomplete="off"
                            oninput="this.value = this.value < 1 ? '' : this.value">
                    </div>

                    <div class="form-group">
                        <label>Period <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="period" name="period"
                            value="{{ $price->period }}" autocomplete="off"
                            oninput="this.value = this.value.replace(/[^a-zA-Z0-9 ]/g, '')">
                    </div>

                    <div class="form-group">
                        <label>Description <span style="color:red;">*</span></label>
                        <textarea class="form-control" id="description" name="description">{{ $price->description }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Button Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="button_name" name="button_name"
                            value="{{ $price->button_name }}">
                    </div>

                    <div class="form-group">
                        <label>Button Link <span style="color:red;">*</span></label>
                        <input type="url" class="form-control" id="button_link" name="button_link"
                            value="{{ $price->button_link }}">
                    </div>

                    <div class="form-group">
                        <label>Is Most Popular</label>
                        <select name="is_most_popular" id="is_most_popular" class="form-control">
                            <option value="no" {{ $price->is_most_popular == 'no' ? 'selected' : '' }}>No</option>
                            <option value="yes" {{ $price->is_most_popular == 'yes' ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>

                    <button type="button" class="btn btn-primary" id="updateBtn">Update</button>
                    <a href="{{ route('priceSection.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script src="{{ asset('js/common-iconpicker.js') }}"></script>

    <script>
        CKEDITOR.replace('description');
        $('#updateBtn').on('click', function() {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('priceForm'));
            formData.set('description', CKEDITOR.instances.description.getData());
            let id = $('#price_id').val();
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('title')) showError('#title', 'Title is required');

            const amount = formData.get('amount');
            if (!amount || Number(amount) <= 0) {
                showError('#amount', 'Please enter a valid positive amount');
            }

            if (!formData.get('period')) showError('#period', 'Period is required');
            if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
                isValid = false;
            }
            if (!formData.get('button_name')) showError('#button_name', 'Button Name is required');
            if (!formData.get('button_link')) showError('#button_link', 'Button Link is required');

            if (!isValid) return;

            Swal.fire({
                title: 'Updating...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            formData.append('_method', 'PUT');
            fetch('{{ route('api.priceSection.update', $price->id) }}', {
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
                        notify('success', 'Price Section updated successfully!');
                        setTimeout(() => window.location.href = '{{ route('priceSection.index') }}', 1200);
                    } else {
                        notify('error', data.message || 'Something went wrong');
                    }
                });
        });

        $(document).on('input change', 'input, select, textarea', function() {
            $(this).next('.error-message').remove();
        });
        CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });
    </script>
@endsection
