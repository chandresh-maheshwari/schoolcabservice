<!-- Services Section -->
<section id="services" class="services section">

    <!-- Section Title -->
    <div class="container section-title" data-aos="fade-up">
        <h2>{{ $page->title ?? 'Services' }}</h2>
        <p>{!! $page->description ?? 'Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit' !!}</p>
    </div><!-- End Section Title -->

    <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row gy-4 justify-content-center" id="services-container-{{ $page->id ?? 'default' }}">
            @if (isset($data) && $data && $data->count() > 0)
                @foreach ($data as $index => $service)
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="{{ 100 + $index * 50 }}">
                        <article class="service-item">
                            <span class="service-icon rounded-circle">
                                <i class="{{ $service->service_icon ?? 'bi bi-gear' }}"></i>
                            </span>
                            <h3>
                                <a href="#">
                                    {{ $service->title ?? 'Service Title' }}
                                </a>
                            </h3>
                            <p>
                                {{ $service->description ?? 'Service description will be displayed here.' }}
                            </p>
                            <a href="#"
                                class="card-action rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-arrow-up-right"></i>
                            </a>
                        </article>
                    </div>
                @endforeach
            @else
                <!-- Initial loading spinner; will be replaced by API content -->
                <div class="col-12 text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

</section><!-- /Services Section -->

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Always try API first; if it fails or returns no data, leave any server-rendered content intact
        const container = document.getElementById('services-container-{{ $page->id ?? 'default' }}');

        fetch('/api/frontend/services')
            .then(response => response.json())
            .then(data => {
                if (data && data.success && Array.isArray(data.data) && data.data.length > 0) {
                    container.innerHTML = '';

                    data.data.forEach((service, index) => {
                        const delay = 100 + (index * 50); // Staggered animation delays
                        const fullText = service.description || 'Service description will be displayed here.';
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = fullText;
                        const plainText = tempDiv.textContent || tempDiv.innerText || '';
                        const shortText = plainText.length > 140 ? plainText.substring(0, 140) + '...' : plainText;
                        const isExpandable = plainText.length > 140;

                        const serviceHtml = `
                        <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="${delay}">
                            <article class="service-item">
                                <span class="service-icon rounded-circle">
                                    <i class="${service.service_icon || 'bi bi-gear'}"></i>
                                </span>
                                <h3>
                                    <a href="#">
                                        ${service.title || 'Service Title'}
                                    </a>
                                </h3>
                                ${isExpandable ? `
                                <div class="description-wrapper" data-expanded="false">
                                    <span class="description-text">${shortText}</span>
                                    <div class="full-description" style="display:none;">${fullText}</div>
                                    <a href="javascript:void(0);" class="toggle-front-description" style="margin-left: 5px; color: #007bff;">Read More</a>
                                </div>` : `<span class="description-text">${plainText}</span>`}
                                <a href="#"
                                    class="card-action rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-arrow-up-right"></i>
                                </a>
                            </article>
                        </div>
                    `;

                        container.insertAdjacentHTML('beforeend', serviceHtml);
                    });

                    if (typeof AOS !== 'undefined') {
                        AOS.refresh();
                    }
                } else {
                    // No services from API → remove any spinner and hide the section
                    if (container) {
                        container.innerHTML = '';
                    }
                    const servicesSection = document.getElementById('services');
                    if (servicesSection) {
                        servicesSection.style.display = 'none';
                    }
                }
            })
            .catch(() => {
                // On error → remove spinner and hide the section if nothing useful is rendered
                if (container) {
                    const hasItems = container.querySelectorAll('.service-item').length > 0;
                    if (!hasItems) {
                        container.innerHTML = '';
                        const servicesSection = document.getElementById('services');
                        if (servicesSection) {
                            servicesSection.style.display = 'none';
                        }
                    }
                }
            });
    });
</script>
