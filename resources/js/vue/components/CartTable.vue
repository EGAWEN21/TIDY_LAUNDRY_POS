<template>
  <div class="tw-h-[calc(100vh-4rem)] dark:tw-bg-slate-900/50 tw-bg-white/50 tw-backdrop-blur-xl tw-shadow-[-4px_0_24px_rgba(0,0,0,0.05)] tw-border-l tw-border-white/20 dark:tw-border-white/5 tw-p-4 lg:tw-p-6" :class="shown && detached ? 'tw-absolute tw-inset-0 tw-w-full tw-z-50' : 'tw-hidden lg:tw-block lg:tw-w-7/12 tw-w-full tw-shrink-0'">
      <div class="tw-flex tw-items-center tw-gap-8 tw-w-full">
          <div class="tw-flex tw-min-w-fit tw-shrink tw-flex-col">
              <div class="tw-text-sm">Order : <span class="tw-font-bold">#{{ pos.cartOrderId }}</span></div>
              <div class="tw-flex tw-items-center tw-gap-2">
                  <div class="tw-text-sm tw-relative">
                      Date : <span class="tw-font-bold">{{ pos.orderDate }}</span>
                      <input type="date" v-model="pos.orderDate" name="" class="tw-opacity-0 tw-absolute tw-pointer-events-none" ref="datePicker">
                  </div>
                  <button @click="$refs.datePicker.showPicker()" class="tw-px-2 tw-py-1 bg-primary-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0">
                      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-calendar3" viewBox="0 0 16 16">
                          <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2M1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857z" />
                          <path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2" />
                      </svg>
                  </button>
              </div>

              <div class="tw-flex tw-items-center tw-gap-2">
                  <div class="tw-text-sm tw-relative">
                      Delivery Date : <span class="tw-font-bold">{{ pos.deliveryDate }}</span>
                      <input type="date" v-model="pos.deliveryDate" name="" class="tw-opacity-0 tw-absolute tw-pointer-events-none" ref="deliveryDatePicker">
                  </div>
                  <button @click="$refs.deliveryDatePicker.showPicker()" class="tw-px-2 tw-py-1 bg-primary-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0">
                      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-calendar3" viewBox="0 0 16 16">
                          <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2M1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857z" />
                          <path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2" />
                      </svg>
                  </button>
              </div>
          </div>
          <div class="tw-flex tw-items-center tw-gap-2 tw-w-full tw-shrink">
              <div class="tw-flex tw-flex-col tw-gap-2 tw-w-full">
                  <div v-if="pos.cartCustomer" class="tw-flex tw-items-center tw-justify-between tw-w-full tw-border tw-rounded-lg tw-px-3 tw-py-2 tw-bg-green-50/80 dark:tw-bg-green-900/30 tw-border-green-300 dark:tw-border-green-700">
                      <div class="tw-flex tw-flex-col">
                          <span class="tw-font-bold tw-text-sm tw-text-green-800 dark:tw-text-green-300">{{ pos.cartCustomer.name }}</span>
                          <span class="tw-text-xs tw-text-green-600 dark:tw-text-green-400">{{ pos.cartCustomer.phone }}</span>
                      </div>
                      <button @click="pos.cartCustomer = null" class="tw-text-red-500 hover:tw-text-red-700 dark:tw-text-red-400 dark:hover:tw-text-red-300 tw-font-bold tw-px-2 tw-text-lg">&times;</button>
                  </div>
                  <div class="icon-field tw-relative tw-w-full tw-items-center">
                      <span class="icon -tw-translate-y-[2px]">
                          <iconify-icon icon="f7:person"></iconify-icon>
                      </span>
                      <input type="text" class="form-control" :placeholder="pos.cartCustomer ? 'Change Customer...' : 'Select A Customer'" @focus="showCustomerDropdown = true" @blur="hideCustomerDropdown" v-model="pos.customerQuery">
                      <div v-show="showCustomerDropdown && filteredCustomers.length > 0" class="tw-absolute tw-top-[100%] tw-left-0 tw-w-full tw-z-20 tw-shadow-md tw-bg-white tw-rounded-lg">
                          <ul>
                              <li v-for="row in filteredCustomers" :key="row.id" class="dropdown-item px-16 py-8 rounded text-secondary-light bg-hover-neutral-200 text-hover-neutral-900 tw-cursor-pointer" @mousedown.prevent="selectCustomer(row)">{{ row.name }} - {{ row.phone }}</li>
                          </ul>
                      </div>
                  </div>
              </div>
              <button type="button" data-bs-toggle="modal" data-bs-target="#addcustomer" class="tw-px-4 tw-py-3 bg-primary-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-fill-add" viewBox="0 0 16 16">
                      <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0m-2-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0" />
                      <path d="M2 13c0 1 1 1 1 1h5.256A4.5 4.5 0 0 1 8 12.5a4.5 4.5 0 0 1 1.544-3.393Q8.844 9.002 8 9c-5 0-6 3-6 4" />
                  </svg>
              </button>
          </div>
      </div>
      <div class="tw-w-full tw-flex tw-flex-col tw-mt-6 tw-rounded-2xl tw-overflow-clip tw-border tw-border-white/60 dark:tw-border-white/10 tw-shadow-lg tw-bg-white/60 dark:tw-bg-slate-800/60 tw-backdrop-blur-md">
          <div class="tw-flex tw-flex-col lg:tw-w-full tw-overflow-x-auto">
              <div class="tw-flex tw-flex-col lg:tw-w-full tw-w-full tw-min-w-[60rem]">
                  <div class="tw-flex tw-flex-col tw-overflow-x-auto tw-w-full tw-shrink-0">
                      <table class="tw-w-full tw-text-xs tw-shrink-0 tw-h-fit">
                          <thead class="tw-bg-slate-100/50 dark:tw-bg-slate-700/50 tw-text-slate-600 dark:tw-text-slate-300 tw-uppercase tw-tracking-wider tw-font-semibold tw-border-b tw-border-white/40 dark:tw-border-white/10">
                              <tr>
                                  <th class="tw-py-3 tw-px-3 tw-text-xs tw-w-[10rem] lg:tw-w-[10%] tw-text-left">Service</th>
                                  <th class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[15%] tw-text-center">Color</th>
                                  <th class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[15%] tw-text-center">Price</th>
                                  <th class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[15%] tw-text-center">Rate</th>
                                  <th class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[15%] tw-text-center">QTY</th>
                                  <th class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[10%] tw-text-center">Tax ({{ pos.settings.tax_percentage }}%)</th>
                                  <th class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[10%] tw-text-center">Total</th>
                                  <th class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[5%] tw-text-center"></th>
                              </tr>
                          </thead>
                      </table>
                  </div>

                  <div class="tw-flex tw-h-[calc(100dvh-23rem)] tw-overflow-y-auto tw-overflow-x-auto tw-w-full tw-shrink-0">
                      <table class="tw-w-full tw-text-xs tw-shrink-0 tw-h-fit">
                          <TransitionGroup name="cart-list" tag="tbody">
                              <tr v-for="(item, key) in pos.cart" :key="key" class="tw-border-b tw-border-neutral-200 dark:tw-border-neutral-800/50 tw-border-solid tw-transition-all tw-duration-200">
                                  <td class="tw-py-2 tw-px-2 lg:tw-w-[10%] tw-w-[10rem] tw-text-left">
                                      <div class="tw-flex tw-flex-col">
                                          <div class="tw-text-xs tw-font-semibold">{{ item.service_name }}</div>
                                          <div class="tw-text-xs tw-font-normal text-primary-600">[{{ item.service_type_name }}]</div>
                                      </div>
                                  </td>
                                  <td class="tw-py-2 tw-px-1 lg:tw-w-[15%] tw-w-[10rem] tw-text-center">
                                      <div class="tw-flex tw-items-center tw-justify-center tw-gap-1 tw-w-full">
                                          <div class="tw-relative">
                                              <button type="button" @click="openSwatchIndex = openSwatchIndex === key ? null : key" class="tw-flex tw-items-center tw-justify-center tw-w-7 tw-h-6 tw-bg-gray-100 hover:tw-bg-gray-200 tw-border tw-border-gray-300 tw-rounded tw-transition-colors" title="Choose from quick swatches">
                                                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="tw-text-gray-600" viewBox="0 0 16 16">
                                                      <path d="M8 5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zm4 3a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM5.5 7a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm.5 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z" />
                                                      <path d="M16 8c0 3.15-1.866 2.585-3.567 2.07C11.42 9.763 10.465 9.473 10 10c-.603.683-.475 1.819-.351 2.92C9.826 14.495 9.996 16 8 16a8 8 0 1 1 8-8zm-8 7c.611 0 .654-.171.655-.176.078-.146.124-.464.07-1.119-.014-.168-.037-.37-.061-.591-.052-.464-.112-1.005-.118-1.462-.01-.707.083-1.61.704-2.314.369-.417.845-.578 1.272-.618.404-.038.812.026 1.16.104.343.077.702.186 1.025.284 1.064.326 1.756.54 1.886.541.01 0 .013 0 .013-.001C15.823 13.064 16 11.233 16 8A7 7 0 1 0 8 15z" />
                                                  </svg>
                                              </button>
                                              <div v-show="openSwatchIndex === key" class="tw-absolute tw-z-50 tw-bg-white tw-shadow-lg tw-border tw-border-gray-200" style="top: 32px; left: 0px; width: 170px; border-radius: 12px; padding: 12px;">
                                                  <div class="tw-flex tw-justify-between tw-items-center" style="margin-bottom: 10px;">
                                                      <span class="tw-text-xs tw-font-semibold tw-uppercase tw-tracking-wider" style="font-size: 10px; color: #4b5563;">Quick Colors</span>
                                                      <button @click="openSwatchIndex = null" class="tw-text-gray-400 hover:tw-text-gray-600">&times;</button>
                                                  </div>
                                                  <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                                      <div v-for="(swatch, sIndex) in quickSwatches" :key="sIndex" class="tw-relative tw-group" style="display: flex; align-items: center; justify-content: center;">
                                                          <button type="button" @click="applySwatch(item, swatch)" class="tw-rounded-full tw-shadow-sm hover:tw-scale-110 tw-transition-transform" style="cursor: pointer; padding: 0; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05);" :style="`background-color: ${swatch}; width: 22px; height: 22px;`" :title="swatch"></button>
                                                          <button type="button" @click.stop="removeSwatch(sIndex)" class="tw-hidden group-hover:tw-flex tw-items-center tw-justify-center" style="position: absolute; top: -4px; right: -4px; z-index: 10; width: 14px; height: 14px; background-color: #ef4444; color: white; border-radius: 50%; font-size: 10px; line-height: 1; border: none; padding: 0; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.1);" title="Remove">&times;</button>
                                                      </div>
                                                  </div>
                                              </div>
                                          </div>
                                          <input type="color" pattern="^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$" class="tw-w-10 tw-h-6 tw-p-0 tw-border-0" v-model="item.color_code">
                                          <button type="button" @click="addSwatch(item.color_code)" class="tw-flex tw-items-center tw-justify-center tw-w-6 tw-h-6 tw-bg-gray-100 hover:tw-bg-gray-200 tw-rounded tw-border tw-border-gray-300 tw-transition-colors tw-text-gray-700 tw-text-xs tw-font-bold" title="Save current color to quick swatches">+</button>
                                      </div>
                                  </td>
                                  <td class="tw-py-2 tw-px-1 lg:tw-w-[15%] tw-w-[10rem] tw-text-center">
                                      <div class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center">
                                          <input type="number" step="0.01" v-model.number="item.price" class="tw-ring-1 tw-px-1 tw-py-0.5 tw-rounded-md tw-w-[4.5rem]">
                                      </div>
                                  </td>
                                  <td class="tw-py-2 tw-px-1 lg:tw-w-[15%] tw-w-[10rem] tw-text-center">
                                      <div class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center">{{ formatCurrency(item.price) }}</div>
                                  </td>
                                  <td class="tw-py-2 tw-px-1 lg:tw-w-[15%] tw-w-[10rem] tw-text-center">
                                      <div class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center">
                                          <div class="tw-flex tw-items-center tw-gap-2 tw-justify-center tw-text-sm">
                                              <button @click="pos.decreaseQty(key)" class="tw-px-2 tw-py-1 bg-primary-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md">
                                                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-dash" viewBox="0 0 16 16"><path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8" /></svg>
                                              </button>
                                              {{ item.quantity }}
                                              <button @click="pos.increaseQty(key)" class="tw-px-2 tw-py-1 bg-primary-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md">
                                                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-lg" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2" /></svg>
                                              </button>
                                          </div>
                                      </div>
                                  </td>
                                  <td class="tw-py-2 tw-px-1 lg:tw-w-[10%] tw-w-[10rem] tw-text-center">
                                      <div class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center">{{ formatCurrency(pos.calculateItemTax(item)) }}</div>
                                  </td>
                                  <td class="tw-py-2 tw-px-1 lg:tw-w-[10%] tw-w-[10rem] tw-text-center">
                                      <div class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center">{{ formatCurrency(pos.calculateItemTotal(item)) }}</div>
                                  </td>
                                  <td class="tw-py-2 tw-px-1 lg:tw-w-[10%] tw-w-[10rem] tw-text-center">
                                      <div class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center tw-gap-2">
                                          <button @click.prevent="pos.duplicateItem(key)" title="Duplicate" class="tw-px-2 tw-py-1 bg-info-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-justify-center tw-gap-1.5 tw-border-0 tw-shadow-md">
                                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-copy" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z"/></svg>
                                          </button>
                                          <button @click="pos.removeItem(key)" title="Remove" class="tw-px-2 tw-py-1 tw-bg-red-500 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md">
                                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z" /><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z" /></svg>
                                          </button>
                                      </div>
                                  </td>
                              </tr>
                          </TransitionGroup>
                      </table>
                  </div>
              </div>
          </div>
          <div class="tw-mt-2 tw-flex tw-justify-between tw-text-sm tw-p-4 tw-bg-slate-50/50 dark:tw-bg-slate-800/80 tw-border-t tw-border-white/40 dark:tw-border-white/10 tw-rounded-b-2xl dark:tw-text-slate-200">
              <div class="tw-flex tw-flex-col tw-gap-2">
                  <div class="tw-flex tw-items-end tw-justify-end tw-gap-2">
                      <div class="tw-flex tw-items-center tw-gap-2">
                          Addon <button data-bs-toggle="modal" data-bs-target="#addons" class="tw-px-1 tw-py-1 tw-rounded-md tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md bg-primary-600 tw-text-white tw-border tw-border-solid tw-bg-transparent tw-border-neutral-400"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-box-fill" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15.528 2.973a.75.75 0 0 1 .472.696v8.662a.75.75 0 0 1-.472.696l-7.25 2.9a.75.75 0 0 1-.557 0l-7.25-2.9A.75.75 0 0 1 0 12.331V3.669a.75.75 0 0 1 .471-.696L7.443.184l.004-.001.274-.11a.75.75 0 0 1 .558 0l.274.11.004.001zm-1.374.527L8 5.962 1.846 3.5 1 3.839v.4l6.5 2.6v7.922l.5.2.5-.2V6.84l6.5-2.6v-.4l-.846-.339Z" /></svg></button> :
                      </div>
                      <div class="tw-font-bold">{{ formatCurrency(pos.cartAddonsTotal) }}</div>
                  </div>
                  <div class="tw-flex tw-items-center tw-gap-2">
                      <div class="">Total Items :</div>
                      <div class="tw-font-bold">{{ pos.cartTotalItems }}</div>
                  </div>
                  <div class="tw-flex tw-items-center tw-gap-2">
                      <div class="">Sub Total :</div>
                      <div class="tw-font-bold">{{ formatCurrency(pos.cartSubTotal) }}</div>
                  </div>
                  <div class="tw-flex tw-items-center tw-gap-2">
                      <div class="tw-flex tw-items-center tw-gap-2">
                          Notes : <button data-bs-toggle="modal" data-bs-target="#notesModal" class="tw-px-1 tw-py-1 tw-rounded-md tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md bg-primary-600 tw-text-white tw-border tw-border-solid tw-bg-transparent tw-border-neutral-400"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16"><path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z" /><path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z" /></svg></button>
                      </div>
                  </div>
              </div>
              <div class="tw-flex tw-flex-col tw-gap-2">
                  <div class="tw-flex tw-items-end tw-justify-end tw-gap-2">
                      <div class="">Tax ({{ pos.settings.tax_percentage }}%) :</div>
                      <div class="tw-font-bold">{{ formatCurrency(pos.cartTax) }}</div>
                  </div>
                  <div class="tw-flex tw-items-end tw-justify-end tw-gap-2">
                      <div class="tw-flex tw-items-center tw-gap-2">
                          Discount <button data-bs-toggle="modal" data-bs-target="#discount" class="tw-px-1 tw-py-1 tw-rounded-md tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md bg-primary-600 tw-text-white tw-border tw-border-solid tw-bg-transparent tw-border-neutral-400"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-tag-fill" viewBox="0 0 16 16"><path d="M2 1a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7-7A1 1 0 0 0 6.586 1zm4 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0" /></svg></button> :
                      </div>
                      <div class="tw-font-bold">{{ formatCurrency(pos.cartDiscount) }}</div>
                  </div>
                  <div class="tw-flex tw-items-center tw-justify-end tw-gap-2">
                      <div class="">Gross Total :</div>
                      <div class="tw-font-extrabold">{{ formatCurrency(pos.cartTotal) }}</div>
                  </div>
              </div>
          </div>
      </div>
      <div class="tw-flex tw-items-center tw-gap-3 tw-mt-4 tw-w-full tw-h-14">
          <button class="tw-px-4 tw-font-semibold tw-py-3 tw-h-full tw-bg-gradient-to-r tw-from-slate-800 tw-to-slate-900 dark:tw-from-slate-700 dark:tw-to-slate-800 hover:tw-from-slate-900 hover:tw-to-black tw-transition-all tw-rounded-xl tw-text-white tw-flex tw-items-center tw-justify-center tw-gap-2 tw-w-full tw-border-0 tw-shadow-lg hover:-tw-translate-y-0.5" data-bs-toggle="modal" data-bs-target="#payment">
              <span>Payment</span>
          </button>
          <button class="tw-px-4 tw-font-semibold tw-py-3 tw-h-full tw-bg-gradient-to-r tw-from-emerald-500 tw-to-emerald-600 dark:tw-from-emerald-600 dark:tw-to-emerald-700 hover:tw-from-emerald-600 hover:tw-to-emerald-700 tw-transition-all tw-rounded-xl tw-text-white tw-flex tw-items-center tw-justify-center tw-gap-2 tw-w-full tw-border-0 tw-shadow-lg hover:-tw-translate-y-0.5" @click.prevent="$emit('save', 'cash')">
              <span>Cash</span>
          </button>
          <button class="tw-px-4 tw-font-semibold tw-py-3 tw-h-full tw-bg-gradient-to-r tw-from-amber-500 tw-to-amber-600 dark:tw-from-amber-600 dark:tw-to-amber-700 hover:tw-from-amber-600 hover:tw-to-amber-700 tw-transition-all tw-rounded-xl tw-text-white tw-flex tw-items-center tw-justify-center tw-gap-2 tw-w-full tw-border-0 tw-shadow-lg hover:-tw-translate-y-0.5" @click.prevent="$emit('saveOffline')">
              <span>Save Offline</span>
          </button>
          <button :disabled="isSyncing" class="tw-px-4 tw-font-semibold tw-py-3 tw-h-full tw-bg-gradient-to-r tw-from-primary-600 tw-to-primary-700 hover:tw-from-primary-700 hover:tw-to-primary-800 tw-transition-all tw-rounded-xl tw-text-white tw-flex tw-items-center tw-justify-center tw-gap-2 tw-w-full tw-border-0 tw-shadow-lg hover:-tw-translate-y-0.5 disabled:tw-opacity-50 disabled:tw-cursor-not-allowed disabled:hover:tw-translate-y-0" @click.prevent="$emit('syncAndPrint')">
              <svg v-if="isSyncing" class="tw-animate-spin -tw-ml-1 tw-mr-2 tw-h-4 tw-w-4 tw-text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="tw-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="tw-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              <span>{{ isSyncing ? 'Syncing...' : 'Sync & Print' }}</span>
          </button>
          <button class="tw-px-4 tw-py-3 tw-bg-gradient-to-r tw-from-rose-500 tw-to-rose-600 hover:tw-from-rose-600 hover:tw-to-rose-700 tw-transition-all tw-rounded-xl tw-text-white tw-h-full tw-flex tw-items-center tw-justify-center tw-gap-2 tw-border-0 tw-shadow-lg hover:-tw-translate-y-0.5" @click.prevent="$emit('clearAll')">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-arrow-repeat" viewBox="0 0 16 16"><path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41m-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9" /><path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5 5 0 0 0 8 3M3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9z" /></svg>
          </button>
      </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { usePosStore } from '../../stores/posStore';

