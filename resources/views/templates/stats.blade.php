 {{-- <!-- Stats Section -->
 <section id="stats" class="stats section">

    <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="stats-hero row align-items-center mb-5">
            <div class="col-lg-7 mb-4 mb-lg-0" data-aos="fade-right" data-aos-delay="200">
                <h3 class="headline mb-3">{{ $page->title ?? 'Transforming Data Into Impactful Insights' }}</h3>
                <p class="lead">{!! $page->description ?? 'Nunc euismod, tortor nec facilisis egestas, ligula turpis cursus odio, a lobortis sapien ipsum et dolor. Morbi dignissim cursus massa non lobortis.' !!}</p>
            </div>
            <div class="col-lg-5 text-lg-end text-center" data-aos="zoom-in" data-aos-delay="300">
                <div class="stats-rating d-inline-flex align-items-center py-3 px-4 shadow-sm">
                    <img src="assets/img/about/about-1.webp" class="img-fluid stats-rating-img me-3"
                        alt="User Group" width="64" height="48">
                    <div>
                        <div class="rating-score d-flex align-items-center mb-1">
                            <span id="google-rating" class="fs-5 fw-semibold me-2">4.8/5</span>
                            <span id="google-stars" class="stars"></span>
                        </div>
                        <div class="user-feedback small" id="google-total">Based on 70+ unique user reviews</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="stats-counters row gy-4 justify-content-center">
            <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="100">
                <article class="stats-counter-card">
                    <div class="counter-value mb-1">
                        <span data-purecounter-start="0" data-purecounter-end="120"
                            data-purecounter-duration="1.5" class="purecounter"></span>K+
                    </div>
                    <small class="label">Active Clients</small>
                </article>
            </div>
            <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="200">
                <article class="stats-counter-card">
                    <div class="counter-value mb-1">
                        <span data-purecounter-start="0" data-purecounter-end="34"
                            data-purecounter-duration="1.5" class="purecounter"></span>K+
                    </div>
                    <small class="label">Analytics Projects</small>
                </article>
            </div>
            <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="300">
                <article class="stats-counter-card">
                    <div class="counter-value mb-1">
                        <span data-purecounter-start="0" data-purecounter-end="97"
                            data-purecounter-duration="1.5" class="purecounter"></span>%
                    </div>
                    <small class="label">Automation Success</small>
                </article>
            </div>
            <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="400">
                <article class="stats-counter-card">
                    <div class="counter-value mb-1">
                        <span data-purecounter-start="0" data-purecounter-end="99.96"
                            data-purecounter-duration="1.5" class="purecounter"></span>%
                    </div>
                    <small class="label">Cloud Reliability</small>
                </article>
            </div>
        </div><!-- End stats counters -->

    </div>

</section><!-- /Stats Section --> --}}

 <section id="stats" class="stats section">

     <div class="container" data-aos="fade-up" data-aos-delay="100">

         <div class="stats-hero row align-items-center mb-5">
             <div class="col-lg-7 mb-4 mb-lg-0" data-aos="fade-right" data-aos-delay="200">
                 <h3 class="headline mb-3">{{ $page->title ?? 'Transforming Data Into Impactful Insights' }}</h3>
                 {{-- <p class="lead">{!! $page->description ?? 'Nunc euismod, tortor nec facilisis egestas, ligula turpis cursus odio, a lobortis sapien ipsum et dolor. Morbi dignissim cursus massa non lobortis.' !!}</p> --}}
                 @php
                     $fullText =
                         $page->description ??
                         'Nunc euismod, tortor nec facilisis egestas, ligula turpis cursus odio, a lobortis sapien ipsum et dolor. Morbi dignissim cursus massa non lobortis.';
                     $plainText = trim(strip_tags($fullText));
                     $shortText = strlen($plainText) > 200 ? substr($plainText, 0, 200) . '...' : $plainText;
                     $isExpandable = strlen($plainText) > 200;
                 @endphp
                 <div class="lead">
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
                 </div>
             </div>
             {{-- DO NOT REMOVE THIS COMMENT IT IS USED FOR THE REVIEW SECTION --}}
             {{-- <div class="col-lg-5 text-lg-end text-center" data-aos="zoom-in" data-aos-delay="300">
                <div class="stats-rating d-inline-flex align-items-center py-3 px-4 shadow-sm">
                    <img src="assets/img/about/about-1.webp" class="img-fluid stats-rating-img me-3"
                        alt="User Group" width="64" height="48">
                    <div>
                        <div class="rating-score d-flex align-items-center mb-1">
                            <span id="google-rating" class="fs-5 fw-semibold me-2">4.8/5</span>
                            <span id="google-stars" class="stars"></span>
                        </div>
                        <div class="user-feedback small" id="google-total">Based on 70+ unique user reviews</div>
                    </div>
                </div>
            </div> --}}
         </div>

         <div class="stats-counters row gy-4 justify-content-center" id="stats-container">
             <div class="col-12 text-center">
                 <div class="spinner-border text-primary" role="status">
                     <span class="visually-hidden">Loading...</span>
                 </div>
             </div>
         </div><!-- End stats counters -->

     </div>

 </section><!-- /Stats Section -->

 <script>
     document.addEventListener('DOMContentLoaded', function() {
         const container = document.getElementById('stats-container');
         const ratingEl = document.getElementById('google-rating');
         const starsEl = document.getElementById('google-stars');
         const totalEl = document.getElementById('google-total');

         // Fetch Google rating summary
         fetch('/api/frontend/google-reviews')
             .then(r => r.json())
             .then(p => {
                 if (!p || !p.success || !p.data) return;
                 const rating = p.data.rating;
                 const total = p.data.user_ratings_total;
                 if (typeof rating === 'number' && ratingEl) {
                     ratingEl.textContent = rating.toFixed(1) + '/5';
                     // Build stars
                     if (starsEl) {
                         const full = Math.floor(rating);
                         const half = (rating - full) >= 0.25 && (rating - full) < 0.75 ? 1 : ((rating -
                             full) >= 0.75 ? 0 : 0);
                         const extraFull = (rating - full) >= 0.75 ? 1 : 0;
                         const totalFull = full + extraFull;
                         let html = '';
                         for (let i = 0; i < totalFull && i < 5; i++) html +=
                             '<i class="bi bi-star-fill"></i>';
                         if (half && totalFull < 5) html += '<i class="bi bi-star-half"></i>';
                         for (let i = (totalFull + half); i < 5; i++) html += '<i class="bi bi-star"></i>';
                         starsEl.innerHTML = html;
                     }
                 }
                 if (typeof total === 'number' && totalEl) {
                     totalEl.textContent = 'Based on ' + total + ' Google reviews';
                 }
             })
             .catch(() => {});

         fetch('/api/frontend/stats')
             .then(response => response.json())
             .then(payload => {
                 if (payload && payload.success && Array.isArray(payload.data) && payload.data.length > 0) {
                     container.innerHTML = '';
                     payload.data.forEach((item, index) => {
                         const delay = 100 + (index * 100);
                         // const statIcon = item.stat_icon;
                         const statHtml = `
                        <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="${delay}">
                            <article class="stats-counter-card">    
                                <div class="counter-value mb-1">
                                    <span data-purecounter-start="0" data-purecounter-end="${item.stats_counter ?? 0}"
                                        data-purecounter-duration="1.5" class="purecounter"></span>
                                        <i class="${item.stat_icon} icon_css"></i>
                                </div>
                                <small class="label">${item.stats_title ?? ''}</small>
                            </article>
                        </div>
                    `;
                         container.insertAdjacentHTML('beforeend', statHtml);
                     });

                     if (typeof AOS !== 'undefined') {
                         AOS.refresh();
                     }
                     if (typeof PureCounter !== 'undefined') {
                         new PureCounter();
                     }
                 } else {
                     // No stats → hide section
                     container.innerHTML = '';
                     const section = document.getElementById('stats');
                     if (section) section.style.display = 'none';
                 }
             })
             .catch(() => {
                 // On error → hide if nothing rendered
                 const hasItem = container.querySelector('.stats-counter-card');
                 if (!hasItem) {
                     container.innerHTML = '';
                     const section = document.getElementById('stats');
                     if (section) section.style.display = 'none';
                 }
             });
     });
 </script>
