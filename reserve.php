<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Методът не е разрешен.']);
    exit;
}

$name    = trim($_POST['name']    ?? '');
$phone   = trim($_POST['phone']   ?? '');
$email   = trim($_POST['email']   ?? '');
$service = trim($_POST['service'] ?? '');
$barber  = trim($_POST['barber']  ?? '');
$date    = trim($_POST['date']    ?? '');
$time    = trim($_POST['time']    ?? '');
$notes   = trim($_POST['notes']   ?? '');

// Basic validation
if (!$name || !$phone || !$service || !$barber || !$date || !$time) {
    http_response_code(400);
    echo json_encode(['error' => 'Моля, попълнете всички задължителни полета.']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Невалиден формат на дата.']);
    exit;
}

if (strtotime($date) < strtotime('today')) {
    http_response_code(400);
    echo json_encode(['error' => 'Моля, изберете бъдеща дата.']);
    exit;
}

$allowedServices = ['Haircut', 'Beard Trim', 'Haircut + Beard', 'Hot Towel Shave', 'Kids Cut'];
$allowedBarbers  = ['Marco', 'Diego', 'Luca'];

if (!in_array($service, $allowedServices) || !in_array($barber, $allowedBarbers)) {
    http_response_code(400);
    echo json_encode(['error' => 'Невалиден избор на услуга или бръснар.']);
    exit;
}

try {
    $pdo = getConnection();

    // Check for existing reservation at same barber/date/time
    $check = $pdo->prepare(
        "SELECT id FROM reservations WHERE barber = :barber AND date = :date AND time = :time AND status != 'cancelled'"
    );
    $check->execute([':barber' => $barber, ':date' => $date, ':time' => $time]);
    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Този час вече е зает. Моля, изберете друг.']);
        exit;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO reservations (name, phone, email, service, barber, date, time, notes)
         VALUES (:name, :phone, :email, :service, :barber, :date, :time, :notes)"
    );
    $stmt->execute([
        ':name'    => $name,
        ':phone'   => $phone,
        ':email'   => $email,
        ':service' => $service,
        ':barber'  => $barber,
        ':date'    => $date,
        ':time'    => $time,
        ':notes'   => $notes ?: null,
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Неуспешно запазване на резервацията. Моля, опитайте отново.']);
}