const props = defineProps({
  shown: Boolean,
  detached: Boolean,
  isSyncing: Boolean
});

const emit = defineEmits(['save', 'clearAll', 'saveOffline', 'syncAndPrint']);

const pos = usePosStore();

const showCustomerDropdown = ref(false);

const filteredCustomers = computed(() => {
  if (!pos.customerQuery) return [...pos.customers].reverse().slice(0, 5);
  const q = pos.customerQuery.toLowerCase();
  return pos.customers.filter(c => 
    (c.name && c.name.toLowerCase().includes(q)) || 
    (c.phone && c.phone.includes(q))
  ).slice(0, 5);
});

const hideCustomerDropdown = () => {
    setTimeout(() => {
        showCustomerDropdown.value = false;
    }, 150);
};

const selectCustomer = (cust) => {
  pos.cartCustomer = cust;
  pos.customerQuery = '';
  showCustomerDropdown.value = false;
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

const formatCurrency = (val) => {
  const amount = Number(val).toFixed(2);
  return `${pos.settings.currency} ${amount}`;
};
</script>

<style>
/* Vue Transition Group Animation for Cart items */
.cart-list-enter-active,
.cart-list-leave-active {
  transition: all 0.2s ease;
}
.cart-list-enter-from,
.cart-list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
</style>
