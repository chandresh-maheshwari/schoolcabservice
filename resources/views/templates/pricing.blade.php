<!-- Pricing Section -->
<section id="pricing" class="pricing section light-background">
    <!-- Section Title -->
    <div class="container section-title" data-aos="fade-up">
        <h2>{{ $page->title ?? 'Pricing' }}</h2>
        <p>{!! $page->description ?? 'Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit' !!}</p>
    </div><!-- End Section Title -->

    <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row g-4 justify-content-center" id="pricing-cards-container">
            <!-- Pricing cards will be rendered here -->
        </div>
    </div>
</section>

<!-- Modal -->
<div class="modal fade" id="blogViewModal" tabindex="-1" aria-labelledby="blogViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-white text-dark">
            <div class="modal-header">
                <h5 class="modal-title" id="blogViewModalLabel">Package & Training Details</h5>
                <button type="button" class="btn-close custom-close ms-auto" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body d-flex" style="gap: 2rem;">

                <!-- Left Side: Form -->
                <div style="flex: 1; padding-right: 1rem;">
                    <form id="trainingForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            {{-- <label for="firstName" class="form-label">First Name</label> --}}
                            <input type="text" class="form-control" id="firstName" name="firstName"
                                placeholder="Enter first name" />
                        </div>

                        <div class="mb-3">
                            {{-- <label for="lastName" class="form-label">Last Name</label> --}}
                            <input type="text" class="form-control" id="lastName" name="lastName"
                                placeholder="Enter last name" />
                        </div>

                        <div class="mb-3">
                            {{-- <label for="email" class="form-label">Email</label> --}}
                            <input type="email" class="form-control" id="email" name="email"
                                placeholder="Enter email" />
                        </div>

                        <div class="mb-3">
                            {{-- <label for="contactNumber" class="form-label">Contact Number</label> --}}
                            <input type="text" class="form-control" id="contactNumber" name="contactNumber"
                                placeholder="Enter contact number" />
                        </div>

                        <div class="mb-3">
                            {{-- <label for="technologies" class="form-label">Technologies</label> --}}
                            <input type="text" class="form-control" id="technologies" name="technologies"
                                placeholder="Enter technologies (e.g. HTML, CSS, React)" />
                        </div>

                        <div class="mb-3">
                            {{-- <label for="description" class="form-label">Description</label> --}}
                            <textarea class="form-control" id="description" name="description" placeholder="Enter description" rows="4"></textarea>
                        </div>

                        <div class="mb-3">
                            <button type="button" class="btn btn-primary" id="uploadBtn">Upload Resume</button>
                            <input type="file" id="cv" name="cv" style="display: none;" accept="csv.pdf.png.jpeg.jpg.doc"/>
                            <div id="fileName" style="margin-top: 10px; font-weight: bold;"></div>
                        </div>


                        <div class="modal-footer"
                            style="border-top:none; justify-content: flex-start; padding-left: 0;">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>

                <!-- Right Side: Package Details -->
                <!-- Right Side: Package Details -->
                <div id="modalPackageDetails"
                    style="flex: 1; background-color: #007bff; color: white; border-left: none; padding: 2rem; border-radius: 8px;">
                    <h3 id="modalPackageTitle"></h3>
                    <div class="price" style="font-size: 1.5rem; margin-bottom: 1rem;">
                        <span class="currency" id="modalPackageCurrency"></span>
                        <span class="amount" id="modalPackageAmount"></span>
                        <span class="period" id="modalPackagePeriod"></span>
                    </div>
                    <p id="modalPackageDescription"></p>
                    <h4 style="margin-top: 1rem;">Features Included:</h4>
                    <ul id="modalPackageFeatures" class="features-list" style="list-style: none; padding-left: 0;">
                        <!-- Features added dynamically -->
                    </ul>
                </div>


            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="responseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-3">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="responseModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="responseModalBody"></div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Script -->
