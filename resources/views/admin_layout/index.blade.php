{{-- @extends('admin_layout.header')


@section('content') --}}
@include('admin_layout.header')

<body>
    <style>
        :root {
            --admin-navbar-height: 64px;
        }

        html, body {
            height: 100%;
        }

        body {
            min-height: 100vh;
        }

        .container-scroller {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container-fluid.page-body-wrapper {
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - var(--admin-navbar-height));
        }

        .main-panel {
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - var(--admin-navbar-height));
        }

        .content-wrapper {
            flex: 1 0 auto;
            min-height: 0;
        }

        .footer {
            margin-top: auto;
        }

        .app-picker-field {
            position: relative;
        }

        .app-picker-field .form-control {
            padding-right: 44px;
        }

        .app-picker-trigger {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            width: 30px;
            height: 30px;
            border: 0;
            border-radius: 6px;
            background: transparent;
            color: #6c757d;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 3;
        }

        .app-picker-trigger:hover,
        .app-picker-trigger:focus {
            background: #f3f5ff;
            color: #2f3b8f;
            outline: none;
        }
    </style>
    <div class="container-scroller">
    @include('admin_layout.navbar')
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
        <div class="main-panel">
            <div class="content-wrapper pb-0">

                @yield('content')
               
            </div>
            <!-- content-wrapper ends -->
            <!-- partial:partials/_footer.html -->
            @include('admin_layout.footer')
            <!-- partial -->
        </div>
        <!-- main-panel ends -->
    </div>
 </div>
    <link rel="stylesheet" href="{{ asset('assets/css/cherrypik-custom-css/custom.css') }}?v={{ filemtime(public_path('assets/css/cherrypik-custom-css/custom.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/jquery-ui/jquery-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-datepicker/bootstrap-datetimepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/datepicker.css') }}">

    {{-- <script src="{{ asset('assets/vendors/js/vendor.bundle.base.js') }}"></script> --}}
  <!-- Core JS Files -->

   <!-- <script src="{{ asset('assets/js/core/bootstrap.bundle.min.js') }}"></script> -->
  {{-- <script src="{{ asset('assets/js/plugins/perfect-scrollbar.jquery.min.js') }}"></script> --}}
    {{-- <script src="{{ asset('assets/js/plugins/chartjs.min.js') }}"></script> --}}
  <script src="{{ asset('assets/js/plugins/bootstrap-notify.js') }}"></script>
  {{-- <script src="{{ asset('assets/js/now-ui-dashboard.min.js?v=1.5.0') }}" type="text/javascript"></script> --}}
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
  {{-- <script src="{{ asset('js/datatables.js') }}"></script> --}}
  <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<!-- Plugin js for this page -->
<script src="{{ asset('assets/vendors/jquery-bar-rating/jquery.barrating.min.js') }}"></script>
<script src="{{ asset('assets/vendors/chart.js/Chart.min.js') }}"></script>
<script src="{{ asset('assets/js/adminJs/jquery.cookie.js') }}" type="text/javascript"></script>

    {{-- <link rel="stylesheet" href="{{ asset('assets/vendors/select2/select2.min.js')}}" /> --}}

<!-- End plugin js for this page -->
<script src="{{ asset('assets/js/adminJs/notify.js') }}"></script>
<!-- inject:js -->
<script src="{{ asset('assets/js/adminJs/off-canvas.js') }}"></script>
<script src="{{ asset('assets/js/adminJs/hoverable-collapse.js') }}"></script>
<script src="{{ asset('assets/js/adminJs/misc.js') }}"></script>
<script src="{{ asset('assets/js/adminJs/settings.js') }}?v={{ filemtime(public_path('assets/js/adminJs/settings.js')) }}"></script>
<script src="{{ asset('assets/js/adminJs/todolist.js') }}"></script>
<script src="{{ asset('js/common_js.js') }}?v={{ filemtime(public_path('js/common_js.js')) }}"></script>
<script src="{{ asset('assets/vendors/jquery-ui/jquery-ui.js') }}"></script>
<script src="{{ asset('assets/vendors/bootstrap-datepicker/bootstrap-datetimepicker.min.js') }}"></script>
<!-- endinject -->

