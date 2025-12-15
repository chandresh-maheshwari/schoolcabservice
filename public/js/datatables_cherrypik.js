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
        // dom: 'Bfrtip',
        // dom: 'Blftipr',
        dom: '<"top"f>rt<"bottom"liBp><"clear">',
        // "dom": '<"top"f>rt<"bottom"lpB><"clear">',
        // buttons: leftbutton,
        // responsive: true,
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
            // alert(4444);
            data;
            // $('.dataTables_filter').wrap('<div class="filterGroup"></div>');
            $(".dataTables_filter input").val(data.search_text);

            // $('#loader_modal').modal('hide');
            // Search button
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
            // $(tableId + '_filter').append($searchButton, $clearButton);

            $action_filter1 = $("#action_filter1")
                .addClass("d-none")
                .clone();
            // $('#action_filter1').remove();

            // $(this).next('label').andSelf().wrapAll('<div class="testing"/>');
            $(tableId + "_filter")
                .append($searchButton, $clearButton)
                .next("label")
                // .andSelf()
                .wrapAll('<div class="wrapper_actionfilter"></div>');


            $(tableId + "_filter").wrapInner(
                '<div class="wrapper_searchfilter"></div>'
            );
            $(".wrapper_actionfilter").append(
                $($action_filter1).removeClass("d-none")
            );
            // $action_filter1 = $("#action_filter1")
            //         .addClass("d-none")
            //         .clone();
            // table_cal(moment(datestart), moment(dateend), dumy);
        },
        footerCallback: function (row, data, start, end, display) {
            var api = this.api(),
                data;
            // alert(data[0].search_text);
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
                    // $("#loader_modal").modal("show");
                    // console.log(xhr);
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
        } else if (tableId == "#faqsTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                // { mDataProp: "category", name: "category" },
                { mDataProp: "question", name: "question" },
                { mDataProp: "answer", name: "answer" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#contactsTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "description", name: "description" },
                { mDataProp: "location_title", name: "location_title" },
                // { mDataProp: "location", name: "location" },
                // { mDataProp: "contact_title", name: "contact_title" },
                // { mDataProp: "contact_1", name: "contact_1" },
                // { mDataProp: "contact_2", name: "contact_2" },
                // { mDataProp: "email_title", name: "email_title" },
                // { mDataProp: "email_1", name: "email_1" },
                // { mDataProp: "email_2", name: "email_2" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#footerTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "contact", name: "contact" },
                { mDataProp: "email", name: "email" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#headerTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "link", name: "link" },
                { mDataProp: "button_title", name: "button_title" },
                { mDataProp: "button_link", name: "button_link" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#clientsTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "client", name: "client" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#heroSectionTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "image", name: "image" },
                { mDataProp: "description", name: "description" },
                // { mDataProp: "button_title_1", name: "button_title_1" },
                // { mDataProp: "button_title_2", name: "button_title_2" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#featureTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "image", name: "image" },
                { mDataProp: "description", name: "description" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#statsTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "stats_counter", name: "stats_counter" },
                { mDataProp: "stats_title", name: "stats_title" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#serviceTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "description", name: "description" },
                { mDataProp: "service_icon", name: "service_icon" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#alternativeTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "description", name: "description" },
                { mDataProp: "alternative_icon", name: "alternative_icon" },
                { mDataProp: "button_title", name: "button_title" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#portfolioTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "description", name: "description" },
                { mDataProp: "image", name: "image" },
                { mDataProp: "category", name: "category" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#teamsTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "description", name: "description" },
                { mDataProp: "image", name: "image" },
                { mDataProp: "role", name: "role" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#pricingTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "currency", name: "currency" },
                { mDataProp: "amount", name: "amount" },
                { mDataProp: "period", name: "period" },
                { mDataProp: "description", name: "description" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#capabilityTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "description", name: "description" },
                { mDataProp: "capability_icon", name: "capability_icon" },
                { mDataProp: "progress_label", name: "progress_label" },
                { mDataProp: "progress_indicator", name: "progress_indicator" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#advanceCapabilityTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "description", name: "description" },
                { mDataProp: "advance_capability_icon", name: "advance_capability_icon" },
                { mDataProp: "feature_status_badge", name: "feature_status_badge" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#whyUsTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "description", name: "description" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#callToActionTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "feature_1", name: "feature_1" },
                { mDataProp: "feature_2", name: "feature_2" },
                { mDataProp: "feature_3", name: "feature_3" },
                { mDataProp: "feature_4", name: "feature_4" },
                { mDataProp: "button_title", name: "button_title" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        }  else if (tableId == "#aboutUsTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "image", name: "image" },
                { mDataProp: "description", name: "description" },
                { mDataProp: "profile_name", name: "profile_name" },
                { mDataProp: "profile_position", name: "profile_position" },
                { mDataProp: "profile_image", name: "profile_image" },
                { mDataProp: "contact_number", name: "contact_number" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#categoriesTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "name", name: "name" },
                { mDataProp: "category_link", name: "category_link" },
                { mDataProp: "category_icon", name: "category_icon" },
                { mDataProp: "status", name: "status" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#contactMessagesTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "first_name", name: "first_name" },
                // { mDataProp: "last_name", name: "last_name" },
                { mDataProp: "email", name: "email" },
                { mDataProp: "subject", name: "subject" },
                { mDataProp: "message", name: "message" },
                // { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#socialsMediaTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
                { mDataProp: "name", name: "name" },
                { mDataProp: "social_link", name: "social_link" },
                { mDataProp: "social_icon", name: "social_icon" },
                { mDataProp: "status", name: "status" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        }  else if (tableId == "#cherrypikPagesTable") {
            columnData = [
                { mDataProp: "id", name: "id" },
                { mDataProp: "title", name: "title" },
                { mDataProp: "image", name: "image" },
                { mDataProp: "template", name: "template" },
                { mDataProp: "description", name: "description" },
                { mDataProp: "status", name: "status" },
                // { mDataProp: "inner_page_status", name: "inner_page_status" },
                { mDataProp: "Actions", name: "Actions" },
            ];
        } else if (tableId == "#newsLetterTable") {
            columnData = [
                { mDataProp: "checkbox", name: "checkbox" },
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
                    // targets: 1,
                    // orderable: false,
                    // render: function (data, type, row, meta) {
                    //     const photoUrl = row.photo ? `/storage/${row.photo}` : '/assets/images/person.jpg';
                    //     return `<img src="${photoUrl}" class="rounded-circle" style="width: 50px; height: 50px;" alt="User Photo">`;
                    // },
                    targets: 1,
                    render: function (data, type, row, meta) {
                        if (row.photo && row.photo.trim() !== "") {
                            // Show the image, fallback to default if it fails to load

                            return `<img src="/${row.photo}?cb=${Date.now()}" alt="Image" style="width: 50px; height: 50px;"
                                    onerror="this.onerror=null; this.src='/images/person.jpg';">`;
                        } else {
                            // Show default image if row.image is null/empty
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
        }  else if (tableId == "#faqsTable") {
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
                // {
                //     targets: 1,
                //     render: function (data, type, row, meta) {
                //         return row.category;
                //     },
                // },
                {
                    targets: 1,
                    render: function (data, type, row, meta) {
                        return row.question;
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        const fullText = row.answer;
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
                    targets: 3,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleStatus"  onclick="toggleData(this, ${row.id} , '${tableId}' , '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        actionBtn += `<a href="/admin/faqs/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteFaq" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        }  else if (tableId == "#contactsTable") {
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
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.location_title;
                    },
                },
                // {
                //     targets: 4,
                //     render: function (data, type, row, meta) {
                //         const fullText = row.location;
                //         const tempDiv = document.createElement('div');
                //         tempDiv.innerHTML = fullText;
                //         const plainText = tempDiv.textContent || tempDiv.innerText || "";
                //         const shortText = plainText.length > 50 ? plainText.substring(0, 50) + '...' : plainText;
                //         const isExpandable = plainText.length > 50;

                //         if (isExpandable) {
                //             return `
                //                 <div class="location-wrapper" data-expanded="false">
                //                     <span class="location-text">${shortText}</span>
                //                     <span class="full-location" style="display:none;">${fullText}</span>
                //                     <a href="javascript:void(0);" class="toggle-location" style="margin-left: 5px; color: #007bff;">Read More</a>
                //                 </div>
                //             `;
                //         } else {
                //             return `<span>${shortText}</span>`;
                //         }
                //     }
                // },
                // {
                //     targets: 5,
                //     render: function (data, type, row, meta) {
                //         return row.contact_title;
                //     },
                // },
                // {
                //     targets: 6,
                //     render: function (data, type, row, meta) {
                //         return row.contact_1;
                //     },
                // },
                // {
                //     targets: 7,
                //     render: function (data, type, row, meta) {
                //         return row.contact_2;
                //     },
                // },
                // {
                //     targets: 8,
                //     render: function (data, type, row, meta) {
                //         return row.email_title;
                //     },
                // },
                // {
                //     targets: 9,
                //     render: function (data, type, row, meta) {
                //         return row.email_1;
                //     },
                // },
                // {
                //     targets: 10,
                //     render: function (data, type, row, meta) {
                //         return row.email_2;
                //     },
                // },
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
                        actionBtn += `<a href="/admin/contacts/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteContact" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fas fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        }
        else if (tableId == "#footerTable") {
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
                        const fullText = row.location;
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = fullText;
                        const plainText = tempDiv.textContent || tempDiv.innerText || "";
                        const shortText = plainText.length > 50 ? plainText.substring(0, 50) + '...' : plainText;
                        const isExpandable = plainText.length > 50;

                        if (isExpandable) {
                            return `
                                <div class="location-wrapper" data-expanded="false">
                                    <span class="location-text">${shortText}</span>
                                    <span class="full-location" style="display:none;">${fullText}</span>
                                    <a href="javascript:void(0);" class="toggle-location" style="margin-left: 5px; color: #007bff;">Read More</a>
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
                        return row.contact;
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.email;
                    },
                },
                {
                    targets: 5,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleStatus"  onclick="toggleData(this, ${row.id} , '${tableId}' , '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        actionBtn += `<a href="/admin/footer/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        // actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteContact" data-id="${row.id}"><i class="fas fa-trash"></i></button>`;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="footerDeleteBtn" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;

                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#headerTable") {
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
                        return row.link;
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.button_title;
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.button_link;
                    },
                },
                {
                    targets: 5,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleStatus"  onclick="toggleData(this, ${row.id} , '${tableId}' , '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        actionBtn += `<a href="/admin/header/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        // actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteContact" data-id="${row.id}"><i class="fas fa-trash"></i></button>`;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="footerDeleteBtn" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;

                        return actionBtn;
                    },
                },
            ];
        }  else if (tableId == "#clientsTable") {
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
                        if (row.client && row.client.trim() !== "") {
                            // Show the image, fallback to default if it fails to load
                            return `<img src="/${row.client}?cb=${Date.now()}" alt="Image" style="width: 100px; height: 50px;"
                        onerror="this.onerror=null; this.src='/images/Default.jpg';">`;
                        } else {
                            // Show default image if row.image is null/empty
                            return `<img src="/images/Default.jpg" alt="Default" style="width: 100px; height: 50px;">`;
                        }
                    }
                },
                // {
                //     targets: 1,
                //     render: function (data, type, row, meta) {
                //         return `<img src="/${row.client}?cb=${Date.now()}" alt="Image" style="width: 100px; height: 50px;">`;

                //     },
                // },
                {
                    targets: 2,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        // let actionBtn = "";
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleStatus"  onclick="toggleData(this, ${row.id} , '${tableId}' , '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        actionBtn += `<a href="/admin/client/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#aboutUsTable") {
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

                        // return `<img src="/${row.image}?cb=${Date.now()}" alt="Image" style="width: 50px; height: 50px;">`;
                        if (row.image && row.image.trim() !== "") {
                            // Show the image, fallback to default if it fails to load
                            return `<img src="/${row.image}?cb=${Date.now()}" alt="Image" style="width: 100px; height: 50px;"
              onerror="this.onerror=null; this.src='/images/Default.jpg';">`;
                        } else {
                            // Show default image if row.image is null/empty
                            return `<img src="/images/Default.jpg" alt="Default" style="width: 100px; height: 50px;">`;
                        }
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        const fullText = row.description; // HTML from CKEditor
                        // Strip HTML for short text preview
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
                    render: function (data, type, row, meta) {
                        return row.profile_name;
                    },
                },
                {
                    targets: 5,
                    render: function (data, type, row, meta) {
                        return row.profile_position;
                    },
                },
                {
                    targets: 6,
                    render: function (data, type, row, meta) {
                        // return `<img src="/${row.profile_image}?cb=${Date.now()}" alt="Image" style="width: 50px; height: 50px;">`;
                        if (row.profile_image && row.profile_image.trim() !== "") {
                            // Show the image, fallback to default if it fails to load
                            return `<img src="/${row.profile_image}?cb=${Date.now()}" alt="Image" style="width: 100px; height: 50px;"
              onerror="this.onerror=null; this.src='/images/Default.jpg';">`;
                        } else {
                            // Show default image if row.image is null/empty
                            return `<img src="/images/Default.jpg" alt="Default" style="width: 100px; height: 50px;">`;
                        }
                    },
                },
                {
                    targets: 7,
                    render: function (data, type, row, meta) {
                        return row.contact_number;
                    },
                },
                {
                    targets: 8,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleStatus"  onclick="toggleData(this, ${row.id} , '${tableId}' , '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        actionBtn += `<a href="/admin/aboutUs/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteAboutUs" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fas fa-trash"></i></button>`;
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
                            // Show the image, fallback to default if it fails to load
                            return `<img src="/${row.image}?cb=${Date.now()}" alt="Image" style="width: 100px; height: 50px;"
              onerror="this.onerror=null; this.src='/images/Default.jpg';">`;
                        } else {
                            // Show default image if row.image is null/empty
                            return `<img src="/images/Default.jpg" alt="Default" style="width: 100px; height: 50px;">`;
                        }
                    }
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        const fullText = row.description; // HTML from CKEditor
                        // Strip HTML for short text preview
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
        } else if (tableId == "#statsTable") {
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
                        return row.stats_counter;
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.stats_title;
                    },
                },
                {
                    targets: 3,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleStatus"  onclick="toggleData(this, ${row.id} , '${tableId}' , '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        actionBtn += `<a href="/admin/stats/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
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
                        const fullText = row.description; // HTML from CKEditor
                        // Strip HTML for short text preview
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
                    targets: 3,
                    render: function (data, type, row, meta) {
                        if (row.service_icon) {
                            return `<i class="${row.service_icon}" style="font-size: 20px;"></i>`;
                        }
                        return '';
                    },
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
                        actionBtn += `<a href="/admin/service/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#alternativeTable") {
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
                        const fullText = row.description; // HTML from CKEditor
                        // Strip HTML for short text preview
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
                    targets: 3,
                    render: function (data, type, row, meta) {
                        if (row.alternative_icon) {
                            return `<i class="${row.alternative_icon}" style="font-size: 20px;"></i>`;
                        }
                        return '';
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.button_title;
                    },
                },
                {
                    targets: 5,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleStatus"  onclick="toggleData(this, ${row.id} , '${tableId}' , '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        actionBtn += `<a href="/admin/alternative/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        }
        else if (tableId == "#portfolioTable") {
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
                        const fullText = row.description; // HTML from CKEditor
                        // Strip HTML for short text preview
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
                // {
                //     targets: 3,
                //     render: function (data, type, row, meta) {

                //         return `<img src="/${row.image}?cb=${Date.now()}" alt="Image" style="width: 50px; height: 50px;">`;
                //     },
                // },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        if (row.image && row.image.trim() !== "") {
                            // Show the image, fallback to default if it fails to load

                            return `<img src="/${row.image}?cb=${Date.now()}" alt="Image" style="width: 100px; height: 50px;"
              onerror="this.onerror=null; this.src='/images/Default.jpg';">`;
                        } else {
                            // Show default image if row.image is null/empty
                            return `<img src="/images/Default.jpg" alt="Default" style="width: 100px; height: 50px;">`;
                        }
                    }
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.name;
                    },
                },
                {
                    targets: 5,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleStatus"  onclick="toggleData(this, ${row.id} , '${tableId}' , '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        actionBtn += `<a href="/admin/portfolio/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#teamsTable") {
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
                        const fullText = row.description; // HTML from CKEditor
                        // Strip HTML for short text preview
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
                // {
                //     targets: 3,
                //     render: function (data, type, row, meta) {

                //         return `<img src="/${row.image}?cb=${Date.now()}" alt="Image" style="width: 50px; height: 50px;">`;
                //     },
                // },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        if (row.image && row.image.trim() !== "") {
                            // Show the image, fallback to default if it fails to load
                            return `<img src="/${row.image}?cb=${Date.now()}" alt="Image" style="width: 100px; height: 50px;"
              onerror="this.onerror=null; this.src='/images/Default.jpg';">`;
                        } else {
                            // Show default image if row.image is null/empty
                            return `<img src="/images/Default.jpg" alt="Default" style="width: 100px; height: 50px;">`;
                        }
                    }
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.role;
                    },
                },
                {
                    targets: 5,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleStatus"  onclick="toggleData(this, ${row.id} , '${tableId}' , '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        actionBtn += `<a href="/admin/teams/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#pricingTable") {
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
                        return row.currency;
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.amount;
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.period;
                    },
                },
                {
                    targets: 5,
                    render: function (data, type, row, meta) {
                        const fullText = row.description; // HTML from CKEditor
                        // Strip HTML for short text preview
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
                    targets: 6,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleStatus"  onclick="toggleData(this, ${row.id} , '${tableId}' , '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        actionBtn += `<a href="/admin/pricing/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#capabilityTable") {
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
                        const fullText = row.description; // HTML from CKEditor
                        // Strip HTML for short text preview
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
                    targets: 3,
                    render: function (data, type, row, meta) {
                        if (row.capability_icon) {
                            return `<i class="${row.capability_icon}" style="font-size: 20px;"></i>`;
                        }
                        return '';
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.progress_indicator;
                    },
                },
                {
                    targets: 5,
                    render: function (data, type, row, meta) {
                        return row.progress_label;
                    },
                },
                {
                    targets: 6,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleStatus"  onclick="toggleData(this, ${row.id} , '${tableId}' , '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        actionBtn += `<a href="/admin/capability/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#advanceCapabilityTable") {
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
                        const fullText = row.description; // HTML from CKEditor
                        // Strip HTML for short text preview
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
                    targets: 3,
                    render: function (data, type, row, meta) {
                        if (row.advance_capability_icon) {
                            return `<i class="${row.advance_capability_icon}" style="font-size: 20px;"></i>`;
                        }
                        return '';
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.feature_status_badge;
                    },
                },
                {
                    targets: 5,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleStatus"  onclick="toggleData(this, ${row.id} , '${tableId}' , '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        actionBtn += `<a href="/admin/advance_capability/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#whyUsTable") {
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
                        const fullText = row.description; // HTML from CKEditor
                        // Strip HTML for short text preview
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
                    targets: 3,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleStatus"  onclick="toggleData(this, ${row.id} , '${tableId}' , '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        actionBtn += `<a href="/admin/why_us/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#callToActionTable") {
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
                        return row.feature_1;
                    },
                },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.feature_2;
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.feature_3;
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        return row.feature_4;
                    },
                },
                {
                    targets: 5,
                    render: function (data, type, row, meta) {
                        return row.button_title;
                    },
                },
                {
                    targets: 6,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleStatus"  onclick="toggleData(this, ${row.id} , '${tableId}' , '${deleteRoute}', ${numberOfActivePost})" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        actionBtn += `<a href="/admin/call_to_action/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#featureTable") {
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
                        // return `<img src="/${row.image}?cb=${Date.now()}" alt="Image" style="width: 100px; height: 50px;">`;
                        if (row.image && row.image.trim() !== "") {
                            // Show the image, fallback to default if it fails to load
                            return `<img src="/${row.image}?cb=${Date.now()}" alt="Image" style="width: 100px; height: 50px;"
              onerror="this.onerror=null; this.src='/images/Default.jpg';">`;
                        } else {
                            // Show default image if row.image is null/empty
                            return `<img src="/images/Default.jpg" alt="Default" style="width: 100px; height: 50px;">`;
                        }
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        const fullText = row.description; // HTML from CKEditor
                        // Strip HTML for short text preview
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
                        actionBtn += `<a href="/admin/feature/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#cherrypikPagesTable") {
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
                        // return `<img src="/${row.image}?cb=${Date.now()}" alt="Image" style="width: 100px; height: 50px;">`;
                        if (row.image && row.image.trim() !== "") {
                            // Show the image, fallback to default if it fails to load
                            return `<img src="/${row.image}?cb=${Date.now()}" alt="Image" style="width: 100px; height: 50px;"
              onerror="this.onerror=null; this.src='/images/Default.jpg';">`;
                        } else {
                            // Show default image if row.image is null/empty
                            return `<img src="/images/Default.jpg" alt="Default" style="width: 100px; height: 50px;">`;
                        }
                    },
                },

                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.template;
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        const fullText = row.description; // HTML from CKEditor
                        // Strip HTML for short text preview
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
                    targets: 5,
                    render: function (data, type, row, meta) {
                        const statusText = row.status ? 'Active' : 'Inactive';
                        const statusColor = row.status ? 'green' : 'red';
                        return `<span style="color: ${statusColor}; font-weight: bold;">${statusText}</span>`;
                    },
                },
                {
                    targets: 6,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.inner_page_status ? 'Change Inner Page Status to Inactive' : 'Change Inner Page Status to Active'}">
                            <input type="checkbox" id="toggleInnerPageStatus" data-id="${row.id}" data-status="${row.inner_page_status}" ${row.inner_page_status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleCherrypikPageStatus" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>`;
                        actionBtn += `<a href="/admin/cherrypik_pages/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" id="edit" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#categoriesTable") {
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
                        return row.category_link;
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        if (row.category_icon) {
                            return `<i class="${row.category_icon}" style="font-size: 20px;"></i>`;
                        }
                        return '';
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        const statusText = row.status ? 'Active' : 'Inactive';
                        const statusColor = row.status ? 'green' : 'red';
                        return `<span style="color: ${statusColor}; font-weight: bold;">${statusText}</span>`;
                    },
                },
                {
                    targets: 5,
                    render: function (data, type, row, meta) {
                        return row.order;
                    },
                },
                {
                    targets: 6,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleCategoryStatus" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>
                    `;
                        actionBtn += `<a href="/admin/categories/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" id="deleteCategory" data-id="${row.id}"><i class="fas fa-trash"></i></button> `;
                        return actionBtn;
                    },
                },
            ];
        }  else if (tableId == "#contactMessagesTable") {
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
                        return row.first_name;
                    },
                },
                // {
                //     targets: 2,
                //     render: function (data, type, row, meta) {
                //         return row.last_name;
                //     },
                // },
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        return row.email;
                    },
                },
                {
                    targets: 3,
                    render: function (data, type, row, meta) {
                        return row.subject;
                    },
                },
                {
                    targets: 4,
                    render: function (data, type, row, meta) {
                        const fullText = row.message;
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
                // {
                //     targets: 6,
                //     orderable: false,
                //     render: function (data, type, row, meta) {
                //         let actionBtn = "";
                //         actionBtn += `<a href="/contact_messages/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2C9DD4;"><i class="fa fa-pencil"></i></a> `;
                //         actionBtn += `<button class="btn btn-oblong btn-danger Fbtn-sm" title="Delete" id="deleteContactMessage" data-id="${row.id}"><i class="fas fa-trash"></i></button>`;
                //         return actionBtn;
                //     },
                // },
            ];

        }  else if (tableId == "#socialsMediaTable") {
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
                                    <a href="javascript:void(0);" class="toggle-description" style="margin-left: 5px; color: #007bff;">Read More</a>
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
                    render: function (data, type, row, meta) {
                        const statusText = row.status ? 'Active' : 'Inactive';
                        const statusColor = row.status ? 'green' : 'red';
                        return `<span style="color: ${statusColor}; font-weight: bold;">${statusText}</span>`;
                    },
                },
                {
                    targets: 5,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let actionBtn = "";
                        actionBtn += `
                        <label class="switch" title="${row.status ? 'Change Status to Inactive' : 'Change Status to Active'}">
                            <input type="checkbox" id="toggleAuthorSocialStatus" data-id="${row.id}" data-status="${row.status}" ${row.status ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>
                    `;
                        actionBtn += `<a href="/admin/socials-media/${row.id}/edit" class="btn btn-oblong btn-primary btn-sm" title="Edit" style="background-color: #2d336b;"><i class="fas fa-edit"></i></a> `;
                        actionBtn += `<button class="btn btn-oblong btn-danger btn-sm" title="Delete" id="deleteCMSCategory" onclick="deleteData(this , '${tableId}' , '${deleteRoute}')" data-id="${row.id}"><i class="fas fa-trash"></i></button> `;
                        return actionBtn;
                    },
                },
            ];
        } else if (tableId == "#newsLetterTable") {
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
                        return row.email;
                    },
                },
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

        // Revert the toggle until confirmation
        $checkbox.prop('checked', !newStatus);

        // if (newStatus === 1) {
            // Activating: show simple confirmation popup
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


    $(document).on('change', '#toggleCategoryStatus', function () {
        let categoryId = $(this).data('id');
        let newStatus = $(this).is(':checked') ? 1 : 0;

        $.ajax({
            url: `/api/categories/${categoryId}/toggle-status`,
            type: 'POST',
            data: {
                status: newStatus,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    notify('success', 'Category status updated Successfully!');
                    $('#categoriesTable').DataTable().ajax.reload();
                } else {
                    notify('error', 'Error updating category status!');
                }
            },
            error: function () {
                notify('error', 'Error updating category status!');
            }
        });
    });

     $(document).on('change', '#toggleAuthorSocialStatus', function () {
        let soicalId = $(this).data('id');
        let newStatus = $(this).is(':checked') ? 1 : 0;

        $.ajax({
            url: `/api/socials-media/${soicalId}/toggle-status`,
            type: 'POST',
            data: {
                status: newStatus,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    notify('success', 'Social status updated Successfully!');
                    $('#socialsMediaTable').DataTable().ajax.reload();
                } else {
                    notify('error', 'Error updating social status!');
                }
            },
            error: function () {
                notify('error', 'Error updating social status!');
            }
        });
    });


    $(document).on('change', '#toggleInnerPageStatus', function () {
        let pageId = $(this).data('id');
        let newStatus = $(this).is(':checked') ? 1 : 0;

        $.ajax({
            url: `/api/cherrypik_pages/${pageId}/toggle-inner-page-status`,
            type: 'POST',
            data: {
                status: newStatus,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    notify('success', 'Cherrypik Page Inner status updated Successfully!');
                    $('#cherrypikPagesTable').DataTable().ajax.reload();
                } else {
                    notify('error', 'Error updating Cherrypik Inner Page status!');
                }
            },
            error: function () {
                notify('error', 'Error updating Cherrypik Inner Page status!');
            }
        });
    });

    $(document).on('change', '#toggleCherrypikPageStatus', function () {
        let pageId = $(this).data('id');
        let newStatus = $(this).is(':checked') ? 1 : 0;

        $.ajax({
            url: `/api/cherrypik_pages/${pageId}/toggle-status`,
            type: 'POST',
            data: {
                status: newStatus,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    notify('success', 'Cherrypik Page status updated Successfully!');
                    $('#cherrypikPagesTable').DataTable().ajax.reload();
                } else {
                    notify('error', 'Error updating Cherrypik Page status!');
                }
            },
            error: function () {
                notify('error', 'Error updating Cherrypik Page status!');
            }
        });
    });







    $(document).on('click', '.toggle-description', function () {
        const $link = $(this);
        const $wrapper = $link.closest('.description-wrapper');
        const $descText = $wrapper.find('.description-text');
        const $fullDesc = $wrapper.find('.full-description');
        const isExpanded = $wrapper.data('expanded');

        if (isExpanded) {
            // Collapse to short text
            $fullDesc.hide();
            $descText.show();
            $link.text('Read More');
            $wrapper.data('expanded', false);
        } else {
            // Expand to full HTML
            $descText.hide();
            $fullDesc.show();
            $link.text('Read Less');
            $wrapper.data('expanded', true);
        }
    });


    $(document).on('click', '.toggle-front-description', function () {
        const $link = $(this);
        const $wrapper = $link.closest('.description-wrapper');
        const $descText = $wrapper.find('.description-text');
        const $fullDesc = $wrapper.find('.full-description');
        const isExpanded = $wrapper.data('expanded');

        if (isExpanded) {
            // Collapse to short text
            $fullDesc.hide();
            $descText.show();
            $link.text('Read More');
            $wrapper.data('expanded', false);
        } else {
            // Expand to full HTML
            $descText.hide();
            $fullDesc.show();
            $link.text('Read Less');
            $wrapper.data('expanded', true);
        }
    });



