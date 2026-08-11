<?php

namespace App\Http\Controllers;

use App\Models\MasterSettings;
use Illuminate\Http\Request;

class PwaManifestController extends Controller
{
    /**
     * Generate a dynamic manifest.json for the PWA using MasterSettings.
     */
    public function generate(Request $request)
    {
        $settings = new MasterSettings();
        $site = $settings->siteData();

        $appName = (isset($site['default_application_name']) && !empty($site['default_application_name'])) ? $site['default_application_name'] : 'TidyPOS';
        $shortName = (isset($site['pwa_short_name']) && !empty($site['pwa_short_name'])) ? $site['pwa_short_name'] : 'TidyPOS';

        $favicon = (isset($site['default_favicon']) && !empty($site['default_favicon'])) ? $site['default_favicon'] : '/assets/images/favicon.png';
        $logo = (isset($site['default_logo']) && !empty($site['default_logo'])) ? $site['default_logo'] : '/assets/images/logo.png';

        $logo192Version = file_exists(public_path('assets/images/logo-192.png')) ? filemtime(public_path('assets/images/logo-192.png')) : time();
        $logo512Version = file_exists(public_path('assets/images/logo-512.png')) ? filemtime(public_path('assets/images/logo-512.png')) : time();
        
        $manifest = [
            'name' => $appName,
            'short_name' => $shortName,
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#1b2a47',
            'icons' => [
                [
                    'src' => asset('assets/images/logo-192.png') . '?v=' . $logo192Version,
                    'sizes' => '192x192',
                    'type' => 'image/png'
                ],
                [
                    'src' => asset('assets/images/logo-512.png') . '?v=' . $logo512Version,
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ]
            ]
        ];

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json');
    }
}
