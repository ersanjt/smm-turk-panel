<?php
/**
 * Payment methods registry (SMMFA-style add funds).
 */
class PaymentRegistry
{
    public const SMMPAYGATE   = 'smmpaygate';
    public const HELEKET      = 'heleket';
    public const USDT_TRC20   = 'usdt_trc20';
    public const BINANCE_PAY  = 'binance_pay';
    public const ZARINPAL     = 'zarinpal';
    public const CRYPTOCLOUD  = 'cryptocloud';

    /** @return array<string, array{label: string, desc: string, type: string, icon: string, enabled_setting: string, settings: array<string, string>}> */
    public static function definitions(): array
    {
        return [
            self::SMMPAYGATE => [
                'label' => 'SmmPayGate',
                'desc' => 'Own payment gateway',
                'type' => 'redirect',
                'icon' => '💳',
                'enabled_setting' => 'payment_smmpaygate_enabled',
                'settings' => [
                    'payment_smmpaygate_api_url' => 'API URL',
                    'payment_smmpaygate_api_key' => 'API Key',
                    'payment_smmpaygate_merchant_id' => 'Merchant ID',
                    'payment_smmpaygate_secret' => 'Webhook Secret (optional)',
                ],
            ],
            self::HELEKET => [
                'label' => 'Heleket',
                'desc' => 'Crypto — USDT & more',
                'type' => 'hybrid',
                'icon' => '⚡',
                'enabled_setting' => 'payment_heleket_enabled',
                'settings' => [
                    'payment_heleket_merchant_id' => 'Merchant UUID',
                    'payment_heleket_api_key' => 'Payment API Key',
                    'payment_heleket_mode' => 'Mode: panel or redirect',
                    'payment_heleket_currency' => 'Crypto currency (USDT)',
                    'payment_heleket_network' => 'Network (tron, bsc, eth…)',
                ],
            ],
            self::USDT_TRC20 => [
                'label' => 'USDT TRC20',
                'desc' => 'Direct wallet transfer',
                'type' => 'manual',
                'icon' => '₮',
                'enabled_setting' => 'payment_usdt_trc20_enabled',
                'settings' => [
                    'wallet_usdt_trc20' => 'TRC20 wallet address',
                ],
            ],
            self::BINANCE_PAY => [
                'label' => 'Binance Pay',
                'desc' => 'Binance checkout',
                'type' => 'redirect',
                'icon' => '🟡',
                'enabled_setting' => 'payment_binance_pay_enabled',
                'settings' => [
                    'payment_binance_pay_api_key' => 'Certificate SN (API Key)',
                    'payment_binance_pay_secret' => 'Secret Key',
                ],
            ],
            self::ZARINPAL => [
                'label' => 'ZarinPal',
                'desc' => 'Iran card / bank',
                'type' => 'redirect',
                'icon' => '🏦',
                'enabled_setting' => 'payment_zarinpal_enabled',
                'settings' => [
                    'payment_zarinpal_merchant_id' => 'Merchant ID (36 chars)',
                    'payment_zarinpal_usd_rate' => 'USD → IRR rate (e.g. 600000)',
                    'payment_zarinpal_sandbox' => 'Sandbox mode (0/1)',
                ],
            ],
            self::CRYPTOCLOUD => [
                'label' => 'CryptoCloud',
                'desc' => 'Multi-crypto invoices',
                'type' => 'redirect',
                'icon' => '☁️',
                'enabled_setting' => 'payment_cryptocloud_enabled',
                'settings' => [
                    'payment_cryptocloud_shop_id' => 'Shop ID',
                    'payment_cryptocloud_api_key' => 'API Key (Token)',
                ],
            ],
        ];
    }

    public static function label(string $slug): string
    {
        return self::definitions()[$slug]['label'] ?? $slug;
    }

    public static function isEnabled(string $slug): bool
    {
        $def = self::definitions()[$slug] ?? null;
        if (!$def) {
            return false;
        }
        $db = Database::getInstance();
        if (($db->getSetting($def['enabled_setting']) ?? '0') !== '1') {
            return false;
        }
        return self::hasRequiredCredentials($slug);
    }

    public static function hasRequiredCredentials(string $slug): bool
    {
        $db = Database::getInstance();
        return match ($slug) {
            self::SMMPAYGATE => trim((string) $db->getSetting('payment_smmpaygate_api_url')) !== ''
                && trim((string) $db->getSetting('payment_smmpaygate_api_key')) !== '',
            self::HELEKET => trim((string) $db->getSetting('payment_heleket_merchant_id')) !== ''
                && trim((string) $db->getSetting('payment_heleket_api_key')) !== '',
            self::USDT_TRC20 => trim((string) $db->getSetting('wallet_usdt_trc20')) !== '',
            self::BINANCE_PAY => trim((string) $db->getSetting('payment_binance_pay_api_key')) !== ''
                && trim((string) $db->getSetting('payment_binance_pay_secret')) !== '',
            self::ZARINPAL => trim((string) $db->getSetting('payment_zarinpal_merchant_id')) !== '',
            self::CRYPTOCLOUD => trim((string) $db->getSetting('payment_cryptocloud_shop_id')) !== ''
                && trim((string) $db->getSetting('payment_cryptocloud_api_key')) !== '',
            default => false,
        };
    }

    /** @return array<string, array> */
    public static function enabledMethods(): array
    {
        $out = [];
        foreach (self::definitions() as $slug => $def) {
            if (self::isEnabled($slug)) {
                $out[$slug] = $def;
            }
        }
        return $out;
    }

    public static function orderId(int $transactionId): string
    {
        return 'dep_' . $transactionId;
    }

    public static function depositDescription(float $amount, string $methodSlug): string
    {
        return 'Deposit $' . number_format($amount, 2, '.', '') . ' — ' . self::label($methodSlug);
    }

    public static function parseMethodFromDescription(?string $description): ?string
    {
        if ($description === null || $description === '') {
            return null;
        }
        foreach (self::definitions() as $slug => $def) {
            if (stripos($description, '— ' . $def['label']) !== false) {
                return $slug;
            }
        }
        if (stripos($description, 'USDT TRC20') !== false || stripos($description, 'USDT Tron') !== false) {
            return self::USDT_TRC20;
        }
        if (preg_match('/\(crypto\)$/i', $description)) {
            return self::USDT_TRC20;
        }
        return null;
    }

    public static function callbackUrl(string $gateway): string
    {
        return url('payment-callback.php') . '?gateway=' . rawurlencode($gateway);
    }

    public static function webhookUrl(string $gateway): string
    {
        return url('payment-webhook.php') . '?gateway=' . rawurlencode($gateway);
    }

    public static function coinKeyForManual(string $slug): ?string
    {
        return $slug === self::USDT_TRC20 ? 'wallet_usdt_trc20' : null;
    }

    /** Parse Heleket reference: hk:uuid|address */
    public static function parseHeleketRef(?string $reference): ?array
    {
        if ($reference === null || !str_starts_with($reference, 'hk:')) {
            return null;
        }
        $parts = explode('|', substr($reference, 3), 2);
        return [
            'uuid' => $parts[0] ?? '',
            'address' => $parts[1] ?? '',
        ];
    }

    public static function heleketNetworkLabel(string $network): string
    {
        return match (strtolower($network)) {
            'tron', 'trc20' => 'TRC20 (Tron)',
            'bsc', 'bep20' => 'BEP-20 (BSC)',
            'eth', 'erc20' => 'ERC20 (Ethereum)',
            'btc' => 'Bitcoin',
            default => strtoupper($network),
        };
    }
}
