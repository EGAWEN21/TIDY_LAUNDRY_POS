<?php
/**
 * Global Helper Functions for TidyPOS.
 *
 * This file is autoloaded via composer.json and provides globally available
 * utility functions for settings retrieval, SMS/email dispatch, tax calculation,
 * currency formatting, and order status mapping.
 *
 * @see composer.json autoload.files
 */

/* get expense category type */

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Twilio\Rest\Client;

if (!function_exists('getSessionTranslation')) {
    /**
     * Get the active Translation model based on session language or default.
     */
    function getSessionTranslation(): ?\App\Models\Translation
    {
        if (session()->has('selected_language')) {
            return \App\Models\Translation::where('id', session()->get('selected_language'))->first();
        }
        return \App\Models\Translation::where('default', 1)->first();
    }
}
if (!function_exists('siteSettings')) {
    function siteSettings()
    {
        return app('site.settings');
    }
}

if (!function_exists('sanitize_search')) {
    function sanitize_search($term)
    {
        return str_replace(['%', '_'], ['\%', '\_'], $term ?? '');
    }
}

function getExpenseCategoryType($type)
{
    $lang = getSessionTranslation();
    if ($lang) {
        switch ($type) {
            case 1:
                return $lang->data['asset'] ?? 'Asset';
            case 2:
                return  $lang->data['liability'] ?? 'Liability';
            default:
                return '';
        }
    }
    switch ($type) {
        case 1:
            return 'Asset';
        case 2:
            return 'Liability';
        default:
            return '';
    }
}
/* get payment mode */
function getpaymentMode($type)
{
    $lang = getSessionTranslation();
    if ($lang) {
        switch ($type) {
            case 1:
                return $lang->data['cash'] ?? 'CASH';
            case 2:
                return $lang->data['upi'] ?? 'UPI';
            case 3:
                return $lang->data['card'] ?? 'CARD';
            case 4:
                return $lang->data['cheque'] ?? 'CHEQUE';
            case 5:
                return $lang->data['bank_transfer'] ?? 'BANK TRANSFER';
            default:
                return '';
        }
    } else {
    switch ($type) {
        case 1:
            return 'CASH';
        case 2:
            return 'UPI';
        case 3:
            return 'CARD';
        case 4:
            return 'CHEQUE';
        case 5:
            return 'BANK TRANSFER';
        default:
            return '';
    }
}
}
/* get financial year */
function getFinancialYearId()
{
    $site = siteSettings();
    if (isset($site['default_financial_year'])) {
        $year_id = (($site['default_financial_year']) && ($site['default_financial_year'] != "")) ? $site['default_financial_year'] : '';
        return $year_id;
    }
    return null;
}

/* get financial year by date dynamically */
function resolveFinancialYearId($date)
{
    if (!$date) return getFinancialYearId();
    
    $dateObj = \Carbon\Carbon::parse($date)->toDateString();
    
    $financialYear = \App\Models\FinancialYear::where('starting_date', '<=', $dateObj)
        ->where('ending_date', '>=', $dateObj)
        ->first();
        
    if ($financialYear) {
        return $financialYear->id;
    }
    
    // Fallback if transaction date doesn't match any explicitly configured year
    return getFinancialYearId();
}
/* get Currency */
function getCurrency()
{
    $site = siteSettings();
    if (isset($site['default_currency'])) {
        $currency = (($site['default_currency']) && ($site['default_currency'] != "")) ? $site['default_currency'] : '$';
        return $currency;
    }
    return '$';
}
/* get Tax percentage */
if(!function_exists('getTaxPercentage'))
{
    function getTaxPercentage()
    {
    $site = siteSettings();
        if(isset($site['default_tax_percentage']))
        {
            $currency = (($site['default_tax_percentage']) && ($site['default_tax_percentage'] !=""))? $site['default_tax_percentage'] : 0;
            return $currency;
        }
        return 0;
    }
}



