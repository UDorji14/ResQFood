<?php
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ResQFood - Turn Surplus Into Community Value</title>
    <meta name="description" content="ResQFood connects food businesses, local users, and charities to coordinate surplus food redistribution through structured listings, reservations, and pickup tracking.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,700;1,9..144,500;1,9..144,600&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>

<!-- ════ NAVIGATION ════ -->
<header class="site-nav" id="top">
    <nav class="nav-inner container" aria-label="Main navigation">
        <a href="#top" class="nav-logo" aria-label="Home">
            <span class="logo-mark" aria-hidden="true">
                <?php if (setting('logo_path')): ?>
                    <img src="<?= url(setting('logo_path')) ?>" alt="Logo" style="width:34px;height:34px;object-fit:contain">
                <?php else: ?>
                    <svg viewBox="0 0 36 36" width="34" height="34">
                        <circle cx="18" cy="18" r="16" fill="currentColor" opacity=".12"/>
                        <path d="M10 20c0-5 4-9 9-9h4c0 5-4 9-9 9h-4Z" fill="currentColor"/>
                        <path d="M11 22h6c5 0 9 4 9 9h-6c-5 0-9-4-9-9Z" fill="currentColor" opacity=".5"/>
                    </svg>
                <?php endif; ?>
            </span>
            <strong><?= e(setting('site_name', 'ResQFood')) ?></strong>
        </a>
        <button class="hamburger" type="button" aria-expanded="false" aria-controls="nav-drawer" aria-label="Open menu">
            <span></span><span></span><span></span>
        </button>
        <div class="nav-drawer" id="nav-drawer">
            <ul class="nav-links" role="list">
                <li><a href="<?= url('about.php') ?>">About Us</a></li>
                <li><a href="<?= url('contact.php') ?>">Contact Us</a></li>
                <li><a href="<?= url('privacy.php') ?>">Privacy Policy</a></li>
            </ul>
            <div class="nav-ctas">
                <a href="<?= url('login.php') ?>" class="btn btn-ghost btn-sm">Log In</a>
                <a href="<?= url('register.php') ?>" class="btn btn-primary btn-sm">Get Started</a>
            </div>
        </div>
    </nav>
</header>


<!-- ════ HERO — Editorial image-led opening ════ -->
<section class="hero" id="home" aria-label="Introduction">

    <!-- Large editorial photo: right side, angled left edge -->
    <div class="hero-img-main" aria-hidden="true">
        <img src="https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=1400&q=80" alt="" class="hero-main-img">
    </div>

    <!-- Floating stat chip -->
    <div class="hero-stat-chip h-enter h-enter-6" aria-hidden="true">
        <strong>1,240+</strong>
        <span>meals rescued</span>
    </div>

    <!-- Editorial copy -->
    <div class="hero-body container">
        <div class="hero-copy">
            <div class="hero-eyebrow h-enter h-enter-1">
                <span class="tag-dot"></span>
                Surplus Food Redistribution
            </div>
            <h1 class="hero-h1 h-enter h-enter-2">
                Surplus food,<br><em>shared with</em><br>purpose.
            </h1>
            <p class="hero-sub h-enter h-enter-3">Connecting food businesses, communities, and charities - so good food reaches people before it goes to waste.</p>
            <div class="hero-ctas h-enter h-enter-4">
                <a href="<?= url('register.php') ?>" class="btn btn-primary btn-lg">Get Started</a>
                <a href="#how-it-works" class="btn btn-outline btn-lg">How It Works</a>
            </div>
            <div class="hero-roles h-enter h-enter-5">
                <span class="role-pip role-pip--olive"></span>
                <span>Businesses</span>
                <span class="role-sep"></span>
                <span class="role-pip role-pip--sage"></span>
                <span>Communities</span>
                <span class="role-sep"></span>
                <span class="role-pip role-pip--terra"></span>
                <span>Charities</span>
            </div>
        </div>
    </div>

    <!-- Wave: blends into next section -->
    <div class="hero-wave" aria-hidden="true">
        <svg viewBox="0 0 1440 120" preserveAspectRatio="none">
            <path d="M0 60C240 100 480 120 720 100C960 80 1200 30 1440 60V120H0Z" fill="#f0e8d4"/>
        </svg>
    </div>

</section>


<!-- ════ SHIFT — Transformation story ════ -->
<section class="shift" id="about" aria-label="The transformation">

    <div class="shift-before">
        <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=80" alt="Surplus food before coordination" class="shift-photo" loading="lazy">
        <div class="shift-overlay">
            <div class="shift-content" data-reveal>
                <span class="shift-eyebrow shift-eyebrow--bad">The gap today</span>
                <h2 class="shift-h">Good food wasted<br><em>every day.</em></h2>
                <p>Businesses end each day with edible surplus and no structured way to share it. Without a system, food disappears into waste.</p>
                <div class="shift-chips">
                    <span class="shift-chip shift-chip--bad"><svg viewBox="0 0 14 14" width="12"><path d="M7 1L2 13h10Z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M7 6v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>No coordination</span>
                    <span class="shift-chip shift-chip--bad"><svg viewBox="0 0 14 14" width="12"><circle cx="7" cy="7" r="5.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M5 5l4 4M9 5L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>Food wasted daily</span>
                </div>
            </div>
        </div>
    </div>

    <div class="shift-pivot" aria-hidden="true">
        <div class="shift-pivot-inner">
            <svg viewBox="0 0 44 44" width="44">
                <circle cx="22" cy="22" r="20" fill="#4a6741"/>
                <path d="M12 22H32M22 14l8 8-8 8" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            </svg>
        </div>
    </div>

    <div class="shift-after">
        <img src="https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=900&q=80" alt="Community volunteers collecting surplus food" class="shift-photo" loading="lazy">
        <div class="shift-overlay shift-overlay--light">
            <div class="shift-content" data-reveal data-reveal-delay="200">
                <span class="shift-eyebrow shift-eyebrow--good">With ResQFood</span>
                <h2 class="shift-h">Surplus claimed<br><em>before waste happens.</em></h2>
                <p>Businesses list in minutes. Users and charities discover, reserve, and collect locally. Every step is tracked.</p>
                <div class="shift-chips">
                    <span class="shift-chip shift-chip--good"><svg viewBox="0 0 14 14" width="12"><circle cx="7" cy="7" r="5.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M4.5 7l2 2 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none"/></svg>Listed in minutes</span>
                    <span class="shift-chip shift-chip--good"><svg viewBox="0 0 14 14" width="12"><circle cx="7" cy="7" r="5.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M4.5 7l2 2 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none"/></svg>Reserved and collected</span>
                </div>
            </div>
        </div>
    </div>

