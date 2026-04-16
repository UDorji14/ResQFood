<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ResQFood | Food Redistribution Platform</title>
    <meta
        name="description"
        content="ResQFood is a food redistribution platform that helps businesses, local users, and charities coordinate surplus food listings and reduce edible food waste."
    >
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="page-shell">
        <header class="site-header" id="home">
            <nav class="navbar container" aria-label="Primary">
                <a href="#home" class="brand" aria-label="ResQFood home">
                    <span class="brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 52 52" role="img">
                            <circle cx="26" cy="26" r="25" fill="currentColor" opacity="0.1"></circle>
                            <path d="M16 28.5C16 20.77 22.27 14.5 30 14.5H35C35 22.23 28.73 28.5 21 28.5H16Z" fill="currentColor"></path>
                            <path d="M17.5 30.5H25.5C33.23 30.5 39.5 36.77 39.5 44.5H31.5C23.77 44.5 17.5 38.23 17.5 30.5Z" fill="currentColor" opacity="0.6"></path>
                        </svg>
                    </span>
                    <span class="brand-text">
                        <strong>ResQFood</strong>
                        <span>Food Redistribution Platform</span>
                    </span>
                </a>

                <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-menu">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span class="sr-only">Toggle navigation</span>
                </button>

                <div class="nav-panel" id="mobile-menu">
                    <ul class="nav-links">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#how-it-works">How It Works</a></li>
                        <li><a href="#roles">Roles</a></li>
                        <li><a href="#impact">Impact</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>

                    <div class="nav-actions">
                        <a href="#" class="button button-secondary button-compact">Login</a>
                        <a href="#" class="button button-primary button-compact">Get Started</a>
                    </div>
                </div>
            </nav>
        </header>

        <main>
            <section class="hero section">
                <div class="hero-backdrop" aria-hidden="true"></div>
                <div class="container hero-grid">
                    <div class="hero-copy reveal">
                        <div class="eyebrow">
                            <span class="eyebrow-dot"></span>
                            Coordinated food recovery for communities
                        </div>
                        <h1>Turn surplus food into timely community access.</h1>
                        <p class="hero-text">
                            ResQFood helps food businesses, local users, and charities share edible surplus through a single,
                            structured platform for listings, reservations, pickups, and measurable impact.
                        </p>

                        <div class="hero-actions">
                            <a href="#" class="button button-primary">Get Started</a>
                            <a href="#" class="button button-secondary">Explore Listings</a>
                        </div>

                        <div class="trust-row">
                            <div class="trust-card">
                                <strong>Verified coordination</strong>
                                <span>Role-based access for businesses, users, charities, and administrators.</span>
                            </div>
                            <div class="trust-card">
                                <strong>Operational clarity</strong>
                                <span>Listings, reservations, and pickups managed in one workflow.</span>
                            </div>
                        </div>
                    </div>

                    <div class="hero-visual reveal">
                        <div class="hero-card hero-card-main">
                            <div class="hero-card-top">
                                <span class="status-pill">Live surplus listing</span>
                                <span class="status-meta">Pickup today</span>
                            </div>
                            <h2>Fresh bakery items ready for collection</h2>
                            <p>Reserved through ResQFood and matched with nearby community demand.</p>
                            <div class="hero-card-tags">
                                <span>Bakery</span>
                                <span>Reserved</span>
                                <span>Tracked</span>
                            </div>
                        </div>

                        <div class="hero-card hero-card-float hero-card-small">
                            <strong>24 pickups</strong>
                            <span>completed this week across active partners</span>
                        </div>

                        <div class="hero-card hero-card-float hero-card-mini">
                            <strong>4 roles</strong>
                            <span>one coordinated platform</span>
                        </div>

                        <div class="hero-illustration" aria-hidden="true">
                            <svg viewBox="0 0 620 520" role="img">
                                <defs>
                                    <linearGradient id="panelFill" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0%" stop-color="#f6f0df"></stop>
                                        <stop offset="100%" stop-color="#ecdfc3"></stop>
                                    </linearGradient>
                                    <linearGradient id="accentFill" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0%" stop-color="#637a4e"></stop>
                                        <stop offset="100%" stop-color="#3d5a40"></stop>
                                    </linearGradient>
                                </defs>
                                <path d="M80 120C150 38 305 16 405 65C505 114 574 218 549 309C524 400 404 477 262 469C120 461 32 365 35 266C37 206 48 159 80 120Z" fill="#f5ecdb"></path>
                                <path d="M138 146H429C459 146 483 170 483 200V352C483 382 459 406 429 406H138C108 406 84 382 84 352V200C84 170 108 146 138 146Z" fill="url(#panelFill)"></path>
                                <path d="M319 112C345 88 398 85 431 103C464 121 479 160 466 193C453 226 412 251 367 250C322 249 284 224 279 188C275 159 293 133 319 112Z" fill="#d1b174" opacity="0.25"></path>
                                <path d="M124 178H444V220H124Z" fill="#ffffff" opacity="0.7"></path>
                                <path d="M124 244H444V372H124Z" fill="#ffffff" opacity="0.48"></path>
                                <rect x="146" y="262" width="146" height="88" rx="24" fill="url(#accentFill)"></rect>
                                <rect x="312" y="262" width="110" height="18" rx="9" fill="#a8b794"></rect>
                                <rect x="312" y="294" width="90" height="18" rx="9" fill="#c9d4bc"></rect>
                                <rect x="312" y="326" width="74" height="18" rx="9" fill="#d7dfcb"></rect>
                                <path d="M205 314C225 280 266 264 297 273C328 282 351 316 344 352C337 388 300 417 257 414C214 411 174 377 174 332C174 325 175 319 177 314H205Z" fill="#f7f1e3"></path>
                                <path d="M239 314C239 289 259 269 284 269H299C299 294 279 314 254 314H239Z" fill="#466447"></path>
                                <path d="M240 319H267C292 319 312 339 312 364H285C260 364 240 344 240 319Z" fill="#8ea272"></path>
                                <path d="M495 206C531 233 553 280 543 318C533 356 493 385 448 385C403 385 353 356 344 318C335 280 367 233 414 206C441 190 468 185 495 206Z" fill="#f0e4c8"></path>
                                <circle cx="449" cy="286" r="66" fill="#fffaf0"></circle>
                                <path d="M445 244C456 232 471 229 481 237C492 245 494 262 485 276C477 291 459 304 445 314C432 304 414 291 405 276C397 262 398 244 409 237C420 229 434 232 445 244Z" fill="#c97758"></path>
                                <path d="M96 426C134 385 197 378 243 402C289 426 315 476 303 515H84C75 480 75 449 96 426Z" fill="#e7d5b0" opacity="0.65"></path>
                                <path d="M357 72C373 48 401 34 425 36C448 38 467 57 473 80C479 103 471 131 454 147C437 163 410 166 389 157C367 149 347 125 347 101C347 91 350 81 357 72Z" fill="#6d8354" opacity="0.15"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </section>

            <div class="section-divider" aria-hidden="true">
                <svg viewBox="0 0 1440 120" preserveAspectRatio="none">
                    <path d="M0 20C108 56 216 79 324 76C432 73 540 44 648 48C756 52 864 89 972 93C1080 97 1188 69 1296 54C1368 44 1416 47 1440 52V120H0V20Z"></path>
                </svg>
            </div>

            <section class="section about-section" id="about">
                <div class="container split-layout">
                    <div class="section-heading reveal">
                        <span class="section-label">Problem and solution</span>
                        <h2>Food surplus exists. Coordination is usually the missing layer.</h2>
                        <p>
                            Many businesses still handle excess food informally, while nearby users and charities struggle to know
                            what is available, when it can be collected, and who has already reserved it. The result is avoidable
                            waste, missed access, and fragmented communication.
                        </p>
                    </div>

                    <div class="problem-solution-grid">
                        <article class="info-card reveal">
                            <div class="icon-badge">
                                <svg viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M24 6C16.27 6 10 12.27 10 20C10 30.5 24 42 24 42C24 42 38 30.5 38 20C38 12.27 31.73 6 24 6Z" fill="currentColor" opacity="0.2"></path>
                                    <circle cx="24" cy="20" r="6" fill="none" stroke="currentColor" stroke-width="3"></circle>
                                </svg>
                            </div>
                            <h3>Without a shared system</h3>
                            <p>Listings are inconsistent, updates are manual, and pickups depend on fragmented messages and guesswork.</p>
                        </article>
                        <article class="info-card reveal">
                            <div class="icon-badge">
                                <svg viewBox="0 0 48 48" aria-hidden="true">
                                    <rect x="8" y="10" width="32" height="28" rx="8" fill="currentColor" opacity="0.18"></rect>
                                    <path d="M16 20H32M16 28H26" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                </svg>
                            </div>
                            <h3>Structured listing workflow</h3>
                            <p>ResQFood gives businesses a reliable way to publish available food with timing, quantities, and pickup details.</p>
                        </article>
                        <article class="info-card reveal">
                            <div class="icon-badge">
                                <svg viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M10 24H38" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                    <path d="M24 10V38" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                    <circle cx="24" cy="24" r="14" fill="none" stroke="currentColor" stroke-width="3"></circle>
                                </svg>
                            </div>
                            <h3>Accessible reservations</h3>
                            <p>Users and charities can browse availability, reserve suitable items, and reduce confusion around collection.</p>
                        </article>
                        <article class="info-card reveal">
                            <div class="icon-badge">
                                <svg viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M24 8L37 14V24C37 31.18 31.96 37.57 24 40C16.04 37.57 11 31.18 11 24V14L24 8Z" fill="currentColor" opacity="0.18"></path>
                                    <path d="M18 24L22 28L30 20" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            </div>
                            <h3>Trust and oversight</h3>
                            <p>Administrators can oversee activity, moderate reports, and maintain a dependable platform environment.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="section process-section" id="how-it-works">
                <div class="container">
                    <div class="section-heading centered reveal">
                        <span class="section-label">How it works</span>
                        <h2>A simple platform journey, designed for real-world coordination.</h2>
                        <p>Each stage is clear, trackable, and suited to the needs of participating businesses, community users, and charities.</p>
                    </div>

                    <div class="process-grid">
                        <article class="process-card reveal">
                            <span class="step-number">01</span>
                            <div class="icon-badge">
                                <svg viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M14 17H34" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                    <path d="M14 24H34" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                    <path d="M14 31H26" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                    <rect x="10" y="10" width="28" height="28" rx="8" fill="none" stroke="currentColor" stroke-width="3"></rect>
                                </svg>
                            </div>
                            <h3>Businesses post surplus food</h3>
                            <p>Restaurants, bakeries, and cafes publish available food with quantity, collection window, and collection instructions.</p>
                        </article>
                        <article class="process-card reveal">
                            <span class="step-number">02</span>
                            <div class="icon-badge">
                                <svg viewBox="0 0 48 48" aria-hidden="true">
                                    <circle cx="20" cy="20" r="8" fill="none" stroke="currentColor" stroke-width="3"></circle>
                                    <path d="M28 28L36 36" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                    <path d="M34 14H38" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                </svg>
                            </div>
                            <h3>Users and charities browse listings</h3>
                            <p>Eligible participants review current listings and identify suitable food offers based on location and timing.</p>
                        </article>
                        <article class="process-card reveal">
                            <span class="step-number">03</span>
                            <div class="icon-badge">
                                <svg viewBox="0 0 48 48" aria-hidden="true">
                                    <rect x="10" y="12" width="28" height="24" rx="8" fill="currentColor" opacity="0.18"></rect>
                                    <path d="M17 24L22 29L31 19" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            </div>
                            <h3>Reservation is confirmed</h3>
                            <p>The platform records the reservation, reduces overlap, and keeps the listing workflow clear for all parties.</p>
                        </article>
                        <article class="process-card reveal">
                            <span class="step-number">04</span>
                            <div class="icon-badge">
                                <svg viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M12 26L20 18L26 24L36 14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M31 14H36V19" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M10 38H38" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                </svg>
                            </div>
                            <h3>Pickup is completed</h3>
                            <p>Collection is tracked, food reaches people who need it, and the platform captures useful operational impact data.</p>
                        </article>
                    </div>
                </div>
            </section>

            <div class="section-divider divider-soft" aria-hidden="true">
                <svg viewBox="0 0 1440 140" preserveAspectRatio="none">
                    <path d="M0 96L60 89C120 82 240 68 360 57C480 47 600 40 720 47C840 54 960 75 1080 83C1200 91 1320 86 1380 83L1440 79V140H1380C1320 140 1200 140 1080 140C960 140 840 140 720 140C600 140 480 140 360 140C240 140 120 140 60 140H0V96Z"></path>
                </svg>
            </div>

            <section class="section roles-section" id="roles">
                <div class="container">
                    <div class="section-heading reveal">
                        <span class="section-label">User roles</span>
                        <h2>Designed for every participant in the redistribution cycle.</h2>
                        <p>ResQFood supports the operational needs of each role while keeping the overall experience consistent and easy to understand.</p>
                    </div>

                    <div class="roles-grid">
                        <article class="role-card reveal">
                            <h3>Food Businesses</h3>
                            <p>Create listings, manage availability, control pickup details, and reduce disposal through structured redistribution.</p>
                        </article>
                        <article class="role-card reveal">
                            <h3>General Users</h3>
                            <p>Browse nearby listings, reserve available food responsibly, and access surplus food through a clear reservation journey.</p>
                        </article>
                        <article class="role-card reveal">
                            <h3>Charities</h3>
                            <p>Coordinate larger or recurring pickups, match surplus to community need, and operate with better visibility across listings.</p>
                        </article>
                        <article class="role-card reveal">
                            <h3>Administrators</h3>
                            <p>Oversee platform activity, support role management, moderate reports, and maintain trustworthy platform operations.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="section features-section">
                <div class="container">
                    <div class="section-heading centered reveal">
                        <span class="section-label">Platform features</span>
                        <h2>Built to support dependable, practical food redistribution.</h2>
                    </div>

                    <div class="features-grid">
                        <?php
                        $features = [
                            ['Role-based access', 'Separate experiences and permissions for businesses, users, charities, and administrators.'],
                            ['Live food listings', 'Current availability displayed with clear details, timing, and pickup information.'],
                            ['Reservation management', 'Reservations are recorded and reflected in the listing workflow to reduce overlap.'],
                            ['Pickup scheduling', 'Collection windows and practical pickup details keep handovers organized.'],
                            ['Admin dashboard', 'Operational oversight for monitoring platform activity and maintaining quality.'],
                            ['Impact tracking', 'Core metrics help demonstrate platform usage and meaningful redistribution outcomes.'],
                            ['Secure account system', 'Structured authentication and account management support trusted access.'],
                            ['Reporting and moderation', 'Issue reporting tools help administrators respond to misuse or listing problems.'],
                        ];
                        foreach ($features as [$title, $description]):
                        ?>
                            <article class="feature-card reveal">
                                <div class="feature-icon">
                                    <svg viewBox="0 0 48 48" aria-hidden="true">
                                        <rect x="10" y="10" width="28" height="28" rx="12" fill="currentColor" opacity="0.16"></rect>
                                        <path d="M18 24H30" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                        <path d="M24 18V30" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                    </svg>
                                </div>
                                <h3><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="section impact-section" id="impact">
                <div class="impact-overlay" aria-hidden="true"></div>
                <div class="container">
                    <div class="section-heading reveal">
                        <span class="section-label">Impact</span>
                        <h2>Better coordination leads to measurable local value.</h2>
                        <p>ResQFood is designed to help stakeholders understand activity, monitor adoption, and highlight practical outcomes across the redistribution process.</p>
                    </div>

                    <div class="impact-grid">
                        <article class="impact-card reveal">
                            <strong class="impact-value" data-count="1240">1,240</strong>
                            <span class="impact-label">Meals Saved</span>
                        </article>
                        <article class="impact-card reveal">
                            <strong class="impact-value" data-count="386">386</strong>
                            <span class="impact-label">Listings Shared</span>
                        </article>
                        <article class="impact-card reveal">
                            <strong class="impact-value" data-count="295">295</strong>
                            <span class="impact-label">Successful Pickups</span>
                        </article>
                        <article class="impact-card reveal">
                            <strong class="impact-value" data-count="42">42</strong>
                            <span class="impact-label">Community Partners</span>
                        </article>
                    </div>
                </div>
            </section>

            <section class="section reasons-section">
                <div class="container reasons-layout">
                    <div class="section-heading reveal">
                        <span class="section-label">Why choose ResQFood</span>
                        <h2>A serious product experience for a meaningful operational challenge.</h2>
                    </div>

                    <div class="reasons-grid">
                        <article class="reason-card reveal">
                            <h3>Trustworthy structure</h3>
                            <p>Clear roles, controlled workflows, and administrative oversight create a more dependable platform environment.</p>
                        </article>
                        <article class="reason-card reveal">
                            <h3>Usable by different audiences</h3>
                            <p>The experience is designed for businesses, local users, charities, and stakeholders without unnecessary complexity.</p>
                        </article>
                        <article class="reason-card reveal">
                            <h3>Focused on practical coordination</h3>
                            <p>ResQFood centers on the actual steps needed to share, reserve, collect, and monitor surplus food efficiently.</p>
                        </article>
                        <article class="reason-card reveal">
                            <h3>Impact with community value</h3>
                            <p>The platform helps reduce edible food waste while improving access and supporting socially responsible operations.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="section cta-section">
                <div class="container">
                    <div class="cta-panel reveal">
                        <span class="section-label">Start with a stronger redistribution workflow</span>
                        <h2>Join ResQFood and make surplus food easier to share, reserve, and recover.</h2>
                        <p>Whether you are managing food availability or responding to community demand, ResQFood provides a clearer path from listing to pickup.</p>
                        <div class="hero-actions">
                            <a href="#" class="button button-primary">Sign Up</a>
                            <a href="#" class="button button-secondary">View Listings</a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="site-footer" id="contact">
            <div class="container footer-grid">
                <div class="footer-brand">
                    <a href="#home" class="brand brand-footer">
                        <span class="brand-mark" aria-hidden="true">
                            <svg viewBox="0 0 52 52" role="img">
                                <circle cx="26" cy="26" r="25" fill="currentColor" opacity="0.1"></circle>
                                <path d="M16 28.5C16 20.77 22.27 14.5 30 14.5H35C35 22.23 28.73 28.5 21 28.5H16Z" fill="currentColor"></path>
                                <path d="M17.5 30.5H25.5C33.23 30.5 39.5 36.77 39.5 44.5H31.5C23.77 44.5 17.5 38.23 17.5 30.5Z" fill="currentColor" opacity="0.6"></path>
                            </svg>
                        </span>
                        <span class="brand-text">
                            <strong>ResQFood</strong>
                            <span>Community food recovery platform</span>
                        </span>
                    </a>
                    <p>ResQFood supports more reliable food redistribution by connecting surplus supply with community demand through a practical digital workflow.</p>
                </div>

                <div class="footer-links">
                    <h3>Navigation</h3>
                    <a href="#about">About</a>
                    <a href="#how-it-works">How It Works</a>
                    <a href="#roles">Roles</a>
                    <a href="#impact">Impact</a>
                </div>

                <div class="footer-links">
                    <h3>Platform</h3>
                    <a href="#">Login</a>
                    <a href="#">Get Started</a>
                    <a href="#">Listings</a>
                    <a href="#">Support</a>
                </div>

                <div class="footer-links">
                    <h3>Contact</h3>
                    <a href="mailto:hello@resqfood.local">hello@resqfood.local</a>
                    <span>Available for platform demonstrations and project review.</span>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="container">
                    <p>&copy; <?= date('Y'); ?> ResQFood. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
