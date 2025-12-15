(function ($) {
  "use strict";


    // ------------ loader -----------
    // $(window).on("load", function() {
    //     $(".loader_main").fadeOut(500);
    // });

    // dynamic-year js
    let dynamicyearElm = $(".dynamic-year");
    if (dynamicyearElm.length) {
        let currentYear = new Date().getFullYear();
        dynamicyearElm.html(currentYear);
    }
 


    // popup video
    new VenoBox({
        selector: '.popup-youtube',
    });
     /*--------------------------------------------------------
    /   04. Back To Top Start
    /--------------------------------------------------------*/

    if ($('.scroll_to_top').length > 0) {

        document.addEventListener("DOMContentLoaded", function() {
            const scrollToTopButton = document.querySelector('.scroll_to_top');
        
            // Show the button when user scrolls down
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 500) {
                    scrollToTopButton.classList.add('show');
                } else {
                    scrollToTopButton.classList.remove('show');
                }
            });
        
            // Smooth scroll back to top
            scrollToTopButton.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        });
        
    };
   

    /*--------------------------------------------------------
    /  Back To Top End
    /--------------------------------------------------------*/

    // =======Sticky-header========>>>>>
    $(window).on('scroll', function () {
        var scroll = $(window).scrollTop();
        if (scroll < 50) {
            $(".sticky-navbar").removeClass("sticky");
            $('.back-to-top').hide();
        } else {
            $(".sticky-navbar").addClass("sticky");
            $('.back-to-top').show();
        }

        let sectionHeaderHeight = $('.section-header').height();
        let customStickyElements = $('.custom-sticky-elements');
        
        // Check if custom-sticky-elements exists
        if (customStickyElements.length > 0) {
            let customStickyElementsOffset = customStickyElements.offset().top - sectionHeaderHeight;

            let articleBlog = $('.article-blog-content');
            let articleBlogHeight = articleBlog.height();
            let articleBlogOffset = articleBlog.offset().top + articleBlogHeight;

            if ((scroll > customStickyElementsOffset) && (scroll < articleBlogOffset)) {
                $(customStickyElements).addClass('custom-sticky');
                $('.custom-sticky-elements .sidebar-social').css({
                    "top": sectionHeaderHeight + 30 +"px",
                });
            } else {
                $(customStickyElements).removeClass('custom-sticky');
            }
        }
    });

    // =======Sticky-header========>>>>>
   

    /*--------------------------------------------------------
    // --------     dark_light themes     --------
    /--------------------------------------------------------*/
    document.addEventListener('DOMContentLoaded', function () {
        const htmlDocument = document.documentElement;
        const bodyDocument = document.body;
        const toggleBtn = document.getElementById('toggleBtn');
        const lightIcon = document.querySelector('.light-icon');
        const darkIcon = document.querySelector('.dark-icon');

        let theme = window.serverTheme || 'dark';
        setTheme(theme);
        updateIconClasses();
        updateBodyClass();

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                const currentTheme = htmlDocument.getAttribute('data-bs-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                setTheme(newTheme);
                updateIconClasses();
                updateBodyClass();
                fetch('/set-theme', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ theme: newTheme })
                });
            });
        }

        function setTheme(theme) {
            htmlDocument.setAttribute('data-bs-theme', theme);
        }

        function updateIconClasses() {
            const themeMode = htmlDocument.getAttribute('data-bs-theme');
            if (lightIcon && darkIcon) {
                lightIcon.classList.remove('active');
                darkIcon.classList.remove('active');
                if (themeMode === 'light') {
                    lightIcon.classList.add('active');
                } else {
                    darkIcon.classList.add('active');
                }
            }
        }

        function updateBodyClass() {
            const themeMode = htmlDocument.getAttribute('data-bs-theme');
            bodyDocument.classList.remove('light-mode', 'dark-mode');
            if (themeMode === 'light') {
                bodyDocument.classList.add('light-mode');
            } else {
                bodyDocument.classList.add('dark-mode');
            }
        }
    });
    
    
     /*--------------------------------------------------------
    // -------- End dark_light themes     --------
    /--------------------------------------------------------*/

    // =======Swiper .swiper-card========>>>>>
    if ($('.swiper-card').length > 0) {
        new Swiper(".swiper-card", {
        loop: true,      
        spaceBetween: 20,
        slidesPerGroup: 2,
        freemode:true,
        keyboard: {
            enabled: true,
        },
        breakpoints: {
            320: {
            slidesPerView: 2,
            },
            460: {
            slidesPerView: 3,
            spaceBetween: 20,
            },
            992: {
            slidesPerView: 4,
            spaceBetween: 20,
            },
            1320: {
            slidesPerView: 5,
            spaceBetween: 10,
            },
            1410: {
            slidesPerView: 5,
            spaceBetween: 24,
            }
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
        }        
        });
    }
    // =======Swiper .service-swiper========>>>>>


    // hero slider
    if ($('.hero-slider').length > 0) {
        new Swiper(".hero-slider", {
            // effect: "fade",
            loop: true,
            freemode: true,
            autoplay: true,
            autoplay: {
                delay: 5000,
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            on: {
                slideChange: function () {
                    textAnimate(this.el);
                }
            },
        });
    };
   
    function textAnimate(sliderElement) {
        const textsToAnimate = sliderElement.querySelectorAll(".rv-text-anime");
        textsToAnimate.forEach(textToAnimate => {
            const animate = new SplitType(textToAnimate, { types: 'words , chars' });
            gsap.from(animate.chars, {
                opacity: 0,
                x: 100,
                duration: 1.1,
                stagger: { amount: 0.9 },
                scrollTrigger: {
                    trigger: textToAnimate,
                    start: "top 95%",
                }
            });
        })
    };
    textAnimate(document);


    // =======Swiper .swiper-card========>>>>>
    if ($('.swiper-card-2').length > 0) {
      new Swiper(".swiper-card-2", {
        loop: true,      
        spaceBetween: 20,
        slidesPerGroup: 2,
        freemode:true,
        keyboard: {
          enabled: true,
        },
        breakpoints: {
          320: {
            slidesPerView: 2,
          },
          460: {
            slidesPerView: 3,
            spaceBetween: 20,
          },
          992: {
            slidesPerView: 4,
            spaceBetween: 20,
          },
          1320: {
            slidesPerView: 5,
            spaceBetween: 10,
          },
          1410: {
            slidesPerView: 5,
            spaceBetween: 24,
          }
        },
        pagination: {
          el: ".swiper-pagination-2",
          clickable: true,
        }        
      });
    }
    // =======Swiper .service-swiper========>>>>>


    // =======Swiper .swiper-card========>>>>>
    // Home page 3
    if ($('.swiper-card-3').length > 0) {
        new Swiper(".swiper-card-3", {
        loop: true,      
        spaceBetween: 20,
        slidesPerGroup: 2,
        freemode:true,
        keyboard: {
            enabled: true,
        },
        breakpoints: {
            320: {
            slidesPerView: 2,
            },
            460: {
            slidesPerView: 3,
            spaceBetween: 20,
            },
            992: {
            slidesPerView: 4,
            spaceBetween: 20,
            },
            1320: {
            slidesPerView: 5,
            spaceBetween: 10,
            },
            1410: {
            slidesPerView: 6,
            spaceBetween: 24,
            }
        },
                
        });
    }
    // =======Swiper .service-swiper========>>>>>

    // catagory page
    if ($('.swiper-card-4').length > 0) {
        new Swiper(".swiper-card-4", {
          loop: true,      
          spaceBetween: 20,
          slidesPerGroup: 2,
          freemode:true,
          keyboard: {
            enabled: true,
          },
          pagination: {
            el: ".swiper-pagination",
            clickable: true,
          },
          breakpoints: {
            320: {
              slidesPerView: 2,
            },
            460: {
              slidesPerView: 3,
              spaceBetween: 20,
            },
            992: {
              slidesPerView: 4,
              spaceBetween: 20,
            },
            1320: {
              slidesPerView: 5,
              spaceBetween: 10,
            },
            1410: {
              slidesPerView: 6,
              spaceBetween: 24,
            }
          },
                
        });
      }

    // Home page 3
    if ($('.instagram-slider').length > 0) {
        new Swiper(".instagram-slider", {
        // loop: true,      
        spaceBetween: 0,
        freemode:true,
        grabCursor: true,
        // autoplay:true,
        effect: "coverflow",
        grabCursor: true,
        slidesPerView: 5,
        coverflowEffect: {
            rotate: 50,
            stretch: 0,
            depth: 100,
            modifier: 1,
            slideShadows: true,
        },

        // breakpoints: {
        //     320: {
        //     slidesPerView: 2,
        //     spaceBetween: 20,
        //     },
        //     400: {
        //     slidesPerView: 3,
        //     spaceBetween: 20,
        //     },
        //     576: {
        //     slidesPerView: 3,
        //     spaceBetween: 20,
        //     },
        //     768: {
        //     slidesPerView: 4,
        //     spaceBetween: 20,
        //     },
        //     992: {
        //     slidesPerView: 6,
        //     spaceBetween: 20,
        //     },
        //     1200: {
        //     slidesPerView: 6,
        //     spaceBetween: 40,
        //     },
        // },
                
        });
    }

   

    if ($('.section-blog-slider').length > 0) {

        $('.section-blog-slider').owlCarousel({
            center: true,
            items: 4,
            loop: true,
            margin: 40,
            responsive:{

                320: {
                    items: 1,
                    margin:10,
                },
                480: {
                    items: 1.4,
                    margin: 15,
                    center: true,
                },
                576: {
                    items: 1.5,
                    margin:15,
                },
                768: {
                    items: 2,
                    margin:15,
                },
                992: {
                    items: 2.5,
                    center:true,
                    margin:15,
                },
                1200: {
                    items: 3,
                    center:false,
                    margin: 20,
                },
                1600: {
                    items: 3.5,
                    center:true
                },
                1800: {
                    items: 4,
                    center:true
                },
                
               
            }
        })
    }

    if ($('.section-blog-slider-rtl').length > 0) {

        $('.section-blog-slider-rtl').owlCarousel({
            center: true,
            items: 4,
            loop: true,
            margin: 40,
            rtl: true,
            responsive:{

                320: {
                    items: 1,
                    margin:10,
                },
                480: {
                    items: 1.4,
                    margin: 15,
                    center: true,
                },
                576: {
                    items: 1.5,
                    margin:15,
                },
                768: {
                    items: 2,
                    margin:15,
                },
                992: {
                    items: 2.5,
                    center:true,
                    margin:15,
                },
                1200: {
                    items: 3,
                    center:false,
                    margin: 20,
                },
                1600: {
                    items: 3.5,
                    center:true
                },
                1800: {
                    items: 4,
                    center:true
                },
                
               
            }
        })
    }


 
   

    if ($('.carousel').length > 0) {

        let nextDom = document.getElementById('next');
        let prevDom = document.getElementById('prev');

        let carouselDom = document.querySelector('.carousel');
        let SliderDom = carouselDom.querySelector('.carousel .list');
        let thumbnailBorderDom = document.querySelector('.carousel .thumbnail');
        let thumbnailItemsDom = thumbnailBorderDom.querySelectorAll('.item');
        let timeDom = document.querySelector('.carousel .time');

        thumbnailBorderDom.appendChild(thumbnailItemsDom[0]);
        let timeRunning = 500;
        let timeAutoNext = 7000;

        nextDom.onclick = function(){
            showSlider('next');    
        }

        prevDom.onclick = function(){
            showSlider('prev');    
        }
        let runTimeOut;
        let runNextAuto = setTimeout(() => {
            next.click();
        }, timeAutoNext)
        function showSlider(type){
            let  SliderItemsDom = SliderDom.querySelectorAll('.carousel .list .item');
            let thumbnailItemsDom = document.querySelectorAll('.carousel .thumbnail .item');
            
            if(type === 'next'){
                SliderDom.appendChild(SliderItemsDom[0]);
                thumbnailBorderDom.appendChild(thumbnailItemsDom[0]);
                carouselDom.classList.add('next');
            }else{
                SliderDom.prepend(SliderItemsDom[SliderItemsDom.length - 1]);
                thumbnailBorderDom.prepend(thumbnailItemsDom[thumbnailItemsDom.length - 1]);
                carouselDom.classList.add('prev');
            }
            clearTimeout(runTimeOut);
            runTimeOut = setTimeout(() => {
                carouselDom.classList.remove('next');
                carouselDom.classList.remove('prev');
            }, timeRunning);

            clearTimeout(runNextAuto);
            runNextAuto = setTimeout(() => {
                next.click();
            }, timeAutoNext)
        }
    }
    

    // article  page 1 slider start
    if ($('.article-slider-thumb-wrapper').length > 0) {
        var swiper = new Swiper(".article-slider-thumb-wrapper", {
            spaceBetween: 10,
            slidesPerView: 5,
            loop:true,
            freeMode: false,
            watchSlidesProgress: true,
            centeredSlides: true,
            watchSlidesProgress: true,
            breakpoints: {
                320: {
                    slidesPerView: 3,
                },
                575: {
                    slidesPerView: 4,
                },
                768: {
                    slidesPerView: 4,
                },
                992: {
                    slidesPerView: 5,
                },     
               
            },
        });
    };

    if ($('.article-slider-wrapper').length > 0) {
        var swiper2 = new Swiper(".article-slider-wrapper", {
        loop: true,
        navigation: {
            nextEl: ".swiper-slide-button-next",
            prevEl: ".swiper-slide-button-prev",
        },
        thumbs: {
            swiper: swiper,
        },
        })
    };

    // wow js active
    wowfunction();
    function wowfunction() {
        new WOW().init();
    }


    // gsap animation start
    // --------- INDEX-1 BANNER TITLE ANIMATION ----------
    
    if ($('.exp-text-animate-1').length > 0) {

        let headingTimeLine = gsap.timeline()
    
        let subTitleContainer = new SplitText(".exp-text-animate-1", { type: "chars" });
        let subTitleChar = subTitleContainer.chars 
        headingTimeLine.from(subTitleChar, {
          x: 20,
          ease: "back.out",
          opacity: 0,
          duration: 1,
          stagger: 0.1
        });
    };
    
    if ($('.exp-text-animate-3').length > 0) {
        function textAnimate(sliderElement) {
            const textsToAnimate = sliderElement.querySelectorAll(".exp-text-animate-3");
            textsToAnimate.forEach(textToAnimate => {
                const animate = new SplitType(textToAnimate, { types: 'words,chars' });  // Correct the types option
                gsap.from(animate.chars, {
                    opacity: 0,
                    x: 100,
                    duration: 1.1,
                    stagger: { amount: 0.9 },
                    scrollTrigger: {
                        trigger: textToAnimate,
                        start: "top 95%",
                        once: false  // Allow the animation to be triggered repeatedly
                    }
                });
            });
        };
    
        textAnimate(document);
    }
    // --------- INDEX-1 BANNER TITLE ANIMATION ----------


    // odometer CountDown
    if ($('.odometer').length > 0) {

        $('.odometer').appear(function(e) {
            var odo = $(".odometer");
            odo.each(function() {
                var countNumber = $(this).attr("data-count");
        
                // Initialize Odometer with duration option
                var odometer = new Odometer({
                    el: this,
                    value: 0,
                    format: '',
                    duration: 2000, // Set the duration in milliseconds
                });
        
                // Animate the odometer to the target value
                odometer.update(countNumber);
            });
        });
    };



    if ($('.table-of-content-body > li').length > 0) {

        document.addEventListener('DOMContentLoaded', function () {
            // Smooth scroll function with easeOutCubic easing function
            function smoothScroll(target, duration) {
                var targetElement = document.getElementById(target);
                var targetPosition = targetElement.offsetTop;
                var headerHeight = 100; // Adjust this value according to your header height
                var startPosition = window.pageYOffset + headerHeight;
                var distance = targetPosition - startPosition;
                var startTime = null;
        
                function animation(currentTime) {
                    if (startTime === null) startTime = currentTime;
                    var timeElapsed = currentTime - startTime;
                    var run = easeOutCubic(timeElapsed, startPosition, distance, duration);
                    window.scrollTo(0, run);
                    if (timeElapsed < duration) requestAnimationFrame(animation);
                }
        
                function easeOutCubic(t, b, c, d) {
                    t /= d;
                    t--;
                    return c * (t * t * t + 1) + b;
                }
        
                requestAnimationFrame(animation);
            }
        
            // Add click event listeners to table of contents links
            var tocLinks = document.querySelectorAll('.table-of-content-body > li');
            tocLinks.forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    var target = this.getAttribute('href').substring(1); // remove the '#' character
                    smoothScroll(target, 500); // adjust the duration as needed
                });
            });
        });
    };
    
    // Home Page 5 Hero silder
    if ($('.hero-thumbs-slide').length > 0) {
        var swiperThumbs = new Swiper(".hero-thumbs-slide", {
            spaceBetween: 20,
            loop: true,
            breakpoints: {
                320: { slidesPerView: 2 },
                768: { slidesPerView: 1 },
                992: { 
                    slidesPerView: 3, 
                    spaceBetween:10 
                },
                
                1200: { 
                    slidesPerView: 3.5, 
                    spaceBetween:20,

                }
            },
            navigation: {
                nextEl: ".hero-slider-next-btn",
                prevEl: ".hero-slider-prev-btn",
            },
            
        });
    
        var swiper2 = new Swiper(".hero-5-slider-wrapper", {
            loop: true,
            thumbs: {
                swiper: swiperThumbs,
            },
            navigation: {
                nextEl: ".hero-slider-next-btn",
                prevEl: ".hero-slider-prev-btn",
            },
            pagination: {
                el: ".swiper-pagination",
                type: "custom",
                renderCustom: function (swiper, current, total) {
                    var formattedCurrent = current < 10 ? '0' + current : current;
                    return formattedCurrent;
                }
            },
            on: {
                slideChange: function () {
                    textAnimate(this.el);
                }
            },
            
        });
    }

   
    // Home page 6 hero iamge change 
    if ($('.hero-inner-image').length > 0) {
        const heroImages = document.querySelectorAll('.hero-inner-image img');
        const heroWrapper = document.querySelector('.hero-wrapper-6');
    
        // Set initial background image
        let currentImageIndex = 0;
        const defaultImageUrl = heroImages[currentImageIndex].src;
        heroWrapper.style.backgroundImage = `url(${defaultImageUrl})`;
    
        // Function to change background image
        function changeBackgroundImage() {
            currentImageIndex = (currentImageIndex + 1) % heroImages.length;
            const imageUrl = heroImages[currentImageIndex].src;
            heroWrapper.style.transition = 'background-image 0.3s ease-in-out';
            heroWrapper.style.backgroundImage = `url(${imageUrl})`;
        }
    
        // Change background image automatically every 4 seconds
        const interval = setInterval(changeBackgroundImage, 4000);
    
        // Stop automatic background image change when any hero-inner-image is clicked
        const heroInnerImages = document.querySelectorAll('.hero-inner-image');
        heroInnerImages.forEach(image => {
            image.addEventListener('click', function() {
                clearInterval(interval); // Stop automatic change
                const imageUrl = this.querySelector('img').src;
                heroWrapper.style.transition = 'none'; // Remove transition for manual change
                heroWrapper.style.backgroundImage = `url(${imageUrl})`;
            });
        });
    }
    
})(jQuery);

document.addEventListener("DOMContentLoaded", function () {
  // make it as accordion for smaller screens
    if (window.innerWidth > 992) {
        document.querySelectorAll('.hover-menu .nav-item.dropdown').forEach(function (everyitem) {
            everyitem.addEventListener('mouseover', function (e) {
                let el_link = this.querySelector('a[data-bs-toggle]');
                if (el_link !== null) {
                    let nextEl = el_link.nextElementSibling;
                    el_link.classList.add('show');
                    if (nextEl !== null && this.contains(nextEl)) {
                        nextEl.classList.add('show');
                    }
                }
            }.bind(everyitem)); // Explicitly bind the context to the current element
            everyitem.addEventListener('mouseleave', function (e) {
                let el_link = this.querySelector('a[data-bs-toggle]');
                if (el_link !== null) {
                    let nextEl = el_link.nextElementSibling;
                    if (nextEl !== null && this.contains(nextEl)) {
                        el_link.classList.remove('show');
                        nextEl.classList.remove('show');
                    }
                }
            }.bind(everyitem)); // Explicitly bind the context to the current element
        });
        
    }
  // end if innerWidth
});

