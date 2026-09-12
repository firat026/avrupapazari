<?php
/**
 * admin/listing-edit.php - İlan Ekle/Düzenle
 * Konum: /admin/listing-edit.php
 */
require_once __DIR__ . '/includes/auth.php';
$currentPage = 'listings';
$pdo = getDB();
$lang = $_SESSION['lang'] ?? 'tr';
$msg = '';
$isEdit = false;
$listing = null;

// Düzenleme modunda?
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $listing = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($listing) $isEdit = true;
}

$pageTitle = $isEdit ? 'İlan Düzenle' : 'Yeni İlan';

// Kategoriler
$categories = [];
try {
    $stmt = $pdo->prepare("SELECT c.id, c.module, ct.name FROM categories c LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = ? WHERE c.is_active = 1 ORDER BY c.sort_order ASC");
    $stmt->execute([$lang]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

// KAYDET
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $priceType = $_POST['price_type'] ?? 'fixed';
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $module = $_POST['module'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $isPremium = isset($_POST['is_premium']) ? 1 : 0;

    // Slug otomatik oluştur
    if (!$slug) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug = trim($slug, '-');
    }

    // Modül: kategoriden al
    if (!$module && $categoryId) {
        $s = $pdo->prepare("SELECT module FROM categories WHERE id = ?");
        $s->execute([$categoryId]);
        $module = $s->fetchColumn() ?: 'ikinci_el';
    }

    // Resim upload
    $imagePath = $listing['image'] ?? '';
    if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === 0) {
        $uploadDir = __DIR__ . '/../uploads/listings/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allowed)) {
            $newName = bin2hex(random_bytes(12)) . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newName)) {
                $imagePath = 'listings/' . $newName;
            }
        }
    }

    try {
        if ($isEdit) {
            $stmt = $pdo->prepare("UPDATE listings SET title=?, slug=?, description=?, price=?, price_type=?, category_id=?, module=?, status=?, is_premium=?, image=? WHERE id=?");
            $stmt->execute([$title, $slug, $description, $price, $priceType, $categoryId, $module, $status, $isPremium, $imagePath, $listing['id']]);
            $msg = 'İlan güncellendi.';
            // Refresh
            $stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ?");
            $stmt->execute([$listing['id']]);
            $listing = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("INSERT INTO listings (user_id, title, slug, description, price, price_type, category_id, module, status, is_premium, image, view_count) VALUES (?,?,?,?,?,?,?,?,?,?,?,0)");
            $stmt->execute([$_SESSION['admin_id'], $title, $slug, $description, $price, $priceType, $categoryId, $module, $status, $isPremium, $imagePath]);
            header('Location: ' . BASE_URL . '/admin/listings.php?msg=added');
            exit;
        }
    } catch(Exception $e) {
        $msg = 'Hata: ' . $e->getMessage();
    }
}

// Form defaults
$f = [
    'title' => $listing['title'] ?? '',
    'slug' => $listing['slug'] ?? '',
    'description' => $listing['description'] ?? '',
    'price' => $listing['price'] ?? '',
    'price_type' => $listing['price_type'] ?? 'fixed',
    'category_id' => $listing['category_id'] ?? '',
    'status' => $listing['status'] ?? 'active',
    'is_premium' => $listing['is_premium'] ?? 0,
    'image' => $listing['image'] ?? '',
];

include __DIR__ . '/includes/header.php';
?>

