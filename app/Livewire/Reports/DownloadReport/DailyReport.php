<?php

namespace App\Livewire\Reports\DownloadReport;

use Livewire\Component;
use Livewire\Attributes\Layout;

class DailyReport extends Component
{
    public $today;
    public $new_order;
    public $delivered_orders;
    public $total_payment;
    public $total_expense;
    public $total_sales;
    public $lang;
    /* render the page */
    #[Layout('components.layouts.print-layout')]
    public function render()
    {
        return view('livewire.reports.download-report.daily-report');
    }
}
