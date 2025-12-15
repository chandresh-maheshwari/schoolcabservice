@include('common.header');
@include('common.sidebar');

<body class="">
    <div class="wrapper">

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
                                    <h4 class="card-title"> Testimonial List</h4>
                                </div>
                                {{-- <div class="pull-right pr-3">
                                    <a href="/promocode_datatable">English</a> | <a href="/promocode_datatable/cn"> 中文
                                        (香港)</a>
                                </div> --}}
                                <div class="card-body">
                                    <div class="table" id="hi">
                                        {{-- <table class="table" id="#testionmonialTable"> --}}
                                        <table id="testimonial_datatable" class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Sr.No</th>
                                                    <th>Testimonial</th>
                                                    <th>Added by</th>
                                                    <th>Position</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>

                                            </tbody>
                                        </table>


                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                url: "{{ route('testionmonial.show') }}",
                type: 'GET',
                dataType: "JSON",
                success: function(response) {

                    // console.log(response);
                    var table = $('#testimonial_datatable').DataTable({
                        // processing: true,
                        // serverside: true,
                        data: response,
                        columns: [{
                                title: 'SR.no',
                                data: 'id'
                            },
                            {
                                title: 'Testimonial',
                                data: function(data) {
                                    return data.testimonial;
                                }
                            },
                            {
                                title: 'Add_by',
                                data: 'add_by'
                            },
                            {

                                title: 'Position',
                                data: 'position'
                            },
                            {
                                title: 'Action',
                                // data: 'action',
                                data: 'id',
                                orderable: false,
                                render: function(data, type, row) {
                                    return '<div class="btn-group">' +
                                        `<a href="/edit/${row.id}/" class="btn btn-primary mr-2" data-toggle="tooltip"  title="Edit" title="Edit" id="edit"><i class="far fa-edit"></i></a>` +
                                        '<button class="btn btn-danger"  data-id="' +
                                        row.id +
                                        '" data-toggle="tooltip" data-placement="top" title="Delete" id="delete"><i class="far fa-trash-alt"></i></button>' +
                                        //   `<a href="/edit/${row.id}/" class="btn btn-primary mr-2" data-toggle="tooltip"  title="Edit" title="Edit" id="edit"><i class="far fa-edit"></i></a> ` 
                                        '</div>';

                                }
                            },
                        ]
                    })

                },
                error: function(error) {

                }
            });



            // $('body').on('click', '#delete', function() {
            //     // Show a confirmation alert
            //     var id = $(this).data("id");

            //     $.ajax({
            //         url: "{{ route('testionmonial.destroy') }}" + '/' + id,
            //         type: 'DELETE',
            //         data: {
            //             id: id,
            //         },
            //         success: function(response) {
            //             // If the Ajax call was successful, show a success message
            //             // console.log(response);

            //             // alert(response.code);
            //             if (response.code == 1) {
            //                 swal({
            //                     title: "Testimonial has been deleted",
            //                     text: "",
            //                     type: "success",
            //                 }).then(function() {
            //                     window.location.reload();
            //                 });


            //                 setTimeout(function() {
            //                     window.location.reload();
            //                 }, 5000);
            //             } else {
            //                 swal({
            //                     title: "Please try again",
            //                     text: "",
            //                     type: "error",
            //                 });
            //             }
            //         },
            //         error: function(error) {
            //             // If the Ajax call failed, show an error message

            //         },
            //     });


            // });

            // });

// ----------------------------------------------------------------------------------------------------
            $('body').on('click', '#delete', function() {
                // Show a confirmation alert
                var id = $(this).data("id");
                // alert(id);

                // return false;

                swal({
                        title: 'Are you sure?',
                        text: 'This will delete the testimonial permanently.',
                        icon: 'warning',
                        buttons: ['Cancel', 'Delete'],
                    })
                    .then((willDelete) => {
                        if (willDelete) {
                            // Make the Ajax call to delete the testimonial
                            $.ajax({
                                url: "{{ route('testionmonial.destroy') }}" + '/' + id,
                                type: 'DELETE',
                                data: {
                                    id: id,
                                },
                                success: function(response) {
                                    swal('Testimonial deleted Successfully!', '',
                                    'success');
                        if (response.code == 1) {
                         
                            setTimeout(function() {
                               window.location.reload();
                            }, 2000);
                        } 
                    },
                               
                            });
                        }
                    });
            });

        });
    </script>
    @include('common.footer');
