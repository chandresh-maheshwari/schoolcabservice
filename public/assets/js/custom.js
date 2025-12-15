

function DatatableRenderFunction_ss(tableId, id = null, bottomInfo = true, pagination = false, route, method, location, deleteRoute) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    let dumy = {

        table_id: tableId,
        bottomInfo: bottomInfo,
        pagination: pagination,
        route_name: route,
        mehtod: method,
        deleteRoute: deleteRoute,
        id: id,
        location: location,
    }

    console.table(dumy);

    if ($.fn.dataTable.isDataTable(tableId)) {
        $(tableId).dataTable()
            .destroy();
    }

    $(tableId).dataTable({
        processing: false,
        serverSide: true,
        searching: true,
        serverMethod: method,
        sAjaxSource: route,
        sSearch: '',
        lengthMenu: [
            [5, 10, 25, 50, 100],
            [5, 10, 25, 50, 100],
        ],
        bottomInfo: true,
        pagination: false,
        aoColumns: getColumnNames(tableId),
        iDisplayLength: 5,
        order: [
            [0, 'desc']
        ],
        fnServerData: function (sSource, aoData, fnCallback, oSettings) {
            aoData.push({
                // name: "token",
                // value:$("#api_token").html(),
            },
                // {
                //     name: "id",
                //     value: id,
                // },
                {
                    name: "location",
                    value: location,
                },

            );
            oSettings.jqXHR = $.ajax({
                dataType: "json",
                type: "POST",
                crossDomain: true,
                url: sSource,
                data: aoData,
                success: function (response) {
                    // $('#loader_modal').modal('hide');
                    fnCallback(response);

                    if (tableId) {
                        let grnLabel = [];
                        let bulkUploaded = [];
                        $.each(response.aaData, function (index, value) {
                            grnLabel.push(value.grn_3rd_party);
                            if (value.date_csv_imported != null) {
                                bulkUploaded.push(value.date_csv_imported);
                            }
                        });

                        if (bulkUploaded.length > 0) {
                            if (!$('.grnFilterContentBox .row').find('#btnUnGrnupload').length) {
                                $('.grnFilterContentBox .row').append(`<a class="btn btn-sm ml-2 btn-round btn-info text-white" id="btnUnGrnupload" data-bs-toggle="modal" data-bs-target="#UndoUploadWbtPopup">Undo Grn</a>`);
                                var uniqueArray = [...new Set(bulkUploaded)];
                                uniqueArray.forEach(element => {
                                    $('#date_csv_imported').append(`<option value="${element}">${element}</option>`)
                                });
                            }
                        }

                        function findDuplicates(grnLabel) {
                            var duplicates = [];
                            var count = {};
                            for (var i = 0; i < grnLabel.length; i++) {
                                var item = grnLabel[i];
                                count[item] = count[item] ? count[item] + 1 : 1;
                            }
                            for (var key in count) {
                                if (count[key] > 1) {
                                    duplicates.push(key);
                                }
                            }
                            return duplicates;
                        }
                        var duplicateValues = findDuplicates(grnLabel);
                        if (duplicateValues.length > 0) {

                            if (!$('#grnTable_wrapper .bottom').find('p').length) {
                                $('#grnTable_wrapper .bottom').prepend('<p class="d-inline">There Are Duplicate GRNS Numbers</p>');
                            }
                        }
                        if (!$('#grnTable_wrapper .bottom').find('#RemeberLabel').length) {
                            $('#grnTable_wrapper .bottom').append('<span class="ml-5" id="RemeberLabel">Remember Previous GRN Data</span> <input type="checkbox" name="" id="grnsave" label="" data-toggle="checkbox" class="" checked="checked" value="1">');
                        }
                    } else if (tableId == '#searchMTNTable') {
                        $.each(response.aaData, function (index, value) {
                            $('#total_mass_title').text(value.total_mass_title);
                            $('#total_vol_title').text(value.total_vol_title);
                        });

                        let calculatedRow;
                        if (response.calculatedRow.grn_net_vol) {
                            calculatedRow += `<td>${response.calculatedRow.grn_net_vol.toFixed(2)}</td>`;
                        } else {
                            calculatedRow += `<td>&nbsp;</td>`;
                        }
                        if (response.calculatedRow.grn_net_mass) {
                            calculatedRow += `<td>${response.calculatedRow.grn_net_mass.toFixed(2)}</td>`;
                        } else {
                            calculatedRow += `<td>&nbsp;</td>`;
                        }
                        if (response.calculatedRow.line_cost) {
                            calculatedRow += `<td>${response.calculatedRow.line_cost.toFixed(2)}</td>`;
                        } else {
                            calculatedRow += `<td>&nbsp;</td>`;
                        }
                        if (response.calculatedRow.grn_distance_miles) {
                            calculatedRow += `<td>${response.calculatedRow.grn_distance_miles.toFixed(2)}</td>`;
                        } else {
                            calculatedRow += `<td>&nbsp;</td>`;
                        }
                        if (response.calculatedRow.grn_distance_km) {
                            calculatedRow += `<td> ${response.calculatedRow.grn_distance_km.toFixed(2)}</td>`;
                        } else {
                            calculatedRow += `<td>&nbsp;</td>`;
                        }
                        // $calculatedRow .= '<td>' . $all_percentage / $recycling_percentage * 100 . '</td>';
                        if (response.calculatedRow.recycling_percentage) {
                            calculatedRow += `<td> ${(response.calculatedRow.recycling_percentage / parseInt(response.iTotalRecords)).toFixed(2)}</td>`;
                        } else {
                            calculatedRow += `<td>&nbsp;</td>`;
                        }
                        if (response.calculatedRow.total_landfill) {
                            calculatedRow += `<td> ${response.calculatedRow.total_landfill.toFixed(2)}</td>`;
                        } else {
                            calculatedRow += `<td>&nbsp;</td>`;
                        }
                        if (response.calculatedRow.total_recycled_tonnage) {
                            calculatedRow += `<td> ${response.calculatedRow.total_recycled_tonnage.toFixed(2)}</td>`;
                        } else {
                            calculatedRow += `<td>&nbsp;</td>`;
                        }
                        $('#footer_calculation_row').html(calculatedRow);
                        setTimeout(() => {
                            var calculatedHtml = $('.table_cal_div').html();
                            $('#table_calculation').html('');
                            $(calculatedHtml).insertBefore("table#searchMTNTable");
                        }, 500);
                    }
                },
            });
        },
        columnDefs: getDatatableColumns(tableId),
        language: {
            emptyTable: "No data found",
        },
    });

    function getColumnNames(tableId) {
        var columnData;

        if (tableId == "#oooo") {
            columnData = [{
                mDataProp: 'checkbox',
                name: 'checkbox',
            },
            {
                mDataProp: 'id',
                name: 'id',
            },
            {
                mDataProp: "partner_name",
                name: "partner_name",
            },
            {
                mDataProp: "partner_logo",
                name: "partner_logo",
            },
            {
                mDataProp: "action",
                name: "action",
            },
            ];
        }
       else if (tableId == "#services") {
            columnData = [{
                mDataProp: 'checkbox',
                name: 'checkbox',
            },
            {
                mDataProp: 'id',
                name: 'id',
            },
            {
                mDataProp: "service_title",
                name: "service_title",
            },
            {
                mDataProp: "description",
                name: "description",
            },
            {
                mDataProp: "action",
                name: "action",
            },
            ];
        }
        else if (tableId == "#package") {
            columnData = [{
                mDataProp: 'checkbox',
                name: 'checkbox',
            },
            {
                mDataProp: 'id',
                name: 'id',
            },
            {
                mDataProp: "package_title",
                name: "package_title",
            },
            {
                mDataProp: "package_des",
                name: "package_des",
            },
            {
                mDataProp: "package_price",
                name: "package_price",
            },
            {
                mDataProp: "additional_info",
                name: "additional_info",
            },
            {
                mDataProp: "action",
                name: "action",
            },
            ];
        }
        return columnData;
    }


    // To get Database table columns Data
    function getDatatableColumns(tableId) {
        let response;

        if (tableId == '#oooo') {


            response = [
                {
                    targets: 0,
                    orderable: false,
                    width: 10,
                    render: function (data, type, row, meta) {
                        let selectBtn = `<input type="checkbox" class="delete_check" id="delcheck_${row.id}" value="${row.id}">`;
                        return selectBtn;
                    },
                },
                {
                    targets: 1,
                    width: 20,
                    render: function (data, type, row, meta) {
                        return row.id;

                    },
                },
                {
                    targets: 2,
                    width: 40,
                    render: function (data, type, row, meta) {
                        return row.partner_name;
                    },
                },
                {
                    targets: 3,
                    width: 20,
                    render: function (data, type, row, meta) {
                        var id = row.id;
                        return '<img src="/images/client/' + row.partner_logo + '" style="width:30%;">';
                    },
                },
                {
                    targets: 4,
                    width: 20,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn +=
                            `<a href="/cedit/${row.id}/" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit"><i class="fa fa-edit"></i></a> `;
                        actionBtn +=
                            // `<a href="/roles/${row.id}" class="btn btn-oblong btn-primary btn-sm" ><i class="fa fa-trash"></i></a> `;
                            `<button class="btn btn-oblong btn-danger btn-sm "  title="Delete" id="delete" onclick="removeButton(this , '${tableId}' , '${deleteRoute}')" title="Delete" id="delete" data-id=${row.id}><i class="fa fa-trash"></i></button>`;


                        return actionBtn;
                    },
                },
            ];

            // console.log(response);

        }
       else if (tableId == '#services') {


            response = [
                {
                    targets: 0,
                    orderable: false,
                    width: 10,
                    render: function (data, type, row, meta) {
                        let selectBtn = `<input type="checkbox" class="delete_check" id="delcheck_${row.id}" value="${row.id}">`;
                        return selectBtn;
                    },
                },
                {
                    targets: 1,
                    width: 20,
                    render: function (data, type, row, meta) {
                        return row.id;

                    },
                },
                {
                    targets: 2,
                    width: 40,
                    render: function (data, type, row, meta) {
                        return row.service_title;
                    },
                },
                {
                    targets: 3,
                    width: 20,
                    render: function (data, type, row, meta) {
                        return row.description;
                    },
                },
                {
                    targets: 4,
                    width: 20,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn +=
                            `<a href="/service_edit/${row.id}/" class="btn btn-oblong btn-primary btn-sm " id="edit" title="Edit"><i class="fa fa-edit"></i></a> `;
                        actionBtn +=
                            // `<a href="/roles/${row.id}" class="btn btn-oblong btn-primary btn-sm" ><i class="fa fa-trash"></i></a> `;
                            `<button class="btn btn-oblong btn-danger btn-sm "  title="Delete" id="delete" onclick="removeButton(this , '${tableId}' , '${deleteRoute}')" title="Delete" id="delete" data-id=${row.id}><i class="fa fa-trash"></i></button>`;


                        return actionBtn;
                    },
                },
            ];

            // console.log(response);

        }

        else if (tableId == '#package') {


            response = [
                {
                    targets: 0,
                    orderable: false,
                    width: 10,
                    render: function (data, type, row, meta) {
                        let selectBtn = `<input type="checkbox" class="delete_check" id="delcheck_${row.id}" value="${row.id}">`;
                        return selectBtn;
                    },
                },
                {
                    targets: 1,
                    width: 20,
                    render: function (data, type, row, meta) {
                        return row.id;

                    },
                },
                {
                    targets: 2,
                    width: 40,
                    render: function (data, type, row, meta) {
                        return row.package_title;
                    },
                },
                {
                    targets: 3,
                    width: 20,
                    render: function (data, type, row, meta) {
                        return row.package_des;
                    },
                },
                {
                    targets: 4,
                    width: 20,
                    render: function (data, type, row, meta) {
                        return row.package_price;
                    },
                },
                {
                    targets: 5,
                    width: 20,
                    render: function (data, type, row, meta) {
                        return row.additional_info;
                    },
                },
                {
                    targets: 6,
                    width: 20,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn +=
                            `<a href="/package_edit/${row.id}/" class="btn btn-oblong btn-primary btn-sm " id="edit" title="Edit"><i class="fa fa-edit"></i></a> `;
                        actionBtn +=
                            // `<a href="/roles/${row.id}" class="btn btn-oblong btn-primary btn-sm" ><i class="fa fa-trash"></i></a> `;
                            `<button class="btn btn-oblong btn-danger btn-sm "  title="Delete" id="delete" onclick="removeButton(this , '${tableId}' , '${deleteRoute}')" title="Delete" id="delete" data-id=${row.id}><i class="fa fa-trash"></i></button>`;


                        return actionBtn;
                    },
                },
            ];

            // console.log(response);

        }


        return response;
    }
}
$(document).on('click', '#checkall', function () {
    if ($(this).is(':checked')) {
        $('.delete_check').prop('checked', true);
    } else {
        $('.delete_check').prop('checked', false);
    }
});
function removeButton(dis, tableId, deleteRoute) {

    // $('#Comfrim_modal').modal('show');
    // $('#confirm_message').text('Are you sure want to delete ?');
    // alert("fsdfdsfsd");

    let del_id = dis.getAttribute('data-id');

    // alert(del_id);
    swal({
        title: "Are you sure?",
        text: "Do you really want to delete this record?",
        icon: "warning",
        buttons: true,
        dangerMode: true,
    }).then((willDelete) => {
        if (willDelete)     {
            $.ajax({
                type: "POST",
                url: deleteRoute,
                data: {
                    id: del_id,
                    token: $("#api_token").html(),
                },
                dataType: "json",
                success: function (response) {
                   var message = response.message;
                    // alert(message);
                    if (response.code == '200') {
                        swal(message);
                        // $(del_id).closest("tr").fadeOut();
                        // $(tableId).DataTable().ajax.reload(null,false);
                    }
                    setTimeout(function () {
                        window.location.reload();
                    }, 2000);
                }
            });
        }
    });
}

