<?php
$currentPage = current_page();
$user        = Auth::user();
$initials    = strtoupper(substr($user['username'] ?? 'U', 0, 1));
$displayName = $user['full_name'] ?: ('@' . $user['username']);
?>
<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">

  <!-- Brand -->
  <div class="sidebar-brand">
    <div class="brand-icon"><img src="<?= APP_URL ?>/assets/img/logo.png" alt="Logo" style="width:28px;height:28px;object-fit:contain;"></div>
    <div>
      <div class="brand-name"><?= e(setting('site_name', APP_NAME)) ?></div>
      <div class="brand-sub">Member Portal</div>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>

    <a href="<?= APP_URL ?>/?page=dashboard"
       class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
      <span class="nav-icon">🏠</span> Dashboard
    </a>

    <a href="<?= APP_URL ?>/?page=earnings"
       class="nav-item <?= $currentPage === 'earnings' ? 'active' : '' ?>">
      <span class="nav-icon">💰</span> Earnings
    </a>

    <div class="nav-section-label">Network</div>

    <a href="<?= APP_URL ?>/?page=genealogy&view=binary"
       class="nav-item <?= ($currentPage === 'genealogy' && ($_GET['view'] ?? '') !== 'referral') ? 'active' : '' ?>">
      <span class="nav-icon">🌳</span> Binary Tree
    </a>

    <a href="<?= APP_URL ?>/?page=genealogy&view=referral"
       class="nav-item <?= ($currentPage === 'genealogy' && ($_GET['view'] ?? '') === 'referral') ? 'active' : '' ?>">
      <span class="nav-icon">👥</span> Referral Network
    </a>

    <div class="nav-section-label">Account</div>

    <a href="<?= APP_URL ?>/?page=payout"
       class="nav-item <?= $currentPage === 'payout' ? 'active' : '' ?>">
      <span class="nav-icon">💳</span> Payouts
    </a>

    <a href="<?= APP_URL ?>/?page=profile"
       class="nav-item <?= $currentPage === 'profile' ? 'active' : '' ?>">
      <span class="nav-icon">⚙️</span> Profile & Settings
    </a>
  </nav>

  <!-- User footer -->
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar">
        <?php if ($user['photo']): ?>
          <img src="<?= APP_URL ?>/uploads/<?= e($user['photo']) ?>" alt="">
        <?php else: ?>
          <?= $initials ?>
        <?php endif; ?>
      </div>
      <div class="user-info">
        <div class="user-name"><?= e($displayName) ?></div>
        <div class="user-role"><?= e($user['package_name'] ?? 'Member') ?></div>
      </div>
      <a href="<?= APP_URL ?>/?page=logout" class="sidebar-logout" title="Log out">⏻</a>
    </div>
  </div>
</aside>
