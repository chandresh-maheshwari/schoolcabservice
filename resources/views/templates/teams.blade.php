<!-- Team Section -->
<section id="team" class="team section">

    <!-- Section Title -->
    <div class="container section-title" data-aos="fade-up">
        <h2>{{ $page->title ?? 'Team' }}</h2>
        <p>{!! $page->description ?? 'Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit' !!}</p>
    </div><!-- End Section Title -->

    <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-4 justify-content-center" id="teams-row">
            <!-- Dynamic team cards will be rendered here -->
        </div>

    </div>

</section><!-- /Team Section -->
<script>
    (function() {
        function renderTeams() {
            var row = document.getElementById('teams-row');
            if (!row) return;
            var fallbackImg = '{{ asset('images/Default.jpg') }}';

            function getImageUrl(imagePath) {
                if (!imagePath) return fallbackImg;
                var s = String(imagePath);
                if (s.indexOf('http://') === 0 || s.indexOf('https://') === 0) return s;
                if (s.indexOf('/') === 0) s = s.substring(1);
                return '{{ asset('') }}' + s;
            }

            function checkImageExists(url) {
                return new Promise(function(resolve) {
                    var img = new Image();
                    img.onload = function() {
                        resolve(true);
                    };
                    img.onerror = function() {
                        resolve(false);
                    };
                    img.src = url;
                });
            }
            fetch('/api/frontend/teams')
                .then(function(r) {
                    return r.json();
                })
                .then(async function(payload) {
                    var items = Array.isArray(payload) ? payload : (payload && payload.data) ? payload
                        .data : [];
                    if (!Array.isArray(items)) return;
                    var parts = [];
                    for (var idx = 0; idx < items.length; idx++) {
                        var member = items[idx];
                        var delay = 200 + (idx % 4) * 50; // 200, 250, 300, 350 pattern
                        var name = member.title || member.full_name || '';
                        var role = member.role || member.position || '';
                        var bio = member.bio || member.description || '';
                        var candidate = getImageUrl(member.image || member.photo || '');
                        var ok = await checkImageExists(candidate);
                        var image = ok ? candidate : fallbackImg;
                        var linkedin = member.linkedin || member.linkedin_url || '';
                        var twitter = member.twitter || member.twitter_url || '';
                        var instagram = member.instagram || member.instagram_url || '';

                        function socialIcon(href, icon) {
                            return href ? '<a href="' + href +
                                '" target="_blank" rel="noopener"><i class="' + icon + '"></i></a>' : '';
                        }
                        const fullText = bio ||
                            'Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam.';
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = fullText;
                        const plainText = tempDiv.textContent || tempDiv.innerText || '';
                        const shortText = plainText.length > 100 ? plainText.substring(0, 100) + '...' :
                            plainText;
                        const isExpandable = plainText.length > 100;
                        // parts.push('\n<div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="'+ delay +'">\n'
                        //     + '  <div class="team-card">\n'
                        //     + '    <div class="profile-image">\n'
                        //     + '      <img src="'+ image +'" class="img-fluid" alt="" loading="lazy" style="width:100%;height:100%;object-fit:contain;">\n'
                        //     + (role ? '      <div class="role-badge">'+ role +' </div>\n' : '')
                        //     + '      <div class="social-icons">\n'
                        //     +          socialIcon(linkedin, 'bi bi-linkedin')
                        //     +          socialIcon(twitter, 'bi bi-twitter-x')
                        //     +          socialIcon(instagram, 'bi bi-instagram')
                        //     + '      </div>\n'
                        //     + '    </div>\n'
                        //     + '    <div class="member-info">\n'
                        //     + '      <h4>'+ name +'</h4>\n'
                        //     + (isExpandable
                        //         ? '      <div class="description-wrapper" data-expanded="false">\n'
                        //         + '        <span class="description-text">'+ shortText +'</span>\n'
                        //         + '        <div class="full-description" style="display:none;">'+ fullText +'</div>\n'
                        //         + '        <a href="javascript:void(0);" class="toggle-front-description" style="margin-left: 5px; color: #007bff;">Read More</a>\n'
                        //         + '      </div>\n'
                        //         : '      <span class="description-text">'+ plainText +'</span>\n'
                        //     )
                        //     + '    </div>\n'
                        //     + '  </div><!-- End Team Card -->\n'
                        //     + '</div>');
                        parts.push(`
                            <div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="${delay}">
                                <div class="team-card">
                                <div class="profile-image">
                                    <img src="${image}" class="img-fluid" alt="" loading="lazy" style="width:100%;height:100%;object-fit:contain;">
                                    ${role ? `<div class="role-badge">${role}</div>` : ''}
                                    <div class="social-icons">
                                    ${socialIcon(linkedin, 'bi bi-linkedin')}
                                    ${socialIcon(twitter, 'bi bi-twitter-x')}
                                    ${socialIcon(instagram, 'bi bi-instagram')}
                                    </div>
                                </div>
                                <div class="member-info">
                                    <h4>${name}</h4>
                                    ${
                                    isExpandable
                                        ? `
                                        <div class="description-wrapper" data-expanded="false">
                                            <span class="description-text">${shortText}</span>
                                            <div class="full-description" style="display:none;">${fullText}</div>
                                            <a href="javascript:void(0);" class="toggle-front-description" style="margin-left: 5px; color: #007bff;">Read More</a>
                                        </div>
                                        `
                                        : `<span class="description-text">${plainText}</span>`
                                    }
                                </div>
                                </div><!-- End Team Card -->
                            </div>
                            `);

                    }
                    row.innerHTML = parts.join('');
                    if (typeof AOS !== 'undefined') {
                        AOS.refresh();
                    }
                })
                .catch(function() {});
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', renderTeams, {
                once: true
            });
        } else {
            renderTeams();
        }
    })();
</script>
