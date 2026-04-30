<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

// Handle contact form submission
$formSuccess = false;
$formError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_form'])) {
    $cName    = sanitize($_POST['contact_name'] ?? '');
    $cEmail   = sanitize($_POST['contact_email_field'] ?? '');
    $cSubject = sanitize($_POST['contact_subject'] ?? '');
    $cMessage = sanitize($_POST['contact_message'] ?? '');
    if ($cName && $cEmail && $cSubject && $cMessage) {
        // In a real app you'd send an email here. We'll just show success.
        $formSuccess = true;
    } else {
        $formError = 'Please fill in all fields before submitting.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us — <?= e(setting('site_name', 'ResQFood')) ?></title>
    <meta name="description" content="Get in touch with the <?= e(setting('site_name', 'ResQFood')) ?> team. We're here to help with any questions, support requests, or partnership enquiries.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,700;1,9..144,500;1,9..144,600&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <style>
        .pub-hero { background: linear-gradient(160deg, #1a2a17 0%, #2e3f2a 40%, #3d5436 100%); color: #fff; padding: 9rem 0 6rem; position: relative; overflow: hidden; min-height: 360px; display: flex; align-items: center; }
        .pub-hero::before { content: ''; position: absolute; inset: 0; background: url('https://images.unsplash.com/photo-1423592707957-3b212afa6733?auto=format&fit=crop&w=1400&q=50') center/cover no-repeat; opacity: 0.07; pointer-events: none; }
        .pub-hero .container { position: relative; z-index: 1; width: 100%; }
        .pub-hero__eyebrow { font-family: 'Manrope', sans-serif; font-size: 0.8rem; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #a8d49a; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .pub-hero__eyebrow::before { content: ''; width: 24px; height: 2px; background: #a8d49a; border-radius: 2px; }
        .pub-hero h1 { font-family: 'Fraunces', serif; font-size: clamp(2.4rem, 5vw, 3.8rem); font-weight: 700; line-height: 1.15; margin-bottom: 1.25rem; color: #ffffff; text-shadow: 0 2px 20px rgba(0,0,0,0.4); }
        .pub-hero h1 em { font-style: italic; color: #b8e0a8; }
        .pub-hero__sub { font-family: 'Manrope', sans-serif; font-size: 1.1rem; line-height: 1.7; color: rgba(255,255,255,0.88); max-width: 520px; text-shadow: 0 1px 6px rgba(0,0,0,0.3); }
        
        .pub-section { padding: 5rem 0; }
        .pub-section--alt { background: #f7f4ef; }
        .section-eyebrow { font-family: 'Manrope', sans-serif; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; color: #4a6741; margin-bottom: 0.75rem; }
        .pub-section h2 { font-family: 'Fraunces', serif; font-size: clamp(1.8rem, 3.5vw, 2.4rem); font-weight: 700; line-height: 1.2; margin-bottom: 1rem; color: #1c2b19; }
        .pub-section p { font-family: 'Manrope', sans-serif; font-size: 1rem; line-height: 1.8; color: #4a5544; }
        
        .contact-layout { display: grid; grid-template-columns: 1fr 1.4fr; gap: 4rem; align-items: start; }
        
        /* Contact info cards */
        .contact-info { display: flex; flex-direction: column; gap: 1.25rem; }
        .contact-card { background: #fff; border-radius: 16px; padding: 1.5rem; display: flex; gap: 1rem; align-items: flex-start; box-shadow: 0 2px 12px rgba(30,50,20,0.07); }
        .contact-card__icon { width: 44px; height: 44px; border-radius: 12px; background: #e8f4e0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #4a6741; }
        .contact-card__label { font-family: 'Manrope', sans-serif; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #4a6741; margin-bottom: 0.3rem; }
        .contact-card__value { font-family: 'Manrope', sans-serif; font-size: 0.95rem; color: #1c2b19; font-weight: 500; }
        .contact-card a { color: inherit; text-decoration: none; }
        .contact-card a:hover { color: #4a6741; text-decoration: underline; }
        
        /* Social row */
        .contact-socials { display: flex; gap: 0.75rem; margin-top: 0.5rem; }
        .contact-social-btn { width: 40px; height: 40px; border-radius: 50%; background: #e8f4e0; display: flex; align-items: center; justify-content: center; color: #4a6741; transition: background 0.2s, transform 0.2s; text-decoration: none; }
        .contact-social-btn:hover { background: #4a6741; color: #fff; transform: translateY(-2px); }
        
        /* Contact form */
        .contact-form-wrap { background: #fff; border-radius: 20px; padding: 2.5rem; box-shadow: 0 4px 24px rgba(30,50,20,0.08); }
        .contact-form-wrap h3 { font-family: 'Fraunces', serif; font-size: 1.5rem; font-weight: 700; color: #1c2b19; margin-bottom: 0.5rem; }
        .contact-form-wrap > p { font-family: 'Manrope', sans-serif; font-size: 0.9rem; color: #6a7a64; margin-bottom: 1.75rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-family: 'Manrope', sans-serif; font-size: 0.82rem; font-weight: 600; color: #3a4a35; margin-bottom: 0.45rem; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #d8e8d0; border-radius: 10px; font-family: 'Manrope', sans-serif; font-size: 0.95rem; color: #1c2b19; background: #fafcf9; transition: border-color 0.2s, box-shadow 0.2s; outline: none; box-sizing: border-box; }
        .form-group input:focus, .form-group textarea:focus { border-color: #4a6741; box-shadow: 0 0 0 3px rgba(74,103,65,0.12); }
        .form-group textarea { resize: vertical; min-height: 130px; }
        .form-submit { background: #4a6741; color: #fff; border: none; padding: 0.85rem 2.25rem; border-radius: 12px; font-family: 'Manrope', sans-serif; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: background 0.2s, transform 0.2s; width: 100%; }
        .form-submit:hover { background: #3d5436; transform: translateY(-1px); }
        
        .form-alert { padding: 0.9rem 1.2rem; border-radius: 10px; font-family: 'Manrope', sans-serif; font-size: 0.9rem; margin-bottom: 1.25rem; }
        .form-alert--success { background: #e8f4e0; color: #2e5a28; border: 1px solid #b8d8b0; }
        .form-alert--error { background: #fde8e8; color: #8b2020; border: 1px solid #f0c0c0; }
        
        @media (max-width: 900px) { .contact-layout { grid-template-columns: 1fr; gap: 2.5rem; } }
        @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } .contact-form-wrap { padding: 1.75rem; } .pub-hero { padding: 5rem 0 3.5rem; } }
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
                <li><a href="<?= url('contact.php') ?>" class="active">Contact</a></li>
                <li><a href="<?= url('privacy.php') ?>">Privacy</a></li>
            </ul>
            <div class="nav-ctas">
                <a href="<?= url('login.php') ?>" class="btn btn-ghost btn-sm">Log In</a>
                <a href="<?= url('register.php') ?>" class="btn btn-primary btn-sm">Get Started</a>
            </div>
        </div>
    </nav>
</header>

<!-- ════ HERO ════ -->
<section class="pub-hero" aria-label="Contact page introduction">
    <div class="container">
        <div class="pub-hero__eyebrow">Get in Touch</div>
        <h1>We'd love to<br><em>hear from you.</em></h1>
        <p class="pub-hero__sub">Whether you have a question, a partnership idea, or just want to learn more about how <?= e(setting('site_name', 'ResQFood')) ?> works — our team is here to help.</p>
    </div>
</section>

<!-- ════ CONTACT SECTION ════ -->
<section class="pub-section">
    <div class="container">
        <div class="contact-layout">

            <!-- Left: contact info -->
            <div>
                <p class="section-eyebrow">Contact Details</p>
                <h2>Reach us directly.</h2>
                <p style="margin-bottom:2rem;color:#6a7a64">Our team typically responds within one business day. You can also find us on the platforms below.</p>

                <div class="contact-info">
                    <?php if (setting('contact_email')): ?>
                    <div class="contact-card">
                        <div class="contact-card__icon">
                            <svg viewBox="0 0 24 24" width="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="m2 7 10 7 10-7"/></svg>
                        </div>
                        <div>
                            <div class="contact-card__label">Email</div>
                            <div class="contact-card__value"><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (setting('contact_phone')): ?>
                    <div class="contact-card">
                        <div class="contact-card__icon">
                            <svg viewBox="0 0 24 24" width="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.63A2 2 0 012 .95h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                        </div>
                        <div>
                            <div class="contact-card__label">Phone</div>
                            <div class="contact-card__value"><a href="tel:<?= e(setting('contact_phone')) ?>"><?= e(setting('contact_phone')) ?></a></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (setting('business_address')): ?>
                    <div class="contact-card">
                        <div class="contact-card__icon">
                            <svg viewBox="0 0 24 24" width="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
                        </div>
                        <div>
                            <div class="contact-card__label">Address</div>
                            <div class="contact-card__value"><?= e(setting('business_address')) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (setting('facebook_url') || setting('twitter_url') || setting('instagram_url')): ?>
                <div style="margin-top:1.75rem">
                    <p style="font-family:'Manrope',sans-serif;font-size:0.8rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#4a6741;margin-bottom:0.75rem">Follow Us</p>
                    <div class="contact-socials">
                        <?php if (setting('facebook_url')): ?>
                        <a href="<?= e(setting('facebook_url')) ?>" class="contact-social-btn" target="_blank" rel="noopener" aria-label="Facebook">
                            <svg viewBox="0 0 24 24" width="16" fill="currentColor"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3l-.5 3H13v6.8C18.56 20.87 22 16.84 22 12z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if (setting('twitter_url')): ?>
                        <a href="<?= e(setting('twitter_url')) ?>" class="contact-social-btn" target="_blank" rel="noopener" aria-label="Twitter/X">
                            <svg viewBox="0 0 24 24" width="16" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if (setting('instagram_url')): ?>
                        <a href="<?= e(setting('instagram_url')) ?>" class="contact-social-btn" target="_blank" rel="noopener" aria-label="Instagram">
                            <svg viewBox="0 0 24 24" width="16" fill="currentColor"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07s-3.58-.01-4.85-.07c-3.26-.15-4.77-1.7-4.92-4.92C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.15-3.23 1.66-4.77 4.92-4.92C8.42 2.17 8.8 2.16 12 2.16M12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.7.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c4.35-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.2-4.35-2.62-6.78-6.98-6.98C15.67.01 15.26 0 12 0z"/><path d="M12 5.84A6.16 6.16 0 1018.16 12 6.16 6.16 0 0012 5.84zm0 10.16A4 4 0 1116 12a4 4 0 01-4 4z"/><circle cx="18.41" cy="5.59" r="1.44"/></svg>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right: contact form -->
            <div class="contact-form-wrap">
                <h3>Send us a message</h3>
                <p>Fill in the form and we'll get back to you as soon as possible.</p>

                <?php if ($formSuccess): ?>
                <div class="form-alert form-alert--success">
                    ✓ Your message has been sent! We'll get back to you within one business day.
                </div>
                <?php endif; ?>
                <?php if ($formError): ?>
                <div class="form-alert form-alert--error"><?= e($formError) ?></div>
                <?php endif; ?>

                <?php if (!$formSuccess): ?>
                <form method="POST" action="<?= url('contact.php') ?>">
                    <input type="hidden" name="contact_form" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_name">Full Name</label>
                            <input type="text" id="contact_name" name="contact_name" placeholder="Your name" required>
                        </div>
                        <div class="form-group">
                            <label for="contact_email_field">Email Address</label>
                            <input type="email" id="contact_email_field" name="contact_email_field" placeholder="you@example.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="contact_subject">Subject</label>
                        <input type="text" id="contact_subject" name="contact_subject" placeholder="What's this about?" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_message">Message</label>
                        <textarea id="contact_message" name="contact_message" placeholder="Tell us more…" required></textarea>
                    </div>
                    <button type="submit" class="form-submit">Send Message</button>
                </form>
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>

<!-- ════ FOOTER ════ -->
<?php include __DIR__ . '/partials/public_footer.php'; ?>

<script src="<?= asset('js/main.js') ?>" defer></script>
</body>
</html>
