<?php $pageTitle = 'Register — ' . setting('site_name', APP_NAME); ?>
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
  <div class="auth-card auth-card-wide">

    <!-- Header -->
    <div class="auth-header">
      <div class="auth-logo"><img src="<?= APP_URL ?>/assets/img/logo.png" alt="Logo" style="width:36px;height:36px;object-fit:contain;"></div>
      <h1><?= e(setting('site_name', APP_NAME)) ?></h1>
      <p>Create your member account</p>
    </div>

    <!-- Step Progress Bar -->
    <div class="steps-bar" id="stepsBar">
      <div class="step active" id="step-ind-1">
        <div class="step-dot">1</div>
        <div class="step-text">Validate Code</div>
      </div>
      <div class="step" id="step-ind-2">
        <div class="step-dot">2</div>
        <div class="step-text">Account Setup</div>
      </div>
      <div class="step" id="step-ind-3">
        <div class="step-dot">3</div>
        <div class="step-text">Confirm</div>
      </div>
    </div>

    <!-- Flash -->
    <div style="padding: 0 36px;">
      <?= render_flash() ?>
    </div>

    <!-- Master form — all steps, submitted at Step 3 -->
    <form method="POST" action="<?= APP_URL ?>/?page=do_register" id="regForm">
      <?= csrf_field() ?>

      <!-- ═══════════════════════════════════════════════════
           STEP 1 — Registration Code
      ════════════════════════════════════════════════════ -->
      <div class="auth-body" id="step1">
        <p style="font-size:13px;color:#6b7a99;margin-bottom:18px;">
          Enter the registration code provided by your sponsor or purchased from the company.
        </p>

        <div class="form-group">
          <label class="form-label" for="reg_code">
            Registration Code <span class="required">*</span>
          </label>
          <div style="display:flex;gap:10px;">
            <input
              type="text"
              id="reg_code"
              name="reg_code"
              class="form-control text-mono"
              placeholder="XXXX-XXXX-XXXX"
              maxlength="14"
              autocomplete="off"
              style="text-transform:uppercase;letter-spacing:2px;font-size:16px;"
              required
            >
            <button type="button" class="btn btn-outline" id="validateCodeBtn" style="flex-shrink:0;">
              Validate
            </button>
          </div>
          <div class="form-hint" id="codeHint"></div>
        </div>

        <!-- Package info (shown after validation) -->
        <div id="packageInfo" style="display:none;" class="code-verified">
          <span class="icon">✅</span>
          <div>
            <div style="font-weight:700;" id="pkgName">Package</div>
            <div style="font-size:12px;margin-top:2px;" id="pkgDetails">—</div>
          </div>
        </div>

        <input type="hidden" name="validated_code" id="validatedCode" value="">

        <button type="button" class="btn btn-primary btn-full btn-lg" id="toStep2Btn" disabled>
          Continue →
        </button>
      </div>

      <!-- ═══════════════════════════════════════════════════
           STEP 2 — Account Setup
      ════════════════════════════════════════════════════ -->
      <div class="auth-body" id="step2" style="display:none;">

        <!-- Username -->
        <div class="form-group">
          <label class="form-label" for="username">
            Username <span class="required">*</span>
          </label>
          <input
            type="text"
            id="username"
            name="username"
            class="form-control"
            placeholder="Choose a username (letters, numbers, _)"
            autocomplete="off"
            minlength="3"
            maxlength="40"
            required
          >
          <div class="form-hint" id="usernameHint"></div>
        </div>

        <!-- Password -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="password">
              Password <span class="required">*</span>
            </label>
            <div class="input-group">
              <input type="password" id="password" name="password" class="form-control"
                placeholder="Min. 8 characters" minlength="8" required>
              <button type="button" class="input-btn" onclick="togglePw('password', this)">👁</button>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="password_confirm">
              Confirm Password <span class="required">*</span>
            </label>
            <div class="input-group">
              <input type="password" id="password_confirm" name="password_confirm" class="form-control"
                placeholder="Repeat password" required>
              <button type="button" class="input-btn" onclick="togglePw('password_confirm', this)">👁</button>
            </div>
            <div class="form-hint" id="pwMatchHint"></div>
          </div>
        </div>

        <hr style="border:none;border-top:1px solid #e5e7eb;margin:4px 0 18px;">

        <!-- Sponsor -->
        <div class="form-group">
          <label class="form-label" for="sponsor_username">
            Sponsor Username <span class="required">*</span>
          </label>
          <input
            type="text"
            id="sponsor_username"
            name="sponsor_username"
            class="form-control"
            placeholder="Your sponsor's username"
            autocomplete="off"
            required
          >
          <div class="form-hint" id="sponsorHint"></div>
        </div>

        <!-- Binary Upline + Position -->
        <div class="form-group">
          <label class="form-label" for="upline_username">
            Binary Upline Username <span class="required">*</span>
          </label>
          <input
            type="text"
            id="upline_username"
            name="upline_username"
            class="form-control"
            placeholder="Your upline in the binary tree"
            autocomplete="off"
            required
          >
          <div class="form-hint" id="uplineHint"></div>
          <!-- Slot status indicator -->
          <div id="slotStatus" class="slot-status" style="display:none;">
            <span id="leftSlot">↙ Left: —</span>
            <span id="rightSlot">↘ Right: —</span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">
            Binary Position <span class="required">*</span>
          </label>
          <div class="position-toggle">
            <div class="position-option">
              <input type="radio" id="pos_left" name="binary_position" value="left" required>
              <label class="position-label" for="pos_left" id="leftLabel">
                ↙ Left
              </label>
            </div>
            <div class="position-option">
              <input type="radio" id="pos_right" name="binary_position" value="right">
              <label class="position-label" for="pos_right" id="rightLabel">
                ↘ Right
              </label>
            </div>
          </div>
          <div class="form-hint" id="positionHint"></div>
        </div>

        <div style="display:flex;gap:10px;">
          <button type="button" class="btn btn-ghost" onclick="goStep(1)">← Back</button>
          <button type="button" class="btn btn-primary btn-full" id="toStep3Btn">
            Review →
          </button>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════
           STEP 3 — Confirm & Submit
      ════════════════════════════════════════════════════ -->
      <div class="auth-body" id="step3" style="display:none;">

        <p style="font-size:13px;color:#6b7a99;margin-bottom:18px;">
          Please review your information before completing registration.
        </p>

        <div class="card" style="margin-bottom:20px;">
          <div class="card-header">
            <span class="card-title">📋 Registration Summary</span>
          </div>
          <div class="card-body">
            <table class="info-table">
              <tr>
                <td>Registration Code</td>
                <td><span class="reg-code" id="rev_code">—</span></td>
              </tr>
              <tr>
                <td>Package</td>
                <td id="rev_package">—</td>
              </tr>
              <tr>
                <td>Username</td>
                <td id="rev_username" style="font-weight:700;">—</td>
              </tr>
              <tr>
                <td>Sponsor</td>
                <td id="rev_sponsor">—</td>
              </tr>
              <tr>
                <td>Binary Upline</td>
                <td id="rev_upline">—</td>
              </tr>
              <tr>
                <td>Binary Position</td>
                <td id="rev_position">—</td>
              </tr>
            </table>
          </div>
        </div>

        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:9px;padding:12px 14px;margin-bottom:18px;font-size:12px;color:#92400e;">
          ⚠️ Binary position cannot be changed after registration. Make sure the details above are correct.
        </div>

        <div style="display:flex;gap:10px;">
          <button type="button" class="btn btn-ghost" onclick="goStep(2)">← Back</button>
          <button type="submit" class="btn btn-primary btn-full btn-lg" id="submitBtn">
            <span id="submitText">✓ Complete Registration</span>
          </button>
        </div>
      </div>

    </form>

    <!-- Footer -->
    <div class="auth-footer">
      Already have an account? <a href="<?= APP_URL ?>/?page=login">Sign in →</a>
    </div>

  </div>