</section>


<!-- ════ JOURNEY — Process visualization ════ -->
<section class="journey" id="how-it-works" aria-labelledby="journey-h">
    <div class="container">
        <div class="section-head centered" data-reveal>
            <span class="overline">How it works</span>
            <h2 id="journey-h">From surplus to pickup,<br>every step is clear.</h2>
            <p class="section-sub">A consistent, trackable process designed for businesses, community users, and charities.</p>
        </div>
    </div>

    <div class="journey-wrap">
        <div class="journey-line" aria-hidden="true">
            <div class="journey-progress" id="journey-progress"></div>
        </div>

        <div class="jstep jstep--l" data-reveal>
            <div class="jstep-body">
                <span class="jstep-num">01</span>
                <h3>Business posts surplus food</h3>
                <p>Restaurants, bakeries, and cafes publish a listing with quantity, pickup window, and instructions - in minutes.</p>
            </div>
            <div class="jstep-mid">
                <div class="jstep-icon">
                    <svg viewBox="0 0 40 40" aria-hidden="true"><rect x="10" y="7" width="20" height="26" rx="5" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M15 13h10M13 18h14M13 23h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </div>
            </div>
            <div class="jstep-gap"></div>
        </div>

        <div class="jstep jstep--r" data-reveal data-reveal-delay="100">
            <div class="jstep-gap"></div>
            <div class="jstep-mid">
                <div class="jstep-icon">
                    <svg viewBox="0 0 40 40" aria-hidden="true"><circle cx="17" cy="18" r="7" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M22 23l6 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/><circle cx="17" cy="18" r="3" fill="currentColor" opacity=".3"/></svg>
                </div>
            </div>
            <div class="jstep-body jstep-body--r">
                <span class="jstep-num">02</span>
                <h3>Listings appear to nearby users</h3>
                <p>Local users and charities discover what is available, filtered by proximity and pickup window.</p>
            </div>
        </div>

        <div class="jstep jstep--l" data-reveal data-reveal-delay="200">
            <div class="jstep-body">
                <span class="jstep-num">03</span>
                <h3>Reservation confirmed instantly</h3>
                <p>A single action reserves the listing. Status updates across the platform - preventing double-bookings.</p>
            </div>
            <div class="jstep-mid">
                <div class="jstep-icon">
                    <svg viewBox="0 0 40 40" aria-hidden="true"><rect x="8" y="10" width="24" height="22" rx="6" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M15 9v3M25 9v3" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/><path d="M13 23l3.5 3.5 10-9" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
            </div>
            <div class="jstep-gap"></div>
        </div>

        <div class="jstep jstep--r" data-reveal data-reveal-delay="300">
            <div class="jstep-gap"></div>
            <div class="jstep-mid">
                <div class="jstep-icon">
                    <svg viewBox="0 0 40 40" aria-hidden="true"><rect x="6" y="18" width="20" height="14" rx="4" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M12 32l-2 4M22 32l2 4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/><rect x="22" y="22" width="12" height="8" rx="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M28 18v4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
                </div>
            </div>
            <div class="jstep-body jstep-body--r">
                <span class="jstep-num">04</span>
                <h3>Food collected at the pickup window</h3>
                <p>The collector arrives within the agreed window. Pickup is confirmed and the listing closes automatically.</p>
            </div>
        </div>

        <div class="jstep jstep--l jstep--final" data-reveal data-reveal-delay="400">
            <div class="jstep-body">
                <span class="jstep-num">05</span>
                <h3>Impact recorded on the platform</h3>
                <p>Every completed pickup contributes to a growing record of meals saved and environmental impact, visible across the platform.</p>
            </div>
            <div class="jstep-mid">
                <div class="jstep-icon jstep-icon--impact">
                    <svg viewBox="0 0 40 40" aria-hidden="true"><path d="M8 28l7-9 5 5 7-9 5 7" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 34h28" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
                </div>
            </div>
            <div class="jstep-gap"></div>
        </div>

    </div>
</section>


