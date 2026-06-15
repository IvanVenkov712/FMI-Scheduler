<?php
require_once __DIR__ . '/../shared/ui.php';
$f = is_object($floor ?? null) ? $floor->to_array() : ($floor ?? []);
$b = is_object($building ?? null) ? $building->to_array() : ($building ?? []);
$can_edit = in_array($current_user_role ?? '', ['admin', 'schedule_manager']);

$GRID_COLS = 20;
$GRID_ROWS = 12;

// Separate placed vs. unplaced rooms
$placed   = [];
$unplaced = [];
foreach ($rooms ?? [] as $room) {
    $r = is_object($room) ? $room->to_array() : $room;
    $code = trim($r['position_code'] ?? '');
    if (preg_match('/^(\d+),(\d+)$/', $code, $m)) {
        $col = (int)$m[1];
        $row = (int)$m[2];
        if ($col >= 0 && $col < $GRID_COLS && $row >= 0 && $row < $GRID_ROWS) {
            $placed["$col,$row"] = $r;
            continue;
        }
    }
    $unplaced[] = $r;
}

page_start(($b['name'] ?? 'Floor') . ' — Floor Plan', 'floors');
?>

<div class="actions page-actions">
    <?php if ($can_edit): ?>
        <a href="/floors/<?= e($f['id']) ?>/edit" class="btn btn-muted">Edit Floor</a>
    <?php endif; ?>
    <a href="/buildings/<?= e($b['id'] ?? '') ?>/vertical" class="btn btn-muted">Building View</a>
    <a href="/floors" class="btn btn-muted">Back</a>
</div>

<div class="card" style="margin-bottom:12px">
    <h2><?= e($f['name'] ?? 'Floor') ?> <span class="badge neutral">Level <?= e($f['level'] ?? 0) ?></span>
        <?php if (!empty($b['name'])): ?><span style="color:var(--muted);font-size:14px;font-weight:400;margin-left:8px"><?= e($b['name']) ?></span><?php endif; ?>
    </h2>
    <?php if (!$can_edit): ?><p style="color:var(--muted);font-size:13px;margin-top:4px">View only — you do not have permission to move rooms.</p><?php endif; ?>
</div>

<?php if (empty($rooms)): ?>
    <div class="empty">No rooms on this floor yet. <a href="/rooms/create">Add a room</a>.</div>
<?php else: ?>

<div class="plan-wrap">
    <!-- Grid -->
    <div class="plan-grid"
         id="planGrid"
         data-cols="<?= $GRID_COLS ?>"
         data-rows="<?= $GRID_ROWS ?>"
         data-editable="<?= $can_edit ? '1' : '0' ?>">
        <?php for ($row = 0; $row < $GRID_ROWS; $row++):
              for ($col = 0; $col < $GRID_COLS; $col++):
                  $key = "$col,$row";
                  $r   = $placed[$key] ?? null;
        ?>
        <div class="plan-cell<?= $r ? ' occupied' : '' ?>"
             data-col="<?= $col ?>"
             data-row="<?= $row ?>">
            <?php if ($r): ?>
            <div class="plan-room room-<?= e($r['room_type'] ?? 'physical') ?><?= ($r['is_active'] ?? 1) ? '' : ' room-inactive' ?>"
                 id="room-<?= e($r['id']) ?>"
                 data-room-id="<?= e($r['id']) ?>"
                 draggable="<?= $can_edit ? 'true' : 'false' ?>"
                 title="<?= e($r['name']) ?> · cap <?= e($r['capacity']) ?> · <?= e($r['room_type'] ?? '') ?>">
                <span class="plan-room-name"><?= e($r['name']) ?></span>
                <?php if ($r['capacity'] > 0): ?><span class="plan-room-cap"><?= e($r['capacity']) ?></span><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endfor; endfor; ?>
    </div>

    <!-- Axis labels -->
    <div class="plan-col-labels">
        <?php for ($col = 0; $col < $GRID_COLS; $col++): ?>
        <span><?= $col ?></span>
        <?php endfor; ?>
    </div>
</div>