</div>

<script>
const API = '<?= APP_URL ?>';
let codeData    = {};
let usernameOk  = false;
let sponsorOk   = false;
let uplineOk    = false;
let slotData    = {};

// ── Step navigation ─────────────────────────────────────────
function goStep(n) {
  [1, 2, 3].forEach(i => {
    const el = document.getElementById('step' + i);
    if (el) el.style.display = i === n ? 'block' : 'none';
  });
  // Update progress indicators
  [1, 2, 3].forEach(i => {
    const ind = document.getElementById('step-ind-' + i);
    ind.className = 'step ' + (i < n ? 'done' : i === n ? 'active' : '');
  });
  window.scrollTo({top: 0, behavior: 'smooth'});
}

// ── Step 1: Code Validation ──────────────────────────────────
function formatCode(val) {
  const clean = val.replace(/[^A-Z0-9]/gi, '').toUpperCase().slice(0, 12);
  const parts = [clean.slice(0,4), clean.slice(4,8), clean.slice(8,12)].filter(Boolean);
  return parts.join('-');
}

const codeInput = document.getElementById('reg_code');
codeInput.addEventListener('input', function() {
  this.value = formatCode(this.value);
  resetCodeState();
});

function resetCodeState() {
  document.getElementById('packageInfo').style.display = 'none';
  document.getElementById('validatedCode').value = '';
  document.getElementById('toStep2Btn').disabled = true;
  document.getElementById('codeHint').textContent = '';
  document.getElementById('codeHint').className = 'form-hint';
  codeData = {};
}

