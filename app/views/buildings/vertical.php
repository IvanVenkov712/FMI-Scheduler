<?php
require_once __DIR__ . '/../shared/ui.php';
$b   = is_object($building ?? null) ? $building->to_array() : ($building ?? []);
$dir = strtoupper($_GET['dir'] ?? 'ALL');
if (!in_array($dir, ['N', 'S', 'E', 'W', 'ALL'], true)) $dir = 'ALL';

// Must match the floor plan grid dimensions
$V_COLS = 20;
$V_ROWS = 12;

// ── Build directional projection ─────────────────────────────
// projection[level][h] = ['room' => [...], 'depth' => int]
// depth: lower value wins (closest to viewer)
$projection    = [];
$unplaced      = [];
$placed_any    = false;

if ($dir !== 'ALL') {
    foreach ($floors ?? [] as $floor) {
        $fl    = is_object($floor) ? $floor->to_array() : $floor;
        $level = (int) $fl['level'];
        foreach ($rooms_by_floor[$fl['id']] ?? [] as $room) {
            $r = is_object($room) ? $room->to_array() : $room;
            if (!preg_match('/^(\d+),(\d+)$/', $r['position_code'] ?? '', $m)) {
                $unplaced[] = $r;
                continue;
            }
            $placed_any = true;
            $col = (int) $m[1];
            $row = (int) $m[2];
            // h = horizontal position in this view
            // depth_val: smallest value = closest to viewer (wins occlusion)
            [$h, $depth_val] = match ($dir) {
                'N' => [$col,  $row],   // N face: look S, col=W→E, min row wins
                'S' => [$col, -$row],   // S face: look N, col=W→E, max row wins
                'E' => [$row, -$col],   // E face: look W, row=N→S, max col wins
                'W' => [$row,  $col],   // W face: look E, row=N→S, min col wins
            };
            if (!isset($projection[$level][$h]) || $depth_val < $projection[$level][$h]['depth']) {
                $projection[$level][$h] = ['room' => $r, 'depth' => $depth_val];
            }
        }
    }

    // Compute visible h range
    $all_h = [];
    foreach ($projection as $row_data) {
        foreach (array_keys($row_data) as $h) $all_h[] = $h;
    }
    $h_min = $all_h ? min($all_h) : 0;
    $h_max = $all_h ? max($all_h) : 0;
}

// Direction labels
$dir_labels = [
    'ALL' => ['label' => 'All', 'axis' => ''],
    'N'   => ['label' => 'North', 'axis' => '← West · col axis · East →'],
    'S'   => ['label' => 'South', 'axis' => '← West · col axis · East →'],
    'E'   => ['label' => 'East',  'axis' => '← North · row axis · South →'],
    'W'   => ['label' => 'West',  'axis' => '← North · row axis · South →'],
];

$sorted_floors = array_reverse($floors ?? []);

page_start(($b['name'] ?? 'Building') . ' — Vertical View', 'buildings');
?>

<div class="actions page-actions">
    <a href="/buildings/<?= e($b['id']) ?>" class="btn btn-muted">Details</a>
    <a href="/buildings/<?= e($b['id']) ?>/edit" class="btn btn-muted">Edit</a>
    <a href="/buildings" class="btn btn-muted">Back</a>
</div>

<div class="card">
    <h2><?= e($b['name'] ?? '') ?> <span class="badge neutral"><?= e($b['code'] ?? '') ?></span></h2>
    <?php if (!empty($b['address'])): ?>
        <p class="vert-address"><?= e($b['address']) ?><?= !empty($b['campus_area']) ? ' · ' . e($b['campus_area']) : '' ?></p>
    <?php endif; ?>
</div>

<?php if (empty($floors)): ?>
    <div class="empty">No floors defined for this building.</div>
<?php else: ?>

<!-- Direction selector -->
<div class="vert-dir-tabs">
    <?php foreach ($dir_labels as $key => $info): ?>
    <a href="?dir=<?= $key ?>" class="vert-dir-tab<?= $dir === $key ? ' active' : '' ?>"><?= $info['label'] ?></a>
    <?php endforeach; ?>
</div>

