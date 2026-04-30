<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

$pdo = db();

// Fetch active team members
$teamMembers = [];
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS team_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(120) NOT NULL,
            role_title VARCHAR(120) DEFAULT '',
            short_description TEXT DEFAULT '',
            image_path VARCHAR(255) DEFAULT '',
            display_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $stmt = $pdo->query("SELECT * FROM team_members WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
    $teamMembers = $stmt->fetchAll();
} catch (Throwable $e) {
    // graceful fallback
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us — <?= e(setting('site_name', 'ResQFood')) ?></title>
    <meta name="description" content="Learn about <?= e(setting('site_name', 'ResQFood')) ?> — our mission, our story, and the team working to reduce food waste and connect communities.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,700;1,9..144,500;1,9..144,600&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <style>
        /* ── Page-level overrides ── */
        .pub-hero {
            background: linear-gradient(160deg, #1a2a17 0%, #2e3f2a 40%, #3d5436 100%);
            color: #fff;
            padding: 9rem 0 6rem;
            position: relative;
            overflow: hidden;
            min-height: 380px;
            display: flex;
            align-items: center;
        }
        .pub-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=1400&q=60') center/cover no-repeat;
            opacity: 0.08;
            pointer-events: none;
        }
        .pub-hero .container { position: relative; z-index: 1; width: 100%; }
        .pub-hero__eyebrow { font-family: 'Manrope', sans-serif; font-size: 0.8rem; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #a8d49a; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .pub-hero__eyebrow::before { content: ''; width: 24px; height: 2px; background: #a8d49a; border-radius: 2px; }
        .pub-hero h1 { font-family: 'Fraunces', serif; font-size: clamp(2.4rem, 5vw, 3.8rem); font-weight: 700; line-height: 1.15; margin-bottom: 1.25rem; color: #ffffff; text-shadow: 0 2px 20px rgba(0,0,0,0.4); }
        .pub-hero h1 em { font-style: italic; color: #b8e0a8; }
        .pub-hero__sub { font-family: 'Manrope', sans-serif; font-size: 1.1rem; line-height: 1.7; color: rgba(255,255,255,0.88); max-width: 560px; text-shadow: 0 1px 6px rgba(0,0,0,0.3); }
        
        .pub-section { padding: 5rem 0; }
        .pub-section--alt { background: #f7f4ef; }
        .pub-section--dark { background: #2e3f2a; color: #fff; }
        
        .section-eyebrow { font-family: 'Manrope', sans-serif; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; color: #4a6741; margin-bottom: 0.75rem; }
        .pub-section--dark .section-eyebrow { color: rgba(200,225,190,0.8); }
        
        .pub-two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center; }
        .pub-two-col--rev { direction: rtl; }
        .pub-two-col--rev > * { direction: ltr; }
        
        .pub-img-frame { border-radius: 20px; overflow: hidden; aspect-ratio: 4/3; }
        .pub-img-frame img { width: 100%; height: 100%; object-fit: cover; display: block; }
        
        .pub-section h2 { font-family: 'Fraunces', serif; font-size: clamp(1.8rem, 3.5vw, 2.6rem); font-weight: 700; line-height: 1.2; margin-bottom: 1rem; color: #1c2b19; }
        .pub-section--dark h2 { color: #e8f0e4; }
        .pub-section p { font-family: 'Manrope', sans-serif; font-size: 1rem; line-height: 1.8; color: #4a5544; margin-bottom: 1rem; }
        .pub-section--dark p { color: rgba(200,225,190,0.8); }
        
        .stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-top: 2.5rem; }
        .stat-item { text-align: center; padding: 1.5rem; background: rgba(74,103,65,0.07); border-radius: 16px; }
        .stat-item strong { display: block; font-family: 'Fraunces', serif; font-size: 2rem; font-weight: 700; color: #4a6741; }
        .stat-item span { font-family: 'Manrope', sans-serif; font-size: 0.85rem; color: #6a7a64; }
        
        /* Values */
        .values-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-top: 2rem; }
        .value-card { background: rgba(255,255,255,0.08); border: 1px solid rgba(200,225,190,0.15); border-radius: 16px; padding: 1.75rem; }
        .value-card__icon { width: 44px; height: 44px; border-radius: 12px; background: rgba(200,225,190,0.12); display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; color: rgba(200,225,190,0.9); }
        .value-card h3 { font-family: 'Fraunces', serif; font-size: 1.15rem; font-weight: 600; color: #e8f0e4; margin-bottom: 0.5rem; }
        .value-card p { font-size: 0.9rem; color: rgba(200,225,190,0.7); margin: 0; line-height: 1.7; }
        
        /* Team */
        .team-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 2rem; margin-top: 2.5rem; }
        .team-card { background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 20px rgba(30,50,20,0.08); transition: transform 0.25s, box-shadow 0.25s; }
        .team-card:hover { transform: translateY(-4px); box-shadow: 0 12px 36px rgba(30,50,20,0.14); }
        .team-card__photo { width: 100%; aspect-ratio: 1/1; overflow: hidden; background: #e8f0e4; }
        .team-card__photo img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.4s; }
        .team-card:hover .team-card__photo img { transform: scale(1.04); }
        .team-card__photo-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #c8e0c0, #e8f0e4); }
        .team-card__photo-placeholder svg { opacity: 0.4; }
        .team-card__body { padding: 1.25rem 1.5rem 1.5rem; }
        .team-card__name { font-family: 'Fraunces', serif; font-size: 1.1rem; font-weight: 700; color: #1c2b19; margin-bottom: 0.25rem; }
        .team-card__role { font-family: 'Manrope', sans-serif; font-size: 0.78rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #4a6741; margin-bottom: 0.75rem; }
        .team-card__bio { font-family: 'Manrope', sans-serif; font-size: 0.88rem; line-height: 1.65; color: #6a7a64; }
        
        .team-empty { text-align: center; padding: 3rem; background: #f0e8d4; border-radius: 20px; color: #6a7a64; font-family: 'Manrope', sans-serif; }
        
        @media (max-width: 900px) {
            .pub-two-col { grid-template-columns: 1fr; gap: 2rem; }
            .pub-two-col--rev { direction: ltr; }
            .stat-grid { grid-template-columns: repeat(2, 1fr); }
            .values-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 600px) {
            .pub-hero { padding: 5rem 0 3.5rem; }
            .stat-grid, .values-grid { grid-template-columns: 1fr; }
            .team-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 420px) {
            .team-grid { grid-template-columns: 1fr; }
        }
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
                <li><a href="<?= url('about.php') ?>" class="active">About Us</a></li>
                <li><a href="<?= url('contact.php') ?>">Contact</a></li>
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
<section class="pub-hero" aria-label="About us introduction">
    <div class="container">
        <div class="pub-hero__eyebrow">Our Story</div>
        <h1>Rescuing food,<br><em>building community.</em></h1>
        <p class="pub-hero__sub">We believe good food should never go to waste while people nearby go without. <?= e(setting('site_name', 'ResQFood')) ?> was built to bridge that gap — systematically, transparently, and at scale.</p>
    </div>
</section>

<!-- ════ WHO WE ARE ════ -->
<section class="pub-section">
    <div class="container">
        <div class="pub-two-col">
            <div>
                <p class="section-eyebrow">Who We Are</p>
                <h2>A platform built around one simple idea.</h2>
                <p><?= e(setting('site_name', 'ResQFood')) ?> is a food redistribution platform that connects businesses with surplus food to the people and charities who need it most. We provide the infrastructure — the listings, the reservations, the pickup tracking — so that surplus becomes an opportunity rather than a cost.</p>
                <p>Our platform serves food businesses, local community users, and charitable organisations with a coordinated, role-based workflow that makes every exchange simple, reliable, and transparent.</p>
                <div class="stat-grid">
                    <div class="stat-item"><strong>1,240+</strong><span>Meals rescued</span></div>
                    <div class="stat-item"><strong>386</strong><span>Listings shared</span></div>
                    <div class="stat-item"><strong>42+</strong><span>Active partners</span></div>
                </div>
            </div>
            <div class="pub-img-frame">
                <img src="https://images.unsplash.com/photo-1593113598332-cd288d649433?auto=format&fit=crop&w=800&q=80" alt="Volunteers working together" loading="lazy">
            </div>
        </div>
    </div>
</section>

<!-- ════ MISSION ════ -->
<section class="pub-section pub-section--alt">
    <div class="container">
        <div class="pub-two-col pub-two-col--rev">
            <div>
                <p class="section-eyebrow">The Problem We Solve</p>
                <h2>Every day, tonnes of edible food disappear into waste.</h2>
                <p>Restaurants, bakeries, cafes, and food retailers prepare more than they sell. Without a coordinated system to share what remains, that surplus goes to landfill — while communities nearby go without.</p>
                <p>The missing ingredient is not food. It is <strong>coordination</strong>. <?= e(setting('site_name', 'ResQFood')) ?> is that coordination layer — a structured, trackable system that turns leftover into listed, available into reserved, and reserved into collected.</p>
            </div>
            <div class="pub-img-frame">
                <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=800&q=80" alt="Food ready to be shared" loading="lazy">
            </div>
        </div>
    </div>
</section>

<!-- ════ VALUES ════ -->
<section class="pub-section pub-section--dark">
    <div class="container">
        <div style="text-align:center;max-width:540px;margin:0 auto 1.5rem">
            <p class="section-eyebrow">What Drives Us</p>
            <h2>Three commitments, one mission.</h2>
        </div>
        <div class="values-grid">
            <div class="value-card">
                <div class="value-card__icon">
                    <svg viewBox="0 0 24 24" width="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h3>Trusted by design</h3>
                <p>Role-based access, verified accounts, and administrative moderation ensure every exchange is dependable and accountable.</p>
            </div>
            <div class="value-card">
                <div class="value-card__icon">
                    <svg viewBox="0 0 24 24" width="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </div>
                <h3>Practical for real operations</h3>
                <p>Built around actual redistribution steps — listing, reserving, scheduling, and collecting — not abstract features or theory.</p>
            </div>
            <div class="value-card">
                <div class="value-card__icon">
                    <svg viewBox="0 0 24 24" width="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 3v18h18"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/></svg>
                </div>
                <h3>Local impact at scale</h3>
                <p>Every completed pickup is tracked, counted, and contributes to a growing record of measurable social and environmental value.</p>
            </div>
        </div>
    </div>
</section>

<!-- ════ TEAM ════ -->
<section class="pub-section">
    <div class="container">
        <div style="text-align:center;max-width:500px;margin:0 auto 0.5rem">
            <p class="section-eyebrow">The People Behind It</p>
            <h2>Meet our team.</h2>
            <p style="color:#6a7a64;font-family:'Manrope',sans-serif;font-size:1rem;line-height:1.7">A dedicated group of people passionate about food sustainability, community welfare, and practical technology.</p>
        </div>

        <?php if (!empty($teamMembers)): ?>
        <div class="team-grid">
            <?php foreach ($teamMembers as $member): ?>
            <div class="team-card">
                <div class="team-card__photo">
                    <?php if (!empty($member['image_path'])): ?>
                        <img src="<?= url($member['image_path']) ?>" alt="<?= e($member['full_name']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="team-card__photo-placeholder">
                            <svg viewBox="0 0 64 64" width="56" fill="none" stroke="#4a6741" stroke-width="1.5">
                                <circle cx="32" cy="24" r="12"/>
                                <path d="M8 56c0-13.3 10.7-24 24-24s24 10.7 24 24"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="team-card__body">
                    <div class="team-card__name"><?= e($member['full_name']) ?></div>
                    <?php if (!empty($member['role_title'])): ?>
                        <div class="team-card__role"><?= e($member['role_title']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($member['short_description'])): ?>
                        <p class="team-card__bio"><?= e($member['short_description']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="team-empty">
            <svg viewBox="0 0 48 48" width="48" fill="none" stroke="#4a6741" stroke-width="1.5" style="margin-bottom:1rem;opacity:0.5"><circle cx="24" cy="18" r="9"/><path d="M6 42c0-9.9 8.1-18 18-18s18 8.1 18 18"/></svg>
            <p style="margin:0;font-size:0.95rem">Team members will appear here once added by the admin.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ════ FOOTER ════ -->
<?php include __DIR__ . '/partials/public_footer.php'; ?>

<script src="<?= asset('js/main.js') ?>" defer></script>
</body>
</html>
