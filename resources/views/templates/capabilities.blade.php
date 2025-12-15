 <!-- Capabilities Section -->
 <section id="capabilities" class="capabilities section features">

     <!-- Section Title -->
     {{-- <div class="container section-title" data-aos="fade-up">
        {{-- <h2>Capabilities</h2>
        <p>Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit</p> --
    </div> --}}

     <div class="container" data-aos="fade-up" data-aos-delay="100">
         <div class="row gy-4 align-items-center justify-content-between"
             id="capabilities-container-{{ $page->id ?? 'default' }}">
             <div class="features-grid mb-0">
                 <div class="row g-4" id="capabilities-grid">
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
         const grid = document.getElementById('capabilities-grid');

         fetch('/api/frontend/capabilities')
             .then(response => response.json())
             .then(payload => {
                 if (payload && payload.success && Array.isArray(payload.data) && payload.data.length > 0) {
                     grid.innerHTML = '';
                     payload.data.forEach((item, index) => {
                         const delay = 100 + (index * 100);
                         const icon = item.capability_icon || 'bi bi-lightning-charge';
                         const progress = Number(item.progress_indicator || 0);
                         const label = item.progress_label || '';
                         const title = item.title || '';
                         const description = item.description || '';

                         const fullText = description ?? '';
                         const tempDiv = document.createElement('div');
                         tempDiv.innerHTML = fullText;
                         const plainText = tempDiv.textContent || tempDiv.innerText || '';
                         const shortText = plainText.length > 97 ? plainText.substring(0, 97) + '...' : plainText;
                         const isExpandable = plainText.length > 97;
                         // <p>${description}</p>
                         const card = `
                        <div class="col-xl-3 col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="${delay}">
                            <div class="feature-box text-center">
                                <div class="feature-icon-wrapper">
                                    <i class="${icon}"></i>
                                </div>
                                <h4>${title}</h4>
                                ${isExpandable ? `<div class="description-wrapper" data-expanded="false">
                                    <span class="description-text">${shortText}</span>
                                    <div class="full-description" style="display:none;">${fullText}</div>
                                    <a href="javascript:void(0);" class="toggle-front-description" style="margin-left: 5px; color: #007bff;">Read More</a>
                                </div>`
                                    : `<span class="description-text">${plainText}</span>`}
                                <div class="progress-indicator">
                                    <div class="progress-bar" style="width: ${progress}%;"></div>
                                </div>
                                <span class="progress-label">${label}</span>
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
                     const section = document.getElementById('capabilities');
                     if (section) section.style.display = 'none';
                 }
             })
             .catch(() => {
                 const hasItems = grid.querySelector('.feature-box');
                 if (!hasItems) {
                     grid.innerHTML = '';
                     const section = document.getElementById('capabilities');
                     if (section) section.style.display = 'none';
                 }
             });
     });
 </script>
