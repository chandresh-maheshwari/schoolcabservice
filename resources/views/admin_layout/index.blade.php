{{-- @extends('admin_layout.header')


@section('content') --}}
@include('admin_layout.header')
@include('admin_layout.navbar')

<body>
    <div class="container-scroller">
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
<script src="{{ asset('assets/js/adminJs/settings.js') }}"></script>
<script src="{{ asset('assets/js/adminJs/todolist.js') }}"></script>
<script src="{{ asset('js/common_js.js') }}?v={{ filemtime(public_path('js/common_js.js')) }}"></script>
<!-- endinject -->

<!-- Custom js for this page -->




  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
  

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
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
