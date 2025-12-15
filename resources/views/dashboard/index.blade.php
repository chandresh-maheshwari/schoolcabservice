@include('common.header')
@include('common.sidebar')

<body class="" style="background-color: #FFF2F2 !important;">
  <!-- <div class="wrapper"> -->
    <div class="main-panel" id="main-panel" style="background-color: #A9B5DF !important;">
      @include('common.navbar')
      <div class="content mt-5" style="background-color: #FFF2F2 !important; padding: 1px 0px 30px;">
        <div class="content">
          @yield('content')
        </div>
      </div>
    </div>
  <!-- </div> -->
  <!-- Core JS Files -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- <script src="{{ asset('assets/js/core/bootstrap.bundle.min.js') }}"></script> -->
  <script src="{{ asset('assets/js/plugins/perfect-scrollbar.jquery.min.js') }}"></script>
  <!-- <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_KEY_HERE"></script> -->
  <script src="{{ asset('assets/js/plugins/chartjs.min.js') }}"></script>
  <script src="{{ asset('assets/js/plugins/bootstrap-notify.js') }}"></script>
  <script src="{{ asset('assets/js/now-ui-dashboard.min.js?v=1.5.0') }}" type="text/javascript"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
  <script src="{{ asset('js/datatables.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

  <script>
    $(document).ready(function() {
      // demo.initDashboardPageCharts();
    });

    document.addEventListener('DOMContentLoaded', function() {
      const token = localStorage.getItem('token');
      
      // Check if the user is logged in by verifying the presence of a token
      if (token) {
        if (isTokenExpired(token)) {
          Swal.fire({
            title: 'Token Expired',
            text: 'Your session has expired. Please refresh your token.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Refresh',
            cancelButtonText: 'Logout'
          }).then((result) => {
            if (result.isConfirmed) {
              refreshAuthToken();
            } else {
              // Handle logout
              localStorage.removeItem('token');
              window.location.href = '/admin/login';
            }
          });
        } else {
          // Optionally refresh token on page load if not expired
          refreshAuthToken(false);
        }
      }

      // Show login success message if applicable
      if (localStorage.getItem('login_success') === 'true') {
        Swal.fire({
          title: 'Success!',
          text: 'You have Successfully logged in.',
          icon: 'success',
          confirmButtonText: 'OK'
        }).then(() => {
          localStorage.removeItem('login_success');
        });
      }
    });
    
    function isTokenExpired(token) {
      const payload = JSON.parse(atob(token.split('.')[1]));
      return payload.exp < Date.now() / 1000;
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
        deleteAllCookies();
        sessionStorage.clear();
        // Swal.fire({
        //     title: 'Invalid Token',
        //     text: 'Your token is invalid. Please log in again.',
        //     icon: 'error',
        //     confirmButtonText: 'OK'
        // }).then(() => {
        //     localStorage.removeItem('token');
        //     window.location.href = '/admin/login';
        // });
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
  <style>
    .dataTables_wrapper .dataTables_processing {
      display: none !important;
    }

    .cke_notification_warning {
    display: none !important;
}
.styled-form .form-control-file {
    background-color: #2d336b;
    color: #fff;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #2d336b !important;
    color: white !important;
}

#edit {
    background-color: #2d336b !important;
}

#submitBtn {
    background-color: #2d336b !important;
    color: white !important;
}

#cancelBtn {
    background-color: #7886c7 !important;
    color: white !important;
}

#uploadImageBtn {
    background-color: #2d336b !important;
    color: white !important;
}

#uploadPdfBtn {
    background-color: #2d336b !important;
    color: white !important;
}

.form-control {
  border-radius: 4px !important;
}

  </style>
  <link href="{{ asset('assets/css/custom-styles.css') }}" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
  
</body>
</html>