document.getElementById('validateCodeBtn').addEventListener('click', async function() {
  const code = codeInput.value.trim();
  if (code.length < 14) {
    setHint('codeHint', 'Enter a complete code (XXXX-XXXX-XXXX)', false);
    return;
  }

  this.disabled = true;
  this.textContent = '…';

  try {
    const fd = new FormData();
    fd.append('code', code);
    fd.append('csrf_token', document.querySelector('[name=csrf_token]').value);
    const res = await fetch(API + '/?page=validate_code', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.valid) {
      codeData = data;
      document.getElementById('pkgName').textContent    = data.package_name;
      document.getElementById('pkgDetails').textContent =
        'Entry: ' + data.entry_fee + ' · Pairing Bonus: ' + data.pairing_bonus + ' · Cap: ' + data.daily_cap + ' pairs/day';
      document.getElementById('packageInfo').style.display = 'flex';
      document.getElementById('validatedCode').value = code;
      document.getElementById('toStep2Btn').disabled = false;
      setHint('codeHint', '✓ Code is valid!', true);
    } else {
      setHint('codeHint', data.message || 'Invalid code.', false);
    }
  } catch(e) {
    setHint('codeHint', 'Network error. Please try again.', false);
  }

  this.disabled = false;
  this.textContent = 'Validate';
});

document.getElementById('toStep2Btn').addEventListener('click', function() {
  if (!document.getElementById('validatedCode').value) return;
  goStep(2);
});

// ── Step 2: Account Setup ────────────────────────────────────
let usernameTimer, sponsorTimer, uplineTimer;

const usernameInput = document.getElementById('username');
usernameInput.addEventListener('input', function() {
  usernameOk = false;
  clearTimeout(usernameTimer);
  const val = this.value.trim();
  if (val.length < 3) { setHint('usernameHint', 'At least 3 characters required.', null); return; }
  setHint('usernameHint', 'Checking…', null);
  usernameTimer = setTimeout(() => checkUsername(val), 600);
});

async function checkUsername(val) {
  try {
    const res  = await fetch(API + '/?page=check_username&username=' + encodeURIComponent(val));
    const data = await res.json();
    usernameOk = data.available;
    setHint('usernameHint', data.message, data.available);
    usernameInput.classList.toggle('is-valid',   data.available);
    usernameInput.classList.toggle('is-invalid', !data.available);
  } catch(e) { setHint('usernameHint', 'Could not check.', null); }
}

// Password match
document.getElementById('password_confirm').addEventListener('input', function() {
  const pw  = document.getElementById('password').value;
  const ok  = pw && pw === this.value;
  const msg = !this.value ? '' : ok ? '✓ Passwords match.' : '✗ Passwords do not match.';
  setHint('pwMatchHint', msg, this.value ? ok : null);
});

// Sponsor check
const sponsorInput = document.getElementById('sponsor_username');
sponsorInput.addEventListener('input', function() {
  sponsorOk = false;
  clearTimeout(sponsorTimer);
  const val = this.value.trim();
  if (!val) { setHint('sponsorHint', '', null); return; }
  setHint('sponsorHint', 'Checking…', null);
  sponsorTimer = setTimeout(() => checkSponsor(val), 600);
});

async function checkSponsor(val) {
  try {
    const res  = await fetch(API + '/?page=check_username&username=' + encodeURIComponent(val));
    const data = await res.json();
    // Sponsor must EXIST (not be available)
    sponsorOk = !data.available; // available=false means username exists
    const msg = sponsorOk ? '✓ Sponsor @' + val + ' found.' : '✗ Sponsor not found.';
    setHint('sponsorHint', msg, sponsorOk);
  } catch(e) { setHint('sponsorHint', 'Could not check.', null); }
}

// Upline + slot check
const uplineInput = document.getElementById('upline_username');
uplineInput.addEventListener('input', function() {
  uplineOk = false;
  slotData = {};
  clearTimeout(uplineTimer);
  const val = this.value.trim();
  if (!val) {
    setHint('uplineHint', '', null);
    document.getElementById('slotStatus').style.display = 'none';
    resetPositionBtns();
    return;
  }
  setHint('uplineHint', 'Checking…', null);
  uplineTimer = setTimeout(() => checkUpline(val), 600);
});

document.querySelectorAll('[name=binary_position]').forEach(r => {
  r.addEventListener('change', function() { checkPositionValidity(this.value); });
});

