<?php $pageTitle = 'Registration Codes'; ?>
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
  <style>
  @media print {
    .sidebar, .topbar, .no-print, .main-area > .topbar { display: none !important; }
    .main-area { margin-left: 0 !important; }
    .shell { display: block; }
    .print-grid { display: grid !important; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    .print-code-card {
      border: 1px solid #ddd; border-radius: 8px; padding: 12px; break-inside: avoid;
    }
  }
  .print-grid { display: none; }
  </style>
</head>
<body>
<div class="shell">
  <?php require 'views/partials/sidebar_admin.php'; ?>
  <div class="main-area">
    <?php require 'views/partials/topbar.php'; ?>
    <main class="page-content">
      <?= render_flash() ?>

      <!-- Stats bar -->
      <div class="grid-4" style="margin-bottom:16px;">
        <?php foreach ([
          ['Total Codes',   $stats['total'],   'accent-blue',   'primary'],
          ['Unused',        $stats['unused'],  'accent-green',  'success'],
          ['Used / Sold',   $stats['used'],    'accent-orange', 'warning'],
          ['Revenue',       fmt_money($stats['revenue']), 'accent-purple', 'purple'],
        ] as [$label, $val, $accent, $color]): ?>
        <div class="stat-card" style="padding:14px 16px;">
          <div class="stat-card-accent <?= $accent ?>"></div>
          <div class="stat-label"><?= $label ?></div>
          <div class="stat-value" style="font-size:20px;color:var(--<?= $color ?>);"><?= is_numeric($val) ? number_format($val) : $val ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="grid-2" style="gap:20px;align-items:start;">

        <!-- Generate codes form -->
        <div class="card no-print">
          <div class="card-header"><span class="card-title">⚡ Generate Codes</span></div>
          <div class="card-body">
            <form method="POST" action="<?= APP_URL ?>/?page=admin_gen_codes" id="genForm">
              <?= csrf_field() ?>
              <div class="form-group">
                <label class="form-label">Package <span class="required">*</span></label>
                <select name="package_id" class="form-control" required onchange="updatePrice(this)">
                  <option value="">— Select package —</option>
                  <?php foreach ($packages as $pkg): ?>
                  <option value="<?= $pkg['id'] ?>" data-entry="<?= $pkg['entry_fee'] ?>">
                    <?= e($pkg['name']) ?> (entry: <?= fmt_money($pkg['entry_fee']) ?>)
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Quantity <span class="required">*</span></label>
                  <input type="number" name="quantity" class="form-control" inputmode="numeric"
                    min="1" max="500" value="10" required>
                  <div class="form-hint">Max 500 per batch</div>
                </div>
                <div class="form-group">
                  <label class="form-label">Code Price (₱) <span class="required">*</span></label>
                  <input type="number" name="price" id="codePrice" class="form-control" inputmode="decimal"
                    min="0" step="0.01" required placeholder="e.g. 10500.00">
                  <div class="form-hint">What you charge per code (your revenue)</div>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Expiry Date (optional)</label>
                <input type="date" name="expires_at" class="form-control"
                  min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                <div class="form-hint">Leave blank for no expiry</div>
              </div>
              <button type="submit" class="btn btn-primary btn-full"
                onclick="return confirm('Generate ' + document.querySelector('[name=quantity]').value + ' code(s)?')">
                🎟️ Generate Codes
              </button>
            </form>
          </div>
        </div>

        <!-- Filters + export -->
        <div class="card no-print">
          <div class="card-header"><span class="card-title">🔍 Filter & Export</span></div>
          <div class="card-body">
            <form method="GET" action="<?= APP_URL ?>/">
              <input type="hidden" name="page" value="admin_codes">
              <div class="form-group">
                <label class="form-label">Filter by Status</label>
                <select name="status" class="form-control" onchange="this.form.submit()">
                  <option value="">All Statuses</option>
                  <?php foreach (['unused','used','expired'] as $s): ?>
                  <option value="<?= $s ?>" <?= ($status??'') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Filter by Package</label>
                <select name="pkg" class="form-control" onchange="this.form.submit()">
                  <option value="">All Packages</option>
                  <?php foreach ($packages as $pkg): ?>
                  <option value="<?= $pkg['id'] ?>" <?= ($pkgId??0) == $pkg['id'] ? 'selected' : '' ?>><?= e($pkg['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </form>

            <hr style="border:none;border-top:1px solid var(--border-light);margin:12px 0;">

            <div style="display:flex;flex-direction:column;gap:8px;">
              <a href="<?= APP_URL ?>/?page=admin_export_codes&status=<?= urlencode($status??'') ?>&pkg=<?= $pkgId??0 ?>"
                 class="btn btn-outline btn-full">
                📥 Export to CSV / Excel
              </a>
              <button class="btn btn-ghost btn-full" onclick="printCodes()">
                🖨️ Print Codes (PDF-ready)
              </button>
            </div>
          </div>
        </div>

      </div>

      <!-- Codes table -->
      <div class="card no-print" style="margin-top:16px;">
        <div class="card-header">
          <span class="card-title">🎟️ Code List
            <?php if ($status || $pkgId): ?>
              <span class="badge badge-primary" style="margin-left:8px;"><?= $codes['total'] ?> results</span>
            <?php endif; ?>
          </span>
          <?php if ($status || $pkgId): ?>
            <a href="<?= APP_URL ?>/?page=admin_codes" class="card-action">✕ Clear filter</a>
          <?php endif; ?>
        </div>
        <div class="table-wrap">
          <table class="data-table" id="codesTable">
            <thead>
              <tr>
                <th>#</th>
                <th>Code</th>
                <th>Package</th>
                <th>Price</th>
                <th>Status</th>
                <th>Used By</th>
                <th>Created</th>
                <th>Expires</th>
              </tr>
            </thead>
            <tbody>
            <?php if (empty($codes['data'])): ?>
              <tr><td colspan="8"><div class="empty-state"><div class="empty-icon">🎟️</div><p>No codes found.</p></div></td></tr>
            <?php else: foreach ($codes['data'] as $i => $c): ?>
            <tr>
              <td class="td-muted" style="font-size:11px;"><?= ($codes['page']-1)*25 + $i + 1 ?></td>
              <td>
                <span class="reg-code" style="font-size:13px;letter-spacing:1.5px;"><?= e($c['code']) ?></span>
              </td>
              <td><span class="badge badge-primary"><?= e($c['package_name']) ?></span></td>
              <td class="text-mono" style="font-weight:700;"><?= fmt_money($c['price']) ?></td>
              <td>
                <?php $b = match($c['status']) { 'unused'=>'badge-success','used'=>'badge-neutral','expired'=>'badge-danger',default=>'badge-neutral' }; ?>
                <span class="badge <?= $b ?>"><?= ucfirst($c['status']) ?></span>
              </td>
              <td class="td-muted"><?= $c['used_by_username'] ? '@'.e($c['used_by_username']) : '—' ?></td>
              <td class="td-muted" style="font-size:11px;"><?= fmt_date($c['created_at']) ?></td>
              <td class="td-muted" style="font-size:11px;"><?= $c['expires_at'] ? fmt_date($c['expires_at']) : '—' ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($codes['total_pages'] > 1): ?>
        <div class="card-footer">
          <?= pagination_links($codes, APP_URL . '/?page=admin_codes&status=' . urlencode($status??'') . '&pkg=' . ($pkgId??0)) ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Print-only grid -->
      <div class="print-grid" id="printGrid">
        <?php foreach ($codes['data'] as $c): if ($c['status'] !== 'unused') continue; ?>
        <div class="print-code-card">
          <div style="font-size:10px;color:#666;text-transform:uppercase;letter-spacing:1px;"><?= e(setting('site_name',APP_NAME)) ?></div>
          <div style="font-size:9px;color:#999;margin-bottom:6px;"><?= e($c['package_name']) ?> · <?= fmt_money($c['price']) ?></div>
          <div style="font-family:monospace;font-size:16px;font-weight:700;letter-spacing:2px;color:#111;"><?= e($c['code']) ?></div>
          <div style="font-size:9px;color:#bbb;margin-top:4px;"><?= $c['expires_at'] ? 'Expires ' . fmt_date($c['expires_at']) : 'No expiry' ?></div>
        </div>
        <?php endforeach; ?>
      </div>

    </main>
  </div>
</div>

<script>
function updatePrice(select) {
  const opt   = select.options[select.selectedIndex];
  const entry = opt.dataset.entry;
  if (entry) {
    document.getElementById('codePrice').value = parseFloat(entry) + 500;
  }
}

function printCodes() {
  document.getElementById('printGrid').style.display = 'grid';
  window.print();
  setTimeout(() => { document.getElementById('printGrid').style.display = 'none'; }, 500);
}
</script>
</body>
</html>
