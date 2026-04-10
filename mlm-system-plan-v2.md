# Binary MLM System — Complete Development Plan (v2)
**Stack:** PHP 8.1+ · MySQL 8.0+ · No Framework · Vanilla JS · CSS Custom Properties
**Architecture:** Single-entry `index.php` router, MVC-like separation, PDO for all DB access

---

## CORE ARCHITECTURE DECISION: REAL-TIME COMMISSIONS

All commissions fire **immediately** when triggered:

| Event                        | Commission Triggered               | When             |
|------------------------------|------------------------------------|------------------|
| New member registers         | Direct referral bonus → sponsor    | Instant          |
| New member registers         | Indirect referral (up to 10 lvls)  | Instant          |
| New member completes a pair  | Pairing bonus → each ancestor      | Instant          |
| Daily flush-out cap reached  | Pairs are lost (flushed)           | Instant          |
| Midnight cron                | Reset `pairs_paid_today = 0`       | Daily (cron only)|

The **cron job does exactly one thing**: reset the daily pair counter at midnight so members can earn pairing bonuses again the next day. Nothing else.

---

## PAIRING BONUS — REAL-TIME LOGIC EXPLAINED

### The Counter System (on the `users` table)

```
left_count       — total members ever placed in left subtree (increments up on each join)
right_count      — total members ever placed in right subtree (increments up on each join)
pairs_paid       — lifetime pairs that earned a bonus (never resets)
pairs_flushed    — lifetime pairs that were lost to daily cap (never resets)
pairs_paid_today — pairs that earned a bonus TODAY (resets to 0 at midnight via cron)
```

Key identity:
```
pairs_processed = pairs_paid + pairs_flushed   ← total pairs ever evaluated
new_pairs       = MIN(left_count, right_count) - pairs_processed
```

### What Happens When Member X Joins

```
New member X is placed at position P (left/right) under binary parent B.

Walk UP the tree from B toward root:
  For each ancestor A:
    1. Increment A.left_count  OR  A.right_count  (whichever side X is on)
    2. Compute new_pairs = MIN(A.left_count, A.right_count)
                         - (A.pairs_paid + A.pairs_flushed)
    3. If new_pairs == 0 → skip (no new pair formed for this ancestor)
    4. If new_pairs > 0:
         remaining_today = A.daily_pair_cap - A.pairs_paid_today
         pay_now   = MIN(new_pairs, remaining_today)
         flush_now = new_pairs - pay_now

         → Credit pairing bonus for pay_now pairs immediately
         → Update A.pairs_paid       += pay_now
         → Update A.pairs_flushed    += flush_now
         → Update A.pairs_paid_today += pay_now
         → Log flush_now to commissions as 'voided' (audit trail)
```

### Why This Works Perfectly

- Every join triggers an upward tree walk — O(depth) per registration
- At each ancestor, we know exactly how many new pairs formed RIGHT NOW
- Cap is enforced per-day per-member via `pairs_paid_today`
- Flushed pairs are permanently gone (can never be reclaimed)
- At midnight: `pairs_paid_today = 0` for all members → cap resets → they can earn again tomorrow
- No batch job, no delay, no "pending commissions" — member sees their balance go up the moment someone joins under them

---

## DATABASE SCHEMA

