<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

require __DIR__.'/auth.php';


Route::get('/final-qr-test', function () {
    try {
        // Test data
        $testData = [
            'transaction_id' => 'TEST-' . time(),
            'customer_email' => 'test@example.com',
            'points' => 100,
            'type' => 'earning',
            'timestamp' => now()->toISOString()
        ];
        
        // Generate QR
        $qr = QrCode::format('png')
            ->size(300)
            ->generate(json_encode($testData));
        
        // Save to storage
        $filename = 'storage/qr-codes/final-test-' . time() . '.png';
        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $qr);
        
        $url = asset('storage/storage/qr-codes/' . basename($filename));

        
        return response()->json([
            'status' => 'success',
            'message' => 'QR generated and saved successfully!',
            'url' => $url,
            'data' => $testData
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
});