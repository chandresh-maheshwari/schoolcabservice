<!-- Call To Action Section -->
<section id="call-to-action" class="call-to-action section">

    <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="cta-wrapper">
            <div class="background-pattern">
                <div class="wave"></div>
                <div class="geometric-shape shape-1"></div>
                <div class="geometric-shape shape-2"></div>
            </div>

            <div class="row align-items-center">
                <div class="col-lg-6 order-lg-2">
                    <div class="image-section" data-aos="fade-left" data-aos-delay="200">
                        <div class="main-image-wrapper">
                            <img id="cta-main-img" src="{{ asset('images/Default.jpg') }}" alt="CTA Image"
                                class="img-fluid main-img" style="width:100%;height:100%;object-fit:contain;">
                            <div class="overlay-circle circle-1"></div>
                            <div class="overlay-circle circle-2"></div>
                        </div>

                        <div id="cta-stats-container">
                            <!-- Stats cards will be loaded dynamically via API -->
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 order-lg-1">
                    <div class="content-section" data-aos="fade-right" data-aos-delay="300">
                        <div class="label-badge" data-aos="fade-up" data-aos-delay="400" id="cta-label-badge">
                            <i id="cta-badge-icon" class="bi bi-rocket-takeoff"></i>
                            <span id="cta-badge-text">Loading...</span>
                        </div>

                        <h2 data-aos="fade-up" data-aos-delay="450">{{ $page->title ?? '' }}</h2>
                        {{-- <p data-aos="fade-up" data-aos-delay="500">{!! $page->description ?? '' !!}</p> --}}
                        @php
                            $fullText = $page->description ??
                                'Nunc euismod, tortor nec facilisis egestas, ligula turpis cursus odio, a lobortis sapien ipsum et dolor. Morbi dignissim cursus massa non lobortis.';
                            $plainText = trim(strip_tags($fullText));
                            $shortText = strlen($plainText) > 150 ? substr($plainText, 0, 150) . '...' : $plainText;
                            $isExpandable = strlen($plainText) > 150;
                        @endphp
                        @if ($isExpandable)
                            <div class="description-wrapper" data-expanded="false">
                                <span class="description-text">{{ $shortText }}</span>
                                <div class="full-description" style="display:none;">{!! $fullText !!}</div>
                                <a href="javascript:void(0);" class="toggle-front-description"
                                    style="margin-left: 5px; color: #007bff;">Read More</a>
                            </div>
                        @else
                            <span class="description-text">{{ $plainText }}</span>
                        @endif

                        <div class="features-grid" data-aos="fade-up" data-aos-delay="550" id="cta-features"></div>

                        <div class="cta-actions" data-aos="fade-up" data-aos-delay="600">
                            <a href="#" class="btn btn-cta-primary" id="cta-primary-btn">Get Started Free</a>
                            <div class="secondary-action">
                                <a href="#" class="btn btn-cta-secondary glightbox" id="cta-secondary-btn">
                                    <i class="bi bi-play-circle"></i>
                                    <span id="cta-secondary-text">Watch Demo</span>
                                </a>
                                <span class="note" id="cta-note">No credit card required</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</section><!-- /Call To Action Section -->

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get all elements
        const featuresEl = document.getElementById('cta-features');
        const primaryBtn = document.getElementById('cta-primary-btn');
        const secondaryBtn = document.getElementById('cta-secondary-btn');
        const secondaryText = document.getElementById('cta-secondary-text');
        const ctaImgEl = document.getElementById('cta-main-img');
        const badgeText = document.getElementById('cta-badge-text');
        const noteText = document.getElementById('cta-note');
        const statsContainer = document.getElementById('cta-stats-container');
        const labelBadge = document.getElementById('cta-label-badge');

        const pageImage = @json($page->image ?? null);
        const fallbackImg = '{{ asset('images/Default.jpg') }}';

        // Build full URL from API-provided relative path or absolute URL
        const getImageUrl = (imagePath) => {
            if (!imagePath || imagePath === '') return fallbackImg;
            const s = String(imagePath);
            if (s.startsWith('http://') || s.startsWith('https://')) return s;
            const clean = s.startsWith('/') ? s.substring(1) : s;
            return '{{ asset('') }}' + clean;
        };

        // Probe image existence client-side
        const checkImageExists = (url) => new Promise((resolve) => {
            const img = new Image();
            img.onload = () => resolve(true);
            img.onerror = () => resolve(false);
            img.src = url;
        });

        fetch('/api/frontend/call-to-action')
            .then(response => response.json())
            .then(payload => {
                if (!payload || !payload.success || !payload.data || !Array.isArray(payload.data) || payload
                    .data.length === 0) {
                    // Hide section if no data
                    const ctaSection = document.getElementById('call-to-action');
                    if (ctaSection) ctaSection.style.display = 'none';
                    return;
                }

                const cta = payload.data[0]; // Get first item from array

                // Debug: Log the data to see what we're working with
                // console.log('CTA Data:', cta);

                // Update image
                const candidate = getImageUrl(cta.image || pageImage || '');
                checkImageExists(candidate).then(ok => {
                    if (ok && ctaImgEl) ctaImgEl.src = candidate;
                });

                // Update badge
                if (cta.badge_title && badgeText) {
                    badgeText.textContent = cta.badge_title;
                }

                // Update badge icon
                if (cta.badge_icon) {
                    const badgeIcon = document.getElementById('cta-badge-icon');
                    if (badgeIcon) {
                        badgeIcon.className = cta.badge_icon;
                    }
                }

                // Create stats cards dynamically
                if (statsContainer) {
                    statsContainer.innerHTML = '';

                    const stats = [{
                            value: cta.stat_count_1,
                            label: cta.stat_text_1,
                            icon: cta.stat_icon_1 || 'bi bi-graph-up-arrow'
                        },
                        {
                            value: cta.stat_count_2,
                            label: cta.stat_text_2,
                            icon: cta.stat_icon_2 || 'bi bi-trophy-fill'
                        },
                        {
                            value: cta.stat_count_3,
                            label: cta.stat_text_3,
                            icon: cta.stat_icon_3 || 'bi bi-award'
                        },
                        {
                            value: cta.stat_count_4,
                            label: cta.stat_text_4,
                            icon: cta.stat_icon_4 || 'bi bi-people'
                        }
                    ].filter(stat => stat.value && stat.label);

                    stats.forEach((stat, index) => {
                        const statsCard = document.createElement('div');
                        statsCard.className = `stats-card card-${index + 1}`;
                        statsCard.setAttribute('data-aos', 'zoom-in');
                        statsCard.setAttribute('data-aos-delay', `${400 + index * 100}`);

                        statsCard.innerHTML = `
                        <div class="stat-icon">
                            <i class="${stat.icon}"></i>
                        </div>
                        <div class="stat-content">
                            <h6>${stat.value}</h6>
                            <span>${stat.label}</span>
                        </div>
                    `;

                        statsContainer.appendChild(statsCard);
                    });
                }

                // Update features
                const features = [cta.feature_1, cta.feature_2, cta.feature_3, cta.feature_4, cta.feature_5,
                        cta.feature_6
                    ]
                    .filter(Boolean)
                    .map((text, idx) => `
                    <div class="feature-item" data-aos="fade-up" data-aos-delay="${550 + idx * 50}">
                        <div class="feature-icon">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <span>${text}</span>
                    </div>
                `)
                    .join('');

                if (featuresEl) featuresEl.innerHTML = features;

                // Update primary button
                if (cta.button_title && primaryBtn) {
                    primaryBtn.textContent = cta.button_title;
                }
                if (cta.button_link && primaryBtn) {
                    primaryBtn.setAttribute('href', cta.button_link);
                }

                // Update secondary button
                if (cta.secondary_button_title && secondaryText) {
                    secondaryText.textContent = cta.secondary_button_title;
                }
                if (cta.secondary_button_link && secondaryBtn) {
                    secondaryBtn.setAttribute('href', cta.secondary_button_link);
                }

                // Update note
                if (cta.note_text && noteText) {
                    noteText.textContent = cta.note_text;
                }

                // Hide elements if no data
                // if (!cta.badge_text && labelBadge) labelBadge.style.display = 'none';
                if (!cta.secondary_button_title && secondaryBtn) secondaryBtn.style.display = 'none';
                if (!cta.note_text && noteText) noteText.style.display = 'none';

                if (typeof AOS !== 'undefined') {
                    AOS.refresh();
                }
            })
            .catch(() => {
                // On error, fallback to page image if available
                const candidate = getImageUrl(pageImage || '');
                checkImageExists(candidate).then(ok => {
                    if (ok && ctaImgEl) ctaImgEl.src = candidate;
                });

                // Hide section on error
                const ctaSection = document.getElementById('call-to-action');
                if (ctaSection) ctaSection.style.display = 'none';
            });
    });
</script>
