{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')


@section('content')
{{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: #e3e6f0;">
        <div class="card-header" style="background-color: #a9b5df; color: white; padding: 10px 15px 1px;">
            <h2 class="user-listing-header" style="text-align: center; font-size: 1.5em; margin-bottom: 6px; font-weight: bold; color: #2d336b;">Edit FAQ</h2>
        </div> --}}
        <div class="section-breadcrumb">
    <div class="breadcrumb-wrapper pb-0">
        <div class="container">
            <nav aria-label="breadcrumb-nav">
                <ol class="breadcrumb breadcrumb-style-2 my-20">
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a></li> 
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('faqs.index') }}">FAQs</a></li>
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit FAQ</li>
                </ol>
            </nav>
        </div>
    </div>
</div>
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h2 class="user-listing-header">Edit FAQ</h2>
                </div>
        <div class="card-body">
            <form id="faqForm">
                @csrf
                {{-- <div class="form-group">
                    <label for="category" style="font-weight: bold;">Category <span style="color: red;">*</span></label>
                    <select class="form-control" id="category" name="category_id" required>
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ $faq->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <div id="category-error" style="color: red; display: none;">Please select a category.</div>
                </div> --}}
                <div class="form-group">
                    <label for="question" style="font-weight: bold;">Question <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="question" name="question" value="{{ $faq->question }}" required>
                    <div id="question-error" style="color: red; display: none;">Please enter a question.</div>
                </div>
                <div class="form-group">
                    <label for="answer" style="font-weight: bold;">Answer <span style="color: red;">*</span></label>
                    <textarea class="form-control" id="answer" name="answer" rows="4" required>{{ $faq->answer }}</textarea>
                    <div id="answer-error" style="color: red; display: none;">Please enter an answer.</div>
                </div>
                <button type="button" class="btn btn-primary" id="submitBtn" style="background-color: #2C9DD4; color: white;">Update</button>
                <a href="{{ route('faqs.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- <script src="https://cdn.ckeditor.com/4.25.1-lts/standard/ckeditor.js"></script> -->
<script>
    CKEDITOR.replace('answer');

    document.getElementById('submitBtn').addEventListener('click', function() {
        // var categoryContent = document.getElementById('category').value;
        var questionContent = document.getElementById('question').value.trim();
        var answerContent = CKEDITOR.instances.answer.getData().trim();

        var hasError = false;

        // if (!categoryContent) {
        //     document.getElementById('category-error').style.display = 'block';
        //     hasError = true;
        // } else {
        //     document.getElementById('category-error').style.display = 'none';
        // }

        if (!questionContent) {
            document.getElementById('question-error').style.display = 'block';
            hasError = true;
        } else {
            document.getElementById('question-error').style.display = 'none';
        }

        if (!answerContent) {
            document.getElementById('answer-error').style.display = 'block';
            hasError = true;
        } else {
            document.getElementById('answer-error').style.display = 'none';
        }

        if (hasError) {
            return;
        }

        var formData = new FormData(document.getElementById('faqForm'));
        formData.set('answer', CKEDITOR.instances.answer.getData());

        Swal.fire({
            title: 'Please wait...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        fetch('{{ route('api.faqs.update', $faq->id) }}', {
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
                notify('success', 'FAQ updated Successfully!');
                setTimeout(function() {
                    window.location.href = '{{ route('faqs.index') }}';
                }, 1500);
            } else {
                notify('error', 'There was an error updating the FAQ.');
            }
        })
        .catch(error => {
            Swal.close();
            notify('error', 'An unexpected error occurred.');
        });
    });

    CKEDITOR.instances.answer.on('change', function() {
        var answerContent = CKEDITOR.instances.answer.getData().trim();
        if (answerContent) {
            document.getElementById('answer-error').style.display = 'none';
        }
    });

    document.getElementById('question').addEventListener('input', function() {
        var questionContent = document.getElementById('question').value.trim();
        if (questionContent) {
            document.getElementById('question-error').style.display = 'none';
        }
    });

    // document.getElementById('category').addEventListener('change', function() {
    //     var categoryContent = document.getElementById('category').value;
    //     if (categoryContent) {
    //         document.getElementById('category-error').style.display = 'none';
    //     }
    // });

    // $(document).ready(function() {
    //     $('#category').select2({
    //         placeholder: "Select a Category",
    //         allowClear: true
    //     });
    // });
</script>
@endsection 