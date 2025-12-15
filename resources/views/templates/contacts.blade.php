<!-- Contact Section -->
<section id="contact" class="contact section light-background">
    <div class="container section-title" data-aos="fade-up">
        <h2 id="contact-section-title">{{ $page->title }}</h2>
        <p id="contact-section-desc">{!! $page->description !!}</p>
    </div>
    <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-5">
                <div class="info-box" data-aos="fade-up" data-aos-delay="200" id="dynamic-contact-info">
                    <!-- Will be filled by JS from API info_blocks array -->
                </div>
            </div>
            <div class="col-lg-7">
                <div class="contact-form" data-aos="fade-up" data-aos-delay="300">
                    <h3 id="contact-form-title">Get In Touch</h3>
                    <p id="contact-form-desc">Loading...</p>
                    <form id="contact-form" class="php-email-form" data-aos="fade-up" data-aos-delay="200">
                        <div class="row gy-4">
                            <div class="col-md-6">
                                <input type="text" name="name" class="form-control" placeholder="Your Name" required>
                            </div>
                            <div class="col-md-6 ">
                                <input type="email" class="form-control" name="email" placeholder="Your Email" required>
                            </div>
                            <div class="col-12">
                                <input type="text" class="form-control" name="subject" placeholder="Subject" required>
                            </div>
                            <div class="col-12">
                                <textarea class="form-control" name="message" rows="6" placeholder="Message" required></textarea>
                            </div>
                            <div class="col-12 text-center">
                                <div class="loading" id="contact-loading" style="display:none;">Loading</div>
                                <div class="error-message" id="contact-error" style="display:none;"></div>
                                <div class="sent-message" id="contact-sent" style="display:none;">Your message has been sent. Thank you!</div>
                                <button type="submit" class="btn" id="contact-button">Send Message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function(){
function infoBlockHtml(key, values){
    if(!Array.isArray(values) || values.length === 0) return '';
    const iconMap = {
      addresses: 'bi-geo-alt', phones: 'bi-telephone', emails: 'bi-envelope',
    };
    const labelMap = {
      addresses: 'Our Location', phones: 'Phone Number', emails: 'Email Address',
    };
    const icon = iconMap[key] || 'bi-info-circle';
    const label = labelMap[key] || key;
    return `<div class="info-item">\n      <div class=\"icon-box\"><i class=\"bi ${icon}\"></i></div>\n      <div class=\"content\">\n        <h4>${label}</h4>\n        ${values.map(val => `<p>${val.replace(/^<p>|<\/p>$/g, '')}</p>`).join('')}\n      </div>\n    </div>`;
}
function renderContacts(){
    fetch('/api/frontend/contacts')
        .then(r => r.json())
        .then(payload => {
            const data = (payload && payload.data) ? payload.data : payload;
            // Section titles/descriptions
            if (data.title) document.getElementById('contact-form-title').textContent = data.title;
            if (data.description) document.getElementById('contact-form-desc').innerHTML = data.description;
            // if (data.info_title) document.getElementById('contact-section-title').textContent = data.info_title;
            // if (data.info_description) document.getElementById('contact-section-desc').innerHTML = data.info_description;
            // if (data.form_title) document.getElementById('contact-form-title').textContent = data.form_title;
            // if (data.form_description) document.getElementById('contact-form-desc').innerHTML = data.form_description;
            if (data.loading_text) document.getElementById('contact-loading').textContent = data.loading_text;
            if (data.sent_text)  document.getElementById('contact-sent').textContent = data.sent_text;
            if (data.button_text) document.getElementById('contact-button').textContent = data.button_text;
            // Info blocks
            const infoBox = document.getElementById('dynamic-contact-info');
            let html = '';
            ['addresses','phones','emails'].forEach(k => {
                if(Array.isArray(data[k]) && data[k].length>0) {
                   html += infoBlockHtml(k, data[k]);
                }
            });
            if(infoBox) infoBox.innerHTML = html;
            if (typeof AOS !== 'undefined') { AOS.refresh(); }
        })
        .catch(err => { console.error('Error loading contact data:', err); });
}
function handleFormSubmission() {
    var form = document.getElementById('contact-form');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(form);
        var submitButton = document.getElementById('contact-button');
        var loadingDiv = document.getElementById('contact-loading');
        var errorDiv = document.getElementById('contact-error');
        var sentDiv = document.getElementById('contact-sent');
        submitButton.style.display = 'none';
        loadingDiv.style.display = 'block';
        errorDiv.style.display = 'none';
        sentDiv.style.display = 'none';
        var data = {
            name: formData.get('name'),
            email: formData.get('email'),
            subject: formData.get('subject'),
            message: formData.get('message')
        };
        fetch('/api/frontend/contact-message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            loadingDiv.style.display = 'none';
            if (result.success) {
                sentDiv.style.display = 'block';
                form.reset();
            } else {
                errorDiv.textContent = result.message || 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
            }
        })
        .catch(error => {
            loadingDiv.style.display = 'none';
            errorDiv.textContent = 'An error occurred. Please try again.';
            errorDiv.style.display = 'block';
            console.error('Error:', error);
        })
        .finally(function() {
            submitButton.style.display = 'block';
        });
    });
}
function initializePage() {
    renderContacts();
    handleFormSubmission();
}
if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', initializePage, { once: true });
} else {
    initializePage();
}
})();
</script>