<?php
declare(strict_types=1);
$googleEnabled = GOOGLE_CLIENT_ID !== '';
?>
<div class="auth-overlay" id="authModal" aria-hidden="true" data-testid="auth-modal">
  <div class="auth-card" role="dialog" aria-modal="true" aria-labelledby="authTitle">
    <button type="button" class="auth-close" data-close-auth aria-label="<?= e(t('common.close')) ?>" data-testid="auth-close">
      <svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg>
    </button>
    <div class="auth-brand">Avrupa<em>pazari</em></div>
    <div class="auth-tabs" role="tablist">
      <button type="button" class="auth-tab active" data-auth-tab="register" role="tab" data-testid="auth-tab-register"><?= e(t('auth.register')) ?></button>
      <button type="button" class="auth-tab" data-auth-tab="login" role="tab" data-testid="auth-tab-login"><?= e(t('auth.login')) ?></button>
    </div>

    <?php if ($googleEnabled): ?>
    <a class="google-btn" href="<?= url('auth/google.php') ?>" data-testid="google-login-button">
      <svg viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9 3.5l6.7-6.7C35.6 2.6 30.2 0 24 0 14.6 0 6.5 5.4 2.6 13.3l7.8 6C12.3 13.6 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.5 5.8c4.4-4.1 7.1-10.1 7.1-17.5z"/><path fill="#FBBC05" d="M10.4 28.7A14.5 14.5 0 0 1 9.5 24c0-1.6.3-3.2.8-4.7l-7.8-6A24 24 0 0 0 0 24c0 3.9.9 7.5 2.6 10.7l7.8-6z"/><path fill="#34A853" d="M24 48c6.2 0 11.6-2 15.4-5.6l-7.5-5.8c-2.1 1.4-4.8 2.3-7.9 2.3-6.3 0-11.7-4.1-13.6-9.8l-7.8 6C6.5 42.6 14.6 48 24 48z"/></svg>
      <span><?= e(t('auth.google')) ?></span>
    </a>
    <div class="auth-divider"><span><?= e(t('common.or')) ?></span></div>
    <?php endif; ?>

    <form class="auth-form active" data-auth-form="register" action="<?= url('auth/register.php') ?>" method="post" novalidate data-testid="register-form">
      <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
      <div class="auth-row">
        <label class="auth-field"><span><?= e(t('auth.first_name')) ?></span><input type="text" name="first_name" required autocomplete="given-name" data-testid="register-first-name"></label>
        <label class="auth-field"><span><?= e(t('auth.last_name')) ?></span><input type="text" name="last_name" required autocomplete="family-name" data-testid="register-last-name"></label>
      </div>
      <label class="auth-field"><span><?= e(t('auth.email')) ?></span><input type="email" name="email" required autocomplete="email" data-testid="register-email"></label>
      <label class="auth-field"><span><?= e(t('auth.password')) ?></span><input type="password" name="password" required minlength="8" autocomplete="new-password" data-testid="register-password"></label>
      <p class="auth-error" data-auth-error hidden data-testid="register-error"></p>
      <button type="submit" class="btn-primary auth-submit" data-testid="register-submit"><?= e(t('auth.create_account')) ?></button>
      <p class="auth-note"><?= e(t('auth.terms_notice')) ?></p>
    </form>

    <form class="auth-form" data-auth-form="login" action="<?= url('auth/login.php') ?>" method="post" novalidate data-testid="login-form">
      <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
      <label class="auth-field"><span><?= e(t('auth.email')) ?></span><input type="email" name="email" required autocomplete="email" data-testid="login-email"></label>
      <label class="auth-field"><span><?= e(t('auth.password')) ?></span><input type="password" name="password" required autocomplete="current-password" data-testid="login-password"></label>
      <p class="auth-error" data-auth-error hidden data-testid="login-error"></p>
      <button type="submit" class="btn-primary auth-submit" data-testid="login-submit"><?= e(t('auth.login')) ?></button>
    </form>
  </div>
</div>
