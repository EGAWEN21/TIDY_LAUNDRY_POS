<?php

namespace App\Http\Controllers;

use App\Models\MasterSettings;

class PwaManifestController extends Controller
{
    /**
     * Generate a dynamic manifest.json for the PWA using MasterSettings.
     */
    public function generate()
    {
        $settings = new MasterSettings();
        $site = $settings->siteData();

        $appName = (isset($site['default_application_name']) && !empty($site['default_application_name'])) ? $site['default_application_name'] : 'TidyPOS';
        $shortName = (isset($site['pwa_short_name']) && !empty($site['pwa_short_name'])) ? $site['pwa_short_name'] : 'TidyPOS';

        $logo192Version = file_exists(public_path('assets/images/logo-192.png')) ? filemtime(public_path('assets/images/logo-192.png')) : time();
        $logo512Version = file_exists(public_path('assets/images/logo-512.png')) ? filemtime(public_path('assets/images/logo-512.png')) : time();
        $manifest = [
            'name' => $appName,
            'short_name' => $shortName,
            'id' => '/admin/pos',
            'description' => 'Offline-ready point of sale for fast, reliable laundry order processing.',
            'start_url' => '/admin/pos',
            'scope' => '/',
            'display' => 'standalone',
            'display_override' => ['window-controls-overlay', 'standalone'],
            'orientation' => 'any',
            'background_color' => '#f8fafc',
            'theme_color' => '#1b2a47',
            'categories' => ['business', 'productivity'],
            'shortcuts' => [
                [
                    'name' => 'Open POS',
                    'short_name' => 'POS',
                    'url' => '/admin/pos',
                    'icons' => [[
                        'src' => asset('assets/images/logo-192.png').'?v='.$logo192Version,
                        'sizes' => '192x192',
                        'type' => 'image/png',
                    ]],
                ],
            ],
            'icons' => [
                [
                    'src' => asset('assets/images/logo-192.png').'?v='.$logo192Version,
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('assets/images/logo-512.png').'?v='.$logo512Version,
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
        ];

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json');
    }
}
