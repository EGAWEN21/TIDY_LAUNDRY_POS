<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Customer;
use App\Models\MasterSettings;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class SmsService
{
    public function send(string $phoneNumber, string $message): bool|string
    {
        $settings = new MasterSettings();
        $site = $settings->siteData();
        
        if (!isset($site['sms_gateway']) || $site['sms_gateway'] != 1) {
            return true; // SMS disabled
        }
        if (isset($site['sms_provider']) && $site['sms_provider'] == 'twilio') {
            return $this->sendViaTwilio($phoneNumber, $message, $site);
        }
        // Extend here later for other providers (e.g. Vonage, SMSGlobal)
        return "Unsupported SMS Provider configured.";
    }

    private function sendViaTwilio(string $phoneNumber, string $message, array $site)
    {
        $account_sid = (($site['sms_account_sid']) && ($site['sms_account_sid'] != "")) ? $site['sms_account_sid'] : '';
        $auth_token = (($site['sms_auth_token']) && ($site['sms_auth_token'] != "")) ? $site['sms_auth_token'] : '';
        $twilio_number = (($site['sms_twilio_number']) && ($site['sms_twilio_number'] != "")) ? $site['sms_twilio_number'] : '';

        try {
            $client = new Client($account_sid, $auth_token);
            $phoneInt = (int)$phoneNumber;

            $dailyLimit = isset($site['sms_global_daily_limit']) ? max((int)$site['sms_global_daily_limit'], 100) : 100;
            if (RateLimiter::tooManyAttempts('global-sms-limit', $dailyLimit)) {
                Log::warning('Global SMS limit reached. Blocking outgoing SMS to ' . $phoneInt);
                return 'Global SMS limit reached';
            }

            $client->messages->create(
                getCountryCode() . $phoneInt,
                ['from' => $twilio_number, 'body' => $message]
            );
            
            RateLimiter::hit('global-sms-limit', 86400);
            return true;
        } catch (\Exception $e) {
            $messageerror = $e->getMessage();
            if ($e->getCode() == 21211) {
                $messageerror = 'Could not send SMS,Because the phone number is invalid';
            }
            return $messageerror;
        }
    }

    public function sendOrderCreateSMS($order, $to)
    {
        if (isSMSEnabled() == true) {
            $messageerror = null;
            try {
                $myorder = Order::find($order);
                if (smsOrderDeliveredOnly() && smsOrderReadyToDeliverOnly()) {
                    return;
                }
                if (smsOrderDeliveredOnly()) {
                    return;
                }
                if (smsOrderReadyToDeliverOnly()) {
                    return;
                }

                $customer = Customer::find($to);
                if ($customer) {
                    $phoneInt = (string)$customer->phone;
                    $message = getFormatedTextSMS($order, 1);

                    $result = $this->send($phoneInt, $message);
                    if ($result !== true) {
                        return $result;
                    }
                }
            } catch (\Exception $e) {
                $messageerror = $e->getMessage();
            }
            return $messageerror;
        }
    }

    public function sendOrderStatusChangeSMS($order, $to_status)
    {
        if (isSMSEnabled() == true) {
            $messageerror = null;
            try {
                $myorder = Order::find($order);
                if (smsOrderDeliveredOnly() && smsOrderReadyToDeliverOnly()) {
                    if ($myorder->status != 3 && $myorder->status != 2) {
                        return;
                    }
                }
                if (smsOrderDeliveredOnly() && (!smsOrderReadyToDeliverOnly())) {
                    if (smsOrderDeliveredOnly() && $myorder->status != 3) {
                        return;
                    }
                }
                if ((!smsOrderDeliveredOnly()) && (smsOrderReadyToDeliverOnly())) {
                    if (smsOrderReadyToDeliverOnly() && $myorder->status != 2) {
                        return;
                    }
                }
                $customer = Customer::find($myorder->customer_id);
                if ($customer) {
                    if ($to_status == 2) {
                        $message = getFormatedTextSMS($order, 3);
                    } else {
                        $message = getFormatedTextSMS($order, 2);
                    }
                    $phoneInt = (string)$customer->phone;
                    
                    $result = $this->send($phoneInt, $message);
                    if ($result !== true) {
                        return $result;
                    }
                }
            } catch (\Exception $e) {
                $messageerror = $e->getMessage();
            }
            return $messageerror;
        }
    }
}
