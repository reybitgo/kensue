<?php $pageTitle = 'Earnings'; ?>
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
  <?php require 'views/partials/sidebar_member.php'; ?>
  <div class="main-area">
    <?php require 'views/partials/topbar.php'; ?>
    <main class="page-content">
      <?= render_flash() ?>

      <!-- Summary Cards -->
      <div class="grid-4" style="margin-bottom:20px;">
        <div class="stat-card">
          <div class="stat-card-accent accent-blue"></div>
          <div class="stat-label">Total Earned</div>
          <div class="stat-value" style="font-size:20px;color:var(--primary);"><?= fmt_money($summary['total_earned']) ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-card-accent accent-green"></div>
          <div class="stat-label">Pairing Bonuses</div>
          <div class="stat-value" style="font-size:20px;color:var(--success);"><?= fmt_money($summary['total_pairing']) ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-card-accent accent-orange"></div>
          <div class="stat-label">Direct Referral</div>
          <div class="stat-value" style="font-size:20px;color:var(--orange);"><?= fmt_money($summary['total_direct']) ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-card-accent accent-purple"></div>
          <div class="stat-label">Indirect Referral</div>
          <div class="stat-value" style="font-size:20px;color:var(--purple);"><?= fmt_money($summary['total_indirect']) ?></div>
        </div>
      </div>

      <!-- History Table -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">📋 Earnings History</span>
        </div>
        <div class="card-body" style="padding:12px 16px 0;">
          <!-- Type filter tabs -->
          <div class="tab-bar">
            <?php foreach (['' => 'All', 'pairing' => '🤝 Pairing', 'direct_referral' => '👥 Direct', 'indirect_referral' => '🔗 Indirect'] as $val => $label): ?>
            <a href="<?= APP_URL ?>/?page=earnings&type=<?= $val ?>"
               class="tab-btn <?= $type === $val ? 'active' : '' ?>">
              <?= $label ?>
            </a>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Date & Time</th>
                <th>Type</th>
                <th>Description</th>
                <th>From</th>
                <th>Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($history['data'])): ?>
              <tr><td colspan="6">
                <div class="empty-state"><div class="empty-icon">📭</div><p>No earnings found.</p></div>
              </td></tr>
              <?php else: foreach ($history['data'] as $row):
                $typeName = match($row['type']) {
                  'pairing'           => '🤝 Pairing',
                  'direct_referral'   => '👥 Direct Referral',
                  'indirect_referral' => '🔗 Indirect Lvl ' . $row['level'],
                  default => $row['type'],
                };
              ?>
              <tr>
                <td class="text-mono td-muted" style="font-size:11px;"><?= fmt_datetime($row['created_at']) ?></td>
                <td><?= $typeName ?></td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                    title="<?= e($row['description']) ?>"><?= e($row['description']) ?></td>
                <td class="td-muted"><?= $row['source_username'] ? '@' . e($row['source_username']) : '—' ?></td>
                <td>
                  <?php if ($row['status'] === 'credited'): ?>
                    <span class="td-green">+<?= fmt_money($row['amount']) ?></span>
                  <?php else: ?>
                    <span class="td-muted">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($row['status'] === 'credited'): ?>
                    <span class="badge badge-success">Credited</span>
                  <?php else: ?>
                    <span class="badge badge-warning">Flushed</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($history['total_pages'] > 1): ?>
        <div class="card-footer">
          <?= pagination_links($history, APP_URL . '/?page=earnings&type=' . urlencode($type)) ?>
        </div>
        <?php endif; ?>
      </div>

    </main>
  </div>
</div>
</body></html>
