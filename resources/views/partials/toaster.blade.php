@if(Session::has('success'))
    <script>
        toastr.success("{{ Session::get('success') }}", "Success");
    </script>
@endif

@if(Session::has('error'))
    <script>
        toastr.error("{{ Session::get('error') }}", "Error");
    </script>
@endif

@if(Session::has('info'))
    <script>
        toastr.info("{{ Session::get('info') }}", "Info");
    </script>
@endif

@if(Session::has('warning'))
    <script>
        toastr.warning("{{ Session::get('warning') }}", "Warning");
    </script>
@endif
