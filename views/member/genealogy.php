<?php $pageTitle = $view === 'referral' ? 'Referral Network' : 'Binary Tree'; ?>
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
  <style>
    /* Binary tree canvas styles */
    #treeContainer { overflow: auto; background: var(--surface2); border-radius: var(--radius); min-height: 360px; position: relative; cursor: grab; }
    #treeContainer:active { cursor: grabbing; }
    #treeCanvas { display: block; }
    .tree-loading { display:flex;align-items:center;justify-content:center;height:300px;color:var(--text-muted);font-size:14px;gap:10px; }

    /* Referral tree */
    .ref-level-group { margin-bottom: 8px; }
    .ref-level-header {
      display: flex; align-items: center; gap: 10px;
      padding: 8px 16px; background: var(--surface2);
      border-radius: var(--radius); font-size: 12px; font-weight: 700;
      color: var(--text-muted); letter-spacing: .5px; text-transform: uppercase;
      margin-bottom: 4px; cursor: pointer; user-select: none;
    }
    .ref-level-header:hover { background: var(--surface3); }
    .ref-level-members { display: flex; flex-direction: column; gap: 0; }
    .ref-member-row {
      display: flex; align-items: center; gap: 12px;
      padding: 10px 16px; border-bottom: 1px solid var(--border-light);
      transition: background var(--transition);
    }
    .ref-member-row:last-child { border-bottom: none; }
    .ref-member-row:hover { background: var(--surface2); }
    .ref-avatar {
      width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
      background: var(--primary-light); color: var(--primary);
      display: flex; align-items: center; justify-content: center;
      font-size: 14px; font-weight: 700;
    }
    .ref-name { font-weight: 600; font-size: 13px; }
    .ref-meta { font-size: 11px; color: var(--text-muted); margin-top: 1px; }
    .ref-indent { padding-left: calc(16px + var(--indent, 0px)); }
  </style>
</head>
<body>
<div class="shell">
  <?php require 'views/partials/sidebar_member.php'; ?>
  <div class="main-area">
    <?php require 'views/partials/topbar.php'; ?>
    <main class="page-content">

      <!-- View switcher -->
      <div style="margin-bottom:16px;">
        <div class="tab-bar" style="display:inline-flex;">
          <a href="<?= APP_URL ?>/?page=genealogy&view=binary"
             class="tab-btn <?= $view !== 'referral' ? 'active' : '' ?>">🌳 Binary Tree</a>
          <a href="<?= APP_URL ?>/?page=genealogy&view=referral"
             class="tab-btn <?= $view === 'referral' ? 'active' : '' ?>">👥 Referral Network</a>
        </div>
      </div>

      <?php if ($view !== 'referral'): ?>
      <!-- ══ BINARY TREE VIEW ══════════════════════════════════ -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">🌳 Binary Tree</span>
          <div style="display:flex;gap:8px;align-items:center;">
            <button class="btn btn-ghost btn-sm" onclick="resetTree()">⟳ Reset</button>
            <button class="btn btn-ghost btn-sm" onclick="zoomTree(-0.2)">−</button>
            <button class="btn btn-ghost btn-sm" onclick="zoomTree(0.2)">+</button>
          </div>
        </div>
        <div id="treeContainer">
          <div class="tree-loading" id="treeLoading">
            <span style="display:inline-block;width:20px;height:20px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;animation:spin .7s linear infinite;"></span>
            Loading tree…
          </div>
          <canvas id="treeCanvas" style="display:none;"></canvas>
        </div>
        <!-- Tooltip -->
        <div id="treeTooltip" style="display:none;position:fixed;background:#1a2035;color:#fff;border-radius:10px;padding:12px 16px;font-size:12px;pointer-events:none;z-index:999;box-shadow:0 8px 24px rgba(0,0,0,.3);min-width:160px;"></div>
        <div class="card-footer" style="font-size:12px;color:var(--text-muted);display:flex;gap:16px;flex-wrap:wrap;">
          <span>🟢 Active</span>
          <span>🔴 Suspended</span>
          <span>⚫ Open Slot</span>
          <span style="margin-left:auto;">Tap/click a node to expand</span>
        </div>
      </div>

      <?php else: ?>
      <!-- ══ REFERRAL NETWORK VIEW ═════════════════════════════ -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">👥 Referral Network (10 Levels)</span>
          <span class="badge badge-neutral"><?= count($indirect) ?> members</span>
        </div>
        <div class="card-body" style="padding:0;">
          <?php if (empty($indirect)): ?>
            <div class="empty-state"><div class="empty-icon">👥</div><p>You haven't referred anyone yet.</p></div>
          <?php else:
            $grouped = [];
            foreach ($indirect as $m) $grouped[$m['level']][] = $m;
            foreach ($grouped as $lvl => $members):
          ?>
          <div class="ref-level-group">
            <div class="ref-level-header" onclick="toggleLevel(<?= $lvl ?>)">
              <span>Level <?= $lvl ?></span>
              <span class="badge badge-primary"><?= count($members) ?></span>
              <span id="arrow<?= $lvl ?>" style="margin-left:auto;">▼</span>
            </div>
            <div class="ref-level-members" id="level<?= $lvl ?>" style="padding:0 0 4px;">
              <?php foreach ($members as $m): ?>
              <div class="ref-member-row">
                <div class="ref-avatar"><?= strtoupper(substr($m['username'], 0, 1)) ?></div>
                <div style="flex:1;min-width:0;">
                  <div class="ref-name">@<?= e($m['username']) ?><?= $m['full_name'] ? ' — ' . e($m['full_name']) : '' ?></div>
                  <div class="ref-meta"><?= e($m['package_name'] ?? 'Member') ?> · Joined <?= fmt_date($m['joined_at']) ?></div>
                </div>
                <span class="badge <?= $m['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>">
                  <?= ucfirst($m['status']) ?>
                </span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
      <?php endif; ?>

    </main>
  </div>
