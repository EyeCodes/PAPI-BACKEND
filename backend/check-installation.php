<?php

// Run this with: php check-installation.php

echo "=== PHP QR Installation Check ===\n\n";

// Check if running from Laravel root
if (!file_exists('artisan')) {
    echo "❌ Please run this from your Laravel project root directory\n";
    exit(1);
}

// Check composer.json for the package
$composerJson = json_decode(file_get_contents('composer.json'), true);
$hasQrPackage = isset($composerJson['require']['simplesoftwareio/simple-qrcode']);

echo "1. QR Package in composer.json: " . ($hasQrPackage ? "✅ YES" : "❌ NO") . "\n";

// Check if vendor directory has the package
$vendorPath = 'vendor/simplesoftwareio/simple-qrcode';
$vendorExists = is_dir($vendorPath);

echo "2. QR Package in vendor: " . ($vendorExists ? "✅ YES" : "❌ NO") . "\n";

// Check storage directories
$storageExists = is_dir('storage/app/public');
$qrDirExists = is_dir('storage/app/public/qr-codes');
$storageLinkExists = is_link('public/storage');

echo "3. Storage directory: " . ($storageExists ? "✅ YES" : "❌ NO") . "\n";
echo "4. QR codes directory: " . ($qrDirExists ? "✅ YES" : "❌ NO") . "\n";
echo "5. Storage link: " . ($storageLinkExists ? "✅ YES" : "❌ NO") . "\n";

// Check permissions
if ($qrDirExists) {
    $perms = substr(sprintf('%o', fileperms('storage/app/public/qr-codes')), -4);
    echo "6. QR directory permissions: " . $perms . "\n";
    
    // Test write permissions
    $testFile = 'storage/app/public/qr-codes/test-' . time() . '.txt';
    $canWrite = file_put_contents($testFile, 'test') !== false;
    echo "7. Can write to QR directory: " . ($canWrite ? "✅ YES" : "❌ NO") . "\n";
    
    if ($canWrite && file_exists($testFile)) {
        unlink($testFile);
    }
}

echo "\n=== Recommendations ===\n";

if (!$hasQrPackage) {
    echo "- Run: composer require simplesoftwareio/simple-qrcode\n";
}

if (!$storageLinkExists) {
    echo "- Run: php artisan storage:link\n";
}

if (!$qrDirExists) {
    echo "- Run: mkdir -p storage/app/public/qr-codes\n";
}

echo "\n=== Test Commands ===\n";
echo "Visit these URLs to test:\n";
echo "- http://your-app.test/test-qr-generation (JSON response)\n";
echo "- http://your-app.test/test-qr-image (Direct QR image)\n";

echo "\nDone!\n";
