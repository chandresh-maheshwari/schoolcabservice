@extends('admin_layout.index')

@section('content')
    @php
        $dashboardRoute = $panel['is_school_panel']
            ? route('school.dashboard', ['schoolSlug' => $panel['school_slug']])
            : route('admin_layout.index');
        $indexRoute = $panel['is_school_panel']
            ? route('school.supportRequests.index', ['schoolSlug' => $panel['school_slug']])
            : route('supportRequests.index');
        $bulkDeleteRoute = route('api.supportRequests.multi-delete');
    @endphp

    <style>
        .support-page {
            --support-primary: var(--school-primary, #2D336B);
            --support-primary-soft: rgba(45, 51, 107, 0.08);
            --support-border: #d9dee7;
            --support-surface: #f8faff;
            --support-success-bg: #e7f8ef;
            --support-success-text: #198754;
            --support-warning-bg: #fff1db;
            --support-warning-text: #c77700;
            --support-open-bg: #e8f4ff;
            --support-open-text: #0d6efd;
        }

        .support-filter-form .support-filter-field {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .support-filter-form .form-control,
        .support-filter-form .form-select {
            min-height: 48px;
            border: 1px solid #d9dee7;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: none;
        }

        .support-filter-form .form-control:focus,
        .support-filter-form .form-select:focus {
            border-color: #d9dee7;
            box-shadow: none;
        }

        .support-summary-card {
            border: 1px solid rgba(45, 51, 107, 0.08);
            border-radius: 18px;
        }

        .support-summary-badge {
            border-radius: 999px;
            padding: 0.55rem 0.9rem;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .support-filter-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
            height: 100%;
            align-items: end;
        }

        .support-filter-actions .btn {
            min-height: 48px;
            border-radius: 8px;
            font-weight: 600;
            min-width: 120px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding-inline: 1.5rem;
            transition: all 0.2s ease-in-out;
        }

        .support-filter-actions .btn-primary {
            background-color: var(--school-primary, #2D336B);
            border-color: var(--school-primary, #2D336B);
            color: #fff;
        }

        .support-filter-actions .btn-primary:hover,
        .support-filter-actions .btn-primary:focus,
        .support-filter-actions .btn-primary:active {
            background-color: var(--school-primary, #2D336B);
            border-color: var(--school-primary, #2D336B);
            color: #fff;
            filter: brightness(0.95);
            box-shadow: none;
        }

        .support-filter-actions .btn-outline-secondary {
            border-color: rgba(45, 51, 107, 0.22);
            color: var(--school-primary, #2D336B);
            background-color: #fff;
        }

        .support-filter-actions .btn-outline-secondary:hover,
        .support-filter-actions .btn-outline-secondary:focus,
        .support-filter-actions .btn-outline-secondary:active {
            border-color: var(--school-primary, #2D336B);
            color: var(--school-primary, #2D336B);
            background-color: rgba(45, 51, 107, 0.06);
            box-shadow: none;
        }

        .support-bulk-card {
            border: 1px solid rgba(45, 51, 107, 0.08);
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
        }

        .support-bulk-select {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0.9rem;
            border-radius: 12px;
            background-color: var(--support-surface);
            border: 1px solid var(--support-border);
        }

        .support-bulk-select .form-check-input,
        .support-request-select .form-check-input {
            width: 1.05rem;
            height: 1.05rem;
            margin-top: 0;
            box-shadow: none;
        }

        .support-request-card {
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(45, 51, 107, 0.08);
        }

        .support-request-topbar {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid rgba(217, 222, 231, 0.8);
        }

        .support-request-select {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.7rem;
            border: 1px solid var(--support-border);
            border-radius: 999px;
            background-color: #fff;
            color: #6c757d;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .support-request-ticket {
            color: #7c85a3;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .support-request-subject {
            font-size: 1.2rem;
            font-weight: 700;
            color: #18213c;
        }

        .support-request-meta {
            color: #5f6884;
            line-height: 1.8;
        }

        .support-status-panel {
            min-width: 240px;
            padding: 1rem 1.1rem;
            border-radius: 18px;
            border: 1px solid var(--support-border);
            background: linear-gradient(180deg, #ffffff 0%, #f7f9ff 100%);
        }

        .support-status-label {
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #7c85a3;
            margin-bottom: 0.55rem;
        }

        .support-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            padding: 0.55rem 0.95rem;
            font-size: 0.83rem;
            font-weight: 700;
        }

        .support-status-pill.is-open {
            background-color: var(--support-open-bg);
            color: var(--support-open-text);
        }

        .support-status-pill.is-progress {
            background-color: var(--support-warning-bg);
            color: var(--support-warning-text);
        }

        .support-status-pill.is-closed {
            background-color: var(--support-success-bg);
            color: var(--support-success-text);
        }

        .support-status-note {
            margin-top: 0.85rem;
            color: #5f6884;
            font-size: 0.88rem;
            line-height: 1.5;
        }

        .support-delete-button {
            margin-top: 0.9rem;
            width: 100%;
            border-radius: 10px;
            font-weight: 700;
        }

        .support-info-box {
            border: 1px solid rgba(217, 222, 231, 0.85);
            border-radius: 14px;
            padding: 1rem;
            height: 100%;
            background-color: #fbfcff;
        }

        .support-message-box {
            border: 1px solid rgba(217, 222, 231, 0.9);
            border-radius: 14px;
            padding: 1rem;
            background: #fff;
        }

        .support-review-card {
            border: 1px solid rgba(45, 51, 107, 0.08);
            border-radius: 16px;
            padding: 1rem;
            background: linear-gradient(180deg, #fbfcff 0%, #ffffff 100%);
        }

        .support-review-card .form-control {
            min-height: 92px;
            border-radius: 12px;
            border-color: var(--support-border);
            box-shadow: none;
        }

        .support-action-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .support-action-button {
            min-height: 52px;
            border-radius: 14px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            border-width: 1px;
        }

        .support-action-button.complete {
            background-color: #198754;
            border-color: #198754;
            color: #fff;
        }

        .support-action-button.progress {
            background-color: #ff8a3d;
            border-color: #ff8a3d;
            color: #fff;
        }

        .support-action-button.reopen {
            background-color: #fff;
            border-color: var(--support-border);
            color: #4f5a78;
        }

        .support-completed-note {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.8rem;
            padding: 0.7rem 0.9rem;
            border-radius: 12px;
            background-color: var(--support-success-bg);
            color: var(--support-success-text);
            font-weight: 700;
            font-size: 0.92rem;
        }

        @media (max-width: 991.98px) {
            .support-request-topbar {
                flex-direction: column;
            }

            .support-status-panel {
                width: 100%;
                min-width: 0;
            }

            .support-action-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .support-filter-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>

        <div class="section-breadcrumb">
    <div class="breadcrumb-wrapper pb-0">
        <div class="container">
            <nav aria-label="breadcrumb-nav">
                <ol class="breadcrumb breadcrumb-style-2 my-20">
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ $dashboardRoute }}">Dashboard</a></li>
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">{{ $pageTitle }}</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

    <div class="container-fluid support-page">

        <div class="card border-0 shadow-sm mb-4 support-summary-card">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h3 class="mb-1">{{ $pageTitle }}</h3>
                        <p class="text-muted mb-0">{{ $pageDescription }}</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-light text-dark support-summary-badge">Total {{ $requests->total() }}</span>
                        @if ($panel['is_school_panel'])
                            <span class="badge bg-primary-subtle text-primary support-summary-badge">School Scope</span>
                        @else
                            <span class="badge bg-success-subtle text-success support-summary-badge">Admin Scope</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ $indexRoute }}" class="row g-3 support-filter-form">
                    <div class="col-xl-4 col-lg-5 col-md-6">
                        <div class="support-filter-field">
                            <label class="form-label fw-semibold mb-2">Search</label>
                            <input type="text" class="form-control" name="search" value="{{ request('search') }}"
                                placeholder="Subject, message, category, parent, email">
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-3 col-md-6">
                        <div class="support-filter-field">
                            <label class="form-label fw-semibold mb-2">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @if (! $panel['is_school_panel'])
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="support-filter-field">
                                <label class="form-label fw-semibold mb-2">School</label>
                                <select class="form-select" name="school_id">
                                    <option value="">All schools</option>
                                    @foreach ($schoolOptions as $school)
                                        <option value="{{ $school->id }}" @selected((string) request('school_id') === (string) $school->id)>{{ $school->school_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                    <div class="col-xl-2 col-lg-12 col-md-6">
                        <div class="support-filter-actions">
                            <button type="submit" class="btn btn-primary">Apply</button>
                            <a href="{{ $indexRoute }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4 support-bulk-card">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="form-check mb-0 support-bulk-select">
                        <input class="form-check-input" type="checkbox" id="supportSelectAll">
                        <label class="form-check-label fw-semibold mb-0" for="supportSelectAll">Select requests on this page</label>
                    </div>
                    <span class="text-muted small" id="supportBulkSelectionText">0 selected</span>
                </div>
                @if ($canDeleteSupportRequests)
                    <button type="button" id="supportBulkDeleteButton" class="btn btn-danger px-4" data-bulk-delete-url="{{ $bulkDeleteRoute }}" disabled>Delete Selected</button>
                @endif
            </div>
        </div>

        <div class="row">
            @forelse ($requests as $supportRequest)
                @php
                    $reviewRoute = $panel['is_school_panel']
                        ? route('school.supportRequests.review', ['schoolSlug' => $panel['school_slug'], 'id' => $supportRequest->id])
                        : route('supportRequests.review', ['id' => $supportRequest->id]);
                    $deleteRoute = $panel['is_school_panel']
                        ? route('school.supportRequests.destroy', ['schoolSlug' => $panel['school_slug'], 'id' => $supportRequest->id])
                        : route('supportRequests.destroy', ['id' => $supportRequest->id]);
                    $statusClass = match ($supportRequest->status) {
                        'closed' => 'is-closed',
                        'in_progress' => 'is-progress',
                        default => 'is-open',
                    };
                    $statusLabel = match ($supportRequest->status) {
                        'closed' => 'Completed',
                        'in_progress' => 'In Progress',
                        default => 'Open',
                    };
                    $statusNote = match ($supportRequest->status) {
                        'closed' => 'This request is completed and closed.',
                        'in_progress' => 'This request is currently being handled.',
                        default => 'This request still needs team action.',
                    };
                @endphp
                <div class="col-12 mb-4">
                    <div class="card border-0 shadow-sm h-100 support-request-card">
                        <div class="card-body p-4">
                            <div class="support-request-topbar">
                                <div>
                                    @if ($canDeleteSupportRequests)
                                        <div class="form-check mb-3 support-request-select">
                                            <input class="form-check-input support-request-checkbox" type="checkbox" value="{{ $supportRequest->id }}" id="support-request-{{ $supportRequest->id }}">
                                            <label class="form-check-label mb-0" for="support-request-{{ $supportRequest->id }}">Select request</label>
                                        </div>
                                    @endif
                                    <div class="support-request-ticket mb-2">Ticket #{{ $supportRequest->id }}</div>
                                    <div class="support-request-subject mb-2">{{ $supportRequest->subject ?: 'Support Request' }}</div>
                                    <div class="support-request-meta">
                                        {{ $supportRequest->requester_name }}
                                        <span class="mx-1">|</span>{{ $supportRequest->email ?: '-' }}
                                        @if ($supportRequest->requester_contact !== '-')
                                            <span class="mx-1">|</span>{{ $supportRequest->requester_contact }}
                                        @endif
                                    </div>
                                </div>
                                <div class="support-status-panel">
                                    <div class="support-status-label">Request Status</div>
                                    <span class="support-status-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                                    <div class="support-status-note">
                                        {{ $statusNote }}<br>
                                        Raised {{ optional($supportRequest->created_at)->format('d M Y, h:i A') ?: '-' }}
                                    </div>
                                    @if ($canDeleteSupportRequests)
                                        <button type="button" class="btn btn-outline-danger support-delete-button support-request-delete-button" data-delete-url="{{ $deleteRoute }}">Delete Request</button>
                                    @endif
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <div class="support-info-box">
                                        <div class="text-muted small text-uppercase mb-1">Category</div>
                                        <div class="fw-semibold">{{ $supportRequest->category ?: '-' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="support-info-box">
                                        <div class="text-muted small text-uppercase mb-1">School</div>
                                        <div class="fw-semibold">{{ $supportRequest->school_name }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="support-info-box">
                                        <div class="text-muted small text-uppercase mb-1">Children</div>
                                        <div class="fw-semibold">{{ $supportRequest->child_summary }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="support-info-box">
                                        <div class="text-muted small text-uppercase mb-1">Reviewer</div>
                                        <div class="fw-semibold">{{ optional($supportRequest->reviewer)->first_name ?: '-' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="support-message-box mb-3">
                                <div class="text-muted small text-uppercase mb-2">Message</div>
                                <div>{{ $supportRequest->message ?: '-' }}</div>
                            </div>

                            <form method="POST" action="{{ $reviewRoute }}" class="support-review-card">
                                @csrf
                                @method('PATCH')
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-7">
                                        <label class="form-label fw-semibold">Review Notes</label>
                                        <textarea class="form-control" name="admin_notes" rows="2"
                                            placeholder="Add an update for the internal team or parent."
                                            @disabled(! $canReviewSupportRequests)>{{ old('admin_notes') }}</textarea>
                                        @if (! empty($supportRequest->admin_notes))
                                            <div class="text-muted small mt-2">Last note: {{ $supportRequest->admin_notes }}</div>
                                        @endif
                                        @if (! $canReviewSupportRequests)
                                            <div class="text-muted small mt-2">You do not have permission to review or update this support request.</div>
                                        @endif
                                    </div>
                                    <div class="col-lg-5">
                                        <label class="form-label fw-semibold d-block">Update Request</label>
                                        <div class="support-action-grid">
                                            <button type="submit" name="status" value="in_progress" class="btn support-action-button progress" @disabled(! $canReviewSupportRequests)>Mark In Progress</button>
                                            <button type="submit" name="status" value="closed" class="btn support-action-button complete" @disabled(! $canReviewSupportRequests)>Mark Completed</button>
                                            <button type="submit" name="status" value="open" class="btn support-action-button reopen" @disabled(! $canReviewSupportRequests)>Reopen Request</button>
                                        </div>
                                        @if (($supportRequest->status ?? 'open') === 'closed')
                                            <div class="support-completed-note">
                                                Completed successfully
                                            </div>
                                        @endif
                                        @if ($supportRequest->reviewer || $supportRequest->reviewed_at)
                                            <div class="text-muted small mt-2">
                                                Reviewed by {{ optional($supportRequest->reviewer)->first_name ?: 'Panel User' }}
                                                @if ($supportRequest->reviewed_at)
                                                    on {{ optional($supportRequest->reviewed_at)->format('d M Y, h:i A') }}
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body py-5 text-center text-muted">
                            No support requests found for the current filters.
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($requests->hasPages())
            <div class="d-flex justify-content-end">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

    <form id="supportSingleDeleteForm" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('supportSelectAll');
            const checkboxes = Array.from(document.querySelectorAll('.support-request-checkbox'));
            const bulkDeleteButton = document.getElementById('supportBulkDeleteButton');
            const selectionText = document.getElementById('supportBulkSelectionText');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const updateBulkState = () => {
                const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
                if (selectionText) {
                    selectionText.textContent = `${selectedCount} selected`;
                }
                if (bulkDeleteButton) {
                    bulkDeleteButton.disabled = selectedCount === 0;
                }
                if (selectAll) {
                    selectAll.checked = selectedCount > 0 && selectedCount === checkboxes.length;
                    selectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
                }
            };

            if (selectAll) {
                selectAll.disabled = checkboxes.length === 0;
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = selectAll.checked;
                    });
                    updateBulkState();
                });
            }

            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', updateBulkState);
            });

            if (bulkDeleteButton) {
                bulkDeleteButton.addEventListener('click', function () {
                    const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
                    if (selectedCount === 0) {
                        window.Swal?.fire({
                            icon: 'warning',
                            title: 'No selection',
                            text: 'Please select at least one support request to delete.',
                        });
                        return;
                    }

                    window.Swal?.fire({
                        title: 'Delete selected requests?',
                        text: 'This action cannot be undone.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete them',
                        cancelButtonText: 'Cancel',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const deleteUrl = bulkDeleteButton.getAttribute('data-bulk-delete-url');
                            const selectedIds = checkboxes
                                .filter((checkbox) => checkbox.checked)
                                .map((checkbox) => checkbox.value);

                            fetch(deleteUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                },
                                body: JSON.stringify({
                                    ids: selectedIds,
                                }),
                            })
                                .then(async (response) => {
                                    const payload = await response.json();
                                    if (! response.ok || ! payload.success) {
                                        throw new Error(payload.message || 'Unable to delete selected support requests.');
                                    }
                                    window.location.reload();
                                })
                                .catch((error) => {
                                    window.Swal?.fire({
                                        icon: 'error',
                                        title: 'Delete failed',
                                        text: error.message || 'Unable to delete selected support requests.',
                                    });
                                });
                        }
                    });
                });
            }

            const singleDeleteForm = document.getElementById('supportSingleDeleteForm');
            document.querySelectorAll('.support-request-delete-button').forEach((deleteButton) => {
                deleteButton.addEventListener('click', function () {
                    const deleteUrl = deleteButton.getAttribute('data-delete-url');
                    if (! deleteUrl || ! singleDeleteForm) {
                        return;
                    }

                    window.Swal?.fire({
                        title: 'Delete this request?',
                        text: 'This action cannot be undone.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete it',
                        cancelButtonText: 'Cancel',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            singleDeleteForm.action = deleteUrl;
                            singleDeleteForm.submit();
                        }
                    });
                });
            });

            updateBulkState();
        });
    </script>
@endsection
