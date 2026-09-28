<?php
require_once __DIR__ . '/layout.php';

$entities = json_decode('[{"id":"services","name":"Services \/ Products","singular":"Service","icon":"\ud83d\udcbc","fields":[{"name":"title","label":"Title","type":"text","required":true},{"name":"category","label":"Category","type":"text","required":false},{"name":"price","label":"Price ($)","type":"text","required":false},{"name":"description","label":"Description","type":"textarea","required":false}]},{"id":"inquiries","name":"Customer Inquiries","singular":"Inquiry","icon":"\u2709\ufe0f","fields":[{"name":"name","label":"Customer Name","type":"text","required":true},{"name":"email","label":"Email","type":"email","required":true},{"name":"phone","label":"Phone","type":"text","required":false},{"name":"message","label":"Message \/ Notes","type":"textarea","required":false},{"name":"date","label":"Date Received","type":"date","required":false}]}]', true) ?: [];
$activeEntityId = $_GET['entity'] ?? ($entities[0]['id'] ?? 'services');

$activeEntity = null;
foreach ($entities as $ent) {
    if ($ent['id'] === $activeEntityId) {
        $activeEntity = $ent;
        break;
    }
}
if (!$activeEntity && !empty($entities)) {
    $activeEntity = $entities[0];
    $activeEntityId = $activeEntity['id'];
}

$dataFile = __DIR__ . '/data_' . $activeEntityId . '.json';
$records = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
if (!is_array($records)) $records = [];

$msg = '';
// Handle Add / Edit POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_item') {
        $id = trim($_POST['item_id'] ?? '');
        $isEdit = !empty($id);

        $item = ['id' => $isEdit ? $id : (string)time()];
        foreach ($activeEntity['fields'] ?? [] as $f) {
            $fname = $f['name'];
            $item[$fname] = trim($_POST[$fname] ?? '');
        }

        if ($isEdit) {
            foreach ($records as &$r) {
                if (($r['id'] ?? '') === $id) {
                    $r = $item;
                    break;
                }
            }
        } else {
            array_unshift($records, $item);
        }
        file_put_contents($dataFile, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $msg = $isEdit ? 'Record updated successfully!' : 'New record added successfully!';
    }

    if ($action === 'delete_item') {
        $delId = trim($_POST['item_id'] ?? '');
        $records = array_values(array_filter($records, fn($r) => ($r['id'] ?? '') !== $delId));
        file_put_contents($dataFile, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $msg = 'Record deleted.';
    }
}

renderAdminHeader('manage', 'Manage Data');
?>
<div class="top-actions">
  <div>
    <h1 class="page-title">📋 Data &amp; Record Manager</h1>
    <p style="color:#64748b;font-size:.88rem;margin-top:.3rem">Add, edit, or remove live data items for your website.</p>
  </div>
  <button class="btn btn-primary" onclick="openAddModal()">
    <span>➕</span> Add New <?= htmlspecialchars($activeEntity['singular'] ?? 'Item') ?>
  </button>
</div>

<?php if ($msg): ?>
  <div style="background:rgba(16,185,129,.15);border:1px solid #10b981;color:#34d399;padding:.85rem 1.25rem;border-radius:12px;font-weight:700;margin-bottom:1.5rem">
    ✅ <?= htmlspecialchars($msg) ?>
  </div>
<?php endif; ?>

<!-- Tabs for entities -->
<div style="display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap">
  <?php foreach ($entities as $ent): ?>
    <a href="?entity=<?= urlencode($ent['id']) ?>" 
       class="btn <?= $ent['id'] === $activeEntityId ? 'btn-primary' : 'btn-ghost' ?>">
       <span><?= htmlspecialchars($ent['icon'] ?? '📁') ?></span>
       <span><?= htmlspecialchars($ent['name'] ?? $ent['id']) ?></span>
    </a>
  <?php endforeach; ?>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
    <h2 style="font-size:1.1rem;font-weight:800;color:#fff"><?= htmlspecialchars($activeEntity['name'] ?? 'Records') ?> (<?= count($records) ?>)</h2>
    <input type="text" id="filterInp" placeholder="Search records..." class="inp" style="max-width:260px;padding:.5rem .85rem;font-size:.82rem" oninput="filterTable()">
  </div>

  <?php if (empty($records)): ?>
    <div style="text-align:center;padding:3rem 1rem;color:#64748b">
      <div style="font-size:2rem;margin-bottom:.5rem">📂</div>
      <p>No records found yet. Click <strong>+ Add New</strong> above to create your first entry.</p>
    </div>
  <?php else: ?>
    <div style="overflow-x:auto">
      <table id="recordsTable">
        <thead>
          <tr>
            <?php foreach ($activeEntity['fields'] ?? [] as $f): ?>
              <th><?= htmlspecialchars($f['label'] ?? $f['name']) ?></th>
            <?php endforeach; ?>
            <th style="text-align:right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $row): ?>
            <tr>
              <?php foreach ($activeEntity['fields'] ?? [] as $f): 
                $val = $row[$f['name']] ?? '—';
              ?>
                <td><?= htmlspecialchars($val) ?></td>
              <?php endforeach; ?>
              <td style="text-align:right;white-space:nowrap">
                <button type="button" class="btn btn-ghost" style="padding:.35rem .75rem;font-size:.78rem;margin-right:.4rem"
                        onclick='openEditModal(<?= json_encode($row) ?>)'>✏️ Edit</button>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this record?');">
                  <input type="hidden" name="action" value="delete_item">
                  <input type="hidden" name="item_id" value="<?= htmlspecialchars($row['id'] ?? '') ?>">
                  <button type="submit" class="btn btn-danger" style="padding:.35rem .75rem;font-size:.78rem">🗑️</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Add / Edit Modal -->
