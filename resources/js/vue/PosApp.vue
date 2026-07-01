<template>
<div v-if="fatalError" class="tw-absolute tw-inset-0 tw-z-50 tw-bg-red-50 tw-text-red-900 tw-p-8 tw-overflow-y-auto">
    <h1 class="tw-text-2xl tw-font-bold tw-mb-4">Fatal UI Error</h1>
    <pre class="tw-whitespace-pre-wrap tw-text-sm">{{ fatalError }}</pre>
    <button @click="fatalError = null" class="tw-mt-4 tw-px-4 tw-py-2 tw-bg-red-600 tw-text-white tw-rounded">Dismiss</button>
</div>
<div  class="tw-w-full">
    <div class="tw-w-full tw-bg-white tw-flex tw-justify-between tw-items-center ">
        <div class="tw-flex tw-gap-2 tw-px-3 tw-py-2">
            <a href="{{ route('orders') }}" class="no-underline">
                <button
                    class="bg-primary-600 tw-text-white tw-text-xs radius-8 px-20 tw-py-2 d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                        stroke="currentColor" class="tw-size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    <span>Back</span>
                </button>
            </a>
            
                <button
                    class="tw-px-2 tw-py-1.5 bg-primary-600 tw-w-fit tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md"
                    @click="shown = !shown">
                    
                        <div class="tw-flex  tw-items-center tw-gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="tw-size-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                            </svg>
                            <span class="text-sm ">Cart</span>
                        </div>
                    
                    
                        <div class="tw-flex tw-items-center tw-gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="tw-size-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                            </svg>

                            <span class="text-sm ">Products</span>
                        </div>
                    
                </button>
            
            <div class="tw-ml-4 tw-flex tw-items-center">
                <span v-if="pos.isOnline" class="tw-bg-green-100 tw-text-green-800 tw-text-xs tw-font-bold tw-px-3 tw-py-1.5 tw-rounded tw-shadow-sm tw-flex tw-items-center tw-gap-1">
                    <span class="tw-w-2 tw-h-2 tw-bg-green-500 tw-rounded-full"></span> Online
                </span>
                <span v-else class="tw-bg-red-100 tw-text-red-800 tw-text-xs tw-font-bold tw-px-3 tw-py-1.5 tw-rounded tw-shadow-sm tw-flex tw-items-center tw-gap-1">
                    <span class="tw-w-2 tw-h-2 tw-bg-red-500 tw-rounded-full"></span> Offline Mode
                </span>
            </div>
        </div>
        <button type="button" data-theme-toggle
            class="w-40-px h-40-px bg-neutral-200 rounded-circle tw-hidden justify-content-center align-items-center"></button>
    </div>

    <div class="tw-w-[100%] tw-h-full tw-flex lg:tw-flex-row tw-flex-col  tw-relative tw-mt-0.5">
        <div class="lg:tw-w-1/2 tw-w-full tw-flex-col tw-h-[calc(100vh-4.0rem)]  tw-p-2 tw-bg-white p-16">
            <div class="tw-flex tw-flex-col">
                <div class="icon-field has-validation">
                    <span class="icon tw-translate-y-[2px]">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-search" viewBox="0 0 16 16">
                            <path
                                d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
                        </svg>
                    </span>
                    <input type="text" class="form-control" v-model="search_query"
                        placeholder="Search Here" required="">
                </div>
                <div
                    class="tw-w-full tw-h-[calc(100vh-9rem)] tw-overflow-y-scroll custom-scroll tw-mt-2 tw-flex tw-p-0.5">
                    <div class="tw-grid tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-2 tw-h-fit tw-w-full">
                        <template v-for="item in filteredServices" :key="item.id">
                            <a type="button" class=" hover:tw-translate-y-1" data-bs-toggle="modal"
                                data-bs-target="#servicetype" @click="selectService(item)">
                                <div class="card bg-neutral-100">
                                    <div
                                        class="card-body tw-flex tw-items-center tw-justify-center tw-flex-col tw-rounded-md  tw-overflow-clip tw-ring-1 tw-ring-neutral-200">
                                        <img :src="'/assets/img/service-icons/' + item.icon"
                                            class="tw-h-24 tw-w-24 tw-object-center tw-rounded-md tw-py-2">
                                        <div
                                            class="tw-px-2 tw-py-1.5  tw-w-full tw-flex tw-justify-center tw-items-center">
                                            <div class="tw-text-sm tw-text-center tw-truncate tw-font-bold tw-w-[90%] ">
                                                {{ item.service_name }}</div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        <div class=" tw-h-[calc(100vh-4rem)]  tw-bg-white p-16"
            :class="shown && detached ? 'tw-absolute tw-inset-0 tw-w-full' :
                ' tw-hidden lg:tw-block lg:tw-w-1/2 tw-w-full tw-shrink-0 '">
            <div class="tw-flex tw-items-center tw-gap-8 tw-w-full">
                <div class="tw-flex tw-min-w-fit tw-shrink tw-flex-col" >
                    <div class="tw-text-sm">Order : <span
                            class="tw-font-bold">#{{ pos.cartOrderId }}</span></div>
                    <div class="tw-flex tw-items-center tw-gap-2">
                        <div class="tw-text-sm tw-relative">
                            Date : <span
                                class="tw-font-bold">{{ todayDate }}</span>
                            <input type="date" v-model="date" name=""
                                class="tw-opacity-0 tw-absolute tw-pointer-events-none" >
                        </div>

                        <button @click="$refs.date.showPicker()"
                            class="tw-px-2 tw-py-1 bg-primary-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor"
                                class="bi bi-calendar3" viewBox="0 0 16 16">
                                <path
                                    d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2M1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857z" />
                                <path
                                    d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2" />
                            </svg>
                        </button>
                    </div>

                    <div class="tw-flex tw-items-center tw-gap-2">
                        <div class="tw-text-sm tw-relative">
                            Delivery Date : <span
                                class="tw-font-bold">{{ pos.cartDeliveryDate }}</span>
                            <input type="date" v-model="delivery_date" name=""
                                class="tw-opacity-0 tw-absolute tw-pointer-events-none" >
                        </div>

                        <button @click="$refs.delivery_date.showPicker()"
                            class="tw-px-2 tw-py-1 bg-primary-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                fill="currentColor" class="bi bi-calendar3" viewBox="0 0 16 16">
                                <path
                                    d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2M1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857z" />
                                <path
                                    d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2" />
                            </svg>
                        </button>
                    </div>

                </div>
                <div class="tw-flex tw-items-center tw-gap-2 tw-w-full tw-shrink">
                    <div class="tw-flex tw-flex-col tw-gap-2 tw-w-full">
                        <div v-if="pos.cartCustomer" class="tw-flex tw-items-center tw-justify-between tw-w-full tw-border tw-rounded-md tw-px-3 tw-py-2 tw-bg-green-50 tw-border-green-300">
                            <div class="tw-flex tw-flex-col">
                                <span class="tw-font-bold tw-text-sm tw-text-green-800">{{ pos.cartCustomer.name }}</span>
                                <span class="tw-text-xs tw-text-green-600">{{ pos.cartCustomer.phone }}</span>
                            </div>
                            <button @click="pos.cartCustomer = null" class="tw-text-red-500 hover:tw-text-red-700 tw-font-bold tw-px-2">&times;</button>
                        </div>
                        <div class="icon-field tw-relative tw-w-full tw-items-center">
                            <span class="icon -tw-translate-y-[2px]">
                                <iconify-icon icon="f7:person"></iconify-icon>
                            </span>
                            <input type="text"
                                class="form-control"
                                :placeholder="pos.cartCustomer ? 'Change Customer...' : 'Select A Customer'"
                                @focus="showCustomerDropdown = true"
                                @blur="hideCustomerDropdown"
                                v-model="customer_query">
                            <div v-show="showCustomerDropdown && filteredCustomers.length > 0"
                                class="tw-absolute tw-top-[100%] tw-left-0 tw-w-full tw-z-20 tw-shadow-md tw-bg-white tw-rounded-lg ">
                                <ul>
                                    <li v-for="row in filteredCustomers" :key="row.id"
                                        class="dropdown-item px-16 py-8 rounded text-secondary-light bg-hover-neutral-200 text-hover-neutral-900 tw-cursor-pointer"
                                        @mousedown.prevent="selectCustomer(row)">{{ row.name }} -
                                        {{ row.phone }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                        <button type="button" data-bs-toggle="modal" data-bs-target="#addcustomer"
                            class="tw-px-4 tw-py-3 bg-primary-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-person-fill-add" viewBox="0 0 16 16">
                                <path
                                    d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0m-2-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0" />
                                <path
                                    d="M2 13c0 1 1 1 1 1h5.256A4.5 4.5 0 0 1 8 12.5a4.5 4.5 0 0 1 1.544-3.393Q8.844 9.002 8 9c-5 0-6 3-6 4" />
                            </svg>
                        </button>
                    
                </div>
            </div>
            <div
                class="tw-w-full   tw-flex tw-flex-col tw-mt-4 tw-rounded-lg tw-overflow-clip tw-border  tw-border-red-500  tw-border-neutral-200 dark:tw-border-[#1b2431]  tw-border-solid">
                <div class="tw-flex tw-flex-col lg:tw-w-full tw-overflow-x-auto">
                    <div class="tw-flex tw-flex-col lg:tw-w-full tw-w-full tw-min-w-[60rem]">
                        <div class="tw-flex tw-flex-col  tw-overflow-x-auto tw-w-full tw-shrink-0">
                            <table class="tw-w-full tw-text-xs tw-shrink-0 tw-h-fit ">
                                <thead class="tw-bg-[#e9ecef] dark:tw-bg-[#1b2431]">
                                    <tr>
                                        <th class="tw-py-2 tw-px-2 tw-text-xs tw-w-[10rem] lg:tw-w-[10%] tw-text-left">
                                            Service</th>
                                        <th
                                            class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[15%] tw-text-center">
                                            Color</th>
                                        <th
                                            class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[15%] tw-text-center">
                                            Price</th>
                                        <th
                                            class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[15%] tw-text-center">
                                            Rate</th>
                                        <th
                                            class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[15%] tw-text-center">
                                            QTY</th>

                                        <th
                                            class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[10%] tw-text-center">
                                            Tax   ({{ pos.settings.tax_percentage }}%)</th>
                                        <th
                                            class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[10%] tw-text-center">
                                            Total</th>
                                        <th
                                            class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[5%] tw-text-center">
                                        </th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        <div
                            class="tw-flex tw-h-[calc(100dvh-23rem)] tw-overflow-y-auto tw-overflow-x-auto tw-w-full tw-shrink-0">
                            <table class="  tw-w-full tw-text-xs tw-shrink-0  tw-h-fit">
                                <tbody>
                                    
                                    <template v-for="(item, key) in pos.cart" :key="key">
                                        <tr
                                            class="tw-border-b tw-border-neutral-200 dark:tw-border-neutral-800/50 tw-border-solid">
                                            <td class="tw-py-2 tw-px-2 lg:tw-w-[10%] tw-w-[10rem] tw-text-left">
                                                <div class="tw-flex tw-flex-col ">
                                                    
                                                    <div class="tw-text-xs tw-font-semibold">
                                                        {{ item.service_name }}</div>
                                                    <div class="tw-text-xs tw-font-normal text-primary-600">
                                                        [{{ item.service_type_name }}]</div>
                                                </div>
                                            </td>
                                              <td class="tw-py-2 tw-px-1 lg:tw-w-[15%] tw-w-[10rem]  tw-text-center">
    <div class="tw-flex tw-items-center tw-justify-center tw-gap-1 tw-w-full">
        <!-- Left: Quick Swatches Dropdown Menu -->
        <div class="tw-relative">
            <button type="button" @click="openSwatchIndex = openSwatchIndex === key ? null : key"
                    class="tw-flex tw-items-center tw-justify-center tw-w-7 tw-h-6 tw-bg-gray-100 hover:tw-bg-gray-200 tw-border tw-border-gray-300 tw-rounded tw-transition-colors" 
                    title="Choose from quick swatches">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="tw-text-gray-600" viewBox="0 0 16 16">
                  <path d="M8 5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zm4 3a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM5.5 7a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm.5 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z"/>
                  <path d="M16 8c0 3.15-1.866 2.585-3.567 2.07C11.42 9.763 10.465 9.473 10 10c-.603.683-.475 1.819-.351 2.92C9.826 14.495 9.996 16 8 16a8 8 0 1 1 8-8zm-8 7c.611 0 .654-.171.655-.176.078-.146.124-.464.07-1.119-.014-.168-.037-.37-.061-.591-.052-.464-.112-1.005-.118-1.462-.01-.707.083-1.61.704-2.314.369-.417.845-.578 1.272-.618.404-.038.812.026 1.16.104.343.077.702.186 1.025.284 1.064.326 1.756.54 1.886.541.01 0 .013 0 .013-.001C15.823 13.064 16 11.233 16 8A7 7 0 1 0 8 15z"/>
                </svg>
            </button>

            <!-- Dropdown Palette -->
            <div v-show="openSwatchIndex === key" 
                 class="tw-absolute tw-z-50 tw-bg-white tw-shadow-lg tw-border tw-border-gray-200"
                 style="top: 32px; left: 0px; width: 170px; border-radius: 12px; padding: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
                <div class="tw-flex tw-justify-between tw-items-center" style="margin-bottom: 10px;">
                    <span class="tw-text-xs tw-font-semibold tw-uppercase tw-tracking-wider" style="font-size: 10px; color: #4b5563;">Quick Colors</span>
                    <button @click="openSwatchIndex = null" class="tw-text-gray-400 hover:tw-text-gray-600">&times;</button>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <div v-for="(swatch, sIndex) in quickSwatches" :key="sIndex" class="tw-relative tw-group" style="display: flex; align-items: center; justify-content: center; position: relative;">
                        <button type="button" 
                                @click="applySwatch(item, swatch)" 
                                class="tw-rounded-full tw-shadow-sm hover:tw-scale-110 tw-transition-transform"
                                style="cursor: pointer; padding: 0; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05);"
                                :style="`background-color: ${swatch}; width: 22px; height: 22px;`"
                                :title="swatch"></button>
                        <button type="button" 
                                @click.stop="removeSwatch(sIndex)" 
                                class="tw-hidden group-hover:tw-flex tw-items-center tw-justify-center"
                                style="position: absolute; top: -4px; right: -4px; z-index: 10; width: 14px; height: 14px; background-color: #ef4444; color: white; border-radius: 50%; font-size: 10px; line-height: 1; border: none; padding: 0; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.1);"
                                title="Remove">
                            &times;
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Center: Native Color Picker -->
        <input type="color"
            pattern="^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$"
            class="tw-w-10 tw-h-6 tw-p-0 tw-border-0"
            v-model="item.color_code">

        <!-- Right: + Button to save favorite -->
        <button type="button" @click="addSwatch(item.color_code)" 
                class="tw-flex tw-items-center tw-justify-center tw-w-6 tw-h-6 tw-bg-gray-100 hover:tw-bg-gray-200 tw-rounded tw-border tw-border-gray-300 tw-transition-colors tw-text-gray-700 tw-text-xs tw-font-bold" 
                title="Save current color to quick swatches">
            +
        </button>
    </div>
</td>
                                            <td class="tw-py-2 tw-px-1 lg:tw-w-[15%] tw-w-[10rem]  tw-text-center">
                                                <div
                                                    class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center">
                                                    <input type="number" step="0.01" name=""
                                                        v-model.number="item.price"
                                                        id=""
                                                        class="tw-ring-1 tw-px-1 tw-py-0.5 tw-rounded-md tw-w-[4.5rem]">
                                                </div>
                                            </td>
                                            <td class="tw-py-2 tw-px-1 lg:tw-w-[15%] tw-w-[10rem]  tw-text-center">
                                                <div
                                                    class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center">
                                                    {{ formatCurrency(item.price) }}
                                                </div>
                                            </td>
                                            <td class="tw-py-2 tw-px-1 lg:tw-w-[15%] tw-w-[10rem]  tw-text-center">
                                                <div
                                                    class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center">
                                                    <div
                                                        class="tw-flex tw-items-center tw-gap-2 tw-justify-center tw-text-sm">
                                                        <button @click="pos.decreaseQty(key)"
                                                            class="tw-px-2 tw-py-1 bg-primary-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                                height="16" fill="currentColor" class="bi bi-dash"
                                                                viewBox="0 0 16 16">
                                                                <path
                                                                    d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8" />
                                                            </svg>
                                                        </button>
                                                        {{ item.quantity }}
                                                        <button @click="pos.increaseQty(key)"
                                                            class="tw-px-2 tw-py-1 bg-primary-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                                height="16" fill="currentColor"
                                                                class="bi bi-plus-lg" viewBox="0 0 16 16">
                                                                <path fill-rule="evenodd"
                                                                    d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="tw-py-2 tw-px-1 lg:tw-w-[10%] tw-w-[10rem] tw-text-center">
                                                <div
                                                    class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center">
                                                    {{ formatCurrency(pos.calculateItemTax(item)) }}
                                                </div>
                                            </td>
                                            <td class="tw-py-2 tw-px-1 lg:tw-w-[10%] tw-w-[10rem] tw-text-center">
                                                <div
                                                    class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center">
                                                    {{ formatCurrency(pos.calculateItemTotal(item)) }}
                                                </div>
                                            </td>
                                            <td class="tw-py-2 tw-px-1 lg:tw-w-[10%] tw-w-[10rem] tw-text-center">
                                                <div class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center tw-gap-2">
                                                    <button @click.prevent="pos.duplicateItem(key)" title="Duplicate"
                                                        class="tw-px-2 tw-py-1 bg-info-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-justify-center tw-gap-1.5 tw-border-0 tw-shadow-md">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-copy" viewBox="0 0 16 16">
                                                            <path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z"/>
                                                        </svg>
                                                    </button>
                                                    <button @click="pos.removeItem(key)" title="Remove"
                                                        class="tw-px-2 tw-py-1 tw-bg-red-500 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                            height="16" fill="currentColor" class="bi bi-trash"
                                                            viewBox="0 0 16 16">
                                                            <path
                                                                d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z" />
                                                            <path
                                                                d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div
                    class="tw-mt-4 tw-flex tw-justify-between tw-text-sm  tw-p-2 tw-border-t dark:tw-border-[#1b2431] tw-border-dashed tw-border-neutral-200 tw-border-b-0 tw-border-l-0 tw-border-r-0">
                    <div class="tw-flex tw-flex-col tw-gap-2">
                        <div class="tw-flex tw-items-end tw-justify-end tw-gap-2">
                            <div class="tw-flex tw-items-center tw-gap-2">
                                Addon <button data-bs-toggle="modal"
                                    data-bs-target="#addons"
                                    class="tw-px-1 tw-py-1  tw-rounded-md  tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md  bg-primary-600 tw-text-white  tw-border tw-border-solid tw-bg-transparent tw-border-neutral-400 ">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                        fill="currentColor" class="bi bi-box-fill" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd"
                                            d="M15.528 2.973a.75.75 0 0 1 .472.696v8.662a.75.75 0 0 1-.472.696l-7.25 2.9a.75.75 0 0 1-.557 0l-7.25-2.9A.75.75 0 0 1 0 12.331V3.669a.75.75 0 0 1 .471-.696L7.443.184l.004-.001.274-.11a.75.75 0 0 1 .558 0l.274.11.004.001zm-1.374.527L8 5.962 1.846 3.5 1 3.839v.4l6.5 2.6v7.922l.5.2.5-.2V6.84l6.5-2.6v-.4l-.846-.339Z" />
                                    </svg>
                                </button>
                                :
                            </div>
                            <div class="tw-font-bold"> {{ formatCurrency(pos.cartAddonsTotal) }}</div>
                        </div>
                        <div class="tw-flex tw-items-center tw-gap-2">
                            <div class="">Sub Total :</div>
                            <div class="tw-font-bold">{{ formatCurrency(pos.cartSubTotal) }}</div>
                        </div>
                        <div class="tw-flex tw-items-center  tw-gap-2">
                            <div class="tw-flex tw-items-center tw-gap-2">
                                Notes : <button data-bs-toggle="modal"
                                    data-bs-target="#notesModal"
                                    class="tw-px-1 tw-py-1  tw-rounded-md  tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md  bg-primary-600 tw-text-white  tw-border tw-border-solid tw-bg-transparent tw-border-neutral-400 ">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                        fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                        <path
                                            d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z" />
                                        <path fill-rule="evenodd"
                                            d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-2">
                        <div class="tw-flex tw-items-end tw-justify-end tw-gap-2">
                            <div class="">Tax
                                ({{ pos.settings.tax_percentage }}%) :</div>
                            <div class="tw-font-bold"> {{ formatCurrency(pos.cartTax) }} </div>
                        </div>
                        <div class="tw-flex tw-items-end tw-justify-end tw-gap-2">
                            <div class="tw-flex tw-items-center tw-gap-2">
                                Discount
                                <button data-bs-toggle="modal" data-bs-target="#discount"
                                    class="tw-px-1 tw-py-1  tw-rounded-md  tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md  bg-primary-600 tw-text-white  tw-border tw-border-solid tw-bg-transparent tw-border-neutral-400 ">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                        fill="currentColor" class="bi bi-tag-fill" viewBox="0 0 16 16">
                                        <path
                                            d="M2 1a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7-7A1 1 0 0 0 6.586 1zm4 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0" />
                                    </svg>
                                </button>
                                :
                            </div>
                            <div class="tw-font-bold">{{ formatCurrency(pos.cartDiscount) }}</div>
                        </div>
                        <div class="tw-flex tw-items-center  tw-justify-end tw-gap-2">
                            <div class="">Gross Total :</div>
                            <div class="tw-font-extrabold"> {{ formatCurrency(pos.cartTotal) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tw-flex tw-items-center tw-gap-2 tw-mt-1 tw-p-2 tw-w-full tw-h-14">
                <button
                    class="tw-px-2 tw-justify-center tw-font-semibold tw-py-2 tw-h-full bg-success-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-w-full tw-border-0 tw-shadow-md "
                    data-bs-toggle="modal" data-bs-target="#payment">
                    <span>Payment</span>
                </button>
                <button
                    class="tw-px-2 tw-justify-center tw-font-semibold tw-py-2 tw-h-full bg-info-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-w-full tw-border-0 tw-shadow-md "
                    @click.prevent="save('cash')">
                    <span>Cash</span>
                </button>
                <button
                    class="tw-px-2 tw-justify-center tw-font-semibold tw-py-2 tw-h-full bg-primary-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-w-full tw-border-0 tw-shadow-md "
                    @click.prevent="save">
                    <span>Save & Print</span>
                </button>
                <button
                    class="tw-px-2 tw-py-2.5 tw-bg-red-500 tw-rounded-md tw-text-white tw-h-full tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md  "
                    @click.prevent="clearAll">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-arrow-repeat" viewBox="0 0 16 16">
                        <path
                            d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41m-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9" />
                        <path fill-rule="evenodd"
                            d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5 5 0 0 0 8 3M3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>


    <Teleport to="body">
    <div class="modal fade " id="servicetype" tabindex="-1" role="dialog" aria-labelledby="servicetype"
        aria-hidden="true"  key="modal-servicetype">
        <div class="modal-dialog modal-md modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content radius-16 bg-base">
                <div class="modal-header py-16 px-24 border border-top-0 border-start-0 border-end-0">
                    <h1 class="modal-title text-md" id="exampleModalLabel">
                        Select Service Type</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-24">
                    <div class="row">
                        <template v-for="item in availableServiceTypes" :key="item.id">
                            <div class="col-12 mb-20">
                                <div class="tw-flex tw-items-center tw-justify-between">
                                    <div class="d-flex align-items-center gap-10 fw-medium text-lg">
                                        <div class="form-check style-check d-flex align-items-center">
                                            <input class="form-check-input radius-4 border border-neutral-500"
                                                type="radio" :id="'test' + item.id" name="test"
                                                v-model="selected_type" :value="item.id">
                                        </div>
                                        <label :for="'test' + item.id"
                                            class="form-label fw-medium text-primary-light mb-0">{{ item.service_type_name }}</label>
                                    </div>
                                    <div class="">{{ item.price }}</div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="d-flex align-items-start justify-content-end gap-3 mt-24">
                        <button type="button" data-bs-dismiss="modal"
                            class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-40 py-11 radius-8">
                            <span>Cancel</span>
                        </button>
                        <button type="submit" @click.prevent="addItem"
                            class="btn btn-primary border border-primary-600 text-md px-24 py-12 radius-8">
                            <span>Save</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="notesModal" tabindex="-1" role="dialog" aria-labelledby="notesModal"
        aria-hidden="true"  key="modal-notesModal">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content radius-16 bg-base">
                <div class="modal-header py-16 px-24 border border-top-0 border-start-0 border-end-0">
                    <h1 class="modal-title text-md" id="exampleModalLabel">
                        Notes / Remarks</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-24">
                    <div class="tw-flex tw-gap-2 tw-flex-col">
                        <div class="">
                            Notes / Remarks
                        </div>
                        <textarea rows="3" type="number" name="" id="" v-model="payment_notes"
                            class=" form-control" placeholder="Enter Notes"></textarea>
                    </div>

                    <div class="d-flex align-items-start justify-content-end gap-3 mt-24">
                        <button data-bs-dismiss="modal"
                            class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-40 py-11 radius-8">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade " id="discount" tabindex="-1" role="dialog" aria-labelledby="discount"
        aria-hidden="true"  key="modal-discount">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content radius-16 bg-base">
                <div class="modal-header py-16 px-24 border border-top-0 border-start-0 border-end-0">
                    <h1 class="modal-title text-md" id="exampleModalLabel">
                        Discount</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-24">
                    <div class="tw-flex tw-gap-2 tw-flex-col">
                        <div class="">
                            Discount
                        </div>
                        <input type="number" name="" id="" v-model="discount"
                            class=" form-control" placeholder="Enter Amount">
                    </div>
                    <div class="d-flex align-items-start justify-content-end gap-3 mt-24">
                        <button data-bs-dismiss="modal"
                            class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-40 py-11 radius-8">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade " id="addons" tabindex="-1" role="dialog" aria-labelledby="discount"
        aria-hidden="true"  key="modal-addons">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content radius-16 bg-base">
                <div class="modal-header py-16 px-24 border border-top-0 border-start-0 border-end-0">
                    <h1 class="modal-title text-md" id="exampleModalLabel">Addons
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-24">
                    <template v-for="row in pos.addons" :key="row.id">
                        <div class="col-12 mb-20 tw-flex tw-justify-between tw-items-center">
                            <div class="d-flex align-items-center gap-10 fw-medium text-lg">
                                <div class="form-check style-check d-flex align-items-center">
                                    <input class="form-check-input radius-4 border border-neutral-500" type="checkbox"
                                        name="addon" :id="'addon' + row.id"
                                        v-model="pos.cartAddonIds" :value="row.id">
                                </div>
                                <label :for="'addon' + row.id"
                                    class="form-label fw-medium  text-primary-light mb-0">{{ row.addon_name }}</label>
                            </div>
                            <div class="text-primary">{{ formatCurrency(row.addon_price) }}</div>
                        </div>
                    </template>
                    
                        <div class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center">
                            <div class="">No addons were found!.</div>
                        </div>
                    
                    <div class="d-flex align-items-start justify-content-end gap-3 mt-24">
                        <button data-bs-dismiss="modal"
                            class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-40 py-11 radius-8">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade " id="payment" tabindex="-1" role="dialog" aria-labelledby="payment"
        aria-hidden="true"  key="payment-modal">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content modal-content-lg radius-16 bg-base">
                <div class="modal-header py-16 px-24 border border-top-0 border-start-0 border-end-0">
                    <h1 class="modal-title text-md" id="exampleModalLabel">
                        Payments
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-24">
                    <div class="">
                        <ul>

                            <li class="d-flex align-items-center gap-1 tw-justify-between text-sm">
                                <span class="text-md fw-semibold text-primary-light">
                                    Balance :</span>
                                <span class="text-secondary-light fw-medium"> {{ formatCurrency(pos.currentBalance || 0) }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-12 tw-mb-6 tw-mt-4">
                        <hr>
                    </div>
                    <div class="col-12 tw-my-6">
                        
                        <table class="table basic-border-table mb-0 tw-w-full tw-text-xs">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Payment Type </th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="(item, key) in pos.payments" :key="key">
                                    <tr>
                                        <td>
                                            {{ key + 1 }}
                                        </td>
                                        <td class="text-primary">{{ formatCurrency(item.amount) }}</td>
                                        <td> {{ item.payment_type }}</td>
                                        <td>
                                            <button @click="removePayment(key)" type="button" class="remove-item-button bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium tw-size-6 d-flex justify-content-center align-items-center rounded-circle"> 
                                                <iconify-icon icon="fluent:delete-24-regular" class="menu-icon"></iconify-icon>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        
                        <div class="tw-py-16">
                            <div class="text-center tw-text-xs">No payments were added, Add a payment to show it here.</div>
                        </div>
                        
                    </div>
                    <div class="col-12 tw-my-6">
                        <hr>
                    </div>
                    <div class="row mb-20 ">
                        <div class="col-6 ">
                            <label for="name"
                                class="form-label fw-semibold text-primary-light text-sm mb-8">Paid Amount
                                <span class="text-danger">*</span></label>
                            <input type="text" class="form-control radius-8"
                                placeholder="Enter Amount"
                                v-model="payment_amount">
                            
                                <span class="error text-danger tw-text-xs"></span>
                            
                        </div>
                        <div class="col-6 ">
                            <label for="name"
                                class="form-label fw-semibold text-primary-light text-sm mb-8">Payment Type
                                <span class="text-danger">*</span></label>
                            <select class="form-select radius-8" v-model="payment_type">
                                <option value="">
                                    Choose Payment Type
                                </option>
                                <option class="select-box" value="1">
                                    Cash
                                </option>
                                <option class="select-box" value="2">
                                    UPI
                                </option>
                                <option class="select-box" value="3">
                                    Card
                                </option>
                                <option class="select-box" value="4">
                                    Cheque
                                </option>
                                <option class="select-box" value="5">
                                    Bank Transfer
                                </option>
                            </select>
                            
                                <span class="error text-danger tw-text-xs"></span>
                            
                        </div>
                    </div>
                    <div class="row mb-20 ">
                        <div class="col-6 ">
                            <label for="name"
                                class="form-label fw-semibold text-primary-light text-sm mb-8">Notes
                                </label>
                            <input type="text" class="form-control radius-8"
                                placeholder="Notes"
                                v-model="notes">
                            
                                <span class="error text-danger tw-text-xs"></span>
                            
                        </div>
                        <div class="col-6">
                            <button
                                class="tw-px-2 col-6 tw-text-xs tw-justify-center tw-font-semibold tw-py-3 tw-mt-[30px]  bg-success-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-w-full tw-border-0 tw-shadow-md "
                                @click="add_payment">
                                <span>Add Payment</span>
                            </button>
                        </div>
                        
                    </div>
                  
                    </div>
                    <div class="modal-footer tw-mt-12">
                        <button
                        class="tw-justify-center tw-font-semibold tw-py-2 tw-h-full bg-primary-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-px-12 tw-border-0 tw-shadow-md "
                        @click.prevent="save">
                        <span>Save & Print</span></button>
                    </div>
                </div>
            </div>
        </div>

        
            <div class="modal " id="addcustomer" tabindex="-1" role="dialog" aria-labelledby="addcustomer-title"
                aria-hidden="true"  key="addcustomer-modal">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title fw-600" id="addcustomer-title">
                                Add Customer
                            </h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form>
                            <div class="modal-body">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-12 mb-1">
                                        <label class="form-label">Customer Name
                                            <span class="text-danger">*</span></label>
                                        <input type="text" required class="form-control"
                                            placeholder="Enter Customer Name"
                                            v-model="customer_name">
                                        
                                            <span class="text-danger"></span>
                                        
                                    </div>
                                    <div class="col-md-12 mb-1">
                                        <label class="form-label">Phone Number
                                            <span class="text-danger">*</span></label>
                                        <input type="text" required class="form-control"
                                            placeholder="Enter Phone Number"
                                            v-model="customer_phone">
                                        
                                            <span class="text-danger"></span>
                                        
                                    </div>
                                    <div class="col-md-12 mb-1">
                                        <label class="form-label">Email</label>
                                        <input type="text" class="form-control"
                                            placeholder="Enter Email"
                                            v-model="email">
                                        
                                            <span class="text-danger"></span>
                                        
                                    </div>
                                    <div class="col-md-12 mb-1">
                                        <label class="form-label">Tax Number</label>
                                        <input type="text" class="form-control"
                                            placeholder="Enter Tax Number"
                                            v-model="tax_no">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea type="text" class="form-control" placeholder="Enter Address"
                                            v-model="address"></textarea>
                                    </div>
                                    <div class="col-md-12 mb-1">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="employee" checked
                                                v-model="is_active">
                                            <label class="form-check-label"
                                                for="employee">Is Active ?</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary"
                                    @click.prevent="createCustomer()">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
        
        
        
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onErrorCaptured } from 'vue';
import { usePosStore } from '../stores/posStore';
import { db } from '../db';

const pos = usePosStore();

const fatalError = ref(null);
onErrorCaptured((err, instance, info) => {
    fatalError.value = `${err.name}: ${err.message}\n\nStack:\n${err.stack}\n\nInfo:\n${info}`;
    console.error('Captured Vue Error:', err, info);
    return false; // Prevent further propagation
});

const generateUUID = () => {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return crypto.randomUUID();
    }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
};

const searchQuery = ref('');
const todayDate = ref(new Date().toISOString().split('T')[0]);
const date = ref(new Date().toISOString().split('T')[0]);
const delivery_date = ref(new Date().toISOString().split('T')[0]);
const selected_type = ref(null);
const customer_query = ref('');
const shown = ref(false);
const payment_type = ref('');
const payment_amount = ref('');
const notes = ref('');

const add_payment = () => {
    if (!payment_type.value || !payment_amount.value) {
        alert("Please enter both payment type and amount");
        return;
    }
    const typeLabel = {
        1: 'Cash', 2: 'UPI', 3: 'Card', 4: 'Cheque', 5: 'Bank Transfer'
    }[payment_type.value] || 'Unknown';

    pos.payments.push({
        payment_type: typeLabel,
        amount: parseFloat(payment_amount.value),
        notes: notes.value
    });
    
    payment_type.value = '';
    payment_amount.value = '';
    notes.value = '';
};

const removePayment = (index) => {
    pos.payments.splice(index, 1);
};

const quickSwatches = ref(JSON.parse(localStorage.getItem('pos-quick-swatches') || '["#ffffff", "#000000", "#ff0000", "#00ff00", "#0000ff", "#ffff00", "#ff00ff", "#00ffff"]'));

watch(quickSwatches, (newVal) => {
    localStorage.setItem('pos-quick-swatches', JSON.stringify(newVal));
}, { deep: true });

const openSwatchIndex = ref(null);

const addSwatch = (color) => {
    if (!quickSwatches.value.includes(color) && color) {
        if (quickSwatches.value.length >= 10) quickSwatches.value.shift();
        quickSwatches.value.push(color);
    }
};

const removeSwatch = (index) => {
    quickSwatches.value.splice(index, 1);
};

const applySwatch = (item, color) => {
    item.color_code = color;
    openSwatchIndex.value = null;
};

// Customer Modal Refs
const customer_name = ref('');
const customer_phone = ref('');
const email = ref('');
const tax_no = ref('');
const address = ref('');
const is_active = ref(true);


const detached = ref(false);
const isMobileCartView = ref(false);

// Service Selection State
const showServiceTypeModal = ref(false);
const selectedService = ref(null);
const availableServiceTypes = ref([]);

// Customer State
const customerQuery = ref('');
const showNewCustomerModal = ref(false);
const newCustomer = ref({ name: '', phone: '' });

onMounted(async () => {
  await pos.initialize();
});

// Computed properties
const filteredServices = computed(() => {
  if (!searchQuery.value) return pos.services;
  const q = searchQuery.value.toLowerCase();
  return pos.services.filter(s => s.service_name.toLowerCase().includes(q));
});

const filteredCustomers = computed(() => {
  if (!customer_query.value) return [...pos.customers].reverse().slice(0, 5); // Show latest 5 by default
  const q = customer_query.value.toLowerCase();
  return pos.customers.filter(c => 
    (c.name && c.name.toLowerCase().includes(q)) || 
    (c.phone && c.phone.includes(q))
  ).slice(0, 5);
});

// Formatting
const formatCurrency = (val) => {
  const amount = Number(val).toFixed(2);
  return `${pos.settings.currency} ${amount}`;
};

// Actions
const selectService = (service) => {
  selectedService.value = service;
  
  // Find available types for this service by checking serviceDetails
  const details = pos.serviceDetails.filter(sd => sd.service_id === service.id);
  const typeIds = details.map(d => d.service_type_id);
  
  availableServiceTypes.value = pos.serviceTypes
    .filter(st => typeIds.includes(st.id))
    .map(st => {
      const d = details.find(d => d.service_type_id === st.id);
      return { ...st, price: d.service_price };
    });
    
  showServiceTypeModal.value = true;
};

const addToCart = (service, type) => {
  // Tax logic conversion for price based on PosScreen.php
  let finalPrice = parseFloat(type.price);
  
  pos.cart.push({
    service_id: service.id,
    service_name: service.service_name,
    service_type_id: type.id,
    service_type_name: type.service_type_name,
    price: finalPrice, // Keeping base price, tax computed in getters
    quantity: 1,
    color_code: ''
  });
  
  showServiceTypeModal.value = false;
};

const showCustomerDropdown = ref(false);

const hideCustomerDropdown = () => {
    // Delay hiding so clicks on the dropdown list can register
    setTimeout(() => {
        showCustomerDropdown.value = false;
    }, 150);
};

const selectCustomer = (cust) => {
  pos.cartCustomer = cust;
  customer_query.value = '';
  showCustomerDropdown.value = false;
};

const createCustomer = async () => {
  if(!customer_name.value || !customer_phone.value) {
    alert("Name and Phone required");
    return;
  }
  
  const cust = {
    id: Date.now(), // Fallback ID for offline customers
    uuid: generateUUID(),
    name: customer_name.value,
    phone: customer_phone.value,
    email: email.value,
    tax_number: tax_no.value,
    address: address.value,
    is_active: is_active.value ? 1 : 0,
    sync_status: 'pending'
  };
  
  await db.customers.add(cust);
  pos.customers.push(cust);
  
  // Add to sync queue
  await db.syncQueue.add({
    type: 'customer',
    data: cust,
    timestamp: Date.now()
  });
  
  selectCustomer(cust);
  
  // Reset form
  customer_name.value = '';
  customer_phone.value = '';
  email.value = '';
  tax_no.value = '';
  address.value = '';
  
  // Close modal via Bootstrap 5 native API if possible, or fallback to vanilla DOM
  const modalEl = document.getElementById('addcustomer');
  if (modalEl && modalEl.classList.contains('show')) {
      if (typeof window.bootstrap !== 'undefined') {
          const modalInstance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
          if (modalInstance) {
              modalInstance.hide();
          }
      } else if (typeof window.$ !== 'undefined') {
          // Fallback
          window.$('#addcustomer').modal('hide');
      }
  }
  
  // Try sync
  if(pos.isOnline) {
    pos.syncOfflineData();
  }
};

const clearAll = () => {
  pos.cart = [];
  pos.cartCustomer = null;
};

const save = async (type = 'save') => {
  await checkout(type);
};

const addItem = () => {
  if(selectedService.value && selected_type.value) {
    const type = availableServiceTypes.value.find(t => t.id == selected_type.value);
    if(type) {
        addToCart(selectedService.value, type);
        selected_type.value = null; // reset selection
        
        // Hide modal
        const modalEl = document.getElementById('servicetype');
        if (modalEl && modalEl.classList.contains('show')) {
            if (typeof window.bootstrap !== 'undefined') {
                const modalInstance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }
            } else if (typeof window.$ !== 'undefined') {
                window.$('#servicetype').modal('hide');
            }
        }
    }
  }
};

const checkout = async (type) => {
  if (pos.cart.length === 0) {
    alert("Cart is empty");
    return;
  }
  
  const today = new Date().toISOString().split('T')[0];
  
  const orderData = {
    uuid: generateUUID(),
    customer_id: pos.cartCustomer ? pos.cartCustomer.id : null,
    customer_name: pos.cartCustomer ? pos.cartCustomer.name : null,
    phone_number: pos.cartCustomer ? pos.cartCustomer.phone : null,
    order_date: today,
    delivery_date: today,
    sub_total: pos.cartSubTotal,
    addon_total: pos.cartAddonsTotal,
    discount: 0,
    tax_percentage: pos.settings.tax_percentage,
    tax_amount: pos.cartTax,
    tax_type: pos.settings.tax_type,
    taxable_amount: pos.cartTotal - pos.cartTax, // Simplified
    total: pos.cartTotal,
    status: 0,
    details: pos.cart.map(item => ({
      service_id: item.service_id,
      service_name: item.service_type_name, // Mapping correctly for Laravel backend
      service_quantity: item.quantity,
      service_detail_total: item.price * item.quantity,
      service_price: item.price,
      color_code: item.color_code
    })),
    payments: [...pos.payments]
  };

  if(type === 'cash') {
    orderData.payments.push({
      payment_type: 'Cash', // Cash
      amount: orderData.total,
      notes: "Offline Cash Payment"
    });
  }

  // Save to offline queue
  const plainOrderData = JSON.parse(JSON.stringify(orderData));
  await db.syncQueue.add({
    type: 'order',
    data: plainOrderData,
    timestamp: Date.now()
  });

  alert("Order Saved Successfully! It will sync automatically when online.");
  
  // Hide payment modal if open
  const paymentModalEl = document.getElementById('payment');
  if (paymentModalEl && paymentModalEl.classList.contains('show')) {
      if (typeof window.bootstrap !== 'undefined') {
          const mInst = window.bootstrap.Modal.getOrCreateInstance(paymentModalEl);
          if (mInst) mInst.hide();
      } else if (typeof window.$ !== 'undefined') {
          window.$('#payment').modal('hide');
      }
  }

  // Reset Cart
  pos.cart = [];
  pos.cartCustomer = null;
  pos.payments = [];
  customer_query.value = '';
  isMobileCartView.value = false;
  
  // Trigger sync if online
  if(pos.isOnline) {
    pos.syncOfflineData();
  }
};

</script>