<style>
.form-card{background:#fff;border:1px solid #dce8e2;border-radius:12px;padding:28px;margin-bottom:20px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.form-select{width:100%;height:42px;border:1.5px solid #dce8e2;border-radius:8px;padding:0 14px;font-family:inherit;font-size:.88rem;color:#1a2b23;background:#fff;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%237a9488' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center}
.form-select:focus{outline:none;border-color:#1d7a4e;box-shadow:0 0 0 3px rgba(29,122,78,.08)}
.checkbox-wrap{display:flex;align-items:center;gap:8px;font-size:.88rem;font-weight:600;color:#4a6355;cursor:pointer}
.checkbox-wrap input{width:18px;height:18px;accent-color:#1d7a4e}
.img-preview{width:120px;height:80px;border-radius:8px;object-fit:cover;border:1px solid #dce8e2;margin-top:8px}
.back-link{display:inline-flex;align-items:center;gap:6px;font-size:.82rem;font-weight:600;color:#4a6355;text-decoration:none;margin-bottom:16px}
.back-link:hover{color:#1a2b23}
.back-link .icon{width:14px;height:14px}
</style>

<a href="<?= BASE_URL ?>/admin/listings.php" class="back-link">
  <span class="icon"><svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg></span>
  İlanlara Dön
</a>

<?php if($msg): ?><div style="background:#e8f5ee;border:1px solid #d4edde;color:#1d7a4e;padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:.84rem;font-weight:600"><?= e($msg) ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
  <div class="form-card">
    <div class="form-group">
      <label class="form-label">İlan Başlığı *</label>
      <input class="form-input" type="text" name="title" value="<?= e($f['title']) ?>" required placeholder="Örn: Samsung Galaxy S24 Ultra 256GB">
    </div>

    <div class="form-group">
      <label class="form-label">Slug (URL)</label>
      <input class="form-input" type="text" name="slug" value="<?= e($f['slug']) ?>" placeholder="Boş bırakırsan otomatik oluşturulur">
    </div>

    <div class="form-group">
      <label class="form-label">Açıklama</label>
      <textarea class="form-input form-textarea" name="description" placeholder="İlan detayları..."><?= e($f['description']) ?></textarea>
    </div>

    <div class="form-row-3">
      <div class="form-group">
        <label class="form-label">Fiyat (€)</label>
        <input class="form-input" type="number" step="0.01" name="price" value="<?= $f['price'] ?>" placeholder="0.00">
      </div>
      <div class="form-group">
        <label class="form-label">Fiyat Türü</label>
        <select class="form-select" name="price_type">
          <option value="fixed" <?= $f['price_type']==='fixed'?'selected':'' ?>>Sabit Fiyat</option>
          <option value="negotiable" <?= $f['price_type']==='negotiable'?'selected':'' ?>>Pazarlık</option>
          <option value="free" <?= $f['price_type']==='free'?'selected':'' ?>>Ücretsiz</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Kategori *</label>
        <select class="form-select" name="category_id" required>
          <option value="">Seç...</option>
          <?php foreach($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= (int)$f['category_id']===(int)$cat['id']?'selected':'' ?>><?= e($cat['name'] ?? $cat['module']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Durum</label>
        <select class="form-select" name="status">
          <option value="active" <?= $f['status']==='active'?'selected':'' ?>>Aktif</option>
          <option value="pending" <?= $f['status']==='pending'?'selected':'' ?>>Bekliyor</option>
          <option value="sold" <?= $f['status']==='sold'?'selected':'' ?>>Satıldı</option>
          <option value="expired" <?= $f['status']==='expired'?'selected':'' ?>>Süresi Doldu</option>
        </select>
      </div>
      <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:8px">
        <label class="checkbox-wrap">
          <input type="checkbox" name="is_premium" value="1" <?= $f['is_premium']?'checked':'' ?>>
          Premium İlan
        </label>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Görsel</label>
      <input class="form-input" type="file" name="image" accept="image/*" style="padding:8px 14px;height:auto">
      <?php if($f['image']): ?>
      <img src="<?= BASE_URL ?>/uploads/<?= e($f['image']) ?>" class="img-preview" alt="">
      <?php endif; ?>
    </div>
  </div>

  <div style="display:flex;gap:12px;align-items:center">
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Güncelle' : 'İlan Ekle' ?></button>
    <a href="<?= BASE_URL ?>/admin/listings.php" class="btn btn-outline">İptal</a>
  </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>