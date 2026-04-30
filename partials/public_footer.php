<?php
/**
 * Shared footer for public-facing pages (index, about, contact, privacy)
 * Uses dynamic settings for all brand/contact/social content.
 */
?>
<footer class="site-footer" id="contact">
    <div class="container footer-inner">

        <div class="footer-brand">
            <a href="<?= url('index.php') ?>" class="nav-logo" aria-label="Home">
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
                <h4>Company</h4>
                <ul>
                    <li><a href="<?= url('about.php') ?>">About Us</a></li>
                    <li><a href="<?= url('contact.php') ?>">Contact Us</a></li>
                    <li><a href="<?= url('privacy.php') ?>">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Platform</h4>
                <ul>
                    <li><a href="<?= url('register.php') ?>">Register a business</a></li>
                    <li><a href="<?= url('register.php') ?>">Join as charity</a></li>
                    <li><a href="<?= url('login.php') ?>">Log in</a></li>
                </ul>
            </div>
        </nav>

    </div>
    <div class="footer-base">
        <div class="container footer-base-inner">
            <small><?= e(setting('copyright_text', '© ' . date('Y') . ' ResQFood. All rights reserved.')) ?></small>
            <div class="footer-base-links">
                <a href="<?= url('privacy.php') ?>">Privacy</a>
                <a href="<?= url('contact.php') ?>">Contact</a>
            </div>
        </div>
    </div>
</footer>
