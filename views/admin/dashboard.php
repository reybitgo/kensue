<?php $pageTitle = 'Admin Dashboard'; ?>
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

      <?php
      // Extra stats needed on this page
      $totalCommissions = (float)db()->query("SELECT COALESCE(SUM(amount),0) FROM commissions WHERE status='credited'")->fetchColumn();
      $totalEwallets    = (float)db()->query("SELECT COALESCE(SUM(ewallet_balance),0) FROM users WHERE role='member'")->fetchColumn();
      $recentJoins      = db()->query("SELECT u.username, u.full_name, u.joined_at, p.name AS package_name
                                       FROM users u LEFT JOIN packages p ON p.id=u.package_id
                                       WHERE u.role='member' ORDER BY u.joined_at DESC LIMIT 6")->fetchAll();
      $lastReset        = setting('last_reset');
      $codeStats        = $codeStat; // passed from controller
      ?>

      <!-- KPI Row -->
      <div class="grid-4" style="margin-bottom:20px;">

        <div class="stat-card">
          <div class="stat-card-accent accent-blue"></div>
          <div class="stat-card-icon icon-bg-blue">👥</div>
          <div class="stat-label">Total Members</div>
          <div class="stat-value" style="color:var(--primary);"><?= number_format($memberCounts['total']) ?></div>
          <div class="stat-sub">
            <span style="color:var(--success);">+<?= number_format((int)$memberCounts['joined_today']) ?> today</span>
            &nbsp;·&nbsp; <?= number_format((int)$memberCounts['active']) ?> active
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card-accent accent-green"></div>
          <div class="stat-card-icon icon-bg-green">💰</div>
          <div class="stat-label">Code Revenue</div>
          <div class="stat-value" style="color:var(--success);"><?= fmt_money($codeStats['revenue']) ?></div>
          <div class="stat-sub"><?= number_format($codeStats['used']) ?> codes sold</div>
        </div>

        <div class="stat-card">
          <div class="stat-card-accent accent-orange"></div>
          <div class="stat-card-icon icon-bg-orange">💸</div>
          <div class="stat-label">Pending Payouts</div>
          <div class="stat-value" style="color:var(--warning);"><?= fmt_money($pendingPayout) ?></div>
          <div class="stat-sub"><a href="<?= APP_URL ?>/?page=admin_payouts" style="font-size:12px;font-weight:600;">Review requests →</a></div>
        </div>

        <div class="stat-card">
          <div class="stat-card-accent accent-purple"></div>
          <div class="stat-card-icon icon-bg-purple">✅</div>
          <div class="stat-label">Total Paid Out</div>
          <div class="stat-value" style="color:var(--purple);"><?= fmt_money($totalPaid) ?></div>
          <div class="stat-sub">Completed payouts</div>
        </div>

      </div>

      <!-- Second row -->
      <div class="grid-4" style="margin-bottom:20px;">
        <div class="stat-card">
          <div class="stat-card-accent accent-green"></div>
          <div class="stat-label">Total Commissions Paid</div>
          <div class="stat-value" style="font-size:20px;color:var(--success);"><?= fmt_money($totalCommissions) ?></div>
          <div class="stat-sub">All credited bonuses</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-accent accent-blue"></div>
          <div class="stat-label">Total E-Wallet Holdings</div>
          <div class="stat-value" style="font-size:20px;color:var(--primary);"><?= fmt_money($totalEwallets) ?></div>
          <div class="stat-sub">Sum of all member balances</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-accent accent-orange"></div>
          <div class="stat-label">Unused Codes</div>
          <div class="stat-value" style="font-size:20px;color:var(--warning);"><?= number_format($codeStats['unused']) ?></div>
          <div class="stat-sub"><a href="<?= APP_URL ?>/?page=admin_codes" style="font-size:12px;font-weight:600;">Manage codes →</a></div>
        </div>
        <div class="stat-card">
          <div class="stat-card-accent accent-purple"></div>
          <div class="stat-label">Suspended Members</div>
          <div class="stat-value" style="font-size:20px;color:var(--danger);"><?= number_format((int)$memberCounts['suspended']) ?></div>
          <div class="stat-sub"><a href="<?= APP_URL ?>/?page=admin_users&status=suspended" style="font-size:12px;font-weight:600;">View →</a></div>
        </div>
      </div>

      <!-- Pending payouts + Recent joins -->
      <div class="grid-2" style="gap:20px;align-items:start;">

        <!-- Pending payout requests -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">⏳ Pending Payout Requests</span>
            <a href="<?= APP_URL ?>/?page=admin_payouts" class="card-action">View all →</a>
          </div>
          <?php if (empty($pendingList)): ?>
            <div class="empty-state" style="padding:32px;"><div class="empty-icon">🎉</div><p>No pending requests.</p></div>
          <?php else: ?>
          <div class="table-wrap">
            <table class="data-table">
              <thead><tr><th>Member</th><th>Amount</th><th>GCash</th><th>Action</th></tr></thead>
              <tbody>
              <?php foreach ($pendingList as $pr): ?>
              <tr>
                <td>
                  <div style="font-weight:600;font-size:13px;">@<?= e($pr['username']) ?></div>
                  <div style="font-size:11px;color:var(--text-muted);"><?= fmt_date($pr['requested_at']) ?></div>
                </td>
                <td class="text-mono" style="font-weight:700;color:var(--success);"><?= fmt_money($pr['amount']) ?></td>
                <td class="td-muted text-mono" style="font-size:12px;"><?= e($pr['gcash_number']) ?></td>
                <td>
                  <a href="<?= APP_URL ?>/?page=admin_payouts" class="btn btn-sm btn-primary">Review</a>
                </td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>

        <!-- Recent joins -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">🆕 Recent Members</span>
            <a href="<?= APP_URL ?>/?page=admin_users" class="card-action">View all →</a>
          </div>
          <div class="card-body" style="padding:0 20px;">
            <?php if (empty($recentJoins)): ?>
              <div class="empty-state"><div class="empty-icon">👥</div><p>No members yet.</p></div>
            <?php else: ?>
            <div class="activity-list">
              <?php foreach ($recentJoins as $m): ?>
              <div class="activity-item">
                <div class="user-avatar" style="width:36px;height:36px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;">
                  <?= strtoupper(substr($m['username'], 0, 1)) ?>
                </div>
                <div class="activity-body">
                  <div class="activity-desc">@<?= e($m['username']) ?><?= $m['full_name'] ? ' · ' . e($m['full_name']) : '' ?></div>
                  <div class="activity-meta"><?= e($m['package_name'] ?? 'Member') ?> · <?= fmt_datetime($m['joined_at']) ?></div>
                </div>
                <a href="<?= APP_URL ?>/?page=admin_user_view&id=<?= $m['id'] ?? 0 ?>" class="btn btn-ghost btn-sm">View</a>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <!-- Daily reset status -->
      <div style="margin-top:16px;background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius);padding:12px 16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div style="font-size:13px;">
          <strong>Daily Pair Cap Reset</strong>
          <span class="text-muted" style="margin-left:8px;font-size:12px;">
            Last run: <?= $lastReset ? fmt_datetime($lastReset) : 'Never' ?>
          </span>
        </div>
        <form method="POST" action="<?= APP_URL ?>/?page=admin_manual_reset" style="margin:0;">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-ghost btn-sm"
            onclick="return confirm('Reset the daily pair counter for all members now?')">
            ⟳ Run Reset Now
          </button>
        </form>
      </div>

    </main>
  </div>
</div>
</body>
</html>
