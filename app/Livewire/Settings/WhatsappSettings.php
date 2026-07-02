<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\Translation;
use App\Models\MasterSettings;
use Livewire\Attributes\Title;

class WhatsappSettings extends Component
{
    public $whatsapp_enabled;
    public $whatsapp_api_url;
    public $whatsapp_access_token;
    public $whatsapp_phone_number_id;
    public $whatsapp_webhook_verify_token;
    public $whatsapp_not_found_message;
    public $whatsapp_business_number;
    public $enable_automated_whatsapp, $unofficial_whatsapp_url, $unofficial_whatsapp_instance_token, $whatsapp_burner_number;
    public $lang;

    #[Title('WhatsApp Settings')]
    public function render()
    {
        return view('livewire.settings.whatsapp-settings');
    }

    public function mount()
    {
        // Use an existing gate, we'll use setting_sms as a fallback if setting_whatsapp doesn't exist
        if(!\Illuminate\Support\Facades\Gate::allows('setting_master')){
            abort(404);
        }

        $settings = new MasterSettings();
        $site = $settings->siteData();

        $this->whatsapp_enabled = (isset($site['whatsapp_enabled']) && !empty($site['whatsapp_enabled'])) ? $site['whatsapp_enabled'] : 0;
        $this->whatsapp_api_url = (isset($site['whatsapp_api_url']) && !empty($site['whatsapp_api_url'])) ? $site['whatsapp_api_url'] : 'https://graph.facebook.com/v18.0/';
        $this->whatsapp_access_token = (isset($site['whatsapp_access_token']) && !empty($site['whatsapp_access_token'])) ? $site['whatsapp_access_token'] : '';
        $this->whatsapp_phone_number_id = (isset($site['whatsapp_phone_number_id']) && !empty($site['whatsapp_phone_number_id'])) ? $site['whatsapp_phone_number_id'] : '';
        $this->whatsapp_webhook_verify_token = (isset($site['whatsapp_webhook_verify_token']) && !empty($site['whatsapp_webhook_verify_token'])) ? $site['whatsapp_webhook_verify_token'] : '';
        $this->whatsapp_not_found_message = (isset($site['whatsapp_not_found_message']) && !empty($site['whatsapp_not_found_message'])) ? $site['whatsapp_not_found_message'] : "Sorry, we couldn't find an order with that number. Please check the number and try again. For further assistance, contact us at <support_number>.";
        $this->whatsapp_business_number = (isset($site['whatsapp_business_number']) && !empty($site['whatsapp_business_number'])) ? $site['whatsapp_business_number'] : '';
        
        $this->enable_automated_whatsapp = (isset($site['enable_automated_whatsapp']) && $site['enable_automated_whatsapp'] == 1) ? 1 : null;
        $this->unofficial_whatsapp_url = $site['unofficial_whatsapp_url'] ?? '';
        $this->unofficial_whatsapp_instance_token = $site['unofficial_whatsapp_instance_token'] ?? '';
        $this->whatsapp_burner_number = $site['whatsapp_burner_number'] ?? '';

        $this->whatsapp_enabled = $this->whatsapp_enabled == 1 ? true : false;

        if(session()->has('selected_language'))
        {
            $this->lang = Translation::where('id', session()->get('selected_language'))->first();
        }
        else{
            $this->lang = Translation::where('default', 1)->first();
        }
    }

    public function save()
    {
        if($this->whatsapp_enabled)
        {
            $this->validate([
                'whatsapp_api_url' => 'required',
                'whatsapp_access_token' => 'required',
                'whatsapp_phone_number_id' => 'required',
                'whatsapp_webhook_verify_token' => 'required'
            ]);
        }

        $settings = new MasterSettings();
        $site = $settings->siteData();
        
        $site['whatsapp_enabled'] = $this->whatsapp_enabled ? 1 : 0;
        $site['whatsapp_api_url'] = $this->whatsapp_api_url;
        $site['whatsapp_access_token'] = $this->whatsapp_access_token;
        $site['whatsapp_phone_number_id'] = $this->whatsapp_phone_number_id;
        $site['whatsapp_webhook_verify_token'] = $this->whatsapp_webhook_verify_token;
        $site['whatsapp_not_found_message'] = $this->whatsapp_not_found_message;
        $site['whatsapp_business_number'] = $this->whatsapp_business_number;
        
        $site['enable_automated_whatsapp'] = $this->enable_automated_whatsapp ? 1 : 0;
        $site['unofficial_whatsapp_url'] = $this->unofficial_whatsapp_url;
        $site['unofficial_whatsapp_instance_token'] = $this->unofficial_whatsapp_instance_token;
        $site['whatsapp_burner_number'] = $this->whatsapp_burner_number;

        foreach ($site as $key => $value) {
            MasterSettings::updateOrCreate(['master_title' => $key], ['master_value' => $value]);
        }

        $this->dispatch(
            'alert', ['type' => 'success',  'message' => 'WhatsApp Settings Updated!']
        );
    }
}
