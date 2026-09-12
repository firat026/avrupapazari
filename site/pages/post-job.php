<?php
declare(strict_types=1);
require_once __DIR__ . '/../functions.php';
$user = requireLogin();
$pdo = getDB();
$lang = currentLang();

$jobCategoryId = (int)$pdo->query("SELECT id FROM categories WHERE module = 'jobs' AND parent_id IS NULL LIMIT 1")->fetchColumn();
$nameCol = ['tr' => 'name_tr', 'nl' => 'name_nl', 'en' => 'name_en', 'de' => 'name_de'][$lang] ?? 'name_en';
$countries = $pdo->query("SELECT id, code, {$nameCol} AS name FROM countries WHERE is_active = 1 ORDER BY sort_order, id")->fetchAll();
$sectors = array_map('trim', explode(',', t('job.sectors')));
$employments = ['fulltime', 'parttime', 'temporary', 'freelance'];

$prefill = ['country_id' => (int)($_GET['country_id'] ?? 0), 'city_id' => (int)($_GET['city_id'] ?? 0)];
$errors = [];
$old = ['job_type' => 'offer', 'title' => '', 'sector' => '', 'salary' => '', 'employment' => 'fulltime', 'description' => '', 'country_id' => $prefill['country_id'], 'city_id' => $prefill['city_id']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) { $old[$k] = trim((string)($_POST[$k] ?? '')); }
    $old['country_id'] = (int)$old['country_id'];
    $old['city_id'] = (int)$old['city_id'];
    if (!csrfValid($_POST['csrf'] ?? null)) $errors[] = t('auth.session_expired');
    if (!in_array($old['job_type'], ['offer', 'seek'], true)) $old['job_type'] = 'offer';
    if (!in_array($old['employment'], $employments, true)) $old['employment'] = 'fulltime';
    if (mb_strlen($old['title']) < 5) $errors[] = t('job.title_label') . ': ' . t('common.required');
    if ($old['sector'] === '') $errors[] = t('job.sector') . ': ' . t('common.required');
    if ($old['country_id'] <= 0) $errors[] = t('common.country') . ': ' . t('common.required');
    if (mb_strlen($old['description']) < 20) $errors[] = t('job.description') . ': ' . t('common.required');

    if (!$errors && $jobCategoryId) {
        $slug = slugify($old['title']) . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
        $pdo->prepare("INSERT INTO listings (user_id, category_id, module, title, slug, description, price, price_type, country_id, city_id, status, lang, created_at, updated_at) VALUES (?, ?, 'jobs', ?, ?, ?, 0, 'fixed', ?, ?, 'active', ?, NOW(), NOW())")
            ->execute([(int)$user['id'], $jobCategoryId, $old['title'], $slug, $old['description'], $old['country_id'], $old['city_id'] ?: null, $lang]);
        $listingId = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO listing_jobs (listing_id, job_type, sector, salary, employment) VALUES (?, ?, ?, ?, ?)')
            ->execute([$listingId, $old['job_type'], $old['sector'], $old['salary'], $old['employment']]);
        header('Location: ' . url('pages/listing.php?slug=' . rawurlencode($slug) . '&posted=1'));
        exit;
    }
}

$pageTitle = t('job.post_title') . ' - ' . setting('site_name', 'AvrupaPazari');
$pageStyles = ['post-job.css'];
include __DIR__ . '/../includes/header.php';
?>
<main class="post-job" data-testid="post-job-page">
  <form method="post" class="job-form" novalidate data-testid="job-form">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <header class="job-form-head">
      <span class="eyebrow"><?= e(t('jobs.title')) ?></span>
      <h1><?= e(t('job.post_title')) ?></h1>
    </header>
    <?php if ($errors): ?><div class="job-errors" data-testid="job-errors"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div><?php endif; ?>

    <div class="job-field">
      <label><?= e(t('job.type')) ?></label>
      <div class="job-toggle">
        <label class="<?= $old['job_type'] === 'offer' ? 'active' : '' ?>"><input type="radio" name="job_type" value="offer" <?= $old['job_type'] === 'offer' ? 'checked' : '' ?> data-testid="job-type-offer"><span><?= e(t('job.type_offer')) ?></span></label>
        <label class="<?= $old['job_type'] === 'seek' ? 'active' : '' ?>"><input type="radio" name="job_type" value="seek" <?= $old['job_type'] === 'seek' ? 'checked' : '' ?> data-testid="job-type-seek"><span><?= e(t('job.type_seek')) ?></span></label>
      </div>
    </div>
    <div class="job-field">
      <label for="jobTitle"><?= e(t('job.title_label')) ?></label>
      <input id="jobTitle" type="text" name="title" maxlength="150" required value="<?= e($old['title']) ?>" data-testid="job-title">
    </div>
    <div class="job-row">
      <div class="job-field">
        <label for="jobSector"><?= e(t('job.sector')) ?></label>
        <select id="jobSector" name="sector" required data-testid="job-sector">
          <option value=""><?= e(t('common.select')) ?></option>
          <?php foreach ($sectors as $s): ?><option value="<?= e($s) ?>"<?= $old['sector'] === $s ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="job-field">
        <label for="jobEmployment"><?= e(t('job.employment')) ?></label>
        <select id="jobEmployment" name="employment" data-testid="job-employment">
          <?php foreach ($employments as $emp): ?><option value="<?= $emp ?>"<?= $old['employment'] === $emp ? ' selected' : '' ?>><?= e(t('job.' . $emp)) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="job-row">
      <div class="job-field">
        <label for="jobSalary"><?= e(t('job.salary')) ?></label>
        <input id="jobSalary" type="text" name="salary" maxlength="80" placeholder="<?= e(t('job.salary_hint')) ?>" value="<?= e($old['salary']) ?>" data-testid="job-salary">
      </div>
      <div class="job-field">
        <label for="jobCountry"><?= e(t('common.country')) ?></label>
        <div class="job-row tight">
          <select id="jobCountry" name="country_id" required data-city-target="jobCity" data-testid="job-country">
            <option value=""><?= e(t('common.select')) ?></option>
            <?php foreach ($countries as $c): ?><option value="<?= (int)$c['id'] ?>"<?= $old['country_id'] === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
          <select id="jobCity" name="city_id" data-testid="job-city"<?= $old['country_id'] ? '' : ' disabled' ?>>
            <option value=""><?= e(t('common.city')) ?></option>
            <?php if ($old['country_id']): $cs = $pdo->prepare('SELECT id, name FROM cities WHERE country_id = ? AND is_active = 1 ORDER BY name'); $cs->execute([$old['country_id']]); foreach ($cs as $c): ?><option value="<?= (int)$c['id'] ?>"<?= $old['city_id'] === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; endif; ?>
          </select>
        </div>
      </div>
    </div>
    <div class="job-field">
      <label for="jobDesc"><?= e(t('job.description')) ?></label>
      <textarea id="jobDesc" name="description" rows="7" required data-testid="job-description"><?= e($old['description']) ?></textarea>
    </div>
    <button type="submit" class="btn-primary job-submit" data-testid="job-submit"><?= e(t('job.submit')) ?></button>
  </form>
</main>
<script src="<?= asset('js/category-filters.js') ?>" defer></script>
<script>
document.querySelectorAll('.job-toggle input').forEach(function (r) { r.addEventListener('change', function () { document.querySelectorAll('.job-toggle label').forEach(function (l) { l.classList.toggle('active', l.querySelector('input').checked); }); }); });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
