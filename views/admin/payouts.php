<?php $pageTitle = 'Payout Requests'; ?>
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

      <!-- Tab filters -->
      <div class="tab-bar" style="margin-bottom:16px;">
        <?php foreach (['pending'=>'⏳ Pending','approved'=>'✅ Approved','completed'=>'💚 Completed','rejected'=>'❌ Rejected',''=>'📋 All'] as $s => $label): ?>
        <a href="<?= APP_URL ?>/?page=admin_payouts&status=<?= $s ?>"
           class="tab-btn <?= $status === $s ? 'active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
      </div>

      <?php
      // Payout summary bar
      $pendingAmt   = Payout::pendingTotal();
      $completedAmt = Payout::totalPaid();
      ?>
      <div class="grid-2" style="margin-bottom:16px;">
        <div class="stat-card" style="padding:14px 16px;">
          <div class="stat-card-accent accent-orange"></div>
          <div class="stat-label">Pending Amount</div>
          <div class="stat-value" style="font-size:20px;color:var(--warning);"><?= fmt_money($pendingAmt) ?></div>
        </div>
        <div class="stat-card" style="padding:14px 16px;">
          <div class="stat-card-accent accent-green"></div>
          <div class="stat-label">Total Paid Out</div>
          <div class="stat-value" style="font-size:20px;color:var(--success);"><?= fmt_money($completedAmt) ?></div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <span class="card-title">💸 Payout Requests</span>
          <span class="badge badge-neutral"><?= $result['total'] ?> records</span>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Member</th>
                <th>Amount</th>
                <th>GCash Number</th>
                <th>Requested</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if (empty($result['data'])): ?>
              <tr><td colspan="7">
                <div class="empty-state"><div class="empty-icon">💸</div><p>No payout requests found.</p></div>
              </td></tr>
            <?php else: foreach ($result['data'] as $i => $pr): ?>
            <tr>
              <td class="td-muted" style="font-size:11px;"><?= $pr['id'] ?></td>
              <td>
                <a href="<?= APP_URL ?>/?page=admin_user_view&id=<?= $pr['user_id'] ?>" style="font-weight:700;">
                  @<?= e($pr['username']) ?>
                </a>
                <div style="font-size:11px;color:var(--text-muted);"><?= e($pr['full_name'] ?: '') ?></div>
              </td>
              <td class="text-mono" style="font-weight:700;font-size:15px;color:var(--success);">
                <?= fmt_money($pr['amount']) ?>
              </td>
              <td>
                <span class="text-mono" style="font-size:13px;font-weight:600;color:var(--primary);">
                  <?= e($pr['gcash_number']) ?>
                </span>
                <button class="btn btn-ghost btn-sm" style="padding:2px 6px;font-size:11px;"
                  onclick="copyGcash('<?= e($pr['gcash_number']) ?>')" title="Copy">📋</button>
              </td>
              <td class="td-muted" style="font-size:12px;"><?= fmt_datetime($pr['requested_at']) ?></td>
              <td>
                <?php $b = match($pr['status']) { 'pending'=>'badge-warning','approved'=>'badge-info','completed'=>'badge-success','rejected'=>'badge-danger',default=>'badge-neutral' }; ?>
                <span class="badge <?= $b ?>"><?= ucfirst($pr['status']) ?></span>
                <?php if ($pr['admin_note']): ?>
                  <div style="font-size:10px;color:var(--text-muted);margin-top:2px;"><?= e(substr($pr['admin_note'],0,30)) ?>…</div>
                <?php endif; ?>
              </td>
              <td>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                  <?php if ($pr['status'] === 'pending'): ?>
                    <button class="btn btn-sm btn-success action-btn"
                      data-action="approve" data-id="<?= $pr['id'] ?>"
                      data-user="<?= e($pr['username']) ?>" data-amount="<?= fmt_money($pr['amount']) ?>">
                      ✓ Approve
                    </button>
                    <button class="btn btn-sm btn-danger action-btn"
                      data-action="reject" data-id="<?= $pr['id'] ?>"
                      data-user="<?= e($pr['username']) ?>" data-amount="<?= fmt_money($pr['amount']) ?>">
                      ✕ Reject
                    </button>
                  <?php elseif ($pr['status'] === 'approved'): ?>
                    <div style="background:var(--warning-bg);border:1px solid var(--warning-border);border-radius:var(--radius);padding:6px 10px;font-size:12px;">
                      <div style="color:var(--warning);font-weight:700;">Send <?= fmt_money($pr['amount']) ?></div>
                      <div style="color:var(--text-muted);">to GCash <?= e($pr['gcash_number']) ?></div>
                    </div>
                    <button class="btn btn-sm btn-primary action-btn"
                      data-action="complete" data-id="<?= $pr['id'] ?>"
                      data-user="<?= e($pr['username']) ?>" data-amount="<?= fmt_money($pr['amount']) ?>"
                      data-gcash="<?= e($pr['gcash_number']) ?>">
                      ✅ Mark Complete
                    </button>
                  <?php else: ?>
                    <span class="td-muted" style="font-size:12px;">
                      <?= $pr['processed_at'] ? fmt_date($pr['processed_at']) : '—' ?>
                    </span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($result['total_pages'] > 1): ?>
        <div class="card-footer">
          <?= pagination_links($result, APP_URL . '/?page=admin_payouts&status=' . urlencode($status)) ?>
        </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>

