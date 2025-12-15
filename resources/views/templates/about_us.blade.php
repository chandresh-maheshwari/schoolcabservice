<!-- About Section -->
{{-- <section id="about" class="about section light-background">
            <div class="container section-title" data-aos="fade-up">
                <h2>{{ $page->title }}</h2>
                <p>{!! $page->description !!}</p>
            </div>

            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <div class="row gy-4 align-items-center justify-content-between">

                    <div class="col-xl-5" data-aos="fade-up" data-aos-delay="200">
                        <span class="about-meta">MORE ABOUT US</span>
                        <h2 class="about-title">{{ $data->title }}</h2>
                        <p class="about-description">{!! $data->description !!}</p>

                        <div class="row feature-list-wrapper">
                            <div class="col-md-6">
                                <ul class="feature-list">
                                    <li><i class="bi bi-check-circle-fill"></i> {{ $data->feature_1 }}</li>
                                    <li><i class="bi bi-check-circle-fill"></i> {{ $data->feature_2 }}</li>
                                    <li><i class="bi bi-check-circle-fill"></i> {{ $data->feature_3 }}</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="feature-list">
                                    <li><i class="bi bi-check-circle-fill"></i> {{ $data->feature_4 }}</li>
                                    <li><i class="bi bi-check-circle-fill"></i> {{ $data->feature_5 }}</li>
                                    <li><i class="bi bi-check-circle-fill"></i> {{ $data->feature_6 }}</li>
                                </ul>
                            </div>
                        </div>

                        <div class="info-wrapper">
                            <div class="row gy-4">
                                <div class="col-lg-5">
                                    <div class="profile d-flex align-items-center gap-3">
                                        <img src="assets/img/person/person-m-2.webp" alt="CEO Profile"
                                            class="profile-image">
                                        <div>
                                            <h4 class="profile-name">{{ $data->profile_name }}</h4>
                                            <p class="profile-position">{{ $data->profile_position }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="contact-info d-flex align-items-center gap-2">
                                        <i class="bi bi-telephone-fill"></i>
                                        <div>
                                            <p class="contact-label">Call us anytime</p>
                                            <p class="contact-number">{{ $data->contact_number }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6" data-aos="fade-up" data-aos-delay="300">
                        <div class="image-wrapper">
                            <div class="images position-relative" data-aos="zoom-out" data-aos-delay="400">
                                <img src="assets/img/about/about-5.webp" alt="Business Meeting"
                                    class="img-fluid main-image rounded-4">
                                <img src="assets/img/about/about-square-1.webp" alt="Team Discussion"
                                    class="img-fluid small-image rounded-4">
                            </div>
                            <div class="experience-badge floating">
                                <h3>15+ <span>Years</span></h3>
                                <p>Of experience in business service</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </section><!-- /About Section --> --}}

<section id="about" class="about section light-background">
    <div class="container section-title" data-aos="fade-up">
        <h2>{{ $page->title ?? 'About Us' }}</h2>
        <p>{!! $page->description ?? '' !!}</p>
    </div>

    <script>
        // Expose Blade $page to JS
        window.pageData = @json($page);
    </script>

    <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row gy-4 align-items-center justify-content-between"
            id="about-container-{{ $page->id ?? 'default' }}">
            {{-- @if (isset($data) && $data)
                <div class="col-xl-5" data-aos="fade-up" data-aos-delay="200">
                    <span class="about-meta">MORE ABOUT US</span>
                    <h2 class="about-title">{{ $data->title }}</h2>
                    <p class="about-description">{!! $data->description !!}</p>

                    <div class="row feature-list-wrapper">
                        <div class="col-md-6">
                            <ul class="feature-list">
                                <li><i class="bi bi-check-circle-fill"></i> {{ $data->feature_1 }}</li>
                                <li><i class="bi bi-check-circle-fill"></i> {{ $data->feature_2 }}</li>
                                <li><i class="bi bi-check-circle-fill"></i> {{ $data->feature_3 }}</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="feature-list">
                                <li><i class="bi bi-check-circle-fill"></i> {{ $data->feature_4 }}</li>
                                <li><i class="bi bi-check-circle-fill"></i> {{ $data->feature_5 }}</li>
                                <li><i class="bi bi-check-circle-fill"></i> {{ $data->feature_6 }}</li>
                            </ul>
                        </div>
                    </div>

                    <div class="info-wrapper">
                        <div class="row gy-4">
                            <div class="col-lg-5">
                                <div class="profile d-flex align-items-center gap-3">
                                    <img src="{{ $data->profile_image ?? 'assets/img/person/person-m-2.webp' }}" alt="CEO Profile"
                                        class="profile-image">
                                    <div>
                                        <h4 class="profile-name">{{ $data->profile_name }}</h4>
                                        <p class="profile-position">{{ $data->profile_position }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <div class="contact-info d-flex align-items-center gap-2">
                                    <i class="bi bi-telephone-fill"></i>
                                    <div>
                                        <p class="contact-label">Call us anytime</p>
                                        <p class="contact-number">{{ $data->contact_number }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="image-wrapper">
                        <div class="images position-relative" data-aos="zoom-out" data-aos-delay="400">
                            <img src="{{ $data->image ?? 'assets/img/about/about-5.webp' }}" alt="Business Meeting"
                                class="img-fluid main-image rounded-4">
                            <img src="assets/img/about/about-square-1.webp" alt="Team Discussion"
                                class="img-fluid small-image rounded-4">
                        </div>
                        <div class="experience-badge floating">
                            <h3>15+ <span>Years</span></h3>
                            <p>Of experience in business service</p>
                        </div>
                    </div>
                </div> --}}
            {{-- @else
                <div class="col-12 text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            @endif --}}
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('about-container-{{ $page->id ?? 'default' }}');
        const fallbackMain = '{{ asset('images/Default.jpg') }}';
        const fallbackSmall = '{{ asset('images/Default.jpg') }}';
        const profileImage = '{{ asset('images/Default.jpg') }}';

        // Function to check if image exists and return appropriate URL
        const getImageUrl = (imagePath) => {
            if (!imagePath || imagePath === '') {
                return fallbackMain;
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

        const page = (typeof window !== 'undefined' && window.pageData) ? window.pageData : {};

        fetch('/api/frontend/about-us')
            .then(response => response.json())
            .then(async data => {
                if (data && data.success && data.data) {
                    const about = data.data;

                    const fullText = about.description || '';
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = fullText;
                    const plainText = tempDiv.textContent || tempDiv.innerText || '';
                    const shortText = plainText.length > 200 ? plainText.substring(0, 200) + '...' :
                        plainText;
                    const isExpandable = plainText.length > 200;

                    // Process main image
                    const mainImagePath = page && page.image ? page.image : fallbackMain;
                    const mainImageUrl = getImageUrl(mainImagePath);
                    const mainExists = await checkImageExists(mainImageUrl);
                    const mainImg = mainExists ? mainImageUrl : fallbackMain;

                    // Process small image
                    const smallImagePath = about && about.image ? about.image : fallbackSmall;
                    const smallImageUrl = getImageUrl(smallImagePath);
                    const smallExists = await checkImageExists(smallImageUrl);
                    const smallImg = smallExists ? smallImageUrl : fallbackSmall;

                    // Process profile image
                    const profileImagePath = about && about.profile_image ? about.profile_image :
                        profileImage;
                    const profileImageUrl = getImageUrl(profileImagePath);
                    const profileExists = await checkImageExists(profileImageUrl);
                    const profileImg = profileExists ? profileImageUrl : profileImage;

                    // <p class="about-description">${about.description ?? ''}</p>
                    container.innerHTML = `
                    <div class="col-xl-5" data-aos="fade-up" data-aos-delay="200">
                        <span class="about-meta">MORE ABOUT US</span>
                        <h2 class="about-title">${about.title ?? ''}</h2>
                         ${isExpandable ? `<div class="description-wrapper" data-expanded="false">
        <span class="description-text">${shortText}</span>
        <div class="full-description" style="display:none;">${fullText}</div>
        <a href="javascript:void(0);" class="toggle-front-description" style="margin-left: 5px; color: #007bff;">Read More</a>
      </div>`
        : `<span>${plainText}</span>`}
                        <div class="row feature-list-wrapper">
                            <div class="col-md-6">
                                <ul class="feature-list">
                                    <li><i class="bi bi-check-circle-fill"></i> ${about.feature_1 ?? ''}</li>
                                    <li><i class="bi bi-check-circle-fill"></i> ${about.feature_2 ?? ''}</li>
                                    <li><i class="bi bi-check-circle-fill"></i> ${about.feature_3 ?? ''}</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="feature-list">
                                    <li><i class="bi bi-check-circle-fill"></i> ${about.feature_4 ?? ''}</li>
                                    <li><i class="bi bi-check-circle-fill"></i> ${about.feature_5 ?? ''}</li>
                                    <li><i class="bi bi-check-circle-fill"></i> ${about.feature_6 ?? ''}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="info-wrapper">
                            <div class="row gy-4">
                                <div class="col-lg-5">
                                    <div class="profile d-flex align-items-center gap-3">
                                        <img src="${profileImg}" alt="CEO Profile" class="profile-image">
                                        <div>
                                            <h4 class="profile-name">${about.profile_name ?? ''}</h4>
                                            <p class="profile-position">${about.profile_position ?? ''}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="contact-info d-flex align-items-center gap-2">
                                        <i class="bi bi-telephone-fill"></i>
                                        <div>
                                            <p class="contact-label">Call us anytime</p>
                                            <p class="contact-number">${about.contact_number ?? ''}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6" data-aos="fade-up" data-aos-delay="300">
                        <div class="image-wrapper">
                            <div class="images position-relative" data-aos="zoom-out" data-aos-delay="400">
                                <img src="${mainImg}" alt="Business Meeting" class="img-fluid main-image rounded-4" style="width:100%;height:100%;object-fit:contain;">
                                <img src="${smallImg}" alt="Team Discussion" class="img-fluid small-image rounded-4" object-fit:contain;">
                            </div>
                            <div class="experience-badge floating">
                                ${about.experience_badge ?? ''}
                            </div>
                        </div>
                    </div>
                `;

                    if (typeof AOS !== 'undefined') {
                        AOS.refresh();
                    }
                } else {
                    container.innerHTML = '';
                    const section = document.getElementById('about');
                    if (section) section.style.display = 'none';
                }
            })
            .catch(() => {
                const hasContent = container.querySelector('.about-title');
                if (!hasContent) {
                    container.innerHTML = '';
                    const section = document.getElementById('about');
                    if (section) section.style.display = 'none';
                }
            });
    });
</script>
