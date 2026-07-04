<?php

namespace App\Livewire\Customers;

use Livewire\Component;
use App\Models\Customer;
use App\Models\Translation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;

class CustomerLedger extends Component
{
    public $data,$customer,$lang;

    #[Title('Customer Ledger')]
    public function render()
    {
        return view('livewire.customers.customer-ledger');
    }

    public function mount($id)
    {
        if(!\Illuminate\Support\Facades\Gate::allows('customer_view')){
            abort(404);
        }
        if(session()->has('selected_language'))
        { /* if session has selected laugage*/
            $this->lang = Translation::where('id',session()->get('selected_language'))->first();
        }
        else{
            $this->lang = Translation::where('default',1)->first();
        }
        $this->data = collect();
        $this->customer = Customer::find($id);
        if(!$this->customer)
        {
            return abort(404);
        }
        $this->data = array_map(function($row) { return (array) $row; }, DB::select("
            SELECT order_date as date, 'debit' as type, order_number, total, 0 as received_amount, id, NULL as order_id
            FROM orders WHERE customer_id = ?
            UNION ALL
            SELECT created_at as date, 'credit' as type, NULL as order_number, 0 as total, received_amount, NULL as id, order_id
            FROM payments WHERE customer_id = ?
            ORDER BY date ASC
        ", [$this->customer->id, $this->customer->id]));
    }
}
