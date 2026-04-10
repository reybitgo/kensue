<?php
$user         = Auth::user();
$topbarBalance = fmt_money($user['ewallet_balance'] ?? 0);
$initials     = strtoupper(substr($user['username'] ?? 'U', 0, 1));
?>
<header class="topbar">
  <button class="topbar-hamburger" id="hamburger" aria-label="Open menu">☰</button>
  <div class="topbar-title"><?= e($pageTitle ?? 'Dashboard') ?></div>
  <div class="topbar-right">
    <?php if (($user['role'] ?? '') === 'member'): ?>
    <div class="topbar-balance">
      <span class="label">Balance</span>
      <span class="amount" id="topbarBalance"><?= $topbarBalance ?></span>
    </div>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/?page=<?= Auth::isAdmin() ? 'admin' : 'profile' ?>"
       class="topbar-avatar" title="<?= e($user['username'] ?? '') ?>">
      <?php if ($user['photo'] ?? ''): ?>
        <img src="<?= APP_URL ?>/uploads/<?= e($user['photo']) ?>" alt="">
      <?php else: ?>
        <?= $initials ?>
      <?php endif; ?>
    </a>
  </div>
</header>

<script>
// Sidebar toggle
(function() {
  const sidebar  = document.getElementById('sidebar');
  const overlay  = document.getElementById('sidebarOverlay');
  const hamburger= document.getElementById('hamburger');
  if (!sidebar) return;

  function open()  { sidebar.classList.add('open'); overlay.classList.add('show'); }
  function close() { sidebar.classList.remove('open'); overlay.classList.remove('show'); }
  function toggle(){ sidebar.classList.contains('open') ? close() : open(); }

  hamburger?.addEventListener('click', toggle);
  overlay?.addEventListener('click', close);

  // Swipe support
  let startX = 0;
  document.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, { passive: true });
  document.addEventListener('touchend', e => {
    const dx = e.changedTouches[0].clientX - startX;
    if (dx >  60 && startX < 50) open();
    if (dx < -60) close();
  }, { passive: true });

  // Close on ESC
  document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
})();
</script>
