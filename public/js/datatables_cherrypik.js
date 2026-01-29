function DatatableRenderFunction(
    tableId,
    route,
    method,
    leftActionButton,
    searching = true,
    distance,
    location,
    lenghtDropdown = true,
    bottomInfo = true,
    pagination = true,
    multiDelete = false, // NEW PARAM
    deleteRoute,
    numberOfActivePost
) {
    console.log('DatatableRenderFunction called with:', {
        tableId,
        route,
        method,
        leftActionButton,
        searching,
        distance,
        location,
        lenghtDropdown,
        bottomInfo,
        pagination,
        deleteRoute,
        numberOfActivePost
    });

    let dumy = {
        table_id: tableId,
        route_name: route,
        method: method,
        distance: distance,
        location: location,
        left_button: leftActionButton,
        searching: searching,
        lenghtDropdown: lenghtDropdown,
        bottomInfo: bottomInfo,
        pagination: pagination,
        deleteRoute: deleteRoute,
        numberOfActivePost: numberOfActivePost,

    }
    console.table(dumy);

    $(tableId).DataTable({
        processing: true,
        serverSide: true,
        dom: '<"top"f>rt<"bottom"liBp><"clear">',
        searching: searching,
        oLanguage: {
            searchPlaceholder: "Search..",
            sSearch: "",
            sLengthMenu: "_MENU_",
            sInfo: "Showing _START_ to _END_ of _TOTAL_ entries",
            sInfoEmpty: "Showing 0 to 0 of 0 entries",
            sInfoFiltered: ""
        },
        serverMethod: method,
        sAjaxSource: route,
        sSearch: "",

        bLengthChange: lenghtDropdown,
        bInfo: bottomInfo,
        bPaginate: pagination,

        // lengthMenu: '_MENU_ items/page',
        lengthMenu: [
            [10, 25, 50, 100],
            [10, 25, 50, 100],
        ],

        aoColumns: getColumnNames(tableId, deleteRoute),
        iDisplayLength: 25,
        order: [[0, "desc"]],
        initComplete: function (settings, data) {
            data;
            $(".dataTables_filter input").val(data.search_text);
            var filterButton =
                '<a href="javascript:void(0);" id="search_btn" class="dt-button buttons-html5btn btn btn-primary search_btn" style="background-color: #2D336B;"><i class="fa fa-search" aria-hidden="true"></i></a>';
            var clearsearch =
                '<a class="dt-button buttons-html5btn btn btn-primary search_btn" id="searchRefresh" href="javascript:void(0);" title="Clear Search" style="background-color: #2D336B;"><i class="fa fa-refresh"></i></a>';
            var input = $(tableId + "_filter input").unbind(),
                self = this.api(),
                $searchOnEnter = input.on("keyup", function (e) {
                    if (e.keyCode == 13) {
                        /* if enter is pressed */ self.search(
                        $(this).val()
                    ).draw();
                    }
                }),
                $searchButton = $(filterButton).click(function () {
                    self.search(input.val()).draw();
                }),
                $clearButton = $(clearsearch).click(function () {
                    input.val("");
                    self.search("").draw();
                });
            $action_filter1 = $("#action_filter1")
                .addClass("d-none")
                .clone();
            $(tableId + "_filter")
                .append($searchButton, $clearButton)
                .next("label")
                .wrapAll('<div class="wrapper_actionfilter"></div>');


            $(tableId + "_filter").wrapInner(
                '<div class="wrapper_searchfilter"></div>'
            );
            $(".wrapper_actionfilter").append(
                $($action_filter1).removeClass("d-none")
            );
        },
        footerCallback: function (row, data, start, end, display) {
            var api = this.api(),
                data;
            var datalength = data.length;
            if (datalength > 0) {
                if (
                    data[datalength - 1].calcrow_bottom != "undefined" &&
                    data[datalength - 1].calcrow_bottom != ""
                ) {
                    $("tr#footer_calculation_row").html(
                        data[datalength - 1].calcrow_bottom
                    );
                }
            } else {
                $("tr#footer_calculation_row").html("");
            }
        },

        fnServerData: function (sSource, aoData, fnCallback, oSettings) {
            if (tableId === '#blogsTable') {
                aoData.push({ name: "status", value: $('#statusFilter').val() });
            }
            oSettings.jqXHR = $.ajax({
                dataType: "json",
                type: "POST",
                crossDomain: true,
                url: sSource,
                data: aoData,
                beforeSend: function (xhr) {
                },
                success: function (response) {
                    console.log(response);
                    fnCallback(response);


                },
            });
        },
        columnDefs: getDatatableColumns(tableId),
        language: {
            emptyTable: "No data found",
        },
    });

    function getColumnNames(tableId, deleteRoute) {
        var columnData;

        if (tableId == "#usersTable") {
            columnData = [
                { mDataProp: "id", name: "id" },
                { mDataProp: "photo", name: "photo" },
                { mDataProp: "first_name", name: "first_name" },
                { mDataProp: "last_name", name: "last_name" },
                { mDataProp: "mobile", name: "mobile" },
                { mDataProp: "email", name: "email" },
                { mDataProp: "status", name: "status" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#rolesTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "name", name: "name" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#permissionsTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "name", name: "name" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#heroSectionTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "image", name: "image" },
                { mDataProp: "description", name: "description" },
                { mDataProp: "Actions", name: "Actions" },
            ];

        } else if (tableId == "#vehicleTypeTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "vehicle_type", name: "vehicle_type" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#vehicleTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "vehicle_number", name: "vehicle_number" },
                { mDataProp: "vehicle_type", name: "vehicle_type" },
                { mDataProp: "rc_number", name: "rc_number" },
                { mDataProp: "insurance_number", name: "insurance_number" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#driverTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "driver_name", name: "driver_name" },
                { mDataProp: "driver_phone", name: "driver_phone" },
                { mDataProp: "license_no ", name: "license_no " },
                { mDataProp: "license_expiry_date", name: "license_expiry_date" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#schoolTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "school_name", name: "school_name" },
                { mDataProp: "school_code", name: "school_code" },
                { mDataProp: "phone", name: "phone" },
                { mDataProp: "city", name: "city" },
                { mDataProp: "state", name: "state" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#routeTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "name", name: "name" },
                { mDataProp: "vehicle_number", name: "vehicle_number" },
                { mDataProp: "driver_name", name: "driver_name" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#packageDetailTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "package_name", name: "package_name" },
                { mDataProp: "package_type", name: "package_type" },
                { mDataProp: "booking_type", name: "booking_type" },
                { mDataProp: "price", name: "price" },
                { mDataProp: "validity_days", name: "validity_days" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#bookingTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "package_type", name: "package_type" },
                { mDataProp: "booking_type", name: "booking_type" },
                { mDataProp: "latitude", name: "latitude" },
                { mDataProp: "longitude", name: "longitude" },
                { mDataProp: "contact_number", name: "contact_number" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#emergencyTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "driver_name", name: "driver_name" },
                { mDataProp: "vehicle_number", name: "vehicle_number" },
                { mDataProp: "reported_by", name: "reported_by" },
                { mDataProp: "emergency_type", name: "emergency_type" },
                { mDataProp: "contact_number", name: "contact_number" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#feedbackTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "driver_name", name: "driver_name" },
                { mDataProp: "vehicle_number", name: "vehicle_number" },
                { mDataProp: "rating", name: "rating" },
                { mDataProp: "comments", name: "comments" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#stopPickupTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "name", name: "name" },
                { mDataProp: "pickup_name", name: "pickup_name" },
                { mDataProp: "stop_name", name: "stop_name" },
                { mDataProp: "sequence_order", name: "sequence_order" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#driverHistoryTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "driver_name", name: "driver_name" },
                { mDataProp: "vehicle_number", name: "vehicle_number" },
                 { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#parentTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "father_name", name: "father_name" },
                { mDataProp: "mother_name", name: "mother_name" },
                { mDataProp: "contact_number", name: "contact_number" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#childTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "school_name", name: "school_name" },
                { mDataProp: "father_name", name: "father_name" },
                { mDataProp: "name", name: "name" },
                 { mDataProp: "gender", name: "gender" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#aboutSectionTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "name", name: "name" },
                    { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#serviceTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "icon", name: "icon" },
                { mDataProp: "name", name: "name" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if(tableId == "#howItWorksTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "name", name: "name" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if(tableId == "#clientSectionTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "name", name: "name" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if(tableId == "#benefitSectionTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "name", name: "name" },
                { mDataProp: "short_des", name: "short_des" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if(tableId == "#testimonailSectionTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "name", name: "name" },
                { mDataProp: "designation", name: "designation" },
                { mDataProp: "tagline", name: "tagline" },
                { mDataProp: "rating", name: "rating" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#faqSectionTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "question", name: "question" },
                { mDataProp: "answer", name: "answer" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if(tableId == "#priceTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "amount", name: "amount" },
                { mDataProp: "period", name: "period" },
                { mDataProp: "is_most_popular", name: "is_most_popular" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#msbAppSectionTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "icon", name: "icon" },
                { mDataProp: "name", name: "name" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#socialsMediaTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "social_name", name: "social_name" },
                { mDataProp: "social_link", name: "social_link" },
                { mDataProp: "social_icon", name: "social_icon" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#contactMessagesTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "name", name: "name" },
                { mDataProp: "email", name: "email" },
                { mDataProp: "message", name: "message" },
                { mDataProp: "company", name: "company" },
            ];
        } else if (tableId == "#stayConnectTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "name", name: "name" },
                { mDataProp: "company", name: "company" },
                { mDataProp: "email", name: "email" },
            ];
        }


        return columnData;
    }

    function getDatatableColumns(tableId) {
        let response;
        if (tableId == "#usersTable") {
            response = [
                {
                    targets: 0,
                    visible: false,
                    searchable: false,
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        if (row.photo && row.photo.trim() !== "") {
                            return `<img src="/${row.photo}?cb=${Date.now()}" alt="Image" style="width: 50px; height: 50px;"
                                    onerror="this.onerror=null; this.src='/images/person.jpg';">`;
                        } else {
                            return `<img src="/images/person.jpg" alt="Default" style="width: 50px; height: 50px;">`;
                        }
                    }
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.first_name;
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.last_name;
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.mobile;
                    },
                },
                {
                    targets: 5,
                    render: function (data, type, row, meta) {
                        return row.email;
                    },
                },
                {
                    targets: 6,
                    render: function (data, type, row, meta) {
                        if (type === 'display') {
                            const status = row.status == 1 ? 'Active' : 'Inactive';
                            const color = row.status == 1 ? 'text-success' : 'text-danger';
                            return `<span class="${color} fw-bold">${status}</span>`;
                        }
                        return data;
                    },
                },
                {
                    targets: 7,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleUserStatus" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        actionBtn += `<a href="/admin/users/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteuser" data-id="${row.id}"><i class="fas fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#rolesTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                            <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                            <span style="margin-left:8px;">${meta.row + meta.settings._iDisplayStart + 1}</span>
                        `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.name;
                    },
                },
                {
                    targets: 2,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `<a href="/admin/roles/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteRole" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#permissionsTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                            <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                            <span style="margin-left:8px;">${meta.row + meta.settings._iDisplayStart + 1}</span>
                        `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.name;
                    },
                },
                {
                    targets: 2,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `<a href="/admin/permissions/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deletePermission" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#heroSectionTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                            <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                            <span style="margin-left:8px;">${meta.row + meta.settings._iDisplayStart + 1}</span>
                        `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.title;
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        if (row.image && row.image.trim() !== "") {
                            return `<img src="/${row.image}?cb=${Date.now()}" alt="Image" style="width: 100px; height: 50px;"
              onerror="this.onerror=null; this.src='/images/Default.jpg';">`;
                        } else {
                            return `<img src="/images/Default.jpg" alt="Default" style="width: 100px; height: 50px;">`;
                        }
                    }
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        const fullText = row.description;
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = fullText;
                        const plainText = tempDiv.textContent || tempDiv.innerText || "";
                        const shortText = plainText.length > 50 ? plainText.substring(0, 50) + '...' : plainText;
                        const isExpandable = plainText.length > 50;

                        if (isExpandable) {
                            return `
                                <div class="description-wrapper" data-expanded="false">
                                    <span class="description-text">${shortText}</span>
                                    <span class="full-description" style="display:none;">${fullText}</span>
                                    <a href="javascript:void(0);" class="toggle-description" style="margin-left: 5px; color: #007bff;">Read More</a>
                                </div>
                            `;
                        } else {
                            return `<span>${shortText}</span>`;
                        }
                    }
                },
                {
                    targets: 4,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleStatus"  onclick="toggleData(this, ${row.id} , '${tableId}' , '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        actionBtn += `<a href="/admin/hero/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#vehicleTypeTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                            <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                            <span style="margin-left:8px;">${meta.row + meta.settings._iDisplayStart + 1}</span>
                        `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.vehicle_type;
                    },
                },
                {
                    targets: 2,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        actionBtn += `<a href="/admin/vehicleType/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#vehicleTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.vehicle_number ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.vehicle_type ?? '-';
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.rc_number ?? '-';
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.insurance_number ?? '-';
                    },
                },
                {
                    targets: 5,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider round"></span>
                    </label>
                `;

                        actionBtn += `
                    <a href="/admin/vehicle/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;

                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#driverTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.driver_name ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.driver_phone ?? '-';
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.license_no ?? '-';
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.license_expiry_date ?? '-';
                    },
                },
                {
                    targets: 5,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider round"></span>
                    </label>
                `;
                        actionBtn += `
                    <a href="/admin/driver/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;

                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm"
                        title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#schoolTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.school_name ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.school_code ?? '-';
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.phone ?? '-';
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.city ?? '-';
                    },
                },
                {
                    targets: 5,
                    render: function (data, type, row, meta) {
                        return row.state ?? '-';
                    },
                },
                {
                    targets: 6,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                     <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider round"></span>
                    </label>
                `;
                        actionBtn += `<a href="/admin/school/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;

                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#routeTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                            <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                            <span style="margin-left:8px;">${meta.row + meta.settings._iDisplayStart + 1}</span>
                        `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.name;
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.vehicle_number;
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.driver_name;
                    },
                },
                {
                    targets: 4,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                     <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider round"></span>
                    </label>
                `;
                        actionBtn += `<a href="/admin/routes/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;

                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#packageDetailTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.package_name ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.package_type ?? '-';
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.booking_type ?? '-';
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.price ?? '-';
                    },
                },
                {
                    targets: 5,
                    render: function (data, type, row, meta) {
                        return row.validity_days ?? '-';
                    },
                },
                {
                    targets: 6,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider round"></span>
                    </label>
                `;

                        actionBtn += `
                    <a href="/admin/packageDetails/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;

                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#bookingTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.package_type ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.booking_type ?? '-';
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.latitude ?? '-';
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.longitude ?? '-';
                    },
                },
                {
                    targets: 5,
                    render: function (data, type, row, meta) {
                        return row.contact_number ?? '-';
                    },
                },
                {
                    targets: 6,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider round"></span>
                    </label>
                `;

                        actionBtn += `
                    <a href="/admin/booking/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;

                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#emergencyTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.driver_name ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.vehicle_number ?? '-';
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.reported_by ?? '-';
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.emergency_type ?? '-';
                    },
                },
                {
                    targets: 5,
                    render: function (data, type, row, meta) {
                        return row.contact_number ?? '-';
                    },
                },
                {
                    targets: 6,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider round"></span>
                    </label>
                `;

                        actionBtn += `
                    <a href="/admin/emergency/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;

                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#feedbackTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                            <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                            <span style="margin-left:8px;">${meta.row + meta.settings._iDisplayStart + 1}</span>
                        `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.driver_name;
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.vehicle_number;
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.rating;
                    },
                },
                 {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.comments;
                    },
                },
                {
                    targets: 5,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `<a href="/admin/rating/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#stopPickupTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.name ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.pickup_name ?? '-';
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.stop_name ?? '-';
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.sequence_order ?? '-';
                    },
                },
                {
                    targets: 5,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;

                        actionBtn += `
                    <a href="/admin/stopPickup/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;

                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#driverHistoryTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.driver_name ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.vehicle_number ?? '-';
                    },
                },
                 {
                    targets:3,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },

            ];
        } else if (tableId == "#parentTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.father_name ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.mother_name ?? '-';
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.contact_number ?? '-';
                    },
                },

                {
                    targets: 4,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;

                        actionBtn += `
                    <a href="/admin/parent/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;

                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#childTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.school_name ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.father_name ?? '-';
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.name ?? '-';
                    },
                },
                 {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.gender ?? '-';
                    },
                },

                {
                    targets: 5,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;

                        actionBtn += `
                    <a href="/admin/child/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;

                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#driverHistoryTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.driver_name ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.vehicle_number ?? '-';
                    },
                },
                 {
                    targets:3,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },

            ];
        } else if (tableId == "#aboutSectionTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.title ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.name ?? '-';
                    },
                },
                {
                    targets: 3,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;

                        actionBtn += `
                    <a href="/cms/aboutSection/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;

                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#serviceTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.icon ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.name ?? '-';
                    },
                },
                {
                    targets: 3,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;

                        actionBtn += `
                    <a href="/cms/service/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;

                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#howItWorksTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.title ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.name ?? '-';
                    },
                },
                {
                    targets: 3,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;

                        actionBtn += `
                    <a href="/cms/howItWorks/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;

                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#clientSectionTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.name ?? '-';
                    },
                },
                {
                    targets: 2,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;

                        actionBtn += `
                    <a href="/cms/clientSection/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;

                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#benefitSectionTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.name ?? '-';
                    },
                },
                 {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.short_des ?? '-';
                    },
                },
                {
                    targets: 3,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;

                        actionBtn += `
                    <a href="/cms/benefitSection/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;

                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#testimonailSectionTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.name ?? '-';
                    },
                },
                 {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.designation ?? '-';
                    },
                },
                 {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.tagline ?? '-';
                    },
                },
                 {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.rating ?? '-';
                    },
                },
                {
                    targets: 5,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;

                        actionBtn += `
                    <a href="/cms/testimonialSection/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;

                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#faqSectionTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.question ?? '-';
                    },
                },
                 {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.answer ?? '-';
                    },
                },
                {
                    targets: 3,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;

                        actionBtn += `
                    <a href="/cms/faqSection/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;

                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#priceTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.title ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.amount ?? '-';
                    },
                },
                  {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.period ?? '-';
                    },
                },
                  {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.is_most_popular ?? '-';
                    },
                },
                {
                    targets: 5,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;

                        actionBtn += `
                    <a href="/cms/priceSection/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;

                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#msbAppSectionTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.icon ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.name ?? '-';
                    },
                },
                {
                    targets: 3,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;

                        actionBtn += `
                    <a href="/cms/msbAppSection/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;

                        actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#stayConnectTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
                    <input type="checkbox" class="multi-delete-checkbox" value="${row.id}">
                    <span style="margin-left:8px;">
                        ${meta.row + meta.settings._iDisplayStart + 1}
                    </span>
                `;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.name ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.company ?? '-';
                    },
                },
                 {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.email ?? '-';
                    },
                },
                // {
                //     targets: 3,
                //     orderable: false,
                //     render: function (data, type, row, meta) {
                //         let actionBtn = "";

                //         actionBtn += `
                //     <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                //          <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                //         <span class="slider"></span>
                //     </label>
                // `;

                //         actionBtn += `
                //     <a href="/cms/msbAppSection/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                //         <i class="fas fa-edit"></i>
                //     </a>
                // `;

                //         actionBtn += `
                //     <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                //         <i class="fa fa-trash"></i>
                //     </button>
                // `;

                //         return actionBtn;
                //     },
                // },
            ];
        }
        return response;
    }

    $(document).on('click', '#deleteuser', function () {
        let userId = $(this).data('id');
        $.ajax({
            url: `/api/users/${userId}/content-counts`,
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    const data = response.data;
                    Swal.fire({
                        title: 'Delete User?',
                        html: `
                            <div style="text-align: center;">
                                <div style="margin-bottom: 10px;">This user has:</div>
                                <div style="font-size: 1.1em; font-weight: 500;">
                                    <b>${data.blog_count}</b> Blogs
                                    <span style="color: #bbb; font-weight: normal;">|</span>
                                    <b>${data.quote_count}</b> Quotes
                                    <span style="color: #bbb; font-weight: normal;">|</span>
                                    <b>${data.magazine_count}</b> Magazines
                                </div>
                                <div style="margin-top: 15px; color: red; font-weight: bold;">
                                    All of this user's data will be permanently deleted.
                                </div>
                                <div>Are you sure?</div>
                            </div>
                        `,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete everything!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: `/api/users/${userId}/delete-all`,
                                type: 'DELETE',
                                success: function (res) {
                                    if (res.success) {
                                        Swal.fire('Deleted!', res.message, 'success');
                                        $('#usersTable').DataTable().ajax.reload();
                                    } else {
                                        Swal.fire('Error', res.message, 'error');
                                    }
                                },
                                error: function () {
                                    Swal.fire('Error', 'An error occurred while deleting.', 'error');
                                }
                            });
                        }
                    });
                }
            }
        });
    });

    $(document).on('click', '#deleteRole', function () {
        let roleId = $(this).data('id');
        console.log(roleId);
        swal({
            title: "Are you sure?",
            text: "Once deleted, you will not be able to recover this role!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
            .then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        url: `/api/roles/${roleId}`,
                        type: 'DELETE',
                        success: function (response) {
                            if (response.success) {
                                notify('success', 'Role deleted Successfully!');
                                $('#rolesTable').DataTable().ajax.reload();
                            } else {
                                notify('error', 'Error deleting role!');
                            }
                        },
                        error: function () {
                            notify('error', 'Error deleting role!');
                        }
                    });
                }
            });
    });

    $(document).on('click', '#deletePermission', function () {
        let permissionId = $(this).data('id');
        swal({
            title: "Are you sure?",
            text: "Once deleted, you will not be able to recover this permission!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
            .then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        url: `/api/permissions/${permissionId}`,
                        type: 'DELETE',
                        success: function (response) {
                            if (response.success) {
                                notify('success', 'Permission deleted Successfully!');
                                $('#permissionsTable').DataTable().ajax.reload();
                            } else {
                                notify('error', 'Error deleting permission!');
                            }
                        },
                        error: function () {
                            notify('error', 'Error deleting permission!');
                        }
                    });
                }
            });
    });



    $(document).on('change', '#toggleUserStatus', function (e) {
        e.preventDefault();
        let userId = $(this).data('id');
        let newStatus = $(this).is(':checked') ? 1 : 0;
        let $checkbox = $(this);

        $checkbox.prop('checked', !newStatus);
        Swal.fire({
            title: 'Activate User?',
            text: 'Are you sure you want to activate this user?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, activate!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Proceed with status update
                $.ajax({
                    url: `/api/toggle-user-status/${userId}`,
                    type: 'POST',
                    data: {
                        status: newStatus,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.success) {
                            notify('success', 'User status updated Successfully!');
                            $('#usersTable').DataTable().ajax.reload();
                        } else {
                            notify('error', 'Error updating user status!');
                        }
                    },
                    error: function () {
                        notify('error', 'Error updating user status!');
                    }
                });
            } else {
                // Revert the toggle if cancelled
                $checkbox.prop('checked', false);
            }
        });
    });

    // multi-delete button logic
    if (multiDelete) {
        $('#deleteSelectedRows').remove();
        let deleteBtn = $('<button>')
            .attr('id', 'deleteSelectedRows')
            .addClass('btn btn-danger')
            .css({ 'margin-right': '10px' })
            .prop('disabled', true)
            .attr('title', 'Delete selected records')
            .html('<i class="fas fa-trash"></i>');
        $(tableId + '_length').prepend(deleteBtn);
        let th = $(tableId + ' thead th').eq(0);
        if (th.find('.select-all-checkbox').length === 0) {
            th.prepend('<input type="checkbox" class="select-all-checkbox" style="margin-right:5px;">');
        }
    }
    $(document).off('change.multiDelete').on('change.multiDelete', '.multi-delete-checkbox', function () {
        let selected = $('.multi-delete-checkbox:checked').length;
        $('#deleteSelectedRows').prop('disabled', selected === 0);
    });
    $(document).off('change.selectAll').on('change.selectAll', '.select-all-checkbox', function () {
        $('.multi-delete-checkbox').prop('checked', $(this).is(':checked')).trigger('change');
    });
    $(document).off('click.deleteSelected').on('click.deleteSelected', '#deleteSelectedRows', function () {
        let ids = $('.multi-delete-checkbox:checked').map(function () {
            return $(this).val();
        }).get();
        if (ids.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No selection',
                text: 'Please select at least one record to delete.'
            });
            return;
        }
        Swal.fire({
            title: 'Are you sure?',
            text: 'You will not be able to recover these records!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                let apiUrl = '';
                let reloadTable = '';
                if (tableId === '#usersTable') {
                    apiUrl = '/api/users/multi-delete';
                    reloadTable = '#usersTable';
                } else if (tableId === '#rolesTable') {
                    apiUrl = '/api/roles/multi-delete';
                    reloadTable = '#rolesTable';
                } else if (tableId === '#permissionsTable') {
                    apiUrl = '/api/permissions/multi-delete';
                    reloadTable = '#permissionsTable';
                }  else if (tableId === '#vehicleTypeTable') { // Hero Section
                    apiUrl = '/api/vehicleType/multi-delete';
                    reloadTable = '#vehicleTypeTable';
                } else if (tableId === '#vehicleTable') { // Hero Section
                    apiUrl = '/api/vehicle/multi-delete';
                    reloadTable = '#vehicleTable';
                } else if (tableId === '#driverTable') { // Hero Section
                    apiUrl = '/api/driver/multi-delete';
                    reloadTable = '#driverTable';
                } else if (tableId === '#schoolTable') { // Hero Section
                    apiUrl = '/api/school/multi-delete';
                    reloadTable = '#schoolTable';
                } else if (tableId === '#routeTable') { // Hero Section
                    apiUrl = '/api/routes/multi-delete';
                    reloadTable = '#routeTable';
                } else if (tableId === '#packageDetailTable') { // Hero Section
                    apiUrl = '/api/packageDetails/multi-delete';
                    reloadTable = '#packageDetailTable';
                } else if (tableId === '#bookingTable') { // Hero Section
                    apiUrl = '/api/booking/multi-delete';
                    reloadTable = '#bookingTable';
                } else if (tableId === '#emergencyTable') { // Hero Section
                    apiUrl = '/api/emergency/multi-delete';
                    reloadTable = '#emergencyTable';
                } else if (tableId === '#feedbackTable') { // Hero Section
                    apiUrl = '/api/rating/multi-delete';
                    reloadTable = '#feedbackTable';
                } else if (tableId === '#stopPickupTable') { // Hero Section
                    apiUrl = '/api/stopPickup/multi-delete';
                    reloadTable = '#stopPickupTable';
                } else if (tableId === '#driverHistoryTable') { // Hero Section
                    apiUrl = '/api/driverHistory/multi-delete';
                    reloadTable = '#driverHistoryTable';
                } else if (tableId === '#parentTable') { // Hero Section
                    apiUrl = '/api/parent/multi-delete';
                    reloadTable = '#parentTable';
                } else if (tableId === '#childTable') { // Hero Section
                    apiUrl = '/api/child/multi-delete';
                    reloadTable = '#childTable';
                } else if (tableId === '#aboutSectionTable') { // Hero Section
                    apiUrl = '/api/aboutSection/multi-delete';
                    reloadTable = '#aboutSectionTable';
                } else if (tableId === '#serviceTable') { // Hero Section
                    apiUrl = '/api/service/multi-delete';
                    reloadTable = '#serviceTable';
                }
                else {
                    apiUrl = '/api' + tableId.replace('#', '/').replace('Table', '') + '/multi-delete';
                    reloadTable = tableId;
                }
                $.ajax({
                    url: apiUrl,
                    type: 'POST',
                    data: {
                        ids: ids,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.success) {
                            notify('success', 'Selected Records Deleted Successfully!');
                            $(reloadTable).DataTable().ajax.reload();
                            $('#deleteSelectedRows').prop('disabled', true);
                            $('.select-all-checkbox').prop('checked', false);
                        } else {
                            notify('error', 'Error deleting selected records!');
                        }
                    },
                    error: function () {
                        notify('error', 'Error deleting selected records!');
                    }
                });
            }
        });
    });
}

