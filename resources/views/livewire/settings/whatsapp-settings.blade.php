<div class="dashboard-main-body">
    <div class="card h-100 p-0 radius-12 overflow-hidden">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ $lang->data['whatsapp_settings'] ?? 'WhatsApp API Settings' }}</h5>
        </div>
        <div class="card-body ">
            <div class="row">
                <div class="col-12 mt-4">
                    <h6 class="mb-3">1. Official Meta API (For Inbound Chatbot)</h6>
                    <p class="text-sm text-secondary-light mb-4">This powers your inbound customer queries. It uses the official Meta Graph API.</p>
                </div>

                <div class="col-sm-12 row mb-20 tw-pl-6 ">
                    <div class="form-switch switch-primary d-flex align-items-center gap-3 col-sm-4 lg:pt-0 pt-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="switch1" wire:model="whatsapp_enabled">
                        <label class="form-check-label line-height-1 fw-medium text-secondary-light" for="switch1">{{ $lang->data['whatsapp_enabled'] ?? 'Enable Official Chatbot' }}</label>
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="mb-20">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                            Meta API URL <span class="text-danger">*</span>
                        </label>
                        <input type="text" required class="form-control radius-8" wire:model="whatsapp_api_url" placeholder="https://graph.facebook.com/v18.0/">
                        @error('whatsapp_api_url') <span class="text-danger">{{$message}}</span>  @enderror
                    </div>
                </div>
                
                <div class="col-sm-6">
                    <div class="mb-20">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                            Phone Number ID <span class="text-danger">*</span>
                        </label>
                        <input type="text" required class="form-control radius-8" wire:model="whatsapp_phone_number_id">
                        @error('whatsapp_phone_number_id') <span class="text-danger">{{$message}}</span>  @enderror
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="mb-20">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                            Access Token <span class="text-danger">*</span>
                        </label>
                        <input type="password" required class="form-control radius-8" wire:model="whatsapp_access_token">
                        @error('whatsapp_access_token') <span class="text-danger">{{$message}}</span>  @enderror
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="mb-20">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                            Webhook Verify Token <span class="text-danger">*</span>
                        </label>
                        <input type="text" required class="form-control radius-8" wire:model="whatsapp_webhook_verify_token">
                        @error('whatsapp_webhook_verify_token') <span class="text-danger">{{$message}}</span>  @enderror
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <hr>
                    <h6 class="mb-3">2. Unofficial API (For Automated Outbound Updates)</h6>
                    <p class="text-sm text-secondary-light mb-4">This uses a third-party API (like UltraMsg) connected to a burner phone for zero-cost automated status updates.</p>
                </div>

                <div class="col-sm-12 row mb-20 tw-pl-6 ">
                    <div class="form-switch switch-primary d-flex align-items-center gap-3 col-sm-6 lg:pt-0 pt-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="switch_auto_whatsapp" wire:model="enable_automated_whatsapp">
                        <label class="form-check-label line-height-1 fw-medium text-secondary-light" for="switch_auto_whatsapp">{{ $lang->data['enable_automated_whatsapp'] ?? 'Enable Automated WhatsApp Updates' }}</label>
                    </div>
                    <small class="text-muted mt-2 tw-block">If disabled, the system will gracefully fall back to popping open a manual <code>wa.me</code> link for your staff to send.</small>
                </div>

                <div class="col-sm-6">
                    <div class="mb-20">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                            Unofficial API URL
                        </label>
                        <input type="text" class="form-control radius-8" wire:model="unofficial_whatsapp_url" placeholder="https://api.ultramsg.com/instance...">
                        @error('unofficial_whatsapp_url') <span class="text-danger">{{$message}}</span>  @enderror
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="mb-20">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                            Instance Token
                        </label>
                        <input type="password" class="form-control radius-8" wire:model="unofficial_whatsapp_instance_token">
                        @error('unofficial_whatsapp_instance_token') <span class="text-danger">{{$message}}</span>  @enderror
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="mb-20">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                            Burner Phone Number
                        </label>
                        <input type="text" class="form-control radius-8" wire:model="whatsapp_burner_number" placeholder="+1234567890">
                        @error('whatsapp_burner_number') <span class="text-danger">{{$message}}</span>  @enderror
                    </div>
                </div>

                <div class="col-12 ">
                    <hr>
                    <div class="col-sm-6">
                        <div class="mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                Business Support Number (for missing orders)
                            </label>
                            <input type="text" class="form-control radius-8" wire:model="whatsapp_business_number" placeholder="+1234567890">
                            @error('whatsapp_business_number') <span class="text-danger">{{$message}}</span>  @enderror
                        </div>
                    </div>

                    <div class="tw-w-full tw-flex tw-gap-2 tw-mt-4 tw-mb-8">
                        <div class="tw-w-full tw-h-full tw-flex tw-flex-col">
                            <label class="form-label">Not Found Message (Sent if the customer's Order Number is invalid)</label>
                            <textarea class="form-control tw-h-full" rows="5" wire:model="whatsapp_not_found_message"></textarea>
                            <small class="text-muted">Use &lt;support_number&gt; to dynamically inject your Business Support Number above.</small>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-center gap-3 mt-24">
                    <button type="submit" class="btn btn-primary border border-primary-600 text-md px-24 py-12 radius-8" wire:click.prevent="save()">{{ $lang->data['save'] ?? 'Save' }}</button>
                </div>
            </div>
        </div>
    </div>
</div>
