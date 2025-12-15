 <!-- advance_capabilities Section -->
 <section id="advance_capabilities" class="advance_capabilities section features">

     <div class="container" data-aos="fade-up" data-aos-delay="100">
         <div class="row gy-4 align-items-center justify-content-between"
             id="advance_capabilities-container-{{ $page->id ?? 'default' }}">
             <div class="advanced-features-section mt-0" data-aos="fade-up" data-aos-delay="100">
                 <div class="row">
                     <div class="col-lg-8 mx-auto text-center mb-5" data-aos="fade-up" data-aos-delay="200">
                         <h3>{{ $page->title ?? 'Advanced capabilities' }}</h3>
                         <p>{!! $page->description ?? 'Discover powerful features designed to elevate your business operations to the next level
                             of excellence.' !!}</p>
                     </div>
                 </div>

                 <div class="row g-4" id="advance_capabilities-grid">
                     <div class="col-12 text-center">
                         <div class="spinner-border text-primary" role="status">
                             <span class="visually-hidden">Loading...</span>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
     </div>

 </section><!-- /Features Section -->

 <script>
     document.addEventListener('DOMContentLoaded', function() {
         const grid = document.getElementById('advance_capabilities-grid');

         fetch('/api/frontend/advance-capabilities')
             .then(response => response.json())
             .then(payload => {
                 if (payload && payload.success && Array.isArray(payload.data) && payload.data.length > 0) {
                     grid.innerHTML = '';
                     payload.data.forEach((item, index) => {
                         const number = String(index + 1).padStart(2, '0');
                         const delay = 300 + (index * 100);
                         const badge = (item.feature_status_badge || '').toLowerCase();
                         const badgeClass = badge === 'enterprise' ? 'enterprise' : badge ===
                             'premium' ? 'premium' : badge === 'coming soon' ? 'coming-soon' :
                             'standard';
                         const benefit1 = item.feature_benifit_1 || '';
                         const benefit2 = item.feature_benifit_2 || '';
                         const title = item.title || '';
                         const description = item.description || '';
                         const icon = item.advance_capability_icon || 'bi bi-diagram-3';
                         // <p>${description}</p>
                         const fullText = description ?? '';
                         const tempDiv = document.createElement('div');
                         tempDiv.innerHTML = fullText;
                         const plainText = tempDiv.textContent || tempDiv.innerText || '';
                         const shortText = plainText.length > 110 ? plainText.substring(0, 110) +
                             '...' : plainText;
                         const isExpandable = plainText.length > 110;
                         const card = `
                        <div class="col-lg-6" data-aos="fade-${index % 2 === 0 ? 'right' : 'left'}" data-aos-delay="${delay}">
                            <div class="advanced-feature-card">
                                <div class="feature-header">
                                    <div class="feature-number">${number}</div>
                                    <div class="feature-status-badge ${badgeClass}">${item.feature_status_badge || ''}</div>
                                </div>
                                <div class="feature-content">
                                    <h5>${title}</h5>
                                    ${isExpandable ? `<div class="description-wrapper" data-expanded="false">
                                     <span class="description-text">${shortText}</span>
                                     <div class="full-description" style="display:none;">${fullText}</div>
                                     <a href="javascript:void(0);" class="toggle-front-description" style="margin-left: 5px; color: #007bff;">Read More</a>
                                 </div>`
                                    : `<span class="description-text">${plainText}</span>`}
                                    <ul class="feature-benefits">
                                        ${benefit1 ? `<li><i class="bi bi-check-circle"></i> ${benefit1}</li>` : ''}
                                        ${benefit2 ? `<li><i class="bi bi-check-circle"></i> ${benefit2}</li>` : ''}
                                    </ul>
                                </div>
                                <div class="feature-icon-bg">
                                    <i class="${icon}"></i>
                                </div>
                            </div>
                        </div>
                    `;
                         grid.insertAdjacentHTML('beforeend', card);
                     });

                     if (typeof AOS !== 'undefined') {
                         AOS.refresh();
                     }
                 } else {
                     grid.innerHTML = '';
                     const section = document.getElementById('advance_capabilities');
                     if (section) section.style.display = 'none';
                 }
             })
             .catch(() => {
                 const hasItems = grid.querySelector('.advanced-feature-card');
                 if (!hasItems) {
                     grid.innerHTML = '';
                     const section = document.getElementById('advance_capabilities');
                     if (section) section.style.display = 'none';
                 }
             });
     });
 </script>
