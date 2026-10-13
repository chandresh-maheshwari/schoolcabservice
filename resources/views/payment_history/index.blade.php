@extends('admin_layout.index')

@section('content')
    <style>
        #paymentHistoryTable {
            table-layout: fixed;
            width: 100% !important;
        }

        #paymentHistoryTable th,
        #paymentHistoryTable td {
            white-space: normal !important;
            overflow-wrap: anywhere;
            vertical-align: middle;
        }

        #paymentHistoryTable th:nth-child(1) { width: 5% !important; }
        #paymentHistoryTable th:nth-child(2) { width: 10% !important; }
        #paymentHistoryTable th:nth-child(3) { width: 9% !important; }
        #paymentHistoryTable th:nth-child(4) { width: 16% !important; }
        #paymentHistoryTable th:nth-child(5) { width: 6% !important; }
        #paymentHistoryTable th:nth-child(6) { width: 7% !important; }
        #paymentHistoryTable th:nth-child(7) { width: 8% !important; }
        #paymentHistoryTable th:nth-child(8) { width: 6% !important; }
        #paymentHistoryTable th:nth-child(9) { width: 7% !important; }
        #paymentHistoryTable th:nth-child(10) { width: 12% !important; }
        #paymentHistoryTable th:nth-child(11) { width: 9% !important; }
        #paymentHistoryTable th:nth-child(12) { width: 5% !important; }
    </style>

    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item">
                            <a class="breadcrumbLink" href="{{ request()->route('schoolSlug') ? route('school.dashboard', ['schoolSlug' => request()->route('schoolSlug')]) : route('admin_layout.index') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Payment History</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-listing-header">Payment History Listing</h4>
            </div>

            <div class="card-body">
                @php
                    $DatbleVariable['TableHader'] = '';
                    $DatbleVariable['TableId'] = 'paymentHistoryTable';
                    $DatbleVariable['TableCreateRoute'] = '';
                    $DatbleVariable['TableDeleteRoute'] = '';
                    $DatbleVariable['TableRestoreRoute'] = '';
                    $DatbleVariable['TableColumnName'] = [
                        'Sr No.',
                        'School Name',
                        'Child Name',
                        'Parent Name',
                        'Package',
                        'Amount',
                        'Payment Mode',
                        'Status',
                        'Receipt No',
                        'Reference No',
                        'Paid At',
                        'Actions',
                    ];
                    $DatbleVariable['rightActionButton'] = ['toolbarSpacer'];
                @endphp

                <x-datatable :tablevar="$DatbleVariable" class="w-100" />
            </div>
        </div>
    </div>

    <script src="{{ asset('js/datatables_cherrypik.js') }}?v={{ filemtime(public_path('js/datatables_cherrypik.js')) }}"></script>

    <script>
        $(document).ready(function() {
            DatatableRenderFunction(
                '#paymentHistoryTable',
                '{{ route('paymentHistory.list') }}',
                'POST',
                true,
                true,
                null,
                location,
                true,
                true,
                true,
                true,
                'paymentHistory',
                5
            );
        });
    </script>
@endsection
