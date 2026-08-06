@php
    /** @var array<string, int|string|null> $entityIds */
    $entityIds = is_array($entityIds ?? null) ? $entityIds : [];
    $activeTab = in_array($activeTab ?? null, ['child', 'parent', 'subscription'], true) ? $activeTab : 'child';

    $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
    $schoolSlug = request()->route('schoolSlug');
    $isSchoolPanel = filled($schoolSlug) && is_string($routeName) && str_starts_with($routeName, 'school.');
    $panelParams = $isSchoolPanel ? ['schoolSlug' => $schoolSlug] : [];

    $appendQuery = function (string $url, array $query): string {
        $query = array_filter($query, function ($value) {
            return $value !== null && $value !== '';
        });

        if (empty($query)) {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
    };

    $requestedParentId = request()->query('parent_id');
    $requestedChildId = request()->query('child_id');
    $requestedBookingId = request()->query('booking_id');
    $requestedSubscriptionId = request()->query('subscription_id');
    $requestedModuleNav = request()->query('_module_nav');

    $parentContextId = isset($entityIds['parent']) && $entityIds['parent']
        ? (string) $entityIds['parent']
        : (is_numeric($requestedParentId) && (int) $requestedParentId > 0 ? (string) $requestedParentId : '');
    $childContextId = isset($entityIds['child']) && $entityIds['child']
        ? (string) $entityIds['child']
        : (is_numeric($requestedChildId) && (int) $requestedChildId > 0 ? (string) $requestedChildId : '');
    $bookingContextId = isset($entityIds['booking']) && $entityIds['booking']
        ? (string) $entityIds['booking']
        : (is_numeric($requestedBookingId) && (int) $requestedBookingId > 0 ? (string) $requestedBookingId : '');
    $subscriptionContextId = isset($entityIds['subscription']) && $entityIds['subscription']
        ? (string) $entityIds['subscription']
        : (is_numeric($requestedSubscriptionId) && (int) $requestedSubscriptionId > 0 ? (string) $requestedSubscriptionId : '');

    $sharedQuery = array_filter([
        'child_id' => $childContextId,
        'parent_id' => $parentContextId,
    ], function ($value) {
        return $value !== null && $value !== '';
    });

    $tabQueryBase = ['_module_nav' => 1];

    $childRoute = isset($entityIds['child']) && $entityIds['child']
        ? route($isSchoolPanel ? 'school.child.edit' : 'child.edit', array_merge($panelParams, ['child' => $entityIds['child']]))
        : route($isSchoolPanel ? 'school.child.create' : 'child.create', $panelParams);
    $childRoute = $appendQuery($childRoute, array_filter(array_merge($tabQueryBase, [
        'parent_id' => $parentContextId,
        'booking_id' => $bookingContextId,
    ]), function ($value) {
        return $value !== null && $value !== '';
    }));

    $routes = [
        'child' => $childRoute,
        'parent' => isset($entityIds['parent']) && $entityIds['parent']
            ? $appendQuery(
                route($isSchoolPanel ? 'school.parent.edit' : 'parent.edit', array_merge($panelParams, ['parent' => $entityIds['parent']])),
                array_filter(array_merge($tabQueryBase, [
                    'child_id' => $childContextId,
                    'booking_id' => $bookingContextId,
                ]), function ($value) {
                    return $value !== null && $value !== '';
                })
            )
            : $appendQuery(route($isSchoolPanel ? 'school.parent.create' : 'parent.create', $panelParams), array_merge($tabQueryBase, $sharedQuery)),
        'subscription' => $appendQuery(
            route($isSchoolPanel ? 'school.subscriptions.cash.create' : 'subscriptions.cash.create', $panelParams),
            array_filter(array_merge($tabQueryBase, [
                'child_id' => $childContextId,
                'parent_id' => $parentContextId,
                'subscription_id' => $subscriptionContextId,
            ]), function ($value) {
                return $value !== null && $value !== '';
            })
        ),
    ];
@endphp

<div class="container-fluid mb-3">
    <ul class="nav nav-tabs child-module-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $activeTab === 'child' ? 'active' : '' }}" href="{{ $routes['child'] }}" data-module-nav="1">Child</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $activeTab === 'parent' ? 'active' : '' }}" href="{{ $routes['parent'] }}" data-module-nav="1">Parents</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $activeTab === 'subscription' ? 'active' : '' }}" href="{{ $routes['subscription'] }}" data-module-nav="1">Subscription</a>
        </li>
    </ul>
