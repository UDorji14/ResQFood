/* ═══════════════════════════════════════════════════════════
   ResQFood — main.js
   ═══════════════════════════════════════════════════════════ */

(function () {
  'use strict';
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ─── Navbar scroll state ─── */
  const nav = document.querySelector('.site-nav');
  if (nav) {
    const onScroll = () => {
      nav.classList.toggle('scrolled', window.scrollY > 20);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ─── Mobile menu ─── */
  const hamburger = document.querySelector('.hamburger');
  const drawer    = document.querySelector('.nav-drawer');
  if (hamburger && drawer) {
    hamburger.addEventListener('click', () => {
      const open = hamburger.classList.toggle('is-open');
      drawer.classList.toggle('is-open', open);
      hamburger.setAttribute('aria-expanded', open);
      document.body.style.overflow = open ? 'hidden' : '';
    });
    // close on nav link click
    drawer.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        hamburger.classList.remove('is-open');
        drawer.classList.remove('is-open');
        hamburger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      });
    });
  }

  /* ─── Smooth scroll for anchor links ─── */
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const id = a.getAttribute('href');
      if (id === '#') return;
      const target = document.querySelector(id);
      if (!target) return;
      e.preventDefault();
      const offset = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--nav-h')) || 72;
      const top = target.getBoundingClientRect().top + window.scrollY - offset;
      window.scrollTo({ top, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
    });
  });

  /* ─── Scroll reveal ─── */
  const revealEls = document.querySelectorAll('[data-reveal]');
  if (revealEls.length && 'IntersectionObserver' in window) {
    const revealObs = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');

        // Trigger counter if inside this element
        triggerCounterInEl(entry.target);

        // Trigger rings if inside this element
        triggerRingsInEl(entry.target);

        revealObs.unobserve(entry.target);
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

    revealEls.forEach(el => revealObs.observe(el));
  } else {
    // Fallback: show all
    revealEls.forEach(el => el.classList.add('is-visible'));
  }

  /* ─── Counter animation ─── */
  function animateCounter(el) {
    const target  = parseInt(el.dataset.target, 10);
    const dur     = 1800;
    const start   = performance.now();
    const isHero  = el.classList.contains('counter-hero');
    const step = now => {
      const t = Math.min((now - start) / dur, 1);
      // easeOutExpo
      const ease = t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
      const val = Math.round(ease * target);
      el.textContent = val.toLocaleString();
      if (t < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  }

  function triggerCounterInEl(el) {
    const counters = el.classList.contains('counter') || el.classList.contains('counter-hero')
      ? [el]
      : Array.from(el.querySelectorAll('.counter, .counter-hero'));
    counters.forEach(c => {
      if (!c.dataset.animated) {
        c.dataset.animated = '1';
        animateCounter(c);
      }
    });
  }

  // Also observe standalone counters not inside data-reveal
  document.querySelectorAll('.counter, .counter-hero').forEach(el => {
    if (el.closest('[data-reveal]')) return; // handled above
    const obs = new IntersectionObserver(entries => {
      if (entries[0].isIntersecting && !el.dataset.animated) {
        el.dataset.animated = '1';
        animateCounter(el);
        obs.disconnect();
      }
    }, { threshold: 0.3 });
    obs.observe(el);
  });

  /* ─── Ring arc animation ─── */
  function animateRing(ringEl) {
    const pct  = parseFloat(ringEl.dataset.pct || 0) / 100;
    const r    = parseFloat(ringEl.getAttribute('r') || 58);
    const circ = 2 * Math.PI * r;
    ringEl.style.strokeDasharray  = circ;
    ringEl.style.strokeDashoffset = circ;
    // Force layout
    ringEl.getBoundingClientRect();
    ringEl.style.strokeDashoffset = circ * (1 - pct);
  }

  function triggerRingsInEl(el) {
    const arcs = el.classList.contains('ring-arc')
      ? [el]
      : Array.from(el.querySelectorAll('.ring-arc'));
    arcs.forEach(arc => {
      if (!arc.dataset.animated) {
        arc.dataset.animated = '1';
        animateRing(arc);
      }
    });
  }

  // Observe rings not inside data-reveal
  document.querySelectorAll('.ring-arc').forEach(arc => {
    if (arc.closest('[data-reveal]')) return;
    // Initialize as hidden
    const r = parseFloat(arc.getAttribute('r') || 58);
    const circ = 2 * Math.PI * r;
    arc.style.strokeDasharray  = circ;
    arc.style.strokeDashoffset = circ;
    const obs = new IntersectionObserver(entries => {
      if (entries[0].isIntersecting && !arc.dataset.animated) {
        arc.dataset.animated = '1';
        animateRing(arc);
        obs.disconnect();
      }
    }, { threshold: 0.3 });
    obs.observe(arc);
  });

  /* ─── Journey timeline line fill ─── */
  const journeyWrap = document.querySelector('.journey-wrap');
  const journeyProg = document.getElementById('journey-progress');
  if (journeyWrap && journeyProg) {
    function updateJourneyLine() {
      const rect = journeyWrap.getBoundingClientRect();
      const wh   = window.innerHeight;
      // Progress: 0 when section top at viewport bottom, 1 when section bottom at viewport top
      const pct  = Math.max(0, Math.min(1, (wh - rect.top) / (rect.height + wh * 0.4)));
      journeyProg.style.height = (pct * 100) + '%';
    }
    window.addEventListener('scroll', updateJourneyLine, { passive: true });
    updateJourneyLine();
  }

  /* ─── Subtle parallax on hero main image ─── */
  const heroImgMain = document.querySelector('.hero-img-main');
  const isMobileVP  = () => window.matchMedia('(max-width: 680px)').matches;

  if (heroImgMain && !isMobileVP()) {
    function onHeroParallax() {
      heroImgMain.style.transform = `translateY(${window.scrollY * 0.14}px)`;
    }
    window.addEventListener('scroll', onHeroParallax, { passive: true });
  }

  /* ─── Ecosystem carousel: pause on hover + touch ─── */
  (function initEcoCarousel() {
    const track    = document.getElementById('eco-track');
    const carousel = track && track.closest('.eco-carousel');
    if (!track || !carousel) return;

    // Respect prefers-reduced-motion (CSS already handles via animation:none)
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    let touchTimer = null;
    const RESUME_DELAY = 2400; // ms after touch before resuming

    function pause() {
      track.classList.add('is-paused');
    }
    function resume() {
      track.classList.remove('is-paused');
    }

    // Hover — desktop only (carousel CSS already handles :hover, JS is extra insurance)
    carousel.addEventListener('mouseenter', pause);
    carousel.addEventListener('mouseleave', resume);

    // Touch — pause on touch start, auto-resume after brief delay
    carousel.addEventListener('touchstart', () => {
      pause();
      clearTimeout(touchTimer);
    }, { passive: true });

    carousel.addEventListener('touchend', () => {
      clearTimeout(touchTimer);
      touchTimer = setTimeout(resume, RESUME_DELAY);
    }, { passive: true });

    // Visibility API: pause when tab is not visible (performance)
    document.addEventListener('visibilitychange', () => {
      document.hidden ? pause() : resume();
    });
  })();

  /* ─── Parcel Journey: scroll-driven state switching ─── */
  (function initParcelJourney() {
    const journey  = document.getElementById('parcel-journey');
    if (!journey) return;

    // On mobile the sticky effect is disabled — only track for reveal
    const isMobile = () => window.matchMedia('(max-width: 680px)').matches;

    const sections = journey.querySelectorAll('.pj-section[data-state]');

    // Set initial section as active
    journey.dataset.parcelState = 's1';

    if (!('IntersectionObserver' in window)) return;

    // Each section triggers a state change when it crosses 45% of the viewport
    const stateObs = new IntersectionObserver(entries => {
      if (isMobile()) return; // Skip state switching on mobile
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const state = entry.target.dataset.state;
          if (state && journey.dataset.parcelState !== state) {
            journey.dataset.parcelState = state;
            // Briefly add a "transitioning" class for extra polish
            journey.classList.add('parcel-transitioning');
            setTimeout(() => journey.classList.remove('parcel-transitioning'), 800);
          }
        }
      });
    }, {
      threshold: 0.45,
      rootMargin: '0px 0px -10% 0px'
    });

    sections.forEach(s => stateObs.observe(s));

    // Also trigger data-reveal inside pj-sections (they use the same observer
    // as the rest of the page, but just in case they were missed, re-observe them)
    const pjRevealEls = journey.querySelectorAll('[data-reveal]');
    if (pjRevealEls.length && 'IntersectionObserver' in window) {
      const pjRevealObs = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (!entry.isIntersecting) return;
          entry.target.classList.add('is-visible');
          pjRevealObs.unobserve(entry.target);
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -30px 0px' });
      pjRevealEls.forEach(el => {
        // Only observe if not already visible (may be caught by main observer)
        if (!el.classList.contains('is-visible')) pjRevealObs.observe(el);
      });
    }

    // Scroll progress: smoothly interpolate parcel vertical nudge within sticky col
    // This creates an extra layer of "traveling" feel beyond the CSS state transitions
    const parcelWrap = document.getElementById('pj-parcel-wrap');
    if (parcelWrap && !isMobile()) {
      let ticking = false;
      function onJourneyScroll() {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(() => {
          const rect   = journey.getBoundingClientRect();
          const totalH = journey.offsetHeight - window.innerHeight;
          const prog   = Math.max(0, Math.min(1, -rect.top / Math.max(1, totalH)));
          // Translate the parcel stage slightly for a smooth glide feel
          const nudgeY = prog * 28; // up to 28px downward nudge
          const stage  = document.getElementById('pj-parcel-stage');
          if (stage) stage.style.transform = `translateY(${nudgeY}px)`;
          ticking = false;
        });
      }
      window.addEventListener('scroll', onJourneyScroll, { passive: true });
      onJourneyScroll();
    }
  })();

})();
