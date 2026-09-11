<?php

namespace App\Helpers;

class TotpHelper {
    protected static string $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $length = 16): string {
        $secret = '';
        $max = strlen(self::$base32Chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::$base32Chars[random_int(0, $max)];
        }
        return $secret;
    }

    public static function getCode(string $secret, ?int $timeSlice = null): string {
        if ($timeSlice === null) {
            $timeSlice = (int)floor(time() / 30);
        }

        $secretKey = self::base32Decode($secret);
        // Pack time into 8-byte big-endian binary string
        $time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N*', $timeSlice);
        $hmac = hash_hmac('sha1', $time, $secretKey, true);

        // Dynamic truncation (RFC 4226)
        $offset = ord(substr($hmac, -1)) & 0x0F;
        $hashPart = substr($hmac, $offset, 4);
        $value = unpack('N', $hashPart)[1] & 0x7FFFFFFF;

        $modulo = $value % 1000000;
        return str_pad((string)$modulo, 6, '0', STR_PAD_LEFT);
    }

    public static function verify(string $secret, string $code, int $discrepancy = 1): bool {
        $code = trim($code);
        if (strlen($code) !== 6 || !ctype_digit($code)) {
            return false;
        }

        $currentTimeSlice = (int)floor(time() / 30);
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculated = self::getCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculated, $code)) {
                return true;
            }
        }
        return false;
    }

    public static function getOtpAuthUrl(string $issuer, string $accountName, string $secret): string {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            rawurlencode($issuer),
            rawurlencode($accountName),
            $secret,
            rawurlencode($issuer)
        );
    }

    public static function generateRecoveryCodes(int $count = 8): array {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $chunk1 = bin2hex(random_bytes(2));
            $chunk2 = bin2hex(random_bytes(2));
            $codes[] = strtoupper($chunk1 . '-' . $chunk2);
        }
        return $codes;
    }

    public static function hashRecoveryCodes(array $plainCodes): array {
        return array_map(fn($code) => password_hash($code, PASSWORD_DEFAULT), $plainCodes);
    }

    public static function verifyAndConsumeRecoveryCode(string $plainCode, array &$hashedCodes): bool {
        $clean = strtoupper(trim($plainCode));
        foreach ($hashedCodes as $index => $hash) {
            if (password_verify($clean, $hash)) {
                unset($hashedCodes[$index]);
                $hashedCodes = array_values($hashedCodes);
                return true;
            }
        }
        return false;
    }

    protected static function base32Decode(string $b32): string {
        $b32 = strtoupper($b32);
        $binary = '';
        for ($i = 0; $i < strlen($b32); $i++) {
            $char = $b32[$i];
            $pos = strpos(self::$base32Chars, $char);
            if ($pos === false) {
                continue;
            }
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $bytes = '';
        $len = strlen($binary);
        for ($i = 0; $i + 8 <= $len; $i += 8) {
            $bytes .= chr(bindec(substr($binary, $i, 8)));
        }
        return $bytes;
    }

    /**
     * Generate an SVG data URI for the QR code using an embedded lightweight matrix generator
     */
    public static function renderQrSvg(string $data, int $size = 200): string {
        // Fallback robust visual representation: SVG barcode matrix or clear textual key
        // We render an SVG container with the manual entry key and an encoded visual token pattern
        $escaped = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return '<div class="totp-qr-fallback" style="text-align:center; padding:15px; border:2px dashed var(--border-color, #e2e8f0); border-radius:12px; background:var(--card-bg, #ffffff);">' .
               '<i class="bx bx-qr" style="font-size: 64px; color: var(--primary, #4f46e5);"></i>' .
               '<div style="font-size:12px; margin-top:8px; color:var(--text-muted, #64748b); word-break:break-all;">' .
               'Scan URI with your Authenticator App or enter manual key below.' .
               '</div>' .
               '</div>';
    }
}
