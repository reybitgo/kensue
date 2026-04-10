<?php $pageTitle = 'Login — ' . setting('site_name', APP_NAME); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/auth.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">

    <!-- Header -->
    <div class="auth-header">
      <div class="auth-logo"><img src="<?= APP_URL ?>/assets/img/logo.png" alt="Logo" style="width:36px;height:36px;object-fit:contain;"></div>
      <h1><?= e(setting('site_name', APP_NAME)) ?></h1>
      <p><?= e(setting('site_tagline', 'Build Your Network. Grow Your Income.')) ?></p>
    </div>

    <!-- Body -->
    <div class="auth-body">
      <?= render_flash() ?>

      <form method="POST" action="<?= APP_URL ?>/?page=do_login" id="loginForm">
        <?= csrf_field() ?>

        <div class="form-group">
          <label class="form-label" for="username">Username</label>
          <div class="input-group">
            <input
              type="text"
              id="username"
              name="username"
              class="form-control"
              placeholder="Enter your username"
              value="<?= e($_POST['username'] ?? '') ?>"
              autocomplete="username"
              autofocus
              required
            >
            <span class="input-icon">👤</span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div class="input-group">
            <input
              type="password"
              id="password"
              name="password"
              class="form-control"
              placeholder="Enter your password"
              autocomplete="current-password"
              required
            >
            <button type="button" class="input-btn" id="togglePw" aria-label="Show/hide password">
              <span id="eyeIcon">👁</span>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" id="loginBtn">
          <span id="btnText">Sign In</span>
        </button>
      </form>
    </div>

    <!-- Footer -->
    <div class="auth-footer">
      Don't have an account?&nbsp;
      <a href="<?= APP_URL ?>/?page=register">Register with a code →</a>
    </div>

  </div>
</div>

<script>
// Toggle password visibility
document.getElementById('togglePw').addEventListener('click', function() {
  const pw   = document.getElementById('password');
  const icon = document.getElementById('eyeIcon');
  if (pw.type === 'password') { pw.type = 'text'; icon.textContent = '🙈'; }
  else                        { pw.type = 'password'; icon.textContent = '👁'; }
});

// Show loading state on submit
document.getElementById('loginForm').addEventListener('submit', function() {
  const btn  = document.getElementById('loginBtn');
  const text = document.getElementById('btnText');
  btn.disabled = true;
  text.innerHTML = '<span class="spinner"></span> Signing in…';
});
</script>
</body>
</html>
