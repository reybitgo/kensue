<?php $pageTitle = 'Members'; ?>
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
  <?php require 'views/partials/sidebar_admin.php'; ?>
  <div class="main-area">
    <?php require 'views/partials/topbar.php'; ?>
    <main class="page-content">
      <?= render_flash() ?>

      <!-- Summary stats bar -->
      <?php $counts = User::counts(); ?>
      <div class="grid-4" style="margin-bottom:16px;">
        <?php foreach ([
          ['Total', $counts['total'],     'accent-blue',   'primary'],
          ['Active', $counts['active'],   'accent-green',  'success'],
          ['Suspended', $counts['suspended'], 'accent-red','danger'],
          ['Joined Today', $counts['joined_today'], 'accent-orange', 'warning'],
        ] as [$label, $val, $accent, $color]): ?>
        <div class="stat-card" style="padding:14px 16px;">
          <div class="stat-card-accent <?= $accent ?>"></div>
          <div class="stat-label"><?= $label ?></div>
          <div class="stat-value" style="font-size:22px;color:var(--<?= $color ?>);"><?= number_format($val) ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="card">
        <div class="card-header">
          <span class="card-title">👥 All Members</span>
        </div>

        <!-- Filters -->
        <div class="card-body" style="padding:12px 16px;border-bottom:1px solid var(--border-light);">
          <form method="GET" action="<?= APP_URL ?>/" style="display:contents;">
            <input type="hidden" name="page" value="admin_users">
            <div class="filter-bar">
              <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search username, name, email…">
              </div>
              <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php foreach (['active','suspended','pending'] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
              <select name="pkg" class="filter-select" onchange="this.form.submit()">
                <option value="">All Packages</option>
                <?php foreach ($packages as $pkg): ?>
                <option value="<?= $pkg['id'] ?>" <?= $pkgId === (int)$pkg['id'] ? 'selected' : '' ?>>
                  <?= e($pkg['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-primary btn-sm">Search</button>
              <?php if ($search || $status || $pkgId): ?>
                <a href="<?= APP_URL ?>/?page=admin_users" class="btn btn-ghost btn-sm">✕ Clear</a>
              <?php endif; ?>
            </div>
          </form>
        </div>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Package</th>
                <th>Balance</th>
                <th>Pairs Paid</th>
                <th>Joined</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if (empty($result['data'])): ?>
              <tr><td colspan="9">
                <div class="empty-state"><div class="empty-icon">👥</div><p>No members found.</p></div>
              </td></tr>
            <?php else: foreach ($result['data'] as $i => $m): ?>
              <tr>
                <td class="td-muted" style="font-size:11px;"><?= ($result['page']-1)*25 + $i + 1 ?></td>
                <td>
                  <a href="<?= APP_URL ?>/?page=admin_user_view&id=<?= $m['id'] ?>"
                     style="font-weight:700;font-size:13px;">@<?= e($m['username']) ?></a>
                </td>
                <td style="font-size:13px;"><?= e($m['full_name'] ?: '—') ?></td>
                <td><span class="badge badge-primary"><?= e($m['package_name'] ?? '—') ?></span></td>
                <td class="text-mono" style="font-weight:700;color:var(--success);"><?= fmt_money($m['ewallet_balance']) ?></td>
                <td class="text-mono td-muted"><?= number_format($m['pairs_paid']) ?></td>
                <td class="td-muted" style="font-size:12px;"><?= fmt_date($m['joined_at']) ?></td>
                <td>
                  <?php $badge = $m['status'] === 'active' ? 'badge-success' : ($m['status'] === 'suspended' ? 'badge-danger' : 'badge-warning'); ?>
                  <span class="badge <?= $badge ?>"><?= ucfirst($m['status']) ?></span>
                </td>
                <td>
                  <div style="display:flex;gap:6px;">
                    <a href="<?= APP_URL ?>/?page=admin_user_view&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline">View</a>
                    <button class="btn btn-sm btn-ghost toggle-user-btn"
                            data-id="<?= $m['id'] ?>"
                            data-status="<?= $m['status'] ?>"
                            data-username="<?= e($m['username']) ?>">
                      <?= $m['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($result['total_pages'] > 1): ?>
        <div class="card-footer">
          <?= pagination_links($result, APP_URL . '/?page=admin_users&q=' . urlencode($search) . '&status=' . $status . '&pkg=' . $pkgId) ?>
        </div>
        <?php endif; ?>
      </div>

    </main>
  </div>
</div>

<!-- Toggle user modal -->
<div class="modal-backdrop" id="toggleModal">
  <div class="modal" style="max-width:380px;">
    <div class="modal-header">
      <div class="modal-title" id="toggleTitle">Confirm Action</div>
      <button class="modal-close" onclick="closeModal('toggleModal')">×</button>
    </div>
    <div class="modal-body">
      <p id="toggleMsg" style="font-size:14px;"></p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('toggleModal')">Cancel</button>
      <form method="POST" action="<?= APP_URL ?>/?page=admin_toggle_user" id="toggleForm">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="toggleId">
        <button type="submit" class="btn" id="toggleBtn">Confirm</button>
      </form>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.toggle-user-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const id       = this.dataset.id;
    const status   = this.dataset.status;
    const username = this.dataset.username;
    const isSuspend = status === 'active';

    document.getElementById('toggleTitle').textContent = isSuspend ? 'Suspend Member' : 'Activate Member';
    document.getElementById('toggleMsg').textContent =
      isSuspend
        ? `Suspend @${username}? They will not be able to log in.`
        : `Activate @${username}? They will regain access.`;
    document.getElementById('toggleId').value = id;
    const confirmBtn = document.getElementById('toggleBtn');
    confirmBtn.textContent = isSuspend ? 'Suspend' : 'Activate';
    confirmBtn.className = 'btn ' + (isSuspend ? 'btn-danger' : 'btn-success');
    document.getElementById('toggleModal').classList.add('show');
  });
});

function closeModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.modal-backdrop').forEach(m => {
  m.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('show'); });
});
</script>
</body>
</html>
