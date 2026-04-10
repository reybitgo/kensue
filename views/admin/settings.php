<?php $pageTitle = 'System Settings'; ?>
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

        <!-- General settings -->
        <div style="display:flex;flex-direction:column;gap:16px;">

          <div class="card">
            <div class="card-header"><span class="card-title">🌐 General Settings</span></div>
            <div class="card-body">
              <form method="POST" action="<?= APP_URL ?>/?page=admin_save_settings">
                <?= csrf_field() ?>
                <div class="form-group">
                  <label class="form-label">Site Name</label>
                  <input type="text" name="site_name" class="form-control" value="<?= e(setting('site_name')) ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Site Tagline</label>
                  <input type="text" name="site_tagline" class="form-control" value="<?= e(setting('site_tagline')) ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Contact Email</label>
                  <input type="email" name="contact_email" class="form-control" value="<?= e(setting('contact_email')) ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Minimum Payout Amount (₱)</label>
                  <input type="number" name="min_payout" class="form-control" inputmode="decimal"
                    min="0" step="0.01" value="<?= e(setting('min_payout','500')) ?>">
                  <div class="form-hint">Members cannot request below this amount</div>
                </div>
                <div class="form-group">
                  <label class="form-label">Maintenance Mode</label>
                  <select name="maintenance_mode" class="form-control">
                    <option value="0" <?= setting('maintenance_mode')==='0'?'selected':'' ?>>Off (Site is live)</option>
                    <option value="1" <?= setting('maintenance_mode')==='1'?'selected':'' ?>>On (Members see maintenance page)</option>
                  </select>
                </div>
                <button type="submit" class="btn btn-primary btn-full">💾 Save Settings</button>
              </form>
            </div>
          </div>

        </div>

        <!-- System operations -->
        <div style="display:flex;flex-direction:column;gap:16px;">

          <!-- Daily reset -->
          <div class="card">
            <div class="card-header"><span class="card-title">⏱️ Daily Pair Cap Reset</span></div>
            <div class="card-body">
              <p style="font-size:13px;color:var(--text-muted);line-height:1.7;margin-bottom:16px;">
                The midnight cron resets <code style="background:var(--surface2);padding:2px 6px;border-radius:4px;font-size:12px;">pairs_paid_today = 0</code>
                for all members, allowing them to earn pairing bonuses again tomorrow.
              </p>
              <?php $lastReset = setting('last_reset'); ?>
              <div style="background:var(--surface2);border-radius:var(--radius);padding:12px 14px;margin-bottom:14px;">
                <div style="font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--text-muted);margin-bottom:4px;">Last Reset</div>
                <div style="font-size:14px;font-weight:600;font-family:var(--font-mono);">
                  <?= $lastReset ? fmt_datetime($lastReset) : 'Never run' ?>
                </div>
              </div>
              <div style="background:var(--surface2);border-radius:var(--radius);padding:12px 14px;margin-bottom:14px;font-size:12px;font-family:var(--font-mono);color:var(--text-muted);">
                Crontab:<br>
                <strong style="color:var(--text);">0 0 * * * php /path/to/mlm/cron/midnight_reset.php</strong>
              </div>
              <form method="POST" action="<?= APP_URL ?>/?page=admin_manual_reset">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline btn-full"
                  onclick="return confirm('Reset pairs_paid_today=0 for ALL members now? This simulates the midnight cron.')">
                  ⟳ Run Daily Reset Now
                </button>
              </form>
            </div>
          </div>

          <!-- Admin password change -->
          <div class="card">
            <div class="card-header"><span class="card-title">🔒 Change Admin Password</span></div>
            <div class="card-body">
              <form method="POST" action="<?= APP_URL ?>/?page=save_profile">
                <?= csrf_field() ?>
                <div class="form-group">
                  <label class="form-label">Current Password</label>
                  <input type="password" name="current_password" class="form-control" autocomplete="current-password">
                </div>
                <div class="form-group">
                  <label class="form-label">New Password</label>
                  <input type="password" name="new_password" class="form-control" minlength="8" autocomplete="new-password">
                </div>
                <div class="form-group">
                  <label class="form-label">Confirm New Password</label>
                  <input type="password" name="new_password_confirm" class="form-control" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary btn-full">🔒 Update Password</button>
              </form>
            </div>
          </div>

          <!-- System info -->
          <div class="card">
            <div class="card-header"><span class="card-title">ℹ System Info</span></div>
            <div class="card-body">
              <table class="info-table">
                <tr><td>PHP Version</td><td class="text-mono"><?= PHP_VERSION ?></td></tr>
                <tr><td>MySQL Version</td><td class="text-mono"><?= db()->query('SELECT VERSION()')->fetchColumn() ?></td></tr>
                <tr><td>Server Time</td><td class="text-mono"><?= date('Y-m-d H:i:s') ?></td></tr>
                <tr><td>App URL</td><td class="text-mono" style="font-size:11px;"><?= APP_URL ?></td></tr>
                <tr><td>Environment</td><td><span class="badge <?= APP_ENV==='production'?'badge-success':'badge-warning' ?>"><?= APP_ENV ?></span></td></tr>
              </table>
            </div>
          </div>

        </div>
      </div>

    </main>
  </div>
</div>
</body>
</html>
