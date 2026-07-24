<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Settings\MasterSetting;
use App\Models\MasterSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MasterSettingStorageTest extends TestCase
{
    use DatabaseTransactions;

    public function test_public_disk_uses_the_storage_link_contract(): void
    {
        $this->assertSame(storage_path('app/public'), config('filesystems.disks.public.root'));
        $this->assertStringEndsWith('/storage', config('filesystems.disks.public.url'));
        $this->assertTrue(file_exists(public_path('storage')));
    }

    public function test_master_settings_save_persists_logo_and_favicon_storage_urls(): void
    {
        Storage::fake('public');
        $user = User::firstOrFail();

        Livewire::actingAs($user)
            ->test(MasterSetting::class)
            ->set('default_application_name', 'Storage Test App')
            ->set('default_currency', '$')
            ->set('default_phone_number', '0800000000')
            ->set('default_financial_year', '1')
            ->set('default_tax_percentage', '10')
            ->set('default_state', 'Test State')
            ->set('default_city', 'Test City')
            ->set('default_district', 'Test District')
            ->set('default_zip_code', '000000')
            ->set('default_address', 'Storage Test Address')
            ->set('default_country', 'US')
            ->set('store_email', 'store@example.com')
            ->set('store_tax', 'TAX-TEST')
            ->set('email', $user->email)
            ->set('default_printer', 1)
            ->set('country_code', '+1')
            ->set('default_logo', UploadedFile::fake()->image('logo.png', 40, 40))
            ->set('default_favicon', UploadedFile::fake()->image('favicon.png', 20, 20))
            ->call('save')
            ->assertHasNoErrors();

        $site = (new MasterSettings())->siteData();

        $this->assertStringStartsWith('/storage/logo/', $site['default_logo']);
        $this->assertStringStartsWith('/storage/favicon/', $site['default_favicon']);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $site['default_logo']));
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $site['default_favicon']));
    }
}
