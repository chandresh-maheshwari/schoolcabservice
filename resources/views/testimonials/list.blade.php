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
                            @php
                            $DatbleVariable['TableHader'] = 'View Testimonial';
                            $DatbleVariable['TableId'] = 'testimonial';
                            $DatbleVariable['TableCreateRoute'] = 'vehicletypes.create';
                            $DatbleVariable['TableDeleteRoute'] = 'vehicletypes.destroy';
                            $DatbleVariable['TableRestoreRoute'] = 'vehicletypes.restore';
                            $DatbleVariable['TableColumnName'] = ['CHECKBOX', 'MAKE', 'DESCRIPTION', 'EMISSIONS', 'PPM', 'LOAD', 'TARE WEIGHT', 'FUEL', 'ACTIONS'];
                            $DatbleVariable['rightActionButton'] = ['deleteRecordButton', 'createButton', 'deleteButton'];
                        @endphp
                        <x-Datatable :tablevar=$DatbleVariable />
                    
                        <script>
                            $(document).ready(function() {
                    
                                let tableId = "#vehicle_Types_Table";
                                let route = '{{ route('vehicletypes.list') }}';
                                let method = "POST";
                                let leftActionButton = true;
                                let searching = true;
                                let deleteRoute = '{{ route('vehicletypes.destroy') }}';
                                let graphRoute = null;
                                let pagination = true;
                                let restoreRoute = '{{ route('vehicletypes.restore') }}';
                                let inActiveVal = null;
                                DatatableRenderFunction_pp(tableId, route, method, id = null,UserTypeID=null, leftActionButton, searching = true,
                                    deleteRoute, graphRoute, restoreRoute, distance = null, location, depot_id = null,
                                    company_waste_type_id = null, lenghtDropdown = true, bottomInfo = true, pagination,
                                    inActiveVal, togglefield = null);
                            });
                        </script>   
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    
    @include('common.footer');
