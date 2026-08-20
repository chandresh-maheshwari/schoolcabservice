@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')
    @php
        $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
        $schoolSlug = request()->route('schoolSlug');
        $isSchoolPanel = filled($schoolSlug) && is_string($routeName) && str_starts_with($routeName, 'school.');
        $panelParams = $isSchoolPanel ? ['schoolSlug' => $schoolSlug] : [];
        $cancelRoute = route($isSchoolPanel ? 'school.child.index' : 'child.index', $panelParams);
        $childListingRoute = $cancelRoute;
        $latestPayment = !empty($currentSubscription) ? $currentSubscription->payments->first() : null;
        $currentSubscriptionExpiresAt = !empty($currentSubscription?->expires_at)
            ? \Illuminate\Support\Carbon::parse($currentSubscription->expires_at)
            : null;
        $currentSubscriptionStatus = !empty($currentSubscription)
            ? ($currentSubscriptionExpiresAt && $currentSubscriptionExpiresAt->isPast() ? 'expired' : ($currentSubscription->status ?: '-'))
            : null;
        $prefillPaidAt = \App\Support\DateFormat::formatDateTime(now(), '');
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
                <div class="alert alert-info" id="currentSubscriptionSummary" style="{{ !empty($currentSubscription) ? '' : 'display:none;' }}">
                    @if (!empty($currentSubscription))
                        Current subscription:
                        {{ $currentSubscription->package_type ?: '-' }} |
                        {{ ucfirst((string) $currentSubscriptionStatus) }} |
                        Starts @displayDateTime($currentSubscription->starts_at) |
                        Expires @displayDateTime($currentSubscription->expires_at)
                        @if ($latestPayment)
                            | Last payment {{ number_format((float) $latestPayment->amount, 2) }} {{ $latestPayment->currency ?: 'INR' }}
                        @endif
                    @endif
                </div>

                <form id="cashSubscriptionForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="service_type" id="service_type" value="{{ $currentSubscription->service_type ?? 'vehicle' }}">

                    <div class="form-group">
                        <label>Child <span style="color:red;">*</span></label>
                        <select class="form-control" name="child_id" id="child_id">
                            <option value="">Select Child</option>
                            @foreach ($children as $child)
                                <option
                                    value="{{ $child->id }}"
                                    data-school-name="{{ $schoolNameMap[(int) ($child->school_id ?? 0)] ?? $defaultSchoolName ?? '' }}"
                                    {{ (int) ($selectedChildId ?? 0) === (int) $child->id ? 'selected' : '' }}>
                                    #{{ $child->id }} - {{ $child->child_name ?? 'Child' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Showing latest 500 children.</small>
                    </div>

                    <div class="form-group">
                        <label>School</label>
                        <input
                            type="text"
                            class="form-control"
                            id="selected_child_school"
                            value="{{ $displaySchoolName ?? $defaultSchoolName ?? '' }}"
                            placeholder="School"
                            disabled>
                    </div>

                    <div class="form-group">
                        <label>Package Name <span style="color:red;">*</span></label>
                        <select class="form-control" name="package_type" id="package_type">
                            <option value="">Select Package Name</option>
                            @foreach (($packageOptions ?? collect()) as $packageOption)
                                <option
                                    value="{{ $packageOption->id }}"
                                    data-price="{{ $packageOption->price ?? '' }}"
                                    data-package-name="{{ $packageOption->package_name ?? '' }}"
                                    data-package-type="{{ $packageOption->package_type ?? '' }}"
                                    data-booking-type="{{ $packageOption->booking_type ?? '' }}"
                                    {{ (int) ($selectedPackageOptionId ?? 0) === (int) $packageOption->id || (strcasecmp(trim((string) ($currentSubscription->package_type ?? '')), trim((string) ($packageOption->package_type ?? ''))) === 0) ? 'selected' : '' }}>
                                    {{ $packageOption->package_name ?: ('Package #' . $packageOption->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Amount (INR) <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" value="{{ $latestPayment->amount ?? '' }}" required readonly>
                        <input type="hidden" name="currency" value="INR">
                    </div>

                    <div class="form-group">
                        <label>Paid At</label>
                        <input type="text" class="form-control app-datetime-picker" id="paid_at" name="paid_at" value="{{ $prefillPaidAt ?? '' }}" data-default-now="true" data-field-label="Paid At" placeholder="DD/MM/YYYY hh:mm AM" inputmode="numeric" autocomplete="off">
                        <small class="text-muted">Use the current date and time for renewal.</small>
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
        const childSubscriptionSnapshots = @json($subscriptionSnapshotMap ?? []);
        $('#submitCashSubscriptionBtn').on('click', function() {
            $('.error-message').remove();
            let formData = new FormData(document.getElementById('cashSubscriptionForm'));
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('child_id')) showError('#child_id', 'Child is required');
            if (!formData.get('package_type')) showError('#package_type', 'Package Name is required');
            if (!formData.get('amount')) showError('#amount', 'Amount is required');
            if (formData.get('paid_at') && !window.parseDisplayDateTime(formData.get('paid_at'))) showError('#paid_at', 'Use date format DD/MM/YYYY hh:mm AM');
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
                        if (typeof window.__childModuleClearAllState === 'function') {
                            window.__childModuleClearAllState();
                        } else if (typeof window.__childModuleClearDraft === 'function') {
                            window.__childModuleClearDraft();
                        }
                        notify('success', data.message || 'Cash subscription saved');
                        setTimeout(() => {
                            window.location.href = '{{ $childListingRoute }}';
                        }, 1200);
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

        function updateSelectedChildSchool() {
            const selectedOption = $('#child_id option:selected');
            const schoolName = selectedOption.data('school-name') || '{{ $displaySchoolName ?? $defaultSchoolName ?? '' }}' || '';
            $('#selected_child_school').val(schoolName);
        }

        function updatePackageAmount() {
            const selectedOption = $('#package_type option:selected');
            const selectedPrice = selectedOption.data('price');

            if (selectedPrice === undefined || selectedPrice === null || selectedPrice === '') {
                $('#amount').val('');
                return;
            }

            $('#amount').val(selectedPrice);
        }

        function syncPackageSelectionFromSnapshot(snapshot) {
            if (!snapshot) {
                return false;
            }

            const packageField = document.getElementById('package_type');
            if (!packageField) {
                return false;
            }

            let matchedValue = '';

            if (snapshot.package_option_id) {
                matchedValue = String(snapshot.package_option_id);
            } else if (snapshot.package_type) {
                const normalizedPackageType = String(snapshot.package_type).trim().toLowerCase();
                const matchedOption = Array.from(packageField.options).find((option) => {
                    return String(option.getAttribute('data-package-type') || '').trim().toLowerCase() === normalizedPackageType
                        || String(option.getAttribute('data-package-name') || '').trim().toLowerCase() === normalizedPackageType
                        || String(option.value || '').trim().toLowerCase() === normalizedPackageType;
                });

                if (matchedOption) {
                    matchedValue = String(matchedOption.value || '');
                }
            }

            if (!matchedValue) {
                return false;
            }

            packageField.value = matchedValue;
            packageField.dispatchEvent(new Event('change', { bubbles: true }));
            return true;
        }

        function resetSubscriptionSummary() {
            const summary = $('#currentSubscriptionSummary');
            summary.hide().text('');
        }

        function updateSubscriptionSummary(snapshot) {
            const summary = $('#currentSubscriptionSummary');
            if (!snapshot || !snapshot.subscription_id) {
                resetSubscriptionSummary();
                return;
            }

            const parts = [
                'Current subscription:',
                snapshot.package_type || '-',
                snapshot.status ? snapshot.status.toString().replace(/^./, (char) => char.toUpperCase()) : '-',
            ];

            if (snapshot.starts_at_display) {
                parts.push('Starts ' + snapshot.starts_at_display);
            }

            if (snapshot.expires_at_display) {
                parts.push('Expires ' + snapshot.expires_at_display);
            }

            if (snapshot.amount) {
                parts.push('Last payment ' + snapshot.amount + ' ' + (snapshot.currency || 'INR'));
            }

            summary.text(parts.join(' | ')).show();
        }

        function autofillChildSubscription() {
            const childId = String($('#child_id').val() || '');
            const snapshot = childSubscriptionSnapshots[childId] || null;

            updateSelectedChildSchool();

            if (!snapshot || !snapshot.subscription_id) {
                resetSubscriptionSummary();
                return;
            }

            const packageMatched = syncPackageSelectionFromSnapshot(snapshot);

            if (!packageMatched && snapshot.amount !== undefined && snapshot.amount !== null && snapshot.amount !== '') {
                $('#amount').val(snapshot.amount);
            }

            if (snapshot.paid_at) {
                $('#paid_at').val(snapshot.paid_at);
            }

            $('#receipt_no').val(snapshot.receipt_no || '');
            $('#reference_no').val(snapshot.reference_no || '');
            $('#notes').val(snapshot.notes || '');

            updateSubscriptionSummary(snapshot);
        }

        $('#child_id').on('change', autofillChildSubscription);
        $('#package_type').on('change', updatePackageAmount);
        autofillChildSubscription();
        updatePackageAmount();

        window.__childModuleAfterDraftRestore = function () {
            autofillChildSubscription();
            updatePackageAmount();
        };
    </script>
@endsection
