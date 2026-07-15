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

    $parentContextId = isset($entityIds['parent']) && $entityIds['parent'] ? (string) $entityIds['parent'] : '';
    $childContextId = isset($entityIds['child']) && $entityIds['child'] ? (string) $entityIds['child'] : '';
    $bookingContextId = isset($entityIds['booking']) && $entityIds['booking'] ? (string) $entityIds['booking'] : '';
    $subscriptionContextId = isset($entityIds['subscription']) && $entityIds['subscription'] ? (string) $entityIds['subscription'] : '';

    $sharedQuery = array_filter([
        'child_id' => $childContextId,
        'parent_id' => $parentContextId,
    ], function ($value) {
        return $value !== null && $value !== '';
    });

    $childRoute = isset($entityIds['child']) && $entityIds['child']
        ? route($isSchoolPanel ? 'school.child.edit' : 'child.edit', array_merge($panelParams, ['child' => $entityIds['child']]))
        : route($isSchoolPanel ? 'school.child.create' : 'child.create', $panelParams);
    $childRoute = $appendQuery($childRoute, array_filter([
        'parent_id' => $parentContextId,
        'booking_id' => $bookingContextId,
    ], function ($value) {
        return $value !== null && $value !== '';
    }));

    $routes = [
        'child' => $childRoute,
        'parent' => isset($entityIds['parent']) && $entityIds['parent']
            ? $appendQuery(
                route($isSchoolPanel ? 'school.parent.edit' : 'parent.edit', array_merge($panelParams, ['parent' => $entityIds['parent']])),
                array_filter([
                    'child_id' => $childContextId,
                    'booking_id' => $bookingContextId,
                ], function ($value) {
                    return $value !== null && $value !== '';
                })
            )
            : $appendQuery(route($isSchoolPanel ? 'school.parent.create' : 'parent.create', $panelParams), $sharedQuery),
        'subscription' => $appendQuery(
            route($isSchoolPanel ? 'school.subscriptions.cash.create' : 'subscriptions.cash.create', $panelParams),
            array_filter([
                'child_id' => $childContextId,
                'parent_id' => $parentContextId,
                'subscription_id' => $subscriptionContextId,
            ], function ($value) {
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

        const parseHtml = (html) => {
            const parser = new DOMParser();
            return parser.parseFromString(html, 'text/html');
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
            return true;
        };

        const loadPage = async (href, { push = true } = {}) => {
            const currentWrapper = document.querySelector('.content-wrapper');
            if (currentWrapper) {
                currentWrapper.style.opacity = '0.65';
                currentWrapper.style.pointerEvents = 'none';
            }

            try {
                const res = await fetch(href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                const text = await res.text();
                const nextDoc = parseHtml(text);
                const ok = swapContentWrapper(nextDoc);

                if (ok && push) {
                    history.pushState({ href }, '', href);
                }
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
            return loadPage(href, { push: true });
        };
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