<?php if ($dir === 'ALL'): ?>
<!-- ── ALL VIEW (original) ─────────────────────────────── -->
<div class="vert-building">
    <?php foreach ($sorted_floors as $floor):
        $fl       = is_object($floor) ? $floor->to_array() : $floor;
        $floor_id = $fl['id'];
        $level    = (int) ($fl['level'] ?? 0);
        $rooms    = $rooms_by_floor[$floor_id] ?? [];
        $label    = $fl['name'] !== '' ? $fl['name'] : 'Floor ' . $level;
    ?>
    <div class="vert-floor">
        <div class="vert-floor-label">
            <span class="vert-level"><?= $level >= 0 ? '+' . $level : $level ?></span>
            <a href="/floors/<?= e($floor_id) ?>/plan" class="vert-floor-name"><?= e($label) ?></a>
            <span class="vert-room-count"><?= count($rooms) ?> room<?= count($rooms) !== 1 ? 's' : '' ?></span>
        </div>
        <div class="vert-rooms">
            <?php if (empty($rooms)): ?>
                <span class="vert-no-rooms">—</span>
            <?php else: ?>
                <?php foreach ($rooms as $room):
                    $r          = is_object($room) ? $room->to_array() : $room;
                    $type_cls   = match ($r['room_type'] ?? 'physical') {
                        'online' => 'room-online', 'hybrid' => 'room-hybrid', default => 'room-physical',
                    };
                    $active_cls = ($r['is_active'] ?? 1) ? '' : ' room-inactive';
                ?>
                <a href="/rooms/<?= e($r['id']) ?>" class="vert-room <?= $type_cls . $active_cls ?>"
                   title="<?= e($r['name']) ?> · cap <?= e($r['capacity']) ?>">
                    <span class="vert-room-name"><?= e($r['name']) ?></span>
                    <?php if ($r['capacity'] > 0): ?>
                        <span class="vert-room-cap"><?= e($r['capacity']) ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="vert-ground"></div>
</div>

<?php else: ?>
<!-- ── DIRECTIONAL VIEW ────────────────────────────────── -->

<?php if (!$placed_any): ?>
    <div class="empty">No rooms have been placed on a floor plan yet. Use the <a href="/floors">Floor Plan editor</a> to position rooms first.</div>
<?php else: ?>

<div class="vert-building vert-building-grid">
    <?php foreach ($sorted_floors as $floor):
        $fl    = is_object($floor) ? $floor->to_array() : $floor;
        $level = (int) ($fl['level'] ?? 0);
        $label = $fl['name'] !== '' ? $fl['name'] : 'Floor ' . $level;

        // Count visible rooms on this floor in this view
        $visible_count = count($projection[$level] ?? []);
    ?>
    <div class="vert-floor">
        <div class="vert-floor-label">
            <span class="vert-level"><?= $level >= 0 ? '+' . $level : $level ?></span>
            <a href="/floors/<?= e($fl['id']) ?>/plan" class="vert-floor-name"><?= e($label) ?></a>
            <span class="vert-room-count"><?= $visible_count ?> visible</span>
        </div>
        <div class="vert-cells">
            <?php for ($h = $h_min; $h <= $h_max; $h++):
                $entry = $projection[$level][$h] ?? null;
                $r     = $entry ? $entry['room'] : null;
            ?>
            <div class="vert-cell">
                <?php if ($r):
                    $type_cls   = match ($r['room_type'] ?? 'physical') {
                        'online' => 'room-online', 'hybrid' => 'room-hybrid', default => 'room-physical',
                    };
                    $active_cls = ($r['is_active'] ?? 1) ? '' : ' room-inactive';
                ?>
                <a href="/rooms/<?= e($r['id']) ?>" class="vert-cell-room <?= $type_cls . $active_cls ?>"
                   title="<?= e($r['name']) ?> · cap <?= e($r['capacity']) ?> · depth <?= e($entry['depth']) ?>">
                    <span class="vert-room-name"><?= e($r['name']) ?></span>
                    <?php if ($r['capacity'] > 0): ?>
                        <span class="vert-room-cap"><?= e($r['capacity']) ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- H-axis labels (mirrors the floor row structure) -->
    <div class="vert-floor vert-axis-row">
        <div class="vert-floor-label" aria-hidden="true"></div>
        <div class="vert-cells">
            <?php for ($h = $h_min; $h <= $h_max; $h++): ?>
            <div class="vert-cell vert-h-label"><?= $h ?></div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="vert-ground"></div>
</div>

<?php if ($dir_labels[$dir]['axis']): ?>
    <p class="vert-axis-title"><?= $dir_labels[$dir]['axis'] ?></p>
<?php endif; ?>

<?php if (!empty($unplaced)): ?>
<div class="card" style="margin-top:12px">
    <h2>Not visible <span style="color:var(--muted);font-size:13px;font-weight:400">— rooms without a floor plan position</span></h2>
    <div class="vert-rooms" style="padding:8px 0">
        <?php foreach ($unplaced as $r):
            $type_cls = match ($r['room_type'] ?? 'physical') {
                'online' => 'room-online', 'hybrid' => 'room-hybrid', default => 'room-physical',
            };
        ?>
        <a href="/rooms/<?= e($r['id']) ?>" class="vert-room <?= $type_cls ?><?= ($r['is_active'] ?? 1) ? '' : ' room-inactive' ?>">
            <span class="vert-room-name"><?= e($r['name']) ?></span>
            <?php if ($r['capacity'] > 0): ?><span class="vert-room-cap"><?= e($r['capacity']) ?></span><?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php endif; // placed_any ?>
<?php endif; // ALL vs directional ?>

<div class="vert-legend">
    <span class="vert-room room-physical">Physical</span>
    <span class="vert-room room-online">Online</span>
    <span class="vert-room room-hybrid">Hybrid</span>
    <span class="vert-room room-physical room-inactive">Inactive</span>
</div>

<?php endif; // floors empty ?>

<?php page_end(); ?>
