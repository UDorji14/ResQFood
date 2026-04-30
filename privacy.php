<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy — <?= e(setting('site_name', 'ResQFood')) ?></title>
    <meta name="description" content="<?= e(setting('site_name', 'ResQFood')) ?> Privacy Policy — how we collect, use, and protect your information.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,700;1,9..144,500;1,9..144,600&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <style>
        .pub-hero { background: linear-gradient(160deg, #1a2a17 0%, #2e3f2a 40%, #3d5436 100%); color: #fff; padding: 8rem 0 5rem; position: relative; overflow: hidden; min-height: 300px; display: flex; align-items: center; }
        .pub-hero::before { content: ''; position: absolute; inset: 0; background: url('https://images.unsplash.com/photo-1450101499163-c8848c66ca85?auto=format&fit=crop&w=1400&q=40') center/cover no-repeat; opacity: 0.06; pointer-events: none; }
        .pub-hero .container { position: relative; z-index: 1; width: 100%; }
        .pub-hero__eyebrow { font-family: 'Manrope', sans-serif; font-size: 0.8rem; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #a8d49a; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .pub-hero__eyebrow::before { content: ''; width: 24px; height: 2px; background: #a8d49a; border-radius: 2px; }
        .pub-hero h1 { font-family: 'Fraunces', serif; font-size: clamp(2rem, 4vw, 3rem); font-weight: 700; line-height: 1.2; margin-bottom: 0.75rem; color: #ffffff; text-shadow: 0 2px 20px rgba(0,0,0,0.4); }
        .pub-hero__meta { font-family: 'Manrope', sans-serif; font-size: 0.85rem; color: rgba(200,225,190,0.75); }
        
        .privacy-wrap { max-width: 780px; margin: 0 auto; padding: 5rem 1.5rem 6rem; }
        .privacy-toc { background: #f7f4ef; border-radius: 16px; padding: 1.75rem 2rem; margin-bottom: 3.5rem; }
        .privacy-toc h3 { font-family: 'Fraunces', serif; font-size: 1rem; font-weight: 700; color: #1c2b19; margin-bottom: 1rem; }
        .privacy-toc ol { margin: 0; padding-left: 1.2rem; }
        .privacy-toc li { font-family: 'Manrope', sans-serif; font-size: 0.9rem; line-height: 1.7; color: #4a5544; }
        .privacy-toc a { color: #4a6741; text-decoration: none; }
        .privacy-toc a:hover { text-decoration: underline; }
        
        .privacy-section { margin-bottom: 3rem; padding-top: 0.5rem; border-top: 1.5px solid #e8f0e0; }
        .privacy-section:first-child { border-top: none; }
        .privacy-section h2 { font-family: 'Fraunces', serif; font-size: 1.45rem; font-weight: 700; color: #1c2b19; margin-bottom: 0.75rem; margin-top: 2rem; }
        .privacy-section p, .privacy-section li { font-family: 'Manrope', sans-serif; font-size: 0.97rem; line-height: 1.85; color: #4a5544; margin-bottom: 0.75rem; }
        .privacy-section ul, .privacy-section ol { padding-left: 1.4rem; margin-bottom: 1rem; }
        .privacy-section li { margin-bottom: 0.4rem; }
        .privacy-section strong { color: #1c2b19; font-weight: 600; }
        
        .privacy-contact-box { background: linear-gradient(135deg, #e8f4e0, #f0e8d4); border-radius: 16px; padding: 2rem 2.25rem; margin-top: 3rem; display: flex; gap: 1.25rem; align-items: flex-start; }
        .privacy-contact-box__icon { width: 48px; height: 48px; background: #4a6741; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #fff; }
        .privacy-contact-box h3 { font-family: 'Fraunces', serif; font-size: 1.1rem; font-weight: 700; color: #1c2b19; margin-bottom: 0.4rem; }
        .privacy-contact-box p { font-family: 'Manrope', sans-serif; font-size: 0.9rem; color: #4a5544; margin: 0; line-height: 1.7; }
        .privacy-contact-box a { color: #4a6741; font-weight: 600; }
        
        @media (max-width: 600px) { .pub-hero { padding: 4.5rem 0 3rem; } .privacy-wrap { padding: 3rem 1rem 4rem; } }
    </style>
</head>
<body>

<!-- ════ NAVIGATION ════ -->
<header class="site-nav" id="top">
    <nav class="nav-inner container" aria-label="Main navigation">
        <a href="<?= url('index.php') ?>" class="nav-logo" aria-label="Home">
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
                <li><a href="<?= url('index.php') ?>">Home</a></li>
                <li><a href="<?= url('about.php') ?>">About Us</a></li>
                <li><a href="<?= url('contact.php') ?>">Contact</a></li>
                <li><a href="<?= url('privacy.php') ?>" class="active">Privacy</a></li>
            </ul>
            <div class="nav-ctas">
                <a href="<?= url('login.php') ?>" class="btn btn-ghost btn-sm">Log In</a>
                <a href="<?= url('register.php') ?>" class="btn btn-primary btn-sm">Get Started</a>
            </div>
        </div>
    </nav>
</header>

<!-- ════ HERO ════ -->
<section class="pub-hero" aria-label="Privacy policy">
    <div class="container">
        <div class="pub-hero__eyebrow">Legal</div>
        <h1>Privacy Policy</h1>
        <p class="pub-hero__meta">Last updated: <?= date('F j, Y') ?></p>
    </div>
</section>

<!-- ════ CONTENT ════ -->
<div class="privacy-wrap">

    <div class="privacy-toc">
        <h3>Contents</h3>
        <ol>
            <li><a href="#section-intro">Introduction</a></li>
            <li><a href="#section-collect">Information We Collect</a></li>
            <li><a href="#section-use">How We Use Your Information</a></li>
            <li><a href="#section-listings">Listing & Reservation Data</a></li>
            <li><a href="#section-cookies">Cookies & Sessions</a></li>
            <li><a href="#section-sharing">Information Sharing</a></li>
            <li><a href="#section-security">Data Security</a></li>
            <li><a href="#section-rights">Your Rights</a></li>
            <li><a href="#section-contact">Contact & Privacy Enquiries</a></li>
        </ol>
    </div>

    <div class="privacy-section" id="section-intro">
        <h2>1. Introduction</h2>
        <p><?= e(setting('site_name', 'ResQFood')) ?> ("we", "our", or "us") is committed to protecting your personal information and your right to privacy. This Privacy Policy explains what information we collect, how we use it, and what rights you have in relation to it.</p>
        <p>By using our platform — including registering an account, posting a food listing, making a reservation, or browsing available food — you agree to the collection and use of information in accordance with this policy.</p>
        <p>If you have any questions or concerns about this policy or our practices, please contact us at <a href="mailto:<?= e(setting('contact_email', 'hello@resqfood.org')) ?>" style="color:#4a6741"><?= e(setting('contact_email', 'hello@resqfood.org')) ?></a>.</p>
    </div>

    <div class="privacy-section" id="section-collect">
        <h2>2. Information We Collect</h2>
        <p>We collect information you provide directly to us when you:</p>
        <ul>
            <li><strong>Register an account</strong> — name, email address, password (encrypted), and role (business, general user, charity).</li>
            <li><strong>Create a food listing</strong> — item name, description, quantity, pickup window, location details, and images.</li>
            <li><strong>Make a reservation</strong> — the listing reserved, timestamp, and status updates.</li>
            <li><strong>Update your profile</strong> — business name, contact details, organisation name, or charity registration details.</li>
            <li><strong>Contact us</strong> — name, email, and message content.</li>
        </ul>
        <p>We also automatically collect certain technical information when you visit our platform, including your IP address, browser type, pages visited, and the time and date of your visit. This information is used for platform security and analytics purposes only.</p>
    </div>

    <div class="privacy-section" id="section-use">
        <h2>3. How We Use Your Information</h2>
        <p>We use the information we collect to:</p>
        <ul>
            <li>Create and manage your account and verify your identity.</li>
            <li>Enable food listing, discovery, reservation, and pickup workflows.</li>
            <li>Communicate platform updates, reservation statuses, and support responses.</li>
            <li>Monitor and moderate platform activity to maintain safety and trust.</li>
            <li>Generate anonymous aggregate impact statistics (such as total meals rescued).</li>
            <li>Improve platform features based on usage patterns.</li>
            <li>Comply with applicable legal obligations.</li>
        </ul>
        <p>We do <strong>not</strong> sell your personal information to third parties. We do not use your data for targeted advertising.</p>
    </div>

    <div class="privacy-section" id="section-listings">
        <h2>4. Listing & Reservation Data</h2>
        <p>Food listings created by businesses are visible to all registered users and charities on the platform. This includes the listing title, description, pickup window, quantity, and location area. <strong>Full street addresses are only visible to confirmed reservation holders.</strong></p>
        <p>Reservation records — including who reserved a listing and when — are retained for platform integrity and impact reporting. Businesses can see the reservation holder's username and role. Users can see their own reservation history.</p>
        <p>Completed pickups contribute to anonymised platform-level statistics (e.g. meals rescued). Individual user data is never publicly attributed in impact reports.</p>
    </div>

    <div class="privacy-section" id="section-cookies">
        <h2>5. Cookies & Sessions</h2>
        <p><?= e(setting('site_name', 'ResQFood')) ?> uses session cookies to maintain your logged-in state while you use the platform. These are first-party, session-scoped cookies that expire when you close your browser or log out.</p>
        <p>We do not use third-party tracking cookies or advertising cookies. We do not integrate with third-party analytics platforms that collect personally identifiable information.</p>
        <p>You can configure your browser to refuse cookies, but doing so will prevent you from logging in or using account features.</p>
    </div>

    <div class="privacy-section" id="section-sharing">
        <h2>6. Information Sharing</h2>
        <p>We do not sell, rent, or trade your personal information. We may share information in the following limited circumstances:</p>
        <ul>
            <li><strong>Between platform participants</strong> — as described in Section 4 above, certain listing and reservation details are shared between businesses, users, and charities as part of the platform workflow.</li>
            <li><strong>With administrators</strong> — platform administrators can access account and activity data for moderation, safety, and support purposes.</li>
            <li><strong>Legal obligations</strong> — we may disclose information if required by law, court order, or to protect the rights and safety of users or the public.</li>
        </ul>
    </div>

    <div class="privacy-section" id="section-security">
        <h2>7. Data Security</h2>
        <p>We implement appropriate technical and organisational measures to protect your personal information against unauthorised access, alteration, disclosure, or destruction. These include:</p>
        <ul>
            <li>Encrypted password storage using industry-standard hashing.</li>
            <li>CSRF token protection on all data-modifying forms.</li>
            <li>Prepared SQL statements to prevent injection attacks.</li>
            <li>Role-based access control — users can only access data appropriate to their role.</li>
        </ul>
        <p>While we take reasonable precautions, no method of transmission or electronic storage is 100% secure. We encourage you to use a strong, unique password for your account.</p>
    </div>

    <div class="privacy-section" id="section-rights">
        <h2>8. Your Rights</h2>
        <p>Depending on your location, you may have rights regarding your personal information, including:</p>
        <ul>
            <li>The right to <strong>access</strong> the personal information we hold about you.</li>
            <li>The right to <strong>correct</strong> inaccurate or incomplete information.</li>
            <li>The right to <strong>delete</strong> your account and associated data.</li>
            <li>The right to <strong>withdraw consent</strong> where processing is based on consent.</li>
        </ul>
        <p>To exercise any of these rights, please contact us at the address below. We will respond to your request within 30 days.</p>
    </div>

    <div class="privacy-section" id="section-contact">
        <h2>9. Contact & Privacy Enquiries</h2>
        <p>If you have any questions, concerns, or requests regarding this Privacy Policy or the handling of your personal data, please contact us:</p>
    </div>

    <div class="privacy-contact-box">
        <div class="privacy-contact-box__icon">
            <svg viewBox="0 0 24 24" width="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="m2 7 10 7 10-7"/></svg>
        </div>
        <div>
            <h3>Privacy enquiries</h3>
            <p>
                <?= e(setting('site_name', 'ResQFood')) ?>
                <?php if (setting('business_address')): ?> · <?= e(setting('business_address')) ?><?php endif; ?><br>
                Email: <a href="mailto:<?= e(setting('contact_email', 'hello@resqfood.org')) ?>"><?= e(setting('contact_email', 'hello@resqfood.org')) ?></a>
                <?php if (setting('contact_phone')): ?><br>Phone: <?= e(setting('contact_phone')) ?><?php endif; ?>
            </p>
        </div>
    </div>

</div>

<!-- ════ FOOTER ════ -->
<?php include __DIR__ . '/partials/public_footer.php'; ?>

<script src="<?= asset('js/main.js') ?>" defer></script>
</body>
</html>
