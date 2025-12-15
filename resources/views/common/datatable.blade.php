{{-- <div> --}}
    @if ($tablevar['TableHader'] != 'hide')
    <div class="section-title5">
        <label class="section-datatable-title">{{ $tablevar['TableHader'] }}</label>
    </div>
    @endif
    <div class="section-wrapper">
    
        <div class="row">
            {{-- @if (array_key_exists('TableHader', $tablevar))
                <div class="col-12">
                    <label class="section-title">{{ $tablevar['TableHader'] }}</label>
            <p class="mg-b-20 mg-sm-b-40"> Manage your {{ $tablevar['TableHader'] }} here.</p>
        </div>
        @endif --}}
    
        @if (!empty($tablevar['rightActionButton']))
        <div class="dt-buttons" id="action_filter1">
    
            {{-- Set Conversion Factor --}}
            @if (in_array('factorButton', $tablevar['rightActionButton']))
            <a class="dt-button buttons-html5btn btn btn-primary" href="javascript:void(0);" id="searchBtn" title="Set Conversion Factor"><i class="fa fa-cog"></i></a>
            <a class="dt-button buttons-html5btn btn btn-primary" href="javascript:void(0);" id="hideBtn" title="Hide"><i class="fa fa-minus-square"></i></a>
            @endif
    
    
    
            {{-- All users  --}}
            @if (in_array('all', $tablevar['rightActionButton']))
            <a class="dt-button buttons-html5btn btn btn-primary" id="showAllusers" href="javascript:void(0);" title="All Users"><i class="fa fa-users"></i></a>
            @endif
    
            {{-- Administor  --}}
            @if (in_array('administor', $tablevar['rightActionButton']))
            <a class="dt-button buttons-html5btn btn btn-primary userFiler" id="showAdministor" href="javascript:void(0);" title="Administor" data-id="2"><i class="fa fa-user"></i></a>
            @endif
    
            {{-- Companay  --}}
            @if (in_array('company', $tablevar['rightActionButton']))
            <a class="dt-button buttons-html5btn btn btn-primary userFiler" id="showCompany" data-id="3" href="javascript:void(0);" title="Company"><i class="fa fa-industry"></i></a>
            @endif
    
            {{-- Contract  --}}
            @if (in_array('contract', $tablevar['rightActionButton']))
            <a class="dt-button buttons-html5btn btn btn-primary userFiler" id="showContract" data-id="4" href="javascript:void(0);" title="Contract"><i class="fa fa-file"></i></a>
            @endif
    
            {{-- Depot  --}}
            @if (in_array('depot', $tablevar['rightActionButton']))
            <a class="dt-button buttons-html5btn btn btn-primary userFiler" id="showDepot" data-id="5" href="javascript:void(0);" title="Depot"><i class="fa fa-building"></i></a>
            @endif
    
            {{-- Sub - Contractor  --}}
            @if (in_array('sub-contrator', $tablevar['rightActionButton']))
            <a class="dt-button buttons-html5btn btn btn-primary userFiler" id="showSubContractor" data-id="7" href="javascript:void(0);" title="Sub-Contractor"><i class="fa fa-paste"></i></a>
            @endif
    
            {{-- Supplier  --}}
            @if (in_array('supplier', $tablevar['rightActionButton']))
            <a class="dt-button buttons-html5btn btn btn-primary userFiler" id="showSupplier" data-id="6" href="javascript:void(0);" title="Supplier"><i class="fa fa-ambulance"></i></a>
            @endif
    
    
    
            @if (in_array('inactiveButton', $tablevar['rightActionButton']))
            <a class="dt-button buttons-html5btn btn btn-primary" id="showInActiveJobBtn" href="javascript:void(0);" title="Show Inactive Records"><i class="fa fa-lock"></i></a>
            <a class="dt-button buttons-html5btn btn btn-primary" id="showActiveJobBtn" href="javascript:void(0);" title="Show Active Records"><i class="fa fa-unlock"></i></a>
            @endif
    
            @if (in_array('allrecord', $tablevar['rightActionButton']))
            <a class="dt-button buttons-html5btn btn btn-primary" id="AllRecordBtn" href="javascript:void(0);" title="Show All Records"><i class="fa fa-list"></i></a>
            @endif
    
            @if (in_array('approved', $tablevar['rightActionButton']))
            <a class="dt-button buttons-html5btn btn btn-primary" id="approved" href="javascript:void(0);" title="Show Approved Records"><i class="fa fa-check"></i></a>
            @endif
    
    
            @if (in_array('pending', $tablevar['rightActionButton']))
            <a class="dt-button buttons-html5btn btn btn-primary" id="pending" href="javascript:void(0);" title="Show Pending Records"><i class="fa fa-clock-o"></i></a>
            @endif
    
            {{-- ========================= --}}
    
            {{-- Permentally Deleted and Restore Data --}}
            @if (in_array('deleteRecordButton', $tablevar['rightActionButton']))
            <a class="dt-button buttons-html5btn btn btn-primary" id="showDeletedRecordBtn" href="javascript:void(0);" onclick="showDeletedRecord()" title="Show Deleted Records"><i class="fa fa-eye-slash"></i></a>
            @endif
    
            <!-- @if (in_array('filter_by_date', $tablevar['rightActionButton']))
            <a class="dt-button buttons-html5btn btn btn-primary" id="showLimitedRecordBtn" href="javascript:void(0);" onclick="showLimitedRecord()" title="Show Date Filtered Records"><i class="fa fa-calendar" aria-hidden="true"></i></a>
            @endif -->
    
            <a class="dt-button buttons-html5btn btn btn-primary" id="showAllRecordBtn" href="javascript:void(0);" title="Show All Records" style="display: none"><i class="fa fa-check-square-o"></i></a>
    
            {{-- {{dd($tablevar['TableDeleteRoute'])}} --}}
    
            @if ($tablevar['TableDeleteRoute'] != null)
    
            @if (in_array('deleteButton', $tablevar['rightActionButton']))
            <a class="dt-button buttons-html5btn btn btn-primary" id="delete_record" onclick="deleteRecord('{{ route($tablevar['TableDeleteRoute']) }}', '{{ '#' . $tablevar['TableId'] }}')" href="javascript:void(0);" title="Delete Selected Data"><i class="fa fa-trash"></i></a>
            @endif
            @endif
    
            @if ($tablevar['TableDeleteRoute'] != null)
            <a class="dt-button buttons-html5btn btn btn-primary perm_delete_record" id="perm_delete_record" onclick="permanentlyDelete(this, '{{ '#' . $tablevar['TableId'] }}' ,'{{ route($tablevar['TableDeleteRoute']) }}')" href="javascript:void(0);" title="Permanently Delete Selected Data" style="display: none"><i class="fa fa-calendar-times-o"></i></a>
            @endif
    
            @if ($tablevar['TableRestoreRoute'] != null)
            <a class="dt-button buttons-html5btn btn btn-primary restore_record" id="restore_record" onclick="restoreRecord(this, '{{ '#' . $tablevar['TableId'] }}' ,'{{ route($tablevar['TableRestoreRoute']) }}')" href="javascript:void(0);" title="Restore Selected Data" style="display: none"><i class="fa fa-undo"></i></a>
            @endif
    
            {{-- Add Data --}}
            @if (in_array('createButton', $tablevar['rightActionButton']))
            <a href="{{ route($tablevar['TableCreateRoute'], $RouteParam) }}" class="dt-button buttons-html5btn btn btn-primary" title="Add New {{ $tablevar['TableHader'] }}"><i class="fa fa-plus"></i></a>
            @endif
        </div>
        @endif
    
    </div>
    
    
    
    @if (in_array('factorButton', $tablevar['rightActionButton']))
    <form id="searchForm">
        <label class="section-title">Set Conversion Factor</label>
        <div class="row mb-3">
            <label class="col-sm-4 form-control-label">Waste Type Conversion Factor : </label>
            <div class="ln_solid"></div>
            <div class="col-sm-8 mg-t-10 mg-sm-t-0">
                <input type="number" class="form-control" name="waste_type_conversion_factor" id="waste_type_conversion_factor" step=any>
                <div class="ln_solid"></div>
                <button class="btn btn-primary bd-0 button_style">Set</button>
            </div>
        </div><!-- row -->
    </form>
    @endif
    
    
    @if (in_array('calculation_div', $tablevar['rightActionButton']))
    <div class="table_cal_div d-none">
        <table class="table table-condensed table-striped table-bordered jambo_table bulk_action table-hover no-margin" id="table_calculation" style="margin-top: 0px; display: table; width:100%; margin-bottom: 0px;">
            <thead class="mobile-view">
                <tr>
                    <th style="color:#000000;"><?php echo __('Total Net Vxcxcxcolume(M<span>&#179;</span>)'); ?></th>
                    <th style="color:#000000;"><?php echo __('Total Net Mass(Tonne)'); ?></th>
                    <th style="color:#000000;"><?php echo __('Total Line Cost'); ?></th>
                    <th style="color:#000000;"><?php echo __('Total Distance (Miles)'); ?></th>
                    <th style="color:#000000;"><?php echo __('Total Distance (KM)'); ?></th>
                    <th style="color:#000000;"><?php echo __('Total Recycling %'); ?></th>
                    <th style="color:#000000;" class="actions"><?php echo __('Total Tonnage Landfilled'); ?></th>
                    <th style="color:#000000;" class="actions"><?php echo __('Total Tonnage Recycled'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr id="footer_calculation_row"></tr>
            </tbody>
        </table>
    </div>
    @endif
    
    
    <div class="mt-2">
        @include('layouts.partials.messages')
    </div>
    
    <div class="table-wrapper">
        <table id={{ $tablevar['TableId'] }} class="table table-condensed table-striped table-bordered jambo_table bulk_action table-hover no-margin" width="100%" cellspacing="0">
            <thead>
                <tr>
                    @foreach ($tablevar['TableColumnName'] as $head)
                    @if ($head == 'CHECKBOX')
                    <th class="wd-15p"><input type="checkbox" class="checkall" id="checkall"></th>
                    @continue
                    @elseif(str_contains($head, 'WITH CHECKBOX'))
                    <th class="wd-15p">{{ str_replace('WITH CHECKBOX', '', $head) }}<input type="checkbox" class="extraCheck" id="extraCheck"></th>
                    @continue
                    @elseif(str_contains($head, 'CHECKBOX WITH BUTTON'))
                    <th class="wd-15p">{{ str_replace('CHECKBOX WITH BUTTON', '', $head) }}<input type="checkbox" class="extraCheck"> <i class="fa fa-save" id="extraCheck" style="font-size: 20px; cursor: pointer;"></i></th>
                    @continue
                    @endif
                    <th>{{ $head }}</th>
                    @endforeach
                </tr>
            </thead>
          
        </table>
    </div>
    <x-filterbydate />
    
    <x-pre-confirm id="deleteModal" confirm="#" msg="" type="showdeletedrecords"></x-pre-confirm>
    
    <x-pre-confirm id="single_dlt" confirm="yes_single_delete" msg="Are you sure you want to delete.? " type="preconfirm">
    </x-pre-confirm>
    <x-pre-confirm id="multi_dlt" confirm="yes_multi_delete" msg="Do you really want to delete selected records? " type="preconfirm"></x-pre-confirm>
    <x-pre-confirm id="multi_perm_dlt" confirm="yes_multi_perm_delete" msg="Do you really want to permanently delete selected records? " type="preconfirm"></x-pre-confirm>
    <x-pre-confirm id="multi_restore" confirm="yes_multi_restore" msg="Do you really want to restore selected records? " type="preconfirm"></x-pre-confirm>
    
    <x-pre-confirm id="error_multi_restore" confirm="#" msg="Please selete at least one record to delete. " type="error"></x-pre-confirm>
    <x-pre-confirm id="error_multi_delete" confirm="#" msg="Please selete at least one record to delete for this action. " type="error"></x-pre-confirm>
    <x-pre-confirm id="error_multi_perm_delete" confirm="#" msg="Please selete at least one record to delete permanently for this action. " type="error">
    </x-pre-confirm>
    
    <x-pre-confirm id="success_dlt" confirm="#" msg="Data deleted Successfully. " type="success"></x-pre-confirm>
    <x-pre-confirm id="success_restore" confirm="#" msg="Data Restore Successfully. " type="success">
    </x-pre-confirm>
    </div>