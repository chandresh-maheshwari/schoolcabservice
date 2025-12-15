@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/footer-custom.css') }}">
@endpush
<!--Footer Section ======================-->
<footer id="footer" class="footer dark-background">

    <div class="footer-newsletter">
      <div class="container">
        <div class="row justify-content-center text-center">
          <div class="col-lg-6">
            <h4>Join Our Newsletter</h4>
            <p>Subscribe to our newsletter and receive the latest news about our products and services!</p>
            <form id="newsletter-form" method="post" class="php-email-form">
              <div class="newsletter-form">
                <input type="email" name="email" id="newsletter-email">
                <input type="submit" value="Subscribe"></div>
              <div class="loading">Loading</div>
              <div class="error-message"></div>
              <div class="sent-message">Your subscription request has been sent. Thank you!</div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-4 col-md-6 footer-about">
          <a id="footer-brand-link" href="/" class="d-flex align-items-center">
            <span class="sitename" id="footer-sitename"></span>
          </a>
          <div class="footer-contact pt-3">
            <p id="footer-address"></p>
            <p class="mt-3"><strong id="footer-contact-title"></strong> <span id="footer-phone"></span></p>
            <p><strong id="footer-email-title"></strong> <span id="footer-email"></span></p>
          </div>
        </div>

        <div class="col-lg-2 col-md-3 footer-links">
          <h4 id="footer-link-title"></h4>
          <ul id="footer-links-list">
            <!-- Dynamic links -->
          </ul>
        </div>

        <div class="col-lg-2 col-md-3 footer-links">
          <h4 id="footer-service-title"></h4>
          <ul id="footer-services-list">
            <!-- Dynamic services -->
          </ul>
        </div>

        <div class="col-lg-4 col-md-12">
          <h4 id="footer-follow-title"></h4>
          <p id="footer-description"></p>
          <div class="social-links d-flex">
            <a href="" id="footer-twitter"><i class="bi bi-twitter-x"></i></a>
            <a href="" id="footer-facebook"><i class="bi bi-facebook"></i></a>
            <a href="" id="footer-instagram"><i class="bi bi-instagram"></i></a>
            <a href="" id="footer-linkedin"><i class="bi bi-linkedin"></i></a>
          </div>
        </div>

      </div>
    </div>

    <div class="container copyright text-center mt-4">
      <p id="footer-copyright"></p>
      <div class="credits">
        <!-- All the links in the footer should remain intact. -->
        <!-- You can delete the links only if you've purchased the pro version. -->
        <!-- Licensing information: https://bootstrapmade.com/license/ -->
        <!-- Purchase the pro version with working PHP/AJAX contact form: [buy-url] -->
        {{-- Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a> --}}
      </div>
    </div>

  </footer>
  <script>
document.getElementById('newsletter-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const email = document.getElementById('newsletter-email').value;
    const loading = document.querySelector('.loading');
    const errorMsg = document.querySelector('.error-message');
    const successMsg = document.querySelector('.sent-message');

    loading.style.display = 'block';
    errorMsg.textContent = '';
    successMsg.textContent = '';

    fetch('/api/frontend/subscribe', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}' // important in Laravel
        },
        body: JSON.stringify({ email })
    })
    .then(res => res.json())
    .then(data => {
        loading.style.display = 'none';
        if (data.message) {
            successMsg.textContent = data.message;
        } else {
            errorMsg.textContent = 'Something went wrong.';
        }
    })
    .catch(error => {
        loading.style.display = 'none';
        errorMsg.textContent = 'Error: ' + error.message;
    });
});


// <!--Footer Section ======================-->
(function() {
    // Primary footer content
    fetch('/api/frontend/footer')
        .then(r => r.json())
        .then(payload => {
            var data = (payload && payload.data) ? payload.data : payload;
            if (!data) return;
            // Address
            document.getElementById('footer-address').innerHTML = data.address || '';
            // Phone
            document.getElementById('footer-contact-title').textContent = data.contact_title || 'Phone:';
            document.getElementById('footer-phone').textContent = data.contact || '';
            // Email
            document.getElementById('footer-email-title').textContent = data.email_title || 'Email:';
            document.getElementById('footer-email').textContent = data.email || '';
            // Brand title (handle duplicates safely)
            try {
                var brandEls = document.querySelectorAll('[id="footer-sitename"]');
                brandEls.forEach(function(el){ el.textContent = data.title || el.textContent; });
            } catch(e) {}
            var brandLink = document.getElementById('footer-brand-link');
            if (brandLink && data.brand_link) brandLink.setAttribute('href', data.brand_link);
            // Links
            document.getElementById('footer-link-title').textContent = data.footer_link_title || 'Useful Links';
            var linksList = document.getElementById('footer-links-list');
            linksList.innerHTML = '';
            (data.footer_links || []).forEach(function(link) {
                if (link && link.title && link.link) {
                    var li = document.createElement('li');
                    li.innerHTML = '<i class="bi bi-chevron-right"></i> <a href="' + link.link + '">' + link.title + '</a>';
                    linksList.appendChild(li);
                }
            });
            // Services
            // Service section title (handle duplicates safely)
            try {
                var serviceTitleEls = document.querySelectorAll('[id="footer-service-title"]');
                serviceTitleEls.forEach(function(el){ el.textContent = data.footer_service_title || 'Our Services'; });
            } catch(e) {}
            var servicesList = document.getElementById('footer-services-list');
            servicesList.innerHTML = '';
            (data.footer_services || []).forEach(function(service) {
                if (service && service.title && service.link) {
                    var li = document.createElement('li');
                    li.innerHTML = '<i class="bi bi-chevron-right"></i> <a href="' + service.link + '">' + service.title + '</a>';
                    servicesList.appendChild(li);
                }
            });
            // Follow Us & Description
            document.getElementById('footer-follow-title').textContent = data.follow_us || 'Follow Us';
            document.getElementById('footer-description').innerHTML = data.description || '';
            // Copyright
            document.getElementById('footer-copyright').textContent = data.copy_right_text || '';
        })
        .catch(function(){ });

    // Author social links (dynamic icons + hrefs)
    fetch('/api/socials-media/all')
        .then(r => r.json())
        .then(payload => {
            var list = (payload && payload.data) ? payload.data : payload;
            if (!list || !Array.isArray(list)) return;
            var wrap = document.querySelector('#footer .social-links');
            if (!wrap) return;
            wrap.innerHTML = '';
            list.forEach(function(item){
                var href = item.social_link || item.link || item.url || '';
                if (!href) return;
                var icon = item.social_icon || '';
                var name = item.name || '';
                var a = document.createElement('a');
                a.setAttribute('href', href);
                a.setAttribute('target', '_blank');
                a.setAttribute('rel', 'noopener');
                if (name) a.setAttribute('title', name);
                // Prefer provided icon class (Font Awesome), fallback to a generic link icon
                a.innerHTML = '<i class="' + (icon || 'bi bi-link-45deg') + '"></i>';
                wrap.appendChild(a);
            });
        })
        .catch(function(){});
})();
  </script>
<!--Footer Section ======================-->
