<?php
// api/v2.php — Public API endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../app/init.php';

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
                'service'  => (int)$s['service_id'],
                'name'     => $s['name'],
                'type'     => $s['type'] ?? 'Default',
                'category' => $s['category'] ?? '',
                'rate'     => number_format((float)$s['rate'] * (1 + (float)($s['markup'] ?? 0) / 100), 5),
                'min'      => (string)$s['min'],
                'max'      => (string)$s['max'],
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
        $extra     = [];
        if (isset($_POST['runs']) && $_POST['runs'] !== '') $extra['runs'] = (int)$_POST['runs'];
        if (isset($_POST['interval']) && $_POST['interval'] !== '') $extra['interval'] = (int)$_POST['interval'];

        if (!$serviceId || !$link || !$quantity) {
            echo json_encode(['error' => 'Missing required parameters']); exit;
        }

        $result = $om->placeOrder($user['id'], $serviceId, $link, $quantity, $extra);
        if ($result['success']) {
            echo json_encode(['order' => $result['order_id']]);
        } else {
            echo json_encode(['error' => $result['error']]);
        }
        break;

    case 'status':
        if (isset($_POST['orders'])) {
            $ids  = array_map('intval', array_filter(explode(',', str_replace(' ', '', $_POST['orders'] ?? ''))));
            $ids  = array_slice(array_unique($ids), 0, 100);
            $rows = [];
            if (!empty($ids)) {
                $rows = $db->fetchAll(
                    "SELECT id, status, charge, start_count, remains FROM orders WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ") AND user_id = ?",
                    array_merge($ids, [$user['id']])
                );
            }
            $byId = [];
            foreach ($rows as $r) {
                $byId[$r['id']] = [
                    'charge'      => number_format((float)$r['charge'], 5),
                    'start_count' => (string)($r['start_count'] ?? '0'),
                    'status'      => $r['status'],
                    'remains'     => (string)($r['remains'] ?? '0'),
                    'currency'    => 'USD',
                ];
            }
            $out = [];
            foreach ($ids as $id) {
                $out[(string)$id] = $byId[$id] ?? ['error' => 'Incorrect order ID'];
            }
            echo json_encode($out);
        } else {
            $orderId = (int)($_POST['order'] ?? 0);
            $row = $db->fetch("SELECT * FROM orders WHERE id=? AND user_id=?", [$orderId, $user['id']]);
            if (!$row) { echo json_encode(['error' => 'Incorrect order ID']); exit; }
            echo json_encode([
                'charge'      => number_format((float)$row['charge'], 5),
                'start_count' => (string)($row['start_count'] ?? '0'),
                'status'      => $row['status'],
                'remains'     => (string)($row['remains'] ?? '0'),
                'currency'    => 'USD',
            ]);
        }
        break;

    case 'refill':
        if (isset($_POST['orders'])) {
            $ourIds = array_map('intval', array_filter(explode(',', str_replace(' ', '', $_POST['orders'] ?? ''))));
            $ourIds = array_slice(array_unique($ourIds), 0, 100);
            $orderList = [];
            if (!empty($ourIds)) {
                $rows = $db->fetchAll(
                    "SELECT id, provider_order_id FROM orders WHERE id IN (" . implode(',', array_fill(0, count($ourIds), '?')) . ") AND user_id = ? AND provider_order_id IS NOT NULL",
                    array_merge($ourIds, [$user['id']])
                );
                $byOur = [];
                foreach ($rows as $r) {
                    $byOur[(int)$r['id']] = (int)$r['provider_order_id'];
                }
                foreach ($ourIds as $oid) {
                    if (isset($byOur[$oid])) {
                        $orderList[] = ['our' => $oid, 'provider' => $byOur[$oid]];
                    }
                }
            }
            $providerIds = array_column($orderList, 'provider');
            $resp = is_array($providerIds) && count($providerIds) > 0 ? $api->multiRefill($providerIds) : [];
            $out = [];
            if (is_array($resp)) {
                foreach ($resp as $i => $item) {
                    $ourId = isset($orderList[$i]) ? $orderList[$i]['our'] : null;
                    if ($ourId !== null) {
                        $refill = $item['refill'] ?? $item;
                        if (is_array($refill) && isset($refill['error'])) {
                            $out[] = ['order' => $ourId, 'refill' => $refill];
                        } else {
                            $out[] = ['order' => $ourId, 'refill' => $refill];
                        }
                    }
                }
            }
            $foundOur = array_column($orderList, 'our');
            foreach ($ourIds as $oid) {
                if (!in_array($oid, $foundOur)) {
                    $out[] = ['order' => $oid, 'refill' => ['error' => 'Incorrect order ID']];
                }
            }
            echo json_encode($out);
        } else {
            $orderId = (int)($_POST['order'] ?? 0);
            $row = $db->fetch("SELECT id, provider_order_id FROM orders WHERE id=? AND user_id=?", [$orderId, $user['id']]);
            if (!$row || empty($row['provider_order_id'])) {
                echo json_encode(['error' => 'Incorrect order ID']); exit;
            }
            $resp = $api->refill((int)$row['provider_order_id']);
            if ($resp && isset($resp->refill)) {
                echo json_encode(['refill' => (string)$resp->refill]);
            } else {
                echo json_encode(['error' => $resp->error ?? 'Refill failed']);
            }
        }
        break;

    case 'refill_status':
        if (isset($_POST['refills'])) {
            $refillIds = array_map('trim', array_filter(explode(',', str_replace(' ', '', $_POST['refills'] ?? ''))));
            $refillIds = array_slice(array_unique($refillIds), 0, 100);
            $resp = $api->multiRefillStatus($refillIds);
            echo json_encode(is_array($resp) ? $resp : []);
        } else {
            $refillId = trim($_POST['refill'] ?? '');
            if ($refillId === '') {
                echo json_encode(['error' => 'Missing refill parameter']); exit;
            }
            $resp = $api->refillStatus($refillId);
            if ($resp && isset($resp->status)) {
                echo json_encode(['status' => $resp->status]);
            } elseif ($resp && isset($resp->error)) {
                echo json_encode(['status' => ['error' => $resp->error]]);
            } else {
                echo json_encode(['status' => ['error' => 'Refill not found']]);
            }
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
