(function($) {
  'use strict';
  $(function() {
    function closeContentDropdownOverlays() {
      var $contentScope = $('.content-wrapper, .page-body-wrapper');
      var activeElement = document.activeElement;

      if (activeElement && typeof activeElement.blur === 'function' && !$(activeElement).closest('.horizontal-menu').length) {
        activeElement.blur();
      }

      if ($.fn.select2) {
        $contentScope.find('select.select2-hidden-accessible').each(function() {
          try {
            $(this).select2('close');
          } catch (error) {
            // Ignore plugin-specific close failures and continue closing other overlays.
          }
        });
      }

      $contentScope.find('[data-bs-toggle="dropdown"][aria-expanded="true"], [data-toggle="dropdown"][aria-expanded="true"]').each(function() {
        if (window.bootstrap && bootstrap.Dropdown) {
          bootstrap.Dropdown.getOrCreateInstance(this).hide();
          return;
        }

        if ($.fn.dropdown) {
          try {
            $(this).dropdown('hide');
          } catch (error) {
            // Fall through to the class cleanup below.
          }
        }
      });

      $contentScope.find('.dropdown-menu.show').removeClass('show');
      $contentScope.find('.dropdown.show').removeClass('show');
    }

    $(".nav-settings").click(function() {
      $("#right-sidebar").toggleClass("open");
    });
    $(".settings-close").click(function() {
      $("#right-sidebar,#theme-settings").removeClass("open");
    });

    $("#settings-trigger").on("click", function() {
      $("#theme-settings").toggleClass("open");
    });


    //background constants
    var navbar_classes = "navbar-danger navbar-success navbar-warning navbar-dark navbar-light navbar-primary navbar-info navbar-pink";
    var sidebar_classes = "sidebar-light sidebar-dark";
    var $body = $("body");

    //sidebar backgrounds
    $("#sidebar-default-theme").on("click", function() {
      $body.removeClass(sidebar_classes);
      $(".sidebar-bg-options").removeClass("selected");
      $(this).addClass("selected");
    });
    $("#sidebar-dark-theme").on("click", function() {
      $body.removeClass(sidebar_classes);
      $body.addClass("sidebar-dark");
      $(".sidebar-bg-options").removeClass("selected");
      $(this).addClass("selected");
    });


    //Navbar Backgrounds
    $(".tiles.primary").on("click", function() {
      $(".navbar").removeClass(navbar_classes);
      $(".navbar").addClass("navbar-primary");
      $(".tiles").removeClass("selected");
      $(this).addClass("selected");
    });
    $(".tiles.success").on("click", function() {
      $(".navbar").removeClass(navbar_classes);
      $(".navbar").addClass("navbar-success");
      $(".tiles").removeClass("selected");
      $(this).addClass("selected");
    });
    $(".tiles.warning").on("click", function() {
      $(".navbar").removeClass(navbar_classes);
      $(".navbar").addClass("navbar-warning");
      $(".tiles").removeClass("selected");
      $(this).addClass("selected");
    });
    $(".tiles.danger").on("click", function() {
      $(".navbar").removeClass(navbar_classes);
      $(".navbar").addClass("navbar-danger");
      $(".tiles").removeClass("selected");
      $(this).addClass("selected");
    });
    $(".tiles.info").on("click", function() {
      $(".navbar").removeClass(navbar_classes);
      $(".navbar").addClass("navbar-info");
      $(".tiles").removeClass("selected");
      $(this).addClass("selected");
    });
    $(".tiles.dark").on("click", function() {
      $(".navbar").removeClass(navbar_classes);
      $(".navbar").addClass("navbar-dark");
      $(".tiles").removeClass("selected");
      $(this).addClass("selected");
    });
    $(".tiles.light").on("click", function() {
      $(".navbar").removeClass(navbar_classes);
      $(".navbar").addClass("navbar-light");
      $(".tiles").removeClass("selected");
      $(this).addClass("selected");
    });

    //Horizontal menu in mobile
    $('[data-toggle="horizontal-menu-toggle"]').on("click", function() {
      closeContentDropdownOverlays();
      $(".horizontal-menu .bottom-navbar").toggleClass("header-toggled");
    });
    // Horizontal menu navigation in mobile menu on click
    var navItemClicked = $('.horizontal-menu .page-navigation >.nav-item');
    navItemClicked.on("mouseenter focusin", function() {
      closeContentDropdownOverlays();
    });
    navItemClicked.on("click", function(event) {
      closeContentDropdownOverlays();
      if(window.matchMedia('(max-width: 991px)').matches) {
        if(!($(this).hasClass('show-submenu'))) {
          navItemClicked.removeClass('show-submenu');
        }
        $(this).toggleClass('show-submenu');
      }        
    });

    $('.horizontal-menu .top-navbar .navbar-nav .nav-item.dropdown > .nav-link').on('click', function() {
      closeContentDropdownOverlays();
    });

    $(window).scroll(function() {
      if(window.matchMedia('(min-width: 992px)').matches) {
        var header = $('.horizontal-menu');
        if ($(window).scrollTop() >= 71) {
          $(header).addClass('fixed-on-scroll');
        } else {
          $(header).removeClass('fixed-on-scroll');
        }
      }
    });

  });
})(jQuery);