<!-- Custom js for this page -->




  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
  

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    window.parseDisplayDate = function (value) {
        const raw = String(value || '').trim();
        if (!raw) return null;

        let match = raw.match(/^(\d{2})\/(\d{2})\/(\d{2}|\d{4})$/);
        if (match) {
            const yearValue = match[3];
            const year = yearValue.length === 2 ? (2000 + parseInt(yearValue, 10)) : parseInt(yearValue, 10);
            const date = new Date(year, parseInt(match[2], 10) - 1, parseInt(match[1], 10));
            return Number.isNaN(date.getTime()) ? null : date;
        }

        match = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (match) {
            const date = new Date(parseInt(match[1], 10), parseInt(match[2], 10) - 1, parseInt(match[3], 10));
            return Number.isNaN(date.getTime()) ? null : date;
        }

        return null;
    };

    window.parseDisplayDateTime = function (value) {
        const raw = String(value || '').trim();
        if (!raw) return null;

        let match = raw.match(/^(\d{2})\/(\d{2})\/(\d{2}|\d{4})\s+(\d{1,2}):(\d{2})(?:\s*(AM|PM|am|pm))?$/);
        if (match) {
            const yearValue = match[3];
            const year = yearValue.length === 2 ? (2000 + parseInt(yearValue, 10)) : parseInt(yearValue, 10);
            let hours = parseInt(match[4], 10);
            const minutes = parseInt(match[5], 10);
            const meridian = String(match[6] || '').toUpperCase();

            if (meridian === 'AM' || meridian === 'PM') {
                if (hours === 12) {
                    hours = meridian === 'AM' ? 0 : 12;
                } else if (meridian === 'PM') {
                    hours += 12;
                }
            }

            const date = new Date(year, parseInt(match[2], 10) - 1, parseInt(match[1], 10), hours, minutes);
            return Number.isNaN(date.getTime()) ? null : date;
        }

        match = raw.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/);
        if (match) {
            const date = new Date(parseInt(match[1], 10), parseInt(match[2], 10) - 1, parseInt(match[3], 10), parseInt(match[4], 10), parseInt(match[5], 10));
            return Number.isNaN(date.getTime()) ? null : date;
        }

        return null;
    };

    window.startOfToday = function () {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        return today;
    };

    window.isDisplayDateBeforeToday = function (value) {
        const parsedDate = window.parseDisplayDate(value);
        if (!parsedDate) {
            return false;
        }

        const normalizedDate = new Date(parsedDate);
        normalizedDate.setHours(0, 0, 0, 0);

        return normalizedDate < window.startOfToday();
    };

    window.isDisplayDateAfterToday = function (value) {
        const parsedDate = window.parseDisplayDate(value);
        if (!parsedDate) {
            return false;
        }

        const normalizedDate = new Date(parsedDate);
        normalizedDate.setHours(0, 0, 0, 0);

        return normalizedDate > window.startOfToday();
    };

    window.normalizeAadhaarDigits = function (value) {
        return String(value || '').replace(/\D/g, '').slice(0, 12);
    };

    window.formatAadhaarValue = function (value) {
        const digits = window.normalizeAadhaarDigits(value);
        const parts = [];

        if (digits.length > 0) {
            parts.push(digits.slice(0, Math.min(4, digits.length)));
        }
        if (digits.length > 4) {
            parts.push(digits.slice(4, Math.min(8, digits.length)));
        }
        if (digits.length > 8) {
            parts.push(digits.slice(8, Math.min(12, digits.length)));
        }

        return parts.join(' ');
    };

    window.isValidAadhaarNumber = function (value) {
        return window.normalizeAadhaarDigits(value).length === 12;
    };

    window.initAppDatePickers = function (scope) {
        const $scope = scope ? $(scope) : $(document);

        function formatDateInputValue(rawValue) {
            const digits = String(rawValue || '').replace(/\D/g, '').slice(0, 8);
            const parts = [];

            if (digits.length > 0) {
                parts.push(digits.slice(0, Math.min(2, digits.length)));
            }
            if (digits.length > 2) {
                parts.push(digits.slice(2, Math.min(4, digits.length)));
            }
            if (digits.length > 4) {
                parts.push(digits.slice(4, Math.min(8, digits.length)));
            }

            return parts.join('/');
        }

        function formatDateTimeInputValue(rawValue) {
            const normalized = String(rawValue || '').toUpperCase().replace(/[^0-9APM]/g, '');
            const digits = normalized.replace(/\D/g, '').slice(0, 12);
            const dateDigits = digits.slice(0, 8);
            const timeDigits = digits.slice(8, 12);
            const formattedDate = formatDateInputValue(dateDigits);
            const meridianToken = normalized.includes('PM') ? 'PM' : (normalized.includes('AM') ? 'AM' : '');

            if (!timeDigits.length) {
                return meridianToken ? (formattedDate + ' ' + meridianToken).trim() : formattedDate;
            }

            const timeParts = [];
            timeParts.push(timeDigits.slice(0, Math.min(2, timeDigits.length)));
            if (timeDigits.length > 2) {
                timeParts.push(timeDigits.slice(2, Math.min(4, timeDigits.length)));
            }

            const formattedTime = timeParts.join(':');
            return (formattedDate + ' ' + formattedTime + (meridianToken ? ' ' + meridianToken : '')).trim();
        }

        function getCurrentDisplayDateTime() {
            const now = new Date();
            const pad = function (value) {
                return String(value).padStart(2, '0');
            };

            const hours24 = now.getHours();
            const hours12 = hours24 % 12 || 12;
            const meridian = hours24 >= 12 ? 'PM' : 'AM';

            return [
                pad(now.getDate()),
                pad(now.getMonth() + 1),
                now.getFullYear()
            ].join('/') + ' ' + [pad(hours12), pad(now.getMinutes())].join(':') + ' ' + meridian;
        }

        function getDisplayDatePart(rawValue) {
            const raw = String(rawValue || '').trim();
            const match = raw.match(/^(\d{2}\/\d{2}\/\d{4})(?:\s+(\d{1,2}:\d{2}(?:\s*(?:AM|PM|am|pm))?))?$/);
            return match ? match[1] : '';
        }

        function getDisplayTimePart(rawValue, alwaysUseCurrentTime) {
            if (alwaysUseCurrentTime) {
                return getCurrentDisplayDateTime().split(' ').slice(1).join(' ');
            }

            const raw = String(rawValue || '').trim();
            const match = raw.match(/^(\d{2}\/\d{2}\/\d{4})(?:\s+(\d{1,2}:\d{2}(?:\s*(?:AM|PM|am|pm))?))?$/);
            if (match && match[2]) {
                return String(match[2]).toUpperCase();
            }

            return getCurrentDisplayDateTime().split(' ').slice(1).join(' ');
        }

        function ensurePickerTrigger($input, type) {
            if (!$input.parent().hasClass('app-picker-field')) {
                $input.wrap('<div class="app-picker-field"></div>');
            }

            const $wrapper = $input.parent();
            if ($wrapper.find('.app-picker-trigger').length) {
                return;
            }

            const pickerLabel = type === 'datetime-now' ? 'Open date and time picker' : 'Open date picker';
            const $trigger = $('<button type="button" class="app-picker-trigger" aria-label="' + pickerLabel + '" title="' + pickerLabel + '"><i class="fa fa-calendar-alt" aria-hidden="true"></i></button>');

            $trigger.on('click', function (event) {
                event.preventDefault();

                if (type === 'datetime-now') {
                    $input.val(getCurrentDisplayDateTime());
                    $input.data('openFromTrigger', true);

                    if ($input.hasClass('hasDatepicker')) {
                        $input.datepicker('show');
                    }

                    return;
                }

                $input.data('openFromTrigger', true);
                if ($input.hasClass('hasDatepicker')) {
                    $input.datepicker('show');
                }
            });

            $wrapper.append($trigger);
        }

        function positionDatepickerNearTrigger(input, inst) {
            setTimeout(function () {
                const $input = $(input);
                const $trigger = $input.closest('.app-picker-field').find('.app-picker-trigger');
                const $widget = inst && inst.dpDiv ? inst.dpDiv : $('#ui-datepicker-div');

                if (!$trigger.length || !$widget.length) {
                    return;
                }

                const triggerOffset = $trigger.offset();
                const widgetWidth = $widget.outerWidth();
                const triggerWidth = $trigger.outerWidth();
                const top = triggerOffset.top + $trigger.outerHeight() + 6;
                const left = Math.max(0, triggerOffset.left + triggerWidth - widgetWidth);

                $widget.css({
                    top: top + 'px',
                    left: left + 'px'
                });
            }, 0);
        }

        function isFutureOnlyDateField($input) {
            return $input.is('[data-not-past="true"]') || /expiry_date/i.test(String($input.attr('name') || ''));
        }

        function isPastOnlyDateField($input) {
            return $input.is('[data-not-future="true"]') || /date_of_birth/i.test(String($input.attr('name') || ''));
        }

        function formatTodayDisplayDate() {
            const today = new Date();
            const day = String(today.getDate()).padStart(2, '0');
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const year = String(today.getFullYear());
            return `${day}/${month}/${year}`;
        }

        window.formatTodayDisplayDate = formatTodayDisplayDate;

        function attachNoPastDateValidation($input) {
            if ($input.data('noPastDateBound')) {
                return;
            }

            const fieldLabel = $input.data('field-label') || $input.closest('.form-group').find('label').first().text().replace('*', '').trim() || 'Date';

            $input.data('noPastDateBound', true);

            $input.on('change blur', function () {
                const currentValue = this.value;
                if (!currentValue) {
                    return;
                }

                if (window.isDisplayDateBeforeToday(currentValue)) {
                    alert(fieldLabel + ' cannot be before ' + formatTodayDisplayDate());
                    this.value = '';
                    $(this).trigger('focus');
                }
            });
        }

        function attachNoFutureDateValidation($input) {
            if ($input.data('noFutureDateBound')) {
                return;
            }

            const fieldLabel = $input.data('field-label') || $input.closest('.form-group').find('label').first().text().replace('*', '').trim() || 'Date';

            $input.data('noFutureDateBound', true);

            $input.on('change blur', function () {
                const currentValue = this.value;
                if (!currentValue) {
                    return;
                }

                if (window.isDisplayDateAfterToday(currentValue)) {
                    alert(fieldLabel + ' cannot be after ' + formatTodayDisplayDate());
                    this.value = '';
                    $(this).trigger('focus');
                }
            });
        }

        $scope.find('.app-date-picker').each(function () {
            const $input = $(this);
            const restrictPastDates = isFutureOnlyDateField($input);
            const restrictFutureDates = isPastOnlyDateField($input);
            if ($input.hasClass('hasDatepicker')) {
                if (restrictPastDates) {
                    $input.datepicker('option', 'minDate', 0);
                    attachNoPastDateValidation($input);
                }
                if (restrictFutureDates) {
                    $input.datepicker('option', 'maxDate', 0);
                    attachNoFutureDateValidation($input);
                }
                ensurePickerTrigger($input, 'date');
                return;
            }

            $input.attr({
                autocomplete: 'off',
                maxlength: 10,
                placeholder: 'DD/MM/YYYY'
            }).datepicker({
                dateFormat: 'dd/mm/yy',
                changeMonth: true,
                changeYear: true,
                showButtonPanel: true,
                yearRange: '1900:2100',
                minDate: restrictPastDates ? 0 : null,
                maxDate: restrictFutureDates ? 0 : null,
                beforeShow: function (input, inst) {
                    const $currentInput = $(input);
                    if (!$currentInput.data('openFromTrigger')) {
                        return false;
                    }

                    positionDatepickerNearTrigger(input, inst);
                },
                onChangeMonthYear: function (_year, _month, inst) {
                    positionDatepickerNearTrigger(this, inst);
                },
                onClose: function () {
                    $(this).removeData('openFromTrigger');
                }
            });

            ensurePickerTrigger($input, 'date');
            if (restrictPastDates) {
                attachNoPastDateValidation($input);
            }
            if (restrictFutureDates) {
                attachNoFutureDateValidation($input);
            }

            $input.on('input', function () {
                const formattedValue = formatDateInputValue(this.value);
                if (this.value !== formattedValue) {
                    this.value = formattedValue;
                }
            });
        });

        $scope.find('.app-datetime-picker').each(function () {
            const $input = $(this);
            const useCurrentDateTimeOnly = $input.is('[data-default-now="true"]');

            if ($input.hasClass('hasDatepicker')) {
                ensurePickerTrigger($input, useCurrentDateTimeOnly ? 'datetime-now' : 'date');
                return;
            }

            $input.attr({
                autocomplete: 'off',
                maxlength: 19,
                placeholder: 'DD/MM/YYYY hh:mm AM'
            }).datepicker({
                dateFormat: 'dd/mm/yy',
                changeMonth: true,
                changeYear: true,
                showButtonPanel: true,
                yearRange: '1900:2100',
                beforeShow: function (input, inst) {
                    const $currentInput = $(input);
                    if (!$currentInput.data('openFromTrigger')) {
                        return false;
                    }

                    if ($currentInput.is('[data-default-now="true"]')) {
                        const currentTime = getCurrentDisplayDateTime().split(' ')[1];
                        const currentDate = getDisplayDatePart($currentInput.val()) || getCurrentDisplayDateTime().split(' ')[0];
                        $currentInput.val(currentDate + ' ' + currentTime);
                    }

                    positionDatepickerNearTrigger(input, inst);
                },
                onSelect: function (selectedDate) {
                    const timePart = getDisplayTimePart(this.value, $(this).is('[data-default-now="true"]'));
                    this.value = selectedDate + ' ' + timePart;
                    $(this).trigger('change');
                },
                onChangeMonthYear: function (_year, _month, inst) {
                    positionDatepickerNearTrigger(this, inst);
                },
                onClose: function () {
                    $(this).removeData('openFromTrigger');
                }
            });

            ensurePickerTrigger($input, useCurrentDateTimeOnly ? 'datetime-now' : 'date');

            if ($input.is('[data-default-now="true"]') && !$input.val().trim()) {
                $input.val(getCurrentDisplayDateTime());
            } else if ($input.is('[data-default-now="true"]')) {
                $input.val(getCurrentDisplayDateTime());
            }

            $input.on('input', function () {
                const formattedValue = formatDateTimeInputValue(this.value);
                if (this.value !== formattedValue) {
                    this.value = formattedValue;
                }
            });
        });

        $scope.find('input[data-aadhaar-input="true"], input[name="adher_no"], input[name="father_aadhaar_number"], input[name="mother_aadhaar_number"]').each(function () {
            const $input = $(this);

            $input.attr({
                inputmode: 'numeric',
                maxlength: 16,
                autocomplete: 'off',
                placeholder: 'xxxx xxxx xxxx'
            });

            const formattedValue = window.formatAadhaarValue($input.val());
            if ($input.val() !== formattedValue) {
                $input.val(formattedValue);
            }

            if ($input.data('aadhaarBound')) {
                return;
            }

            $input.data('aadhaarBound', true);
            $input.on('input blur', function () {
                const nextValue = window.formatAadhaarValue(this.value);
                if (this.value !== nextValue) {
                    this.value = nextValue;
                }
            });
        });
    };

    document.addEventListener('DOMContentLoaded', function() {
        window.initAppDatePickers(document);
        const token = localStorage.getItem('token');
        const tokenExpiredShown = sessionStorage.getItem('tokenExpiredShown');
 
        if (token && isPageReload()) {
            if (isTokenExpired(token)) {
                refreshAuthToken(false);
            } else {
                refreshAuthToken(false);
                sessionStorage.removeItem('tokenExpiredShown');
            }
        }
    });
 
    function isPageReload() {
        return performance.navigation.type === performance.navigation.TYPE_RELOAD;
    }
 
    function isTokenExpired(token) {
        try {
            const payload = JSON.parse(atob(token.split('.')[1]));
            const currentTime = Date.now() / 1000;
            console.log('Token Expiration Time:', payload.exp);
            console.log('Current Time:', currentTime);
            return payload.exp < currentTime;
        } catch (e) {
            console.error('Error decoding token:', e);
            return true;
        }
    }
 
    function refreshAuthToken(showSuccessMessage = true) {
        fetch('{{ route('api.refreshToken') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('token')
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Invalid token');
            }
            return response.json();
        })
        .then(data => {
            if (data.token) {
                localStorage.setItem('token', data.token);
                if (showSuccessMessage) {
                    Swal.fire('Success', 'Token refreshed Successfully', 'success');
                }
            } else {
                Swal.fire('Error', 'Could not refresh token', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            localStorage.removeItem('token');
            deleteAllCookies();
            sessionStorage.clear();
            window.location.href = '{{ route("login") }}';
        });
    }
   
    function deleteAllCookies() {
        const cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i];
            const eqPos = cookie.indexOf('=');
            const name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
            document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/';
        }
    }
 
    </script>
    

    
</body>

<!-- Toastr CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
@include('partials.toaster')
</html>
