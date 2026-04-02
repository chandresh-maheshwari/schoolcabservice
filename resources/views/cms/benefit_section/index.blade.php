{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
<style>
    #benefitSectionTable {
        width: 100% !important;
        table-layout: fixed;
    }

    #benefitSectionTable th,
    #benefitSectionTable td {
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: anywhere;
        vertical-align: top;
    }

    #benefitSectionTable th:nth-child(1),
    #benefitSectionTable td:nth-child(1) {
        width: 90px;
    }

    #benefitSectionTable th:nth-child(2),
    #benefitSectionTable td:nth-child(2) {
        width: 18%;
    }

    #benefitSectionTable th:nth-child(3),
    #benefitSectionTable td:nth-child(3) {
        width: 42%;
        max-width: 420px;
    }

    #benefitSectionTable th:nth-child(4),
    #benefitSectionTable td:nth-child(4) {
        width: 140px;
        white-space: nowrap !important;
    }

    #benefitSectionTable .benefit-shortdesc-wrapper {
        display: block;
        width: 100%;
        max-width: 420px;
    }

    #benefitSectionTable .benefit-shortdesc-content {
        white-space: normal;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    #benefitSectionTable .benefit-shortdesc-content.is-collapsed {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    #benefitSectionTable .benefit-shortdesc-content > * {
        margin-bottom: 0;
    }

    #benefitSectionTable .benefit-shortdesc-content br + br {
        display: none;
    }

    #benefitSectionTable .benefit-shortdesc-toggle {
        margin-top: 6px;
        padding: 0;
        border: 0;
        background: transparent;
        color: #0d6efd;
        font-size: 14px;
        font-weight: 700;
        text-decoration: underline;
        cursor: pointer;
    }
</style>
{{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: hsl(226, 30%, 92%);">
        <div class="card-header" style="background-color: #a9b5df; color: white; padding: 10px 15px 1px;">
            <h2 class="about-us-listing-header" style="text-align: center; font-size: 1.5em; margin-bottom: 6px; font-weight: bold; color: #2d336b;">About Us Listing</h2>
        </div> --}}
        <div class="section-breadcrumb">
    <div class="breadcrumb-wrapper pb-0">
        <div class="container">
            <nav aria-label="breadcrumb-nav">
                <ol class="breadcrumb breadcrumb-style-2 my-20">
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Benefit Section </li>
                </ol>
            </nav>
        </div>
    </div>
</div>

        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h4 class="about-us-listing-header">Benefit Section Listing</h4>
                </div>
        <div class="card-body">
            @php
                $DatbleVariable['TableHader'] = '';
                $DatbleVariable['TableId'] = 'benefitSectionTable';
                $DatbleVariable['TableCreateRoute'] = 'benefitSection.create';
                $DatbleVariable['TableDeleteRoute'] = '';
                $DatbleVariable['TableRestoreRoute'] = '';

                $DatbleVariable['TableColumnName'] = ['Sr No.', 'Name','Short Description','Actions'];
                $DatbleVariable['rightActionButton'] = ['createButton'];

            @endphp
            <x-datatable :tablevar="$DatbleVariable" class="w-100" />
        </div>
    </div>
</div>

{{-- <script src="{{ asset('js/datatables.js') }}"></script> --}}
<script src="{{ asset('js/datatables_cherrypik.js') }}?v={{ filemtime(public_path('js/datatables_cherrypik.js')) }}"></script>

<script>
    $(document).ready(function() {
        let tableId = "#benefitSectionTable";
        let route = '{{ route('benefitSection.List') }}';
        let method = "POST";
        let leftActionButton = true;
        let searching = true;
        let pagination = true;
        let distance = null;


        DatatableRenderFunction(
            tableId,
            route,
            method,
            leftActionButton,
            searching,
            distance,
            location,
            lenghtDropdown = true,
            bottomInfo = true,
            pagination,
            multiDelete = true,
            deleteRoute = "benefitSection",
            numberOfActivePost = 5,
        );
    });

    $(document)
        .off('click.benefitShortDescToggle', '#benefitSectionTable .benefit-shortdesc-toggle')
        .on('click.benefitShortDescToggle', '#benefitSectionTable .benefit-shortdesc-toggle', function() {
            const button = this;
            const wrapper = button.closest('.benefit-shortdesc-wrapper');
            if (!wrapper) return;

            const content = wrapper.querySelector('.benefit-shortdesc-content');
            if (!content) return;

            const isExpanded = wrapper.getAttribute('data-expanded') === 'true';
            wrapper.setAttribute('data-expanded', isExpanded ? 'false' : 'true');
            content.classList.toggle('is-collapsed', isExpanded);
            button.textContent = isExpanded ? 'Read More' : 'Read Less';
            button.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
        });
</script>
@endsection
