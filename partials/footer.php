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
            <strong style="color:rgba(200,225,190,0.85);font-size:0.85rem">ResQFood</strong>
            <span style="opacity:.45;margin:0 .25rem">-</span>
            <span>&copy; <?= date('Y') ?> Reducing food waste, one rescue at a time.</span>
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

<script src="<?= baseUrl('assets/js/app.js') ?>"></script>
</body>
</html>
