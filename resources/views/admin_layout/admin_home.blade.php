{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Dashboard</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
<div class="container-fluid">
        <div class="row">
            <h1>WelCome Cherrypik Website</h1>
        </div>
        </div>
    {{-- <div class="container-fluid">
        <!-- First Row -->
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0"><a href="{{ route('magazines.index') }}" class="card_link_custom">Total Magazines</a></h6>
                                <h2 class="mt-2 mb-0"><span id="totalMagazines">--</span></h2>
                            </div>
                            <div class="bg-primary rounded-circle p-3">
                                <i class="la la-book text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0"><a href="{{ route('users.index') }}" class="card_link_custom">Total Users</a></h6>
                                <h2 class="mt-2 mb-0"><span id="totalUsers">--</span></h2>
                            </div>
                            <div class="bg-success rounded-circle p-3">
                                <i class="la la-users text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0"><a href="{{ route('blogs.index') }}" class="card_link_custom">Total Blogs</a></h6>
                                <h2 class="mt-2 mb-0"><span id="totalBlogs">--</span></h2>
                            </div>
                            <div class="bg-info rounded-circle p-3">
                                <i class="la la-file-text text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row -->
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0"><a href="{{ route('faqs.index') }}" class="card_link_custom">Total FAQs</a></h6>
                                <h2 class="mt-2 mb-0"><span id="totalFaqs">--</span></h2>
                            </div>
                            <div class="bg-warning rounded-circle p-3">
                                <i class="la la-question-circle text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0"><a href="{{ route('categories.index') }}" class="card_link_custom">Total Categories</a></h6>
                                <h2 class="mt-2 mb-0"><span id="totalCategories">--</span></h2>
                            </div>
                            <div class="bg-danger rounded-circle p-3">
                                <i class="la la-tags text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0"><a href="{{ route('writers.index') }}" class="card_link_custom">Total Writers</a></h6>
                                <h2 class="mt-2 mb-0"><span id="totalWriters">--</span></h2>
                            </div>
                            <div class="bg-secondary rounded-circle p-3">
                                <i class="la la-pencil text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Third Row -->
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0"><a href="{{ route('quotes.index') }}" class="card_link_custom">Total Quotes</a></h6>
                                <h2 class="mt-2 mb-0"><span id="totalQuotes">--</span></h2>
                            </div>
                            <div class="bg-dark rounded-circle p-3">
                                <i class="la la-quote-left text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0"><a href="{{ route('contact_messages.index') }}" class="card_link_custom">Contact Messages</a></h6>
                                <h2 class="mt-2 mb-0"><span id="totalContactMessages">--</span></h2>
                            </div>
                            <div class="bg-primary rounded-circle p-3">
                                <i class="la la-envelope text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0"><a href="{{ route('founders.index') }}" class="card_link_custom">Founders</a></h6>
                                <h2 class="mt-2 mb-0"><span id="totalFounders">--</span></h2>
                            </div>
                            <div class="bg-primary rounded-circle p-3">
                                <i class="la la-users-cog text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Pending Blogs Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Pending Blogs List</h4>
                    </div>
                    <div class="card-body">
                    <div class="table-responsive no-horizontal-scroll">
                            <table class="table table-hover" id="pendingBlogsTable">
                                <thead>
                                    <tr>
                                        <th class="fw-bold">S.No</th>
                                        <th class="fw-bold">Title</th>
                                        <th class="fw-bold">Author</th>
                                        <th class="fw-bold">Category</th>
                                        <th class="fw-bold">Created Date</th>
                                        <th class="fw-bold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                        <!-- DataTable will populate rows including the new Actions column -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
    {{-- <script src="{{ asset('js/datatables.js') }}"></script> --}}
    <script>
    // document.addEventListener('DOMContentLoaded', function() {
    //     fetch('/api/writers/all-counts')
    //         .then(response => response.json())
    //         .then(data => {
    //             if (data) {
    //                 if (data.magazine_count !== undefined) {
    //                     document.getElementById('totalMagazines').textContent = data.magazine_count;
    //                 }
    //                 if (data.blog_count !== undefined) {
    //                     document.getElementById('totalBlogs').textContent = data.blog_count;
    //                 }
    //                 if (data.writers !== undefined) {
    //                     document.getElementById('totalWriters').textContent = data.writers;
    //                 }
    //                 if (data.quote_count !== undefined) {
    //                     document.getElementById('totalQuotes').textContent = data.quote_count;
    //                 }
    //                 if (data.user_count !== undefined) {
    //                     document.getElementById('totalUsers').textContent = data.user_count;
    //                 }
    //                 if (data.faq_count !== undefined) {
    //                     document.getElementById('totalFaqs').textContent = data.faq_count;
    //                 }
    //                 if (data.category_count !== undefined) {
    //                     document.getElementById('totalCategories').textContent = data.category_count;
    //                 }
    //                 if (data.contact_message_count !== undefined) {
    //                     document.getElementById('totalContactMessages').textContent = data.contact_message_count;
    //                 }
    //                 if (data.founder_count !== undefined) {
    //                     document.getElementById('totalFounders').textContent = data.founder_count;
    //                 }
    //                 if (data.authors !== undefined) {
    //                     document.getElementById('totalAuthors').textContent = data.authors;
    //                 }
    //             }
    //         })
    //         .catch(error => {
    //             console.error('Error fetching counts:', error);
    //         });

    
    // });
    </script>

    <script>

   
        
</script>

@endsection



<!-- Pending Blog View Modal -->
{{-- <div clCopyrightCopyright --}}
