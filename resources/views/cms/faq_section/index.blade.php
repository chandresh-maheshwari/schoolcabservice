{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
<style>
    .table-responsive,
    .table-wrapper,
    .section-wrapper,
    #faqSectionTable_wrapper {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden !important;
    }

    #faqSectionTable {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed;
    }

    #faqSectionTable_wrapper table.dataTable {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
    }

    #faqSectionTable th,
    #faqSectionTable td {
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: anywhere;
        vertical-align: top;
    }

    #faqSectionTable th:nth-child(1),
    #faqSectionTable td:nth-child(1) {
        width: 90px;
    }

    #faqSectionTable th:nth-child(2),
    #faqSectionTable td:nth-child(2) {
        width: 24%;
    }

    #faqSectionTable th:nth-child(3),
    #faqSectionTable td:nth-child(3) {
        width: 34%;
        max-width: 340px;
    }

    #faqSectionTable th:nth-child(4),
    #faqSectionTable td:nth-child(4) {
        width: 140px;
        white-space: nowrap !important;
    }

    #faqSectionTable .faq-question-cell,
    #faqSectionTable .faq-answer-content {
        white-space: normal;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    #faqSectionTable .faq-answer-content > * {
        margin-bottom: 0;
    }

    #faqSectionTable .faq-answer-wrapper {
        display: block;
        width: 100%;
        max-width: 340px;
    }

    #faqSectionTable .faq-answer-content.is-collapsed {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    #faqSectionTable .faq-answer-toggle {
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

    #faqSectionTable .faq-answer-content p {
        margin: 0;
    }

    #faqSectionTable .faq-answer-content br + br {
        display: none;
    }

    #faqSectionTable_wrapper .bottom,
    #faqSectionTable_wrapper .dataTables_paginate,
    #faqSectionTable_wrapper .dataTables_info,
    #faqSectionTable_wrapper .dataTables_length {
        max-width: 100%;
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
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">FAQ Section </li>
                </ol>
            </nav>
        </div>
    </div>
</div>

        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h4 class="about-us-listing-header">FAQ Section Listing</h4>
                </div>
        <div class="card-body">
            @php
                $DatbleVariable['TableHader'] = '';
                $DatbleVariable['TableId'] = 'faqSectionTable';
                $DatbleVariable['TableCreateRoute'] = 'faqSection.create';
                $DatbleVariable['TableDeleteRoute'] = '';
                $DatbleVariable['TableRestoreRoute'] = '';

                $DatbleVariable['TableColumnName'] = ['Sr No.', 'Question','Answer','Actions'];
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
        let tableId = "#faqSectionTable";
        let route = '{{ route('faqSection.List') }}';
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
            deleteRoute = "faqSection",
            numberOfActivePost = 5,
        );
    });

    $(document)
        .off('click.faqAnswerToggle', '#faqSectionTable .faq-answer-toggle')
        .on('click.faqAnswerToggle', '#faqSectionTable .faq-answer-toggle', function() {
            const button = this;
            const wrapper = button.closest('.faq-answer-wrapper');
            if (!wrapper) return;

            const content = wrapper.querySelector('.faq-answer-content');
            if (!content) return;

            const isExpanded = wrapper.getAttribute('data-expanded') === 'true';
            wrapper.setAttribute('data-expanded', isExpanded ? 'false' : 'true');
            content.classList.toggle('is-collapsed', isExpanded);
            button.textContent = isExpanded ? 'Read More' : 'Read Less';
            button.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
        });
</script>
@endsection
