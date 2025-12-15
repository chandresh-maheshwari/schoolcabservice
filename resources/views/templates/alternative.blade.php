<!-- Alt Services Section -->
<section id="alt-services" class="alt-services section">

    <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row g-4" id="alternative-container">
            <div class="col-12 text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</section><!-- /Alt Services Section -->

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('alternative-container');
        const fallbackImg = '{{ asset('images/Default.jpg') }}';

        // Function to check if image exists and return appropriate URLimage.png
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

        fetch('/api/frontend/alternatives')
            .then(response => response.json())
            .then(async payload => {
                if (payload && payload.success && Array.isArray(payload.data) && payload.data.length >
                    0) {
                    container.innerHTML = '';

                    // Process each item with image checking
                    for (let index = 0; index < payload.data.length; index++) {
                        const item = payload.data[index];
                        const delay = 100 + (index * 100);
                        const icon = item.icon || 'bi bi-shield-check';
                        const title = item.title || '';
                        const description = item.description || '';
                        const btn = item.button_title || 'Explore Service';

                        // Process image
                        const imageUrl = getImageUrl(item.image);
                        const exists = await checkImageExists(imageUrl);
                        const image = exists ? imageUrl : fallbackImg;

                        const fullText = description || '';
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = fullText;
                        const plainText = tempDiv.textContent || tempDiv.innerText || '';
                        const shortText = plainText.length > 100 ? plainText.substring(0, 100) + '...' :
                            plainText;
                        const isExpandable = plainText.length > 100;
                        // <p>${description}</p>
                        const cardHtml = `
                        <div class="col-lg-6 col-xl-3">
                            <div class="service-card ${index === 1 ? 'featured' : ''}" data-aos="zoom-in" data-aos-delay="${delay}">
                                <div class="card-header">
                                    <div class="icon-box">
                                        <i class="${icon}"></i>
                                    </div>
                                    <h4>${title}</h4>
                                </div>
                                <div class="card-body">
                                ${isExpandable ? `<div class="description-wrapper" data-expanded="false">
                                        <span class="description-text">${shortText}</span>
                                        <div class="full-description" style="display:none;">${fullText}</div>
                                        <a href="javascript:void(0);" class="toggle-front-description" style="margin-left: 5px; color: #007bff;">Read More</a>
                                    </div>`
                                        : `<span class="description-text">${plainText}</span>`}
                                    <div class="feature-image">
                                        <img src="${image}" alt="Alternative Image" class="img-fluid" style="width:100%;height:100%;object-fit:contain;">
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <a href="#" class="btn-explore">
                                        ${btn}
                                        <i class="bi bi-arrow-up-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    `;
                        container.insertAdjacentHTML('beforeend', cardHtml);
                    }

                    if (typeof AOS !== 'undefined') {
                        AOS.refresh();
                    }
                } else {
                    // No data → hide section
                    container.innerHTML = '';
                    const section = document.getElementById('alt-services');
                    if (section) section.style.display = 'none';
                }
            })
            .catch(() => {
                const hasCard = container.querySelector('.service-card');
                if (!hasCard) {
                    container.innerHTML = '';
                    const section = document.getElementById('alt-services');
                    if (section) section.style.display = 'none';
                }
            });
    });
</script>
