/**
 * ResQFood — Application JavaScript
 * ────────────────────────────────────
 * Modular, vanilla JS. No dependencies.
 * Sections:
 *   1. Mobile Navigation
 *   2. Flash Message Dismissal
 *   3. Profile Tab System
 *   4. Progress Bar Animation
 *   5. Form Enhancements
 *   6. Table Responsiveness
 *   7. General UI Polish
 */

(function () {
    'use strict';

    /* ── Utilities ──────────────────────────────────────────── */
    function $(sel, ctx) { return (ctx || document).querySelector(sel); }
    function $$(sel, ctx) { return [...(ctx || document).querySelectorAll(sel)]; }
    function on(el, evt, fn, opts) { el?.addEventListener(evt, fn, opts); }


    /* ══════════════════════════════════════════════════════════
       1. Mobile Navigation
    ══════════════════════════════════════════════════════════ */
    (function initMobileNav() {
        const hamburger = $('#nav-hamburger');
        const drawer    = $('#nav-drawer');
        const backdrop  = $('#nav-backdrop');

        if (!hamburger || !drawer) return;

        let isOpen = false;

        function openNav() {
            isOpen = true;
            hamburger.classList.add('is-open');
            drawer.classList.add('is-open');
            backdrop?.classList.add('is-open');
            hamburger.setAttribute('aria-expanded', 'true');
            drawer.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeNav() {
            isOpen = false;
            hamburger.classList.remove('is-open');
            drawer.classList.remove('is-open');
            backdrop?.classList.remove('is-open');
            hamburger.setAttribute('aria-expanded', 'false');
            drawer.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        on(hamburger, 'click', () => isOpen ? closeNav() : openNav());
        on(backdrop,  'click', closeNav);

        // Close on Escape
        on(document, 'keydown', e => {
            if (e.key === 'Escape' && isOpen) closeNav();
        });

        // Close when a drawer link is clicked
        $$('.nav-drawer__links a').forEach(link => {
            on(link, 'click', closeNav);
        });

        // Close if viewport resizes above mobile breakpoint
        const mq = window.matchMedia('(min-width: 769px)');
        on(mq, 'change', e => { if (e.matches && isOpen) closeNav(); });
    })();


    /* ══════════════════════════════════════════════════════════
       2. Flash Message Dismissal
    ══════════════════════════════════════════════════════════ */
    (function initFlashMessages() {
        // Add close buttons to existing flashes
        $$('.flash').forEach(flash => {
            // Skip if already has close button
            if (flash.querySelector('.flash__close')) return;

            const btn = document.createElement('button');
            btn.className = 'flash__close';
            btn.setAttribute('aria-label', 'Dismiss message');
            btn.innerHTML = '&#x2715;';
            flash.appendChild(btn);

            on(btn, 'click', () => dismissFlash(flash));
        });

        // Auto-dismiss after 6 seconds (success/info only)
        $$('.flash--success, .flash--info').forEach(flash => {
            setTimeout(() => dismissFlash(flash), 6000);
        });

        function dismissFlash(el) {
            el.style.transition = 'opacity 300ms ease, transform 300ms ease, max-height 300ms ease, margin 300ms ease, padding 300ms ease';
            el.style.opacity    = '0';
            el.style.transform  = 'translateY(-4px)';
            el.style.maxHeight  = el.offsetHeight + 'px';
            requestAnimationFrame(() => {
                el.style.maxHeight = '0';
                el.style.margin    = '0';
                el.style.padding   = '0';
                el.style.border    = 'none';
            });
            setTimeout(() => el.remove(), 350);
        }
    })();


    /* ══════════════════════════════════════════════════════════
       3. Profile Tab System
    ══════════════════════════════════════════════════════════ */
    (function initProfileTabs() {
        $$('[data-tab]').forEach(btn => {
            on(btn, 'click', function () {
                const targetId = this.dataset.tab;
                const container = this.closest('.profile-layout, [data-tabs]') || document;

                // Deactivate all tabs and sections in this container
                $$(`.profile-tab, [data-tab]`, container).forEach(t => t.classList.remove('active'));
                $$('.profile-section', container).forEach(s => s.classList.remove('active'));

                // Activate clicked tab
                this.classList.add('active');

                // Activate target section
                const section = $(`#${targetId}`, container);
                if (section) section.classList.add('active');

                // Update URL hash without scrolling
                if (history.replaceState) {
                    history.replaceState(null, '', '#' + targetId);
                }
            });
        });

        // Restore tab from URL hash on page load
        const hash = location.hash.slice(1);
        if (hash) {
            const tabBtn = $(`[data-tab="${hash}"]`);
            if (tabBtn) tabBtn.click();
        }
    })();


    /* ══════════════════════════════════════════════════════════
       4. Progress Bar Animation
    ══════════════════════════════════════════════════════════ */
    (function initProgressBars() {
        // Animated progress bars: set width from data-pct on load
        $$('[data-pct]').forEach(fill => {
            const pct = parseFloat(fill.dataset.pct) || 0;
            // Set initial width to 0, then animate to target
            fill.style.width = '0%';
            requestAnimationFrame(() => {
                setTimeout(() => {
                    fill.style.width = Math.min(100, pct) + '%';
                }, 100);
            });
        });

        // Intersection observer for off-screen bars
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const fill = entry.target.querySelector('[data-pct]');
                        if (fill) {
                            fill.style.width = Math.min(100, parseFloat(fill.dataset.pct) || 0) + '%';
                        }
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.2 });

            $$('.progress-bar, .impact-bar-track').forEach(bar => observer.observe(bar));
        }
    })();


    /* ══════════════════════════════════════════════════════════
       5. Form Enhancements
    ══════════════════════════════════════════════════════════ */
    (function initForms() {
        // Confirm dialogs via data-confirm attribute
        $$('[data-confirm]').forEach(el => {
            const evt = el.tagName === 'FORM' ? 'submit' : 'click';
            on(el, evt, function (e) {
                const msg = this.dataset.confirm || 'Are you sure?';
                if (!confirm(msg)) e.preventDefault();
            });
        });

        // Character counter for textareas with data-maxlength
        $$('textarea[data-maxlength]').forEach(ta => {
            const max = parseInt(ta.dataset.maxlength, 10);
            if (!max) return;

            const counter = document.createElement('span');
            counter.className = 'form-hint';
            counter.style.textAlign = 'right';
            ta.insertAdjacentElement('afterend', counter);

            function update() {
                const left = max - ta.value.length;
                counter.textContent = `${left} character${left !== 1 ? 's' : ''} remaining`;
                counter.style.color = left < 20 ? 'var(--terra)' : '';
            }
            on(ta, 'input', update);
            update();
        });

        // Datetime-local min value = now (prevent past dates on create forms)
        $$('input[type="datetime-local"][data-future]').forEach(input => {
            if (!input.value) {
                const now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                input.min = now.toISOString().slice(0, 16);
            }
        });

        // File input preview
        $$('input[type="file"][data-preview]').forEach(input => {
            const previewId = input.dataset.preview;
            const preview   = previewId ? document.getElementById(previewId) : null;
            if (!preview) return;

            on(input, 'change', function () {
                const file = this.files[0];
                if (!file || !file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = e => {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            });
        });
    })();


    /* ══════════════════════════════════════════════════════════
       6. Table Responsiveness
    ══════════════════════════════════════════════════════════ */
    (function initTables() {
        // Show/hide horizontal scroll indicator
        $$('.table-wrapper').forEach(wrapper => {
            function checkScroll() {
                const scrollable = wrapper.scrollWidth > wrapper.clientWidth;
                wrapper.classList.toggle('is-scrollable', scrollable);
                if (scrollable) {
                    const atEnd = wrapper.scrollLeft + wrapper.clientWidth >= wrapper.scrollWidth - 4;
                    wrapper.classList.toggle('at-scroll-end', atEnd);
                }
            }
            checkScroll();
            on(wrapper, 'scroll', checkScroll, { passive: true });
            on(window, 'resize', checkScroll, { passive: true });
        });
    })();


    /* ══════════════════════════════════════════════════════════
       7. General UI Polish
    ══════════════════════════════════════════════════════════ */
    (function initUI() {
        // Smooth scroll-reveal for stat cards and action cards
        if ('IntersectionObserver' in window) {
            const revealObserver = new IntersectionObserver(entries => {
                entries.forEach((entry, i) => {
                    if (entry.isIntersecting) {
                        const el = entry.target;
                        el.style.transitionDelay = (i * 40) + 'ms';
                        el.classList.add('is-revealed');
                        revealObserver.unobserve(el);
                    }
                });
            }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });

            $$('.stat-card, .action-card, .listing-card').forEach(el => {
                el.style.opacity  = '0';
                el.style.transform = 'translateY(10px)';
                el.style.transition = 'opacity 350ms ease, transform 350ms ease';
                revealObserver.observe(el);
            });
        }

        // Add .is-revealed class that triggers the visible state
        document.addEventListener('animationend', () => {}, { once: true });

        // is-revealed toggled by observer above
        const styleTag = document.createElement('style');
        styleTag.textContent = '.is-revealed { opacity: 1 !important; transform: none !important; }';
        document.head.appendChild(styleTag);

        // Auto-resize textarea to fit content
        $$('textarea.auto-resize').forEach(ta => {
            function resize() {
                ta.style.height = 'auto';
                ta.style.height = ta.scrollHeight + 'px';
            }
            on(ta, 'input', resize);
            resize();
        });

        // Tooltip via title attribute enhancement
        $$('[data-tooltip]').forEach(el => {
            on(el, 'mouseenter', function () {
                const tip = document.createElement('div');
                tip.className = 'tooltip';
                tip.textContent = this.dataset.tooltip;
                Object.assign(tip.style, {
                    position: 'absolute',
                    background: 'rgba(26,37,24,0.9)',
                    color: '#fff',
                    fontSize: '0.75rem',
                    padding: '0.3rem 0.65rem',
                    borderRadius: '6px',
                    pointerEvents: 'none',
                    zIndex: '9999',
                    whiteSpace: 'nowrap',
                    transform: 'translateY(-4px)',
                    transition: 'opacity 150ms',
                });
                document.body.appendChild(tip);

                const rect = this.getBoundingClientRect();
                tip.style.top  = (rect.top + window.scrollY - tip.offsetHeight - 8) + 'px';
                tip.style.left = (rect.left + window.scrollX + rect.width / 2 - tip.offsetWidth / 2) + 'px';

                this._tooltip = tip;
            });

            on(el, 'mouseleave', function () {
                this._tooltip?.remove();
                delete this._tooltip;
            });
        });

        // Copy-to-clipboard for pickup codes
        $$('.pickup-code[data-copy]').forEach(el => {
            el.style.cursor = 'pointer';
            el.title = 'Click to copy';
            on(el, 'click', function () {
                navigator.clipboard?.writeText(this.textContent.trim()).then(() => {
                    const orig = this.textContent;
                    this.textContent = 'Copied!';
                    setTimeout(() => { this.textContent = orig; }, 1500);
                });
            });
        });

        // Sticky table header enhancement via CSS class
        $$('.table-sticky thead').forEach(thead => {
            thead.querySelectorAll('th').forEach(th => {
                th.style.position = 'sticky';
                th.style.top = '0';
                th.style.zIndex = '2';
            });
        });
    })();


    /* ══════════════════════════════════════════════════════════
       Init complete
    ══════════════════════════════════════════════════════════ */
    console.debug('[ResQFood] app.js loaded ✓');

})();
