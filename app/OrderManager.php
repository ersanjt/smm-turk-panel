<?php
class OrderManager {

    private Database $db;
    private SmmApi $api;

    public function __construct() {
        $this->db  = Database::getInstance();
        $this->api = new SmmApi();
    }

    public function placeOrder(int $userId, int $serviceId, string $link, int $quantity, array $extra = []): array {
        $service = $this->db->fetch("SELECT * FROM services WHERE service_id = ? AND status = 'active'", [$serviceId]);
        if (!$service) return ['success' => false, 'error' => 'Service not found or inactive'];

        if ($quantity < $service['min'] || $quantity > $service['max']) {
            return ['success' => false, 'error' => "Quantity must be between {$service['min']} and {$service['max']}"];
        }

        $markup = 1 + ($service['markup'] / 100);
        $charge = round(($service['rate'] / 1000) * $quantity * $markup, 4);

        $user = $this->db->fetch("SELECT balance FROM users WHERE id = ?", [$userId]);
        if (!$user || $user['balance'] < $charge) {
            return ['success' => false, 'error' => 'Insufficient balance. Please add funds.'];
        }

        $orderData = array_merge(['service' => $serviceId, 'link' => $link, 'quantity' => $quantity], $extra);
        $response  = $this->api->order($orderData);

        if (!$response || isset($response->error)) {
            return ['success' => false, 'error' => $response->error ?? 'Provider error. Please try again.'];
        }

        $this->db->execute("UPDATE users SET balance = balance - ?, spent = spent + ? WHERE id = ?", [$charge, $charge, $userId]);

        $orderId = $this->db->insert(
            "INSERT INTO orders (user_id, provider_order_id, service_id, service_name, link, quantity, charge, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')",
            [$userId, $response->order ?? null, $serviceId, $service['name'], $link, $quantity, $charge]
        );

        $balanceAfter = $user['balance'] - $charge;
        $this->db->insert(
            "INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, description, reference)
             VALUES (?, 'order', ?, ?, ?, ?, ?)",
            [$userId, -$charge, $user['balance'], $balanceAfter, "Order #{$orderId}: " . substr($service['name'], 0, 60), (string)$orderId]
        );

        // Referral commission
        $buyer = $this->db->fetch("SELECT referred_by FROM users WHERE id = ?", [$userId]);
        if (!empty($buyer['referred_by'])) {
            $pct = (float)($this->db->getSetting('referral_commission') ?: 2);
            if ($pct > 0) {
                $commission = round($charge * ($pct / 100), 4);
                $this->db->execute("UPDATE users SET referral_earnings = referral_earnings + ? WHERE id = ?", [$commission, $buyer['referred_by']]);
                try {
                    $this->db->execute("UPDATE users SET total_referral_earnings = total_referral_earnings + ? WHERE id = ?", [$commission, $buyer['referred_by']]);
                } catch (Throwable $e) { /* column may not exist */ }
            }
        }

        return ['success' => true, 'order_id' => $orderId, 'charge' => $charge];
    }

    public function syncOrders(): int {
        $orders = $this->db->fetchAll(
            "SELECT id, provider_order_id FROM orders WHERE status IN ('Pending','Processing','In progress') AND provider_order_id IS NOT NULL LIMIT 100"
        );
        if (empty($orders)) return 0;

        $providerIds = array_column($orders, 'provider_order_id');
        $statuses    = $this->api->multiStatus($providerIds);
        $updated     = 0;

        foreach ($orders as $order) {
            $pid    = $order['provider_order_id'];
            $status = $statuses->$pid ?? null;
            if (!$status || isset($status->error)) continue;

            $this->db->execute(
                "UPDATE orders SET status = ?, start_count = ?, remains = ? WHERE id = ?",
                [$status->status, $status->start_count ?? 0, $status->remains ?? 0, $order['id']]
            );
            $updated++;
        }
        return $updated;
    }

    public function getUserOrders(int $userId, string $status = '', int $limit = 50, int $offset = 0): array {
        $where  = "WHERE o.user_id = ?";
        $params = [$userId];
        if ($status) { $where .= " AND o.status = ?"; $params[] = $status; }

        return $this->db->fetchAll(
            "SELECT o.*, s.category FROM orders o LEFT JOIN services s ON o.service_id = s.service_id
             $where ORDER BY o.created_at DESC LIMIT $limit OFFSET $offset",
            $params
        );
    }

    public function getUserOrderCount(int $userId, string $status = ''): int {
        $where  = "WHERE user_id = ?";
        $params = [$userId];
        if ($status) { $where .= " AND status = ?"; $params[] = $status; }
        $row = $this->db->fetch("SELECT COUNT(*) as cnt FROM orders $where", $params);
        return (int)($row['cnt'] ?? 0);
    }

    public function syncServices(): array {
        $services = $this->api->services();
        if (!$services) return ['success' => false, 'error' => 'Could not fetch services from provider'];

        $markup = (float)($this->db->getSetting('markup_percent') ?? MARKUP_PERCENT);
        $count  = 0;

        foreach ($services as $s) {
            $name = ContentCorrections::correctServiceName($s['name'] ?? '');
            $category = ContentCorrections::correctCategory($s['category'] ?? '');
            $this->db->execute(
                "INSERT INTO services (service_id, name, type, category, rate, min, max, refill, cancel, markup)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE name=VALUES(name), type=VALUES(type), category=VALUES(category),
                 rate=VALUES(rate), min=VALUES(min), max=VALUES(max), refill=VALUES(refill), cancel=VALUES(cancel)",
                [
                    $s['service'], $name, $s['type'] ?? 'Default', $category,
                    $s['rate'], $s['min'], $s['max'],
                    ($s['refill'] ?? false) ? 1 : 0, ($s['cancel'] ?? false) ? 1 : 0, $markup
                ]
            );
            $count++;
        }
        return ['success' => true, 'synced' => $count];
    }
}