// Function for the active inactive the status of the field
function toggleData(current, dis, tableId, statusRoute, numberOfActive) {


    let newStatus = current.checked;
    const table = $(tableId).DataTable();

    if (newStatus) {
        $.ajax({
            url: `/api/${statusRoute}/active-count`,
            type: 'GET',
            success: function (response) {
                if (response.count >= numberOfActive) {
                    current.checked = false;
                    notify('error', 'Only ' + numberOfActive + ' entries can be active at a time.');
                } else {
                    updateStatus(current, dis, table, statusRoute);
                }
            },
            error: function () {
                notify('error', 'Error checking active entries.');
                current.checked = false;
            }
        });
    } else {
        updateStatus(current, dis, table, statusRoute);
    }
}

function updateStatus(current, dis, table, statusRoute) {
    let newStatus = current.checked;
    $.ajax({
        // url: `/api/${statusRoute}/${dis}/toggle-status`,
        url: `/api/${statusRoute}/${encodeURIComponent(dis)}/toggle-status`,
        type: 'POST',
        data: {
            status: newStatus,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.success) {
                notify('success', 'Status Updated Successfully!');
                table.ajax.reload();
            } else {
                notify('error', 'Error updating status!');
                // Revert checkbox on failure
                current.checked = !newStatus;
            }
        },
        error: function () {
            notify('error', 'Error updating status!');
            // Revert checkbox on error
            current.checked = !newStatus;
        }
    });
}

// COMMON CODE FOR DELETE
function deleteData(dis, tableId, deleteRoute) {
    let del_id = dis.getAttribute("data-id");
    swal({
        title: "Are you sure?",
        text: "Once deleted, you will not be able to recover this Data!",
        icon: "warning",
        buttons: true,
        dangerMode: true,
    })
        .then((willDelete) => {
            if (willDelete) {
                $.ajax({
                    url: `/api/${deleteRoute}/${del_id}`,
                    type: 'DELETE',
                    success: function (response) {
                        if (response.success) {
                            notify('success', 'Data Deleted Successfully!');
                            $(tableId).DataTable().ajax.reload();
                        } else {
                            notify('error', 'Error deleting Data!');
                        }
                    },
                    error: function () {
                        notify('error', 'Error deleting Data!');
                    }
                });
            }
        });
    // });
}