//   $(document).on('click', '.toggle-front-description', function () {
//         const $link = $(this);
//         const $wrapper = $link.closest('.description-wrapper');
//         const $descText = $wrapper.find('.description-text');
//         const $fullDesc = $wrapper.find('.full-description');
//         const isExpanded = $wrapper.data('expanded');

//         if (isExpanded) {
//             // Collapse to short text
//             $fullDesc.hide();
//             $descText.show();
//             $link.text('Read More');
//             $wrapper.data('expanded', false);
//         } else {
//             // Expand to full HTML
//             $descText.hide();
//             $fullDesc.show();
//             $link.text('Read Less');
//             $wrapper.data('expanded', true);
//         }
//     });



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
                } else if (tableId === '#socialsMediaTable') {
                    apiUrl = '/api/socials-media/multi-delete';
                    reloadTable = '#socialsMediaTable';
                } else if (tableId === '#contactsTable') {
                    apiUrl = '/api/contacts/multi-delete';
                    reloadTable = '#contactsTable';
                } else if (tableId === '#footerTable') {
                    apiUrl = '/api/footer/multi-delete';
                    reloadTable = '#footerTable';
                } else if (tableId === '#aboutUsTable') {
                    apiUrl = '/api/aboutUs/multi-delete';
                    reloadTable = '#aboutUsTable';
                } else if (tableId === '#clientsTable') { // Client Section
                    apiUrl = '/api/client/multi-delete';
                    reloadTable = '#clientsTable';
                } else if (tableId === '#heroSectionTable') { // Hero Section
                    apiUrl = '/api/hero/multi-delete';
                    reloadTable = '#heroSectionTable';
                } else if (tableId === '#statsTable') { // Stats Section
                    apiUrl = '/api/stats/multi-delete';
                    reloadTable = '#statsTable';
                } else if (tableId === '#serviceTable') { // Service Section
                    apiUrl = '/api/service/multi-delete';
                    reloadTable = '#serviceTable';
                } else if (tableId === '#alternativeTable') { // Alternative Section
                    apiUrl = '/api/alternative/multi-delete';
                    reloadTable = '#alternativeTable';
                } else if (tableId === '#featureTable') { // Feature Section
                    apiUrl = '/api/feature/multi-delete';
                    reloadTable = '#featureTable';
                } else if (tableId === '#capabilityTable') { // Capabilities Section
                    apiUrl = '/api/capability/multi-delete';
                    reloadTable = '#capabilityTable';
                } else if (tableId === '#advanceCapabilityTable') { // Advance Capabilities Section
                    apiUrl = '/api/advance_capability/multi-delete';
                    reloadTable = '#advanceCapabilityTable';
                } else if (tableId === '#whyUsTable') { // Why Us Section
                    apiUrl = '/api/why_us/multi-delete';
                    reloadTable = '#whyUsTable';
                } else if (tableId === '#callToActionTable') { // Call To Action Section
                    apiUrl = '/api/call_to_action/multi-delete';
                    reloadTable = '#callToActionTable';
                } else if (tableId === '#portfolioTable') { // Portfolio Section
                    apiUrl = '/api/portfolio/multi-delete';
                    reloadTable = '#portfolioTable';
                } else if (tableId === '#pricingTable') { // Pricing Section
                    apiUrl = '/api/pricing/multi-delete';
                    reloadTable = '#pricingTable';
                } else if (tableId === '#teamsTable') { // Teams Section
                    apiUrl = '/api/teams/multi-delete';
                    reloadTable = '#teamsTable';
                } else if (tableId === '#cherrypikPagesTable') { // Cherrypik Pages Section
                    apiUrl = '/api/cherrypik_pages/multi-delete';
                    reloadTable = '#cherrypikPagesTable';
                } else if (tableId === '#headerTable') {
                    apiUrl = '/api/header/multi-delete';
                    reloadTable = '#headerTable';
                } else {
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
    // alert(`/api/${statusRoute}/active-count`);

    if (newStatus) {
        // Check how many are currently active
        $.ajax({
            url: `/api/${statusRoute}/active-count`, // An API route that returns count of active entries
            type: 'GET',
            success: function (response) {
                // alert(response.count);
                // alert(numberOfActive);
                if (response.count >= numberOfActive) {
                    // Limit reached, revert checkbox and notify
                    current.checked = false;
                    notify('error', 'Only ' + numberOfActive + ' entries can be active at a time.');
                } else {
                    // Proceed to toggle status
                    updateStatus(current, dis, table, statusRoute);
                }
            },
            error: function () {
                notify('error', 'Error checking active entries.');
                // Optionally revert checkbox
                current.checked = false;
            }
        });
    } else {
        // Deactivating, so just proceed
        updateStatus(current, dis, table, statusRoute);
    }
}


function updateStatus(current, dis, table, statusRoute) {
    let newStatus = current.checked;
    $.ajax({
        url: `/api/${statusRoute}/${dis}/toggle-status`,
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
    // alert(del_id);
    // alert(deleteRoute);
    // $(document).on('click', '#deleteCMSCategory', function() {
    // let cmsCategoryId = dis.data('id');
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

