<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\User;
use App\Models\MasterSettings;
use App\Models\Translation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Attributes\Title;

class MasterSetting extends Component
{
    use WithFileUploads;
    public $default_currency;
    public $default_application_name;
    public $default_phone_number;
    public $default_financial_year;
    public $default_tax_percentage;
    public $default_state;
    public $default_city;
    public $default_district;
    public $default_zip_code;
    public $default_address;
    public $user;
    public $email;
    public $password;
    public $default_logo;
    public $default_favicon;
    public $default_currency_alignment = 1;
    public $old_favicon;
    public $old_logo;
    public $default_printer = 1;
    public $lang;
    public $country_code;
    public $default_country;
    public $store_tax;
    public $store_email;
    public $default_tax_mode;
    public $bypass_approval_limit;
    /* render the page */
    #[Title('Master Settings')]
    public function render()
    {
        return view('livewire.settings.master-setting');
    }
    /* set the rules */

    /* set value at the time of render */
    public function mount()
    {
        if (!\Illuminate\Support\Facades\Gate::allows('setting_master')) {
            abort(404);
        }
        $this->initialValue();
    }
    public function initialValue()
    {
        $settings = new MasterSettings();
        $site = $settings->siteData();
        $this->default_currency = (isset($site['default_currency']) && !empty($site['default_currency'])) ? $site['default_currency'] : '';
        $this->default_application_name = (isset($site['default_application_name']) && !empty($site['default_application_name'])) ? $site['default_application_name'] : '';
        $this->default_phone_number = (isset($site['default_phone_number']) && !empty($site['default_phone_number'])) ? $site['default_phone_number'] : '';
        $this->default_financial_year = (isset($site['default_financial_year']) && !empty($site['default_financial_year'])) ? $site['default_financial_year'] : '';
        $this->default_tax_percentage = (isset($site['default_tax_percentage']) && !empty($site['default_tax_percentage'])) ? $site['default_tax_percentage'] : '';
        $this->default_state = (isset($site['default_state']) && !empty($site['default_state'])) ? $site['default_state'] : '';
        $this->default_city = (isset($site['default_city']) && !empty($site['default_city'])) ? $site['default_city'] : '';
        $this->default_district = (isset($site['default_district']) && !empty($site['default_district'])) ? $site['default_district'] : '';
        $this->default_zip_code = (isset($site['default_zip_code']) && !empty($site['default_zip_code'])) ? $site['default_zip_code'] : '';
        $this->default_address = (isset($site['default_address']) && !empty($site['default_address'])) ? $site['default_address'] : '';
        $this->default_country = (isset($site['default_country']) && !empty($site['default_country'])) ? $site['default_country'] : '';
        $this->old_logo = (isset($site['default_logo']) && !empty($site['default_logo'])) ? $site['default_logo'] : '';
        $this->old_favicon = (isset($site['default_favicon']) && !empty($site['default_favicon'])) ? $site['default_favicon'] : '';
        $this->country_code = (isset($site['country_code']) && !empty($site['country_code'])) ? $site['country_code'] : '+91';
        $this->store_tax = (isset($site['store_tax_number']) && !empty($site['store_tax_number'])) ? $site['store_tax_number'] : '';
        $this->default_tax_mode = (isset($site['default_tax_mode']) && !empty($site['default_tax_mode'])) ? $site['default_tax_mode'] : 1;
        $this->store_email = (isset($site['store_email']) && !empty($site['store_email'])) ? $site['store_email'] : '';
        $this->default_printer = (isset($site['default_printer']) && !empty($site['default_printer'])) ? $site['default_printer'] : '';
        $this->default_currency_alignment = (isset($site['default_currency_alignment']) && !empty($site['default_currency_alignment'])) ? $site['default_currency_alignment'] : 1;
        $this->bypass_approval_limit = (isset($site['bypass_approval_limit']) && !empty($site['bypass_approval_limit'])) ? $site['bypass_approval_limit'] : 0;
        if (session()->has('selected_language')) {   /*if session has selected language */
            $this->lang = Translation::where('id', session()->get('selected_language'))->first();
        } else {
            /* if session has no selected language */
            $this->lang = Translation::where('default', 1)->first();
        }
        $user = User::findOrFail(1);
        $this->email = $user->email;
        $this->user = $user;
    }
    /* save the master settings data */
    public function save()
    {
        $this->validate([
            'default_currency' => 'required',
            'default_currency_alignment' => 'required',
            'default_application_name' => 'required',
            'default_phone_number' => 'required',
            'default_financial_year' => 'required',
            'default_tax_percentage' => 'required',
            'default_state' => 'required',
            'default_city' => 'required',
            'default_district' => 'required',
            'default_zip_code' => 'required',
            'default_address' => 'required',
            'default_country' => 'required',
            'store_email'   => 'required',
            'store_tax' => 'required',
            'email' => 'required|email|unique:users',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->user->id)],
            'default_printer' => 'required',
            'country_code'  => 'required',
            'bypass_approval_limit' => 'nullable|numeric|min:0',
        ]);

        $settings = new MasterSettings();
        $site = $settings->siteData();
        $site['default_application_name'] = $this->default_application_name;
        $site['default_currency'] = $this->default_currency;
        $site['default_phone_number'] = $this->default_phone_number;
        $site['default_financial_year'] = $this->default_financial_year;
        $site['default_tax_percentage'] = $this->default_tax_percentage;
        $site['default_state'] = $this->default_state;
        $site['default_city'] = $this->default_city;
        $site['default_country'] = $this->default_country;
        $site['default_district'] = $this->default_district;
        $site['default_zip_code'] = $this->default_zip_code;
        $site['default_address'] = $this->default_address;
        $site['default_tax_mode'] = $this->default_tax_mode;
        $site['store_tax_number'] = $this->store_tax;
        $site['store_email'] = $this->store_email;
        $site['default_printer'] = $this->default_printer;
        $site['country_code'] = $this->country_code;
        $site['default_currency_alignment'] = $this->default_currency_alignment;
        $site['bypass_approval_limit'] = $this->bypass_approval_limit;
        if ($this->default_logo) {
            try {
                ini_set('memory_limit', '512M');
                $filename = 'logo_' . time() . '.' . $this->default_logo->getClientOriginalExtension();
                $tempPath = sys_get_temp_dir() . '/' . $filename;

                if (isset($site['default_logo'])) {
                    $oldPath = str_replace('/storage/', '', $site['default_logo']);
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
                    }
                }

                $imgFile = Image::decodePath($this->default_logo->getRealPath());
                $imgFile->scaleDown(width: 500)->save($tempPath);

                \Illuminate\Support\Facades\Storage::disk('public')->putFileAs('logo', new \Illuminate\Http\File($tempPath), $filename);
                @unlink($tempPath);

                $site['default_logo'] = '/storage/logo/' . $filename;

                // --- NEW PWA LOGIC ---
                // Generate perfectly squared icons for PWA and replace the static ones in public/assets/images
                $imgPwa192 = Image::decodePath($this->default_logo->getRealPath());
                $imgPwa192->scaleDown(110, 110)->pad(192, 192, '#00000000')->save(public_path('assets/images/logo-192.png'));

                $imgPwa512 = Image::decodePath($this->default_logo->getRealPath());
                $imgPwa512->scaleDown(280, 280)->pad(512, 512, '#00000000')->save(public_path('assets/images/logo-512.png'));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Logo upload failed: " . $e->getMessage());
            }
        }

        if ($this->default_favicon) {
            try {
                $filename = 'favicon_' . time() . '.' . $this->default_favicon->getClientOriginalExtension();
                $tempPath = sys_get_temp_dir() . '/' . $filename;

                $imgFile = Image::decodePath($this->default_favicon->getRealPath());
                $imgFile->scaleDown(width: 100)->save($tempPath);

                \Illuminate\Support\Facades\Storage::disk('public')->putFileAs('favicon', new \Illuminate\Http\File($tempPath), $filename);
                
                if (file_exists(public_path($this->old_favicon))) {
                    @unlink(public_path($this->old_favicon));
                }

                $site['default_favicon'] = '/storage/favicon/' . $filename;

                // --- NEW PWA LOGIC ---
                // Generate favicon and apple-touch-icon
                $imgFav = Image::decodePath($this->default_favicon->getRealPath());
                $imgFav->contain(180, 180, '0000')->save(public_path('assets/images/apple-touch-icon.png'));
                
                $imgFavIco = Image::decodePath($this->default_favicon->getRealPath());
                $imgFavIco->contain(32, 32, '0000')->save(public_path('favicon.ico'));
                
                // Keep the standard favicon.png too
                $imgFavPng = Image::decodePath($this->default_favicon->getRealPath());
                $imgFavPng->contain(32, 32, '0000')->save(public_path('assets/images/favicon.png'));

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Favicon upload failed: " . $e->getMessage());
            }
        }
        foreach ($site as $key => $value) {
            MasterSettings::updateOrCreate(['master_title' => $key], ['master_value' => $value]);
        }
        $user = User::findOrFail($this->user->id);
        $user->email = $this->email;
        if ($this->password) {
            $password = Hash::make($this->password);
            $user->password = $password;
        }
        $user->save();
        $this->dispatch(
            'alert',
            ['type' => 'success',  'message' => 'Master Settings Updated Successfully!']
        );
    }
}
