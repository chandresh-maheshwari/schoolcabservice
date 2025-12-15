 <!-- Why Us Section -->
        <section id="why-us" class="why-us section">

            <!-- Section Title -->
            <div class="container section-title" data-aos="fade-up">
                <h2>{{ $page->title ?? 'Why Uss' }}</h2>
                <p>{!! $page->description ?? 'Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit' !!}</p>
            </div><!-- End Section Title -->

            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <div class="row gy-4 align-items-center">
                    <div class="col-lg-7 mb-4 mb-lg-0 whyus-col">
                        <div class="row gy-4 whyus-row"  id="whyus-container">

                            <div class="col-12 text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="col-lg-5 aos-init aos-animate" data-aos="zoom-out" data-aos-delay="300">
                        <div class="image-wrapper">
                            <img id="why-us-image" src="{{ asset('images/Default.jpg') }}" alt="" class="img-fluid rounded-4" style="width:100%;height:100%;object-fit:contain;">
                        </div>
                    </div>
                </div>

            </div>

        </section><!-- /Why Us Section -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const row = document.getElementById('whyus-container');
    const imageElement = document.getElementById('why-us-image');
    const fallbackImg = '{{ asset('images/Default.jpg') }}';
    const pageImage = @json($page->image ?? null);
    
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

    // Process page image
    if (pageImage && imageElement) {
        const imageUrl = getImageUrl(pageImage);
        checkImageExists(imageUrl).then(exists => {
            if (exists) {
                imageElement.src = imageUrl;
            }
        });
    }

    fetch('/api/frontend/why-us')
        .then(response => response.json())
        .then(async payload => {
            if (payload && payload.success && Array.isArray(payload.data) && payload.data.length > 0) {
                row.innerHTML = '';
                
                payload.data.forEach((item, index) => {
                    const delay = 200 + (index % 2 === 0 ? 0 : 100) + (Math.floor(index / 2) * 100);
                    const number = String(index + 1).padStart(2, '0');

                    const fullText = item.description ?? '';
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = fullText;
                    const plainText = tempDiv.textContent || tempDiv.innerText || '';
                    const shortText = plainText.length > 150 ? plainText.substring(0, 150) + '...' : plainText;
                    const isExpandable = plainText.length > 150;
                    // <p>${item.description ?? ''}</p>
                    const card = `
                        <div class="col-md-6" data-aos="fade-up" data-aos-delay="${delay}">
                            <div class="card-item">
                                <div class="card-number">${number}</div>
                                <h3>${item.title ?? ''}</h3>
                                ${isExpandable ? `<div class="description-wrapper" data-expanded="false">
                                    <span class="description-text">${shortText}</span>
                                    <div class="full-description" style="display:none;">${fullText}</div>
                                    <a href="javascript:void(0);" class="toggle-front-description" style="margin-left: 5px; color: #007bff;">Read More</a>
                                </div>`
                                    : `<span class="description-text">${plainText}</span>`}
                            </div>
                        </div>
                    `;
                    row.insertAdjacentHTML('beforeend', card);
                });

                if (typeof AOS !== 'undefined') {
                    AOS.refresh();
                }
            } else {
                // No items; keep section but clear spinner
                row.innerHTML = '';
            }
        })
        .catch(() => {
            // On error, clear spinner if present
            if (row) row.innerHTML = '';
        });
});
</script>