```sql
-- ─── PACKAGES ────────────────────────────────────────────────────────────────
CREATE TABLE packages (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name             VARCHAR(80)       NOT NULL,
  entry_fee        DECIMAL(12,2)     NOT NULL,
  pairing_bonus    DECIMAL(12,2)     NOT NULL,          -- per pair payout
  daily_pair_cap   TINYINT UNSIGNED  NOT NULL DEFAULT 3, -- flush-out limit per member per day
  direct_ref_bonus DECIMAL(12,2)     NOT NULL DEFAULT 0,
  status           ENUM('active','inactive') DEFAULT 'active',
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── INDIRECT REFERRAL LEVELS (1–10 per package) ──────────────────────────
CREATE TABLE package_indirect_levels (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  package_id INT UNSIGNED NOT NULL,
  level      TINYINT UNSIGNED NOT NULL,   -- 1 through 10
  bonus      DECIMAL(12,2)    NOT NULL DEFAULT 0,
  FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
  UNIQUE KEY uq_pkg_level (package_id, level)
);

-- ─── REGISTRATION CODES ───────────────────────────────────────────────────
CREATE TABLE reg_codes (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code        VARCHAR(20)    NOT NULL UNIQUE,
  package_id  INT UNSIGNED   NOT NULL,
  price       DECIMAL(12,2)  NOT NULL,
  status      ENUM('unused','used','expired') DEFAULT 'unused',
  used_by     INT UNSIGNED   NULL,
  created_by  INT UNSIGNED   NOT NULL,
  used_at     TIMESTAMP      NULL,
  expires_at  DATE           NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (package_id) REFERENCES packages(id)
);

-- ─── USERS ────────────────────────────────────────────────────────────────
CREATE TABLE users (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username         VARCHAR(40)   NOT NULL UNIQUE,
  password_hash    VARCHAR(255)  NOT NULL,
  role             ENUM('member','admin') DEFAULT 'member',
  package_id       INT UNSIGNED  NULL,
  reg_code_id      INT UNSIGNED  NULL,

  -- Binary tree
  sponsor_id       INT UNSIGNED  NULL,
  binary_parent_id INT UNSIGNED  NULL,
  binary_position  ENUM('left','right') NULL,

  -- Pair counters (never reset)
  left_count       INT UNSIGNED  NOT NULL DEFAULT 0,
  right_count      INT UNSIGNED  NOT NULL DEFAULT 0,
  pairs_paid       INT UNSIGNED  NOT NULL DEFAULT 0,   -- lifetime bonus-earning pairs
  pairs_flushed    INT UNSIGNED  NOT NULL DEFAULT 0,   -- lifetime lost pairs

  -- Daily pair counter (reset to 0 every midnight by cron)
  pairs_paid_today INT UNSIGNED  NOT NULL DEFAULT 0,

  -- Profile
  full_name        VARCHAR(120)  NULL,
  email            VARCHAR(120)  NULL,
  mobile           VARCHAR(20)   NULL,
  gcash_number     VARCHAR(20)   NULL,
  address          TEXT          NULL,
  photo            VARCHAR(200)  NULL,

  -- E-wallet
  ewallet_balance  DECIMAL(14,2) NOT NULL DEFAULT 0.00,

  status           ENUM('active','suspended','pending') DEFAULT 'active',
  joined_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login        TIMESTAMP NULL,

  FOREIGN KEY (sponsor_id)       REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (binary_parent_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (package_id)       REFERENCES packages(id),
  FOREIGN KEY (reg_code_id)      REFERENCES reg_codes(id)
);

-- ─── COMMISSIONS ──────────────────────────────────────────────────────────
CREATE TABLE commissions (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         INT UNSIGNED  NOT NULL,
  type            ENUM('pairing','direct_referral','indirect_referral') NOT NULL,
  amount          DECIMAL(12,2) NOT NULL DEFAULT 0,
  source_user_id  INT UNSIGNED  NULL,                -- new member who triggered it
  level           TINYINT UNSIGNED NULL,             -- for indirect
  pairs_count     TINYINT UNSIGNED NULL,             -- for pairing (how many pairs in this credit)
  description     VARCHAR(255)  NULL,
  status          ENUM('credited','flushed') NOT NULL DEFAULT 'credited',
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)        REFERENCES users(id),
  FOREIGN KEY (source_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ─── E-WALLET LEDGER ──────────────────────────────────────────────────────
CREATE TABLE ewallet_ledger (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED  NOT NULL,
  type          ENUM('credit','debit') NOT NULL,
  amount        DECIMAL(12,2) NOT NULL,
  reference_id  INT UNSIGNED  NULL,   -- commission_id or payout_request_id
  ref_type      ENUM('commission','payout') NULL,
  balance_after DECIMAL(14,2) NOT NULL,
  note          VARCHAR(255)  NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ─── PAYOUT REQUESTS ──────────────────────────────────────────────────────
CREATE TABLE payout_requests (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED  NOT NULL,
  amount        DECIMAL(12,2) NOT NULL,
  gcash_number  VARCHAR(20)   NOT NULL,
  status        ENUM('pending','approved','rejected','completed') DEFAULT 'pending',
  admin_note    TEXT          NULL,
  processed_by  INT UNSIGNED  NULL,
  requested_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  processed_at  TIMESTAMP NULL,
  FOREIGN KEY (user_id)      REFERENCES users(id),
  FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ─── SYSTEM SETTINGS ─────────────────────────────────────────────────────
CREATE TABLE settings (
  key_name   VARCHAR(80) NOT NULL PRIMARY KEY,
  value      TEXT        NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO settings VALUES
  ('site_name',    'MyMLM Network', NOW()),
  ('min_payout',   '500',           NOW()),
  ('last_reset',   '',              NOW());   -- timestamp of last midnight reset

-- ─── INDEXES ─────────────────────────────────────────────────────────────
ALTER TABLE users         ADD INDEX idx_sponsor      (sponsor_id);
ALTER TABLE users         ADD INDEX idx_binary_parent (binary_parent_id, binary_position);
ALTER TABLE commissions   ADD INDEX idx_user_type    (user_id, type, created_at);
ALTER TABLE commissions   ADD INDEX idx_source       (source_user_id);
ALTER TABLE reg_codes     ADD INDEX idx_status       (status);
ALTER TABLE ewallet_ledger ADD INDEX idx_user        (user_id, created_at);
ALTER TABLE payout_requests ADD INDEX idx_user_status (user_id, status);
```

---

## FILE STRUCTURE

