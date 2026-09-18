<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <title>SPI DPMPTSP Kabupaten Pidie — Sistem Pengawasan Kepatuhan</title>
  <meta name="description" content="Sistem Pengawasan Kepatuhan DPMPTSP Kabupaten Pidie — daftar list sanksi pencabutan & usulan pencabutan perizinan berusaha. Integrasi OCR & cetak SP1/rekap." />
  <meta name="theme-color" content="#8b1c13" />
  <link rel="icon" href="{{ asset('guest/assets/favicon.ico') }}" sizes="any" />
  <link rel="stylesheet" href="{{ asset('guest/assets/montserrat.css') }}" />
  <link rel="stylesheet" href="{{ asset('guest/assets/material-symbols-outlined.css') }}" />
  <link rel="stylesheet" href="{{ asset('guest/assets/tabler-icons.css') }}" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Montserrat', 'system-ui', 'sans-serif'] },
          colors: {
            'oss-base-black': '#0F172A',
            'oss-base-white': '#FFFFFF',
            'oss-gray-25': '#F9FAFB',
            'oss-gray-200': '#E5E7EB',
            'oss-gray-500': '#6B7280',
            'oss-blue-500': '#00479B',
            'primary': '#8b1c13',
            'primary-600': '#8b1c13',
            'primary-700': '#74160f',
          },
          maxWidth: { '1920': '1920px' },
        },
      },
    };
  </script>
  <style>
    body { font-family: 'Montserrat', system-ui, sans-serif; }
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    :where(a, button, input, [tabindex]):focus-visible { outline: 2px solid #8b1c13; outline-offset: 2px; border-radius: 6px; }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .gradient-left, .gradient-right { position: absolute; top: 0; bottom: 0; width: 80px; pointer-events: none; z-index: 10; }
    .gradient-left { left: 0; background: linear-gradient(to right, #fff, transparent); }
    .gradient-right { right: 0; background: linear-gradient(to left, #fff, transparent); }
    @keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-100%); } }
    .marquee-track { animation: marquee 60s linear infinite; will-change: transform; }
    @media (prefers-reduced-motion: reduce) { .marquee-track { animation: none; } }
    .font-system { font-family: ui-sans-serif, system-ui, sans-serif; }
    .slider-track { position: absolute; inset: 0; display: flex; width: 100%; height: 100%; animation: slide 12s ease-in-out infinite; }
    .slider-slide { position: relative; flex: 0 0 100%; min-width: 100%; height: 100%; overflow: hidden; }
    @keyframes slide { 0%, 45% { transform: translateX(0); } 55%, 95% { transform: translateX(-100%); } 100% { transform: translateX(0); } }
    .slider-dot { width: 8px; height: 8px; border-radius: 9999px; background: rgba(255,255,255,0.4); transition: background 200ms; }
    .slider-dot[aria-current="true"] { background: #8b1c13; }
    @media (prefers-reduced-motion: reduce) { .slider-track { animation: none; } }
  </style>
</head>
<body class="bg-white text-oss-base-black min-h-screen">
  <!-- Top announcement -->
  <div class="bg-yellow-500">
    <div class="mx-auto max-w-1920 px-4 sm:px-10 md:px-16 lg:px-24 xl:px-32">
      <div class="mx-0 grid grid-flow-row grid-cols-12 items-center gap-4 py-2">
        <div class="col-span-11 flex items-center">
          <span class="material-symbols-outlined" aria-hidden="true">campaign</span>
          <div class="ml-4 text-start text-xs font-semibold">
            <p>Sistem Pengawasan Kepatuhan DPMPTSP Kabupaten Pidie — kelola daftar list sanksi pencabutan &amp; usulan pencabutan perizinan berusaha terintegrasi OCR.</p>
          </div>
        </div>
        <button type="button" aria-label="Tutup pengumuman" class="col-span-1 ml-auto ti ti-x text-lg" onclick="this.closest('.bg-yellow-500').remove()"></button>
      </div>
    </div>
  </div>

  <header class="relative z-50 border-b border-oss-gray-25 bg-oss-gray-25 py-4">
    <div class="mx-auto max-w-[1920px] px-4 sm:px-10 md:px-16 lg:px-24 xl:px-32 flex items-center justify-between">
      <div class="flex items-center space-x-2"><span class="ti ti-device-mobile"></span><div class="text-sm font-normal hover:underline cursor-pointer">SPI DPMPTSP Kabupaten Pidie</div></div>
      <div class="flex items-center space-x-6">
        <span class="text-sm font-semibold text-oss-base-black">ID</span>
        <span class="text-sm font-semibold text-oss-blue-500">Bantuan</span>
      </div>
    </div>
  </header>

  <header class="sticky top-0 z-30 bg-white shadow" style="height:72px">
    <div class="mx-auto max-w-1920 px-4 sm:px-10 md:px-16 lg:px-24 xl:px-32">
      <div class="flex h-[72px] items-center justify-between gap-3">
        <a href="{{ route('welcome') }}" class="flex shrink-0 items-center gap-3" aria-label="SPI DPMPTSP beranda">
          <img src="{{ asset('logo-pidie.svg') }}" alt="Logo Pidie" class="h-9 w-auto" />
          <span class="hidden sm:block text-sm font-bold leading-tight text-oss-base-black">SPI DPMPTSP<br><span class="text-xs font-normal text-oss-gray-500">Kabupaten Pidie</span></span>
        </a>
        <nav aria-label="Navigasi utama" class="hidden lg:flex items-center gap-1">
          <a href="{{ route('welcome') }}" class="px-3 py-2 text-sm font-semibold hover:text-oss-blue-500">Beranda</a>
          <a href="#layanan" class="px-3 py-2 text-sm font-semibold hover:text-oss-blue-500">Layanan</a>
          <a href="#pengawasan" class="px-3 py-2 text-sm font-semibold hover:text-oss-blue-500">Pengawasan</a>
        </nav>
        <div class="flex items-center gap-2">
          @auth
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-5 py-2 text-sm font-semibold rounded-md bg-primary text-white hover:bg-primary-700">Dashboard</a>
          @else
            <a href="{{ route('login') }}" class="inline-flex items-center px-5 py-2 text-sm font-semibold rounded-md bg-primary text-white hover:bg-primary-700">Masuk</a>
          @endauth
        </div>
      </div>
    </div>
  </header>

  <main>
    <!-- Hero -->
    <section aria-labelledby="hero-title" class="relative overflow-hidden bg-[#8b1c13] py-6 lg:py-24">
      <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <img src="{{ asset('guest/assets/images/grid-pattern.svg') }}" alt="" class="h-full w-full object-cover opacity-30" />
        <img src="{{ asset('guest/assets/images/garis-abu.svg') }}" alt="" class="absolute inset-0 h-full w-full object-cover mix-blend-screen opacity-20" />
      </div>
      <div class="relative mx-auto max-w-1920 px-4 sm:px-10 md:px-16 lg:px-24 xl:px-32 flex flex-col lg:block">
        <figure class="flex order-first w-full flex-row items-center gap-4 rounded-2xl bg-white/10 p-4 text-left backdrop-blur-sm ring-1 ring-white/20 mb-4 lg:mx-0 lg:mb-0 lg:mt-0 lg:w-auto lg:max-w-xs lg:flex-col lg:gap-3 lg:p-5 lg:text-center lg:absolute lg:right-6 lg:top-1/2 lg:-translate-y-1/2">
          <img src="{{ asset('guest/assets/images/Kepala_DPMPTSP.jpg') }}" alt="PLT. Kepala DPMPTSP Kabupaten Pidie" width="180" height="180" loading="lazy" class="h-20 w-20 shrink-0 rounded-full object-cover ring-4 ring-white/40 shadow-lg sm:h-24 sm:w-24 lg:h-44 lg:w-44" />
          <figcaption class="text-white leading-tight lg:leading-none">
            <p class="text-base font-bold sm:text-lg lg:text-xl">Sri Rahayu, S.E</p>
            <p class="mt-0.5 text-[11px] font-semibold uppercase tracking-wider text-white/70 lg:mt-1 lg:text-xs">PLT. Kepala DPMPTSP</p>
            <p class="font-system text-sm text-white/80">Kabupaten Pidie</p>
          </figcaption>
        </figure>
        <div class="max-w-3xl order-last lg:order-none">
          <p class="text-sm font-semibold tracking-widest text-white/80">DPMPTSP KABUPATEN PIDIE</p>
          <h1 id="hero-title" class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight text-white">Sistem Pengawasan Kepatuhan<br><span class="text-white/90">Terintegrasi &amp; Akuntabel</span></h1>
          <p class="font-system mt-4 text-sm sm:text-base font-medium text-white/80">Kelola Daftar List Sanksi Pencabutan &amp; Usulan Pencabutan Perizinan Berusaha — OCR PDF, validasi NIB, dan cetak SP1/Rekap langsung.</p>
          <div class="mt-8 flex flex-wrap gap-3">
            @auth
              <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 text-sm font-bold text-[#8b1c13] hover:bg-white/90">Ke Dashboard <span class="ti ti-arrow-right"></span></a>
            @else
              <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 text-sm font-bold text-[#8b1c13] hover:bg-white/90">Masuk <span class="ti ti-arrow-right"></span></a>
            @endauth
            <a href="#layanan" class="inline-flex items-center gap-2 rounded-lg border border-white/30 bg-white/10 px-6 py-3 text-sm font-semibold text-white backdrop-blur hover:bg-white/20">Pelajari Layanan</a>
          </div>
        </div>
      </div>
    </section>

    <div class="mx-auto max-w-1920 px-4 sm:px-10 md:px-16 lg:px-24 xl:px-32">
      <!-- NIB slider + intro -->
      <section id="layanan" class="bg-transparent py-6 md:flex md:items-center md:justify-between">
        <div class="md:w-1/2">
          <div id="nib-slider" class="relative aspect-[16/10] w-full overflow-hidden rounded-xl bg-oss-gray-200" role="region" aria-roledescription="carousel" aria-label="Slider informasi">
            <div class="slider-track absolute inset-0">
              <div class="slider-slide"><img src="{{ asset('guest/assets/images/web-banner.jpg') }}" alt="Pengelolaan usaha" loading="eager" class="absolute inset-0 h-full w-full object-cover" /></div>
              <div class="slider-slide"><img src="{{ asset('guest/assets/images/pidie.jpeg') }}" alt="Pelayanan Terpadu Satu Pintu" loading="eager" class="absolute inset-0 h-full w-full object-cover" /></div>
            </div>
            <div class="absolute bottom-3 left-1/2 z-10 flex -translate-x-1/2 gap-2 rounded-full bg-black/30 px-2 py-1 backdrop-blur-sm" role="tablist"><button type="button" class="slider-dot" aria-current="true" aria-label="Slide 1"></button><button type="button" class="slider-dot" aria-current="false" aria-label="Slide 2"></button></div>
          </div>
        </div>
        <div class="mt-6 md:mt-0 md:w-1/2 md:pl-10">
          <h2 class="text-[28px] md:text-[32px] font-bold leading-tight text-[#8b1c13]">Pengawasan kepatuhan <span class="text-black">yang mudah dipantau</span></h2>
          <p class="mt-3 text-sm leading-relaxed text-gray-600">Impor PDF sanksi, ekstrak otomatis (NIB, alamat, skala, penanaman modal) via OCR, lalu cetak SP1 per data &amp; rekap tabel 6 kolom langsung dari filter — semua terintegrasi di satu dashboard.</p>
          <div class="mt-5 flex flex-wrap gap-2">
            @auth
              <a href="{{ route('sanksi-administratif.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#8b1c13] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#74160f]">Kelola Usulan <span class="ti ti-arrow-right"></span></a>
            @else
              <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#8b1c13] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#74160f]">Masuk <span class="ti ti-arrow-right"></span></a>
            @endauth
            <span class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700">NIB · Skala · Lokasi</span>
          </div>
        </div>
      </section>

      <!-- 4 tingkat risiko -->
      <section class="relative py-10">
        <div class="absolute inset-0 overflow-hidden rounded-xl"><img src="{{ asset('guest/assets/images/spiral-bg.svg') }}" alt="" aria-hidden="true" class="absolute inset-0 h-full w-full object-cover" /></div>
        <div class="relative mx-auto rounded-xl border border-gray-200 bg-white/90 p-6 shadow-sm md:flex md:items-center md:justify-between backdrop-blur">
          <div class="md:w-2/5"><h2 class="text-lg font-bold text-[#8b1c13]">Perizinan Berbasis Risiko — 4 tingkat risiko menentukan sanksi &amp; pencabutan.</h2><p class="mt-2 text-sm text-gray-600">Pantau sebaran Risiko Rendah → Tinggi serta Skala Usaha Mikro → Besar langsung di dashboard analitik.</p></div>
          <div class="mt-4 md:mt-0 md:w-2/5 md:text-right"><a href="{{ route('login') }}" class="inline-block rounded-lg border border-oss-blue-500 bg-white px-5 py-2.5 font-semibold text-oss-blue-500 hover:bg-oss-blue-500 hover:text-white">Masuk Dashboard</a></div>
        </div>
      </section>

      <!-- Fitur -->
      <section id="pengawasan" class="py-8">
        <h2 class="text-lg font-bold md:text-xl">Fitur Utama</h2>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div class="rounded-xl border border-gray-200 p-5"><div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600"><span class="ti ti-list"></span></div><h3 class="mt-3 font-semibold">Daftar List Sanksi Pencabutan</h3><p class="mt-1 text-sm text-gray-500">Kelola pengawasan lengkap dengan risiko &amp; status sanksi.</p></div>
          <div class="rounded-xl border border-gray-200 p-5"><div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><span class="ti ti-scale"></span></div><h3 class="mt-3 font-semibold">Usulan Pencabutan PB</h3><p class="mt-1 text-sm text-gray-500">OCR NIB/alamat terintegrasi.</p></div>
          <div class="rounded-xl border border-gray-200 p-5"><div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-900 text-white"><span class="ti ti-printer"></span></div><h3 class="mt-3 font-semibold">Cetak SP1 &amp; Rekap</h3><p class="mt-1 text-sm text-gray-500">Checklist → stream PDF F4 &amp; landscape 6 kolom.</p></div>
        </div>
      </section>

      <!-- Marquee kementerian -->
      <section aria-label="Logo kementerian" class="my-10">
        <div class="relative flex w-full overflow-x-clip">
          <div class="gradient-left" aria-hidden="true"></div>
          <div class="marquee-track flex min-w-full items-center">
            @foreach (['kemenhukum.svg','kemenbud.svg','kemenkop.svg','kemenpora.svg','kemhan.png','kemensos.svg','kemenkop.svg'] as $logo)
              <img src="{{ asset('guest/assets/logos/' . $logo) }}" alt="" loading="lazy" class="mx-5 h-10 w-auto grayscale opacity-70" />
            @endforeach
          </div>
          <div class="gradient-right" aria-hidden="true"></div>
        </div>
      </section>
    </div>
  </main>

  <footer id="footer" class="bg-[#8b1c13] py-8">
    <div class="mx-auto max-w-1920 px-4 sm:px-10 md:px-16 lg:px-24 xl:px-32">
      <div class="flex flex-col justify-between gap-8 md:flex-row">
        <div class="flex-1 text-center md:text-left">
          <p class="text-sm font-bold text-white">SPI DPMPTSP Kabupaten Pidie</p>
          <p class="mt-2 text-sm text-white/80">Jln. Tgk. Chiek Direubee No.5 Sigli · Telp. (0653) 23399</p>
          <p class="mt-6 text-xs text-white/60">© {{ date('Y') }} DPMPTSP Kabupaten Pidie</p>
        </div>
        <div class="flex flex-col gap-2">
          <div class="text-sm font-bold text-white">Akses Sistem</div>
          @auth
            <a href="{{ route('dashboard') }}" class="text-sm text-white hover:underline">Dashboard</a>
          @else
            <a href="{{ route('login') }}" class="text-sm text-white hover:underline">Masuk</a>
          @endauth
          <a href="{{ url('/guest/index.html') }}" class="text-sm text-white/70 hover:underline">Versi Guest Statis</a>
        </div>
      </div>
    </div>
  </footer>

  <script>
    document.querySelector('.bg-yellow-500 button[aria-label="Tutup pengumuman"]')?.addEventListener('click', (e) => e.currentTarget.closest('.bg-yellow-500').remove());
    (function () {
      const slider = document.getElementById('nib-slider');
      if (!slider) return;
      const track = slider.querySelector('.slider-track');
      const dots = slider.querySelectorAll('.slider-dot');
      let current = 0;
      let timer = null;
      const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      function goTo(i) { current = (i + dots.length) % dots.length; track.style.transform = `translateX(-${current * 100}%)`; dots.forEach((d, idx) => d.setAttribute('aria-current', idx === current ? 'true' : 'false')); }
      function start() { if (reduce) return; stop(); timer = setInterval(() => goTo(current + 1), 4000); }
      function stop() { if (timer) { clearInterval(timer); timer = null; } }
      dots.forEach((dot, idx) => dot.addEventListener('click', () => { goTo(idx); start(); }));
      slider.addEventListener('mouseenter', stop);
      slider.addEventListener('mouseleave', start);
      if ('IntersectionObserver' in window) { const io = new IntersectionObserver((entries) => entries.forEach((e) => e.isIntersecting ? start() : stop()), { threshold: 0.3 }); io.observe(slider); } else start();
    })();
  </script>
</body>
</html>