<!-- ════ SHOWCASE — Layered product reveal ════ -->
<section class="showcase" aria-label="Platform showcase">

    <div class="container showcase-head" data-reveal>
        <span class="overline">The platform</span>
        <h2>Designed for real kitchens,<br>real communities, real time.</h2>
        <p class="section-sub">Every participant - business, collector, administrator - gets a clear, structured experience built around their specific role.</p>
    </div>

    <div class="showcase-stage">

        <div class="sc-screen sc-screen--left" data-reveal data-reveal-delay="150">
            <div class="sc-head sc-head--olive">
                <span>Reservation</span>
            </div>
            <div class="sc-res-body">
                <div class="sc-res-row"><span>Item</span><strong>Fresh croissants &amp; rolls</strong></div>
                <div class="sc-res-row"><span>Business</span><strong>Green Valley Bakery</strong></div>
                <div class="sc-res-row"><span>Window</span><strong>14:00 – 17:00 today</strong></div>
                <div class="sc-res-row"><span>Quantity</span><strong>28 portions</strong></div>
                <div class="sc-res-confirm">
                    <svg viewBox="0 0 16 16" width="14"><circle cx="8" cy="8" r="7" fill="#4a6741" opacity=".15"/><path d="M4.5 8l2.5 2.5 4.5-4.5" stroke="#4a6741" stroke-width="2" stroke-linecap="round" fill="none"/></svg>
                    Reservation confirmed
                </div>
            </div>
        </div>

        <div class="sc-screen sc-screen--main" data-reveal>
            <div class="sc-head">
                <span class="sc-live"><span class="live-dot"></span>Live listings</span>
                <span class="sc-filter">Today · Nearby</span>
            </div>
            <?php
            $sc_items = [
                ['photo' => 'photo-1509440159596-0249088772ff', 'tag' => 'Available', 'cls' => 'open',    'title' => 'Fresh croissants &amp; bread rolls',    'biz' => 'Green Valley Bakery',    'qty' => '28 portions',  'time' => '14:00 – 17:00'],
                ['photo' => 'photo-1542838132-92c53300491e',  'tag' => 'Reserved',  'cls' => 'claimed', 'title' => 'Organic vegetable selection',         'biz' => 'Harvest Street Market', 'qty' => '14 packs',     'time' => '16:30 – 18:00'],
                ['photo' => 'photo-1504674900247-0877df9cc836','tag' => 'Available', 'cls' => 'open',    'title' => 'Mixed pastries &amp; fruit portions', 'biz' => 'Riverside Cafe',        'qty' => '20 items',     'time' => '18:00 – 19:30'],
            ];
            foreach ($sc_items as $item):
            ?>
            <div class="sc-card sc-card--<?= $item['cls'] ?>">
                <div class="sc-card-img">
                    <img src="https://images.unsplash.com/<?= $item['photo'] ?>?auto=format&fit=crop&w=200&q=75" alt="" loading="lazy">
                </div>
                <div class="sc-card-body">
                    <span class="sc-tag sc-tag--<?= $item['cls'] ?>"><?= $item['tag'] ?></span>
                    <strong><?= $item['title'] ?></strong>
                    <span class="sc-biz"><?= $item['biz'] ?> · <?= $item['qty'] ?></span>
                    <div class="sc-time"><?= $item['time'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="sc-screen sc-screen--right" data-reveal data-reveal-delay="300">
            <div class="sc-head sc-head--dark">
                <span>Impact overview</span>
            </div>
            <div class="sc-dash-body">
                <?php
                $metrics = [
                    ['n' => '1,240', 'l' => 'Meals saved',          'w' => '88', 'c' => ''],
                    ['n' => '386',   'l' => 'Listings posted',       'w' => '64', 'c' => '--amber'],
                    ['n' => '295',   'l' => 'Pickups completed',     'w' => '76', 'c' => '--terra'],
                ];
                foreach ($metrics as $m):
                ?>
                <div class="sc-metric">
                    <strong><?= $m['n'] ?></strong>
                    <span><?= $m['l'] ?></span>
                    <div class="sc-bar"><div class="sc-bar-fill<?= $m['c'] ? ' sc-bar-fill'.$m['c'] : '' ?>" style="--w:<?= $m['w'] ?>%"></div></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <div class="showcase-captions container">
        <div class="sc-cap" data-reveal><span>Browse live listings</span></div>
        <div class="sc-cap" data-reveal data-reveal-delay="100"><span>One-click reservations</span></div>
        <div class="sc-cap" data-reveal data-reveal-delay="200"><span>Impact tracked in real time</span></div>
    </div>

</section>


<!-- ════════════════════════════════════════════════════════════
     PARCEL JOURNEY — 3 connected sections with one shared visual
     One premium food parcel travels through: Surplus → Platform → Impact
════════════════════════════════════════════════════════════ -->
<div class="parcel-journey" id="parcel-journey" data-parcel-state="s1" aria-label="The story of one food parcel">

    <!-- ── STICKY PARCEL COLUMN ── -->
    <div class="pj-left" aria-hidden="true">
        <div class="pj-parcel-stage" id="pj-parcel-stage">

            <!-- S1 overlay: floating value tags -->
            <div class="pj-state-layer pj-sl-s1">
                <div class="pj-bg-glow pj-glow--warm"></div>
                <span class="pj-ftag pj-ftag-1">
                    <svg viewBox="0 0 12 12" width="10"><circle cx="6" cy="6" r="5" fill="none" stroke="currentColor" stroke-width="1.5"/><circle cx="6" cy="6" r="2" fill="currentColor"/></svg>
                    Freshly prepared
                </span>
                <span class="pj-ftag pj-ftag-2">
                    <svg viewBox="0 0 12 12" width="10"><circle cx="6" cy="6" r="4.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M6 3.5v2.5l1.5 1.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none"/></svg>
                    Available today
                </span>
                <span class="pj-ftag pj-ftag-3">
                    <svg viewBox="0 0 12 12" width="10"><path d="M6 1C3.8 1 2 2.8 2 5c0 3.2 4 6 4 6s4-2.8 4-6C10 2.8 8.2 1 6 1Z" fill="currentColor" opacity=".3"/><circle cx="6" cy="5" r="1.5" fill="currentColor"/></svg>
                    Ready for pickup
                </span>
            </div>

            <!-- S2 overlay: platform listing frame -->
            <div class="pj-state-layer pj-sl-s2">
                <div class="pj-bg-glow pj-glow--sage"></div>
                <div class="pj-list-frame">
                    <div class="plf-head">
                        <span class="plf-status"><span class="live-dot"></span>Available</span>
                        <span class="plf-dist">
                            <svg viewBox="0 0 12 12" width="10"><path d="M6 1C3.8 1 2 2.8 2 5c0 3.2 4 6 4 6s4-2.8 4-6C10 2.8 8.2 1 6 1Z" fill="currentColor" opacity=".4"/><circle cx="6" cy="5" r="1.5" fill="currentColor"/></svg>
                            0.8km away
                        </span>
                    </div>
                    <div class="plf-rows">
                        <div class="plf-row"><span>Pickup</span><strong>14:00 – 17:00</strong></div>
                        <div class="plf-row"><span>Quantity</span><strong>28 portions</strong></div>
                        <div class="plf-row"><span>Business</span><strong>Green Valley Bakery</strong></div>
                    </div>
                    <button class="plf-btn">Reserve this listing</button>
                </div>
            </div>

            <!-- S3 overlay: impact rings + chips -->
            <div class="pj-state-layer pj-sl-s3">
                <div class="pj-bg-glow pj-glow--amber"></div>
                <svg class="pj-rings-svg" viewBox="0 0 280 280" aria-hidden="true">
                    <circle cx="140" cy="140" r="130" fill="none" stroke="rgba(196,145,62,0.14)" stroke-width="1.5"/>
                    <circle cx="140" cy="140" r="100" fill="none" stroke="rgba(196,145,62,0.2)"  stroke-width="1.5"/>
                    <circle cx="140" cy="140" r="70"  fill="none" stroke="rgba(196,145,62,0.14)" stroke-width="1"/>
                    <!-- Nodes on outer ring -->
                    <circle cx="140" cy="10"  r="4" fill="rgba(196,145,62,0.5)"/>
                    <circle cx="268" cy="100" r="3" fill="rgba(196,145,62,0.4)"/>
                    <circle cx="240" cy="242" r="3" fill="rgba(122,154,106,0.5)"/>
                    <circle cx="35"  cy="220" r="4" fill="rgba(122,154,106,0.4)"/>
                    <circle cx="18"  cy="100" r="3" fill="rgba(181,96,74,0.4)"/>
                </svg>
                <div class="pj-ichip pj-ichip-1">
                    <svg viewBox="0 0 14 14" width="12"><circle cx="7" cy="7" r="6" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M4 7l2.5 2.5 3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none"/></svg>
                    Pickup confirmed
                </div>
                <div class="pj-ichip pj-ichip-2">
                    <svg viewBox="0 0 14 14" width="12"><path d="M2 10l4-5 3 3 4-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
                    28 meals supported
                </div>
                <div class="pj-ichip pj-ichip-3">
                    <svg viewBox="0 0 14 14" width="12"><circle cx="7" cy="5" r="2.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M2 13c0-2.8 2.2-5 5-5s5 2.2 5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none"/></svg>
                    Local community
                </div>
            </div>

            <!-- THE SHARED PARCEL SVG — the one visual element traveling through all 3 sections -->
            <div class="pj-parcel-wrap" id="pj-parcel-wrap">
                <svg class="pj-svg" viewBox="0 0 220 300" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="A premium bakery food parcel">

                    <!-- Ground shadow -->
                    <ellipse cx="110" cy="290" rx="72" ry="8" fill="rgba(30,50,20,0.1)"/>

                    <!-- Bag body -->
                    <path d="M38 148 L30 272 Q30 280 38 280 L182 280 Q190 280 190 272 L182 148 Z" fill="#e8d9b8"/>

                    <!-- Side depth shadows -->
                    <path d="M38 148 L30 272 Q30 280 38 280 L56 280 L50 148Z" fill="rgba(0,0,0,0.045)"/>
                    <path d="M182 148 L190 272 Q190 280 182 280 L164 280 L170 148Z" fill="rgba(0,0,0,0.045)"/>

                    <!-- Horizontal paper texture lines -->
                    <line x1="36" y1="178" x2="184" y2="178" stroke="#c8b880" stroke-width="0.5" opacity="0.28"/>
                    <line x1="33" y1="212" x2="187" y2="212" stroke="#c8b880" stroke-width="0.5" opacity="0.22"/>
                    <line x1="31" y1="246" x2="189" y2="246" stroke="#c8b880" stroke-width="0.5" opacity="0.18"/>

                    <!-- Centre vertical crease -->
                    <line x1="110" y1="148" x2="110" y2="278" stroke="#c8b880" stroke-width="0.8" stroke-dasharray="5 7" opacity="0.38"/>

                    <!-- Top fold -->
                    <path d="M38 148 L44 122 L176 122 L182 148 Z" fill="#ddd0a4"/>
                    <line x1="38"  y1="148" x2="182" y2="148" stroke="#c4aa68" stroke-width="1.2"/>
                    <line x1="44"  y1="122" x2="176" y2="122" stroke="#c4aa68" stroke-width="0.7" opacity="0.5"/>

                    <!-- Handle left -->
                    <path d="M76 122 Q72 82 92 68 Q108 56 110 62" stroke="#8B6234" stroke-width="6.5" stroke-linecap="round"/>
                    <path d="M79 120 Q76 84 94 71" stroke="#b07840" stroke-width="2.5" stroke-linecap="round" opacity="0.35"/>

                    <!-- Handle right -->
                    <path d="M144 122 Q148 82 128 68 Q112 56 110 62" stroke="#8B6234" stroke-width="6.5" stroke-linecap="round"/>
                    <path d="M141 120 Q144 84 126 71" stroke="#b07840" stroke-width="2.5" stroke-linecap="round" opacity="0.35"/>

                    <!-- Bread loaf peeking from top -->
                    <path d="M56 134 Q56 102 84 92 Q110 84 136 92 Q164 102 164 134" fill="#c87840"/>
                    <path d="M60 130 Q60 106 86 98 Q110 92 134 98 Q158 106 160 130" fill="#d4894a" opacity="0.5"/>
                    <!-- Scoring marks on bread -->
                    <path d="M78 124 Q110 110 142 124" stroke="#a06030" stroke-width="1.5" fill="none" opacity="0.7"/>
                    <path d="M88 128 Q110 116 132 128" stroke="#a06030" stroke-width="1.5" fill="none" opacity="0.5"/>

                    <!-- Fresh herbs left -->
                    <path d="M66 128 L60 98"  stroke="#4a6741" stroke-width="2.5" stroke-linecap="round"/>
                    <ellipse cx="57"  cy="93"  rx="6"   ry="3.5" fill="#5a7a50" transform="rotate(-30 57 93)"/>
                    <path d="M70 128 L58 102"  stroke="#4a6741" stroke-width="2" stroke-linecap="round"/>
                    <ellipse cx="55"  cy="97"  rx="5"   ry="3"   fill="#6a8a5a" transform="rotate(10 55 97)"/>

                    <!-- Fresh herbs right -->
                    <path d="M154 128 L162 96" stroke="#4a6741" stroke-width="2.5" stroke-linecap="round"/>
                    <ellipse cx="165" cy="91"  rx="6.5" ry="3.5" fill="#5a7a50" transform="rotate(20 165 91)"/>
                    <path d="M150 128 L155 94" stroke="#5a7a50" stroke-width="2" stroke-linecap="round"/>
                    <ellipse cx="153" cy="88"  rx="5"   ry="3"   fill="#4a6741" transform="rotate(-25 153 88)"/>
                    <path d="M158 128 L168 100"stroke="#4a6741" stroke-width="2" stroke-linecap="round"/>
                    <ellipse cx="172" cy="96"  rx="5"   ry="3.2" fill="#6a8a5a" transform="rotate(35 172 96)"/>

                    <!-- Hanging label tag -->
                    <rect x="147" y="40" width="60" height="40" rx="6" fill="white" stroke="#c4a868" stroke-width="1.5"/>
                    <circle cx="177" cy="40" r="5" fill="white" stroke="#c4a868" stroke-width="1.5"/>
                    <!-- Tag string -->
                    <path d="M177 45 Q174 58 162 72 Q150 86 142 98" stroke="#c4a868" stroke-width="1" stroke-dasharray="2.5 3" opacity="0.65"/>
                    <!-- Tag inner content -->
                    <rect x="153" y="47" width="48" height="27" rx="3" fill="#f8f4ea"/>
                    <!-- Small ResQFood logotype suggestion -->
                    <rect x="157" y="52" width="16" height="4" rx="2" fill="#4a6741" opacity="0.7"/>
                    <rect x="157" y="59" width="26" height="2.5" rx="1.2" fill="#7a8c72" opacity="0.5"/>
                    <rect x="157" y="64" width="20" height="2" rx="1" fill="#7a8c72" opacity="0.35"/>

                    <!-- Bag bottom stitch line -->
                    <path d="M44 276 L176 276" stroke="#c8b880" stroke-width="0.8" stroke-dasharray="3 5" opacity="0.4"/>

                    <!-- Subtle warm highlight on bag body -->
                    <path d="M110 148 Q72 190 68 240 L58 240 Q60 188 98 145Z" fill="rgba(255,255,255,0.12)"/>
                </svg>
            </div>

        </div><!-- /pj-parcel-stage -->
    </div><!-- /pj-left -->


    <!-- ── SCROLLING CONTENT COLUMN ── -->
    <div class="pj-right">

        <!-- ── S1: Surplus, still valuable ── -->
        <div class="pj-section pj-s1" id="pj-s1" data-state="s1">
            <div class="pj-section-inner">
                <span class="overline pj-overline">Surplus, still valuable</span>
                <h2 class="pj-h2" data-reveal>Good food shouldn't<br>end with the day.</h2>
                <p class="pj-p" data-reveal data-reveal-delay="120">Every evening, bakeries, cafes, and restaurants prepare more than they sell. That surplus has hours of value left - and communities nearby who need it.</p>
                <blockquote class="pj-quote" data-reveal data-reveal-delay="240">
                    <em>"28 portions, freshly prepared. Three hours of pickup time remaining. No one knows it's available yet."</em>
                </blockquote>
                <div class="pj-s1-stat" data-reveal data-reveal-delay="360">
                    <strong>1 in 3</strong>
                    <span>portions prepared daily goes unsold in food service businesses</span>
                </div>
            </div>
        </div>

        <!-- ── S2: Structured by the platform ── -->
        <div class="pj-section pj-s2" id="pj-s2" data-state="s2">
            <div class="pj-section-inner">
                <span class="overline pj-overline">Structured by the platform</span>
                <h2 class="pj-h2" data-reveal>One listing.<br>Ready to be claimed.</h2>
                <div class="pj-ui-card" data-reveal data-reveal-delay="120">
                    <div class="puic-head">
                        <span class="live-dot"></span>
                        <span>Live on ResQFood</span>
                        <span class="puic-dist">0.8km away</span>
                    </div>
                    <div class="puic-title">Fresh croissants &amp; bread rolls</div>
                    <div class="puic-rows">
                        <div class="puic-row">
                            <svg viewBox="0 0 14 14" width="12"><path d="M7 1C4.8 1 3 2.8 3 5c0 3.5 4 6 4 6s4-2.5 4-6C11 2.8 9.2 1 7 1Z" fill="currentColor" opacity=".35"/><circle cx="7" cy="5" r="2" fill="currentColor"/></svg>
                            Green Valley Bakery
                        </div>
                        <div class="puic-row">
                            <svg viewBox="0 0 14 14" width="12"><circle cx="7" cy="7" r="5.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M7 4v3.2l2 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none"/></svg>
                            Pickup window: 14:00 – 17:00
                        </div>
                        <div class="puic-row">
                            <svg viewBox="0 0 14 14" width="12"><rect x="2" y="3" width="10" height="9" rx="2" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M5 2v2M9 2v2M2 7h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none"/></svg>
                            28 portions available
                        </div>
                    </div>
                    <div class="puic-foot">
                        <span class="puic-tag">Bakery surplus</span>
                        <button class="puic-btn">Reserve</button>
                    </div>
                </div>
                <p class="pj-p" data-reveal data-reveal-delay="300">ResQFood turns surplus into a structured, visible listing that nearby users and charities can discover, reserve, and collect - in a single, coordinated workflow.</p>
            </div>
        </div>

        <!-- ── S3: Delivered into community impact ── -->
        <div class="pj-section pj-s3" id="pj-s3" data-state="s3">
            <div class="pj-section-inner">
                <span class="overline pj-overline">Delivered into community impact</span>
                <h2 class="pj-h2" data-reveal>The same parcel.<br><em>A real difference.</em></h2>
                <div class="pj-impact-nums" data-reveal data-reveal-delay="120">
                    <div class="pin-stat">
                        <strong>28</strong>
                        <span>meals from this parcel alone</span>
                    </div>
                    <div class="pin-stat">
                        <strong>1,240+</strong>
                        <span>total meals rescued on the platform</span>
                    </div>
                    <div class="pin-stat">
                        <strong>0 wasted</strong>
                        <span>for every listing successfully claimed</span>
                    </div>
                </div>
                <p class="pj-p" data-reveal data-reveal-delay="280">What started as surplus at the end of a bakery's day became twenty-eight nutritional portions for a local food bank. The platform made that connection possible, trackable, and repeatable.</p>
                <div class="pj-community-note" data-reveal data-reveal-delay="400">
                    <svg viewBox="0 0 16 16" width="14"><circle cx="8" cy="5" r="2.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M3 14c0-2.8 2.2-5 5-5s5 2.2 5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none"/></svg>
                    Collected by St. Andrew's Food Bank · Confirmed pickup · 14:52 today
                </div>
            </div>
        </div>

    </div><!-- /pj-right -->

</div><!-- /parcel-journey -->


<!-- ════ ECOSYSTEM — Roles ════ -->
<section class="ecosystem" id="roles" aria-labelledby="eco-h">

    <div class="container">
        <div class="section-head centered" data-reveal>
            <span class="overline">Who uses ResQFood</span>
            <h2 id="eco-h">One platform,<br>four connected roles.</h2>
        </div>
    </div>

    <!-- ── Premium auto-scrolling marquee carousel ── -->
    <div class="eco-carousel" role="region" aria-label="ResQFood user roles">
        <div class="eco-track" id="eco-track">

            <!-- Set A -->
            <div class="eco-set">

                <article class="eco-card eco-card--biz">
                    <div class="eco-card-img">
                        <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=700&q=80" alt="Restaurant kitchen" loading="lazy">
                        <span class="eco-card-tag">Food Businesses</span>
                    </div>
                    <div class="eco-body">
                        <h3>Publish surplus,<br>reduce disposal.</h3>
                        <p>Restaurants, bakeries, and cafes create structured listings in minutes and manage every pickup through a consistent workflow.</p>
                        <ul class="eco-feats">
                            <li>Create and manage listings</li>
                            <li>Set pickup availability windows</li>
                            <li>Track reservations and history</li>
                        </ul>
                    </div>
                </article>

                <article class="eco-card eco-card--users">
                    <div class="eco-card-img">
                        <img src="https://images.unsplash.com/photo-1600880292203-757bb62b4baf?auto=format&fit=crop&w=700&q=80" alt="Community member" loading="lazy">
                        <span class="eco-card-tag">General Users</span>
                    </div>
                    <div class="eco-body">
                        <h3>Browse, reserve,<br>and collect locally.</h3>
                        <p>Local community members discover available surplus, make reservations, and collect food at the agreed time.</p>
                        <ul class="eco-feats">
                            <li>Browse live listings nearby</li>
                            <li>Reserve available food</li>
                            <li>View pickup details</li>
                        </ul>
                    </div>
                </article>

                <article class="eco-card eco-card--charities">
                    <div class="eco-card-img">
                        <img src="https://images.unsplash.com/photo-1593113598332-cd288d649433?auto=format&fit=crop&w=700&q=80" alt="Charity volunteers" loading="lazy">
                        <span class="eco-card-tag">Charities</span>
                    </div>
                    <div class="eco-body">
                        <h3>Coordinate larger,<br>recurring collections.</h3>
                        <p>Charitable organisations manage bulk pickups, match surplus to community need, and maintain a full collection history.</p>
                        <ul class="eco-feats">
                            <li>Coordinate bulk pickups</li>
                            <li>Match surplus to community demand</li>
                            <li>Track collection records</li>
                        </ul>
                    </div>
                </article>

                <article class="eco-card eco-card--admin">
                    <div class="eco-card-img">
                        <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=700&q=80" alt="Admin analytics" loading="lazy">
                        <span class="eco-card-tag">Administrators</span>
                    </div>
                    <div class="eco-body">
                        <h3>Oversee, moderate,<br>and maintain.</h3>
                        <p>Administrators monitor all activity, manage user roles, handle reports, and ensure the platform remains dependable.</p>
                        <ul class="eco-feats">
                            <li>Platform-wide oversight</li>
                            <li>User and role management</li>
                            <li>Moderation and reporting tools</li>
                        </ul>
                    </div>
                </article>

            </div><!-- /eco-set A -->

            <!-- Set B — duplicate for seamless infinite loop -->
            <div class="eco-set" aria-hidden="true">

                <article class="eco-card eco-card--biz">
                    <div class="eco-card-img">
                        <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=700&q=80" alt="" loading="lazy">
                        <span class="eco-card-tag">Food Businesses</span>
                    </div>
                    <div class="eco-body">
                        <h3>Publish surplus,<br>reduce disposal.</h3>
                        <p>Restaurants, bakeries, and cafes create structured listings in minutes and manage every pickup through a consistent workflow.</p>
                        <ul class="eco-feats">
                            <li>Create and manage listings</li>
                            <li>Set pickup availability windows</li>
                            <li>Track reservations and history</li>
                        </ul>
                    </div>
                </article>

                <article class="eco-card eco-card--users">
                    <div class="eco-card-img">
                        <img src="https://images.unsplash.com/photo-1600880292203-757bb62b4baf?auto=format&fit=crop&w=700&q=80" alt="" loading="lazy">
                        <span class="eco-card-tag">General Users</span>
                    </div>
                    <div class="eco-body">
                        <h3>Browse, reserve,<br>and collect locally.</h3>
                        <p>Local community members discover available surplus, make reservations, and collect food at the agreed time.</p>
                        <ul class="eco-feats">
                            <li>Browse live listings nearby</li>
                            <li>Reserve available food</li>
                            <li>View pickup details</li>
                        </ul>
                    </div>
                </article>

                <article class="eco-card eco-card--charities">
                    <div class="eco-card-img">
                        <img src="https://images.unsplash.com/photo-1593113598332-cd288d649433?auto=format&fit=crop&w=700&q=80" alt="" loading="lazy">
                        <span class="eco-card-tag">Charities</span>
                    </div>
                    <div class="eco-body">
                        <h3>Coordinate larger,<br>recurring collections.</h3>
                        <p>Charitable organisations manage bulk pickups, match surplus to community need, and maintain a full collection history.</p>
                        <ul class="eco-feats">
                            <li>Coordinate bulk pickups</li>
                            <li>Match surplus to community demand</li>
                            <li>Track collection records</li>
                        </ul>
                    </div>
                </article>

                <article class="eco-card eco-card--admin">
                    <div class="eco-card-img">
                        <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=700&q=80" alt="" loading="lazy">
                        <span class="eco-card-tag">Administrators</span>
                    </div>
                    <div class="eco-body">
                        <h3>Oversee, moderate,<br>and maintain.</h3>
                        <p>Administrators monitor all activity, manage user roles, handle reports, and ensure the platform remains dependable.</p>
                        <ul class="eco-feats">
                            <li>Platform-wide oversight</li>
                            <li>User and role management</li>
                            <li>Moderation and reporting tools</li>
                        </ul>
                    </div>
                </article>

            </div><!-- /eco-set B -->

        </div><!-- /eco-track -->
    </div><!-- /eco-carousel -->

</section>


<!-- ════ IMPACT — Living dashboard ════ -->
<section class="impact" id="impact" aria-labelledby="impact-h">

    <div class="impact-decor" aria-hidden="true">
        <svg class="idecor idecor--a" viewBox="0 0 500 500"><circle cx="250" cy="250" r="240" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1.5"/></svg>
        <svg class="idecor idecor--b" viewBox="0 0 320 320"><circle cx="160" cy="160" r="155" fill="none" stroke="rgba(255,255,255,0.04)" stroke-width="1"/></svg>
        <svg class="idecor idecor--c" viewBox="0 0 700 700"><circle cx="350" cy="350" r="330" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="2"/></svg>
    </div>

    <div class="container">
        <div class="impact-top" data-reveal>
            <span class="overline overline--light">Platform impact</span>
            <h2 id="impact-h">Every pickup adds up.</h2>
        </div>

        <div class="impact-hero-num" data-reveal>
            <span class="counter-hero" data-target="1240" aria-label="1,240 meals saved">0</span>
            <sup class="impact-plus">+</sup>
        </div>
        <p class="impact-hero-label" data-reveal data-reveal-delay="100">meals of good food rescued from going to waste</p>

        <div class="impact-rings">
            <article class="iring" data-reveal>
                <div class="iring-wrap">
                    <svg class="iring-svg" viewBox="0 0 140 140">
                        <circle class="ring-track" cx="70" cy="70" r="58"/>
                        <circle class="ring-arc" cx="70" cy="70" r="58" data-pct="72"/>
                    </svg>
                    <div class="iring-center"><strong class="counter" data-target="386">0</strong></div>
                </div>
                <h3>Listings shared</h3>
                <p>Surplus food offers published by participating businesses.</p>
            </article>
            <article class="iring" data-reveal data-reveal-delay="150">
                <div class="iring-wrap">
                    <svg class="iring-svg" viewBox="0 0 140 140">
                        <circle class="ring-track" cx="70" cy="70" r="58"/>
                        <circle class="ring-arc" cx="70" cy="70" r="58" data-pct="65"/>
                    </svg>
                    <div class="iring-center"><strong class="counter" data-target="295">0</strong></div>
                </div>
                <h3>Pickups completed</h3>
                <p>Confirmed collections by users and charities.</p>
            </article>
            <article class="iring" data-reveal data-reveal-delay="300">
                <div class="iring-wrap">
                    <svg class="iring-svg" viewBox="0 0 140 140">
                        <circle class="ring-track" cx="70" cy="70" r="58"/>
                        <circle class="ring-arc" cx="70" cy="70" r="58" data-pct="48"/>
                    </svg>
                    <div class="iring-center"><strong class="counter" data-target="42">0</strong></div>
                </div>
                <h3>Community partners</h3>
                <p>Active businesses and organisations on the platform.</p>
            </article>
        </div>
    </div>
</section>


<!-- ════ MANIFESTO — Why it matters ════ -->
<section class="manifesto" aria-label="Why ResQFood matters">

    <!-- Full-bleed photo hero with centered content -->
    <div class="manifesto-hero">
        <img src="https://images.unsplash.com/photo-1529543544282-ea669407fca3?auto=format&fit=crop&w=1400&q=80" alt="Community sharing a meal together" class="manifesto-photo" loading="lazy">
        <div class="manifesto-veil"></div>
        <div class="manifesto-hero-body container">
            <span class="overline overline--light" data-reveal>Why it matters</span>
            <blockquote class="manifesto-quote" data-reveal data-reveal-delay="150">
                <p>"Every day, tonnes of edible food are discarded while communities nearby go without. The only thing missing is <em>coordination.</em>"</p>
            </blockquote>
            <div class="manifesto-scroll-hint" data-reveal data-reveal-delay="300" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="20"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
            </div>
        </div>
    </div>

    <!-- Values: cream card panel rising from photo -->
    <div class="manifesto-values-wrap">
        <div class="container">
            <div class="manifesto-values-head" data-reveal>
                <span class="overline">What drives us</span>
                <h2 class="mvals-title">Built on three commitments.</h2>
            </div>
            <div class="manifesto-values">
                <div class="mval" data-reveal>
                    <div class="mval-top">
                        <span class="mval-n">01</span>
                        <svg class="mval-icon" viewBox="0 0 32 32" aria-hidden="true"><circle cx="16" cy="16" r="14" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".3"/><path d="M10 16l4 4 8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
                    </div>
                    <h3>Trusted by design</h3>
                    <p>Role-based access, moderation tools, and administrative oversight keep every exchange dependable and accountable.</p>
                </div>
                <div class="mval" data-reveal data-reveal-delay="120">
                    <div class="mval-top">
                        <span class="mval-n">02</span>
                        <svg class="mval-icon" viewBox="0 0 32 32" aria-hidden="true"><circle cx="16" cy="16" r="14" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".3"/><rect x="10" y="9" width="12" height="14" rx="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M13 14h6M13 18h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </div>
                    <h3>Practical for real operations</h3>
                    <p>Built around actual redistribution steps - listing, reserving, scheduling, collecting - not abstract product features.</p>
                </div>
                <div class="mval" data-reveal data-reveal-delay="240">
                    <div class="mval-top">
                        <span class="mval-n">03</span>
                        <svg class="mval-icon" viewBox="0 0 32 32" aria-hidden="true"><circle cx="16" cy="16" r="14" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".3"/><path d="M8 22l5-6 3 3 5-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
                    </div>
                    <h3>Local impact at scale</h3>
                    <p>Every pickup adds to a growing record of measurable social value, helping communities and stakeholders see real outcomes.</p>
                </div>
            </div>
        </div>
    </div>

</section>


<!-- ════ FINAL CTA ════ -->
<section class="cta-final" aria-label="Get started">

    <div class="cta-bg-shapes" aria-hidden="true">
        <svg class="cta-shape cta-shape--1" viewBox="0 0 500 420">
            <path d="M340 26C404 52 446 120 446 192C446 264 404 328 346 358C288 388 212 386 152 356C92 326 46 266 36 198C26 130 50 56 100 28C150 0 218 -8 272 18C298 28 334 18 340 26Z" fill="#5a7a50" opacity=".28"/>
        </svg>
        <svg class="cta-shape cta-shape--2" viewBox="0 0 380 320">
            <path d="M256 20C306 42 338 102 340 164C342 226 310 282 262 308C214 334 150 334 100 308C50 282 14 228 8 166C2 104 28 40 76 14C124 -12 184 -10 228 16C250 28 252 16 256 20Z" fill="#c4913e" opacity=".18"/>
        </svg>
    </div>

    <div class="container cta-body" data-reveal>
        <span class="tag-pill cta-tag"><span class="tag-dot"></span>Join the platform</span>
        <h2 class="cta-h">Ready to turn surplus<br>into community value?</h2>
        <p class="cta-sub">Whether you run a food business, want to reduce local waste, or support your community - ResQFood connects you to the right people at the right time.</p>
        <div class="cta-actions">
            <a href="<?= url('register.php') ?>" class="btn btn-cream btn-lg">Get Started</a>
            <a href="<?= url('login.php') ?>" class="btn btn-outline-cream btn-lg">Explore Listings</a>
        </div>
    </div>

</section>


<!-- ════ FOOTER ════ -->
<footer class="site-footer" id="contact">
    <div class="container footer-inner">

        <div class="footer-brand">
            <a href="#top" class="nav-logo" aria-label="Home">
                <span class="logo-mark" aria-hidden="true">
                    <?php if (setting('logo_path')): ?>
                        <img src="<?= url(setting('logo_path')) ?>" alt="Logo" style="width:32px;height:32px;object-fit:contain">
                    <?php else: ?>
                        <svg viewBox="0 0 36 36" width="32" height="32">
                            <circle cx="18" cy="18" r="16" fill="currentColor" opacity=".12"/>
                            <path d="M10 20c0-5 4-9 9-9h4c0 5-4 9-9 9h-4Z" fill="currentColor"/>
                            <path d="M11 22h6c5 0 9 4 9 9h-6c-5 0-9-4-9-9Z" fill="currentColor" opacity=".5"/>
                        </svg>
                    <?php endif; ?>
                </span>
                <strong><?= e(setting('site_name', 'ResQFood')) ?></strong>
            </a>
            <p><?= e(setting('footer_text', 'A coordinated platform for surplus food redistribution - connecting businesses, communities, and charities.')) ?></p>
            <a href="mailto:<?= e(setting('contact_email', 'hello@resqfood.com')) ?>" class="footer-email"><?= e(setting('contact_email', 'hello@resqfood.com')) ?></a>
            <?php if (setting('facebook_url') || setting('twitter_url') || setting('instagram_url')): ?>
            <div class="footer-social" style="display:flex;align-items:center;gap:0.85rem;margin-top:1rem">
                <?php if (setting('facebook_url')): ?>
                <a href="<?= e(setting('facebook_url')) ?>" target="_blank" rel="noopener" aria-label="Facebook" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,0.08);color:currentColor;transition:background 0.2s,transform 0.2s" onmouseover="this.style.background='rgba(255,255,255,0.18)';this.style.transform='translateY(-2px)'" onmouseout="this.style.background='rgba(255,255,255,0.08)';this.style.transform='translateY(0)'">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3l-.5 3H13v6.8C18.56 20.87 22 16.84 22 12z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (setting('twitter_url')): ?>
                <a href="<?= e(setting('twitter_url')) ?>" target="_blank" rel="noopener" aria-label="Twitter / X" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,0.08);color:currentColor;transition:background 0.2s,transform 0.2s" onmouseover="this.style.background='rgba(255,255,255,0.18)';this.style.transform='translateY(-2px)'" onmouseout="this.style.background='rgba(255,255,255,0.08)';this.style.transform='translateY(0)'">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (setting('instagram_url')): ?>
                <a href="<?= e(setting('instagram_url')) ?>" target="_blank" rel="noopener" aria-label="Instagram" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,0.08);color:currentColor;transition:background 0.2s,transform 0.2s" onmouseover="this.style.background='rgba(255,255,255,0.18)';this.style.transform='translateY(-2px)'" onmouseout="this.style.background='rgba(255,255,255,0.08)';this.style.transform='translateY(0)'">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07s-3.58-.01-4.85-.07c-3.26-.15-4.77-1.7-4.92-4.92C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.15-3.23 1.66-4.77 4.92-4.92C8.42 2.17 8.8 2.16 12 2.16M12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.7.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c4.35-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.2-4.35-2.62-6.78-6.98-6.98C15.67.01 15.26 0 12 0z"/><path d="M12 5.84A6.16 6.16 0 1018.16 12 6.16 6.16 0 0012 5.84zm0 10.16A4 4 0 1116 12a4 4 0 01-4 4z"/><circle cx="18.41" cy="5.59" r="1.44"/></svg>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <nav class="footer-links" aria-label="Footer navigation">
            <div class="footer-col">
                <h4>Platform</h4>
                <ul>
                    <li><a href="#how-it-works">How it works</a></li>
                    <li><a href="#roles">Roles</a></li>
                    <li><a href="#impact">Impact</a></li>
                    <li><a href="#">Features</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Get involved</h4>
                <ul>
                    <li><a href="#">Register a business</a></li>
                    <li><a href="#">Browse listings</a></li>
                    <li><a href="#">Partner as charity</a></li>
                    <li><a href="#">Contact us</a></li>
                </ul>
            </div>
        </nav>

    </div>
    <div class="footer-base">
        <div class="container footer-base-inner">
            <small><?= e(setting('copyright_text', '© ' . date('Y') . ' ResQFood. All rights reserved.')) ?></small>
            <div class="footer-base-links">
                <a href="#">Privacy</a>
                <a href="#">Terms</a>
            </div>
        </div>
    </div>
</footer>

<script src="<?= asset('js/main.js') ?>" defer></script>
</body>
</html>