function deleteRecord(deleteRoute, tableId) {
    var deleteids_arr = [];
    $("input:checkbox[class=delete_check]:checked").each(function () {
    deleteids_arr.push($(this).val());
    });
    
    if (deleteids_arr.length > 0) {
        swal({
            title: "Are you sure?",
            text: "You are about to delete multiple records.",
            icon: "warning",
            buttons: ["Cancel", "Delete"],
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                $(document).off('click').on('click',function (e) {
    
                    $.ajax({
                        url: deleteRoute,
                        type: 'POST',
                        data: {
                            delete_type: 'multi',
                            deleteids_arr: deleteids_arr,
                            token: $("#api_token").html(),
                        },
                        // beforeSend: function () {
                        //     $(tableId).DataTable().ajax.reload();
                        // },
                        success: function (response) {
                            var message = response.status;
                            if (response) {
                                $("#checkall").prop("checked", false);

                                if(message == 'success') {
                                    var message = response.message;
                                swal(message, '',
                                'success');
                            }
                                setTimeout(function () {
                                    window.location.reload();
                                }, 2000);
                            } else {
                                // $(tableId).DataTable().ajax.reload();
                            }
                        }
                    });
                });
            }
        });
    } else {
        // $('#Error_modal').modal('show');
        // $('#Error_modal #error_message').text('Please selete at least one record to delete for this action.');
    }
    }