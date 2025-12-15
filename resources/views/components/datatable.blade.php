<!-- <div class="table-responsive">
    <table id="{{ $tablevar['TableId'] }}" class="table table-striped table-bordered" width="100%">
        <thead>
            <tr>
                @foreach ($tablevar['TableColumnName'] as $head)
<th>{{ $head }}</th>
@endforeach
            </tr>
        </thead>
    </table>
</div> -->

<div class="table-responsive">
    <div class="table-wrapper">
        <div class="section-wrapper">
            <div class="row">
                @if (!empty($tablevar['rightActionButton']))
                    <div class="dt-buttons action_filter1" id="action_filter_{{ $tablevar['TableId'] }}">

                        @if (in_array('createButton', $tablevar['rightActionButton']))
                        <?php //echo auth()->user()->can($tablevar['TableCreateRoute']);exit;?>
                            {{-- @if (auth()->user()->can($tablevar['TableCreateRoute'])) --}}
                                <?php //echo "AAAAAAAAAAAAAAAA"; echo $tablevar['TableHader'];  exit;
                                ?>
                               
                                    <a href="{{ route($tablevar['TableCreateRoute'], $RouteParam ?? []) }}"
                                        id="add-btn-{{ $tablevar['TableId'] }}"
                                        class="dt-add-btn btn btn-primary btn-sm" title="Add New"
                                        ><i class="fa fa-plus"></i></a>
                                
                            {{-- @endif --}}
                        @endif

                    </div>
            </div>
            @endif
            <table id={{ $tablevar['TableId'] }}
                class="table table-condensed table-striped table-bordered jambo_table bulk_action table-hover no-margin"
                width="100%" cellspacing="0">
                <thead>
                    <tr>
                        @foreach ($tablevar['TableColumnName'] as $head)
                            @if ($head == 'CHECKBOX')
                                <th class="wd-15p"><input type="checkbox" class="checkall" id="checkall"></th>
                                @continue
                            @elseif(str_contains($head, 'WITH CHECKBOX'))
                                <th class="wd-15p">{{ str_replace('WITH CHECKBOX', '', $head) }}<input type="checkbox"
                                        class="extraCheck" id="extraCheck"></th>
                                @continue
                            @elseif(str_contains($head, 'CHECKBOX WITH BUTTON'))
                                <th class="wd-15p">{{ str_replace('CHECKBOX WITH BUTTON', '', $head) }}<input
                                        type="checkbox" class="extraCheck" style="vertical-align: middle;"> <i
                                        class="fa fa-save" id="extraCheck"
                                        style="font-size: 20px; cursor: pointer; vertical-align: middle;"></i></th>
                                @continue
                            @endif
                            <th>{{ $head }}</th>
                        @endforeach

                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
