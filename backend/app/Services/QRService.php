<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Illuminate\Support\Facades\Log;
use Zxing\QrReader;

class QRService
{
    protected Key $encryptionKey;

    public function __construct()
    {
        // In production, load this from a secure location or .env
        $keyAscii = env('QR_ENCRYPTION_KEY');
        if (!$keyAscii) {
            throw new \RuntimeException('QR_ENCRYPTION_KEY not set in .env');
        }
        $this->encryptionKey = Key::loadFromAsciiSafeString($keyAscii);
    }

    /**
     * Encrypts the given data as a string.
     * @param array $data
     * @return string
     */
    public function encryptData(array $data): string
    {
        $json = json_encode($data);
        return Crypto::encrypt($json, $this->encryptionKey);
    }

    /**
     * Decrypts the given encrypted string back to array.
     * @param string $encrypted
     * @return array|null
     */
    public function decryptData(string $encrypted): ?array
    {
        try {
            $json = Crypto::decrypt($encrypted, $this->encryptionKey);
            return json_decode($json, true);
        } catch (\Throwable $e) {
            Log::error('QR decryption failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generates a QR code PNG (base64) for the given encrypted payload.
     * @param string $encryptedPayload
     * @return string base64-encoded PNG
     */
    public function generateQrCode(string $encryptedPayload): string
    {
        $qr = new \Endroid\QrCode\QrCode(
            data: $encryptedPayload,
            size: 300,
            margin: 10
        );
        $writer = new PngWriter();
        $result = $writer->write($qr);
        return base64_encode($result->getString());
    }

    /**
     * Decodes a QR code image (base64 PNG) and extracts the encrypted data.
     * @param string $base64Image
     * @return string|null
     */
    public function decodeQrCode(string $base64Image): ?string
    {
        try {
            // Decode base64 to binary
            $imageData = base64_decode($base64Image);
            if ($imageData === false) {
                Log::error('Invalid base64 QR code image');
                return null;
            }

            // Create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'qr_');
            file_put_contents($tempFile, $imageData);

            // Use QrReader to decode the QR code
            $qrReader = new QrReader($tempFile);
            $text = $qrReader->text();

            // Clean up temporary file
            unlink($tempFile);

            if (!$text) {
                Log::error('Failed to decode QR code image');
                return null;
            }

            return $text;
        } catch (\Throwable $e) {
            Log::error('Error decoding QR code', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
