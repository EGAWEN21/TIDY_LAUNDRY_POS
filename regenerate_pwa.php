<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MasterSettings;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;

$setting = MasterSettings::first();
if ($setting && $setting->default_logo) {
    echo "Found default logo: " . $setting->default_logo . "\n";
    // default_logo could be a storage path or full URL. Usually it's like 'master-settings/logo.png'
    $path = public_path('storage/' . $setting->default_logo);
    
    // If it's stored directly in public or something
    if (!file_exists($path)) {
        // Try without storage
        $path = public_path(str_replace('/storage/', '', $setting->default_logo));
    }
    if (!file_exists($path)) {
        // Just try public_path of default_logo
        $path = public_path($setting->default_logo);
    }
    
    if (file_exists($path)) {
        echo "Processing path: $path\n";
        try {
            $image = Image::read($path);
            // Have to use decodePath because read throws namespace errors previously
            $image = Image::decodePath($path);
            
            $imgPwa192 = clone $image;
            $imgPwa512 = clone $image;
            $appleIcon = clone $image;
            
            $imgPwa192->scaleDown(110, 110)->pad(192, 192, '#00000000')->save(public_path('assets/images/logo-192.png'));
            $imgPwa512->scaleDown(280, 280)->pad(512, 512, '#00000000')->save(public_path('assets/images/logo-512.png'));
            $appleIcon->scaleDown(110, 110)->pad(180, 180, '#00000000')->save(public_path('assets/images/apple-touch-icon.png'));
            echo "Successfully generated PWA icons from existing logo.\n";
        } catch (\Exception $e) {
            echo "Error processing image: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Logo file not found on disk: $path\n";
    }
} else {
    echo "No default logo found in database.\n";
}
