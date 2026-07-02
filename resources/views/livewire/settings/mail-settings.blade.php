<div class="dashboard-main-body">
    <div class="card h-100 p-0 radius-12 overflow-hidden">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ $lang->data['mail_settings'] ?? 'Mail Settings' }}</h5>
        </div>
        <div class="card-body ">
            <div class="row">
                <div class="col-sm-12 row mb-20 tw-pl-6 ">
                    <div class="form-switch switch-primary d-flex align-items-center gap-3 col-sm-6 lg:pt-0 pt-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="switch_email_auto" wire:model="enable_automated_emails">
                        <label class="form-check-label line-height-1 fw-medium text-secondary-light" for="switch_email_auto">{{ $lang->data['enable_automated_emails'] ?? 'Enable Automated Status Emails' }}</label>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="mb-20">
                        <label for="mail_host" class="form-label fw-semibold text-primary-light text-sm mb-8">
                            {{ $lang->data['mail_host'] ?? 'Mail Host' }} <span
                                class="text-danger">*</span>
                        </label>
                        <input type="text" required autofocus class="form-control radius-8" id="mail_host"
                            wire:model="mail_host">
                            @error('mail_host') <span class="text-danger">{{$message}}</span> @enderror
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="mb-20">
                        <label for="mail_port" class="form-label fw-semibold text-primary-light text-sm mb-8">
                            {{ $lang->data['mail_port'] ?? 'Mail Port' }} <span
                                class="text-danger">*</span>
                        </label>
                        <input type="text" required autofocus class="form-control radius-8" id="mail_port"
                            wire:model="mail_port">
                            @error('mail_port') <span class="text-danger">{{$message}}</span> @enderror
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="mb-20">
                        <label for="mail_username" class="form-label fw-semibold text-primary-light text-sm mb-8">
                            {{ $lang->data['mail_username'] ?? 'Mail Username' }} <span
                                class="text-danger">*</span>
                        </label>
                        <input type="text" required autofocus class="form-control radius-8" id="mail_username"
                            wire:model="mail_username">
                            @error('mail_username') <span class="text-danger">{{$message}}</span> @enderror
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="mb-20">
                        <label for="mail_password" class="form-label fw-semibold text-primary-light text-sm mb-8">
                            {{ $lang->data['mail_password'] ?? 'Mail Password' }} <span
                                class="text-danger">*</span>
                        </label>
                        <input type="text" required autofocus class="form-control radius-8" id="mail_password"
                            wire:model="mail_password">
                            @error('mail_password') <span class="text-danger">{{$message}}</span> @enderror
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="mb-20">
                        <label for="mail_from_address" class="form-label fw-semibold text-primary-light text-sm mb-8">
                            {{ $lang->data['mail_from_address'] ?? 'Mail From Address' }} <span class="text-danger">*</span>
                        </label>
                        <input type="number" required class="form-control radius-8" id="mail_from_address"
                            placeholder=""
                            wire:model="mail_from_address">
                            @error('mail_from_address') <span class="text-danger">{{$message}}</span> @enderror
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="mb-20">
                        <label for="mail_from_name" class="form-label fw-semibold text-primary-light text-sm mb-8">
                            {{ $lang->data['mail_from_name'] ?? 'Mail From Name' }} <span class="text-danger">*</span>
                        </label>
                        <input type="number" required class="form-control radius-8" id="mail_from_name"
                            placeholder=""
                            wire:model="mail_from_name">
                            @error('mail_from_name') <span class="text-danger">{{$message}}</span> @enderror
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-switch switch-primary d-flex align-items-center gap-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="switch111" wire:model="enable_forget" @checked($enable_forget) >
                        <label class="form-check-label line-height-1 fw-medium text-secondary-light" for="switch111">{{ $lang->data['enable_password_recovery'] ?? 'Enable Password Recovery (Forget Password Section)' }}</label>
                    </div>  
                </div>
                
                <div class="col-12 mt-4">
                    <hr>
                    <h6 class="mb-3">Order Status Email Templates</h6>
                    <p class="text-sm text-secondary-light mb-4">
                        Leave a template blank to prevent sending an email for that specific status.<br>
                        <strong>Available Shortcodes:</strong> <code>{customer_name}</code>, <code>{order_number}</code>, <code>{total_amount}</code>, <code>{delivery_date}</code>
                    </p>
                    <div class="row">
                        <div class="col-sm-6 mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Pending Template</label>
                            <textarea class="form-control radius-8" rows="4" wire:model="email_template_pending" placeholder="Hi {customer_name}, your order {order_number} is pending."></textarea>
                        </div>
                        <div class="col-sm-6 mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Processing Template</label>
                            <textarea class="form-control radius-8" rows="4" wire:model="email_template_processing" placeholder="Great news! We have started cleaning {order_number}."></textarea>
                        </div>
                        <div class="col-sm-6 mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Ready to Deliver Template</label>
                            <textarea class="form-control radius-8" rows="4" wire:model="email_template_ready" placeholder="Hi {customer_name}, your order is ready for pickup!"></textarea>
                        </div>
                        <div class="col-sm-6 mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Delivered Template</label>
                            <textarea class="form-control radius-8" rows="4" wire:model="email_template_delivered" placeholder="Thanks for choosing us. Order {order_number} is complete."></textarea>
                        </div>
                        <div class="col-sm-6 mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Returned Template</label>
                            <textarea class="form-control radius-8" rows="4" wire:model="email_template_returned" placeholder="Order {order_number} has been returned."></textarea>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-center gap-3 mt-24">
                    <button type="submit"
                        class="btn btn-primary border border-primary-600 text-md px-24 py-12 radius-8" wire:click.prevent="save()">{{ $lang->data['save'] ?? 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>