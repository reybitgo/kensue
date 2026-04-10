<?php
$currentPage = current_page();
$user        = Auth::user();
$initials    = strtoupper(substr($user['username'] ?? 'A', 0, 1));

// Badge counts
$pendingPayouts = (int)db()->query("SELECT COUNT(*) FROM payout_requests WHERE status='pending'")->fetchColumn();
$pendingMembers = (int)db()->query("SELECT COUNT(*) FROM users WHERE role='member' AND status='pending'")->fetchColumn();
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">

  <div class="sidebar-brand">
    <div class="brand-icon"><img src="<?= APP_URL ?>/assets/img/logo.png" alt="Logo" style="width:28px;height:28px;object-fit:contain;"></div>
    <div>
      <div class="brand-name"><?= e(setting('site_name', APP_NAME)) ?></div>
      <div class="brand-sub">Admin Panel</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>

    <a href="<?= APP_URL ?>/?page=admin"
       class="nav-item <?= $currentPage === 'admin' ? 'active' : '' ?>">
      <span class="nav-icon">📊</span> Dashboard
    </a>

    <div class="nav-section-label">Management</div>

    <a href="<?= APP_URL ?>/?page=admin_users"
       class="nav-item <?= in_array($currentPage, ['admin_users','admin_user_view']) ? 'active' : '' ?>">
      <span class="nav-icon">👥</span> Members
      <?php if ($pendingMembers): ?>
        <span class="nav-badge"><?= $pendingMembers ?></span>
      <?php endif; ?>
    </a>

    <a href="<?= APP_URL ?>/?page=admin_packages"
       class="nav-item <?= $currentPage === 'admin_packages' ? 'active' : '' ?>">
      <span class="nav-icon">📦</span> Packages
    </a>

    <a href="<?= APP_URL ?>/?page=admin_codes"
       class="nav-item <?= $currentPage === 'admin_codes' ? 'active' : '' ?>">
      <span class="nav-icon">🎟️</span> Reg Codes
    </a>

    <div class="nav-section-label">Finance</div>

    <a href="<?= APP_URL ?>/?page=admin_payouts"
       class="nav-item <?= $currentPage === 'admin_payouts' ? 'active' : '' ?>">
      <span class="nav-icon">💸</span> Payouts
      <?php if ($pendingPayouts): ?>
        <span class="nav-badge"><?= $pendingPayouts ?></span>
      <?php endif; ?>
    </a>

    <div class="nav-section-label">System</div>

    <a href="<?= APP_URL ?>/?page=admin_settings"
       class="nav-item <?= $currentPage === 'admin_settings' ? 'active' : '' ?>">
      <span class="nav-icon">⚙️</span> Settings
    </a>

    <a href="<?= APP_URL ?>/?page=dashboard"
       class="nav-item">
      <span class="nav-icon">👤</span> Member View
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar" style="background:rgba(59,111,240,.2);color:var(--primary);">
        <?= $initials ?>
      </div>
      <div class="user-info">
        <div class="user-name"><?= e($user['full_name'] ?: $user['username']) ?></div>
        <div class="user-role">Administrator</div>
      </div>
      <a href="<?= APP_URL ?>/?page=logout" class="sidebar-logout" title="Log out">⏻</a>
    </div>
  </div>
</aside>
