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
                                <h5 class="title">Add Testimonials</h5>
                            </div>
                            {{-- <ul class="nav nav-tabs">
                                <li class="active"><a data-toggle="tab" href="#english">English</a></li>
                                <li><a data-toggle="tab" href="#chinese">chinese</a></li>
                            </ul> --}}
                            {{-- <div class="tab-content"> --}}
                            {{-- <d iv id="english" class="tab-pane fade in active"> --}}
                            <div class="card-body">
                                <form method="post" action='#'
                                    enctype="multipart/form-data" id="testimonialForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-12 pr-1">
                                            <div class="form-group">
                                                <label>Testimonial</label>
                                                <textarea rows="4" cols="80" class="form-control"  class="testimonial" id="testimonialtextarea" name="testimonialtextarea">
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
                                                    id="position"  autocomplete="off">

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
                            {{-- </div> --}}
                            {{-- <div id="chinese" class="tab-pane fade"> --}}
                            {{-- <div class="card-body">
                                        <form method="post" action='{{ route('promocode_add_data') }}'
                                            enctype="multipart/form-data">
                                            @csrf
                                            <div class="row">
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Code</label>
                                                        <input type="text" class="form-control" placeholder="" name="code_cn"
                                                            id="code_cn" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Name</label>
                                                        <input type="text" class="form-control" placeholder="" name="name_cn"
                                                            id="name_cn" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 pr-1">
                                                    <div class="form-group">
                                                        <label>Validity Period (by)</label>
                                                        <input type="text"class="form-control" 
                                                        name="period_by_cn"  id="period_by_cn" required autocomplete="off">
        
                                                        <!-- <input type=" date text" class="form-control" placeholder="" name="period_by" id="period_by"> -->
                                                    </div>
                                                </div>
        
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Validity Period (To)</label>
                                                        <!-- <input type="text" class="form-control" placeholder="" name="period_to" id="period_to"> -->
                                                        <input type="text" class="form-control"
                                                         name="period_to_cn" id="period_to_cn" required autocomplete="off">
        
                                                    </div>
                                                </div>
                                            </div> --}}


                            {{-- {{dd($data)}} --}}

                            {{-- <div id="more_content_cn"></div>
                                            <div class="row">
                                                <div class="col-11">
                                                    <input type="submit" class="btn btn-success" value="Submit">
                                                    <a href="promocode_datatable/cn"><button type="button" class="btn btn-outline-success">Cancel</button></a> --}}
                            {{-- <a href="promocode_datatable" class="btn-default">Cancel</a> --}}
                            {{-- </div>
                                                <div class="col-1">                                            
                                                    <input class="btn btn-sm btn-info px-1 py-2 add_new" value="Add New" id="more_cn">
                                                </div> --}}
                            {{-- <button type="button" name="add" id="add" class="btn btn-success">Add More</button> --}}
                            {{-- </div>
                                        </form>
        
                                    </div> --}}
                            {{-- </div> --}}


                            {{-- </div> --}}

                        </div>
                    </div>

                </div>
            </div>

            {{-- <script>
                CKEDITOR.replace('testimonial');
            </script> --}}
            <script>
                CKEDITOR.replace('testimonialtextarea');
            </script>
            <script>
               $(document).ready(function() {
    $('#testimonialForm').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: "{{ route('testionmonial.store') }}",
            type: 'POST',
            data: $(this).serialize(),
            success: function(data) {
                if (data.success) {
                    swal('Testimonial saved Successfully', '', 'success');
                    $('#testimonialForm')[0].reset();
                    CKEDITOR.instances['testimonialtextarea'].setData('');
                } else {
                    swal('Error saving testimonial', '', 'error');
                }
            },
           
        });
    });
});
            </script>




            {{-- <script type="text/javascript">
                $(document).ready(function() {// set default dates
                    var start = new Date();
                    // set end date to max one year period:
                    var end = new Date(new Date().setYear(start.getFullYear()+1));

                    $('#period_by').datepicker({
                        startDate : start,
                        endDate   : end
                    // update "toDate" defaults whenever "fromDate" changes
                    }).on('changeDate', function(){
                        // set the "toDate" start to not be later than "fromDate" ends:
                        $('#period_to').datepicker('setStartDate', new Date($(this).val()));
                    }); 

                    $('#period_to').datepicker({
                        startDate : start,
                        endDate   : end
                    // update "fromDate" defaults whenever "toDate" changes
                    }).on('changeDate', function(){
                        // set the "fromDate" end to not be later than "toDate" starts:
                        $('#period_by').datepicker('setEndDate', new Date($(this).val()));
                    });
                    
                    selectRefresh();
                    
                    let i = 0;
                    $("#more").click(function() {
                        i++;
                        console.log($('#pd_section').html());
                        var htmlCotent = `<div class="row add_row" id="row_`+i+`">
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <label>Prices</label>
                                                    <input type="number" step=0.01 class="form-control" name="price[]">
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <label>Products List</label>
                                                    <select name="product_dropdown[]" id="product_dropdown_`+i+`"
                                                        class="form-control p-2 copied_section selectpicker">
                                                        <option value="">Select an option</option>
                                                        @foreach ($data as $datas)
                                                            <option value="{{ $datas->id }}">{{ $datas->title }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-sm-2">
                                                <div class="form-group">
                                                    {{-- <input class="btn btn-danger remove" id="remove_`+i+`"> --}}
            {{-- <a class="btn btn-danger remove" id="remove_`+i+`"><i class="fa fa-trash" style="color: #fff;"></i></a>
                                                </div>
                                            </div>
                                        </div>`;
                        $("#more_content").append(htmlCotent);                        
                       
                          selectRefresh();  
             
                        

                    
                    $(document).on('click','#remove_'+i,function(el){
                  
                    $(this).closest('.row').remove();                 
                })
                });
            });

                function selectRefresh() {
                    $('.selectpicker').select2({
                        tags: true,
                        placeholder: "Select an Option",
                        allowClear: true,
                        width: '100%'
                    });
                }

            </script>  --}}

            {{-- <script type="text/javascript">
    $(document).ready(function() {// set default dates
        var start = new Date();
        // set end date to max one year period:
        var end = new Date(new Date().setYear(start.getFullYear()+1));

        $('#period_by_cn').datepicker({
            startDate : start,
            endDate   : end
        // update "toDate" defaults whenever "fromDate" changes
        }).on('changeDate', function(){
            // set the "toDate" start to not be later than "fromDate" ends:
            $('#period_to_cn').datepicker('setStartDate', new Date($(this).val()));
        }); 

        $('#period_to_cn').datepicker({
            startDate : start,
            endDate   : end
        // update "fromDate" defaults whenever "toDate" changes
        }).on('changeDate', function(){
            // set the "fromDate" end to not be later than "toDate" starts:
            $('#period_by_cn').datepicker('setEndDate', new Date($(this).val()));
        });
        
        selectRefresh();
        
        let i = 0;
        $("#more_cn").click(function() {
            i++;
            // console.log($('#pd_section').html());
            var htmlCotent = `<div class="row add_row" id="row_cn_`+i+`">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Prices</label>
                                        <input type="number" step=0.01 class="form-control" name="price_cn[]">
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Products List</label>
                                        <select name="product_dropdown_cn[]" id="product_dropdown_cn`+i+`"
                                            class="form-control p-2 copied_section selectpicker_cn">
                                            <option value="">Select an option</option>
                                            @foreach ($data_cn as $datas)
                                                <option value="{{ $datas->id }}">{{ $datas->title_cn }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-2">
                                    <div class="form-group">
                                        {{-- <input class="btn btn-danger remove" id="remove_cn_`+i+`"> --}}
            {{-- <a class="btn btn-danger remove" id="remove__cn`+i+`"><i class="fa fa-trash" style="color: #fff;"></i></a>
                                    </div>
                                </div>
                            </div>`;
            $("#more_content_cn").append(htmlCotent);                        
           
              selectRefresh_cn();  
 
            

        
        $(document).on('click','#remove__cn'+i,function(el){
      
        $(this).closest('.row').remove();                 
    })
    });
});

    function selectRefresh_cn() {
        $('.selectpicker_cn').select2({
            tags: true,
            placeholder: "Select an Option",
            allowClear: true,
            width: '100%'
        });
    }

</script> --}}
            @include('common.footer');
