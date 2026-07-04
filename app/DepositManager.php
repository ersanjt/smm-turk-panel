<?php
/**
 * Credit user balance for deposits and notify by email.
 */
class DepositManager {
    private Database $db;
    private Mail $mail;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->mail = new Mail();
    }

    /** Approve a pending crypto deposit transaction. */
    public function approvePendingDeposit(int $transactionId): array {
        $tx = $this->db->fetch(
            "SELECT * FROM transactions WHERE id = ? AND type = 'deposit' AND status = 'pending'",
            [$transactionId]
        );
        if (!$tx) {
            return ['success' => false, 'error' => 'Deposit not found or already processed.'];
        }

        return $this->creditUser(
            (int) $tx['user_id'],
            (float) $tx['amount'],
            'deposit',
            $tx['description'] ?? 'Crypto deposit',
            $transactionId
        );
    }

    /**
     * Add funds to a user account and send confirmation email.
     * @param int|null $existingTransactionId Pending deposit row to mark completed; null creates admin credit row.
     */
    public function creditUser(int $userId, float $amount, string $type = 'deposit', string $description = 'Deposit', ?int $existingTransactionId = null): array {
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Invalid amount.'];
        }

        $user = $this->db->fetch("SELECT id, username, email, balance FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            return ['success' => false, 'error' => 'User not found.'];
        }

        $balanceBefore = (float) $user['balance'];
        $balanceAfter = round($balanceBefore + $amount, 4);

        $this->db->getConnection()->beginTransaction();
        try {
            $this->db->execute("UPDATE users SET balance = balance + ? WHERE id = ?", [$amount, $userId]);

            if ($existingTransactionId) {
                $this->db->execute(
                    "UPDATE transactions SET status = 'completed', balance_after = ? WHERE id = ? AND status = 'pending'",
                    [$balanceAfter, $existingTransactionId]
                );
            } else {
                $this->db->insert(
                    "INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, description, status) VALUES (?, ?, ?, ?, ?, ?, 'completed')",
                    [$userId, $type, $amount, $balanceBefore, $balanceAfter, $description]
                );
            }

            $this->db->getConnection()->commit();
        } catch (Throwable $e) {
            $this->db->getConnection()->rollBack();
            Logger::log("Deposit credit failed user#{$userId}: " . $e->getMessage(), 'deposits');
            return ['success' => false, 'error' => 'Failed to credit balance. Please try again.'];
        }

        $emailSent = $this->mail->sendDepositConfirmed(
            $user['email'],
            $user['username'],
            $amount,
            $balanceAfter,
            $existingTransactionId
        );

        return [
            'success' => true,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'email_sent' => $emailSent,
        ];
    }
}
