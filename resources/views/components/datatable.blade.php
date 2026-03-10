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
                    @php
                        $normalizeAbility = function (?string $routeOrAbility): ?string {
                            $name = trim((string) $routeOrAbility);
                            if ($name === '') {
                                return null;
                            }

                            if (str_starts_with($name, 'api.')) {
                                $name = substr($name, 4);
                            }

                            $parts = explode('.', $name);
                            if (count($parts) < 2) {
                                return $name;
                            }

                            $action = strtolower((string) $parts[count($parts) - 1]);
                            $map = [
                                'list' => 'index',
                                'multi-delete' => 'destroy',
                                'togglestatus' => 'update',
                                'update-photo' => 'update',
                                'deleteimage' => 'update',
                                'vehicleimage' => 'update',
                                'rcimage' => 'update',
                                'insuranceimage' => 'update',
                                'licenseimage' => 'update',
                                'adharcardimage' => 'update',
                                'childimage' => 'update',
                                'childadhaarimage' => 'update',
                                'aboutimage' => 'update',
                                'changepassword' => 'update',
                                'delete' => 'destroy',
                                'delete-all' => 'destroy',
                                'force-delete' => 'destroy',
                            ];

                            if (isset($map[$action])) {
                                $parts[count($parts) - 1] = $map[$action];
                            }

                            return implode('.', $parts);
                        };
                    @endphp
                    <div class="dt-buttons action_filter1" id="action_filter_{{ $tablevar['TableId'] }}">

                        @if (in_array('createButton', $tablevar['rightActionButton']))
                          @if (auth()->user()->canAccessAdminRoute($normalizeAbility($tablevar['TableCreateRoute'])))
                        <?php //echo auth()->user()->can($tablevar['TableCreateRoute']);exit;?>
                            {{-- @if (auth()->user()->can($tablevar['TableCreateRoute'])) --}}
                                <?php //echo "AAAAAAAAAAAAAAAA"; echo $tablevar['TableHader'];  exit;
                                ?>

                                    <a href="{{ route($tablevar['TableCreateRoute'], $RouteParam ?? []) }}"
                                        id="add-btn-{{ $tablevar['TableId'] }}"
                                        class="dt-add-btn btn btn-primary btn-sm" title="Add New"
                                        ><i class="fa fa-plus"></i></a>

                            @endif
                        @endif


                    </div>
                     @if ($tablevar['TableDeleteRoute'] != null)
                        @php
                            $deleteRouteName = trim((string) ($tablevar['TableDeleteRoute'] ?? ''));
                        @endphp
                        @if ($deleteRouteName !== '' && \Illuminate\Support\Facades\Route::has($deleteRouteName))
                            @if (auth()->user()->canAccessAdminRoute($normalizeAbility($deleteRouteName)))
                                @if (in_array('deleteButton', $tablevar['rightActionButton']))
                                    <a class="dt-button buttons-html5btn btn btn-primary" id="delete_record"
                                        onclick="deleteRecord('{{ route($deleteRouteName) }}', '{{ '#' . $tablevar['TableId'] }}')"
                                        href="javascript:void(0);" title="Delete Selected Data"><i
                                            class="fa fa-trash"></i></a>
                                @endif
                            @endif
                        @endif
                    @endif

                    @if ($tablevar['TableDeleteRoute'] != null)
                        @php
                            $deleteRouteName = trim((string) ($tablevar['TableDeleteRoute'] ?? ''));
                        @endphp
                        @if ($deleteRouteName !== '' && \Illuminate\Support\Facades\Route::has($deleteRouteName))
                            @if (auth()->user()->canAccessAdminRoute($normalizeAbility($deleteRouteName)))
                                @if (in_array('deleteButton', $tablevar['rightActionButton']))
                                    <a class="dt-button buttons-html5btn btn btn-primary perm_delete_record"
                                        id="perm_delete_record"
                                        onclick="permanentlyDelete(this, '{{ '#' . $tablevar['TableId'] }}' ,'{{ route($deleteRouteName) }}')"
                                        href="javascript:void(0);" title="Permanently Delete Selected Data"
                                        style="display: none"><i class="fa fa-calendar-times-o"></i></a>
                                @endif
                            @endif
                        @endif
                    @endif
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
