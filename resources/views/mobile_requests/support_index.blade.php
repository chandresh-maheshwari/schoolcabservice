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

    <div class="container-fluid">

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h3 class="mb-1">{{ $pageTitle }}</h3>
                        <p class="text-muted mb-0">{{ $pageDescription }}</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge rounded-pill bg-light text-dark px-3 py-2">Total {{ $requests->total() }}</span>
                        @if ($panel['is_school_panel'])
                            <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2">School Scope</span>
                        @else
                            <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2">Admin Scope</span>
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

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="supportSelectAll">
                        <label class="form-check-label fw-semibold" for="supportSelectAll">Select all on this page</label>
                    </div>
                    <span class="text-muted small" id="supportBulkSelectionText">0 selected</span>
                </div>
                <button type="button" id="supportBulkDeleteButton" class="btn btn-danger" data-bulk-delete-url="{{ $bulkDeleteRoute }}" disabled>Delete Selected</button>
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
                        'closed' => 'bg-success-subtle text-success',
                        'in_progress' => 'bg-warning-subtle text-warning',
                        default => 'bg-info-subtle text-info',
                    };
                @endphp
                <div class="col-12 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
                                <div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input support-request-checkbox" type="checkbox" value="{{ $supportRequest->id }}" id="support-request-{{ $supportRequest->id }}">
                                        <label class="form-check-label small text-muted" for="support-request-{{ $supportRequest->id }}">Select request</label>
                                    </div>
                                    <div class="text-muted small mb-1">Ticket #{{ $supportRequest->id }}</div>
                                    <h5 class="mb-1">{{ $supportRequest->subject ?: 'Support Request' }}</h5>
                                    <div class="text-muted">
                                        {{ $supportRequest->requester_name }}
                                        <span class="mx-1">|</span>{{ $supportRequest->email ?: '-' }}
                                        @if ($supportRequest->requester_contact !== '-')
                                            <span class="mx-1">|</span>{{ $supportRequest->requester_contact }}
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge rounded-pill {{ $statusClass }} px-3 py-2">{{ strtoupper(str_replace('_', ' ', $supportRequest->status ?: 'open')) }}</span>
                                    <div class="text-muted small mt-2">Raised {{ optional($supportRequest->created_at)->format('d M Y, h:i A') ?: '-' }}</div>
                                    <button type="button" class="btn btn-outline-danger btn-sm mt-3 support-request-delete-button" data-delete-url="{{ $deleteRoute }}">Delete</button>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <div class="border rounded-3 p-3 h-100 bg-light">
                                        <div class="text-muted small text-uppercase mb-1">Category</div>
                                        <div class="fw-semibold">{{ $supportRequest->category ?: '-' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded-3 p-3 h-100 bg-light">
                                        <div class="text-muted small text-uppercase mb-1">School</div>
                                        <div class="fw-semibold">{{ $supportRequest->school_name }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded-3 p-3 h-100 bg-light">
                                        <div class="text-muted small text-uppercase mb-1">Children</div>
                                        <div class="fw-semibold">{{ $supportRequest->child_summary }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded-3 p-3 h-100 bg-light">
                                        <div class="text-muted small text-uppercase mb-1">Reviewer</div>
                                        <div class="fw-semibold">{{ optional($supportRequest->reviewer)->first_name ?: '-' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="border rounded-3 p-3 mb-3">
                                <div class="text-muted small text-uppercase mb-2">Message</div>
                                <div>{{ $supportRequest->message ?: '-' }}</div>
                            </div>

                            <form method="POST" action="{{ $reviewRoute }}">
                                @csrf
                                @method('PATCH')
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-7">
                                        <label class="form-label fw-semibold">Review Notes</label>
                                        <textarea class="form-control" name="admin_notes" rows="2"
                                            placeholder="Add an update for the internal team or parent.">{{ old('admin_notes') }}</textarea>
                                        @if (! empty($supportRequest->admin_notes))
                                            <div class="text-muted small mt-2">Last note: {{ $supportRequest->admin_notes }}</div>
                                        @endif
                                    </div>
                                    <div class="col-lg-5">
                                        <label class="form-label fw-semibold d-block">Quick Actions</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button type="submit" name="status" value="in_progress" class="btn btn-warning text-white">Mark In Progress</button>
                                            <button type="submit" name="status" value="closed" class="btn btn-success">Close</button>
                                            <button type="submit" name="status" value="open" class="btn btn-outline-secondary">Reopen</button>
                                        </div>
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
