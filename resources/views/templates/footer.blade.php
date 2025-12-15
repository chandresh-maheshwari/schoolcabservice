 <!-- Features Section -->
 <section id="features" class="features section">

     <!-- Section Title -->
     <div class="container section-title" data-aos="fade-up">
         <h2>Features</h2>
         <p>Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit</p>
     </div><!-- End Section Title -->

     <div class="container" data-aos="fade-up" data-aos-delay="100">
         <div class="row gy-4 align-items-center justify-content-between"
             id="feature-container-{{ $page->id ?? 'default' }}">

             {{-- <div class="row align-items-center mb-5">
                 <div class="col-lg-6" data-aos="fade-right" data-aos-delay="200">
                     <div class="features-intro">
                         <h2>Revolutionary Digital Solutions</h2>
                         <p>Transforming business operations through innovative technology and strategic
                             implementation methodologies.</p>
                         <div class="highlights-grid">
                             <div class="highlight-item">
                                 <div class="highlight-number">99.9%</div>
                                 <div class="highlight-text">Uptime Guarantee</div>
                             </div>
                             <div class="highlight-item">
                                 <div class="highlight-number">24/7</div>
                                 <div class="highlight-text">Expert Support</div>
                             </div>
                             <div class="highlight-item">
                                 <div class="highlight-number">500+</div>
                                 <div class="highlight-text">Satisfied Clients</div>
                             </div>
                         </div>
                     </div>
                 </div>
                 <div class="col-lg-6" data-aos="fade-left" data-aos-delay="300">
                     <div class="featured-image-container">
                         <img src="assets/img/features/features-3.webp" alt="Digital Solutions" class="img-fluid">
                         <div class="floating-badge">
                             <i class="bi bi-award"></i>
                             <span>Industry Leader</span>
                         </div>
                     </div>
                 </div>
             </div> --}}

             {{-- <div class="features-grid">
                 <div class="row g-4">
                     <div class="col-xl-3 col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="100">
                         <div class="feature-box text-center">
                             <div class="feature-icon-wrapper">
                                 <i class="bi bi-lightning-charge"></i>
                             </div>
                             <h4>Lightning Performance</h4>
                             <p>Experience ultra-fast processing speeds with our optimized infrastructure and
                                 cutting-edge technology stack.</p>
                             <div class="progress-indicator">
                                 <div class="progress-bar" style="width: 95%;"></div>
                             </div>
                             <span class="progress-label">95% Efficiency</span>
                         </div>
                     </div>

                     <div class="col-xl-3 col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="200">
                         <div class="feature-box text-center">
                             <div class="feature-icon-wrapper">
                                 <i class="bi bi-shield-shaded"></i>
                             </div>
                             <h4>Advanced Security</h4>
                             <p>Multi-layered security protocols ensure your data remains protected against modern
                                 cyber threats.</p>
                             <div class="progress-indicator">
                                 <div class="progress-bar" style="width: 100%;"></div>
                             </div>
                             <span class="progress-label">100% Secure</span>
                         </div>
                     </div>

                     <div class="col-xl-3 col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="300">
                         <div class="feature-box text-center">
                             <div class="feature-icon-wrapper">
                                 <i class="bi bi-layers"></i>
                             </div>
                             <h4>Seamless Integration</h4>
                             <p>Connect all your existing systems effortlessly with our flexible and scalable
                                 integration platform.</p>
                             <div class="progress-indicator">
                                 <div class="progress-bar" style="width: 88%;"></div>
                             </div>
                             <span class="progress-label">88% Compatibility</span>
                         </div>
                     </div>

                     <div class="col-xl-3 col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="400">
                         <div class="feature-box text-center">
                             <div class="feature-icon-wrapper">
                                 <i class="bi bi-graph-up"></i>
                             </div>
                             <h4>Smart Analytics</h4>
                             <p>Gain valuable insights through sophisticated data analysis and real-time reporting
                                 capabilities.</p>
                             <div class="progress-indicator">
                                 <div class="progress-bar" style="width: 92%;"></div>
                             </div>
                             <span class="progress-label">92% Accuracy</span>
                         </div>
                     </div>
                 </div>
             </div> --}}

             {{-- <div class="advanced-features-section" data-aos="fade-up" data-aos-delay="100">
                 <div class="row">
                     <div class="col-lg-8 mx-auto text-center mb-5" data-aos="fade-up" data-aos-delay="200">
                         <h3>Advanced Capabilities</h3>
                         <p>Discover powerful features designed to elevate your business operations to the next level
                             of excellence.</p>
                     </div>
                 </div>

                 <div class="row g-4">
                     <div class="col-lg-6" data-aos="fade-right" data-aos-delay="300">
                         <div class="advanced-feature-card">
                             <div class="feature-header">
                                 <div class="feature-number">01</div>
                                 <div class="feature-status-badge enterprise">Enterprise</div>
                             </div>
                             <div class="feature-content">
                                 <h5>Automated Workflow Engine</h5>
                                 <p>Streamline complex business processes with intelligent automation that adapts to
                                     your organizational needs.</p>
                                 <ul class="feature-benefits">
                                     <li><i class="bi bi-check-circle"></i> Process Optimization</li>
                                     <li><i class="bi bi-check-circle"></i> Error Reduction</li>
                                 </ul>
                             </div>
                             <div class="feature-icon-bg">
                                 <i class="bi bi-diagram-3"></i>
                             </div>
                         </div>
                     </div>

                     <div class="col-lg-6" data-aos="fade-left" data-aos-delay="400">
                         <div class="advanced-feature-card">
                             <div class="feature-header">
                                 <div class="feature-number">02</div>
                                 <div class="feature-status-badge premium">Premium</div>
                             </div>
                             <div class="feature-content">
                                 <h5>Real-time Collaboration</h5>
                                 <p>Enable seamless teamwork with synchronized data sharing and instant communication
                                     across all platforms.</p>
                                 <ul class="feature-benefits">
                                     <li><i class="bi bi-check-circle"></i> Live Updates</li>
                                     <li><i class="bi bi-check-circle"></i> Team Sync</li>
                                 </ul>
                             </div>
                             <div class="feature-icon-bg">
                                 <i class="bi bi-people"></i>
                             </div>
                         </div>
                     </div>

                     <div class="col-lg-6" data-aos="fade-right" data-aos-delay="500">
                         <div class="advanced-feature-card">
                             <div class="feature-header">
                                 <div class="feature-number">03</div>
                                 <div class="feature-status-badge standard">Standard</div>
                             </div>
                             <div class="feature-content">
                                 <h5>Predictive Intelligence</h5>
                                 <p>Leverage machine learning algorithms to forecast trends and make data-driven
                                     strategic decisions.</p>
                                 <ul class="feature-benefits">
                                     <li><i class="bi bi-check-circle"></i> Trend Analysis</li>
                                     <li><i class="bi bi-check-circle"></i> Risk Assessment</li>
                                 </ul>
                             </div>
                             <div class="feature-icon-bg">
                                 <i class="bi bi-cpu"></i>
                             </div>
                         </div>
                     </div>

                     <div class="col-lg-6" data-aos="fade-left" data-aos-delay="600">
                         <div class="advanced-feature-card">
                             <div class="feature-header">
                                 <div class="feature-number">04</div>
                                 <div class="feature-status-badge coming-soon">Coming Soon</div>
                             </div>
                             <div class="feature-content">
                                 <h5>Cloud Scalability</h5>
                                 <p>Dynamic resource allocation ensures optimal performance during peak usage while
                                     maintaining cost efficiency.</p>
                                 <ul class="feature-benefits">
                                     <li><i class="bi bi-check-circle"></i> Auto Scaling</li>
                                     <li><i class="bi bi-check-circle"></i> Cost Optimization</li>
                                 </ul>
                             </div>
                             <div class="feature-icon-bg">
                                 <i class="bi bi-cloud-arrow-up"></i>
                             </div>
                         </div>
                     </div>
                 </div>
             </div> --}}

         </div>
     </div>

 </section><!-- /Features Section -->

 <script>
     document.addEventListener('DOMContentLoaded', function() {
         const container = document.getElementById('feature-container-{{ $page->id ?? 'default' }}');

         fetch('/api/frontend/features')
             .then(response => response.json())
             .then(payload => {
                 if (payload && payload.success && Array.isArray(payload.data) && payload.data.length > 0) {
                     const items = payload.data;
                     const feature = items[0];
                     const pageImage = {!! json_encode($page->image ?? '') !!};
                    //  console.log(items);
                     container.innerHTML = `
                     <div class="row align-items-center mb-5">
                        <div class="col-lg-6" data-aos="fade-right" data-aos-delay="200">
                            <div class="features-intro">
                                <h2>${feature?.title ?? 'Revolutionary Digital Solutions'}</h2>
                                <p>${feature?.description ?? 'Transforming business operations through innovative technology and strategic implementation methodologies.'}</p>
                                <div class="highlights-grid">
                                    <div class="highlight-item">
                                        <div class="highlight-number">${feature?.highlight_number_1 ?? ''}</div>
                                        <div class="highlight-text">${feature?.hightlight_text_1 ?? ''}</div>
                                    </div>
                                    <div class="highlight-item">
                                        <div class="highlight-number">${feature?.highlight_number_2 ?? ''}</div>
                                        <div class="highlight-text">${feature?.hightlight_text_2 ?? ''}</div>
                                    </div>
                                    <div class="highlight-item">
                                        <div class="highlight-number">${feature?.highlight_number_3 ?? ''}</div>
                                        <div class="highlight-text">${feature?.hightlight_text_3 ?? ''}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                         <div class="col-lg-6" data-aos="fade-left" data-aos-delay="300">
                            <div class="featured-image-container">
                                 <img src="${pageImage || feature?.image || 'assets/img/features/features-3.webp'}" alt="Digital Solutions" class="img-fluid">
                                <div class="floating-badge">
                                    <i class="bi bi-award"></i>
                                    <span>Industry Leader</span>
                                </div>
                            </div>
                        </div>
                    </div> `;
                 } else {
                     if (container) {
                         container.innerHTML = '';
                     }
                     const features = document.getElementById('features');
                     if (features) {
                         features.style.display = 'none';
                     }
                 }
             })
             .catch(() => {
                 if (container) {
                     const hasItems = container.querySelectorAll('.features-intro').length > 0;
                     if (!hasItems) {
                         container.innerHTML = '';
                         const features = document.getElementById('features');
                         if (features) {
                             features.style.display = 'none';
                         }
                     }
                 }
             });
     });
 </script>
