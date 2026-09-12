<?php if (!isset($L)) { require_once __DIR__ . '/../config.php'; } ?>
<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-col">
      <div class="brand footer-brand">
        <span class="brand-primary">Avrupa</span><span class="brand-secondary">pazarı</span>
      </div>
      <p class="muted"><?= t('footer_about') ?></p>
    </div>
    <div class="footer-col">
      <h4><?= t('footer_categories') ?></h4>
      <ul>
        <li><a href="pages/vasitalar.php"><?= t('vasitalar') ?></a></li>
        <li><a href="pages/emlak.php"><?= t('emlak') ?></a></li>
        <li><a href="pages/ikinci-el.php"><?= t('ikinci_el') ?></a></li>
        <li><a href="pages/hizmetler.php"><?= t('hizmetler') ?></a></li>
        <li><a href="pages/is-ilanlari.php"><?= t('is_ilanlari') ?></a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4><?= t('footer_help') ?></h4>
      <ul>
        <li><a href="#">FAQ</a></li>
        <li><a href="#">Blog</a></li>
        <li><a href="#">Kullanım Koşulları</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4><?= t('footer_contact') ?></h4>
      <ul>
        <li>info@avrupapazari.eu</li>
        <li>+31 (0) 20 000 00 00</li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom container">
    <span>© <?= date('Y') ?> Avrupapazarı. <?= t('footer_rights') ?></span>
  </div>
</footer>