<script>
    const fileInput = document.getElementById('cv');
    const uploadBtn = document.getElementById('uploadBtn');
    const fileNameDiv = document.getElementById('fileName');

    uploadBtn.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            fileNameDiv.textContent = fileInput.files[0].name; // show file name below
        } else {
            fileNameDiv.textContent = ""; // clear if no file
        }
    });
    (function() {
        let pricingData = [];

        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('pricing-cards-container');
            if (!container) return;

            fetch('/api/frontend/pricing')
                .then((r) => r.json())
                .then((payload) => {
                    if (!payload || payload.success === false) return;

                    pricingData = Array.isArray(payload) ? payload : (payload.data || []);

                    const html = pricingData
                        .map((plan, idx) => {
                            const features = [
                                    plan.feature_1,
                                    plan.feature_2,
                                    plan.feature_3,
                                    plan.feature_4,
                                    plan.feature_5,
                                    plan.feature_6,
                                ]
                                .filter(Boolean)
                                .map((text) =>
                                    `<li><i class="bi bi-check-circle-fill"></i> ${text}</li>`)
                                .join('');
                            const currency = plan.currency ? `<i class="${plan.currency}"></i>` :
                                '$';
                            const amount = plan.amount || '';
                            const period = plan.period ? ` / ${plan.period}` : '/ month';
                            const buttonTitle = plan.button_title || 'Buy Now';
                            const popularClass = idx === 1 ? ' popular' : '';
                            const delay = (idx + 1) * 100;

                            return `
                    <div class="col-lg-4" data-aos="fade-up" data-aos-delay="${delay}">
                      <div class="pricing-card${popularClass}">
                        ${popularClass ? '<div class="popular-badge">Most Popular</div>' : ''}
                        <h3>${plan.title || ''}</h3>
                        <div class="price">
                          <span class="currency">${currency}</span>
                          <span class="amount">${amount}</span>
                          <span class="period">${period}</span>
                        </div>
                        <p class="description">${plan.description || ''}</p>
                        ${features ? '<h4>Features Included:</h4>' : ''}
                        ${
                          features
                            ? `<ul class="features-list">${features}</ul>`
                            : ''
                        }
                        <button type="button"
                          class="btn ${
                            popularClass ? 'btn-light' : 'btn btn-primary'
                          } buy-now-btn"
                          data-index="${idx}">
                          ${buttonTitle}
                          <i class="bi bi-arrow-right"></i>
                        </button>
                      </div>
                    </div>`;
                        })
                        .join('');

                    container.innerHTML = html;

                    if (typeof AOS !== 'undefined') {
                        AOS.refresh();
                    }
                })
                .catch(() => {});
        });

        // Show modal & fill details on Buy Now click
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.buy-now-btn');
            if (btn) {
                const idx = btn.getAttribute('data-index');
                const plan = pricingData[idx];
                if (!plan) return;

                // Fill modal package details
                document.getElementById('modalPackageTitle').textContent = plan.title || '';
                document.getElementById('modalPackageCurrency').innerHTML = plan.currency ?
                    `<i class="${plan.currency}"></i>` : '$';
                document.getElementById('modalPackageAmount').textContent = plan.amount || '';
                document.getElementById('modalPackagePeriod').textContent = plan.period ?
                    ` / ${plan.period}` : '/ month';
                document.getElementById('modalPackageDescription').textContent = plan.description || '';

                const featuresArr = [plan.feature_1, plan.feature_2, plan.feature_3, plan.feature_4, plan
                    .feature_5, plan.feature_6
                ].filter(Boolean);
                const featuresHtml = featuresArr.map(f =>
                    `<li><i class="bi bi-check-circle-fill"></i> ${f}</li>`).join('');
                document.getElementById('modalPackageFeatures').innerHTML = featuresHtml;

                // Reset form on modal open
                const form = document.getElementById('trainingForm');
                form.reset();

                const modal = new bootstrap.Modal(document.getElementById('blogViewModal'));
                modal.show();
            }
        });

        // Handle form submission
        document.getElementById('trainingForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            // Example: Add package info to form data (optional)
            formData.append('packageTitle', document.getElementById('modalPackageTitle').textContent);

            fetch('api/pricing/package', {
                    method: 'POST',
                    body: formData,
                })
                .then(async (response) => {
                    const data = await response.json();

                    const modalElement = document.getElementById('responseModal');
                    const modal = new bootstrap.Modal(modalElement);
                    const title = document.getElementById('responseModalTitle');
                    const body = document.getElementById('responseModalBody');

                    if (!response.ok) {
                        // Validation or server error
                        let message = 'Something went wrong.';
                        if (data.errors) {
                            message = Object.values(data.errors).flat().join('<br>');
                        } else if (data.message) {
                            message = data.message;
                        }

                        title.textContent = 'Error';
                        body.innerHTML = `<div class="text-danger">${message}</div>`;
                        modal.show();
                        return;
                    }

                    // ✅ Success
                    title.textContent = 'Success';
                    body.innerHTML = `<div class="text-success">${data.message}</div>`;
                    modal.show();

                    // Close your main form modal (if open)
                    const formModal = bootstrap.Modal.getInstance(document.getElementById(
                        'blogViewModal'));
                    formModal?.hide();

                    // Reset form
                    document.querySelector('form').reset();
                })
                .catch(() => {
                    const modalElement = document.getElementById('responseModal');
                    const modal = new bootstrap.Modal(modalElement);
                    const title = document.getElementById('responseModalTitle');
                    const body = document.getElementById('responseModalBody');

                    title.textContent = 'Error';
                    body.innerHTML =
                        `<div class="text-danger">Network error occurred. Please try again.</div>`;
                    modal.show();
                });
        });
    })();
</script>
