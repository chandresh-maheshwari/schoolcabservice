<!--================Start Portfolio Area =================-->
<section class="portfolio_area" id="portfolio">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="main_title text-left">
                    <h2>{{ $page->title ?? 'Portfolio' }} </h2>
        <p>{!! $page->description ?? 'Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit' !!}</p>

                </div>
            </div>
        </div>
        <div class="filters portfolio-filter">
            <ul class="portfolio-filters-ul"></ul>
        </div>

        <div class="filters-content">
            <div class="portfolio-grid"></div>
        </div>
    </div>
</section>

<style>
/* Satner Template Portfolio Structure - True Masonry Layout */
.portfolio-grid {
  display: block;
  width: 100%;
  margin: 0;
  padding: 0;
}

.portfolio-item {
  width: 33.333%;
  float: left;
  padding: 0 15px 30px 15px;
  box-sizing: border-box;
  margin: 0;
}

.portfolio-item:nth-child(3n+1) {
  clear: left;
}

.portfolio_box {
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  transition: all 0.3s ease;
  height: auto;
  display: block;
}

.portfolio_box:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.single_portfolio {
  position: relative;
  overflow: hidden;
  width: 100%;
  aspect-ratio: 1 / 1;
}

.single_portfolio img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: block;
  border-radius: 8px 8px 0 0;
  object-fit: fill;
  object-position: center;
}

.overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.7);
  opacity: 0;
  transition: opacity 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.single_portfolio:hover .overlay {
  opacity: 1;
}

.overlay .icon {
  color: #fff;
  font-size: 24px;
}

.short_info {
  padding: 20px;
  background: #fff;
}

.short_info h4 {
  margin: 0 0 10px 0;
  font-size: 18px;
  font-weight: 600;
  color: #333;
}

.short_info h4 a {
  color: #333;
  text-decoration: none;
}

.short_info h4 a:hover {
  color: #007bff;
}

.short_info p {
  margin: 0;
  color: #666;
  font-size: 14px;
  line-height: 1.4;
  overflow-wrap: anywhere;
}

/* Responsive Design */
@media (max-width: 768px) {
  .portfolio-item {
    width: 50%;
  }
  .portfolio-item:nth-child(3n+1) {
    clear: none;
  }
  .portfolio-item:nth-child(2n+1) {
    clear: left;
  }
}

@media (max-width: 480px) {
  .portfolio-item {
    width: 100%;
    padding: 0 0 30px 0;
  }
  .portfolio-item:nth-child(2n+1) {
    clear: none;
  }
}

