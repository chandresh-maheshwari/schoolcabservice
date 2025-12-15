<!-- Clients Section - FULL WORKING VERSION -->
<section id="clients" class="clients section" aria-label="Clients" style="position:relative;">
  <div data-aos="fade-up" data-aos-delay="100">
    <div class="clients-marquee-wrap" id="clients-marquee-wrap">
      <div class="clients-marquee">
        <div class="clients-track" id="clients-track" aria-live="polite" aria-busy="true"></div>
      </div>
    </div>
  </div>

  <!-- Debug overlay (visible during dev; remove if not needed) -->
  <div id="clients-debug" style="... display:none;">
  <div id="dbg-status">loading…</div>
  <div id="dbg-counts"></div>
</div>

</section>

<style>
/* Basic layout */
.clients-marquee-wrap {
  overflow: hidden;
  width: 100%;
  padding: 10px 20px;
  opacity: 0;
  transition: opacity 0.25s ease;
}

.clients-marquee { width:100%; overflow:hidden; position:relative; }

.clients-track {
  display:flex;
  align-items:center;
  gap:30px;
  will-change: transform;
}

/* item */
.clients-item { flex:0 0 auto; display:flex; align-items:center; justify-content:center; min-width:120px; }

.clients-item img {
  max-height:60px;
  width:auto;
  object-fit:contain;
  display:block;
  user-select:none;
  pointer-events:none;
  filter:grayscale(100%);
  transition: filter .35s, transform .2s;
}
#clients-debug { display: none !important; }

.clients-item:hover img { filter:grayscale(0); transform:scale(1.03); }

/* pause on hover */
.clients-marquee-wrap:hover .clients-track { animation-play-state: paused !important; }