```
/project-root
├── index.php                    ← front controller / router
├── config/
│   └── db.php                   ← PDO connection + app constants
├── core/
│   ├── Auth.php                 ← session, login, guard, current user
│   ├── helpers.php              ← fmt_money(), generate_code(), e(), redirect(), flash()
│   └── Commission.php           ← ALL commission logic (real-time engine)
├── models/
│   ├── User.php                 ← register(), find(), update(), binary tree walk
│   ├── Package.php              ← CRUD, indirect levels
│   ├── Code.php                 ← generate(), validate(), export
│   ├── Ewallet.php              ← credit(), debit(), ledger(), balance()
│   └── Payout.php               ← request(), approve(), reject(), complete()
├── controllers/
│   ├── AuthController.php
│   ├── MemberController.php
│   └── AdminController.php
├── views/
│   ├── auth/
│   │   ├── login.php
│   │   └── register.php
│   ├── member/
│   │   ├── dashboard.php
│   │   ├── earnings.php
│   │   ├── genealogy_binary.php
│   │   ├── genealogy_referral.php
│   │   ├── profile.php
│   │   └── payout.php
│   ├── admin/
│   │   ├── dashboard.php
│   │   ├── users.php
│   │   ├── user_view.php
│   │   ├── packages.php
│   │   ├── codes.php
│   │   ├── payouts.php
│   │   └── settings.php
│   └── partials/
│       ├── head.php             ← <head>, CSS links
│       ├── sidebar_member.php
│       ├── sidebar_admin.php
│       ├── topbar.php
│       └── footer.php
├── assets/
│   ├── css/
│   │   ├── main.css             ← design tokens, reset, typography
│   │   ├── auth.css
│   │   ├── layout.css           ← sidebar shell, topbar, responsive
│   │   └── components.css       ← cards, tables, badges, modals, forms
│   └── js/
│       ├── sidebar.js           ← toggle, swipe-to-close
│       ├── genealogy.js         ← binary tree SVG renderer
│       └── register.js          ← multi-step form, AJAX code validation
├── cron/
│   └── midnight_reset.php       ← ONLY job: UPDATE users SET pairs_paid_today = 0
├── exports/                     ← temp folder for generated files (gitignored)
├── uploads/                     ← profile photos
└── logs/                        ← cron logs
```

---

## PHASE 1 — Infrastructure & Schema

**Deliverables:**
- Full schema (all tables above) with seed data
- `config/db.php` — PDO singleton
- `core/helpers.php` — all utility functions
- `core/Auth.php` — session management
- `index.php` — route map + dispatcher
- Default admin account: `admin` / `admin123` (forced change on first login)
- Seed: 1 active package with sample commission rates

**Key code — `config/db.php`:**
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mlm_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_URL',  'http://localhost/mlm');
define('APP_NAME', 'MyMLM Network');

function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
```

**Key code — `index.php` router:**
```php
<?php
session_start();
require_once 'config/db.php';
require_once 'core/helpers.php';
require_once 'core/Auth.php';
require_once 'core/Commission.php';

spl_autoload_register(function($class) {
    foreach (['models/', 'controllers/'] as $dir) {
        $f = $dir . $class . '.php';
        if (file_exists($f)) { require_once $f; return; }
    }
});

$page = $_GET['page'] ?? 'login';

$routes = [
    // [ControllerClass, method, role: 'guest'|'member'|'admin'|'any']
    'login'            => ['AuthController',   'showLogin',      'guest'],
    'do_login'         => ['AuthController',   'doLogin',        'guest'],
    'register'         => ['AuthController',   'showRegister',   'guest'],
    'do_register'      => ['AuthController',   'doRegister',     'guest'],
    'validate_code'    => ['AuthController',   'ajaxValidateCode','guest'],  // AJAX
    'check_username'   => ['AuthController',   'ajaxCheckUser',  'guest'],   // AJAX
    'check_upline'     => ['AuthController',   'ajaxCheckUpline','guest'],   // AJAX
    'logout'           => ['AuthController',   'logout',         'any'],

    'dashboard'        => ['MemberController', 'dashboard',      'member'],
    'profile'          => ['MemberController', 'profile',        'member'],
    'save_profile'     => ['MemberController', 'saveProfile',    'member'],
    'earnings'         => ['MemberController', 'earnings',       'member'],
    'genealogy'        => ['MemberController', 'genealogy',      'member'],
    'payout'           => ['MemberController', 'payout',         'member'],
    'request_payout'   => ['MemberController', 'requestPayout',  'member'],
    'api_binary_tree'  => ['MemberController', 'apiBinaryTree',  'member'],  // AJAX

    'admin'            => ['AdminController',  'dashboard',      'admin'],
    'admin_users'      => ['AdminController',  'users',          'admin'],
    'admin_user_view'  => ['AdminController',  'viewUser',       'admin'],
    'admin_toggle_user'=> ['AdminController',  'toggleUser',     'admin'],
    'admin_packages'   => ['AdminController',  'packages',       'admin'],
    'admin_save_package'=> ['AdminController', 'savePackage',    'admin'],
    'admin_codes'      => ['AdminController',  'codes',          'admin'],
    'admin_gen_codes'  => ['AdminController',  'generateCodes',  'admin'],
    'admin_export_codes'=> ['AdminController', 'exportCodes',    'admin'],
    'admin_payouts'    => ['AdminController',  'payouts',        'admin'],
    'admin_payout_action'=>['AdminController', 'payoutAction',   'admin'],
    'admin_settings'   => ['AdminController',  'settings',       'admin'],
    'admin_save_settings'=>['AdminController', 'saveSettings',   'admin'],
];

