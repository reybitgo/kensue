<?php $pageTitle = 'Member: @' . $user['username']; ?>
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

      <!-- Back + header -->
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
        <a href="<?= APP_URL ?>/?page=admin_users" class="btn btn-ghost btn-sm">← Back</a>
        <div style="flex:1;">
          <h2 style="font-size:18px;font-weight:800;">@<?= e($user['username']) ?></h2>
          <p class="text-muted" style="font-size:12px;">Member since <?= fmt_datetime($user['joined_at']) ?></p>
        </div>
        <?php $badge = $user['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>
        <span class="badge <?= $badge ?>" style="font-size:13px;padding:5px 14px;"><?= ucfirst($user['status']) ?></span>
        <form method="POST" action="<?= APP_URL ?>/?page=admin_toggle_user">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= $user['id'] ?>">
          <button type="submit" class="btn btn-sm <?= $user['status'] === 'active' ? 'btn-danger' : 'btn-success' ?>"
            onclick="return confirm('<?= $user['status'] === 'active' ? 'Suspend' : 'Activate' ?> this member?')">
            <?= $user['status'] === 'active' ? '🔒 Suspend' : '✅ Activate' ?>
          </button>
        </form>
      </div>

      <!-- KPI row -->
      <div class="grid-4" style="margin-bottom:20px;">
        <div class="stat-card">
          <div class="stat-card-accent accent-blue"></div>
          <div class="stat-label">E-Wallet Balance</div>
          <div class="stat-value" style="font-size:20px;color:var(--primary);"><?= fmt_money($user['ewallet_balance']) ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-card-accent accent-green"></div>
          <div class="stat-label">Total Earned</div>
          <div class="stat-value" style="font-size:20px;color:var(--success);"><?= fmt_money($summary['total_earned']) ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-card-accent accent-orange"></div>
          <div class="stat-label">Pairs Paid / Today</div>
          <div class="stat-value" style="font-size:20px;color:var(--warning);">
            <?= $pairingStatus['pairs_paid'] ?> / <?= $pairingStatus['pairs_paid_today'] ?>
          </div>
          <div class="stat-sub">Today: <?= $pairingStatus['pairs_paid_today'] ?> / <?= $pairingStatus['daily_cap'] ?> cap</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-accent accent-red"></div>
          <div class="stat-label">Pairs Flushed</div>
          <div class="stat-value" style="font-size:20px;color:var(--danger);"><?= number_format($pairingStatus['pairs_flushed']) ?></div>
          <div class="stat-sub">Lost to daily cap</div>
        </div>
      </div>

      <!-- Two-column layout -->
      <div class="grid-2" style="gap:20px;align-items:start;margin-bottom:20px;">

        <!-- Profile info -->
        <div class="card">
          <div class="card-header"><span class="card-title">👤 Profile</span></div>
          <div class="card-body">
            <table class="info-table">
              <tr><td>Full Name</td><td><?= e($user['full_name'] ?: '—') ?></td></tr>
              <tr><td>Email</td><td><?= e($user['email'] ?: '—') ?></td></tr>
              <tr><td>Mobile</td><td><?= e($user['mobile'] ?: '—') ?></td></tr>
              <tr><td>GCash</td><td><strong><?= e($user['gcash_number'] ?: '—') ?></strong></td></tr>
              <tr><td>Address</td><td><?= e($user['address'] ?: '—') ?></td></tr>
              <tr><td>Last Login</td><td><?= $user['last_login'] ? fmt_datetime($user['last_login']) : 'Never' ?></td></tr>
            </table>
          </div>
        </div>

        <!-- Binary placement -->
        <div class="card">
          <div class="card-header"><span class="card-title">🌳 Binary Placement</span></div>
          <div class="card-body">
            <table class="info-table">
              <tr><td>Package</td><td><span class="badge badge-primary"><?= e($user['package_name'] ?? '—') ?></span></td></tr>
              <tr><td>Sponsor</td><td><?= $user['sponsor_username'] ? '<a href="' . APP_URL . '/?page=admin_user_view&id=' . $user['sponsor_id'] . '">@' . e($user['sponsor_username']) . '</a>' : '—' ?></td></tr>
              <tr><td>Binary Upline</td><td><?= $user['binary_parent_username'] ? '@' . e($user['binary_parent_username']) . ' (' . $user['binary_position'] . ')' : '—' ?></td></tr>
              <tr><td>Left Leg</td><td><?= number_format($user['left_count']) ?> members</td></tr>
              <tr><td>Right Leg</td><td><?= number_format($user['right_count']) ?> members</td></tr>
              <tr><td>Pairing Bonus</td><td><?= fmt_money($user['pairing_bonus'] ?? 0) ?> / pair</td></tr>
              <tr><td>Daily Cap</td><td><?= $user['daily_pair_cap'] ?> pairs / day</td></tr>
            </table>

            <div class="leg-bar" style="margin-top:14px;">
              <div class="leg">
                <div class="leg-label">↙ Left</div>
                <div class="leg-count"><?= number_format($user['left_count']) ?></div>
              </div>
              <div class="leg">
                <div class="leg-label">↘ Right</div>
                <div class="leg-count"><?= number_format($user['right_count']) ?></div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Tabs: Commissions | E-Wallet | Payouts -->
      <?php $tab = $_GET['tab'] ?? 'commissions'; ?>
      <div class="card">
        <div class="card-body" style="padding:12px 16px 0;border-bottom:1px solid var(--border-light);">
          <div class="tab-bar" style="margin-bottom:0;">
            <?php foreach (['commissions' => '💰 Commissions', 'ledger' => '📒 E-Wallet Ledger', 'payouts' => '💳 Payouts'] as $t => $label): ?>
            <a href="<?= APP_URL ?>/?page=admin_user_view&id=<?= $user['id'] ?>&tab=<?= $t ?>"
               class="tab-btn <?= $tab === $t ? 'active' : '' ?>"><?= $label ?></a>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if ($tab === 'commissions'): ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>From</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (empty($commHist['data'])): ?>
              <tr><td colspan="6"><div class="empty-state"><div class="empty-icon">📭</div><p>No commissions.</p></div></td></tr>
            <?php else: foreach ($commHist['data'] as $c):
              $typeName = match($c['type']) {
                'pairing' => '🤝 Pairing', 'direct_referral' => '👥 Direct', 'indirect_referral' => '🔗 Indirect Lvl '.$c['level'], default => $c['type']
              };
            ?>
            <tr>
              <td class="td-muted" style="font-size:11px;"><?= fmt_datetime($c['created_at']) ?></td>
              <td><?= $typeName ?></td>
              <td style="font-size:12px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($c['description']) ?></td>
              <td class="td-muted"><?= $c['source_username'] ? '@'.e($c['source_username']) : '—' ?></td>
              <td class="<?= $c['status']==='credited'?'td-green':'td-muted' ?> text-mono">
                <?= $c['status']==='credited' ? '+'.fmt_money($c['amount']) : '—' ?>
              </td>
              <td><span class="badge <?= $c['status']==='credited'?'badge-success':'badge-warning' ?>"><?= ucfirst($c['status']) ?></span></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>

        <?php elseif ($tab === 'ledger'): ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Balance After</th><th>Note</th></tr></thead>
            <tbody>
            <?php if (empty($ledger['data'])): ?>
              <tr><td colspan="5"><div class="empty-state"><div class="empty-icon">📒</div><p>No ledger entries.</p></div></td></tr>
            <?php else: foreach ($ledger['data'] as $l): ?>
            <tr>
              <td class="td-muted" style="font-size:11px;"><?= fmt_datetime($l['created_at']) ?></td>
              <td><span class="badge <?= $l['type']==='credit'?'badge-success':'badge-danger' ?>"><?= ucfirst($l['type']) ?></span></td>
              <td class="text-mono <?= $l['type']==='credit'?'td-green':'td-red' ?>">
                <?= ($l['type']==='credit'?'+':'-') . fmt_money($l['amount']) ?>
              </td>
              <td class="text-mono" style="font-weight:600;"><?= fmt_money($l['balance_after']) ?></td>
              <td class="td-muted" style="font-size:12px;"><?= e($l['note'] ?? '—') ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>

        <?php else: /* payouts */ ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Requested</th><th>Amount</th><th>GCash</th><th>Status</th><th>Processed</th><th>Note</th></tr></thead>
            <tbody>
            <?php if (empty($payouts['data'])): ?>
              <tr><td colspan="6"><div class="empty-state"><div class="empty-icon">💳</div><p>No payout history.</p></div></td></tr>
            <?php else: foreach ($payouts['data'] as $pr): ?>
            <tr>
              <td class="td-muted" style="font-size:11px;"><?= fmt_datetime($pr['requested_at']) ?></td>
              <td class="text-mono" style="font-weight:700;"><?= fmt_money($pr['amount']) ?></td>
              <td class="text-mono td-muted"><?= e($pr['gcash_number']) ?></td>
              <td>
                <?php $b = match($pr['status']) { 'pending'=>'badge-warning','approved'=>'badge-info','completed'=>'badge-success','rejected'=>'badge-danger',default=>'badge-neutral' }; ?>
                <span class="badge <?= $b ?>"><?= ucfirst($pr['status']) ?></span>
              </td>
              <td class="td-muted" style="font-size:12px;"><?= $pr['processed_at'] ? fmt_datetime($pr['processed_at']) : '—' ?></td>
              <td class="td-muted" style="font-size:12px;"><?= e($pr['admin_note'] ?? '—') ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

    </main>
  </div>
</div>
</body>
</html>