</div>

<script>
    // AJAX tab navigation (no full page refresh).
    // Replaces only the `.content-wrapper` content and evaluates inline scripts in it.
    (function () {
        if (window.__childModuleAjaxNavBound) return;
        window.__childModuleAjaxNavBound = true;

        const moduleDraftStoragePrefix = 'childModuleDraft';
        const formSelectors = ['#childForm', '#parentForm', '#editParentForm', '#cashSubscriptionForm'];
        window.__childModulePageCache = window.__childModulePageCache || {};

        const clearAllModuleState = () => {
            window.__childModulePageCache = {};

            try {
                const keysToRemove = [];
                for (let i = 0; i < sessionStorage.length; i += 1) {
                    const key = sessionStorage.key(i);
                    if (key && key.indexOf(moduleDraftStoragePrefix) === 0) {
                        keysToRemove.push(key);
                    }
                }

                keysToRemove.forEach((key) => sessionStorage.removeItem(key));
                sessionStorage.removeItem('childModule.child_id');
                sessionStorage.removeItem('childModule.parent_id');
            } catch (e) {}
        };

        const getActiveForm = () => {
            for (const selector of formSelectors) {
                const form = document.querySelector(selector);
                if (form) {
                    return form;
                }
            }

            return null;
        };

        const getDraftContext = () => {
            const activeTabLink = document.querySelector('.child-module-tabs .nav-link.active');
            const activeTab = activeTabLink ? String(activeTabLink.textContent || '').trim().toLowerCase() : '';
            const url = new URL(window.location.href);
            const pathParts = url.pathname.split('/').filter(Boolean);
            const currentEntityId = pathParts.length ? pathParts[pathParts.length - 1] : 'create';
            const childId = url.searchParams.get('child_id') || sessionStorage.getItem('childModule.child_id') || '';
            const parentId = url.searchParams.get('parent_id') || sessionStorage.getItem('childModule.parent_id') || '';
            const subscriptionId = url.searchParams.get('subscription_id') || '';
            const schoolSlug = pathParts.length && pathParts[0] !== 'admin' ? pathParts[0] : '';

            return [moduleDraftStoragePrefix, schoolSlug, activeTab, currentEntityId, childId, parentId, subscriptionId].join(':');
        };

        const collectFormData = (form) => {
            const data = {};
            const fields = Array.from(form.querySelectorAll('input, select, textarea'));

            for (const field of fields) {
                if (!field.name || field.disabled || field.type === 'file') {
                    continue;
                }

                if ((field.type === 'checkbox' || field.type === 'radio')) {
                    if (!data[field.name]) {
                        data[field.name] = [];
                    }

                    if (field.checked) {
                        data[field.name].push(field.value);
                    }
                    continue;
                }

                data[field.name] = field.value;
            }

            return data;
        };

        const persistActiveFormDraft = () => {
            const form = getActiveForm();
            if (!form) {
                return;
            }

            try {
                sessionStorage.setItem(getDraftContext(), JSON.stringify(collectFormData(form)));
            } catch (e) {}
        };

        const restoreActiveFormDraft = () => {
            const form = getActiveForm();
            if (!form) {
                return;
            }

            let raw = null;
            try {
                raw = sessionStorage.getItem(getDraftContext());
            } catch (e) {
                raw = null;
            }

            if (!raw) {
                return;
            }

            let draft = null;
            try {
                draft = JSON.parse(raw);
            } catch (e) {
                draft = null;
            }

            if (!draft || typeof draft !== 'object') {
                return;
            }

            Object.keys(draft).forEach((name) => {
                const value = draft[name];
                const fields = form.querySelectorAll(`[name="${CSS.escape(name)}"]`);

                fields.forEach((field) => {
                    if (field.disabled || field.type === 'file') {
                        return;
                    }

                    if (field.type === 'checkbox' || field.type === 'radio') {
                        const selectedValues = Array.isArray(value) ? value.map(String) : [String(value)];
                        field.checked = selectedValues.includes(String(field.value));
                    } else {
                        field.value = value == null ? '' : String(value);
                    }

                    field.dispatchEvent(new Event('change', { bubbles: true }));
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                });
            });
        };

        const clearActiveFormDraft = () => {
            try {
                sessionStorage.removeItem(getDraftContext());
            } catch (e) {}
        };

        const getActiveFormDraft = () => {
            try {
                const raw = sessionStorage.getItem(getDraftContext());
                return raw ? (JSON.parse(raw) || {}) : {};
            } catch (e) {
                return {};
            }
        };

        const patchActiveFormDraft = (patch) => {
            if (!patch || typeof patch !== 'object') {
                return;
            }

            const nextDraft = Object.assign({}, getActiveFormDraft(), patch);
            try {
                sessionStorage.setItem(getDraftContext(), JSON.stringify(nextDraft));
            } catch (e) {}
        };

        const bindDraftPersistence = () => {
            const form = getActiveForm();
            if (!form || form.dataset.draftBound === '1') {
                return;
            }

            form.dataset.draftBound = '1';
            form.addEventListener('input', persistActiveFormDraft, true);
            form.addEventListener('change', persistActiveFormDraft, true);
        };

        const parseHtml = (html) => {
            const parser = new DOMParser();
            return parser.parseFromString(html, 'text/html');
        };

        const serializeWrapperHtml = (wrapper) => {
            if (!wrapper) {
                return '';
            }

            const clone = wrapper.cloneNode(true);
            const sourceFields = wrapper.querySelectorAll('input, textarea, select');
            const cloneFields = clone.querySelectorAll('input, textarea, select');

            sourceFields.forEach((field, index) => {
                const cloneField = cloneFields[index];
                if (!cloneField) {
                    return;
                }

                if (field instanceof HTMLTextAreaElement) {
                    cloneField.textContent = field.value;
                    return;
                }

                if (field instanceof HTMLSelectElement) {
                    Array.from(field.options).forEach((option, optionIndex) => {
                        const cloneOption = cloneField.options[optionIndex];
                        if (!cloneOption) {
                            return;
                        }

                        if (option.selected) {
                            cloneOption.setAttribute('selected', 'selected');
                        } else {
                            cloneOption.removeAttribute('selected');
                        }
                    });
                    return;
                }

                if (field.type === 'checkbox' || field.type === 'radio') {
                    if (field.checked) {
                        cloneField.setAttribute('checked', 'checked');
                    } else {
                        cloneField.removeAttribute('checked');
                    }
                } else {
                    cloneField.setAttribute('value', field.value || '');
                }

                if (field.disabled) {
                    cloneField.setAttribute('disabled', 'disabled');
                } else {
                    cloneField.removeAttribute('disabled');
                }
            });

            return clone.innerHTML;
        };

        const snapshotCurrentWrapper = () => {
            const currentWrapper = document.querySelector('.content-wrapper');
            if (!currentWrapper) {
                return;
            }
            window.__childModulePageCache[window.location.href] = serializeWrapperHtml(currentWrapper);
        };

        const restoreCachedWrapper = (href) => {
            const currentWrapper = document.querySelector('.content-wrapper');
            const cachedHtml = window.__childModulePageCache[href];

            if (!currentWrapper || typeof cachedHtml !== 'string' || cachedHtml === '') {
                return false;
            }

            currentWrapper.innerHTML = cachedHtml;
            runInlineScripts(currentWrapper);
            restoreActiveFormDraft();
            bindDraftPersistence();
            return true;
        };

        const runInlineScripts = (container) => {
            const scripts = Array.from(container.querySelectorAll('script'));
            for (const script of scripts) {
                // Avoid reloading shared assets; the layout already includes vendor scripts.
                if (script.src) {
                    script.remove();
                    continue;
                }

                const code = script.textContent || '';
                script.remove();
                if (!code.trim()) continue;

                const executable = document.createElement('script');
                executable.text = code;
                document.body.appendChild(executable);
                executable.remove();
            }
        };

        const swapContentWrapper = (nextDocument) => {
            const nextWrapper = nextDocument.querySelector('.content-wrapper');
            const currentWrapper = document.querySelector('.content-wrapper');
            if (!nextWrapper || !currentWrapper) return false;

            currentWrapper.innerHTML = nextWrapper.innerHTML;
            runInlineScripts(currentWrapper);
            restoreActiveFormDraft();
            bindDraftPersistence();
            return true;
        };

        const loadPage = async (href, { push = true } = {}) => {
            const currentWrapper = document.querySelector('.content-wrapper');
            if (currentWrapper) {
                currentWrapper.style.opacity = '0.65';
                currentWrapper.style.pointerEvents = 'none';
            }

            try {
                snapshotCurrentWrapper();

                if (restoreCachedWrapper(href)) {
                    if (push) {
                        history.pushState({ href }, '', href);
                    }
                    return;
                }

                const res = await fetch(href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                if (!res.ok) {
                    throw new Error('Navigation request failed with status ' + res.status);
                }
                const text = await res.text();
                const nextDoc = parseHtml(text);
                const ok = swapContentWrapper(nextDoc);
                if (!ok) {
                    throw new Error('Navigation content swap failed');
                }

                if (ok && push) {
                    history.pushState({ href }, '', href);
                }
            } catch (error) {
                window.location.href = href;
                return;
            } finally {
                const wrapper = document.querySelector('.content-wrapper');
                if (wrapper) {
                    wrapper.style.opacity = '';
                    wrapper.style.pointerEvents = '';
                }
            }
        };

        document.addEventListener('click', function (event) {
            const link = event.target && event.target.closest ? event.target.closest('a[data-module-nav="1"]') : null;
            if (!link) return;

            const href = link.getAttribute('href');
            if (!href || href === '#') return;

            // Allow ctrl/cmd click / new tab.
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) return;

            // Persist current Parent selection (if present) so Child form can auto-select after tab switches.
            const parentSelect = document.querySelector('#parent_id');
            if (parentSelect && parentSelect.value) {
                try {
                    sessionStorage.setItem('childModule.parent_id', parentSelect.value);
                } catch (e) {}
            }

            persistActiveFormDraft();

            event.preventDefault();
            event.stopPropagation();
            if (typeof event.stopImmediatePropagation === 'function') {
                event.stopImmediatePropagation();
            }

            loadPage(href, { push: true });
        }, true);

        window.addEventListener('popstate', function (event) {
            const href = event && event.state && event.state.href ? event.state.href : window.location.href;
            loadPage(href, { push: false });
        });

        // Expose for inline form scripts (e.g., Parent create -> back to Child create).
        window.__childModuleLoadPage = function (href) {
            persistActiveFormDraft();
            return loadPage(href, { push: true });
        };

        window.__childModuleClearDraft = function () {
            clearActiveFormDraft();
        };

        window.__childModuleGetDraftState = function () {
            return getActiveFormDraft();
        };

        window.__childModulePatchDraftState = function (patch) {
            patchActiveFormDraft(patch);
        };

        const currentUrl = new URL(window.location.href);
        const isFreshCreateEntry = !currentUrl.searchParams.get('_module_nav')
            && currentUrl.pathname.indexOf('/child/create') !== -1
            && !currentUrl.searchParams.get('child_id')
            && !currentUrl.searchParams.get('parent_id')
            && !currentUrl.searchParams.get('subscription_id');

        if (isFreshCreateEntry) {
            clearAllModuleState();
        }

        window.addEventListener('beforeunload', function () {
            persistActiveFormDraft();
        });

        restoreActiveFormDraft();
        bindDraftPersistence();
    })();
</script>

<style>
    .child-module-tabs {
        gap: 10px;
        border-bottom: 0;
    }

    .child-module-tabs .nav-link {
        border: 1px solid #dfe4ea;
        border-radius: 999px;
        color: #2d336b;
        font-weight: 600;
        padding: 8px 18px;
        background: #f8f9fb;
    }

    .child-module-tabs .nav-link.active {
        background: #2d336b;
        border-color: #2d336b;
        color: #fff;
    }
</style>
