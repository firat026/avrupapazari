<?php
/**
 * includes/footer.php
 * Konum: /includes/footer.php
 */
?>

<!-- FOOTER -->
<footer class="site-footer">
  <div class="footer-brand">
    <div class="footer-logo">avrupa<em>pazari</em></div>
    <p class="footer-desc"><?= t('footer.description') ?></p>
  </div>
  <div>
    <div class="footer-col-title"><?= t('footer.categories') ?></div>
    <ul class="footer-links">
      <li><a href="<?= BASE_URL ?>/esnaf-rehberi.php"><?= t('nav.business_dir') ?></a></li>
      <li><a href="<?= BASE_URL ?>/ikinci-el.php"><?= t('nav.second_hand') ?></a></li>
      <li><a href="<?= BASE_URL ?>/emlak.php"><?= t('nav.real_estate') ?></a></li>
      <li><a href="<?= BASE_URL ?>/araclar.php"><?= t('nav.vehicles') ?></a></li>
      <li><a href="<?= BASE_URL ?>/reklam.php"><?= t('footer.ads') ?></a></li>
    </ul>
  </div>
  <div>
    <div class="footer-col-title"><?= t('footer.account') ?></div>
    <ul class="footer-links">
      <li><a href="<?= BASE_URL ?>/giris.php"><?= t('nav.login') ?></a></li>
      <li><a href="<?= BASE_URL ?>/kayit.php"><?= t('nav.register') ?></a></li>
      <li><a href="<?= BASE_URL ?>/ilan-ver.php"><?= t('nav.post_ad') ?></a></li>
      <li><a href="<?= BASE_URL ?>/profil.php"><?= t('footer.my_profile') ?></a></li>
    </ul>
  </div>
  <div>
    <div class="footer-col-title"><?= t('footer.info') ?></div>
    <ul class="footer-links">
      <li><a href="<?= BASE_URL ?>/hakkimizda.php"><?= t('footer.about') ?></a></li>
      <li><a href="<?= BASE_URL ?>/iletisim.php"><?= t('footer.contact') ?></a></li>
      <li><a href="<?= BASE_URL ?>/gizlilik.php"><?= t('footer.privacy') ?></a></li>
      <li><a href="<?= BASE_URL ?>/sartlar.php"><?= t('footer.terms') ?></a></li>
    </ul>
  </div>
</footer>
<div class="footer-bottom">
  <span><?= t('footer.copyright', ['year' => date('Y')]) ?></span>
  <div class="footer-bottom-links">
    <a href="<?= BASE_URL ?>/gizlilik.php"><?= t('footer.privacy_short') ?></a>
    <a href="<?= BASE_URL ?>/sartlar.php"><?= t('footer.terms_short') ?></a>
    <a href="<?= BASE_URL ?>/cerezler.php"><?= t('footer.cookies') ?></a>
  </div>
</div>

<script>
function toggleTheme() {
  var html = document.documentElement;
  var current = html.getAttribute('data-theme');
  var next = current === 'light' ? 'dark' : 'light';
  html.setAttribute('data-theme', next);
  fetch('<?= BASE_URL ?>/api/set-theme.php?theme=' + next);
}
</script>
<!-- Scroll to top button -->
<button type="button" id="scrollTopBtn" aria-label="Scroll to top" style="position:fixed;bottom:28px;right:28px;width:44px;height:44px;border-radius:12px;border:none;background:rgba(22,163,74,0.9);color:#fff;cursor:pointer;display:none;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(22,163,74,0.3);backdrop-filter:blur(8px);transition:opacity 0.3s,transform 0.3s;z-index:999;opacity:0;transform:translateY(10px);"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 15l-6-6-6 6"/></svg></button>
<script>
(function(){var btn=document.getElementById('scrollTopBtn');if(!btn)return;window.addEventListener('scroll',function(){if(window.scrollY>300){btn.style.display='flex';setTimeout(function(){btn.style.opacity='1';btn.style.transform='translateY(0)';},10);}else{btn.style.opacity='0';btn.style.transform='translateY(10px)';setTimeout(function(){if(window.scrollY<=300)btn.style.display='none';},300);}});btn.addEventListener('click',function(){window.scrollTo({top:0,behavior:'smooth'});});})();
</script>
</body>
</html>
