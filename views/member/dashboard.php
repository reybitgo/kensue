<?php
$pageTitle = 'Dashboard';
$extraCss  = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="<?= APP_URL ?>/assets/img/favicon.png" type="image/png">
  <title><?= e($pageTitle . ' — ' . setting('site_name', APP_NAME)) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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

      <!-- Welcome bar -->
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
        <div>
          <h2 style="font-size:20px;font-weight:800;letter-spacing:-.3px;">
            Welcome back, <?= e($user['full_name'] ? explode(' ', $user['full_name'])[0] : '@' . $user['username']) ?>! 👋
          </h2>
          <p class="text-muted" style="font-size:13px;margin-top:2px;">
            <?= e($user['package_name'] ?? 'Member') ?> · Joined <?= fmt_date($user['joined_at']) ?>
          </p>
        </div>
        <a href="<?= APP_URL ?>/?page=payout" class="btn btn-primary btn-sm">💳 Request Payout</a>
      </div>

      <!-- ── KPI Cards ─────────────────────────────────────── -->
      <div class="grid-4" style="margin-bottom:20px;">

        <div class="stat-card">
          <div class="stat-card-accent accent-blue"></div>
          <div class="stat-card-icon icon-bg-blue">💰</div>
          <div class="stat-label">E-Wallet Balance</div>
          <div class="stat-value" style="color:var(--primary);font-size:22px;">
            <?= fmt_money($user['ewallet_balance']) ?>
          </div>
          <div class="stat-sub">
            <a href="<?= APP_URL ?>/?page=payout" style="font-size:12px;font-weight:600;">Withdraw →</a>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card-accent accent-green"></div>
          <div class="stat-card-icon icon-bg-green">🤝</div>
          <div class="stat-label">Pairing Earnings</div>
          <div class="stat-value" style="color:var(--success);font-size:22px;">
            <?= fmt_money($summary['total_pairing']) ?>
          </div>
          <div class="stat-sub"><?= number_format($user['pairs_paid']) ?> pairs lifetime</div>
        </div>

        <div class="stat-card">
          <div class="stat-card-accent accent-orange"></div>
          <div class="stat-card-icon icon-bg-orange">👥</div>
          <div class="stat-label">Direct Referral</div>
          <div class="stat-value" style="color:var(--orange);font-size:22px;">
            <?= fmt_money($summary['total_direct']) ?>
          </div>
          <div class="stat-sub">
            <a href="<?= APP_URL ?>/?page=genealogy&view=referral" style="font-size:12px;font-weight:600;">View network →</a>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card-accent accent-purple"></div>
          <div class="stat-card-icon icon-bg-purple">🔗</div>
          <div class="stat-label">Indirect Referral</div>
          <div class="stat-value" style="color:var(--purple);font-size:22px;">
            <?= fmt_money($summary['total_indirect']) ?>
          </div>
          <div class="stat-sub">Up to 10 levels deep</div>
        </div>

      </div>

      <!-- ── Two Column: Pair Status + Leg Counter ─────────── -->
      <div class="grid-2" style="margin-bottom:20px;">

        <!-- Today's Pairing Cap -->
        <div class="pair-cap-card">
          <div class="pair-cap-header">
            <div class="pair-cap-title">🎯 Today's Pairing Cap</div>
            <div class="pair-cap-reset">Resets at midnight</div>
          </div>

          <?php $pct = $status['cap_percent']; ?>
          <div class="cap-bar-track">
            <div class="cap-bar-fill <?= $pct >= 100 ? 'full' : '' ?>"
                 style="width:<?= $pct ?>%"></div>
          </div>

          <div class="cap-stats">
            <span><strong><?= $status['pairs_paid_today'] ?></strong> earned today</span>
            <span><strong><?= $status['cap_remaining'] ?></strong> / <?= $status['daily_cap'] ?> remaining</span>
          </div>

          <div class="cap-earned">
            <span>Earned today</span>
            <strong><?= fmt_money($status['earned_today']) ?></strong>
          </div>

          <?php if ($status['cap_remaining'] === 0): ?>
          <div style="margin-top:10px;font-size:12px;color:var(--warning);text-align:center;background:var(--warning-bg);border-radius:var(--radius);padding:6px;">
            ⚡ Daily cap reached — resets at midnight
          </div>
          <?php endif; ?>
        </div>

        <!-- Binary Leg Counters -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">🌳 Binary Legs</span>
            <a href="<?= APP_URL ?>/?page=genealogy&view=binary" class="card-action">View Tree →</a>
          </div>
          <div class="card-body">
            <div class="leg-bar">
              <div class="leg">
                <div class="leg-label">↙ Left Leg</div>
                <div class="leg-count"><?= number_format($status['left_count']) ?></div>
                <div class="leg-sub">members</div>
              </div>
              <div class="leg">
                <div class="leg-label">↘ Right Leg</div>
                <div class="leg-count"><?= number_format($status['right_count']) ?></div>
                <div class="leg-sub">members</div>
              </div>
            </div>

            <div style="margin-top:12px;display:flex;flex-direction:column;gap:8px;">
              <div style="display:flex;justify-content:space-between;font-size:13px;">
                <span class="text-muted">Lifetime pairs paid</span>
                <strong><?= number_format($status['pairs_paid']) ?></strong>
              </div>
              <div style="display:flex;justify-content:space-between;font-size:13px;">
                <span class="text-muted">Pairs flushed (lifetime)</span>
                <strong style="color:var(--warning);"><?= number_format($status['pairs_flushed']) ?></strong>
              </div>
              <div style="display:flex;justify-content:space-between;font-size:13px;">
                <span class="text-muted">Pairing bonus/pair</span>
                <strong style="color:var(--success);"><?= fmt_money($status['pairing_bonus']) ?></strong>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- ── Recent Activity ───────────────────────────────── -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">📋 Recent Activity</span>
          <a href="<?= APP_URL ?>/?page=earnings" class="card-action">View all →</a>
        </div>
        <div class="card-body" style="padding:0;">
          <?php if (empty($recent)): ?>
            <div class="empty-state">
              <div class="empty-icon">📭</div>
              <p>No activity yet. Your earnings will appear here.</p>
            </div>
          <?php else: ?>
            <div class="activity-list" style="padding:0 20px;">
              <?php foreach ($recent as $item):
                $isCredit = $item['status'] === 'credited';
                $typeMap  = [
                  'pairing'           => ['🤝', 'var(--success-bg)',  'var(--success)'],
                  'direct_referral'   => ['👥', 'var(--orange-bg)',   'var(--orange)'],
                  'indirect_referral' => ['🔗', 'var(--purple-bg)',   'var(--purple)'],
                ];
                [$icon, $bg, $color] = $typeMap[$item['type']] ?? ['💬', 'var(--surface2)', 'var(--text-muted)'];
                $typeName = match($item['type']) {
                  'pairing'           => 'Pairing Bonus',
                  'direct_referral'   => 'Direct Referral',
                  'indirect_referral' => 'Indirect — Level ' . $item['level'],
                  default             => $item['type'],
                };
              ?>
              <div class="activity-item">
                <div class="activity-dot" style="background:<?= $bg ?>;color:<?= $color ?>;">
                  <?= $icon ?>
                </div>
                <div class="activity-body">
                  <div class="activity-desc"><?= e($typeName) ?>
                    <?php if ($item['source_username']): ?>
                      <span class="text-muted">via @<?= e($item['source_username']) ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="activity-meta">
                    <?= fmt_datetime($item['created_at']) ?>
                    <?php if ($item['pairs_count']): ?>
                      · <?= $item['pairs_count'] ?> pair(s)
                    <?php endif; ?>
                  </div>
                </div>
                <div class="activity-amount" style="color:<?= $isCredit ? 'var(--success)' : 'var(--text-muted)' ?>">
                  <?= $isCredit ? '+' . fmt_money($item['amount']) : '<span class="badge badge-neutral">Flushed</span>' ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </main>
  </div><!-- .main-area -->
</div><!-- .shell -->
</body>
</html>