$route = $routes[$page] ?? $routes['login'];
[$ctrl, $method, $role] = $route;

if ($role === 'guest'  && Auth::check()) redirect(Auth::isAdmin() ? '/?page=admin' : '/?page=dashboard');
if ($role === 'member' && !Auth::check()) redirect('/?page=login');
if ($role === 'admin'  && !Auth::isAdmin()) redirect('/?page=login');

(new $ctrl)->$method();
```

---

## PHASE 2 — Auth & Registration

**Deliverables:**
- Login page (centered card, mobile-first)
- 3-step registration with real-time AJAX validation
- Binary slot checking
- Real-time commission firing on successful registration

**`core/Commission.php` — THE ENGINE:**
```php
<?php
class Commission {

    /**
     * Called immediately when a new member is placed in the binary tree.
     * Walks upward, updating counts and firing pairing bonuses in real time.
     */
    public static function processBinaryPlacement(int $newUserId, int $parentId, string $position): void {
        $pdo = db();

        // Walk up the tree from the immediate parent toward root
        $cur       = $parentId;
        $side      = $position; // which side of $cur did the new member land on?

        while ($cur !== null) {
            // 1. Increment the appropriate leg count
            $col = ($side === 'left') ? 'left_count' : 'right_count';
            $pdo->prepare("UPDATE users SET $col = $col + 1 WHERE id = ?")->execute([$cur]);

            // 2. Read fresh counts for this ancestor
            $row = $pdo->prepare("
                SELECT left_count, right_count, pairs_paid, pairs_flushed,
                       pairs_paid_today, p.pairing_bonus, p.daily_pair_cap
                FROM users u
                JOIN packages p ON p.id = u.package_id
                WHERE u.id = ?
            ");
            $row->execute([$cur]);
            $a = $row->fetch();

            if ($a) {
                $processed  = $a['pairs_paid'] + $a['pairs_flushed'];
                $available  = min($a['left_count'], $a['right_count']);
                $new_pairs  = $available - $processed;

                if ($new_pairs > 0) {
                    $remaining_today = $a['daily_pair_cap'] - $a['pairs_paid_today'];
                    $pay_now         = min($new_pairs, max(0, $remaining_today));
                    $flush_now       = $new_pairs - $pay_now;

                    // Pay the earned pairs
                    if ($pay_now > 0) {
                        $bonus = $pay_now * $a['pairing_bonus'];
                        self::creditPairing($cur, $bonus, $pay_now, $newUserId);
                    }

                    // Record flushed pairs (lost forever, audit trail only)
                    if ($flush_now > 0) {
                        self::recordFlush($cur, $flush_now, $newUserId);
                    }

                    // Update counters on this ancestor
                    $pdo->prepare("
                        UPDATE users
                        SET pairs_paid       = pairs_paid       + ?,
                            pairs_flushed    = pairs_flushed    + ?,
                            pairs_paid_today = pairs_paid_today + ?
                        WHERE id = ?
                    ")->execute([$pay_now, $flush_now, $pay_now, $cur]);
                }
            }

            // Move up: find this ancestor's parent and which side $cur is on
            $up = $pdo->prepare("SELECT binary_parent_id, binary_position FROM users WHERE id = ?");
            $up->execute([$cur]);
            $upRow = $up->fetch();
            $side  = $upRow['binary_position'] ?? null;
            $cur   = $upRow['binary_parent_id'] ?? null;
        }
    }

    /**
     * Credits pairing bonus to e-wallet immediately.
     */
    private static function creditPairing(int $userId, float $amount, int $pairs, int $sourceId): void {
        $pdo = db();
        $pdo->prepare("
            INSERT INTO commissions
              (user_id, type, amount, source_user_id, pairs_count, description, status)
            VALUES (?, 'pairing', ?, ?, ?, ?, 'credited')
        ")->execute([
            $userId, $amount, $sourceId, $pairs,
            "$pairs pair(s) × ₱" . number_format($amount / $pairs, 2)
        ]);
        $commId = $pdo->lastInsertId();
        Ewallet::credit($userId, $amount, $commId, 'commission', "Pairing bonus — $pairs pair(s)");
    }

    /**
     * Records flushed pairs to commissions table for audit.
     * No money credited. pairs_flushed counter updated by caller.
     */
    private static function recordFlush(int $userId, int $pairs, int $sourceId): void {
        db()->prepare("
            INSERT INTO commissions
              (user_id, type, amount, source_user_id, pairs_count, description, status)
            VALUES (?, 'pairing', 0, ?, ?, ?, 'flushed')
        ")->execute([
            $userId, $sourceId, $pairs,
            "$pairs pair(s) flushed — daily cap reached"
        ]);
    }

