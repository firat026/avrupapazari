<?php
declare(strict_types=1);
$footerCategories = $navCategories ?? [];
$staticPages = ['about', 'contact', 'privacy', 'terms', 'cookies'];
?>
<footer class="site-footer" data-testid="site-footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="brand-text">Avrupa<em>pazari</em></div>
      <p><?= e(t('footer.description')) ?></p>
      <div class="footer-langs">
        <?php foreach (['tr', 'nl', 'en', 'de'] as $code): ?>
        <a href="<?= e(langUrl($code)) ?>" class="<?= $code === currentLang() ? 'active' : '' ?>"><?= strtoupper($code) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="footer-col">
      <h4><?= e(t('footer.categories')) ?></h4>
      <ul>
        <?php foreach ($footerCategories as $cat): ?>
        <li><a href="<?= e(categoryUrl($cat)) ?>"><?= e($cat['name']) ?></a></li>
        <?php endforeach; ?>
        <li><a href="<?= url('pages/map.php') ?>"><?= e(t('nav.map_search')) ?></a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4><?= e(t('footer.account')) ?></h4>
      <ul>
        <?php if (currentUser()): ?>
        <li><a href="<?= url('account.php') ?>"><?= e(t('nav.my_account')) ?></a></li>
        <li><a href="<?= url('account.php#listings') ?>"><?= e(t('nav.my_listings')) ?></a></li>
        <li><a href="<?= url('auth/logout.php') ?>"><?= e(t('nav.logout')) ?></a></li>
        <?php else: ?>
        <li><a href="#" data-open-auth="login"><?= e(t('auth.login')) ?></a></li>
        <li><a href="#" data-open-auth="register"><?= e(t('auth.register')) ?></a></li>
        <?php endif; ?>
        <li><a href="<?= url('pages/post.php') ?>"><?= e(t('nav.post_ad')) ?></a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4><?= e(t('footer.info')) ?></h4>
      <ul>
        <?php foreach ($staticPages as $slug): ?>
        <li><a href="<?= url('page.php?slug=' . $slug) ?>"><?= e(t('footer.' . $slug)) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="footer-inner">
      <span><?= e(t('footer.copyright', ['year' => date('Y')])) ?></span>
      <span class="footer-contact"><?= e(setting('site_email')) ?></span>
    </div>
  </div>
</footer>
<button type="button" class="scroll-top" id="scrollTop" aria-label="Top" data-testid="scroll-top"><svg viewBox="0 0 24 24"><path d="m18 15-6-6-6 6"/></svg></button>
<script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js" defer></script>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