/* get order status */
function getOrderStatus($status, $preventlang = null)
{
    $lang = getSessionTranslation();
    if ($lang == null || $preventlang) {
        switch ($status) {
            case -1:
                return 'All Orders';
            case 0:
                return 'Pending';
            case 1:
                return 'Processing';
            case 2:
                return 'Ready To Deliver';
            case 3:
                return 'Delivered';
            case 4:
                return 'Returned';
        }
    } else {
        switch ($status) {
            case -1:
                return 'All Orders';
            case 0:
                return $lang->data['pending'] ?? 'Pending';
            case 1:
                return $lang->data['processing'] ?? 'Processing';
            case 2:
                return $lang->data['ready_to_deliver'] ?? 'Ready To Deliver';
            case 3:
                return $lang->data['delivered'] ?? 'Delivered';
            case 4:
                return $lang->data['returned'] ?? 'Returned';
        }
    }
}
/* get order status wit color */
function getOrderStatusWithColor($status)
{
    switch ($status) {
        case 0:
            return 'today-task-pending';
        case 1:
            return 'today-task-processing';
        case 2:
            return 'today-task-ready';
        case 3:
            return 'today-task-delivered';
        case 4:
            return 'today-task-returned';
    }
}
/* get order status with color for change status screen */
function getOrderStatusWithColorKan($status)
{
    switch ($status) {
        case 0:
            return 'scrum-task-pending';
        case 1:
            return 'scrum-task-processing';
        case 2:
            return 'scrum-task-ready';
    }
}
/* get priner type */
function getPrinterType()
{
    $site = siteSettings();
    if (isset($site['default_printer'])) {
        $printerType = (($site['default_printer']) && ($site['default_printer'] != "")) ? $site['default_printer'] : 1;
        return $printerType;
    }
    return 1;
}

/* get favicon */
function getFavIcon()
{
    $site = siteSettings();
    if (isset($site['default_favicon']) && file_exists(public_path($site['default_favicon']))) {
        $favicon = (($site['default_favicon']) && ($site['default_favicon'] != "")) ? $site['default_favicon'] : 'assets/img/favicon.png';
        return $favicon;
    }
    return asset('assets/img/logo-ct.png');
}


/* get getAppliation Name */
function getApplicationName()
{
    $site = siteSettings();
    if (isset($site['default_application_name'])) {
        $favicon = (($site['default_application_name']) && ($site['default_application_name'] != "")) ? $site['default_application_name'] : 'Tidy LMS';
        return $favicon;
    }
    return 'Tidy LMS';
}


/* get site logo */
function getSiteLogo()
{
    $site = siteSettings();
    if (isset($site['default_logo']) && file_exists(public_path($site['default_logo']))) {
        $favicon = (($site['default_logo']) && ($site['default_logo'] != "")) ? $site['default_logo'] : 'assets/img/logo-ct.png';
        return $favicon;
    }
    return asset('assets/img/logo-ct.png');
}

//Checks if Selected language is RTL
function isRTL()
{
    if (session()->has('selected_language')) {
        $lang = \App\Models\Translation::where('id', session()->get('selected_language'))->first();
        if ($lang) {
            if ($lang->is_rtl) {
                return true;
            }
        }
    }
    return false;
}

function getCountryCode()
{
    $site = siteSettings();
    if (isset($site['country_code']) && $site['country_code'] != '') {
        return '+'.ltrim($site['country_code'], '+');
    }
    return '+91';
}

function smsOrderDeliveredOnly()
{
    $site = siteSettings();
    if (isset($site['sms_delivered_only']) && $site['sms_delivered_only'] == 1) {
        return true;
    }
    return false;
}

function smsOrderReadyToDeliverOnly()
{
    $site = siteSettings();
    if (isset($site['sms_ready_to_deliver_only']) && $site['sms_ready_to_deliver_only'] == 1) {
        return true;
    }
    return false;
}


function isSMSEnabled()
{
    $site = siteSettings();
    if (isset($site['sms_enabled']) && ($site['sms_enabled'] == 1)) {
        return true;
    }
    return false;
}

function sendOrderCreateSMS($order, $to)
{
    $smsService = new \App\Services\SmsService();
    return $smsService->sendOrderCreateSMS($order, $to);
}

function sendOrderStatusChangeSMS($order, $to_status)
{
    $smsService = new \App\Services\SmsService();
    return $smsService->sendOrderStatusChangeSMS($order, $to_status);
}

//get formatted currency
function getFormattedCurrency($value)
{
    $site = siteSettings();
    $symbol = $site['default_currency'] ?? '$';
    $alignment = $site['default_currency_alignment'] ?? 1;
    $value = number_format($value, 2);
    if ($alignment == 1) {
        return $symbol . ' ' . $value;
    }
    return $value . ' ' . $symbol;
}