</div>

<?php if ($view !== 'referral'): ?>
<script>
// ── Binary Tree Renderer ─────────────────────────────────────
const API_URL   = '<?= APP_URL ?>/?page=api_binary_tree&root=<?= Auth::id() ?>';
const canvas    = document.getElementById('treeCanvas');
const ctx       = canvas.getContext('2d');
const container = document.getElementById('treeContainer');
const tooltip   = document.getElementById('treeTooltip');

let treeData   = null;
let scale      = 1;
let offsetX    = 0;
let offsetY    = 0;
let nodeMap    = [];   // {x, y, r, node} for hit detection

const NODE_R   = 26;
const H_GAP    = 64;
const V_GAP    = 90;
const COLORS   = { active: '#12a05c', suspended: '#e03434', empty: '#9ca3af' };

async function loadTree() {
  try {
    const res  = await fetch(API_URL + '&depth=4');
    treeData   = await res.json();
    document.getElementById('treeLoading').style.display = 'none';
    canvas.style.display = 'block';
    drawTree();
  } catch(e) {
    document.getElementById('treeLoading').innerHTML = '⚠ Failed to load tree.';
  }
}

function calcLayout(node, depth, x, y, spread) {
  if (!node) return { w: spread };
  node._x = x;
  node._y = y;
  const childY = y + V_GAP;
  let lw = spread / 2, rw = spread / 2;

  if (node.left)  calcLayout(node.left,  depth+1, x - spread/4, childY, spread/2);
  if (node.right) calcLayout(node.right, depth+1, x + spread/4, childY, spread/2);
  return node;
}

function drawTree() {
  if (!treeData) return;
  const cw = Math.max(container.clientWidth, 600);
  const ch = Math.max(container.clientHeight - 20, 400);
  canvas.width  = cw;
  canvas.height = ch;
  ctx.clearRect(0, 0, cw, ch);
  nodeMap = [];

  const rootX = cw / 2 + offsetX;
  const rootY = 60 + offsetY;
  const spread = cw * 0.8;

  calcLayout(treeData, 0, rootX, rootY, spread);
  ctx.save();
  ctx.scale(scale, scale);
  drawNode(treeData, null);
  ctx.restore();
}