    /**
     * Fires direct referral bonus to sponsor immediately on registration.
     */
    public static function processDirectReferral(int $sponsorId, int $newUserId, int $packageId): void {
        $pkg = Package::find($packageId);
        if (!$pkg || $pkg['direct_ref_bonus'] <= 0) return;

        $pdo = db();
        $pdo->prepare("
            INSERT INTO commissions
              (user_id, type, amount, source_user_id, description, status)
            VALUES (?, 'direct_referral', ?, ?, 'Direct referral bonus', 'credited')
        ")->execute([$sponsorId, $pkg['direct_ref_bonus'], $newUserId]);
        $commId = $pdo->lastInsertId();
        Ewallet::credit($sponsorId, $pkg['direct_ref_bonus'], $commId, 'commission', 'Direct referral bonus');
    }

    /**
     * Fires indirect referral bonuses up the sponsor chain (levels 1–10) immediately.
     */
    public static function processIndirectReferral(int $directSponsorId, int $newUserId, int $packageId): void {
        $levels = Package::getIndirectLevels($packageId); // [1 => amount, 2 => amount, ...]
        $cur    = $directSponsorId;

        for ($lvl = 1; $lvl <= 10; $lvl++) {
            // Go up one level in the SPONSOR chain (not binary)
            $row = db()->prepare("SELECT sponsor_id FROM users WHERE id = ?");
            $row->execute([$cur]);
            $cur = $row->fetchColumn();
            if (!$cur) break;

            $bonus = $levels[$lvl] ?? 0;
            if ($bonus <= 0) continue;

            db()->prepare("
                INSERT INTO commissions
                  (user_id, type, amount, source_user_id, level, description, status)
                VALUES (?, 'indirect_referral', ?, ?, ?, ?, 'credited')
            ")->execute([
                $cur, $bonus, $newUserId, $lvl,
                "Indirect referral bonus — Level $lvl"
            ]);
            $commId = db()->lastInsertId();
            Ewallet::credit($cur, $bonus, $commId, 'commission', "Indirect referral — Level $lvl");
        }
    }
}
```

**`models/Ewallet.php`:**
```php
<?php
class Ewallet {

