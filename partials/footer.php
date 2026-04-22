    </div><!-- /.app-container -->
</main><!-- /.app-main -->

<footer class="app-footer">
    <div class="app-container app-footer__inner">
        <span class="app-footer__brand">
            &copy; <?= date('Y') ?> ResQFood &mdash; Food Redistribution Platform
        </span>
        <nav class="app-footer__links" aria-label="Footer links">
            <a href="<?= baseUrl('index.php') ?>">Home</a>
            <a href="<?= baseUrl('modules/profile/index.php') ?>">My Profile</a>
        </nav>
    </div>
</footer>

<script src="<?= baseUrl('assets/js/app.js') ?>"></script>
</body>
</html>