/* hide debug on small screens */
@media (max-width:600px) { #clients-debug { display:none !important; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const track = document.getElementById('clients-track');
  const wrap = document.getElementById('clients-marquee-wrap');
  const debugBox = document.getElementById('clients-debug');
  const dbgStatus = document.getElementById('dbg-status');
  const dbgCounts = document.getElementById('dbg-counts');

  // BLADE placeholders (will render to real URLs)
  const defaultLogo = "{{ asset('images/Default.jpg') }}";
  const storageBase = "{{ url('storage') }}/";

  // show debug overlay for now
  debugBox.style.display = 'block';

  const log = (...args) => {
    console.log('[clients-marquee]', ...args);
    dbgCounts.innerText = args.map(a => (typeof a === 'object' ? JSON.stringify(a) : String(a))).join(' | ');
  };

  const getImageUrl = (path) => {
    if (!path) return defaultLogo;
    if (path.startsWith('http')) return path;
    let clean = path.replace(/^\/+/, '').replace(/^public\//, '').replace(/^storage\//, '');
    return storageBase + clean;
  };

  const createSlide = (src, alt = 'Client') => {
    const item = document.createElement('div');
    item.className = 'clients-item';
    const img = document.createElement('img');
    img.src = src;
    img.alt = alt;
    // onerror fallback to default
    img.onerror = () => { if (img.src !== defaultLogo) img.src = defaultLogo; };
    item.appendChild(img);
    return item;
  };

  // inject animation style with pixel distance and duration
  const injectAnimation = (distancePx, durationSec) => {
    const id = 'clients-marquee-anim';
    let old = document.getElementById(id);
    if (old) old.remove();
    const s = document.createElement('style');
    s.id = id;
    s.innerHTML = `
      @keyframes clients-slide {
        0% { transform: translateX(0); }
        100% { transform: translateX(-${distancePx}px); }
      }
      .clients-track { animation: clients-slide ${durationSec}s linear infinite; }
    `;
    document.head.appendChild(s);
  };

  // measure sum width of a NodeList
  const sumWidths = (nodes) => nodes.reduce((acc, n) => acc + Math.ceil(n.getBoundingClientRect().width), 0);

  (async function init() {
    dbgStatus.innerText = 'fetching API...';
    try {
      // show immediate placeholder so user doesn't see blank
      track.innerHTML = '';
      track.appendChild(createSlide(defaultLogo, 'loading'));

      const res = await fetch('/api/frontend/clients', { cache: 'no-store' });
      let json = null;
      try { json = await res.json(); } catch(e) { log('json parse error', e); }

      // clear placeholder
      track.innerHTML = '';

      const items = Array.isArray(json?.data) ? json.data.filter(d => d.status == 1) : [];
      dbgStatus.innerText = `items fetched: ${items.length}`;

      if (!items.length) {
        track.appendChild(createSlide(defaultLogo, 'Default'));
      } else {
        // append original set
        for (const it of items) {
          const url = getImageUrl(it.image || it.client || '');
          track.appendChild(createSlide(url, it.title || 'Client'));
        }
      }

      // Wait a tick so images start loading and DOM paints
      await new Promise(r => setTimeout(r, 80));

      // compute original width (sum of current children)
      let originalChildren = Array.from(track.children);
      let originalWidth = sumWidths(originalChildren);

      // If no content width (images not loaded yet), try waiting a bit more
      if (originalWidth < 50) {
        dbgStatus.innerText = 'waiting for images to layout...';
        await new Promise(r => setTimeout(r, 300));
        originalChildren = Array.from(track.children);
        originalWidth = sumWidths(originalChildren);
      }

      // Ensure track overflows container: clone originals until originalWidth >= containerWidth
      const containerWidth = wrap.clientWidth || document.documentElement.clientWidth;
      let copies = 0;
      while (originalWidth < containerWidth + 20 && copies < 6) {
        // append one clone of originals
        originalChildren.forEach(n => track.appendChild(n.cloneNode(true)));
        copies++;
        await new Promise(r => setTimeout(r, 30)); // let layout update
        // recompute originalWidth as sum of first set (we keep originals at start)
        const firstSet = Array.from(track.children).slice(0, originalChildren.length);
        originalWidth = sumWidths(firstSet);
      }

      // Now compute the distance we need to slide = width of first original set
      const firstSetElems = Array.from(track.children).slice(0, originalChildren.length);
      const distancePx = sumWidths(firstSetElems) || containerWidth || 800;

      // Duplicate exactly once after the original set to make seamless loop
      // (If we already cloned, avoid excessive duplication)
      if (track.childElementCount <= originalChildren.length * 2) {
        originalChildren.forEach(n => track.appendChild(n.cloneNode(true)));
      }

      // compute duration based on px/sec speed
      const pxPerSecond = 45; // tweakable: larger -> slower
      const durationSec = Math.max(8, Math.round(distancePx / pxPerSecond));

      // inject animation and show
      injectAnimation(distancePx, durationSec);
      wrap.style.opacity = '1';
      track.setAttribute('aria-busy', 'false');

      debugBox.style.display = 'block'; // <--- this makes it visible
dbgStatus.innerText = `running: distance=${distancePx}px duration=${durationSec}s copies=${copies}`;
log({ distancePx, durationSec, copies, childCount: track.childElementCount });


    } catch (err) {
      console.error('[clients-marquee] critical', err);
      dbgStatus.innerText = 'error';
      track.innerHTML = '';
      track.appendChild(createSlide(defaultLogo, 'Default'));
      wrap.style.opacity = '1';
      track.setAttribute('aria-busy', 'false');
    }
  })();

  // helpful click to dump debug in console
  document.getElementById('clients-marquee-wrap').addEventListener('click', () => {
    console.log('[clients-marquee-debug]', {
      wrapWidth: wrap.clientWidth,
      scrollWidth: track.scrollWidth,
      children: track.childElementCount
    });
  });

});
</script>

