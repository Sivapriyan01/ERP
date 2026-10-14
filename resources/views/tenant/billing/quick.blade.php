@extends('layouts.tenant')
@section('title', 'Quick POS')
@section('page-title', 'Quick Outward')

@section('content')
<div x-data="posSystem" class="flex flex-col lg:flex-row h-full gap-3 bg-slate-50 dark:bg-transparent overflow-hidden p-4 pb-0">
    <div x-data="{ test: 'JS_ENGINE_ACTIVE' }" x-text="test" class="hidden"></div>
    
    <!-- Main Workspace -->
    <main class="flex-1 flex flex-col min-w-0 gap-3 overflow-hidden">
        <!-- Header: Tabs & Search -->
        <header class="bg-white dark:bg-slate-900/80 border border-slate-200/60 dark:border-slate-800/80 rounded-[2rem] p-4 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar max-w-2xl">
                <template x-for="(tab, index) in tabs" :key="tab.id">
                    <button @click="activeTab = index" 
                            :class="activeTab === index ? 'bg-slate-900 dark:bg-blue-600 text-white shadow-lg shadow-blue-100 dark:shadow-none' : 'bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="px-6 py-2.5 rounded-2xl text-[11px] font-black uppercase tracking-widest flex items-center gap-3 transition-all whitespace-nowrap">
                        <span x-text="tab.label"></span>
                        <svg x-show="tabs.length > 1" @click.stop="closeTab(index)" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="opacity-50 hover:opacity-100"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </template>
                <button @click="addTab()" class="p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white rounded-xl transition-all">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                </button>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative w-64">
                    <input type="text" x-model="search" placeholder="Scan Barcode or Search..." 
                           class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-950/50 text-slate-800 dark:text-slate-100 border border-transparent dark:border-slate-800/80 rounded-2xl text-xs font-bold focus:ring-4 focus:ring-blue-50 transition-all outline-none">
                    <svg class="absolute left-3.5 top-2.5 text-slate-900 dark:text-slate-400" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>
        </header>

        <!-- Categories Chips -->
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
            <button @click="activeCategory = 'All'" 
                    :class="activeCategory === 'All' ? 'bg-slate-900 dark:bg-blue-600 text-white border-transparent' : 'bg-white dark:bg-slate-900/80 text-slate-900 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-850 border-slate-200/60 dark:border-slate-800'"
                    class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all shadow-sm">All</button>
            @foreach($categories as $cat)
            <button @click="activeCategory = '{{ $cat }}'" 
                    :class="activeCategory === '{{ $cat }}' ? 'bg-slate-900 dark:bg-blue-600 text-white border-transparent' : 'bg-white dark:bg-slate-900/80 text-slate-900 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-850 border-slate-200/60 dark:border-slate-800'"
                    class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all shadow-sm">{{ $cat }}</button>
            @endforeach
        </div>

        <!-- Product Grid -->
        <div class="flex-1 overflow-y-auto pr-1 custom-scrollbar">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
                <template x-for="product in filteredProducts()" :key="product.id">
                    <div @click="addToCart(product)" 
                         class="bg-white dark:bg-slate-900/80 border border-slate-200/60 dark:border-slate-800 p-3 rounded-[2rem] cursor-pointer group hover:border-blue-200 dark:hover:border-blue-500/50 hover:shadow-xl hover:shadow-blue-100 dark:hover:shadow-none transition-all flex flex-col gap-3 relative">
                        <div class="aspect-square bg-slate-50 dark:bg-slate-950/50 rounded-[1.5rem] flex items-center justify-center relative overflow-hidden group-hover:bg-blue-50/50 dark:group-hover:bg-blue-900/20 transition-colors">
                            <template x-if="product.image"><img :src="'/storage/' + product.image" class="max-h-[80%] max-w-[80%] transform group-hover:scale-110 transition-transform duration-500"></template>
                            <template x-if="!product.image"><svg width="32" height="32" class="text-slate-200 dark:text-slate-700 group-hover:text-blue-200 dark:group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg></template>
                            <div class="absolute inset-0 bg-slate-900/0 group-hover:bg-slate-900/5 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all">
                                <div class="bg-slate-900 dark:bg-blue-600 text-white px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest shadow-xl transform translate-y-2 group-hover:translate-y-0 transition-all">Add to Cart</div>
                            </div>
                        </div>
                        <div class="px-1 pb-1">
                            <h4 class="text-[11px] font-black text-slate-800 dark:text-slate-100 uppercase tracking-tight line-clamp-1" x-text="product.product_name"></h4>
                            <div class="flex justify-between items-end mt-1">
                                <div>
                                    <p class="text-[8px] font-bold text-slate-400 dark:text-slate-500" x-text="product.brand || 'Generic'"></p>
                                    <p class="text-sm font-black text-slate-900 dark:text-slate-200" x-text="'₹' + Number(product.mrp).toFixed(2)"></p>
                                </div>
                                <span class="text-[7px] font-black px-1.5 py-0.5 rounded-md uppercase tracking-widest" 
                                      :class="product.stock > 10 ? 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400' : 'bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400'"
                                      x-text="product.stock + ' left'"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </main>

    <!-- Right Side: Cart Summary -->
    <aside class="w-60 flex-shrink-0 flex flex-col gap-2.5 h-full overflow-y-auto no-scrollbar pb-6 pr-1">
        <!-- Customer Panel -->
        <div class="bg-white dark:bg-slate-900/80 border border-slate-200/60 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-[9px] font-black text-slate-900 dark:text-slate-200 uppercase tracking-widest">Customer Details</h3>
                <button class="p-1.5 text-slate-900 dark:text-blue-400 bg-blue-50 dark:bg-slate-800 rounded-lg hover:bg-blue-100 dark:hover:bg-slate-700 transition-all">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                </button>
            </div>
                <div class="space-y-2">
                    <div class="relative">
                        <input type="text" x-model="tabs[activeTab].customerPhone" placeholder="Mobile Number..." 
                               @input="if(tabs[activeTab].customerPhone.trim().length >= 10) lookupCustomer()"
                               class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-950/50 border border-transparent dark:border-slate-800/80 rounded-xl text-xs font-bold text-slate-800 dark:text-slate-100 focus:ring-4 focus:ring-blue-50 transition-all outline-none">
                        <div class="absolute left-3 top-2.5 text-slate-900 dark:text-slate-400 flex items-center gap-1.5">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </div>
                    </div>
                    <div x-show="customerCredit > 0" class="flex items-center justify-between px-3 py-1.5 bg-rose-50 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/50 rounded-lg animate-fadeIn" style="display: none;">
                        <span class="text-[9px] font-black text-rose-500 uppercase tracking-widest">Credit Due</span>
                        <span class="text-[10px] font-black text-rose-600" x-text="'₹' + Number(customerCredit).toFixed(2)"></span>
                    </div>
                    <div x-show="customerWallet > 0" class="flex items-center justify-between px-3 py-1.5 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/50 rounded-lg animate-fadeIn" style="display: none;">
                        <span class="text-[9px] font-black text-emerald-500 uppercase tracking-widest">Wallet Balance</span>
                        <span class="text-[10px] font-black text-emerald-600" x-text="'₹' + Number(customerWallet).toFixed(2)"></span>
                    </div>
                    <div class="flex gap-1.5">
                        <div class="relative flex-1">
                            <input type="text" x-model="tabs[activeTab].customerGstin" placeholder="GSTIN" 
                                   @input="if(tabs[activeTab].customerGstin.trim().length === 15) verifyQuickGstin()"
                                   class="w-full pl-9 pr-2 py-2 bg-slate-50 dark:bg-slate-955/50 border border-transparent dark:border-slate-800/80 rounded-xl text-xs font-bold text-slate-800 dark:text-slate-100 focus:ring-4 focus:ring-blue-50 transition-all outline-none uppercase">
                            <div class="absolute left-3 top-2.5 text-slate-900 dark:text-slate-400">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                        </div>
                        <button @click="verifyQuickGstin()" :disabled="isValidatingGstin"
                                class="px-2.5 bg-slate-900 dark:bg-blue-600 text-white rounded-lg text-[9px] font-black uppercase tracking-wider hover:bg-slate-800 dark:hover:bg-blue-700 transition-all disabled:opacity-50">
                            <span x-show="!isValidatingGstin">Verify</span>
                            <span x-show="isValidatingGstin">...</span>
                        </button>
                    </div>
                    <div x-show="gstError" x-text="gstError" class="text-[8px] font-black text-rose-500 uppercase px-3"></div>
                    <div class="relative">
                        <input type="text" id="q_customer_name" x-model="tabs[activeTab].customerName" placeholder="Customer Name..." 
                               class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-950/50 border border-transparent dark:border-slate-800/80 rounded-xl text-xs font-bold text-slate-800 dark:text-slate-100 focus:ring-4 focus:ring-blue-50 transition-all outline-none">
                        <div class="absolute left-3 top-2.5 text-slate-900 dark:text-slate-400 flex items-center gap-1.5">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                    </div>
                    <div class="px-1 py-0.5">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="tabs[activeTab].reminderEnabled" class="sr-only peer">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                            <span class="ml-2.5 text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">1 Year Reminder</span>
                        </label>
                    </div>
                </div>
        </div>

        <!-- Current Cart -->
        <div class="bg-white dark:bg-slate-900/80 border border-slate-200/60 dark:border-slate-800 rounded-2xl p-4 flex flex-col flex-1 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-slate-900 dark:bg-slate-800 rounded-lg flex items-center justify-center">
                        <svg width="16" height="16" class="text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                    <h2 class="text-[10px] font-black text-slate-900 dark:text-slate-100 uppercase tracking-tighter">Quick Bill</h2>
                </div>
                <span class="bg-blue-50 dark:bg-slate-800 text-slate-900 dark:text-slate-300 px-2 py-0.5 rounded-full text-[9px] font-black tracking-wider uppercase" x-text="tabs[activeTab].items.length + ' items'"></span>
            </div>

            <div class="space-y-2 pr-1 flex-1 overflow-y-auto custom-scrollbar">
                <template x-if="tabs[activeTab].items.length === 0">
                    <div class="h-full flex flex-col items-center justify-center opacity-30 gap-2 py-6">
                        <div class="w-14 h-14 bg-slate-50 dark:bg-slate-800/50 rounded-full flex items-center justify-center">
                            <svg width="24" height="24" class="text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-900 dark:text-slate-400">Cart is Empty</p>
                    </div>
                </template>
                <template x-for="(item, index) in tabs[activeTab].items" :key="item.id">
                    <div class="flex items-center gap-2 bg-slate-50/50 dark:bg-slate-950/30 p-2 rounded-xl border border-slate-100 dark:border-slate-800/80 hover:border-blue-100 dark:hover:border-blue-500/30 transition-all group animate-fadeIn">
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] font-black text-slate-800 dark:text-slate-200 truncate uppercase" x-text="item.product_name"></p>
                            <p class="text-[9px] font-bold text-slate-900 dark:text-slate-400 mt-0.5" x-text="'₹' + Number(item.mrp).toFixed(2)"></p>
                        </div>
                        <div class="flex items-center bg-white dark:bg-slate-900 rounded-lg p-0.5 shadow-sm border border-slate-100 dark:border-slate-800">
                            <button @click="updateQty(index, -1)" class="w-5 h-5 flex items-center justify-center hover:bg-slate-50 dark:hover:bg-slate-850 rounded text-slate-900 dark:text-slate-100 font-black">-</button>
                            <span class="w-6 text-center text-[9px] font-black text-slate-900 dark:text-slate-100" x-text="item.qty"></span>
                            <button @click="updateQty(index, 1)" class="w-5 h-5 flex items-center justify-center hover:bg-slate-50 dark:hover:bg-slate-850 rounded text-slate-900 dark:text-slate-100 font-black">+</button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Totals -->
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 space-y-2">
                <div class="flex justify-between text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                    <span>Subtotal</span>
                    <span class="text-slate-900 dark:text-slate-200" x-text="'₹' + Number(getSubtotal()).toFixed(2)"></span>
                </div>
                <template x-if="{{ $settings['gst_enabled'] ? 'true' : 'false' }}">
                    <div class="flex justify-between text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                        <span>Tax (GST {{ $settings['default_gst'] }}%{{ ($settings['gst_calc_type'] ?? 'inclusive') === 'inclusive' ? ' Incl' : '' }})</span>
                        <span class="text-slate-900 dark:text-slate-200" x-text="'₹' + Number(getTaxAmount()).toFixed(2)"></span>
                    </div>
                </template>
                <button @click="showCheckout = true" :disabled="tabs[activeTab].items.length === 0" 
                        class="w-full mt-2 bg-blue-600 text-white p-3 rounded-xl flex flex-col items-center gap-0.5 shadow-lg shadow-blue-100 dark:shadow-none hover:bg-blue-700 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:grayscale transition-all mb-2">
                    <span class="text-[8px] font-black uppercase tracking-[0.2em] opacity-80">Pay Now</span>
                    <span class="text-lg font-black" x-text="'₹' + Number(getGrandTotal()).toFixed(2)"></span>
                </button>
            </div>
        </div>
    </aside>

    <!-- Finalize Order Modal -->
    <div x-show="showCheckout" 
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak>
        
        <div @click.away="showCheckout = false" 
             class="bg-white w-full max-w-md rounded-[2.5rem] overflow-hidden shadow-2xl animate-modalUp relative">
            
            <!-- Modal Header -->
            <div class="bg-slate-900 p-6 text-white flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-black tracking-tight mb-0.5">Finalize Order</h2>
                    <p class="text-blue-100 text-[9px] font-black uppercase tracking-widest">Select Payment Method</p>
                </div>
                <div class="text-right">
                    <p class="text-[9px] font-bold text-blue-200 uppercase mb-0.5">Total Amount</p>
                    <p class="text-2xl font-black" x-text="'₹' + Number(getGrandTotal()).toFixed(2)"></p>
                </div>
            </div>

            <div class="p-10 space-y-8">
                
                <!-- Wallet Usage Option -->
                <div x-show="customerWallet > 0 && !emiStep" class="bg-emerald-50 border border-emerald-100 rounded-2xl p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" x-model="useWallet" class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500 border-gray-300">
                            <span class="text-[11px] font-black text-emerald-800 uppercase tracking-widest">Apply Wallet Balance</span>
                        </div>
                        <span class="text-[11px] font-black text-emerald-600" x-text="'₹' + Number(customerWallet).toFixed(2) + ' Available'"></span>
                    </div>
                    <div x-show="useWallet" class="flex gap-2 items-center animate-fadeIn">
                        <span class="text-[9px] font-black text-emerald-700 uppercase tracking-widest whitespace-nowrap">Amount to use:</span>
                        <div class="relative flex-1">
                            <span class="absolute left-3 top-2 text-sm font-black text-emerald-500">₹</span>
                            <input type="number" x-model.number="walletAmountUsed" :max="Math.min(customerWallet, getGrandTotal())" 
                                   @input="if(walletAmountUsed > customerWallet) walletAmountUsed = customerWallet; if(walletAmountUsed > getGrandTotal()) walletAmountUsed = getGrandTotal();"
                                   class="w-full pl-7 pr-3 py-1.5 bg-white border border-emerald-200 rounded-lg text-sm font-black text-emerald-900 focus:ring-2 focus:ring-emerald-500 outline-none">
                        </div>
                        <button @click="walletAmountUsed = Math.min(customerWallet, getGrandTotal())" class="px-3 py-1.5 bg-emerald-200 text-emerald-800 rounded-lg text-[9px] font-black uppercase hover:bg-emerald-300 transition-all">Max</button>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div x-show="!emiStep" class="grid grid-cols-3 md:grid-cols-5 gap-3">
                    <button @click="paymentMode = 'cash'" 
                            :class="paymentMode === 'cash' ? 'border-slate-900 bg-blue-50/50 ring-4 ring-blue-50' : 'border-slate-100 hover:border-blue-200'"
                            class="flex flex-col items-center justify-center p-4 border-2 rounded-[1.5rem] transition-all gap-2 group">
                        <div class="w-9 h-9 rounded-full bg-slate-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg width="18" height="18" class="text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-widest">Cash</span>
                    </button>
                    <button @click="paymentMode = 'card'" 
                            :class="paymentMode === 'card' ? 'border-slate-900 bg-blue-50/50 ring-4 ring-blue-50' : 'border-slate-100 hover:border-blue-200'"
                            class="flex flex-col items-center justify-center p-4 border-2 rounded-[1.5rem] transition-all gap-2 group">
                        <div class="w-9 h-9 rounded-full bg-slate-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg width="18" height="18" class="text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-widest">Card</span>
                    </button>
                    <button @click="paymentMode = 'upi'" 
                            :class="paymentMode === 'upi' ? 'border-slate-900 bg-blue-50/50 ring-4 ring-blue-50' : 'border-slate-100 hover:border-blue-200'"
                            class="flex flex-col items-center justify-center p-4 border-2 rounded-[1.5rem] transition-all gap-2 group">
                        <div class="w-9 h-9 rounded-full bg-slate-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg width="18" height="18" class="text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h2M4 8h2m1-4h1m15 1h1M4 16h2m8 0h2m-11 4h3m9-15v3m-3.01 0h.01m.01 0h.01M9 20h3.01M8 4h4.01M4 4h4v4H4V4zm12 0h4v4h-4V4zM4 16h4v4H4v-4z"/></svg>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-widest">UPI</span>
                    </button>
                    <button @click="paymentMode = 'credit'" 
                            :class="paymentMode === 'credit' ? 'border-slate-900 bg-blue-50/50 ring-4 ring-blue-50' : 'border-slate-100 hover:border-blue-200'"
                            class="flex flex-col items-center justify-center p-4 border-2 rounded-[1.5rem] transition-all gap-2 group">
                        <div class="w-9 h-9 rounded-full bg-slate-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg width="18" height="18" class="text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-widest">Credit</span>
                    </button>
                    <button @click="paymentMode = 'gift_card'" 
                            :class="paymentMode === 'gift_card' ? 'border-amber-600 bg-amber-50/50 ring-4 ring-amber-100' : 'border-slate-100 hover:border-amber-200'"
                            class="flex flex-col items-center justify-center p-4 border-2 rounded-[1.5rem] transition-all gap-2 group">
                        <div class="w-9 h-9 rounded-full bg-amber-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg width="18" height="18" class="text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V4.5a2.5 2.5 0 115 0v3.5h-5zm0 0H7.5a2.5 2.5 0 110-5H12v5zm-7 4h14a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2z"/>
                            </svg>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-widest text-slate-800">Gift Card</span>
                    </button>
                </div>

                <!-- Gift Card Input & Verification Step -->
                <div x-show="paymentMode === 'gift_card' && !emiStep" class="space-y-3 animate-fadeIn p-5 bg-amber-50/40 rounded-[2rem] border border-amber-200/60">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-base">🎁</span>
                            <span class="text-[10px] font-black uppercase tracking-widest text-amber-900">Gift Card Redemption</span>
                        </div>
                        <span x-show="giftCardApplied" class="text-[10px] font-black text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200">✓ Applied</span>
                    </div>

                    <div>
                        <label class="text-[9px] font-bold text-slate-500 uppercase block mb-1">Voucher Code</label>
                        <input type="text" x-model="giftCardCode" placeholder="Enter Gift Card Code (e.g. GC-XXXX)" 
                               class="w-full px-4 py-2.5 bg-white border border-amber-200 rounded-xl text-xs font-mono font-bold uppercase outline-none focus:ring-2 focus:ring-amber-500">
                    </div>

                    <div x-show="giftCardError" class="p-2.5 bg-rose-50 text-rose-700 text-xs font-bold rounded-xl border border-rose-200" x-text="giftCardError"></div>

                    <div x-show="giftCardApplied" class="p-3 bg-white rounded-xl border border-emerald-200 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700">Verified Card Balance:</span>
                        <span class="text-base font-black text-emerald-600" x-text="'₹' + Number(giftCardAmount).toFixed(2)"></span>
                    </div>

                    <button type="button" @click="verifyAndApplyGiftCard()" :disabled="giftCardLoading"
                            class="w-full py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-xl text-xs font-black uppercase transition-all disabled:opacity-50 shadow-sm">
                        <span x-text="giftCardLoading ? 'Verifying Card...' : (giftCardApplied ? 'Re-verify Card' : 'Verify & Apply Gift Card')"></span>
                    </button>
                </div>

                <!-- Cash Input -->
                <div x-show="paymentMode === 'cash' && !emiStep" class="space-y-4 animate-fadeIn">
                    <div class="relative">
                        <span class="absolute left-6 top-6 text-xl font-black text-slate-300">₹</span>
                        <input type="number" x-model.number="amountPaid" 
                               class="w-full pl-12 pr-6 py-6 bg-slate-50 border-none rounded-[1.5rem] text-3xl font-black text-slate-900 focus:ring-8 focus:ring-blue-50 transition-all outline-none"
                               placeholder="0.00">
                    </div>
                    <div class="flex justify-between items-center px-6">
                        <span class="text-[9px] font-black text-slate-900 uppercase tracking-widest">Return</span>
                        <span class="text-2xl font-black text-emerald-600" x-text="'₹' + Number(getChange()).toFixed(2)"></span>
                    </div>
                </div>

                <!-- Credit/EMI Setup Step -->
                <div x-show="emiStep" class="space-y-6 animate-fadeIn p-6 bg-slate-50 rounded-[2rem] border border-slate-100">
                    <div class="flex items-center justify-between">
                        <h4 class="text-[10px] font-black text-slate-900 uppercase tracking-widest">Instalment Setup</h4>
                        <span class="text-[9px] font-bold text-slate-900 uppercase">Step 2 of 2</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[9px] font-black text-gray-400 uppercase px-1">Months</label>
                            <select x-model="emiMonths" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-xs font-bold outline-none focus:ring-4 focus:ring-blue-50">
                                <option value="1">1</option>
                                <option value="3">3</option>
                                <option value="6">6</option>
                                <option value="12">12</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] font-black text-gray-400 uppercase px-1">Start Date</label>
                            <input type="date" x-model="emiStartDate" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-xs font-bold outline-none focus:ring-4 focus:ring-blue-50">
                        </div>
                    </div>
                    <div class="bg-slate-900 p-4 rounded-2xl text-center">
                        <p class="text-[8px] font-black text-blue-200 uppercase tracking-widest mb-1">Monthly EMI</p>
                        <p class="text-xl font-black text-white" x-text="'₹' + (getGrandTotal() / emiMonths).toFixed(2)"></p>
                    </div>
                    <div class="flex gap-2">
                         <button @click="emiStep = false; showCheckout = false; window.location.reload();" class="flex-1 py-3 bg-slate-200 text-slate-900 rounded-xl text-[9px] font-black uppercase">Skip/Later</button>
                         <button @click="confirmEMI()" class="flex-[2] py-3 bg-slate-900 text-white rounded-xl text-[9px] font-black uppercase shadow-lg shadow-blue-100">Confirm Plan</button>
                    </div>
                </div>

                <!-- Actions -->
                <div x-show="!emiStep" class="flex gap-4 pt-4">
                    <button @click="showCheckout = false" class="flex-1 px-8 py-5 rounded-[2rem] bg-slate-100 text-slate-900 font-black text-[11px] uppercase tracking-widest hover:bg-slate-200 transition-all">Cancel</button>
                    <button @click="submitOrder()" class="flex-[2] px-8 py-5 rounded-[2rem] bg-slate-900 text-white font-black text-[11px] uppercase tracking-widest hover:scale-[1.02] shadow-2xl transition-all">Complete Payment</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('posSystem', () => ({
            products: @json($products),
            categories: @json($categories),
            tabs: [{ id: Date.now(), label: 'Billing 1', items: [], customerPhone: '', customerName: '', customerGstin: '', reminderEnabled: false }],
            activeTab: 0,
            activeCategory: 'All',
            search: '',
            showCheckout: false,
            paymentMode: 'cash',
            amountPaid: 0,
            useWallet: false,
            walletAmountUsed: 0,
            giftCardCode: '',
            giftCardPin: '',
            giftCardApplied: false,
            giftCardAmount: 0,
            giftCardLoading: false,
            giftCardError: '',
            emiStep: false,
            emiMonths: 3,
            emiStartDate: new Date().toISOString().split('T')[0],
            emiBillId: null,
            isValidatingGstin: false,
            gstError: '',
            customerCredit: 0,
            customerWallet: 0,
            lookupTimeout: null,
            
            init() {
                console.log('POS Initialized', this.products.length, 'products');
            },

            async verifyAndApplyGiftCard() {
                if (!this.giftCardCode) {
                    this.giftCardError = 'Enter Gift Card Code';
                    return;
                }
                this.giftCardLoading = true;
                this.giftCardError = '';
                try {
                    const res = await fetch('{{ route('tenant.gift-cards.check') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ code: this.giftCardCode })
                    });
                    const data = await res.json();
                    if (data.success) {
                        const balance = parseFloat(data.current_balance) || 0;
                        if (balance <= 0) {
                            this.giftCardError = 'Gift card has zero balance.';
                            return;
                        }
                        this.giftCardApplied = true;
                        this.giftCardAmount = balance;
                        this.amountPaid = Math.min(balance, this.getGrandTotal());
                    } else {
                        this.giftCardError = data.message || 'Invalid Gift Card';
                        this.giftCardApplied = false;
                    }
                } catch (e) {
                    this.giftCardError = 'Failed to verify gift card';
                    this.giftCardApplied = false;
                } finally {
                    this.giftCardLoading = false;
                }
            },

            addTab() {
                const id = Date.now();
                this.tabs.push({ id: id, label: 'Billing ' + (this.tabs.length + 1), items: [], customerPhone: '', customerName: '', customerGstin: '', reminderEnabled: false });
                this.activeTab = this.tabs.length - 1;
            },

            lookupCustomer() {
                const phone = this.tabs[this.activeTab].customerPhone.trim();
                clearTimeout(this.lookupTimeout);
                
                this.lookupTimeout = setTimeout(async () => {
                    try {
                        const response = await fetch('{{ route("tenant.billing.customer.lookup") }}?phone=' + phone);
                        const data = await response.json();
                        if (data.found) {
                            this.tabs[this.activeTab].customerName = data.customer.name || '';
                            this.tabs[this.activeTab].customerGstin = data.customer.gstin || '';
                            
                            // Load wallet balance & pending credit
                            let totalDue = parseFloat(data.credit_balance) || 0;
                            let walletBal = parseFloat(data.customer.wallet_balance) || 0;
                            
                            this.customerCredit = totalDue;
                            this.customerWallet = walletBal;

                            if (data.was_created) {
                                setTimeout(() => {
                                    const nameInput = document.getElementById('q_customer_name');
                                    if (nameInput) nameInput.focus();
                                }, 100);
                            }
                        } else {
                            this.customerCredit = 0;
                            this.customerWallet = 0;
                            this.useWallet = false;
                            this.walletAmountUsed = 0;
                        }
                    } catch (e) {
                        console.error('Customer lookup failed', e);
                    }
                }, 500);
            },

            async verifyQuickGstin() {
                const gstin = this.tabs[this.activeTab].customerGstin.trim();
                if (gstin.length !== 15) return;
                
                this.isValidatingGstin = true;
                this.gstError = '';
                
                try {
                    const response = await fetch('{{ route('tenant.gst.validate') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ gstin: gstin })
                    });
                    
                    const result = await response.json();
                    if (result.success && result.data) {
                        this.tabs[this.activeTab].customerName = result.data.legal_name || this.tabs[this.activeTab].customerName;
                    } else {
                        this.gstError = result.message || 'Not found';
                    }
                } catch (e) {
                    this.gstError = 'Error';
                } finally {
                    this.isValidatingGstin = false;
                }
            },

            closeTab(index) {
                if (this.tabs.length > 1) {
                    this.tabs.splice(index, 1);
                    if (this.activeTab >= this.tabs.length) this.activeTab = this.tabs.length - 1;
                }
            },

            filteredProducts() {
                return this.products.filter(p => {
                    const term = (this.search || '').toLowerCase();
                    const matchesSearch = p.product_name.toLowerCase().includes(term) || 
                                          (p.barcode && p.barcode.toLowerCase().includes(term)) ||
                                          (p.brand && p.brand.toLowerCase().includes(term));
                    const matchesCat = this.activeCategory === 'All' || p.product_type === this.activeCategory;
                    return matchesSearch && matchesCat;
                });
            },

            addToCart(product) {
                if (product.expiry_date) {
                    const today = new Date().toISOString().split('T')[0];
                    if (product.expiry_date < today) {
                        alert(`⚠️ WARNING: ${product.product_name} has expired on ${product.expiry_date}!`);
                    }
                }
                
                if (product.stock <= (product.low_stock_alert || 0)) {
                    alert(`⚠️ Low Stock Alert: ${product.product_name} (Only ${product.stock} left)`);
                }

                const tab = this.tabs[this.activeTab];
                const existing = tab.items.find(c => c.id === product.id);
                if (existing) {
                    existing.qty++;
                } else {
                    tab.items.push({
                        id: product.id,
                        product_name: product.product_name,
                        mrp: parseFloat(product.mrp || 0),
                        qty: 1
                    });
                }
            },

            updateQty(index, delta) {
                const tab = this.tabs[this.activeTab];
                if (!tab.items[index]) return;
                tab.items[index].qty += delta;
                if (tab.items[index].qty <= 0) tab.items.splice(index, 1);
            },

            getSubtotal() {
                const tab = this.tabs[this.activeTab];
                let total = 0;
                (tab.items || []).forEach(item => {
                    total += (parseFloat(item.mrp) || 0) * (parseInt(item.qty) || 0);
                });
                return total;
            },

            getDiscountAmount() {
                return 0; // Fixed for now
            },

            getTaxAmount() {
                if (!{{ $settings['gst_enabled'] ? 'true' : 'false' }}) return 0;
                const subtotal = this.getSubtotal() - this.getDiscountAmount();
                const gstRate = {{ $settings['default_gst'] ?? 0 }} / 100;
                
                if ("{{ $settings['gst_calc_type'] }}" === 'inclusive') {
                    // Inclusive calculation: Amount * Rate / (1 + Rate)
                    return subtotal * gstRate / (1 + gstRate);
                }
                
                // Exclusive (Direct) calculation
                return subtotal * gstRate;
            },

            getGrandTotal() {
                const subtotal = this.getSubtotal() - this.getDiscountAmount();
                if ("{{ $settings['gst_calc_type'] }}" === 'inclusive') {
                    return subtotal;
                }
                const res = subtotal + this.getTaxAmount();
                return isNaN(res) ? 0 : res;
            },

            getChange() {
                const paid = parseFloat(this.amountPaid) || 0;
                const remainingToPay = this.useWallet ? (this.getGrandTotal() - this.walletAmountUsed) : this.getGrandTotal();
                const change = paid - remainingToPay;
                return change > 0 ? change : 0;
            },

            async submitOrder() {
                const tab = this.tabs[this.activeTab];
                if (tab.items.length === 0) return;
                
                const remainingToPay = this.useWallet ? Math.max(0, this.getGrandTotal() - this.walletAmountUsed) : this.getGrandTotal();
                
                const payload = {
                    customer_phone: tab.customerPhone,
                    customer_gstin: tab.customerGstin,
                    reminder_enabled: tab.reminderEnabled ? 1 : 0,
                    customer_name: tab.customerName,
                    subtotal: this.getSubtotal(),
                    discount_amt: 0,
                    tax_amt: this.getTaxAmount(),
                    gst_percent: {{ $settings['default_gst'] ?? 0 }},
                    grand_total: this.getGrandTotal(),
                    payment_mode: this.paymentMode,
                    amount_paid: this.amountPaid || remainingToPay,
                    wallet_used: this.useWallet ? this.walletAmountUsed : 0,
                    gift_card_code: this.paymentMode === 'gift_card' ? this.giftCardCode : null,
                    items: tab.items.map(i => ({ id: i.id, qty: i.qty, price: i.mrp }))
                };

                try {
                    const response = await fetch('{{ route("tenant.billing.generate") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();
                    if (result.success) {
                        if (this.paymentMode === 'credit') {
                            this.emiBillId = result.bill_id;
                            this.emiStep = true;
                        } else {
                            // ── Printer Settings: open POS and/or A4 based on admin config ──
                            const printPOS = {{ $settings['inv_print_pos'] ? 'true' : 'false' }};
                            const printA4  = {{ $settings['inv_print_a4']  ? 'true' : 'false' }};

                            const posUrl = '{{ route("tenant.billing.print", ":id") }}'.replace(':id', result.bill_id);
                            const a4Url  = '{{ route("tenant.billing.invoice.view", ":id") }}'.replace(':id', result.bill_id);

                            if (printPOS) window.open(posUrl, '_blank');
                            if (printA4)  window.open(a4Url, '_blank_a4');

                            // If neither is enabled, default to POS so the cashier always gets a receipt
                            if (!printPOS && !printA4) window.open(posUrl, '_blank');

                            this.closeTab(this.activeTab);
                            if (this.tabs.length === 0) this.addTab();
                            this.showCheckout = false;
                            window.location.reload();
                        }
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    alert('Submission failed');
                }
            },

            async confirmEMI() {
                try {
                    const response = await fetch('{{ route("tenant.instalments.pay") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            bill_id: this.emiBillId,
                            total_amount: this.getGrandTotal(),
                            months: this.emiMonths,
                            start_date: this.emiStartDate,
                            type: 'create_plan'
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        alert('EMI Plan Created!');
                        const printPOS = {{ $settings['inv_print_pos'] ? 'true' : 'false' }};
                        const printA4  = {{ $settings['inv_print_a4']  ? 'true' : 'false' }};
                        const posUrl = '{{ route("tenant.billing.print", ":id") }}'.replace(':id', this.emiBillId);
                        const a4Url  = '{{ route("tenant.billing.invoice.view", ":id") }}'.replace(':id', this.emiBillId);
                        if (printPOS) window.open(posUrl, '_blank');
                        if (printA4)  window.open(a4Url, '_blank_a4');
                        if (!printPOS && !printA4) window.open(posUrl, '_blank');
                        window.location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (e) {
                    alert('EMI Confirmation failed');
                }
            }
        }));
    });

    // Global Key Listeners
    document.addEventListener('keydown', e => {
        if (e.key === 'F9') {
            e.preventDefault();
            const pos = document.querySelector('[x-data="posSystem"]').__x.$data;
            if (pos.showCheckout) {
                pos.submitOrder();
            } else if (pos.tabs[pos.activeTab].items.length > 0) {
                pos.showCheckout = true;
            }
        }
    });
</script>
@endpush

@push('styles')
<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes modalUp { from { opacity: 0; transform: translateY(40px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
    .animate-fadeIn { animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    .animate-modalUp { animation: modalUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    [x-cloak] { display: none !important; }
</style>
@endpush