/* Clear floats */
.portfolio-grid::after {
  content: "";
  display: table;
  clear: both;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filtersUl = document.querySelector('#portfolio .portfolio-filters-ul');
    const grid = document.querySelector('#portfolio .portfolio-grid');
    const fallbackImg = '{{ asset("images/Default.jpg") }}'; // Use the existing default image

    // Function to check if image exists without causing 404 errors
    const checkImageExists = (url) => {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = () => resolve(true);
            img.onerror = () => resolve(false);
            img.src = url;
        });
    };

    // Function to get safe image URL
    const getSafeImageUrl = async (imagePath) => {
        // If no image path provided, return fallback
        if (!imagePath || imagePath.trim() === '') {
            return fallbackImg;
        }
        
        // If it's already an absolute URL, return as is
        if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
            return imagePath;
        }
        
        // Clean the path and construct the full URL
        const cleanPath = imagePath.startsWith('/') ? imagePath.substring(1) : imagePath;
        const fullUrl = '{{ asset("") }}' + cleanPath;
        
        // Check if image exists
        const exists = await checkImageExists(fullUrl);
        return exists ? fullUrl : fallbackImg;
    };

    // Fetch data
    fetch('/api/frontend/portfolio-and-categories')
        .then(response => response.json())
        .then(async payload => {
            if (!payload || !payload.success || !payload.data) return;
            const { categories, portfolios } = payload.data;

            // Render filter menu
            let filtersHtml = '<li class="active" data-filter="*">all</li>';
            categories.forEach(cat => {
                const slug = (cat.name || '').toLowerCase().replace(/[^a-z0-9]+/g, '-');
                filtersHtml += `<li data-filter=".${slug}">${cat.name}</li>`;
            });
            filtersUl.innerHTML = filtersHtml;

            // Wire up filtering
            filtersUl.addEventListener('click', function(e) {
                const t = e.target;
                if (t && t.matches('li[data-filter]')) {
                    e.preventDefault();
                    filtersUl.querySelectorAll('li').forEach(li => li.classList.remove('active'));
                    t.classList.add('active');
                    if (typeof Isotope !== 'undefined' && grid._iso) {
                        grid._iso.arrange({ filter: t.getAttribute('data-filter') || '*' });
                    }
                }
            });

            // Render portfolio items with proper image handling
            grid.innerHTML = '';
            for (let i = 0; i < portfolios.length; i++) {
              const item = portfolios[i];
              const cat = categories.find(c => c.id === item.category_id);
              const slug = cat ? (cat.name || '').toLowerCase().replace(/[^a-z0-9]+/g, '-') : '';
              // console.log("TESTTTTTTTTTTTTTTTT");
              // console.log(item.short_desc);
                
                // Create masonry item without Bootstrap classes
                const col = document.createElement('div');
                col.className = `portfolio-item all ${slug}`;
                col.innerHTML = `
                    <div class="portfolio_box">
                        <div class="single_portfolio">
                            <img src="${item.main_image ? `/${item.main_image.image_path}` : fallbackImg}" alt="${item.title || 'Portfolio Image'}" loading="lazy">
                            <div class="overlay"></div>
                            <a href="#" class="img-gal">
                                <div class="icon"><span class="lnr lnr-cross"></span></div>
                            </a>
                        </div>
                        <div class="short_info">
                            <h4><a href="/portfolio-details/${item.id}">${item.title || ''}</a></h4>
                            <p>${item.short_desc || ''}</p>
                        </div>
                    </div>
                `;
                // Handle image loading asynchronously to prevent 404 errors
                const imgElement = col.querySelector('img');
                const lightboxLink = col.querySelector('.img-gal'); 
                const mainImagePath = item.main_image ? item.main_image.image_path : item.image;
                const safeUrl = await getSafeImageUrl(mainImagePath);
                imgElement.src = safeUrl;
                lightboxLink.href = safeUrl;
                grid.appendChild(col);
            }

            // Init Isotope masonry - Satner Template Style
            setTimeout(function() {
                if (typeof Isotope !== 'undefined') {
                    grid._iso = new Isotope(grid, {
                        itemSelector: '.portfolio-item',
                        layoutMode: 'masonry',
                        percentPosition: true,
                        masonry: {
                            columnWidth: '.portfolio-item',
                            gutter: 0,
                            horizontalOrder: true
                        }
                    });
                    
                    // Force layout after initialization
                    setTimeout(function() {
                        if (grid._iso) {
                            grid._iso.layout();
                        }
                    }, 100);
                }
                
                // Wait for all images to load before layout
                if (typeof imagesLoaded !== 'undefined') {
                    imagesLoaded(grid, function() { 
                        if (grid._iso) {
                            grid._iso.layout();
                        }
                    });
                } else {
                    // Fallback if imagesLoaded is not available
                    setTimeout(function() {
                        if (grid._iso) {
                            grid._iso.layout();
                        }
                    }, 1500);
                }
            }, 500);

            // Init lightbox on dynamic images
            if (typeof GLightbox !== 'undefined') {
                GLightbox({ selector: '#portfolio .img-gal', loop: true, touchNavigation: true });
            }
        })
        .catch(error => {
            console.error('Error loading portfolio data:', error);
            // Show error message or fallback content
            grid.innerHTML = '<div class="col-12 text-center"><p>Unable to load portfolio items. Please try again later.</p></div>';
        });
});
</script>