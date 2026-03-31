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
    // If a school user is logged in, admin panel routes should be slug-prefixed.
    const schoolSlugMeta = document.querySelector('meta[name="school-slug"]');
    const schoolSlug = (schoolSlugMeta && schoolSlugMeta.getAttribute('content')) ? schoolSlugMeta.getAttribute('content').trim() : '';
    const panelBase = schoolSlug ? `/${schoolSlug}` : '/admin';

    window.loginAsSchool = function (schoolId) {
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : null;

        $.ajax({
            url: `${panelBase}/school/${schoolId}/login-as`,
            type: 'POST',
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
            success: function (response) {
                if (response && response.redirect_url) {
                    window.location.href = response.redirect_url;
                    return;
                }
                window.location.reload();
            },
            error: function () {
                alert('Unable to login to school.');
            },
        });
    };

    const canRoute = (routeName) => {
        if (typeof window !== 'undefined' && typeof window.__canRoute === 'function') {
            return !!window.__canRoute(routeName);
        }
        return true;
    };

    const permissionModuleAliases = {
        driverHistory: 'driverHistoryList',
    };

    const getPermissionModule = (moduleName) => {
        if (typeof moduleName !== 'string') return '';
        const trimmed = moduleName.trim();
        if (!trimmed) return '';
        return permissionModuleAliases[trimmed] || trimmed;
    };

    const canModuleAction = (action, moduleName = deleteRoute) => {
        const module = getPermissionModule(moduleName);
        if (!module) return false;
        return canRoute(`${module}.${action}`);
    };

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
            const $tableFilter = $(tableId + "_filter");
            const $tableInput = $tableFilter.find("input");
            const tableFilterId = tableId.replace("#", "");
            const $actionFilter = $("#action_filter_" + tableFilterId).length
                ? $("#action_filter_" + tableFilterId)
                : $("#action_filter1");

            $tableInput.val(data.search_text || "");
            var filterButton =
                '<a href="javascript:void(0);" id="search_btn" class="dt-button buttons-html5btn btn btn-primary search_btn" style="background-color: #2D336B;"><i class="fa fa-search" aria-hidden="true"></i></a>';
            var clearsearch =
                '<a class="dt-button buttons-html5btn btn btn-primary search_btn" id="searchRefresh" href="javascript:void(0);" title="Clear Search" style="background-color: #2D336B;"><i class="fa fa-refresh"></i></a>';
            var input = $tableInput.unbind(),
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
            const $actionFilterClone = $actionFilter.length
                ? $actionFilter.addClass("d-none").clone().removeClass("d-none")
                : $();

            $tableFilter.find("#search_btn, #searchRefresh").remove();
            $tableFilter.append($searchButton, $clearButton);

            if (!$tableFilter.find(".wrapper_searchfilter").length) {
                $tableFilter.wrapInner('<div class="wrapper_searchfilter"></div>');
            }

            let $wrapperActionFilter = $tableFilter.prev(".wrapper_actionfilter");
            if (!$wrapperActionFilter.length) {
                $wrapperActionFilter = $('<div class="wrapper_actionfilter"></div>');
                $tableFilter.before($wrapperActionFilter);
            }

            $wrapperActionFilter.empty();
            if ($actionFilterClone.length) {
                $wrapperActionFilter.append($actionFilterClone);
            }
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
                { mDataProp: "checkbox", name: "checkbox" },
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
                { mDataProp: "school_name", name: "school_name" },
                { mDataProp: "vehicle_type", name: "vehicle_type" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#vehicleTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "school_name", name: "school_name" },
                { mDataProp: "vehicle_number", name: "vehicle_number" },
                { mDataProp: "vehicle_type", name: "vehicle_type" },
                { mDataProp: "rc_number", name: "rc_number" },
                { mDataProp: "insurance_number", name: "insurance_number" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#driverTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "school_name", name: "school_name" },
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
        } else if (tableId == "#schoolTrashTable") {
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
                { mDataProp: "school_name", name: "school_name" },
                { mDataProp: "name", name: "name" },
                { mDataProp: "vehicle_number", name: "vehicle_number" },
                { mDataProp: "driver_name", name: "driver_name" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#packageDetailTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "school_name", name: "school_name" },
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
                { mDataProp: "school_name", name: "school_name" },
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
                { mDataProp: "school_name", name: "school_name" },
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
                { mDataProp: "school_name", name: "school_name" },
                { mDataProp: "driver_name", name: "driver_name" },
                { mDataProp: "vehicle_number", name: "vehicle_number" },
                { mDataProp: "rating", name: "rating" },
                { mDataProp: "comments", name: "comments" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#stopPickupTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "school_name", name: "school_name" },
                { mDataProp: "name", name: "name" },
                { mDataProp: "pickup_name", name: "pickup_name" },
                { mDataProp: "stop_name", name: "stop_name" },
                { mDataProp: "sequence_order", name: "sequence_order" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#driverHistoryTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "school_name", name: "school_name" },
                { mDataProp: "driver_name", name: "driver_name" },
                { mDataProp: "vehicle_number", name: "vehicle_number" },
                 { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#parentTable") {
            columnData = schoolSlug
                ? [
                    { mDataProp: "checkbox", name: "checkbox" },
                    { mDataProp: "father_name", name: "father_name" },
                    { mDataProp: "mother_name", name: "mother_name" },
                    { mDataProp: "children_names", name: "children_names" },
                    { mDataProp: "contact_number", name: "contact_number" },
                    { mDataProp: "Actions", name: "Actions" },
                ]
                : [
                    { mDataProp: "checkbox", name: "checkbox" },
                    { mDataProp: "school_name", name: "school_name" },
                    { mDataProp: "father_name", name: "father_name" },
                    { mDataProp: "mother_name", name: "mother_name" },
                    { mDataProp: "children_names", name: "children_names" },
                    { mDataProp: "contact_number", name: "contact_number" },
                    { mDataProp: "Actions", name: "Actions" },
                ];
        } else if (tableId == "#childTable") {
            columnData = schoolSlug
                ? [
                    { mDataProp: "checkbox", name: "checkbox" },
                    { mDataProp: "child_name", name: "child_name" },
                    { mDataProp: "father_name", name: "father_name" },
                    { mDataProp: "name", name: "name" },
                    { mDataProp: "gender", name: "gender" },
                    { mDataProp: "Actions", name: "Actions" },
                ]
                : [
                    { mDataProp: "checkbox", name: "checkbox" },
                    { mDataProp: "school_name", name: "school_name" },
                    { mDataProp: "child_name", name: "child_name" },
                    { mDataProp: "father_name", name: "father_name" },
                    { mDataProp: "name", name: "name" },
                    { mDataProp: "gender", name: "gender" },
                    { mDataProp: "Actions", name: "Actions" },
                ];
        } else if (tableId == "#leaveRequestsTable") {
            columnData = schoolSlug
                ? [
                    { mDataProp: "checkbox", name: "checkbox" },
                    { mDataProp: "child_name", name: "child_name" },
                    { mDataProp: "parent_name", name: "parent_name" },
                    { mDataProp: "reason", name: "reason" },
                    { mDataProp: "from_date", name: "from_date" },
                    { mDataProp: "to_date", name: "to_date" },
                    { mDataProp: "submitted_at", name: "submitted_at" },
                    { mDataProp: "Actions", name: "Actions" },
                ]
                : [
                    { mDataProp: "checkbox", name: "checkbox" },
                    { mDataProp: "school_name", name: "school_name" },
                    { mDataProp: "child_name", name: "child_name" },
                    { mDataProp: "parent_name", name: "parent_name" },
                    { mDataProp: "reason", name: "reason" },
                    { mDataProp: "from_date", name: "from_date" },
                    { mDataProp: "to_date", name: "to_date" },
                    { mDataProp: "submitted_at", name: "submitted_at" },
                    { mDataProp: "Actions", name: "Actions" },
                ];
        } else if (tableId == "#pushNotificationsTable") {
            columnData = [
                { mDataProp: "id", name: "id" },
                { mDataProp: "recipient", name: "recipient" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "message", name: "message" },
                { mDataProp: "type", name: "type" },
                { mDataProp: "created_at_value", name: "created_at_value" },
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
        }
        return columnData;
    }

    function getDatatableColumns(tableId) {
        let response;
        if (tableId == "#usersTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    searchable: false,
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
                        if (row.photo && row.photo.trim() !== "") {
                            let photoPath = row.photo.trim().replace(/^\/+/, "");
                            if (!photoPath.startsWith("storage/")) {
                                photoPath = `storage/${photoPath}`;
                            }
                            return `<img src="/${photoPath}?cb=${Date.now()}" alt="Image" style="width: 50px; height: 50px;"
                                    onerror="this.onerror=null; this.src='/storage/profile_pictures/default-user.svg';">`;
                        } else {
                            return `<img src="/storage/profile_pictures/default-user.svg" alt="Default" style="width: 50px; height: 50px;">`;
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
                        if (canModuleAction('update')) {
                            actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleUserStatus" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        }
                        if (canModuleAction('edit')) {
                            actionBtn += `<a href="${panelBase}/users/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        }
                        if (canModuleAction('destroy')) {
                            actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteuser" data-id="${row.id}"><i class="fas fa-trash"></i></button>`;
                        }
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
                        if (canModuleAction('edit')) {
                            actionBtn += `<a href="${panelBase}/roles/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        }
                        if (canModuleAction('destroy')) {
                            actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteRole" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        }
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
                        if (canModuleAction('edit')) {
                            actionBtn += `<a href="${panelBase}/permissions/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        }
                        if (canModuleAction('destroy')) {
                            actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deletePermission" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        }
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
                        if (canModuleAction('update')) {
                            actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleStatus"  onclick="toggleData(this, ${row.id} , '${tableId}' , '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        }
                        if (canModuleAction('edit')) {
                            actionBtn += `<a href="${panelBase}/hero/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        }
                        if (canModuleAction('destroy')) {
                            actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        }
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
                        return row.school_name ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.vehicle_type;
                    },
                },
                {
                    targets: 3,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        if (canModuleAction('update')) {
                            actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        }
                        if (canModuleAction('edit')) {
                            actionBtn += `<a href="${panelBase}/vehicleType/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        }
                        if (canModuleAction('destroy')) {
                            actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        }
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
                        return row.school_name ?? '-';
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
                        return row.vehicle_type ?? '-';
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.rc_number ?? '-';
                    },
                },
                {
                    targets: 5,
                    render: function (data, type, row, meta) {
                        return row.insurance_number ?? '-';
                    },
                },
                {
                    targets: 6,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        const trackingIsMapped = !!row.tracking_driver_id && row.tracking_status === 'mapped';
                        const trackingUrl = trackingIsMapped
                            ? `${panelBase}/vehicle-tracking?focus_driver_id=${encodeURIComponent(row.tracking_driver_id)}`
                            : 'javascript:void(0)';
                        const trackingMessage = (row.tracking_message || 'Tracking unavailable for this vehicle.')
                            .replace(/'/g, '&#39;');
                        const trackingTitle = trackingIsMapped ? 'Tracking' : 'Tracking unavailable';
                        const trackingStyle = trackingIsMapped
                            ? 'background-color: #138f5a; color: #fff;'
                            : 'background-color: #f59e0b; color: #fff;';

                        actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider round"></span>
                    </label>
                `;


                        if (canModuleAction('edit')) {
                            actionBtn += `
                    <a href="${panelBase}/vehicle/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;
                        }

                        actionBtn += `
                    <a href="${trackingUrl}" class="btn btn-oblong btn-sm" title="${trackingTitle}" style="${trackingStyle}" ${trackingIsMapped ? '' : `onclick="showTrackingMappingMessage('${trackingMessage}'); return false;"`}>
                        <i class="fa fa-map-marker"></i>
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
                        return row.school_name ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.driver_name ?? '-';
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.driver_phone ?? '-';
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.license_no ?? '-';
                    },
                },
                {
                    targets: 5,
                    render: function (data, type, row, meta) {
                        return row.license_expiry_date ?? '-';
                    },
                },
                {
                    targets: 6,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        if (canModuleAction('update')) {
                            actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider round"></span>
                    </label>
                `;
                        }
                        if (canModuleAction('edit')) {
                            actionBtn += `
                    <a href="${panelBase}/driver/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;
                        }

                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm"
                        title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }

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
                        if (canModuleAction('update')) {
                            actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                     <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider round"></span>
                    </label>
                `;
                        }
                        if (!schoolSlug && canModuleAction('update')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-info btn-sm" title="Login as School" onclick="loginAsSchool('${row.id}')">
                        <i class="fas fa-sign-in-alt"></i>
                    </button>
                `;
                        }
                        if (canModuleAction('edit')) {
                            actionBtn += `<a href="${panelBase}/school/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;
                        }

                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#schoolTrashTable") {
            response = [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
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
                        if (canModuleAction('restore')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-success btn-sm" title="Restore" onclick="restoreData(this, '${tableId}', 'school')" data-id="${row.id}">
                        <i class="fa fa-undo"></i>
                    </button>
                `;
                        }
                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Permanent Delete" onclick="forceDeleteSchool(this, '${tableId}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }
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
                        return row.name ?? '-';
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.vehicle_number ?? '-';
                    },
                },

                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.driver_name ?? '-';
                    },
                },

                {
                    targets: 5,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        if (canModuleAction('update')) {
                            actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider round"></span>
                    </label>
                `;
                        }

                        if (canModuleAction('edit')) {
                            actionBtn += `
                    <a href="${panelBase}/routes/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;
                        }

                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }

                        return actionBtn;
                    },
                },
            ];
        }else if (tableId == "#packageDetailTable") {
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
                        return row.package_name ?? '-';
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.package_type ?? '-';
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.booking_type ?? '-';
                    },
                },
                {
                    targets: 5,
                    render: function (data, type, row, meta) {
                        return row.price ?? '-';
                    },
                },
                {
                    targets: 6,
                    render: function (data, type, row, meta) {
                        return row.validity_days ?? '-';
                    },
                },
                {
                    targets: 7,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        if (canModuleAction('update')) {
                            actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider round"></span>
                    </label>
                `;
                        }

                        if (canModuleAction('edit')) {
                            actionBtn += `
                    <a href="${panelBase}/packageDetails/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;
                        }

                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }

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
                        return row.school_name ?? '-';
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
                        return row.latitude ?? '-';
                    },
                },
                {
                    targets: 5,
                    render: function (data, type, row, meta) {
                        return row.longitude ?? '-';
                    },
                },
                {
                    targets: 6,
                    render: function (data, type, row, meta) {
                        return row.contact_number ?? '-';
                    },
                },
                {
                    targets: 7,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        if (canModuleAction('update')) {
                            actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider round"></span>
                    </label>
                `;
                        }

                        if (canModuleAction('edit')) {
                            actionBtn += `
                    <a href="${panelBase}/booking/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;
                        }

                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }

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
                        return row.school_name ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.driver_name ?? '-';
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.vehicle_number ?? '-';
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.reported_by ?? '-';
                    },
                },
                {
                    targets: 5,
                    render: function (data, type, row, meta) {
                        return row.emergency_type ?? '-';
                    },
                },
                {
                    targets: 6,
                    render: function (data, type, row, meta) {
                        return row.contact_number ?? '-';
                    },
                },
                {
                    targets: 7,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        if (canModuleAction('update')) {
                            actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider round"></span>
                    </label>
                `;
                        }

                        if (canModuleAction('edit')) {
                            actionBtn += `
                    <a href="${panelBase}/emergency/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;
                        }

                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }

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
                        return row.school_name ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.driver_name;
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.vehicle_number;
                    },
                },
                 {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.rating;
                    },
                },
                 {
                    targets: 5,
                    render: function (data, type, row, meta) {
                        return row.comments;
                    },
                },
                {
                    targets: 6,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        if (canModuleAction('edit')) {
                            actionBtn += `<a href="${panelBase}/rating/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        }
                        if (canModuleAction('destroy')) {
                            actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        }
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
                        return row.school_name ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.route_name  ?? '-';
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.pickup_name ?? '-';
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.stop_name ?? '-';
                    },
                },
                {
                    targets: 5,
                    render: function (data, type, row, meta) {
                        return row.sequence_order ?? '-';
                    },
                },
                {
                    targets: 6,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        if (canModuleAction('update')) {
                            actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;
                        }

                        if (canModuleAction('edit')) {
                            actionBtn += `
                    <a href="${panelBase}/stopPickup/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;
                        }

                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }

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
                        return row.school_name ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.driver_name ?? '-';
                    },
                },
                 {
                    targets:3,
                    render: function (data, type, row, meta) {
                        return row.vehicle_number ?? '-';
                    },
                },
                {
                    targets: 4,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }

                        return actionBtn;
                    },
                },

            ];
        } else if (tableId == "#parentTable") {
            if (schoolSlug) {
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
                            return row.children_names ?? '-';
                        },
                    },
                    {
                        targets: 4,
                        render: function (data, type, row, meta) {
                            return row.contact_number ?? '-';
                        },
                    },
                    {
                        targets: 5,
                        orderable: false,
                        render: function (data, type, row, meta) {
                            let actionBtn = "";
                            if (canModuleAction('update')) {
                                actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                             <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                            <span class="slider"></span>
                        </label>
                    `;
                            }

                            if (canModuleAction('edit')) {
                                actionBtn += `
                        <a href="${panelBase}/parent/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                            <i class="fas fa-edit"></i>
                        </a>
                    `;
                            }

                            if (canModuleAction('destroy')) {
                                actionBtn += `
                        <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                            <i class="fa fa-trash"></i>
                        </button>
                    `;
                            }

                            return actionBtn;
                        },
                    },
                ];
            } else {
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
                            return row.mother_name ?? '-';
                        },
                    },
                    {
                        targets: 4,
                        render: function (data, type, row, meta) {
                            return row.children_names ?? '-';
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
                            if (canModuleAction('update')) {
                                actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                             <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                            <span class="slider"></span>
                        </label>
                    `;
                            }

                            if (canModuleAction('edit')) {
                                actionBtn += `
                        <a href="${panelBase}/parent/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                            <i class="fas fa-edit"></i>
                        </a>
                    `;
                            }

                            if (canModuleAction('destroy')) {
                                actionBtn += `
                        <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                            <i class="fa fa-trash"></i>
                        </button>
                    `;
                            }

                            return actionBtn;
                        },
                    },
                ];
            }
        } else if (tableId == "#childTable") {
            if (schoolSlug) {
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
                            return row.child_name ?? '-';
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
                            if (canModuleAction('update')) {
                                actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                             <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                            <span class="slider"></span>
                        </label>
                    `;
                            }

                            if (canModuleAction('edit')) {
                                actionBtn += `
                        <a href="${panelBase}/child/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                            <i class="fas fa-edit"></i>
                        </a>
                    `;
                            }

                            if (canModuleAction('destroy')) {
                                actionBtn += `
                        <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                            <i class="fa fa-trash"></i>
                        </button>
                    `;
                            }

                            return actionBtn;
                        },
                    },
                ];
            } else {
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
                            return row.child_name ?? '-';
                        },
                    },
                    {
                        targets: 3,
                        render: function (data, type, row, meta) {
                            return row.father_name ?? '-';
                        },
                    },
                    {
                        targets: 4,
                        render: function (data, type, row, meta) {
                            return row.name ?? '-';
                        },
                    },
                    {
                        targets: 5,
                        render: function (data, type, row, meta) {
                            return row.gender ?? '-';
                        },
                    },
                    {
                        targets: 6,
                        orderable: false,
                        render: function (data, type, row, meta) {
                            let actionBtn = "";
                            if (canModuleAction('update')) {
                                actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                             <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                            <span class="slider"></span>
                        </label>
                    `;
                            }

                            if (canModuleAction('edit')) {
                                actionBtn += `
                        <a href="${panelBase}/child/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                            <i class="fas fa-edit"></i>
                        </a>
                    `;
                            }

                            if (canModuleAction('destroy')) {
                                actionBtn += `
                        <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                            <i class="fa fa-trash"></i>
                        </button>
                    `;
                            }

                            return actionBtn;
                        },
                    },
                ];
            }
        } else if (tableId == "#leaveRequestsTable") {
            if (schoolSlug) {
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
                            return row.child_name ?? '-';
                        },
                    },
                    {
                        targets: 2,
                        render: function (data, type, row, meta) {
                            return row.parent_name ?? '-';
                        },
                    },
                    {
                        targets: 3,
                        render: function (data, type, row, meta) {
                            return row.reason ?? '-';
                        },
                    },
                    {
                        targets: 4,
                        render: function (data, type, row, meta) {
                            return row.from_date ?? '-';
                        },
                    },
                    {
                        targets: 5,
                        render: function (data, type, row, meta) {
                            return row.to_date ?? '-';
                        },
                    },
                    {
                        targets: 6,
                        render: function (data, type, row, meta) {
                            return row.submitted_at ?? '-';
                        },
                    },
                    {
                        targets: 7,
                        orderable: false,
                        render: function (data, type, row, meta) {
                            let actionBtn = "";

                            if (canModuleAction('destroy')) {
                                actionBtn += `
                        <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                            <i class="fa fa-trash"></i>
                        </button>
                    `;
                            }

                            return actionBtn;
                        },
                    },
                ];
            } else {
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
                            return row.child_name ?? '-';
                        },
                    },
                    {
                        targets: 3,
                        render: function (data, type, row, meta) {
                            return row.parent_name ?? '-';
                        },
                    },
                    {
                        targets: 4,
                        render: function (data, type, row, meta) {
                            return row.reason ?? '-';
                        },
                    },
                    {
                        targets: 5,
                        render: function (data, type, row, meta) {
                            return row.from_date ?? '-';
                        },
                    },
                    {
                        targets: 6,
                        render: function (data, type, row, meta) {
                            return row.to_date ?? '-';
                        },
                    },
                    {
                        targets: 7,
                        render: function (data, type, row, meta) {
                            return row.submitted_at ?? '-';
                        },
                    },
                    {
                        targets: 8,
                        orderable: false,
                        render: function (data, type, row, meta) {
                            let actionBtn = "";

                            if (canModuleAction('destroy')) {
                                actionBtn += `
                        <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                            <i class="fa fa-trash"></i>
                        </button>
                    `;
                            }

                            return actionBtn;
                        },
                    },
                ];
            }
        } else if (tableId == "#pushNotificationsTable") {
            response = [
                {
                    targets: 0,
                    render: function (data, type, row, meta) {
                        return row.id ?? meta.row + meta.settings._iDisplayStart + 1;
                    },
                },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.recipient ?? '-';
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.title ?? '-';
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return `<div style="max-width: 360px; white-space: normal;">${row.message ?? '-'}</div>`;
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return `<span class="badge bg-light text-dark">${row.type ?? 'general'}</span>`;
                    },
                },
                {
                    targets: 5,
                    render: function (data, type, row, meta) {
                        return row.created_at_value ?? '-';
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
                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }

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
                        if (canModuleAction('update')) {
                            actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;
                        }

                        if (canModuleAction('edit')) {
                            actionBtn += `
                    <a href="/cms/aboutSection/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;
                        }

                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }

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
                        if (canModuleAction('update')) {
                            actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;
                        }

                        if (canModuleAction('edit')) {
                            actionBtn += `
                    <a href="/cms/service/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;
                        }

                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }

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
                        if (canModuleAction('update')) {
                            actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;
                        }

                        if (canModuleAction('edit')) {
                            actionBtn += `
                    <a href="/cms/howItWorks/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;
                        }

                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }

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
                        if (canModuleAction('update')) {
                            actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;
                        }

                        if (canModuleAction('edit')) {
                            actionBtn += `
                    <a href="/cms/clientSection/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;
                        }

                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }

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
                        if (canModuleAction('update')) {
                            actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;
                        }

                        if (canModuleAction('edit')) {
                            actionBtn += `
                    <a href="/cms/benefitSection/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;
                        }

                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }

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
                        if (canModuleAction('update')) {
                            actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;
                        }

                        if (canModuleAction('edit')) {
                            actionBtn += `
                    <a href="/cms/testimonialSection/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;
                        }

                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }

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
                        if (canModuleAction('update')) {
                            actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;
                        }

                        if (canModuleAction('edit')) {
                            actionBtn += `
                    <a href="/cms/faqSection/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;
                        }

                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }

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
                        if (canModuleAction('update')) {
                            actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;
                        }

                        if (canModuleAction('edit')) {
                            actionBtn += `
                    <a href="/cms/priceSection/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;
                        }

                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }

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
                        if (canModuleAction('update')) {
                            actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;
                        }

                        if (canModuleAction('edit')) {
                            actionBtn += `
                    <a href="/cms/msbAppSection/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;
                        }

                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#socialsMediaTable") {
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
                        return row.social_name;
                    },
                },
                {
                    targets: 2,
                    // render: function (data, type, row, meta) {
                    //     return row.social_link;
                    // },
                    render: function (data, type, row, meta) {
                        const fullText = row.social_link;
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
                                </div>
                            `;
                        } else {
                            return `<span>${shortText}</span>`;
                        }
                    }
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        if (row.social_icon) {
                            return `<i class="${row.social_icon}" style="font-size: 20px;"></i>`;
                        }
                        return '';
                    },
                },
                {
                    targets: 4,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        if (canModuleAction('update')) {
                            actionBtn += `
                    <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                         <input type="checkbox" onclick="toggleData(this, '${row.id}', '${tableId}', '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" ${row.status ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                `;
                        }

                        if (canModuleAction('edit')) {
                            actionBtn += `
                    <a href="/cms/socialMediaSection/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;">
                        <i class="fas fa-edit"></i>
                    </a>
                `;
                        }

                        if (canModuleAction('destroy')) {
                            actionBtn += `
                    <button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this, '${tableId}', '${deleteRoute}')" data-id="${row.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
                        }

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#contactMessagesTable") {
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
                        return row.email ?? '-';
                    },
                },
                 {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.message ?? '-';
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.company ?? '-';
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
        const isActivating = newStatus === 1;

        $checkbox.prop('checked', !newStatus);
        Swal.fire({
            title: isActivating ? 'Activate User?' : 'Deactivate User?',
            text: isActivating
                ? 'Are you sure you want to activate this user?'
                : 'Are you sure you want to deactivate this user?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: isActivating ? 'Yes, activate!' : 'Yes, deactivate!',
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
                            notify('error', response.message || 'Error updating user status!');
                        }
                    },
                    error: function (xhr) {
                        notify('error', xhr.responseJSON?.message || 'Error updating user status!');
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
                }  else if (tableId === '#vehicleTypeTable') {
                    apiUrl = '/api/vehicleType/multi-delete';
                    reloadTable = '#vehicleTypeTable';
                } else if (tableId === '#vehicleTable') {
                    apiUrl = '/api/vehicle/multi-delete';
                    reloadTable = '#vehicleTable';
                } else if (tableId === '#driverTable') {
                    apiUrl = '/api/driver/multi-delete';
                    reloadTable = '#driverTable';
                } else if (tableId === '#schoolTable') {
                    apiUrl = '/api/school/multi-delete';
                    reloadTable = '#schoolTable';
                } else if (tableId === '#routeTable') {
                    apiUrl = '/api/routes/multi-delete';
                    reloadTable = '#routeTable';
                } else if (tableId === '#packageDetailTable') {
                    apiUrl = '/api/packageDetails/multi-delete';
                    reloadTable = '#packageDetailTable';
                } else if (tableId === '#bookingTable') {
                    apiUrl = '/api/booking/multi-delete';
                    reloadTable = '#bookingTable';
                } else if (tableId === '#emergencyTable') {
                    apiUrl = '/api/emergency/multi-delete';
                    reloadTable = '#emergencyTable';
                } else if (tableId === '#feedbackTable') {
                    apiUrl = '/api/rating/multi-delete';
                    reloadTable = '#feedbackTable';
                } else if (tableId === '#stopPickupTable') {
                    apiUrl = '/api/stopPickup/multi-delete';
                    reloadTable = '#stopPickupTable';
                } else if (tableId === '#driverHistoryTable') {
                    apiUrl = '/api/driverHistory/multi-delete';
                    reloadTable = '#driverHistoryTable';
                } else if (tableId === '#parentTable') {
                    apiUrl = '/api/parent/multi-delete';
                    reloadTable = '#parentTable';
                } else if (tableId === '#childTable') {
                    apiUrl = '/api/child/multi-delete';
                    reloadTable = '#childTable';
                } else if (tableId === '#aboutSectionTable') {
                    apiUrl = '/api/aboutSection/multi-delete';
                    reloadTable = '#aboutSectionTable';
                } else if (tableId === '#serviceTable') {
                    apiUrl = '/api/service/multi-delete';
                    reloadTable = '#serviceTable';
                } else if(tableId === '#testimonialTable') {
                    apiUrl = '/api/testimonailSection/multi-delete';
                    reloadTable = '#testimonialTable';
                } else if(tableId === '#contactMessageTable') {
                    apiUrl = '/api/contactMessage/multi-delete';
                    reloadTable = '#contactMessageTable';
                } else if (tableId === '#faqTable') {
                    apiUrl = '/api/faqSection/multi-delete';
                    reloadTable = '#faqTable';
                } else if(tableId === '#priceTable') {
                    apiUrl = '/api/priceSection/multi-delete';
                    reloadTable = '#priceTable';
                } else if(tableId === '#msbAppSectionTable') {
                    apiUrl = '/api/msbAppSection/multi-delete';
                    reloadTable = '#msbAppSectionTable';
                } else if(tableId === '#socialsMediaTable') {
                    apiUrl = '/api/socialMediaSection/multi-delete';
                    reloadTable = '#socialsMediaTable';
                } else if(tableId === '#contactMessagesTable') {
                    apiUrl = '/api/contactMessage/multi-delete';
                    reloadTable = '#contactMessagesTable';
                } else if(tableId === '#howItWorksTable') {
                    apiUrl = '/api/howItWorks/multi-delete';
                    reloadTable = '#howItWorksTable';
                } else if(tableId === '#benefitSectionTable') {
                    apiUrl = '/api/benefitSection/multi-delete';
                    reloadTable = '#benefitSectionTable';
                } else if(tableId === '#clientSectionTable') {
                    apiUrl = '/api/clientSection/multi-delete';
                    reloadTable = '#clientSectionTable';
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

function showTrackingMappingMessage(message) {
    const text = message || 'Tracking unavailable for this vehicle.';

    if (typeof Swal !== 'undefined' && Swal.fire) {
        Swal.fire({
            icon: 'warning',
            title: 'Tracking Unavailable',
            text: text,
            confirmButtonText: 'OK'
        });
        return;
    }

    alert(text);
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

// COMMON CODE FOR RESTORE
function restoreData(dis, tableId, restoreRoute) {
    let restore_id = dis.getAttribute("data-id");
    swal({
        title: "Restore this school?",
        text: "All school related data will be restored.",
        icon: "info",
        buttons: true,
    })
        .then((willRestore) => {
            if (willRestore) {
                $.ajax({
                    url: `/api/${restoreRoute}/${restore_id}/restore`,
                    type: 'POST',
                    success: function (response) {
                        if (response.success) {
                            notify('success', 'Restored Successfully!');
                            $(tableId).DataTable().ajax.reload();
                        } else {
                            notify('error', 'Error restoring Data!');
                        }
                    },
                    error: function () {
                        notify('error', 'Error restoring Data!');
                    }
                });
            }
        });
}

function forceDeleteSchool(dis, tableId) {
    let del_id = dis.getAttribute("data-id");
    swal({
        title: "Permanent delete this school?",
        text: "This will generate an Excel file backup and then permanently remove all related data.",
        icon: "warning",
        buttons: true,
        dangerMode: true,
    })
        .then((willDelete) => {
            if (willDelete) {
                $.ajax({
                    url: `/api/school/${del_id}/force-delete`,
                    type: 'POST',
                    success: function (response) {
                        if (response.success) {
                            notify('success', response.message || 'Permanently deleted!');
                            if (response.download_url) {
                                window.open(response.download_url, '_blank');
                            }
                            $(tableId).DataTable().ajax.reload();
                        } else {
                            notify('error', response.message || 'Error permanently deleting!');
                        }
                    },
                    error: function () {
                        notify('error', 'Error permanently deleting!');
                    }
                });
            }
        });
}

