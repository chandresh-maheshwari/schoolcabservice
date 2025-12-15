@extends('admin_layout.index')

@section('content')
    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('cherrypik_pages.index') }}">Cherrypik Pages</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Add Page</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4>Add Page</h4>
            </div>
            <div class="card-body">
                <form id="pageForm">
                    @csrf
                    <div class="form-group">
                        <label for="title" style="font-weight:bold;">Title <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="template" style="font-weight:bold;">Template <span style="color:red;">*</span></label>
                        <select class="form-control" id="template" name="template" onchange="updateImageRequirement()"
                            required>
                            <option value="">Select Template</option>
                            @foreach ($templates ?? [] as $tpl)
                                <option value="{{ $tpl }}">{{ ucfirst(str_replace('_', ' ', $tpl)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="description" style="font-weight:bold;">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="image" style="font-weight:bold;">Image <span style="color:red;">*</span></label>
                        <p id="requiredSize" style="font-weight:bold; color:#555;"></p>

                        <p id="imageError" style="font-weight:bold; margin-top:5px; color:red; display:none;">
                        </p>

                        <div class="mt-2">
                            <input type="file" class="form-control-file" id="image" name="image" accept="image/*"
                                style="display: none;" onchange="previewImage(event)">
                            <button type="button" class="btn btn-primary" id="uploadImageBtn"
                                onclick="document.getElementById('image').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Image</button>
                            <span id="imageName">No image selected</span>
                            {{-- <img id="imagePreview" src="#" alt="Image Preview"
                                style="display: none; width: 100px; height: 100px; margin-top: 10px;"> --}}
                        </div>
                        <div id="dlt_btn_div" class="dlt_btn_div" style="display: none;">
                            <img id="imagePreview" src="#" alt="Image Preview"
                                style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                            <button type="button" class="btn" style="display: none" id="removeImageBtn"><i
                                    class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    {{-- <div class="form-group">
                        <label for="width" style="font-weight:bold;">Width </label>
                        <input type="text" class="form-control" id="width" name="width">
                    </div>
                    <div class="form-group">
                        <label for="hight" style="font-weight:bold;">Height </label>
                        <input type="text" class="form-control" id="hight" name="hight">
                    </div> --}}
                    <div class="form-group">
                        <label for="status" style="font-weight:bold;">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" id="submitBtn">Save</button>
                    <a href="{{ route('cherrypik_pages.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <script>
        function getTemplateSize(template) {
            switch (template.toLowerCase()) {
                case "hero":
                case "about_us":
                case "feature":
                    return {
                        width: 546, height: 364
                    };
                case "why_us":
                    return {
                        width: 451, height: 601
                    };
                case "call_to_action":
                    return {
                        width: 450, height: 707
                    };
            }
        }

        function updateImageRequirement() {
            let template = document.getElementById('template').value;
            let size = getTemplateSize(template);

            let sizeBox = document.getElementById('requiredSize');
            let errorBox = document.getElementById('imageError');

            sizeBox.innerHTML =
                `Required Image Size: <strong>${size.width} x ${size.height} px</strong>`;

            // Normal grey note (forcefully)
            sizeBox.style.color = "#555";

            // Hide error message on template change
            errorBox.style.display = "none";
            errorBox.innerHTML = "";
        }

        function previewImage(event) {
            const file = event.target.files[0];
            const errorBox = document.getElementById('imageError');
            const sizeBox = document.getElementById('requiredSize');

            // Clear previous error
            errorBox.style.display = "none";
            errorBox.innerHTML = "";

            if (!file) return;

            let template = document.getElementById('template').value;
            if (!template) {
                errorBox.style.display = "block";
                errorBox.style.color = "red";
                errorBox.innerHTML = "Please select a template first!";
                event.target.value = "";
                return;
            }

            let size = getTemplateSize(template);

            const img = new Image();
            img.onload = function() {

                if (this.width < size.width || this.height < size.height) {

                    // Wrong image → ERROR RED BELOW upload
                    errorBox.style.display = "block";
                    errorBox.style.color = "red"; // forcefully
                    errorBox.innerHTML =
                        `Wrong Image! Required: ${size.width}x${size.height}px — Selected: ${this.width}x${this.height}px`;

                    event.target.value = "";
                    document.getElementById('imagePreview').style.display = "none";
                    return;
                }

                // Correct image → remove error
                errorBox.style.display = "none";
                errorBox.innerHTML = "";

                sizeBox.style.color = "#555";

                // Preview
                document.getElementById('imagePreview').src = URL.createObjectURL(file);
                document.getElementById('imagePreview').style.display = "block";
                document.getElementById('dlt_btn_div').style.display = "block";
            };

            img.src = URL.createObjectURL(file);
        }
        const maxLength = 200;
        CKEDITOR.replace('description');

        // Ensure error span exists under description
        (function ensureDescErrorSpan() {
            const container = document.querySelector('#description').parentNode;
            let span = container.querySelector('.error-message');
            if (!span) {
                span = document.createElement('span');
                span.className = 'error-message';
                span.style.color = 'red';
                container.appendChild(span);
            }
        })();

        const getErrorSpan = () => document.querySelector('#description').parentNode.querySelector('.error-message');

        // Editor runtime limits (typing)
        CKEDITOR.instances.description.on('contentDom', function() {
            const editor = this;
            const editable = editor.editable();

            editable.attachListener(editable, 'keydown', function(evt) {
                const text = editor.getData().replace(/<[^>]*>/g, '').trim();
                const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp',
                    'ArrowDown', 'Control', 'Meta', 'Alt'
                ];
                if (text.length >= maxLength && !allowedKeys.includes(evt.data.$.key)) {
                    evt.data.$.preventDefault();
                    getErrorSpan().textContent = `Description cannot exceed ${maxLength} characters.`;
                } else if (text !== '') {
                    getErrorSpan().textContent = '';
                }
            });

            editable.attachListener(editable, 'input', function() {
                let text = editor.getData().replace(/<[^>]*>/g, '').trim();
                if (text === '') {
                    getErrorSpan().textContent = 'Description is required.';
                } else if (text.length > maxLength) {
                    editor.setData(text.slice(0, maxLength));
                    editor.focus();
                    getErrorSpan().textContent = `Description cannot exceed ${maxLength} characters.`;
                } else {
                    getErrorSpan().textContent = '';
                }
            });
        });

        // Enforce limit on paste
        CKEDITOR.instances.description.on('paste', function(evt) {
            const editor = this;
            const currentText = editor.getData().replace(/<[^>]*>/g, '').trim();
            const incomingHtml = (evt.data && evt.data.dataValue) || '';
            const incomingText = incomingHtml.replace(/<[^>]*>/g, '');
            const allowed = Math.max(0, maxLength - currentText.length);
            if (incomingText.length > allowed) {
                evt.cancel();
                if (allowed > 0) {
                    editor.insertText(incomingText.slice(0, allowed));
                }
                getErrorSpan().textContent = `Description cannot exceed ${maxLength} characters.`;
            }
        });

        // Fallback on general changes
        CKEDITOR.instances.description.on('change', function() {
            const text = this.getData().replace(/<[^>]*>/g, '').trim();
            if (text.length > maxLength) {
                this.setData(text.slice(0, maxLength));
                this.focus();
                getErrorSpan().textContent = `Description cannot exceed ${maxLength} characters.`;
            } else if (text === '') {
                getErrorSpan().textContent = 'Description is required.';
            } else {
                getErrorSpan().textContent = '';
            }
        });

        document.getElementById('submitBtn').addEventListener('click', function() {
            const form = document.getElementById('pageForm');
            const formData = new FormData(form);
            formData.set('description', CKEDITOR.instances.description ? CKEDITOR.instances.description.getData() :
                document.getElementById('description').value);
            // client-side required validation like other forms
            // document.querySelectorAll('.error-message').forEach(e => e.remove());
            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });

            // let ok = true;
            var isValid = true;
            if (!formData.get('title')) {
                document.getElementById('title').nextElementSibling.textContent = 'Title is required.';
                isValid = false;
            }

            // Validate description required and length on submit
            const descPlainText = (CKEDITOR.instances.description ? CKEDITOR.instances.description.getData() : '')
                .replace(/<[^>]*>/g, '').trim();
            if (!descPlainText) {
                getErrorSpan().textContent = 'Description is required.';
                isValid = false;
            } else if (descPlainText.length > maxLength) {
                getErrorSpan().textContent = `Description cannot exceed ${maxLength} characters.`;
                isValid = false;
            }

            // template/width/height checks continue
            if (!formData.get('template')) {
                const el = document.getElementById('template');
                el.insertAdjacentHTML('afterend',
                    '<span class="error-message" style="color:red;">Template is required.</span>');
                // ok = false;
            }
            // if (!formData.get('width')) {
            //     document.getElementById('width').nextElementSibling.textContent = 'width is required.';
            //     isValid = false;
            // }

            // if (!formData.get('hight')) {
            //     document.getElementById('hight').nextElementSibling.textContent = 'Height is required.';
            //     isValid = false;
            // }
            // if (!formData.get('template')) {
            //     $('#template').next('.select2').find('.select2-selection').after(
            //         '<span class="error-message" style="color: red;">Template is required.</span>');
            //     isValid = false;
            // }
            if (!isValid) return;
            fetch('{{ route('cherrypik_pages.store') }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                }
            }).then(r => r.json()).then(d => {
                if (d.success) {
                    notify('success', 'Page created Successfully');
                    setTimeout(() => window.location = '{{ route('cherrypik_pages.index') }}', 800);
                } else {
                    notify('error', d.message || 'Error creating page');
                }
            }).catch(() => notify('error', 'Unexpected error'));
        });
        document.querySelectorAll('.form-control').forEach(function(input) {
            if (!input.classList.contains('select2-hidden-accessible')) { // Exclude Select2
                var errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.style.color = 'red';
                input.parentNode.appendChild(errorSpan);
            }
        });

        // CKEDITOR.replace('description');



        // function cpPreviewImage(event) {
        //     var reader = new FileReader();
        //     reader.onload = function() {
        //         var output = document.getElementById('imagePreview');
        //         output.src = reader.result;
        //         output.style.display = 'block';
        //     }
        //     var file = event.target.files[0];
        //     if (file) {
        //         reader.readAsDataURL(file);
        //         var imageName = document.getElementById('imageName');
        //         imageName.textContent = file.name;
        //     }
        // }

        // document.getElementById('title').addEventListener('input', function() {
        //     this.parentNode.querySelector('.error-message').textContent = '';
        // });
        const titleInput = document.getElementById('title');
        const errorSpan = titleInput.parentNode.querySelector('.error-message');

        titleInput.addEventListener('input', function() {
            let value = this.value;

            if (value.length > 50) {
                this.value = value.slice(0, 50); // stop extra characters
                errorSpan.textContent = 'Title cannot exceed 50 characters.';
            } else if (value.trim() === '') {
                errorSpan.textContent = 'Title is required.';
            } else {
                errorSpan.textContent = '';
            }
        });

        // const maxLength = 200;
        // CKEDITOR.replace('description');

        // const errorSpanDesc = document.querySelector('#description').parentNode.querySelector('.error-message');

        // // If the error span doesn't exist, create one
        // if (!errorSpanDesc) {
        //     const span = document.createElement('span');
        //     span.className = 'error-message';
        //     span.style.color = 'red';
        //     document.querySelector('#description').parentNode.appendChild(span);
        // }

        // const getErrorSpan = () => document.querySelector('#description').parentNode.querySelector('.error-message');

        // CKEDITOR.instances.description.on('contentDom', function() {
        //     const editor = this;
        //     const editable = editor.editable();

        //     editable.attachListener(editable, 'keydown', function(evt) {
        //         const text = editor.getData().replace(/<[^>]*>/g, '').trim();
        //         const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp',
        //             'ArrowDown', 'Control', 'Meta', 'Alt'
        //         ];

        //         if (text.length >= maxLength && !allowedKeys.includes(evt.data.$.key)) {
        //             evt.data.$.preventDefault();
        //             getErrorSpan().textContent = `Description cannot exceed ${maxLength} characters.`;
        //             isValid = false;
        //         } else {
        //             if (text !== '') {
        //                 getErrorSpan().textContent = '';
        //             }
        //         }
        //     });

        //     editable.attachListener(editable, 'input', function() {
        //         let text = editor.getData().replace(/<[^>]*>/g, '').trim();

        //         if (text === '') {
        //             getErrorSpan().textContent = 'Description is required.';
        //             isValid = false;
        //         } else if (text.length > maxLength) {
        //             editor.setData(text.slice(0, maxLength));
        //             editor.focus();
        //             getErrorSpan().textContent = `Description cannot exceed ${maxLength} characters.`;
        //             isValid = false;
        //         } else {
        //             getErrorSpan().textContent = ''; // Clear error when valid input present
        //         }
        //     });
        // });


        $('#template').on('change', function() {
            $(this).next().remove();
        });

        // document.getElementById('width').addEventListener('input', function() {
        //     this.parentNode.querySelector('.error-message').textContent = '';
        // });

        // document.getElementById('hight').addEventListener('input', function() {
        //     this.parentNode.querySelector('.error-message').textContent = '';
        // });

        document.getElementById('removeImageBtn').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview',
                imageNameSelector: '#imageName',
                imageInputSelector: '#image',
                removeImageBtnSelector: '#removeImageBtn'
            });
        });
    </script>
@endsection
