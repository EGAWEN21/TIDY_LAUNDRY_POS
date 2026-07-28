<?php

namespace App\Livewire\Orders\Traits;

use App\Models\Service;
use App\Models\ServiceDetail;
use App\Models\ServiceType;

trait ManagesCart
{
    public function editItem($row){
        $this->add($this->inputi);
        $service = Service::whereId($row->service_id)->first();

        if (!$service) return;

        // Support composite items (service_type_ids) and legacy items (service_name lookup)
        $typeIds = $row->service_type_ids; // Already cast to array by model

        if (!empty($typeIds) && is_array($typeIds)) {
            // COMPOSITE: Use stored IDs directly
            $this->selservices[$this->inputi]['service'] = $service->id;
            $this->selservices[$this->inputi]['service_types'] = $typeIds;
        } else {
            // LEGACY: Reverse-lookup by service_type_name
            $serviceType = ServiceType::where('service_type_name', $row->service_name)->first();
            $this->selservices[$this->inputi]['service'] = $service->id;
            $this->selservices[$this->inputi]['service_types'] = $serviceType ? [$serviceType->id] : [];
        }

        if ($this->order->tax_type == 2) {
            $this->selling_price[$this->inputi] = $row->service_price;
            $itemtotallocal = $row->service_price * (100 / (100 + ($this->tax_percent ?? 0)));
            $this->prices[$this->inputi] = number_format($itemtotallocal, 2);
        } else {
            $this->prices[$this->inputi] = $row->service_price;
            $this->selling_price[$this->inputi] = $row->service_price;
        }

        $this->colors[$this->inputi] = $row->color_code;
        $this->prices[$this->inputi] = $row->service_price;
        $this->quantity[$this->inputi] = $row->service_quantity;
        $this->calculateTotal();
    }

    public function changeColor($id)
    {
        $this->colors[$id] = $this->colors[$id];
    }

    /* select service */
    public function selectService($id)
    {
        $this->selected_type = [];
        $this->service = Service::where('id', $id)->first();
        $this->service_types = collect();
        if ($this->service) {
            $servicedetails = ServiceDetail::where('service_id', $id)->get();
            $serviceTypeIds = $servicedetails->pluck('service_type_id')->toArray();
            
            $serviceTypes = ServiceType::whereIn('id', $serviceTypeIds)
                ->orderBy('position', 'ASC')
                ->orderBy('id', 'ASC')
                ->get();

            foreach ($serviceTypes as $servicetype) {
                $detail = $servicedetails->where('service_type_id', $servicetype->id)->first();
                if ($detail) {
                    $servicetypeArray = $servicetype->toArray();
                    $servicetypeArray['price'] = getFormattedCurrency($detail->service_price);
                    $this->service_types->push($servicetypeArray);
                }
            }
        }
        if ($this->service_types) {
            if (count($this->service_types) > 0) {
                $first = $this->service_types->first();
                if ($first) {
                    $this->selected_type [$first['id']] = true;
                }
            }
        }
        $this->calculateTotal();
    }
    /* select services*/
    public function addItem()
    {
        if ($this->service) {
            // Collect all checked service type IDs
            $checkedTypeIds = [];
            foreach ($this->selected_type as $typeId => $isChecked) {
                if ($isChecked === true) {
                    $checkedTypeIds[] = $typeId;
                }
            }

            if (count($checkedTypeIds) === 0) {
                $this->addError('service_error', 'Select a service type');
                return 0;
            }

            $tax_type = getTaxType();

            // Calculate composite price by summing ALL selected service type prices
            $compositePrice = 0;
            $compositeTypeIds = [];

            foreach ($checkedTypeIds as $typeId) {
                $servicedetail = ServiceDetail::where('service_id', $this->service->id)
                    ->where('service_type_id', $typeId)
                    ->first();

                if ($servicedetail) {
                    $compositePrice += $servicedetail->service_price;
                    $compositeTypeIds[] = $typeId;
                }
            }

            // Add ONE composite row
            $this->add($this->inputi);
            $this->selservices[$this->inputi]['service'] = $this->service->id;
            $this->selservices[$this->inputi]['service_types'] = $compositeTypeIds;

            if ($tax_type == 2) {
                $this->selling_price[$this->inputi] = $compositePrice;
                $itemtotallocal = $compositePrice * (100 / (100 + ($this->tax_percent ?? 0)));
                $this->prices[$this->inputi] = number_format($itemtotallocal, 2);
            } else {
                $this->prices[$this->inputi] = $compositePrice;
                $this->selling_price[$this->inputi] = $compositePrice;
            }

            $this->service_types = collect();
            $this->dispatch('closemodal');
            $this->calculateTotal();
        }
    }
    /* add the item to array */
    public function add($i)
    {
        $this->inputi = $i + 1;
        $this->inputs[$this->inputi] = 1;
        $this->prices[$this->inputi] = 100;
        $this->service_types[$this->inputi] = '';
        $this->quantity[$this->inputi]  = 1;
        $this->colors[$this->inputi]  = '#000000';
    }
    /* increase the count */
    public function increase($key)
    {
        /* if quantity of key is exist */
        if (isset($this->quantity[$key])) {
            $this->quantity[$key]++;
            $this->calculateTotal();
        }
    }

    public function priceChange($key)
    {
        $this->calculateTotal();
    }
    /* decrease the count */
    public function decrease($key)
    {
        /* is quantity of key is exist */
        if (isset($this->quantity[$key])) {
            if ($this->quantity[$key] > 1) {
                /* if quantity of key is >1 */
                $this->quantity[$key]--;
            } else {
                /* unset the details if quantity of key is 1 */
                unset($this->quantity[$key]);
                unset($this->prices[$key]);
                unset($this->service_types[$key]);
                unset($this->selservices[$key]);
                unset($this->selling_price[$key]);
            }
            $this->calculateTotal();
        }
    }
    public function removeItem($key)
    {
        unset($this->quantity[$key]);
        unset($this->prices[$key]);
        unset($this->service_types[$key]);
        unset($this->selservices[$key]);
        unset($this->selling_price[$key]);
        $this->calculateTotal();
    }

    public function duplicateItem($key)
    {
        if (isset($this->selservices[$key])) {
            $this->add($this->inputi);
            $newKey = $this->inputi;
            $this->selservices[$newKey] = $this->selservices[$key] ?? [];
            $this->prices[$newKey] = $this->prices[$key] ?? 0;
            $this->selling_price[$newKey] = $this->selling_price[$key] ?? 0;
            $this->colors[$newKey] = $this->colors[$key] ?? '#000000';
            $this->quantity[$newKey] = $this->quantity[$key] ?? 1;
            $this->service_types = collect();
            $this->calculateTotal();
        }
    }
}