    public static function credit(int $userId, float $amount, int $refId, string $refType, string $note): void {
        $pdo = db();
        $pdo->prepare("UPDATE users SET ewallet_balance = ewallet_balance + ? WHERE id = ?")
            ->execute([$amount, $userId]);
        $bal = (float)$pdo->query("SELECT ewallet_balance FROM users WHERE id = $userId")->fetchColumn();
        $pdo->prepare("
            INSERT INTO ewallet_ledger (user_id, type, amount, reference_id, ref_type, balance_after, note)
            VALUES (?, 'credit', ?, ?, ?, ?, ?)
        ")->execute([$userId, $amount, $refId, $refType, $bal, $note]);
    }

    public static function debit(int $userId, float $amount, int $refId, string $refType, string $note): bool {
        $pdo = db();
        // Check balance first
        $bal = (float)$pdo->query("SELECT ewallet_balance FROM users WHERE id = $userId")->fetchColumn();
        if ($bal < $amount) return false;

        $pdo->prepare("UPDATE users SET ewallet_balance = ewallet_balance - ? WHERE id = ?")
            ->execute([$amount, $userId]);
        $newBal = $bal - $amount;
        $pdo->prepare("
            INSERT INTO ewallet_ledger (user_id, type, amount, reference_id, ref_type, balance_after, note)
            VALUES (?, 'debit', ?, ?, ?, ?, ?)
        ")->execute([$userId, $amount, $refId, $refType, $newBal, $note]);
        return true;
    }

    public static function ledger(int $userId, int $limit = 30): array {
        $st = db()->prepare("
            SELECT * FROM ewallet_ledger WHERE user_id = ?
            ORDER BY created_at DESC LIMIT $limit
        ");
        $st->execute([$userId]);
        return $st->fetchAll();
    }
}
```

**`models/User.php` — register() with real-time commissions:**
```php
<?php
class User {

    public static function register(array $data): int {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            // 1. Verify binary slot is available
            $slotCheck = $pdo->prepare("
                SELECT COUNT(*) FROM users
                WHERE binary_parent_id = ? AND binary_position = ?
            ");
            $slotCheck->execute([$data['binary_parent_id'], $data['binary_position']]);
            if ($slotCheck->fetchColumn() > 0) {
                throw new Exception("That binary position is already taken. Please choose another.");
            }

            // 2. Insert new user
            $hash = password_hash($data['password'], PASSWORD_BCRYPT);
            $st = $pdo->prepare("
                INSERT INTO users
                  (username, password_hash, package_id, reg_code_id,
                   sponsor_id, binary_parent_id, binary_position)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $st->execute([
                $data['username'],
                $hash,
                $data['package_id'],
                $data['reg_code_id'],
                $data['sponsor_id'],
                $data['binary_parent_id'],
                $data['binary_position'],
            ]);
            $newId = (int)$pdo->lastInsertId();

            // 3. Mark registration code as used
            $pdo->prepare("UPDATE reg_codes SET status='used', used_by=?, used_at=NOW() WHERE id=?")
                ->execute([$newId, $data['reg_code_id']]);

            $pdo->commit();

            // ── REAL-TIME COMMISSIONS (outside transaction to avoid lock contention) ──

            // 4. Walk binary tree up and fire pairing bonuses immediately
            Commission::processBinaryPlacement($newId, $data['binary_parent_id'], $data['binary_position']);

            // 5. Direct referral bonus → sponsor (immediately)
            Commission::processDirectReferral($data['sponsor_id'], $newId, $data['package_id']);

            // 6. Indirect referral bonuses → sponsor chain levels 1–10 (immediately)
            Commission::processIndirectReferral($data['sponsor_id'], $newId, $data['package_id']);

            return $newId;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function find(int $id): ?array {
        $st = db()->prepare("SELECT u.*, p.name AS package_name, p.pairing_bonus, p.daily_pair_cap
                             FROM users u LEFT JOIN packages p ON p.id = u.package_id WHERE u.id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public static function findByUsername(string $username): ?array {
        $st = db()->prepare("SELECT * FROM users WHERE username = ?");
        $st->execute([$username]);
        return $st->fetch() ?: null;
    }

    /**
     * Returns today's pairing status for dashboard display.
     */
    public static function todayPairingStatus(int $userId): array {
        $u = self::find($userId);
        if (!$u) return [];
        return [
            'left_count'       => $u['left_count'],
            'right_count'      => $u['right_count'],
            'pairs_paid'       => $u['pairs_paid'],
            'pairs_flushed'    => $u['pairs_flushed'],
            'pairs_paid_today' => $u['pairs_paid_today'],
            'daily_cap'        => $u['daily_pair_cap'],
            'cap_remaining'    => max(0, $u['daily_pair_cap'] - $u['pairs_paid_today']),
            'pairing_bonus'    => $u['pairing_bonus'],
            'earned_today'     => $u['pairs_paid_today'] * $u['pairing_bonus'],
        ];
    }
}
```

---

## PHASE 3 — Member Dashboard

**Deliverables:**
- Sidebar layout (slide-in mobile, fixed desktop)
- 6 pages: Home, Earnings, Binary Genealogy, Referral Genealogy, Profile, Payout
- Real-time pairing status widget (today's cap, remaining slots)

**UI Design Tokens:**
```css
:root {
  /* Colors */
  --bg:           #f5f7fa;
  --surface:      #ffffff;
  --surface2:     #f0f3f8;
  --border:       #e2e8f0;
  --text:         #1a2035;
  --text-muted:   #64748b;
  --primary:      #2563eb;
  --primary-dark: #1d4ed8;
  --primary-bg:   #eff6ff;
  --success:      #16a34a;
  --danger:       #dc2626;
  --warning:      #d97706;
  --purple:       #7c3aed;

  /* Layout */
  --sidebar-w:    260px;
  --topbar-h:     60px;
  --radius:       10px;
  --shadow:       0 1px 3px rgba(0,0,0,.08);
  --shadow-md:    0 4px 16px rgba(0,0,0,.10);
}

/* Sidebar shell */
.shell { display: flex; min-height: 100vh; }
.sidebar {
  width: var(--sidebar-w);
  position: fixed; top: 0; left: 0; bottom: 0;
  background: var(--text);      /* dark sidebar */
  color: #fff;
  display: flex; flex-direction: column;
  z-index: 100;
  transition: transform .25s ease;
}
.main-area {
  flex: 1;
  margin-left: var(--sidebar-w);
  display: flex; flex-direction: column;
  min-height: 100vh;
}
.topbar {
  height: var(--topbar-h);
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  position: sticky; top: 0; z-index: 50;
  display: flex; align-items: center; padding: 0 24px;
}
.content { padding: 24px; flex: 1; }

@media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); }
  .sidebar.open { transform: translateX(0); }
  .main-area { margin-left: 0; }
  .sidebar-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,.4);
    z-index: 99;
    display: none;
  }
  .sidebar-overlay.show { display: block; }
}
```

**Dashboard Home — Pairing Status Widget:**
```html
<!-- Real-time pairing cap display -->
<div class="pair-status-card">
  <div class="pair-status-header">
    <span>Today's Pairing Cap</span>
    <span class="badge-reset">Resets at midnight</span>
  </div>
  <div class="cap-bar">
    <div class="cap-fill" style="width: <?= ($status['pairs_paid_today'] / $status['daily_cap']) * 100 ?>%"></div>
  </div>
  <div class="cap-labels">
    <span><?= $status['pairs_paid_today'] ?> pairs earned today</span>
    <span><?= $status['cap_remaining'] ?> remaining</span>
  </div>
  <div class="cap-earned">
    Earned today: <strong><?= fmt_money($status['earned_today']) ?></strong>
  </div>
</div>
```

**Binary Genealogy — AJAX API (`/?page=api_binary_tree`):**
```php
// Returns JSON for the SVG tree renderer
function apiBinaryTree() {
    Auth::guard('member');
    $rootId = $_GET['root'] ?? Auth::user()['id'];
    $depth  = min((int)($_GET['depth'] ?? 3), 5); // max 5 levels deep per request

    header('Content-Type: application/json');
    echo json_encode(buildTreeNode((int)$rootId, $depth));
}

function buildTreeNode(int $id, int $depth): array {
    $u = User::find($id);
    if (!$u) return [];

    $node = [
        'id'         => $u['id'],
        'username'   => $u['username'],
        'status'     => $u['status'],
        'package'    => $u['package_name'],
        'joined'     => $u['joined_at'],
        'left_count' => $u['left_count'],
        'right_count'=> $u['right_count'],
        'left'       => null,
        'right'      => null,
    ];

    if ($depth > 0) {
        // Find left child
        $st = db()->prepare("SELECT id FROM users WHERE binary_parent_id = ? AND binary_position = 'left'");
        $st->execute([$id]);
        $lc = $st->fetchColumn();
        if ($lc) $node['left'] = buildTreeNode((int)$lc, $depth - 1);

        // Find right child
        $st = db()->prepare("SELECT id FROM users WHERE binary_parent_id = ? AND binary_position = 'right'");
        $st->execute([$id]);
        $rc = $st->fetchColumn();
        if ($rc) $node['right'] = buildTreeNode((int)$rc, $depth - 1);
    }

    return $node;
}
```

---

## PHASE 4 — The Cron Job (Midnight Reset Only)

**This is the ENTIRE job. Nothing else.**

```php
<?php
// cron/midnight_reset.php
// Cron: 0 0 * * * php /path/to/cron/midnight_reset.php

require_once __DIR__ . '/../config/db.php';

$pdo  = db();
$date = date('Y-m-d H:i:s');

try {
    // THE ONE JOB: reset daily pair counter for all active members
    $affected = $pdo->exec("UPDATE users SET pairs_paid_today = 0 WHERE role = 'member'");

    // Log the reset
    $pdo->prepare("UPDATE settings SET value = ? WHERE key_name = 'last_reset'")
        ->execute([$date]);

    echo "[$date] Midnight reset complete. Members reset: $affected\n";

} catch (Exception $e) {
    echo "[$date] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
```

**Crontab setup:**
```bash
# Edit crontab
crontab -e

# Run exactly at midnight every day
0 0 * * * /usr/bin/php /var/www/html/mlm/cron/midnight_reset.php >> /var/www/html/mlm/logs/reset.log 2>&1
```

**Why this is all the cron needs to do:**
- Pairing bonuses: fired in real time during registration ✓
- Direct referral: fired in real time during registration ✓
- Indirect referral: fired in real time during registration ✓
- Daily cap: enforced in real time via `pairs_paid_today` ✓
- Payout deductions: happen immediately when admin marks complete ✓
- The only thing that needs a scheduled trigger is "new day = reset cap" ✓

**Admin manual reset button** (for testing):
```php
// Admin can trigger manually: POST /?page=admin_manual_reset
// Same code as cron, protected by CSRF + admin auth
public function manualReset(): void {
    Auth::guard('admin');
    // CSRF check...
    $affected = db()->exec("UPDATE users SET pairs_paid_today = 0 WHERE role = 'member'");
    flash('success', "Daily pair counter reset for $affected members.");
    redirect('/?page=admin_settings');
}
```

---

## PHASE 5 — Admin Dashboard

**Deliverables:**
- Same sidebar shell with admin-specific nav
- Overview with KPIs and live stats
- Member management with detail view
- Package CRUD (with 10-level indirect config)
- Code generation + CSV export + print-to-PDF view
- Payout approval workflow
- Settings page + manual reset trigger

**Payout Workflow (`models/Payout.php`):**
```php
<?php
class Payout {

    public static function request(int $userId, float $amount, string $gcash): int|false {
        $minPayout = (float)(db()->query("SELECT value FROM settings WHERE key_name='min_payout'")->fetchColumn() ?: 500);
        $balance   = (float)(db()->query("SELECT ewallet_balance FROM users WHERE id=$userId")->fetchColumn());

        if ($amount < $minPayout)  return false;
        if ($amount > $balance)    return false;

        $st = db()->prepare("
            INSERT INTO payout_requests (user_id, amount, gcash_number)
            VALUES (?, ?, ?)
        ");
        $st->execute([$userId, $amount, $gcash]);
        return (int)db()->lastInsertId();
    }

    /**
     * Admin marks the payout as completed AFTER manually sending via GCash.
     * This is the point where the e-wallet balance is actually deducted.
     */
    public static function complete(int $payoutId, int $adminId, string $note = ''): bool {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $req = $pdo->query("SELECT * FROM payout_requests WHERE id = $payoutId")->fetch();
            if (!$req || $req['status'] !== 'approved') throw new Exception("Invalid payout.");

            // Deduct from e-wallet
            $ok = Ewallet::debit($req['user_id'], $req['amount'], $payoutId, 'payout',
                'Payout completed — GCash ' . $req['gcash_number']);
            if (!$ok) throw new Exception("Insufficient balance.");

            // Mark complete
            $pdo->prepare("
                UPDATE payout_requests
                SET status='completed', processed_by=?, admin_note=?, processed_at=NOW()
                WHERE id=?
            ")->execute([$adminId, $note, $payoutId]);

            $pdo->commit();
            return true;

        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public static function approve(int $payoutId, int $adminId): void {
        // Approve = admin has reviewed, now they'll send GCash and mark complete
        db()->prepare("
            UPDATE payout_requests SET status='approved', processed_by=?, processed_at=NOW()
            WHERE id=? AND status='pending'
        ")->execute([$adminId, $payoutId]);
    }

    public static function reject(int $payoutId, int $adminId, string $reason): void {
        // Rejected: balance NOT deducted (was never deducted on request)
        db()->prepare("
            UPDATE payout_requests SET status='rejected', processed_by=?, admin_note=?, processed_at=NOW()
            WHERE id=? AND status='pending'
        ")->execute([$adminId, $reason, $payoutId]);
    }
}
```

**Code Generation + Export (`models/Code.php`):**
```php
public static function generate(int $packageId, int $qty, float $price, ?string $expires, int $adminId): array {
    $pdo   = db();
    $codes = [];

    for ($i = 0; $i < $qty; $i++) {
        do {
            $code = generate_code(); // from helpers.php
            $exists = $pdo->query("SELECT COUNT(*) FROM reg_codes WHERE code='$code'")->fetchColumn();
        } while ($exists);

        $pdo->prepare("
            INSERT INTO reg_codes (code, package_id, price, expires_at, created_by)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$code, $packageId, $price, $expires ?: null, $adminId]);

        $codes[] = $code;
    }
    return $codes;
}

public static function exportCSV(array $codeIds): void {
    $ids = implode(',', array_map('intval', $codeIds));
    $rows = db()->query("
        SELECT r.code, p.name AS package, r.price, r.status, r.created_at, r.expires_at
        FROM reg_codes r JOIN packages p ON p.id = r.package_id
        WHERE r.id IN ($ids)
        ORDER BY r.created_at DESC
    ")->fetchAll();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="codes_' . date('Y-m-d') . '.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Code', 'Package', 'Price', 'Status', 'Created', 'Expires']);
    foreach ($rows as $r) fputcsv($f, array_values($r));
    fclose($f);
}
```

---

## PHASE 6 — Security & Polish

**Security checklist applied to every phase:**

```php
// CSRF token generation (in session start)
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// CSRF check (every POST)
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403);
        die('Invalid request token.');
    }
}

// In every form:
// <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

// Login rate limiting (in AuthController)
$fails = $_SESSION['login_fails'] ?? 0;
$last  = $_SESSION['login_last_fail'] ?? 0;
if ($fails >= 5 && (time() - $last) < 900) {
    flash('error', 'Too many failed attempts. Try again in 15 minutes.');
    redirect('/?page=login');
}
```

**Mobile sidebar JS (`assets/js/sidebar.js`):**
```javascript
const sidebar  = document.getElementById('sidebar');
const overlay  = document.getElementById('sidebarOverlay');
const hamburger= document.getElementById('hamburger');

hamburger.addEventListener('click', () => toggleSidebar(true));
overlay.addEventListener('click', () => toggleSidebar(false));

function toggleSidebar(open) {
  sidebar.classList.toggle('open', open);
  overlay.classList.toggle('show', open);
}

// Swipe support
let touchStartX = 0;
document.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, {passive:true});
document.addEventListener('touchend', e => {
  const dx = e.changedTouches[0].clientX - touchStartX;
  if (dx > 60 && touchStartX < 40) toggleSidebar(true);   // swipe right from edge → open
  if (dx < -60) toggleSidebar(false);                       // swipe left → close
}, {passive:true});
```

---

## COMPUTATION REFERENCE

```
─── On every Registration ──────────────────────────────────────────────

Walk binary tree upward from new member's parent:
  For each ancestor A:
    processed   = A.pairs_paid + A.pairs_flushed
    available   = MIN(A.left_count, A.right_count)   ← after incrementing the leg
    new_pairs   = available - processed
    if new_pairs > 0:
      remaining   = A.daily_pair_cap - A.pairs_paid_today
      pay_now     = MIN(new_pairs, remaining)          ← REAL-TIME CREDIT
      flush_now   = new_pairs - pay_now                ← PERMANENTLY LOST
      bonus       = pay_now × A.pairing_bonus          ← goes to A.ewallet_balance

Direct Referral   → sponsor gets package.direct_ref_bonus immediately
Indirect Referral → walk sponsor chain up 10 levels, pay package_indirect_levels[n].bonus

─── At Midnight (cron) ─────────────────────────────────────────────────

UPDATE users SET pairs_paid_today = 0   ← that's it

─── Payout ──────────────────────────────────────────────────────────────

Member requests → admin approves → admin sends GCash manually
→ admin marks complete → ewallet_balance deducted → ledger logged
```

---

## PHASED DELIVERY SUMMARY

| Phase | Scope                                                   | Cron? |
|-------|---------------------------------------------------------|-------|
| **1** | DB schema, PDO config, router, helpers                  | —     |
| **2** | Auth, registration, real-time Commission engine         | —     |
| **3** | Member dashboard — all 6 pages, genealogy trees         | —     |
| **4** | Midnight reset cron (single SQL statement)              | ✓     |
| **5** | Admin dashboard — all pages, CRUD, exports, payouts     | —     |
| **6** | CSRF, rate-limit, mobile polish, email notifications    | —     |

*All financial computation is real-time. The cron is a utility, not a business logic runner.*
