<div class="author-socials mb-30" id="author-socials-container">

</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('{{ route('api.author_socials.all') }}')
        .then(response => response.json())
        .then(data => {
            const socials = data.data.filter(item => item.status === 1);
            const container = document.getElementById('author-socials-container');
            let widget = document.getElementById('blog-social-widget');
            if (!widget) widget = document.getElementById('contact-social-widget');
            if (!widget) widget = document.getElementById('content-social-widget');
            if (!widget) widget = document.getElementById('social-widget');
            if (socials.length === 0) {
                if (widget) widget.remove();
                return;
            }
            socials.forEach(social => {
                const a = document.createElement('a');
                a.href = social.social_link;
                a.className = social.name.toLowerCase(); 
                a.target = '_blank';
                a.rel = 'noopener noreferrer';

                const i = document.createElement('i');
                i.className = social.social_icon; 

                a.appendChild(i);
                container.appendChild(a);
            });
            console.log(container.innerHTML);
        })
        .catch(error => {
            let widget = document.getElementById('blog-social-widget');
            if (!widget) widget = document.getElementById('contact-social-widget');
            if (!widget) widget = document.getElementById('content-social-widget');
            if (!widget) widget = document.getElementById('social-widget');
            if (widget) widget.remove();
            console.error('Error fetching social links:', error);
        });
});
</script>