<!-- Action Modal -->
<div class="modal-backdrop" id="actionModal">
  <div class="modal" style="max-width:440px;">
    <div class="modal-header">
      <div class="modal-title" id="modalTitle">Confirm</div>
      <button class="modal-close" onclick="closeModal()">×</button>
    </div>
    <div class="modal-body">
      <p id="modalDesc" style="font-size:14px;margin-bottom:16px;line-height:1.6;"></p>
      <form method="POST" action="<?= APP_URL ?>/?page=admin_payout_action" id="actionForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" id="modalAction">
        <input type="hidden" name="id"     id="modalId">
        <div class="form-group" id="noteGroup">
          <label class="form-label" id="noteLabel">Note</label>
          <textarea name="note" id="modalNote" class="form-control" rows="2" placeholder="Optional note to member…"></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
      <button class="btn" id="modalConfirm" onclick="document.getElementById('actionForm').submit()">Confirm</button>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.action-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const action = this.dataset.action;
    const id     = this.dataset.id;
    const user   = this.dataset.user;
    const amount = this.dataset.amount;
    const gcash  = this.dataset.gcash || '';

    document.getElementById('modalAction').value = action;
    document.getElementById('modalId').value     = id;
    document.getElementById('modalNote').value   = '';

    const titles = { approve:'✓ Approve Payout', reject:'✕ Reject Payout', complete:'✅ Mark as Completed' };
    const descs  = {
      approve: `Approve the payout of <strong>${amount}</strong> for <strong>@${user}</strong>? You will then need to send the funds via GCash before marking it complete.`,
      reject:  `Reject the payout request of <strong>${amount}</strong> for <strong>@${user}</strong>? Their e-wallet balance will NOT be deducted.`,
      complete:`Confirm you have sent <strong>${amount}</strong> to <strong>@${user}</strong> via GCash <strong>${gcash}</strong>? This will deduct the amount from their e-wallet.`
    };

    document.getElementById('modalTitle').textContent = titles[action] || 'Confirm';
    document.getElementById('modalDesc').innerHTML    = descs[action] || '';

    const noteLabel = document.getElementById('noteLabel');
    noteLabel.textContent = action === 'reject' ? 'Rejection Reason (shown to member)' : 'Note (optional)';

    const btn = document.getElementById('modalConfirm');
    btn.className = 'btn ' + (action === 'reject' ? 'btn-danger' : action === 'complete' ? 'btn-primary' : 'btn-success');
    btn.textContent = titles[action];

    document.getElementById('actionModal').classList.add('show');
  });
});

function closeModal() { document.getElementById('actionModal').classList.remove('show'); }
document.getElementById('actionModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

function copyGcash(num) {
  navigator.clipboard.writeText(num).then(() => {
    const toast = document.createElement('div');
    toast.textContent = 'Copied: ' + num;
    toast.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#1a2035;color:#fff;padding:8px 18px;border-radius:8px;font-size:13px;z-index:9999;';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2000);
  });
}
</script>
</body>
</html>