async function checkUpline(val) {
  const pos = document.querySelector('[name=binary_position]:checked')?.value || '';
  try {
    const res  = await fetch(API + '/?page=check_upline&username=' + encodeURIComponent(val) + '&position=' + pos);
    const data = await res.json();

    if (!data.valid) {
      setHint('uplineHint', '✗ ' + data.message, false);
      document.getElementById('slotStatus').style.display = 'none';
      uplineOk = false;
      return;
    }

    slotData = data;
    uplineOk = true;
    setHint('uplineHint', '✓ Found @' + data.username, true);

    // Show slot availability
    const leftEl  = document.getElementById('leftSlot');
    const rightEl = document.getElementById('rightSlot');
    leftEl.textContent  = '↙ Left: '  + (data.left_free  ? '✓ Free' : '✗ Taken');
    leftEl.className    = data.left_free  ? 'slot-free' : 'slot-taken';
    rightEl.textContent = '↘ Right: ' + (data.right_free ? '✓ Free' : '✗ Taken');
    rightEl.className   = data.right_free ? 'slot-free' : 'slot-taken';
    document.getElementById('slotStatus').style.display = 'flex';

    // Enable/disable radio buttons
    const leftRadio  = document.getElementById('pos_left');
    const rightRadio = document.getElementById('pos_right');
    leftRadio.disabled  = !data.left_free;
    rightRadio.disabled = !data.right_free;

    // If current selection is now taken, auto-select the free one
    const curPos = document.querySelector('[name=binary_position]:checked')?.value;
    if (curPos === 'left' && !data.left_free && data.right_free) {
      document.getElementById('pos_right').checked = true;
    } else if (curPos === 'right' && !data.right_free && data.left_free) {
      document.getElementById('pos_left').checked = true;
    }
    checkPositionValidity(document.querySelector('[name=binary_position]:checked')?.value || '');
  } catch(e) {
    setHint('uplineHint', 'Could not check.', null);
  }
}

function checkPositionValidity(pos) {
  if (!slotData.username) { setHint('positionHint', '', null); return; }
  if (!pos) { setHint('positionHint', 'Please select a position.', null); return; }
  const free = pos === 'left' ? slotData.left_free : slotData.right_free;
  setHint('positionHint',
    free ? '✓ ' + pos.charAt(0).toUpperCase() + pos.slice(1) + ' slot is available.'
         : '✗ ' + pos.charAt(0).toUpperCase() + pos.slice(1) + ' slot is already taken.',
    free);
}

function resetPositionBtns() {
  document.getElementById('pos_left').disabled  = false;
  document.getElementById('pos_right').disabled = false;
  setHint('positionHint', '', null);
}

document.getElementById('toStep3Btn').addEventListener('click', function() {
  // Basic validation before proceeding
  const username = document.getElementById('username').value.trim();
  const pw       = document.getElementById('password').value;
  const pwc      = document.getElementById('password_confirm').value;
  const sponsor  = document.getElementById('sponsor_username').value.trim();
  const upline   = document.getElementById('upline_username').value.trim();
  const position = document.querySelector('[name=binary_position]:checked')?.value;

  if (!usernameOk)          { setHint('usernameHint', 'Please choose a valid username.', false); return; }
  if (pw.length < 8)        { alert('Password must be at least 8 characters.'); return; }
  if (pw !== pwc)           { setHint('pwMatchHint', 'Passwords do not match.', false); return; }
  if (!sponsorOk)           { setHint('sponsorHint', 'Please enter a valid sponsor.', false); return; }
  if (!uplineOk)            { setHint('uplineHint', 'Please enter a valid upline.', false); return; }
  if (!position)            { setHint('positionHint', 'Please select a position.', false); return; }

  const posSlotFree = position === 'left' ? slotData.left_free : slotData.right_free;
  if (!posSlotFree) { setHint('positionHint', 'Selected position is taken. Choose another.', false); return; }

  // Populate review step
  document.getElementById('rev_code').textContent     = document.getElementById('validatedCode').value;
  document.getElementById('rev_package').textContent  = codeData.package_name || '—';
  document.getElementById('rev_username').textContent = '@' + username;
  document.getElementById('rev_sponsor').textContent  = '@' + sponsor;
  document.getElementById('rev_upline').textContent   = '@' + upline;
  document.getElementById('rev_position').textContent = position.charAt(0).toUpperCase() + position.slice(1);

  goStep(3);
});

// ── Step 3: Submit ───────────────────────────────────────────
document.getElementById('regForm').addEventListener('submit', function() {
  const btn  = document.getElementById('submitBtn');
  const text = document.getElementById('submitText');
  btn.disabled = true;
  text.innerHTML = '<span class="spinner"></span> Creating account…';
});

// ── Utilities ────────────────────────────────────────────────
function setHint(id, msg, ok) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = msg;
  el.className = 'form-hint' + (ok === true ? ' is-valid' : ok === false ? ' is-invalid' : '');
}

function togglePw(id, btn) {
  const el = document.getElementById(id);
  if (el.type === 'password') { el.type = 'text';     btn.textContent = '🙈'; }
  else                        { el.type = 'password'; btn.textContent = '👁'; }
}
</script>
</body>
</html>
