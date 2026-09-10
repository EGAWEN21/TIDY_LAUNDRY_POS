<?php

namespace App\Livewire\Reports\DownloadReport;

use Livewire\Component;
use Livewire\Attributes\Layout;

class TaxReport extends Component
{
    public $from_date;
    public $to_date;
    public $category = 1;

    /* render the page */
    #[Layout('components.layouts.print-layout')]
    public function render()
    {
        return view('livewire.reports.download-report.tax-report', [
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'category' => $this->category,
        ]);
    }

    public function mount($from_date = null, $to_date = null, $category = null)
    {
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->category = $category ?? 1;
    }
}