<div id="itemModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(5px);z-index:9999;align-items:center;justify-content:center;padding:1.5rem">
  <div style="background:#111622;border:1.5px solid #283347;border-radius:20px;max-width:540px;width:100%;padding:2rem;box-shadow:0 25px 60px rgba(0,0,0,.6)">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
      <h3 style="font-size:1.25rem;font-weight:800;color:#fff" id="modalTitle">Add Record</h3>
      <button type="button" onclick="closeModal()" style="background:none;border:none;color:#94a3b8;font-size:1.3rem;cursor:pointer">&times;</button>
    </div>

    <form method="POST" id="itemForm">
      <input type="hidden" name="action" value="save_item">
      <input type="hidden" name="item_id" id="modal_item_id" value="">

      <?php foreach ($activeEntity['fields'] ?? [] as $f): 
        $fname = $f['name'];
        $ftype = $f['type'] ?? 'text';
      ?>
        <div class="field">
          <label class="lbl"><?= htmlspecialchars($f['label'] ?? $fname) ?></label>
          <?php if ($ftype === 'textarea'): ?>
            <textarea name="<?= $fname ?>" id="inp_<?= $fname ?>" rows="3" class="inp"></textarea>
          <?php else: ?>
            <input type="<?= $ftype === 'email' ? 'email' : ($ftype === 'date' ? 'date' : 'text') ?>" 
                   name="<?= $fname ?>" id="inp_<?= $fname ?>" class="inp">
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <!-- Section 18.5 Requirement Builder hook (only inside edit context) -->
      <div id="req-builder-hook" style="display:none;background:#1e1b4b;border:1px solid #4338ca;padding:.75rem;border-radius:10px;margin-bottom:1.25rem;font-size:.8rem;color:#c7d2fe">
        ✨ <strong>Process Builder:</strong> Edit context verified. Requirement builder features enabled.
      </div>

      <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:1.5rem">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Save Record &rarr;</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddModal() {
  document.getElementById('modalTitle').textContent = '➕ Add New ' + <?= json_encode($activeEntity['singular'] ?? 'Item') ?>;
  document.getElementById('modal_item_id').value = '';
  document.getElementById('itemForm').reset();
  document.getElementById('req-builder-hook').style.display = 'none';
  document.getElementById('itemModal').style.display = 'flex';
}

function openEditModal(row) {
  document.getElementById('modalTitle').textContent = '✏️ Edit ' + <?= json_encode($activeEntity['singular'] ?? 'Item') ?>;
  document.getElementById('modal_item_id').value = row.id || '';
  
  <?php foreach ($activeEntity['fields'] ?? [] as $f): ?>
    if (document.getElementById('inp_' + <?= json_encode($f['name']) ?>)) {
      document.getElementById('inp_' + <?= json_encode($f['name']) ?>).value = row[<?= json_encode($f['name']) ?>] || '';
    }
  <?php endforeach; ?>

  // Section 18.5: render builder hook ONLY in edit mode
  document.getElementById('req-builder-hook').style.display = 'block';
  document.getElementById('itemModal').style.display = 'flex';
}

function closeModal() {
  document.getElementById('itemModal').style.display = 'none';
}

function filterTable() {
  const query = document.getElementById('filterInp').value.toLowerCase();
  const rows = document.querySelectorAll('#recordsTable tbody tr');
  rows.forEach(r => {
    r.style.display = r.textContent.toLowerCase().includes(query) ? '' : 'none';
  });
}
</script>

<?php renderAdminFooter(); ?>