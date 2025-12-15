{{-- @php($data = $data ?? null)

<section id="hero" class="hero section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <div class="hero-content" data-aos="fade-up" data-aos-delay="200">
              <h2>{{ $data->title ?? 'Strategic Solutions for Business Growth' }}</h2>
              <p>{!! $data->description ?? 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.' !!}</p>
              <div class="hero-btns">
                <a href="#contact" class="btn btn-primary">{{ $data->button_title_1 ?? 'Get a Free Consultation' }}</a>
                <a href="#services" class="btn btn-outline">{{ $data->button_title_2 ?? 'Our Services' }}</a>
              </div>
              <div class="hero-stats">
                <div class="stat-item">
                  <h3><span data-purecounter-start="0" data-purecounter-end="{{ $data->stat_counter_1 ?? 15 }}" data-purecounter-duration="1" class="purecounter"></span>+</h3>
                  <p>{{ $data->stat_title_1 ?? 'Years Experience' }}</p>
                </div>
                <div class="stat-item">
                  <h3><span data-purecounter-start="0" data-purecounter-end="500" data-purecounter-duration="1" class="purecounter"></span>+</h3>
                  <p>{{ $data->stat_title_2 ?? 'Clients Worldwide' }}</p>
                </div>
                <div class="stat-item">
                  <h3><span data-purecounter-start="0" data-purecounter-end="98" data-purecounter-duration="1" class="purecounter"></span>%</h3>
                  <p>{{ $data->stat_title_3 ?? 'Success Rate' }}</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="hero-image" data-aos="zoom-out" data-aos-delay="300">
              <img src={{ $data->image ?? 'assets/img/about/about-8.webp'}} alt="Consulting Services" class="img-fluid">
            </div>
          </div>
        </div>
      </div>
    </section><!-- /Hero Section -->


 --}}


<!-- Hero Section -->
<section id="hero" class="hero section">
    <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row align-items-center">

            <div class="col-lg-6">
                <div class="hero-image" data-aos="zoom-out" data-aos-delay="300">
                    <img src="assets/img/about/about-8.webp" alt="Consulting Services" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
 </section><!-- /Hero Section -->

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('#hero .container .row');
        const pageImage = @json($page->image ?? null);
        const fallbackImg = '{{ asset('images/Default.jpg') }}';

        // Function to check if image exists and return appropriate URL
        const getImageUrl = (imagePath) => {
            if (!imagePath || imagePath === '') {
                return fallbackImg;
            }

            // If it's already an absolute URL, return as is
            if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
                return imagePath;
            }

            // Clean the path and construct the full URL
            const cleanPath = imagePath.startsWith('/') ? imagePath.substring(1) : imagePath;
            const fullUrl = '{{ asset('') }}' + cleanPath;

            return fullUrl;
        };

        // Function to check if image exists by trying to load it
        const checkImageExists = (url) => {
            return new Promise((resolve) => {
                const img = new Image();
                img.onload = () => resolve(true);
                img.onerror = () => resolve(false);
                img.src = url;
            });
        };

        fetch('/api/frontend/hero')
            .then(response => response.json())
            .then(async payload => {
                if (!payload || !payload.success || !payload.data) return;
                const raw = payload.data;
                const h = Array.isArray(raw) ? (raw[0] || {}) : raw;

                const fullText = h.description || '';
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = fullText;
                const plainText = tempDiv.textContent || tempDiv.innerText || '';
                const shortText = plainText.length > 200 ? plainText.substring(0, 200) + '...' :
                    plainText;
                const isExpandable = plainText.length > 200;

                const title = h.title || 'Strategic Solutions for Business Growth';
                // const description = h.description ||
                //     'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';
                // const description = shortText ||
                //     'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';
                const btn1 = h.button_title_1 || 'Get a Free Consultation';
                const btn2 = h.button_title_2 || 'Our Services';

                // Prefer page image, then API image; check existence directly
                const candidate = h.image || pageImage || '';
                const imageUrl = getImageUrl(candidate);

                // Check if the image exists, if not use default
                const exists = await checkImageExists(imageUrl);
                const image = exists ? imageUrl : fallbackImg;

                // Stats with safe defaults
                const stat1 = (h.stat_counter_1 !== undefined ? h.stat_counter_1 : 15);
                const stat1Title = h.stat_title_1 || 'Years Experience';
                const statIcon1 = h.stat_icon_1 || 'fas fa-plus';
                const stat2 = (h.stat_counter_2 !== undefined ? h.stat_counter_2 : 500);
                const stat2Title = h.stat_title_2 || 'Clients Worldwide';
                const statIcon2 = h.stat_icon_2 || 'fas fa-plus';
                const stat3 = (h.stat_counter_3 !== undefined ? h.stat_counter_3 : 98);
                const stat3Title = h.stat_title_3 || 'Success Rate';
                const statIcon3 = h.stat_icon_3 || 'fas fa-percent';

                const html = `
        <div class="col-lg-6">
          <div class="hero-content" data-aos="fade-up" data-aos-delay="200">
            <h2>${title}</h2>
            
            ${isExpandable ? `<div class="description-wrapper" data-expanded="false">
        <span class="description-text">${shortText}</span>
        <div class="full-description" style="display:none;">${fullText}</div>
        <a href="javascript:void(0);" class="toggle-front-description" style="margin-left: 5px; color: #007bff;">Read More</a>
      </div>`
        : `<span>${plainText}</span>`}
            <div class="hero-btns">
              <a href="#contact" class="btn btn-primary">${btn1}</a>
              <a href="#services" class="btn btn-outline">${btn2}</a>
            </div>
            <div class="hero-stats">
              <div class="stat-item">
                <h3><span data-purecounter-start="0" data-purecounter-end="${stat1}" data-purecounter-duration="1" class="purecounter"></span><i class="${statIcon1} icon_css"></i></h3>
                <p>${stat1Title}</p>
              </div>
              <div class="stat-item">
                <h3><span data-purecounter-start="0" data-purecounter-end="${stat2}" data-purecounter-duration="1" class="purecounter"></span><i class="${statIcon2} icon_css"></i></h3>
                <p>${stat2Title}</p>
              </div>
              <div class="stat-item">
                <h3><span data-purecounter-start="0" data-purecounter-end="${stat3}" data-purecounter-duration="1" class="purecounter"></span><i class="${statIcon3} icon_css"></i></h3>
                <p>${stat3Title}</p>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="hero-image" data-aos="zoom-out" data-aos-delay="300">
            <img src="${image}" alt="Consulting Services" class="img-fluid" style="width:100%;height:100%;object-fit:contain;">
          </div>
        </div>`;

                if (container) {
                    container.innerHTML = html;
                    if (typeof AOS !== 'undefined') AOS.refresh();
                    if (typeof PureCounter !== 'undefined') new PureCounter();
                }
            })
            .catch(() => {
                // leave static content as fallback
            });
    });
</script>
