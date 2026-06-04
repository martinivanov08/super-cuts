<?php
session_start();
require_once 'config.php';

define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'blade2024');

// ── Handle login / logout ──────────────────────────────
if (isset($_POST['login'])) {
    if ($_POST['username'] === ADMIN_USER && $_POST['password'] === ADMIN_PASS) {
        $_SESSION['admin'] = true;
    } else {
        $loginError = 'Невалидно потребителско име или парола.';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ── Handle status update (AJAX) ────────────────────────
if (isset($_POST['action']) && isset($_SESSION['admin'])) {
    header('Content-Type: application/json');
    $id     = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'];

    $statusMap = ['confirm' => 'confirmed', 'cancel' => 'cancelled'];
    if ($id && isset($statusMap[$action])) {
        try {
            $pdo = getConnection();
            $pdo->prepare("UPDATE reservations SET status = :s WHERE id = :id")
                ->execute([':s' => $statusMap[$action], ':id' => $id]);
            echo json_encode(['ok' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Грешка в базата данни.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Невалидна заявка.']);
    }
    exit;
}

// ── Require login ──────────────────────────────────────
if (!isset($_SESSION['admin'])) {
    showLogin($loginError ?? null);
    exit;
}

// ── Fetch reservations ─────────────────────────────────
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$where  = [];
$params = [];

if (in_array($filter, ['pending', 'confirmed', 'cancelled'])) {
    $where[]           = "status = :status";
    $params[':status'] = $filter;
}

if ($search !== '') {
    $where[]    = "(LOWER(name) LIKE :s OR LOWER(email) LIKE :s OR phone LIKE :s)";
    $params[':s'] = '%' . strtolower($search) . '%';
}

$sql = "SELECT * FROM reservations" .
       ($where ? " WHERE " . implode(' AND ', $where) : '') .
       " ORDER BY date DESC, time DESC";

try {
    $pdo  = getConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $stats = $pdo->query(
        "SELECT
            SUM(status = 'pending')   AS pending,
            SUM(status = 'confirmed') AS confirmed,
            SUM(status = 'cancelled') AS cancelled,
            COUNT(*) AS total
         FROM reservations"
    )->fetch();
} catch (PDOException $e) {
    die('<p style="color:red;padding:40px">Грешка в базата данни: ' . htmlspecialchars($e->getMessage()) . '</p>');
}

$statusLabels = [
    'pending'   => 'Изчакваща',
    'confirmed' => 'Потвърдена',
    'cancelled' => 'Отказана',
];

function fmtDate($d) {
    if (!$d) return '—';
    $months = ['Jan'=>'яну','Feb'=>'фев','Mar'=>'мар','Apr'=>'апр','May'=>'май','Jun'=>'юни',
               'Jul'=>'юли','Aug'=>'авг','Sep'=>'сеп','Oct'=>'окт','Nov'=>'ное','Dec'=>'дек'];
    $str = date('M j, Y', strtotime($d));
    return str_replace(array_keys($months), array_values($months), $str);
}
function fmtTime($t) {
    return $t ? date('H:i', strtotime($t)) : '—';
}

?><!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Администрация — SuperCuts</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="admin-body">

<nav>
    <div class="nav-logo">✂ SuperCuts</div>
    <div style="display:flex;align-items:center;gap:16px;">
        <span style="color:var(--muted);font-size:.85rem;">Административен Панел</span>
        <a href="index.php" style="color:var(--muted);font-size:.85rem;">← Обратно към сайта</a>
        <a href="?logout" class="btn" style="padding:7px 18px;font-size:.82rem;">Изход</a>
    </div>
</nav>

<div class="admin-header">
    <h1>Табло с <span>Резервации</span></h1>
    <span style="color:var(--muted);font-size:.85rem;"><?= date('d.m.Y') ?></span>
</div>

<div class="admin-content">

    <!-- Статистики -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-num"><?= $stats['total'] ?></div>
            <div class="stat-label">Общо</div>
        </div>
        <div class="stat-card">
            <div class="stat-num" style="color:var(--gold)"><?= $stats['pending'] ?></div>
            <div class="stat-label">Изчакващи</div>
        </div>
        <div class="stat-card">
            <div class="stat-num" style="color:var(--green)"><?= $stats['confirmed'] ?></div>
            <div class="stat-label">Потвърдени</div>
        </div>
        <div class="stat-card">
            <div class="stat-num" style="color:var(--red)"><?= $stats['cancelled'] ?></div>
            <div class="stat-label">Отказани</div>
        </div>
    </div>

    <!-- Филтри -->
    <form method="GET" class="filter-bar">
        <input type="text" name="search" placeholder="Търсене по име, имейл или телефон…"
               value="<?= htmlspecialchars($search) ?>">
        <select name="filter">
            <option value="all"       <?= $filter==='all'       ?'selected':'' ?>>Всички статуси</option>
            <option value="pending"   <?= $filter==='pending'   ?'selected':'' ?>>Изчакващи</option>
            <option value="confirmed" <?= $filter==='confirmed' ?'selected':'' ?>>Потвърдени</option>
            <option value="cancelled" <?= $filter==='cancelled' ?'selected':'' ?>>Отказани</option>
        </select>
        <button type="submit" class="btn" style="padding:9px 20px;font-size:.85rem;">Филтър</button>
        <?php if ($search || $filter !== 'all'): ?>
            <a href="admin.php" class="btn btn-outline" style="padding:9px 20px;font-size:.85rem;">Изчисти</a>
        <?php endif; ?>
    </form>

    <!-- Таблица -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Име</th>
                    <th>Телефон</th>
                    <th>Имейл</th>
                    <th>Услуга</th>
                    <th>Бръснар</th>
                    <th>Дата</th>
                    <th>Час</th>
                    <th>Статус</th>
                    <th>Резервирано</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr class="empty-row"><td colspan="11">Няма намерени резервации.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                <tr data-id="<?= $r['id'] ?>">
                    <td style="color:var(--muted)"><?= $r['id'] ?></td>
                    <td><strong><?= htmlspecialchars($r['name']) ?></strong>
                        <?php if ($r['notes']): ?>
                            <br><small style="color:var(--muted)"><?= htmlspecialchars($r['notes']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($r['phone']) ?></td>
                    <td><?= $r['email'] ? htmlspecialchars($r['email']) : '<span style="color:var(--muted)">—</span>' ?></td>
                    <td><?= htmlspecialchars($r['service']) ?></td>
                    <td><?= htmlspecialchars($r['barber']) ?></td>
                    <td><?= fmtDate($r['date']) ?></td>
                    <td><?= fmtTime($r['time']) ?></td>
                    <td>
                        <span class="badge badge-<?= $r['status'] ?>" id="badge-<?= $r['id'] ?>">
                            <?= $statusLabels[$r['status']] ?? ucfirst($r['status']) ?>
                        </span>
                    </td>
                    <td style="color:var(--muted);font-size:.8rem">
                        <?= date('d.m', strtotime($r['created_at'])) ?>
                    </td>
                    <td>
                        <?php if ($r['status'] === 'pending'): ?>
                            <button class="action-btn btn-confirm" onclick="updateStatus(<?= $r['id'] ?>, 'confirm')">Потвърди</button>
                            <button class="action-btn btn-cancel"  onclick="updateStatus(<?= $r['id'] ?>, 'cancel')" style="margin-top:4px">Откажи</button>
                        <?php elseif ($r['status'] === 'confirmed'): ?>
                            <button class="action-btn btn-cancel"  onclick="updateStatus(<?= $r['id'] ?>, 'cancel')">Откажи</button>
                        <?php else: ?>
                            <span style="color:var(--muted);font-size:.8rem">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
const statusLabels = { confirmed: 'Потвърдена', cancelled: 'Отказана' };

async function updateStatus(id, action) {
    const fd = new FormData();
    fd.append('action', action);
    fd.append('id', id);

    const res  = await fetch('admin.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.ok) {
        const newStatus = action === 'confirm' ? 'confirmed' : 'cancelled';
        const badge = document.getElementById('badge-' + id);
        badge.className  = 'badge badge-' + newStatus;
        badge.textContent = statusLabels[newStatus];

        const cell = document.querySelector('tr[data-id="' + id + '"] td:last-child');
        if (action === 'confirm') {
            cell.innerHTML = '<button class="action-btn btn-cancel" onclick="updateStatus(' + id + ', \'cancel\')">Откажи</button>';
        } else {
            cell.innerHTML = '<span style="color:var(--muted);font-size:.8rem">—</span>';
        }
    } else {
        alert('Грешка: ' + (data.error || 'Неуспешна промяна на статуса.'));
    }
}
</script>

</body>
</html>
<?php

function showLogin($error = null) {
    ?><!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — SuperCuts</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div style="font-size:2rem;margin-bottom:12px">✂</div>
        <h2>Административен Вход</h2>
        <p>SuperCuts — Достъп за Персонала</p>

        <?php if ($error): ?>
            <div class="form-msg error" style="display:block;margin-bottom:18px;text-align:left">
                ✗ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Потребителско Име</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Парола</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="login" class="btn">Влез</button>
        </form>

        <p style="margin-top:20px;font-size:.78rem;color:var(--muted)">
            <a href="index.php">← Обратно към сайта</a>
        </p>
    </div>
</div>
</body>
</html><?php
}
