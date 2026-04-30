    </div><!-- /.app-container -->
</main><!-- /.app-main -->

<!-- ═══════════════════════════════════════════════════════════
     FOOTER
═══════════════════════════════════════════════════════════ -->
<footer class="app-footer" role="contentinfo">
    <div class="app-footer__inner">
        <div style="display:flex;align-items:center;gap:0.5rem;color:rgba(200,225,190,0.75)">
            <svg viewBox="0 0 20 20" width="16" fill="none" aria-hidden="true">
                <path d="M10 2C6 2 2 6 2 10.5c0 3.5 2.5 6.5 8 7V2z" fill="rgba(200,225,190,0.7)"/>
                <path d="M10 2c4 0 8 4 8 8.5 0 3.5-2.5 6.5-8 7V2z" fill="rgba(200,225,190,0.4)"/>
            </svg>
            <strong style="color:rgba(200,225,190,0.85);font-size:0.85rem"><?= e(setting('site_name', 'ResQFood')) ?></strong>
            <span style="opacity:.45;margin:0 .25rem">-</span>
            <span><?= e(setting('copyright_text', '© ' . date('Y') . ' All rights reserved.')) ?></span>
        </div>
        
        <?php if (setting('facebook_url') || setting('twitter_url') || setting('instagram_url')): ?>
        <div style="display:flex;align-items:center;gap:1.25rem;margin-top:0.75rem">
            <?php if (setting('facebook_url')): ?><a href="<?= e(setting('facebook_url')) ?>" style="color:rgba(200,225,190,0.85);transition:color 0.2s" aria-label="Facebook"><svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-1.1 0-2 .9-2 2v1h3l-1 3h-2v6.8C18.56 20.87 22 16.84 22 12z"/></svg></a><?php endif; ?>
            <?php if (setting('twitter_url')): ?><a href="<?= e(setting('twitter_url')) ?>" style="color:rgba(200,225,190,0.85);transition:color 0.2s" aria-label="Twitter"><svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a><?php endif; ?>
            <?php if (setting('instagram_url')): ?><a href="<?= e(setting('instagram_url')) ?>" style="color:rgba(200,225,190,0.85);transition:color 0.2s" aria-label="Instagram"><svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07s-3.58-.01-4.85-.07c-3.26-.15-4.77-1.7-4.92-4.92-.06-1.27-.07-1.64-.07-4.85s.01-3.58.07-4.85c.15-3.23 1.66-4.77 4.92-4.92 1.27-.06 1.64-.07 4.85-.07m0-2.16C8.74 0 8.33.01 7.05.07c-4.36.2-6.78 2.62-6.98 6.98C0 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98 1.28.06 1.69.07 4.95.07s3.67-.01 4.95-.07c4.36-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.2-4.36-2.62-6.78-6.98-6.98-1.28-.06-1.69-.07-4.95-.07z"/><path d="M12 5.84A6.16 6.16 0 1018.16 12 6.16 6.16 0 0012 5.84zm0 10.16A4 4 0 1116 12a4 4 0 01-4 4z"/><circle cx="18.41" cy="5.59" r="1.44"/></svg></a><?php endif; ?>
        </div>
        <?php endif; ?>
        </div>

        <nav class="app-footer__links" aria-label="Footer navigation">
            <a href="<?= baseUrl('index.php') ?>">Home</a>
            <?php if (isLoggedIn()): ?>
            <a href="<?= baseUrl('dashboard.php') ?>">Dashboard</a>
            <?php if (in_array(currentUserRole(), ['general_user', 'charity'])): ?>
                <a href="<?= baseUrl('modules/listings/browse.php') ?>">Browse Food</a>
            <?php endif; ?>
            <?php if (currentUserRole() === 'admin'): ?>
                <a href="<?= baseUrl('modules/admin/dashboard.php') ?>">Admin</a>
            <?php endif; ?>
            <?php else: ?>
            <a href="<?= baseUrl('login.php') ?>">Login</a>
            <a href="<?= baseUrl('register.php') ?>">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</footer>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
