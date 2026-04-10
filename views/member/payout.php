<?php
$pageTitle       = 'Payouts';
$minPayout       = (float)setting('min_payout', '500');
$availableBalance = (float)$user['ewallet_balance'];
?>
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

      <div class="grid-2" style="gap:20px;align-items:start;margin-bottom:20px;">

        <!-- Balance card -->
        <div class="card" style="background:linear-gradient(135deg,#1a3a8f,#3b6ff0);border:none;color:#fff;">
          <div class="card-body">
            <div style="font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;opacity:.7;margin-bottom:8px;">Available Balance</div>
            <div style="font-size:36px;font-weight:800;font-family:var(--font-mono);line-height:1;"><?= fmt_money($availableBalance) ?></div>
            <div style="font-size:12px;opacity:.6;margin-top:8px;">Minimum withdrawal: <?= fmt_money($minPayout) ?></div>
          </div>
        </div>

        <!-- Request form -->
        <div class="card">
          <div class="card-header"><span class="card-title">💳 Request Payout</span></div>
          <div class="card-body">
            <?php
            // Check for existing pending request
            $hasPending = false;
            foreach ($history['data'] as $h) {
              if ($h['status'] === 'pending') { $hasPending = true; break; }
            }
            ?>
            <?php if ($hasPending): ?>
              <div class="alert alert-warning">
                <span class="alert-icon">⏳</span>
                You already have a pending payout request. Please wait for it to be processed.
              </div>
            <?php elseif ($availableBalance < $minPayout): ?>
              <div class="alert alert-info">
                <span class="alert-icon">ℹ</span>
                Minimum payout is <?= fmt_money($minPayout) ?>. Your current balance is insufficient.
              </div>
            <?php else: ?>
            <form method="POST" action="<?= APP_URL ?>/?page=request_payout" id="payoutForm">
              <?= csrf_field() ?>
              <div class="form-group">
                <label class="form-label">Amount <span class="required">*</span></label>
                <input type="number" name="amount" class="form-control" inputmode="numeric"
                  min="<?= $minPayout ?>" max="<?= $availableBalance ?>" step="1"
                  placeholder="<?= fmt_money($minPayout) ?> minimum" required
                  oninput="updateAmountHint(this.value)">
                <div class="form-hint" id="amountHint">Max: <?= fmt_money($availableBalance) ?></div>
              </div>
              <div class="form-group">
                <label class="form-label">GCash Number <span class="required">*</span></label>
                <input type="tel" name="gcash_number" class="form-control" inputmode="numeric"
                  value="<?= e($user['gcash_number'] ?? '') ?>"
                  placeholder="09XXXXXXXXX" required>
                <div class="form-hint">Funds will be sent to this GCash number.</div>
              </div>
              <button type="submit" class="btn btn-primary btn-full">Submit Payout Request</button>
            </form>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <!-- Payout History -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">📋 Payout History</span>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Requested</th>
                <th>Amount</th>
                <th>GCash</th>
                <th>Status</th>
                <th>Processed</th>
                <th>Note</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($history['data'])): ?>
              <tr><td colspan="6">
                <div class="empty-state"><div class="empty-icon">💳</div><p>No payout requests yet.</p></div>
              </td></tr>
              <?php else: foreach ($history['data'] as $row): ?>
              <tr>
                <td class="td-muted" style="font-size:12px;"><?= fmt_datetime($row['requested_at']) ?></td>
                <td class="text-mono" style="font-weight:700;"><?= fmt_money($row['amount']) ?></td>
                <td class="td-muted text-mono"><?= e($row['gcash_number']) ?></td>
                <td>
                  <?php $badge = match($row['status']) {
                    'pending'   => 'badge-warning',
                    'approved'  => 'badge-info',
                    'completed' => 'badge-success',
                    'rejected'  => 'badge-danger',
                    default     => 'badge-neutral'
                  }; ?>
                  <span class="badge <?= $badge ?>"><?= ucfirst($row['status']) ?></span>
                </td>
                <td class="td-muted" style="font-size:12px;"><?= $row['processed_at'] ? fmt_datetime($row['processed_at']) : '—' ?></td>
                <td class="td-muted" style="font-size:12px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                  <?= $row['admin_note'] ? e($row['admin_note']) : '—' ?>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($history['total_pages'] > 1): ?>
        <div class="card-footer">
          <?= pagination_links($history, APP_URL . '/?page=payout') ?>
        </div>
        <?php endif; ?>
      </div>

    </main>
  </div>
</div>

<script>
function updateAmountHint(val) {
  const hint = document.getElementById('amountHint');
  const n    = parseFloat(val) || 0;
  const max  = <?= $availableBalance ?>;
  const min  = <?= $minPayout ?>;
  if (n > max) hint.innerHTML = '<span style="color:var(--danger)">Exceeds your balance of <?= fmt_money($availableBalance) ?></span>';
  else if (n < min && n > 0) hint.innerHTML = '<span style="color:var(--danger)">Minimum is <?= fmt_money($minPayout) ?></span>';
  else hint.textContent = 'Max: <?= fmt_money($availableBalance) ?>';
}
</script>
</body>
</html>