<?php if (!empty($unplaced)): ?>
<div class="card" style="margin-top:16px">
    <h2>Unplaced Rooms <span style="color:var(--muted);font-size:13px;font-weight:400"><?= $can_edit ? '— drag onto the grid to place' : '' ?></span></h2>
    <div class="plan-palette" id="planPalette">
        <?php foreach ($unplaced as $r): ?>
        <div class="plan-room room-<?= e($r['room_type'] ?? 'physical') ?><?= ($r['is_active'] ?? 1) ? '' : ' room-inactive' ?>"
             id="room-<?= e($r['id']) ?>"
             data-room-id="<?= e($r['id']) ?>"
             draggable="<?= $can_edit ? 'true' : 'false' ?>"
             title="<?= e($r['name']) ?> · cap <?= e($r['capacity']) ?> · <?= e($r['room_type'] ?? '') ?>">
            <span class="plan-room-name"><?= e($r['name']) ?></span>
            <?php if ($r['capacity'] > 0): ?><span class="plan-room-cap"><?= e($r['capacity']) ?></span><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="vert-legend" style="margin-top:12px">
    <span class="plan-room room-physical" style="pointer-events:none">Physical</span>
    <span class="plan-room room-online"   style="pointer-events:none">Online</span>
    <span class="plan-room room-hybrid"   style="pointer-events:none">Hybrid</span>
    <span class="plan-room room-physical room-inactive" style="pointer-events:none">Inactive</span>
</div>

<?php endif; ?>

<script>
(function () {
    var canEdit = document.getElementById('planGrid')?.dataset.editable === '1';
    if (!canEdit) return;

    var dragging = null;
    var fromPalette = false;

    function getRoomEl(e) {
        return e.target.closest('.plan-room');
    }

    function getCellEl(e) {
        return e.target.closest('.plan-cell');
    }

    // ── Drag start ──────────────────────────────────────────────
    document.querySelectorAll('.plan-room[draggable="true"]').forEach(function (el) {
        el.addEventListener('dragstart', function (e) {
            dragging = el;
            fromPalette = !!el.closest('#planPalette');
            e.dataTransfer.effectAllowed = 'move';
            setTimeout(function () { el.classList.add('dragging'); }, 0);
        });
        el.addEventListener('dragend', function () {
            el.classList.remove('dragging');
            document.querySelectorAll('.plan-cell').forEach(function (c) { c.classList.remove('drag-over'); });
            dragging = null;
        });
    });

    // ── Grid cell drop targets ───────────────────────────────────
    var grid = document.getElementById('planGrid');
    grid.addEventListener('dragover', function (e) {
        var cell = getCellEl(e);
        if (!cell) return;
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        document.querySelectorAll('.plan-cell').forEach(function (c) { c.classList.remove('drag-over'); });
        if (!cell.classList.contains('occupied') || cell.querySelector('.plan-room') === dragging) {
            cell.classList.add('drag-over');
        }
    });

    grid.addEventListener('dragleave', function (e) {
        if (!grid.contains(e.relatedTarget)) {
            document.querySelectorAll('.plan-cell').forEach(function (c) { c.classList.remove('drag-over'); });
        }
    });

    grid.addEventListener('drop', function (e) {
        var cell = getCellEl(e);
        if (!cell || !dragging) return;
        e.preventDefault();
        document.querySelectorAll('.plan-cell').forEach(function (c) { c.classList.remove('drag-over'); });

        var col = parseInt(cell.dataset.col, 10);
        var row = parseInt(cell.dataset.row, 10);

        // If cell is occupied by another room, swap back to palette
        var existing = cell.querySelector('.plan-room');
        if (existing && existing !== dragging) return;

        // Move dragged room's old cell to unoccupied
        var oldCell = dragging.closest('.plan-cell');
        if (oldCell) {
            oldCell.classList.remove('occupied');
        }

        cell.appendChild(dragging);
        cell.classList.add('occupied');

        savePosition(dragging.dataset.roomId, col, row);
    });

    // ── Palette drop target (to unplace a room) ──────────────────
    var palette = document.getElementById('planPalette');
    if (palette) {
        palette.addEventListener('dragover', function (e) {
            if (dragging && dragging.closest('#planGrid')) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                palette.classList.add('drag-over');
            }
        });
        palette.addEventListener('dragleave', function () {
            palette.classList.remove('drag-over');
        });
        palette.addEventListener('drop', function (e) {
            if (!dragging || !dragging.closest('#planGrid')) return;
            e.preventDefault();
            palette.classList.remove('drag-over');

            var oldCell = dragging.closest('.plan-cell');
            if (oldCell) oldCell.classList.remove('occupied');

            palette.appendChild(dragging);
            savePosition(dragging.dataset.roomId, null, null);
        });
    }

    // ── Persist to server ────────────────────────────────────────
    function savePosition(roomId, col, row) {
        var body = col === null ? {} : { col: col, row: row };
        api.post('/rooms/' + roomId + '/position', body).catch(function () {
            // silent — page reload will show actual state
        });
    }
})();
</script>

<?php page_end(); ?>
