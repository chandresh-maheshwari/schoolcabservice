@include('common.header');
@include('common.sidebar');

<body class="user-profile">
    <div class="wrapper ">

        <div class="main-panel" id="main-panel">
            <!-- Navbar -->
            @include('common.navbar');

            <!-- End Navbar -->
            <div class="panel-header panel-header-sm">
            </div>
            <div class="content">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="title">Update Testimonials</h5>
                            </div>
                            {{-- <ul class="nav nav-tabs">
                                <li class="active"><a data-toggle="tab" href="#english">English</a></li>
                                <li><a data-toggle="tab" href="#chinese">chinese</a></li>
                            </ul> --}}
                            {{-- <div class="tab-content"> --}}
                            {{-- <d iv id="english" class="tab-pane fade in active"> --}}
                            <div class="card-body">
                                <form method="post" action='#' enctype="multipart/form-data" id="testimonialForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-12 pr-1">
                                            <input type="hidden" class="form-control" name="id" id="id"
                                                value="{{ $id }}">
                                            <div class="form-group">
                                                <label>Testimonial</label>
                                                <textarea rows="4" cols="80" class="form-control" class="testimonial" id="testimonialtextarea"
                                                    name="testimonialtextarea">
                                                </textarea>
                                                {{-- <textarea class="ckeditor" name="editor1">Write any thing</textarea> --}}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 pr-1">
                                            <div class="form-group">
                                                <label>Added by</label>
                                                <input type="text"class="form-control" name="add_by" id="add_by"
                                                    autocomplete="off" id="add_by">

                                                <!-- <input type=" date text" class="form-control" placeholder="" name="period_by" id="period_by"> -->
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Position</label>
                                                <!-- <input type="text" class="form-control" placeholder="" name="period_to" id="period_to"> -->
                                                <input type="text" class="form-control" name="position"
                                                    id="position" autocomplete="off">

                                            </div>
                                        </div>
                                    </div>


                                    {{-- {{dd($data)}} --}}

                                    <div id="more_content"></div>
                                    <div class="row">
                                        <div class="col-11">
                                            <input type="submit" class="btn btn-success" value="Submit">
                                            <a href="list"><button type="button"
                                                    class="btn btn-outline-success">Cancel</button></a>
                                            {{-- <a href="promocode_datatable" class="btn-default">Cancel</a> --}}
                                        </div>
                                        {{-- <div class="col-1">                                            
                                                    <input class="btn btn-sm btn-info px-1 py-2 add_new" value="Add New" id="more">
                                                </div> --}}
                                        {{-- <button type="button" name="add" id="add" class="btn btn-success">Add More</button> --}}
                                    </div>
                                </form>

                            </div>


                        </div>
                    </div>

                </div>
            </div>
            {{-- $(document).ready(function() {

            $.ajax({
                url: "{{ route('calloffs.update') }}",
                type: "GET",
                data: {
                    token: $("#api_token").html(),
                    id: $("#id").val()
                },
                success: function(response) {
                    setTimeout(() => {

                        $('#company_waste_type_id').val(response.company_waste_type_id).trigger('change');
                    }, 335);
                    $('#calloff_line_no').val(response.calloff_line_no);
                    $('#calloff_description').val(response.calloff_description);
                    $('#calloff_cost').val(response.calloff_cost);
                    $('#calloff_units').val(response.calloff_units);
                    $('#calloff_active').val(response.calloff_active);


                }
            });
        }); --}}
            {{-- <script>
                CKEDITOR.replace('testimonial');
            </script> --}}
            <script>
                CKEDITOR.replace('testimonialtextarea');
            </script>
            <script>
                $(document).ready(function() {

                    $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            }); 
                    $.ajax({
                        url: "{{ route('testionmonial.update') }}",
                        type: "GET",
                        data: {
                            // token: $("#api_token").html(),
                            id: $("#id").val()
                        },
                        success: function(response) {

                            CKEDITOR.instances['testimonialtextarea'].setData(response.testimonial); 
                            $('#add_by').val(response.add_by);
                            $('#position').val(response.position);


                        }
                    });

                    $("#testimonialForm").submit(function(e) {
                e.preventDefault();

            //    var data = $(this).serialize();

            //    console.log(data);
                $.ajax({
                    url: "{{ route('testionmonial.update-save') }}",
                    type: "post",
                    data: $(this).serialize(),
                    // data: {
                    //     // data: $(this).serialize(),
                    // },
                    success: function(data) {
                       
                        if (data.success) {
                    swal('Testimonial Update Successfully', '', 'success');
                } else {
                    swal('Error saving testimonial', '', 'error');
                }

                       
                    },
                    error: function(error) {
                swal('Error saving testimonial', '', 'error');
            }
                });

            });
                });
            </script>


            @include('common.footer');