{{-- <!-- Clients Section -->

<section id="clients" class="clients section" aria-label="Clients">

  <div data-aos="fade-up" data-aos-delay="100">

    <div class="clients-marquee-wrap">

      <div class="clients-marquee" id="clients-marquee">

        <div class="clients-track" id="clients-track">

          <!-- Logos injected dynamically -->

        </div>

      </div>

    </div>

  </div>

</section>



<style>

.clients-marquee-wrap {

  overflow: hidden;

  width: 100%;

  padding: 10px 20px;

}



.clients-marquee {

  width: 100%;

  overflow: hidden;

  position: relative;

}



.clients-track {

  display: flex;

  align-items: center;

  gap: 30px;

}



.clients-item {

  flex: 0 0 auto;

  display: flex;

  align-items: center;

  justify-content: center;

  min-width: 120px;

}



.clients-item img {

  max-height: 60px;

  width: auto;

  object-fit: contain;

  display: block;

  user-select: none;

  pointer-events: none;

}

/* By default: blur images */
.clients-item img {
     filter: grayscale(100%) !important;
  transition: filter 0.5s ease-in-out !important;
}

/* On hover: show clear image */
.clients-item:hover img {

    filter: grayscale(0%) !important;

}

</style>



<script>

document.addEventListener('DOMContentLoaded', function () {

  const track = document.getElementById('clients-track');

  const marqueeWrap = document.querySelector('.clients-marquee-wrap');

  const defaultLogo = '{{ asset('images/Default.jpg') }}';



  const getImageUrl = (imagePath) => {

    if (!imagePath || imagePath === '') return defaultLogo;

    if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) return imagePath;

    let clean = imagePath.replace(/^\/+/, '').replace(/^public\//, '').replace(/^storage\//, '');

    return '{{ url('storage') }}/' + clean;

  };



  const checkImageExists = (url, timeout = 3000) => {

    return new Promise((resolve) => {

      const img = new Image();

      let done = false;

      const finish = (val) => { if (!done) { done = true; resolve(val); } };

      img.onload = () => finish(true);

      img.onerror = () => finish(false);

      img.src = url;

      setTimeout(() => finish(false), timeout);

    });

  };



  const createSlide = (imgSrc, alt = 'Client') => {

    const item = document.createElement('div');

    item.className = 'clients-item';

    const img = document.createElement('img');

    img.src = imgSrc;

    img.alt = alt;

    item.appendChild(img);

    return item;

  };



//   const applyMarqueeAnimation = (durationSec) => {

//     const old = document.getElementById('clients-marquee-animation');

//     if (old) old.remove();



//     const style = document.createElement('style');

//     style.id = 'clients-marquee-animation';

//     style.innerHTML = `

//       @keyframes clients-marquee {

//         0% { transform: translateX(0); }

//         100% { transform: translateX(-50%); }

//       }

//       .clients-track {

//         animation: clients-marquee ${durationSec}s linear infinite;

//       }

//     `;

//     document.head.appendChild(style);

//   };

const applyMarqueeAnimation = (durationSec) => {

  const old = document.getElementById('clients-marquee-animation');

  if (old) old.remove();



  const style = document.createElement('style');

  style.id = 'clients-marquee-animation';

  style.innerHTML = `

    @keyframes clients-marquee {

      0% { transform: translateX(100%); }   /* start off-screen right */

      100% { transform: translateX(-50%); } /* move to the left */

    }

    .clients-track {

      animation: clients-marquee ${durationSec}s linear infinite;

    }

  `;

  document.head.appendChild(style);

};

  (async function run() {

    try {

      const res = await fetch('/api/frontend/clients');

      const json = await res.json();



      if (!json || !json.success || !Array.isArray(json.data) || json.data.length === 0) {

        track.appendChild(createSlide(defaultLogo, 'Default'));

        return;

      }



      const activeClients = json.data.filter(c => c.status == 1); // ✅ only active data

      if (activeClients.length === 0) {

        track.appendChild(createSlide(defaultLogo, 'Default'));

        return;

      }



      // Add only active client slides

      for (const client of activeClients) {

        const rawPath = client.image || client.client || '';

        const imageUrl = getImageUrl(rawPath);

        const exists = await checkImageExists(imageUrl);

        const slide = createSlide(exists ? imageUrl : defaultLogo, client.title || 'Client');

        track.appendChild(slide);

      }



      // ✅ Duplicate exactly once for smooth looping — not multiple times

      const originals = Array.from(track.children);

      originals.forEach(c => track.appendChild(c.cloneNode(true)));



      // Wait to render then compute width

      await new Promise(r => setTimeout(r, 50));

      const trackWidth = track.scrollWidth;

      const baseSpeed = 30; // px per second

      const durationSec = Math.max(8, Math.round(trackWidth / baseSpeed));



      applyMarqueeAnimation(durationSec);

    } catch (err) {

      console.error('Clients marquee error:', err);

      track.appendChild(createSlide(defaultLogo, 'Default'));

    }

  })();

});

</script>

 --}}