function getFormatedTextSMS($order, $type)
{
    $myorder = Order::find($order);
    $site = siteSettings();
    $string = null;
    if ($type == 1) {
        if (isset($site['sms_createorder']) && $site['sms_createorder'] != '') {
            $string = $site['sms_createorder'] ?? 'Hi <name> An Order #<order_number> was created and will be delivered on <delivery_date> Your Order Total is <total>.';
        } else {
            $string = 'Hi <name> An Order #<order_number> was created and will be delivered on <delivery_date> Your Order Total is <total>.';
        }
    } else {
        if (isset($site['sms_statuschange']) && $site['sms_statuschange'] != '') {
            $string = $site['sms_statuschange'] ?? 'Hi <name> Your Order #<order_number> status has been changed to <status> on <current_time>';
        } else {
            $string =  'Hi <name> Your Order #<order_number> status has been changed to <status> on <current_time>';
        }
    }

    $replacer = [
        '<name>' => 'Customer Name',
        '<order_date>' => 'Order Date',
        '<delivery_date>' => 'Delivery Date',
        '<no_of_products>' => 'No Of Products',
        '<total>' => 'Total',
        '<discount>' => 'Discount',
        '<paid>' => 'Paid Amount',
        '<status>'  => 'Status',
        '<order_number>'    => 'Order Number',
        '<current_time>'    => 'Current Time'
    ];
    $count = \App\Models\OrderDetail::where('order_id', $order)->count();
    $paid = \App\Models\Payment::where('order_id', $order)->sum('received_amount');
    $replacement = [
        $myorder->customer_name,
        \Carbon\Carbon::parse($myorder->order_date)->format('d/m/Y'),
        \Carbon\Carbon::parse($myorder->delivery_date)->format('d/m/Y'),
        $count,
        getCurrency() . number_format($myorder->total, 2),
        getCurrency() . number_format($myorder->discount, 2),
        getCurrency() . number_format($paid, 2),
        getOrderStatus($myorder->status),
        $myorder->order_number,
        \Carbon\Carbon::now()->format('d/m/Y h:i A')
    ];
    return str_replace(array_keys($replacer), array_values($replacement), $string);
}

if(!function_exists('getTaxType'))
{
    function getTaxType()
    {
    $site = siteSettings();
        if(isset($site['default_tax_mode']))
        {
            $tax_type = (($site['default_tax_mode']) && ($site['default_tax_mode'] !=""))? $site['default_tax_mode'] : 1;
            return $tax_type;
        }
        return 1;
    }
}

if(!function_exists('getBypassLimit'))
{
    function getBypassLimit()
    {
    $site = siteSettings();
        if(isset($site['bypass_approval_limit']))
        {
            $limit = (($site['bypass_approval_limit']) && ($site['bypass_approval_limit'] !=""))? $site['bypass_approval_limit'] : 0;
            return $limit;
        }
        return 0;
    }
}

if(!function_exists('sendOrderStatusChangeEmail'))
{
    function sendOrderStatusChangeEmail($order_id, $status)
    {
    $site = siteSettings();
        
        if (!isset($site['enable_automated_emails']) || $site['enable_automated_emails'] != 1) {
            return;
        }

        $template_key = '';
        switch ($status) {
            case 0: $template_key = 'email_template_pending'; break;
            case 1: $template_key = 'email_template_processing'; break;
            case 2: $template_key = 'email_template_ready'; break;
            case 3: $template_key = 'email_template_delivered'; break;
            case 4: $template_key = 'email_template_returned'; break;
        }

        if (empty($template_key) || empty($site[$template_key])) {
            return;
        }

        $myorder = Order::find($order_id);
        if (!$myorder) return;

        $customer = Customer::find($myorder->customer_id);
        if (!$customer || empty($customer->email)) return;

        $message = $site[$template_key];
        
        $replacements = [
            '{customer_name}' => $myorder->customer_name,
            '{order_number}' => $myorder->order_number,
            '{total_amount}' => getFormattedCurrency($myorder->total),
            '{delivery_date}' => \Carbon\Carbon::parse($myorder->delivery_date)->format('d/m/Y')
        ];

        $message = str_replace(array_keys($replacements), array_values($replacements), $message);

        try {
            \Illuminate\Support\Facades\Mail::raw($message, function ($mail) use ($customer, $myorder, $status) {
                $statusText = getOrderStatus($status, true);
                $mail->to($customer->email)
                     ->subject("Order Update: {$myorder->order_number} is now {$statusText}");
            });
        } catch (\Exception $e) {
            \Log::error('Automated Email Failed: ' . $e->getMessage());
        }
    }
}