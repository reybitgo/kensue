<?php $pageTitle = 'Profile & Settings'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle . ' — ' . setting('site_name', APP_NAME)) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/layout.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/components.css">
</head>
<body>
<div class="shell">
  <?php require 'views/partials/sidebar_member.php'; ?>
  <div class="main-area">
    <?php require 'views/partials/topbar.php'; ?>
    <main class="page-content">
      <?= render_flash() ?>

      <form method="POST" action="<?= APP_URL ?>/?page=save_profile" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="grid-2" style="gap:20px;align-items:start;">

          <!-- Left column -->
          <div style="display:flex;flex-direction:column;gap:16px;">

            <!-- Profile photo card -->
            <div class="card">
              <div class="card-header"><span class="card-title">🖼 Profile Photo</span></div>
              <div class="card-body" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                <div id="photoPreviewWrap" style="width:80px;height:80px;border-radius:50%;overflow:hidden;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:700;color:var(--primary);flex-shrink:0;border:3px solid var(--border);">
                  <?php if ($user['photo']): ?>
                    <img id="photoPreview" src="<?= APP_URL ?>/uploads/<?= e($user['photo']) ?>" style="width:100%;height:100%;object-fit:cover;">
                  <?php else: ?>
                    <span id="photoInitial"><?= strtoupper(substr($user['username'], 0, 1)) ?></span>
                  <?php endif; ?>
                </div>
                <div>
                  <label class="btn btn-outline btn-sm" style="cursor:pointer;">
                    📷 Change Photo
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewPhoto(this)">
                  </label>
                  <p class="text-muted" style="font-size:11px;margin-top:6px;">JPEG, PNG or WebP · Max 2MB</p>
                </div>
              </div>
            </div>

            <!-- Account info (read-only) -->
            <div class="card">
              <div class="card-header"><span class="card-title">ℹ Account Info</span></div>
              <div class="card-body">
                <table class="info-table">
                  <tr><td>Username</td><td><strong>@<?= e($user['username']) ?></strong></td></tr>
                  <tr><td>Package</td><td><?= e($user['package_name'] ?? '—') ?></td></tr>
                  <tr><td>Sponsor</td><td><?= isset($user['sponsor_username']) && $user['sponsor_username'] ? '@' . e($user['sponsor_username']) : '—' ?></td></tr>
                  <tr><td>Binary Upline</td><td>
                    <?php
                    $bpu = $user['binary_parent_username'] ?? null;
                    $bpp = $user['binary_position'] ?? null;
                    echo $bpu ? '@' . e($bpu) . ' (' . e($bpp) . ')' : '—';
                    ?>
                  </td></tr>
                  <tr><td>Joined</td><td><?= fmt_datetime($user['joined_at']) ?></td></tr>
                  <tr><td>Last Login</td><td><?= $user['last_login'] ? fmt_datetime($user['last_login']) : 'First time' ?></td></tr>
                </table>
              </div>
            </div>

          </div>

          <!-- Right column -->
          <div style="display:flex;flex-direction:column;gap:16px;">

            <!-- Personal Info -->
            <div class="card">
              <div class="card-header"><span class="card-title">👤 Personal Information</span></div>
              <div class="card-body">
                <div class="form-group">
                  <label class="form-label">Full Name</label>
                  <input type="text" name="full_name" class="form-control"
                    value="<?= e($user['full_name'] ?? '') ?>" placeholder="Your full name">
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                      value="<?= e($user['email'] ?? '') ?>" placeholder="email@example.com">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Mobile</label>
                    <input type="tel" name="mobile" class="form-control" inputmode="tel"
                      value="<?= e($user['mobile'] ?? '') ?>" placeholder="09XXXXXXXXX">
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Address</label>
                  <textarea name="address" class="form-control" rows="2"
                    placeholder="Your address"><?= e($user['address'] ?? '') ?></textarea>
                </div>
              </div>
            </div>

            <!-- Payout Info -->
            <div class="card">
              <div class="card-header"><span class="card-title">💳 Payout Information</span></div>
              <div class="card-body">
                <div class="form-group">
                  <label class="form-label">GCash Number</label>
                  <input type="tel" name="gcash_number" class="form-control" inputmode="numeric"
                    value="<?= e($user['gcash_number'] ?? '') ?>" placeholder="09XXXXXXXXX">
                  <div class="form-hint">This number will receive your payouts via GCash.</div>
                </div>
              </div>
            </div>

            <!-- Change Password -->
            <div class="card">
              <div class="card-header"><span class="card-title">🔒 Change Password</span></div>
              <div class="card-body">
                <div class="form-group">
                  <label class="form-label">Current Password</label>
                  <input type="password" name="current_password" class="form-control" placeholder="Your current password" autocomplete="current-password">
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Min. 8 chars" minlength="8" autocomplete="new-password">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Confirm New</label>
                    <input type="password" name="new_password_confirm" class="form-control" placeholder="Repeat password" autocomplete="new-password">
                  </div>
                </div>
                <div class="form-hint">Leave blank if you don't want to change your password.</div>
              </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg">💾 Save Changes</button>

          </div>

        </div>
      </form>

    </main>
  </div>
</div>

<script>
function previewPhoto(input) {
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = function(e) {
    const wrap = document.getElementById('photoPreviewWrap');
    wrap.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;">';
  };
  reader.readAsDataURL(input.files[0]);
}
</script>
</body>
</html>
