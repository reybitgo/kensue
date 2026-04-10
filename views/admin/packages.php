<?php $pageTitle = 'Packages'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="<?= APP_URL ?>/assets/img/favicon.png" type="image/png">
  <title><?= e($pageTitle . ' — ' . setting('site_name', APP_NAME)) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/layout.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/components.css">
</head>
<body>
<div class="shell">
  <?php require 'views/partials/sidebar_admin.php'; ?>
  <div class="main-area">
    <?php require 'views/partials/topbar.php'; ?>
    <main class="page-content">
      <?= render_flash() ?>

      <div class="grid-2" style="gap:20px;align-items:start;">

        <!-- Package list -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">📦 All Packages</span>
            <button class="btn btn-primary btn-sm" onclick="openCreateForm()">+ New Package</button>
          </div>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Entry Fee</th>
                  <th>Pair Bonus</th>
                  <th>Daily Cap</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php if (empty($packages)): ?>
                <tr><td colspan="6"><div class="empty-state"><div class="empty-icon">📦</div><p>No packages yet.</p></div></td></tr>
              <?php else: foreach ($packages as $pkg): ?>
              <tr>
                <td style="font-weight:700;"><?= e($pkg['name']) ?></td>
                <td class="text-mono"><?= fmt_money($pkg['entry_fee']) ?></td>
                <td class="text-mono td-green"><?= fmt_money($pkg['pairing_bonus']) ?></td>
                <td class="td-muted"><?= $pkg['daily_pair_cap'] ?> pairs</td>
                <td>
                  <span class="badge <?= $pkg['status']==='active'?'badge-success':'badge-neutral' ?>">
                    <?= ucfirst($pkg['status']) ?>
                  </span>
                </td>
                <td>
                  <a href="<?= APP_URL ?>/?page=admin_packages&edit=<?= $pkg['id'] ?>"
                     class="btn btn-sm btn-outline">Edit</a>
                </td>
              </tr>
              <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Create / Edit form -->
        <div class="card" id="packageForm">
          <div class="card-header">
            <span class="card-title" id="formTitle">
              <?= $editPkg ? '✏️ Edit Package' : '➕ New Package' ?>
            </span>
            <?php if ($editPkg): ?>
              <a href="<?= APP_URL ?>/?page=admin_packages" class="btn btn-ghost btn-sm">✕ Cancel</a>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <form method="POST" action="<?= APP_URL ?>/?page=admin_save_package">
              <?= csrf_field() ?>
              <?php if ($editPkg): ?>
                <input type="hidden" name="package_id" value="<?= $editPkg['id'] ?>">
              <?php endif; ?>

              <div class="form-group">
                <label class="form-label">Package Name <span class="required">*</span></label>
                <input type="text" name="name" class="form-control"
                  value="<?= e($editPkg['name'] ?? '') ?>" placeholder="e.g. Starter, Pro, Elite" required>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Entry Fee (₱) <span class="required">*</span></label>
                  <input type="number" name="entry_fee" class="form-control" inputmode="decimal"
                    min="0" step="0.01" value="<?= e($editPkg['entry_fee'] ?? '') ?>" placeholder="10000.00" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Pairing Bonus (₱) <span class="required">*</span></label>
                  <input type="number" name="pairing_bonus" class="form-control" inputmode="decimal"
                    min="0" step="0.01" value="<?= e($editPkg['pairing_bonus'] ?? '') ?>" placeholder="2000.00" required>
                  <div class="form-hint">Per pair paid out</div>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Daily Pair Cap <span class="required">*</span></label>
                  <input type="number" name="daily_pair_cap" class="form-control" inputmode="numeric"
                    min="1" max="100" value="<?= e($editPkg['daily_pair_cap'] ?? 3) ?>" required>
                  <div class="form-hint">Max pairs/day per member (flush-out limit)</div>
                </div>
                <div class="form-group">
                  <label class="form-label">Direct Referral Bonus (₱)</label>
                  <input type="number" name="direct_ref_bonus" class="form-control" inputmode="decimal"
                    min="0" step="0.01" value="<?= e($editPkg['direct_ref_bonus'] ?? 0) ?>" placeholder="500.00">
                  <div class="form-hint">Paid once to sponsor on join</div>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                  <option value="active"   <?= ($editPkg['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                  <option value="inactive" <?= ($editPkg['status'] ?? '')         === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
              </div>

              <!-- Indirect Referral Levels -->
              <div style="margin:8px 0 14px;">
                <div style="font-size:13px;font-weight:700;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid var(--border);">
                  🔗 Indirect Referral Bonuses (10 Levels)
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                  <?php
                  $lvls = $editPkg['indirect_levels'] ?? [];
                  for ($lvl = 1; $lvl <= 10; $lvl++): ?>
                  <div class="form-group" style="margin-bottom:8px;">
                    <label class="form-label" style="font-size:11px;">Level <?= $lvl ?></label>
                    <div style="position:relative;">
                      <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;">₱</span>
                      <input type="number" name="indirect_<?= $lvl ?>" class="form-control"
                        style="padding-left:24px;" inputmode="decimal" min="0" step="0.01"
                        value="<?= e($lvls[$lvl] ?? 0) ?>" placeholder="0.00">
                    </div>
                  </div>
                  <?php endfor; ?>
                </div>
                <div class="form-hint" style="margin-top:4px;">Set to 0 to disable a level. Paid once to each upline sponsor on member join.</div>
              </div>

              <button type="submit" class="btn btn-primary btn-full btn-lg">
                <?= $editPkg ? '💾 Update Package' : '➕ Create Package' ?>
              </button>
            </form>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>

<script>
function openCreateForm() {
  window.location.href = '<?= APP_URL ?>/?page=admin_packages';
}
</script>
</body>
</html>
