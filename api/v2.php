<?php
// api/v2.php — Public API endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/init.php';

$key    = $_POST['key'] ?? $_GET['key'] ?? '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (!$key || !$action) {
    echo json_encode(['error' => 'Missing key or action']);
    exit;
}

// Validate API key
$user = $db->fetch("SELECT * FROM users WHERE api_key = ? AND status = 'active'", [$key]);
if (!$user) {
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

$om  = new OrderManager();
$api = new SmmApi();

switch ($action) {

    case 'services':
        $services = $db->fetchAll("SELECT * FROM services WHERE status='active' ORDER BY service_id");
        $output = [];
        foreach ($services as $s) {
            $output[] = [
                'service'  => $s['service_id'],
                'name'     => $s['name'],
                'type'     => $s['type'],
                'category' => $s['category'],
                'rate'     => number_format($s['rate'] * (1 + $s['markup']/100), 5),
                'min'      => $s['min'],
                'max'      => $s['max'],
                'refill'   => (bool)$s['refill'],
                'cancel'   => (bool)$s['cancel'],
            ];
        }
        echo json_encode($output);
        break;

    case 'add':
        $serviceId = (int)($_POST['service'] ?? 0);
        $link      = trim($_POST['link'] ?? '');
        $quantity  = (int)($_POST['quantity'] ?? 0);

        if (!$serviceId || !$link || !$quantity) {
            echo json_encode(['error' => 'Missing required parameters']); exit;
        }

        $result = $om->placeOrder($user['id'], $serviceId, $link, $quantity);
        if ($result['success']) {
            echo json_encode(['order' => $result['order_id']]);
        } else {
            echo json_encode(['error' => $result['error']]);
        }
        break;

    case 'status':
        if (isset($_POST['orders'])) {
            $ids  = array_map('intval', explode(',', $_POST['orders']));
            $rows = $db->fetchAll(
                "SELECT id, status, charge, start_count, remains FROM orders WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ") AND user_id = ?",
                array_merge($ids, [$user['id']])
            );
            $out = [];
            foreach ($rows as $r) {
                $out[$r['id']] = [
                    'charge'      => $r['charge'],
                    'start_count' => $r['start_count'],
                    'status'      => $r['status'],
                    'remains'     => $r['remains'],
                    'currency'    => 'USD',
                ];
            }
            echo json_encode($out);
        } else {
            $orderId = (int)($_POST['order'] ?? 0);
            $row = $db->fetch("SELECT * FROM orders WHERE id=? AND user_id=?", [$orderId, $user['id']]);
            if (!$row) { echo json_encode(['error' => 'Order not found']); exit; }
            echo json_encode([
                'charge'      => $row['charge'],
                'start_count' => $row['start_count'],
                'status'      => $row['status'],
                'remains'     => $row['remains'],
                'currency'    => 'USD',
            ]);
        }
        break;

    case 'balance':
        echo json_encode([
            'balance'  => number_format($user['balance'], 5),
            'currency' => 'USD',
        ]);
        break;

    case 'cancel':
        $ids = array_map('intval', explode(',', $_POST['orders'] ?? ''));
        $out = [];
        foreach ($ids as $id) {
            $order = $db->fetch("SELECT * FROM orders WHERE id=? AND user_id=? AND status='Pending'", [$id, $user['id']]);
            if (!$order) { $out[] = ['order' => $id, 'cancel' => ['error' => 'Cannot cancel']]; continue; }
            $db->execute("UPDATE orders SET status='Cancelled' WHERE id=?", [$id]);
            $db->execute("UPDATE users SET balance = balance + ? WHERE id=?", [$order['charge'], $user['id']]);
            $out[] = ['order' => $id, 'cancel' => 1];
        }
        echo json_encode($out);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
