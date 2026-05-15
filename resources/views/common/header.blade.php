<!DOCTYPE html>
<html lang="en"></html>

<head>
  <meta charset="utf-8" />
  <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('assets/img/apple-icon.png') }}">
  {{-- <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}"> --}}
      <link rel="shortcut icon" type="images/png" href="{{ asset('assets/images/fav-icon/cherrypikFavicon.png') }}">

  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Dashboard</title>
  <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no' name='viewport' />

  <!-- Fonts and icons -->
  <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

  <!-- CSS Files -->
  <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" />
  <link href="{{ asset('assets/css/now-ui-dashboard.css?v=1.5.0') }}" rel="stylesheet" />
  <link href="{{ asset('assets/css/custom-styles.css') }}" rel="stylesheet" />
  <link href="{{ asset('assets/css/magazines.css') }}" rel="stylesheet" />
  <link href="{{ asset('assets/css/categories.css') }}" rel="stylesheet" />

  <link href="{{ asset('assets/css/blade.css') }}" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('assets/css/home_pages.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/cherrypik-custom-css/custom.css') }}?v={{ filemtime(public_path('assets/css/cherrypik-custom-css/custom.css')) }}">

  <!-- DataTables CSS -->
  <link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet" />

  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  <!-- JavaScript Libraries -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
  <!-- <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script> -->
  <script src="{{ asset('js/ckeditor/ckeditor.js') }}"></script>
  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfobject/2.2.7/pdfobject.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
  {{-- <script src="{{ asset('js/datatables.js') }}"></script> --}}
  <script src="{{ asset('assets/js/custom.js') }}"></script>
  <script src="{{ asset('js/common_js.js') }}?v={{ filemtime(public_path('js/common_js.js')) }}"></script>

  <style>
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #aaa;
        border-radius: 3px;
        padding: 9px;
        background-color: transparent;
        margin-left: 3px;
        margin-right: 2px;
    }

    .dataTables_wrapper .dataTables_info {
        clear: none;
        float: left;
        padding-top: 0.755em;
        margin-left: 10px;
    }
    .dataTables_wrapper .dataTables_length {
        float: left;
        margin-top: 9px;
    }
  </style>
</head>
