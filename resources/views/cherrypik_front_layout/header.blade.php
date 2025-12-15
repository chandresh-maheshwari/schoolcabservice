@php
    use App\Helpers\IdEncoder;
    // $encodedId = auth()->check() ? IdEncoder::encode(auth()->user()->id) : null;
    // $menuActive = (
    //     Request::is('about*') ||
    //     Request::is('contact*') ||
    //     Request::is('guidelines*') ||
    //     Request::is('authors*') ||
    //     Request::is('magazine*') ||
    //     Request::is('quotes*')
    // );
    // $blogActive = (
    //     Request::is('all-blogs*') ||
    //     Request::is('add-blog*')
    // );
@endphp

@push('styles')
{{-- <link rel="stylesheet" href="{{ asset('assets/css/header-custom.css') }}"> --}}
@endpush
<header id="header" class="header d-flex align-items-center sticky-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">
        <a id="header-logo" href="/" class="logo d-flex align-items-center me-auto">
            <!-- If API returns logo, we'll swap this dynamically -->
            <h1 id="header-sitename" class="sitename">Loading...</h1>
        </a>
        <nav id="navmenu" class="navmenu">
            <ul id="navbar-pages"></ul>
            <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>
        <a id="header-cta" class="btn-getstarted" href="#">Loading...</a>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var list = document.getElementById('navbar-pages');
  if (list) {
    fetch('/api/frontend/navbar-pages').then(r => r.json())
      .then(p => {
        var currentPath = location.pathname;
        let html = '';
        // Home always first
        var homeActive = (currentPath === '/' || currentPath === '/home') ? 'active' : '';
        html += `<li><a href="/" class="${homeActive}">Home</a></li>`;
        if (p && p.success && Array.isArray(p.data)) {
          html += p.data
            .filter(function(page){
              var name = (page.title || page.name || page.slug || '').toString().trim().toLowerCase();
              var tmpl = (page.template || '').toString().trim().toLowerCase();
              // Prevent duplicate Home item if API also sends a Home-like entry
              return name !== 'home' && tmpl !== 'home' && page.slug !== 'home';
            })
            .map(function(page) {
              var name = page.title || page.name || page.slug;
              var tmpl = page.template || '';
              var href = '/page/' + encodeURIComponent(tmpl);
              var active = currentPath === href ? 'active' : '';
              return `<li><a href="${href}" class="${active}">${name}</a></li>`;
            }).join('');
        }
        list.innerHTML = html;
      }).catch(function() {
        var currentPath = location.pathname;
        function li(label, slug) {
          var href = '/page/' + slug;
          var active = currentPath === href ? 'active' : '';
          return `<li><a href="${href}" class="${active}">${label}</a></li>`;
        }
        let html = `<li><a href="/" class="${(currentPath === '/' || currentPath === '/home') ? 'active' : ''}">Home</a></li>`;
        // Fallback items (no duplicate Home)
        html += li('About','about_us') + li('Services','services') + li('Contact','contacts');
        list.innerHTML = html;
      });
  }
  // Header config (site name, links, cta)
  var siteNameEl = document.getElementById('header-sitename');
  var logoLink = document.getElementById('header-logo');
  var ctaBtn = document.getElementById('header-cta');
  fetch('/api/frontend/header-config')
    .then(r => r.json())
    .then(cfg => {
      if (!cfg || !cfg.success || !cfg.data) return;
      var d = Array.isArray(cfg.data) ? (cfg.data[0] || null) : cfg.data;
      if (!d) return;
      // Site name text
      if (siteNameEl) siteNameEl.textContent = d.title || 'Cherrypik';
      // CTA button
      if (ctaBtn) {
        if (d.button_title) ctaBtn.textContent = d.button_title;
        ctaBtn.setAttribute('href', d.button_link || '#');
      }
    })
    .catch(function(){});
});
</script>


