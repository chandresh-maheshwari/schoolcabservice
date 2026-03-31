@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')
    @php
        $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
        $schoolSlug = request()->route('schoolSlug');
        $isSchoolPanel = filled($schoolSlug) && is_string($routeName) && str_starts_with($routeName, 'school.');
        $panelParams = $isSchoolPanel ? ['schoolSlug' => $schoolSlug] : [];
        $cancelRoute = route($isSchoolPanel ? 'school.child.index' : 'child.index', $panelParams);
        $latestPayment = !empty($currentSubscription) ? $currentSubscription->payments->first() : null;
        $currentSubscriptionExpiresAt = !empty($currentSubscription?->expires_at)
            ? \Illuminate\Support\Carbon::parse($currentSubscription->expires_at)
            : null;
        $currentSubscriptionStatus = !empty($currentSubscription)
            ? ($currentSubscriptionExpiresAt && $currentSubscriptionExpiresAt->isPast() ? 'expired' : ($currentSubscription->status ?: '-'))
            : null;
        $prefillPaidAt = now()->format('Y-m-d\TH:i');
    @endphp

    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item">
                            <a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active">
                            Cash Subscription
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    @include('child.partials.module_tabs', [
        'activeTab' => 'subscription',
        'entityIds' => [
            'child' => $selectedChildId ?? request('child_id'),
            'parent' => request('parent_id'),
            'subscription' => $currentSubscription->id ?? request('subscription_id'),
        ],
    ])

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">
                    {{ !empty($currentSubscription) ? 'Subscription Details' : 'Cash Subscription' }}
                </h4>
            </div>

            <div class="card-body">
                @if (!empty($currentSubscription))
                    <div class="alert alert-info">
                        Current subscription:
                        {{ ucfirst((string) $currentSubscription->service_type) }} |
                        {{ $currentSubscription->package_type ?: '-' }} |
                        {{ ucfirst((string) $currentSubscriptionStatus) }} |
                        Starts {{ $currentSubscription->starts_at ? \Illuminate\Support\Carbon::parse($currentSubscription->starts_at)->format('d-m-Y H:i') : '-' }} |
                        Expires {{ $currentSubscription->expires_at ? \Illuminate\Support\Carbon::parse($currentSubscription->expires_at)->format('d-m-Y H:i') : '-' }}
                        @if ($latestPayment)
                            | Last payment {{ number_format((float) $latestPayment->amount, 2) }} {{ $latestPayment->currency ?: 'INR' }}
                        @endif
                    </div>
                @endif

                <form id="cashSubscriptionForm" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label>School</label>
                        @if (!empty($isSchoolUser) && !empty($defaultSchoolId))
                            <input type="text" class="form-control" value="{{ $defaultSchoolName ?? 'School' }}" disabled>
                        @else
                            <input type="text" class="form-control" value="Admin" disabled>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Child <span style="color:red;">*</span></label>
                        <select class="form-control" name="child_id" id="child_id">
                            <option value="">Select Child</option>
                            @foreach ($children as $child)
                                <option value="{{ $child->id }}" {{ (int) ($selectedChildId ?? 0) === (int) $child->id ? 'selected' : '' }}>
                                    #{{ $child->id }} - {{ $child->child_name ?? 'Child' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Showing latest 500 children.</small>
                    </div>

                    <div class="form-group">
                        <label>Service Type <span style="color:red;">*</span></label>
                        <select class="form-control" name="service_type" id="service_type">
                            <option value="vehicle" {{ ($currentSubscription->service_type ?? 'vehicle') === 'vehicle' ? 'selected' : '' }}>Vehicle</option>
                            <option value="school" {{ ($currentSubscription->service_type ?? '') === 'school' ? 'selected' : '' }}>School</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Package Type <span style="color:red;">*</span></label>
                        <select class="form-control" name="package_type" id="package_type">
                            <option value="">Select Package</option>
                            <option value="1day" {{ ($currentSubscription->package_type ?? '') === '1day' ? 'selected' : '' }}>1 Day</option>
                            <option value="1month" {{ ($currentSubscription->package_type ?? '') === '1month' ? 'selected' : '' }}>1 Month</option>
                            <option value="1year" {{ ($currentSubscription->package_type ?? '') === '1year' ? 'selected' : '' }}>1 Year</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Amount (INR) <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" value="{{ $latestPayment->amount ?? '' }}" required>
                        <input type="hidden" name="currency" value="INR">
                    </div>

                    <div class="form-group">
                        <label>Paid At</label>
                        <input type="datetime-local" class="form-control" id="paid_at" name="paid_at" value="{{ $prefillPaidAt ?? '' }}">
                        <small class="text-muted">Renewal ke liye current date/time use karein.</small>
                    </div>

                    <div class="form-group">
                        <label>Receipt No</label>
                        <input type="text" class="form-control" id="receipt_no" name="receipt_no" value="{{ $latestPayment->receipt_no ?? '' }}" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label>Reference No</label>
                        <input type="text" class="form-control" id="reference_no" name="reference_no" value="{{ $latestPayment->reference_no ?? '' }}" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label>Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3">{{ $currentSubscription->notes ?? '' }}</textarea>
                    </div>

                    <button type="button" class="btn btn-primary" id="submitCashSubscriptionBtn">Submit</button>
                    <a href="{{ $cancelRoute }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <script>
        $('#submitCashSubscriptionBtn').on('click', function() {
            $('.error-message').remove();
            let formData = new FormData(document.getElementById('cashSubscriptionForm'));
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('child_id')) showError('#child_id', 'Child is required');
            if (!formData.get('service_type')) showError('#service_type', 'Service Type is required');
            if (!formData.get('package_type')) showError('#package_type', 'Package Type is required');
            if (!formData.get('amount')) showError('#amount', 'Amount is required');
            if (!isValid) return;

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route('api.subscriptions.cash') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        notify('success', data.message || 'Cash subscription saved');
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        notify('error', data.message || 'Something went wrong');
                    }
                })
                .catch(() => {
                    Swal.close();
                    notify('error', 'Something went wrong');
                });
        });

        $(document)
            .off('input.cashSub change.cashSub', 'input, select, textarea')
            .on('input.cashSub change.cashSub', 'input, select, textarea', function() {
                $(this).next('.error-message').remove();
            });
    </script>
@endsection