function drawNode(node, parent) {
  if (!node) return;

  // Draw empty slots
  const slots = [
    { side: 'left',  child: node.left,  x: node._x - V_GAP * 0.7, y: node._y + V_GAP },
    { side: 'right', child: node.right, x: node._x + V_GAP * 0.7, y: node._y + V_GAP },
  ];
  slots.forEach(slot => {
    const cx = (slot.child ? slot.child._x : slot.x);
    const cy = (slot.child ? slot.child._y : slot.y);
    // Line
    ctx.beginPath();
    ctx.moveTo(node._x, node._y + NODE_R);
    ctx.lineTo(cx, cy - NODE_R);
    ctx.strokeStyle = '#dde3ef';
    ctx.lineWidth = 2;
    ctx.stroke();

    if (!slot.child) {
      // Draw empty slot
      ctx.beginPath();
      ctx.arc(cx, cy, NODE_R - 4, 0, Math.PI * 2);
      ctx.fillStyle = '#f4f6fb';
      ctx.fill();
      ctx.strokeStyle = '#dde3ef';
      ctx.lineWidth = 2;
      ctx.setLineDash([4, 3]);
      ctx.stroke();
      ctx.setLineDash([]);
      ctx.fillStyle = '#9ca3af';
      ctx.font = '11px Plus Jakarta Sans';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText(slot.side === 'left' ? '↙' : '↘', cx, cy - 6);
      ctx.font = '9px Plus Jakarta Sans';
      ctx.fillText('empty', cx, cy + 7);
    }
  });

  // Draw current node
  const color = node.status === 'active' ? COLORS.active : COLORS.suspended;

  // Shadow
  ctx.shadowColor = 'rgba(0,0,0,.12)';
  ctx.shadowBlur  = 12;
  ctx.shadowOffsetY = 3;

  ctx.beginPath();
  ctx.arc(node._x, node._y, NODE_R, 0, Math.PI * 2);
  ctx.fillStyle = color;
  ctx.fill();
  ctx.shadowBlur = 0; ctx.shadowOffsetY = 0;

  // White border
  ctx.beginPath();
  ctx.arc(node._x, node._y, NODE_R + 3, 0, Math.PI * 2);
  ctx.strokeStyle = '#fff';
  ctx.lineWidth = 3;
  ctx.stroke();

  // Username text
  ctx.fillStyle = '#fff';
  ctx.font = 'bold 10px Plus Jakarta Sans';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  const label = node.username.length > 8 ? node.username.slice(0,7) + '…' : node.username;
  ctx.fillText(label, node._x, node._y);

  // L/R count below node
  ctx.fillStyle = '#6b7a99';
  ctx.font = '9px Plus Jakarta Sans';
  ctx.fillText('L:' + node.left_count + ' R:' + node.right_count, node._x, node._y + NODE_R + 14);

  // Register for hit detection
  nodeMap.push({ x: node._x, y: node._y, r: NODE_R + 4, node });

  if (node.left)  drawNode(node.left,  node);
  if (node.right) drawNode(node.right, node);
}

// Hit detection → tooltip
canvas.addEventListener('mousemove', function(e) {
  const rect = canvas.getBoundingClientRect();
  const mx = (e.clientX - rect.left) / scale;
  const my = (e.clientY - rect.top)  / scale;

  let hit = null;
  for (const n of nodeMap) {
    if (Math.hypot(mx - n.x, my - n.y) <= n.r) { hit = n; break; }
  }

  if (hit) {
    canvas.style.cursor = 'pointer';
    tooltip.style.display = 'block';
    tooltip.style.left = (e.clientX + 14) + 'px';
    tooltip.style.top  = (e.clientY - 10) + 'px';
    tooltip.innerHTML = `
      <div style="font-weight:700;margin-bottom:4px;">@${hit.node.username}</div>
      <div style="color:rgba(255,255,255,.65);font-size:11px;">
        ${hit.node.package || '—'} · ${hit.node.joined || '—'}<br>
        Left: ${hit.node.left_count} · Right: ${hit.node.right_count}<br>
        Status: <span style="color:${hit.node.status==='active'?'#4ade80':'#f87171'}">${hit.node.status}</span>
      </div>`;
  } else {
    canvas.style.cursor = 'default';
    tooltip.style.display = 'none';
  }
});
canvas.addEventListener('mouseleave', () => { tooltip.style.display = 'none'; });

function zoomTree(delta) {
  scale = Math.max(0.4, Math.min(2, scale + delta));
  drawTree();
}
function resetTree() { scale = 1; offsetX = 0; offsetY = 0; loadTree(); }

window.addEventListener('resize', drawTree);
loadTree();
</script>

<script>
// Collapsible referral levels
function toggleLevel(lvl) {
  const el    = document.getElementById('level' + lvl);
  const arrow = document.getElementById('arrow' + lvl);
  if (el.style.display === 'none') { el.style.display = 'flex'; el.style.flexDirection='column'; arrow.textContent='▼'; }
  else { el.style.display = 'none'; arrow.textContent = '▶'; }
}
</script>
<?php endif; ?>

</body>
</html>
