<!-- Faq Section -->
<section id="faq" class="faq section">

    <div class="container">

        <div class="row gy-4">

            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <div class="content px-xl-5">
                    <h3><span>{{ $page->title ?? 'Frequently Asked' }} </span><strong>Questions</strong></h3>
                    {{-- <p>{!! $page->description ?? 'Find answers to the most common questions.' !!}</p> --}}
                    @php
                        $fullText =
                            $page->description ??
                            'Nunc euismod, tortor nec facilisis egestas, ligula turpis cursus odio, a lobortis sapien ipsum et dolor. Morbi dignissim cursus massa non lobortis.';
                        $plainText = trim(strip_tags($fullText));
                        $shortText = strlen($plainText) > 200 ? substr($plainText, 0, 200) . '...' : $plainText;
                        $isExpandable = strlen($plainText) > 200;
                    @endphp
                    @if ($isExpandable)
                        <div class="description-wrapper" data-expanded="false">
                            <span class="description-text">{{ $shortText }}</span>
                            <div class="full-description" style="display:none;">{!! $fullText !!}</div>
                            <a href="javascript:void(0);" class="toggle-front-description"
                                style="margin-left: 5px; color: #007bff;">Read More</a>
                        </div>
                    @else
                        <span class="description-text">{{ $plainText }}</span>
                    @endif
                </div>
            </div>

            <div class="col-lg-8" data-aos="fade-up" data-aos-delay="200">

                <div class="faq-container" id="faq-container">
                    <!-- Dynamic FAQ items will be rendered here -->
                </div>

            </div>
        </div>

    </div>

</section><!-- /Faq Section -->
<script>
    if (!window.faqScriptLoaded) {
        window.faqScriptLoaded = true;

        (function() {
            document.addEventListener('DOMContentLoaded', function() {
                // Remove duplicate FAQ sections if template included multiple times
                var faqSections = document.querySelectorAll('section#faq');
                if (faqSections.length > 1) {
                    for (var i = 1; i < faqSections.length; i++) {
                        faqSections[i].parentNode && faqSections[i].parentNode.removeChild(faqSections[i]);
                    }
                }

                var container = document.querySelector('#faq .faq-container');
                if (!container) return;
                fetch('/api/frontend/faq')
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(payload) {
                        var items = Array.isArray(payload) ? payload : (payload && payload.data) ?
                            payload.data : [];
                        if (!Array.isArray(items)) return;
                        var html = items.map(function(faq, idx) {
                            var num = (idx + 1) + '.';
                            return '\n<div class="faq-item' + (idx === 0 ? ' faq-active' : '') +
                                '" data-index="' + idx + '">\n' +
                                '  <h3><span class="num">' + num + '</span> <span>' + (faq
                                    .question || '') + '</span></h3>\n' +
                                '  <div class="faq-content" style="display:' + (idx === 0 ?
                                    'block' : 'none') + ';">\n' +
                                '    <p>' + (faq.answer || '') + '</p>\n' +
                                '  </div>\n' +
                                '  <i class="faq-toggle bi bi-chevron-right"></i>\n' +
                                '</div>';
                        }).join('');
                        container.innerHTML = html;

                        // Toggle behavior: only one open at a time
                        function closeAllExcept(activeEl) {
                            var all = container.querySelectorAll('.faq-item');
                            for (var i = 0; i < all.length; i++) {
                                var item = all[i];
                                var content = item.querySelector('.faq-content');
                                if (item !== activeEl) {
                                    item.classList.remove('faq-active');
                                    if (content) content.style.display = 'none';
                                }
                            }
                        }

                        container.addEventListener('click', function(e) {
                            var h3 = e.target.closest('h3');
                            var toggle = e.target.closest('.faq-toggle');
                            var item = h3 ? h3.closest('.faq-item') : (toggle ? toggle.closest(
                                '.faq-item') : null);
                            if (!item) return;
                            var content = item.querySelector('.faq-content');
                            var isOpen = item.classList.contains('faq-active');
                            if (isOpen) {
                                // close current
                                item.classList.remove('faq-active');
                                if (content) content.style.display = 'none';
                            } else {
                                // open this and close others
                                closeAllExcept(item);
                                item.classList.add('faq-active');
                                if (content) content.style.display = 'block';
                            }
                        });

                        if (typeof AOS !== 'undefined') {
                            AOS.refresh();
                        }
                    })
                    .catch(function() {});
            }, {
                once: true
            });
        })();
    }
</script>
