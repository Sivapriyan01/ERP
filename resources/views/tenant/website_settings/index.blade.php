@extends('layouts.tenant')
@section('title', 'Website & Store Settings')
@section('page-title', 'Website Settings')

@section('content')
<div class="space-y-6 animate-in fade-in duration-500"
     x-data="{ 
         activeSection: '{{ request('section', 'all') }}',
         showCreateCouponModal: false, 
         showEditCouponModal: false,
         testPhone: '{{ $settings['website_support_phone'] ?? '6383272563' }}',
         testLoading: false,
         testResult: null,

         
         cashbackPercent: {{ (float) ($settings['wallet_cashback_percent'] ?? 5) }},
         cashbackHoldDays: {{ (int) ($settings['wallet_cashback_hold_days'] ?? 7) }},
         maxWalletPercent: {{ (float) ($settings['wallet_max_usage_percent'] ?? 100) }},
         earnPer100: {{ (float) ($settings['rewards_earn_per_100'] ?? 10) }},
         conversionRate: {{ (float) ($settings['rewards_conversion_rate'] ?? 0.1) }},
         coinsPerRupee: {{ (float) ($settings['rewards_conversion_rate'] ?? 0.1) > 0 ? round(1 / (float) ($settings['rewards_conversion_rate'] ?? 0.1), 1) : 10 }},
         minPointsToConvert: {{ (int) ($settings['rewards_min_conversion_points'] ?? 10) }},
         sampleOrderAmount: 1000,
         resetSuccessMessage: false,
         setCoinsPreset(coins) {
             this.coinsPerRupee = Number(coins);
             this.conversionRate = Number((1 / this.coinsPerRupee).toFixed(4));
         },
         onCoinsInput(val) {
             const c = Number(val);
             if (c > 0) {
                 this.coinsPerRupee = c;
                 this.conversionRate = Number((1 / c).toFixed(4));
             }
         },
         resetRewardsDefaults() {
             this.cashbackPercent = 5;
             this.cashbackHoldDays = 7;
             this.maxWalletPercent = 100;
             this.earnPer100 = 10;
             this.coinsPerRupee = 10;
             this.conversionRate = 0.1;
             this.minPointsToConvert = 10;
             this.resetSuccessMessage = true;
             setTimeout(() => { this.resetSuccessMessage = false; }, 4000);
         },
         async sendTestSms() {
             this.testLoading = true;
             this.testResult = null;
             try {
                 const authKeyInput = document.querySelector('input[name=msg91_auth_key]')?.value || '';
                 const templateIdInput = document.querySelector('input[name=msg91_template_id]')?.value || '';
                 const senderIdInput = document.querySelector('input[name=msg91_dlt_sender_id]')?.value || '';
                 
                 const res = await fetch('{{ route('tenant.website-settings.test-sms') }}', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': '{{ csrf_token() }}',
                         'Accept': 'application/json'
                     },
                     body: JSON.stringify({
                         phone: this.testPhone,
                         auth_key: authKeyInput,
                         template_id: templateIdInput,
                         sender_id: senderIdInput
                     })
                 });
                 const data = await res.json();
                 this.testResult = data;
             } catch (err) {
                 this.testResult = { success: false, message: err.message || 'Network request failed' };
             } finally {
                 this.testLoading = false;
             }
         },
         editingCoupon: {
             id: '',
             code: '',
             name: '',
             description: '',
             discount_type: 'percent',
             discount_value: '',
             min_order_value: 0,
             max_discount: '',
             free_gift_name: '',
             free_gift_min_value: '',
             usage_limit: '',
             is_active: 1
         },
         openEdit(coupon) {
             this.editingCoupon = {
                 id: coupon.id,
                 code: coupon.code,
                 name: coupon.name,
                 description: coupon.description || '',
                 discount_type: coupon.discount_type || 'percent',
                 discount_value: coupon.discount_value,
                 min_order_value: coupon.min_order_value || 0,
                 max_discount: coupon.max_discount || '',
                 free_gift_name: coupon.free_gift_name || '',
                 free_gift_min_value: coupon.free_gift_min_value || '',
                 usage_limit: coupon.usage_limit || '',
                 is_active: coupon.is_active ? 1 : 0
             };
             this.showEditCouponModal = true;
         }
     }">

    {{-- Top Header / Action --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-1">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center shadow-lg shadow-blue-500/25 shrink-0">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Website & Store Settings</h1>
                <p class="text-xs font-bold text-slate-400 dark:text-slate-500">Configure MSG91 OTP gateways, coupons & offers, gift cards, delivery rules, and storefront announcements</p>
            </div>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <a href="http://{{ request()->getHost() }}:5173" target="_blank" class="flex items-center gap-2 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/50 text-blue-600 dark:text-blue-400 px-4 py-2.5 rounded-2xl font-black text-xs uppercase tracking-wider transition-all shadow-sm">
                <span>Storefront :5173</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
            <button type="submit" form="website-settings-form" class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-2xl font-black text-xs uppercase tracking-wider transition-all shadow-md shadow-emerald-500/25 hover:scale-[1.02]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>Save Settings</span>
            </button>
        </div>
    </div>

    {{-- Top Hub: Module Quick Navigation with Separate Distinct Icons --}}
    <div class="glass-card p-2 rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white/70 dark:bg-slate-900/70 backdrop-blur-md shadow-sm">
        <div class="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0 scrollbar-none">
            {{-- Tab 0: All Settings --}}
            <button type="button" @click="activeSection = 'all'"
                :class="activeSection === 'all' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                class="flex items-center gap-2 px-3.5 py-2 rounded-xl font-black text-xs uppercase tracking-wider transition-all shrink-0">
                <div class="w-6 h-6 rounded-lg flex items-center justify-center" :class="activeSection === 'all' ? 'bg-white/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-500'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </div>
                <span>All Overview</span>
            </button>

            {{-- Tab 1: Storefront Identity & Shipping --}}
            <button type="button" @click="activeSection = 'store'"
                :class="activeSection === 'store' ? 'bg-sky-600 text-white shadow-md shadow-sky-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                class="flex items-center gap-2 px-3.5 py-2 rounded-xl font-black text-xs uppercase tracking-wider transition-all shrink-0">
                <div class="w-6 h-6 rounded-lg flex items-center justify-center" :class="activeSection === 'store' ? 'bg-white/20' : 'bg-sky-50 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <span>Store & Shipping</span>
            </button>

            {{-- Tab 2: SMS Gateway & OTP --}}
            <button type="button" @click="activeSection = 'sms'"
                :class="activeSection === 'sms' ? 'bg-purple-600 text-white shadow-md shadow-purple-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                class="flex items-center gap-2 px-3.5 py-2 rounded-xl font-black text-xs uppercase tracking-wider transition-all shrink-0">
                <div class="w-6 h-6 rounded-lg flex items-center justify-center" :class="activeSection === 'sms' ? 'bg-white/20' : 'bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <span>SMS & OTP</span>
            </button>

            {{-- Tab 3: Customer Wallet & Loyalty --}}
            <button type="button" @click="activeSection = 'wallet'"
                :class="activeSection === 'wallet' ? 'bg-amber-500 text-white shadow-md shadow-amber-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                class="flex items-center gap-2 px-3.5 py-2 rounded-xl font-black text-xs uppercase tracking-wider transition-all shrink-0">
                <div class="w-6 h-6 rounded-lg flex items-center justify-center" :class="activeSection === 'wallet' ? 'bg-white/20' : 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <span>Wallet & Loyalty</span>
            </button>

            {{-- Tab 4: Discount Coupons --}}
            <button type="button" @click="activeSection = 'coupons'"
                :class="activeSection === 'coupons' ? 'bg-teal-600 text-white shadow-md shadow-teal-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                class="flex items-center gap-2 px-3.5 py-2 rounded-xl font-black text-xs uppercase tracking-wider transition-all shrink-0">
                <div class="w-6 h-6 rounded-lg flex items-center justify-center" :class="activeSection === 'coupons' ? 'bg-white/20' : 'bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                </div>
                <span>Coupons</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black" :class="activeSection === 'coupons' ? 'bg-white text-teal-600' : 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300'">
                    {{ $coupons->count() }}
                </span>
            </button>

            {{-- Tab 5: Gift Cards & Vouchers --}}
            <button type="button" @click="activeSection = 'gift_cards'"
                :class="activeSection === 'gift_cards' ? 'bg-orange-600 text-white shadow-md shadow-orange-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                class="flex items-center gap-2 px-3.5 py-2 rounded-xl font-black text-xs uppercase tracking-wider transition-all shrink-0">
                <div class="w-6 h-6 rounded-lg flex items-center justify-center" :class="activeSection === 'gift_cards' ? 'bg-white/20' : 'bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V4.5a2.5 2.5 0 115 0v3.5h-5zm0 0H7.5a2.5 2.5 0 110-5H12v5zm-7 4h14a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2z"/></svg>
                </div>
                <span>Gift Cards</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black" :class="activeSection === 'gift_cards' ? 'bg-white text-orange-600' : 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300'">
                    {{ $giftCardStats['active'] ?? 0 }}
                </span>
            </button>


        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-black flex items-center gap-3 animate-in fade-in">
        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    {{-- Main Form --}}
    <form method="POST" action="{{ route('tenant.website-settings.update') }}" id="website-settings-form" class="space-y-8">
        @csrf

        {{-- SECTION 1: STOREFRONT IDENTITY & SHIPPING RULES --}}
        <div x-show="activeSection === 'all' || activeSection === 'store'" class="space-y-6">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-sky-600 text-white flex items-center justify-center shadow-lg shadow-sky-600/20 shrink-0">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base md:text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Storefront Identity & Shipping Rules</h2>
                        <p class="text-xs text-slate-500 font-medium">Store contact details, free shipping threshold, delivery charges, pincode serviceability, and marquee announcement</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-sky-50 text-sky-600 dark:bg-sky-900/30 dark:text-sky-400 border border-sky-200 dark:border-sky-800">
                        <span class="w-2 h-2 rounded-full bg-sky-500 animate-pulse"></span>
                        Live Storefront Sync
                    </span>
                    <a href="http://{{ request()->getHost() }}:5173" target="_blank" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-[10px] font-bold tracking-wider uppercase transition-all flex items-center gap-1">
                        <span>Preview :5173</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Card 1: STOREFRONT IDENTITY & CONTACTS --}}
                <div class="glass-card p-6 md:p-7 rounded-[2.5rem] space-y-5 shadow-sm border border-slate-200/60 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-sky-50 dark:bg-sky-900/40 text-sky-600 dark:text-sky-400 flex items-center justify-center font-black text-sm">
                            1
                        </div>
                        <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">Storefront Identity & Contacts</h3>
                    </div>

                    <div class="space-y-3.5">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Store Name *</label>
                            <input type="text" name="website_store_name" value="{{ old('website_store_name', $settings['website_store_name'] ?? 'Square Store') }}" placeholder="Store Name" required class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500">
                            <p class="text-[9px] text-slate-400 ml-1">Displayed in storefront navbar, invoice headers, and customer receipts</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Support Phone / WA *</label>
                                <input type="text" name="website_support_phone" value="{{ old('website_support_phone', $settings['website_support_phone'] ?? '+91 63832 72563') }}" placeholder="+91 63832 72563" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500">
                                <p class="text-[9px] text-slate-400 ml-1">For WhatsApp order queries</p>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Support Email *</label>
                                <input type="email" name="website_support_email" value="{{ old('website_support_email', $settings['website_support_email'] ?? 'support@squarestore.in') }}" placeholder="support@store.com" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500">
                                <p class="text-[9px] text-slate-400 ml-1">For customer care & notices</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 2: STANDARD SHIPPING & ANNOUNCEMENTS --}}
                <div class="glass-card p-6 md:p-7 rounded-[2.5rem] space-y-5 shadow-sm border border-slate-200/60 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-sky-50 dark:bg-sky-900/40 text-sky-600 dark:text-sky-400 flex items-center justify-center font-black text-sm">
                            2
                        </div>
                        <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">Standard Shipping & Marquee</h3>
                    </div>

                    <div class="space-y-3.5">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Free Shipping Above (₹)</label>
                                <input type="number" name="website_free_delivery_min" value="{{ old('website_free_delivery_min', $settings['website_free_delivery_min'] ?? '999') }}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500">
                                <p class="text-[9px] text-slate-400 ml-1">Orders above this get free delivery</p>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Standard Shipping Fee (₹)</label>
                                <input type="number" name="website_shipping_fee" value="{{ old('website_shipping_fee', $settings['website_shipping_fee'] ?? '40') }}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500">
                                <p class="text-[9px] text-slate-400 ml-1">Charged when order is below threshold</p>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center justify-between ml-1">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Global Catalog Discount (%)</label>
                                <span class="text-[9px] text-slate-400 font-bold">0 = no global discount</span>
                            </div>
                            <div class="relative">
                                <input type="number" step="0.01" min="0" max="100" name="website_discount_percent" value="{{ old('website_discount_percent', $settings['website_discount_percent'] ?? '0') }}" placeholder="0" class="w-full pl-3.5 pr-8 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500">
                                <span class="absolute right-3 top-2 text-slate-400 font-bold text-xs">%</span>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Top Ticker Marquee Announcement</label>
                            <input type="text" name="website_announcement" value="{{ old('website_announcement', $settings['website_announcement'] ?? 'Free miniature gift on orders above ₹899 | Dermatologist Tested') }}" placeholder="Banner text" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500">
                            <p class="text-[9px] text-slate-400 ml-1">Displayed in the top rotating banner across all storefront themes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 2: CORE GATEWAYS & SMS AUTHENTICATION --}}
        <div x-show="activeSection === 'all' || activeSection === 'sms'" class="space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-purple-600 text-white flex items-center justify-center shadow-lg shadow-purple-600/20 shrink-0">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base md:text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">SMS Gateway & OTP Authentication</h2>
                        <p class="text-xs text-slate-500 font-medium">Configure MSG91 credentials, DLT headers, OTP expiry, and test delivery in real-time</p>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 border border-purple-200 dark:border-purple-800">
                    <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span>
                    Direct SMS API Enabled
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                {{-- Card 1: MSG91 OTP WIDGET --}}
                <div class="glass-card p-6 rounded-[2.5rem] space-y-5 shadow-sm border border-slate-200/60 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-black text-sm">
                                1
                            </div>
                            <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">Web OTP Widget</h3>
                        </div>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                            Active
                        </span>
                    </div>

                    <div class="space-y-4">
                        <label class="flex items-center gap-3 cursor-pointer group p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 hover:bg-slate-100 transition-colors">
                            <div class="relative">
                                <input type="hidden" name="website_otp_required" value="0">
                                <input type="checkbox" name="website_otp_required" value="1" {{ ($settings['website_otp_required'] ?? '1') == '1' ? 'checked' : '' }} class="peer hidden">
                                <div class="w-5 h-5 border-2 border-slate-300 dark:border-slate-600 rounded-lg peer-checked:bg-blue-600 peer-checked:border-blue-600 transition-all"></div>
                                <svg class="absolute top-1 left-1 w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <span class="text-xs font-black text-slate-800 dark:text-white block">Enforce Checkout OTP</span>
                                <span class="text-[10px] text-slate-400 font-medium">Verify customer mobile before accepting orders</span>
                            </div>
                        </label>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Widget ID</label>
                            <input type="text" name="msg91_widget_id" value="{{ old('msg91_widget_id', $settings['msg91_widget_id'] ?? '') }}" placeholder="e.g. 36697164476b323432353839" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Token Auth</label>
                            <input type="text" name="msg91_token_auth" value="{{ old('msg91_token_auth', $settings['msg91_token_auth'] ?? '') }}" placeholder="e.g. 572040TDOpHdLN6aab6e64P1" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                {{-- Card 2: SMS GATEWAY (API) & CREDENTIALS --}}
                <div class="glass-card p-6 rounded-[2.5rem] space-y-5 shadow-sm border border-slate-200/60 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center font-black text-sm">
                                2
                            </div>
                            <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">SMS Gateway (API)</h3>
                        </div>
                        <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 rounded-md">DLT / Flow</span>
                    </div>

                    <div class="space-y-3">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">MSG91 Auth Key</label>
                            <input type="text" name="msg91_auth_key" value="{{ old('msg91_auth_key', $settings['msg91_auth_key'] ?? '') }}" placeholder="e.g. 572040TDOpHdLN6aab6e64P1" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Flow / Template ID</label>
                            <input type="text" name="msg91_template_id" value="{{ old('msg91_template_id', $settings['msg91_template_id'] ?? '') }}" placeholder="e.g. 6aae58836219be596d01bc92" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">DLT Sender ID (Header)</label>
                            <input type="text" name="msg91_dlt_sender_id" value="{{ old('msg91_dlt_sender_id', $settings['msg91_dlt_sender_id'] ?? '') }}" placeholder="6-char Header (e.g. SQRECL)" maxlength="10" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none text-slate-900 dark:text-white uppercase focus:ring-2 focus:ring-blue-500 font-mono">
                        </div>

                        <div class="grid grid-cols-2 gap-2.5 pt-1">
                            <div class="space-y-1">
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Expiry (Mins)</label>
                                <input type="number" min="1" max="60" name="msg91_otp_expiry" value="{{ old('msg91_otp_expiry', $settings['msg91_otp_expiry'] ?? '10') }}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none text-slate-900 dark:text-white">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">OTP Length</label>
                                <select name="msg91_otp_length" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none text-slate-900 dark:text-white">
                                    <option value="4" {{ ($settings['msg91_otp_length'] ?? '4') == '4' ? 'selected' : '' }}>4 Digits</option>
                                    <option value="6" {{ ($settings['msg91_otp_length'] ?? '4') == '6' ? 'selected' : '' }}>6 Digits</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 3: LIVE TEST SMS GATEWAY TOOL --}}
                <div class="glass-card p-6 rounded-[2.5rem] space-y-4 shadow-sm border border-blue-500/20 bg-gradient-to-br from-blue-50/30 via-white/50 to-indigo-50/20 dark:from-slate-900/60 dark:to-slate-900/40">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center font-black text-sm shadow-md shadow-blue-500/30">
                                ⚡
                            </div>
                            <div>
                                <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">Test SMS Dispatch</h3>
                                <p class="text-[10px] text-slate-400 font-bold">1-click test to verify Indian carrier delivery</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase ml-1 tracking-widest">Mobile Number to Test</label>
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-black">+91</span>
                                <input type="text" x-model="testPhone" placeholder="6383272563" class="w-full px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <button type="button" @click="sendTestSms()" :disabled="testLoading"
                                class="w-full py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-black uppercase tracking-wider transition-all shadow-md shadow-blue-500/20 flex items-center justify-center gap-2">
                            <template x-if="testLoading">
                                <span class="flex items-center gap-2">
                                    <svg class="animate-spin h-3.5 w-3.5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    Connecting to MSG91...
                                </span>
                            </template>
                            <template x-if="!testLoading">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                    Send Live Test SMS
                                </span>
                            </template>
                        </button>

                        {{-- Real-time Test Output Alert --}}
                        <template x-if="testResult">
                            <div class="p-3 rounded-2xl text-[11px] font-bold border transition-all animate-in fade-in"
                                 :class="testResult.success ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200'">
                                <div class="flex items-start gap-2">
                                    <span x-text="testResult.success ? '✅' : '⚠️'" class="text-sm"></span>
                                    <div class="space-y-1 overflow-hidden">
                                        <p class="font-black" x-text="testResult.message"></p>
                                        <template x-if="testResult.test_otp">
                                            <p class="text-[10px] text-slate-500 dark:text-slate-400">Generated OTP: <strong class="text-slate-900 dark:text-white font-mono" x-text="testResult.test_otp"></strong> (Sent to +91 <span x-text="testResult.phone"></span>)</p>
                                        </template>
                                        <template x-if="testResult.data && testResult.data.request_id">
                                            <p class="text-[9px] font-mono text-slate-400">Req ID: <span x-text="testResult.data.request_id"></span></p>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div class="p-2.5 bg-slate-100/70 dark:bg-slate-800/50 rounded-xl text-[10px] text-slate-500 dark:text-slate-400 space-y-1">
                            <p>💡 <strong>TRAI Rule:</strong> SMS with DLT template ID requires an approved Entity/PE ID and Sender Header on MSG91.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- SECTION 2: CUSTOMER WALLET & LOYALTY REWARDS PROGRAM (NEW DEDICATED SECTION) --}}
        <div x-show="activeSection === 'all' || activeSection === 'wallet'" class="glass-card p-6 md:p-8 rounded-[2.5rem] border-2 border-amber-500/20 space-y-6 bg-gradient-to-br from-amber-50/20 via-white/40 to-yellow-50/10 dark:from-slate-900/60 dark:to-slate-900/40">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 text-white flex items-center justify-center shadow-lg shadow-amber-500/20 shrink-0">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base md:text-lg font-black text-slate-900 dark:text-white tracking-tight uppercase">
                                Customer Wallet & Loyalty Rewards Program
                            </h2>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                SRS 10 & 17
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium">Simple 10 Coins = ₹1.00 loyalty rewards, post-order cashback, and instant wallet redemption</p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap">
                    {{-- 1-Click Reset to Standard Defaults --}}
                    <button type="button" @click="resetRewardsDefaults()"
                            class="flex items-center gap-1.5 px-3.5 py-2.5 rounded-2xl bg-amber-100/90 hover:bg-amber-200 dark:bg-amber-900/40 dark:hover:bg-amber-900/60 text-amber-900 dark:text-amber-200 text-xs font-black uppercase tracking-wider transition-all border border-amber-300/80 dark:border-amber-700 shadow-sm hover:scale-[1.02]">
                        <svg class="w-3.5 h-3.5 text-amber-700 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Reset to Defaults</span>
                    </button>

                    {{-- Wallet Master Toggle --}}
                    <label class="flex items-center gap-3 cursor-pointer p-2.5 px-3.5 rounded-2xl bg-white dark:bg-slate-800 border border-amber-200 dark:border-amber-800/50 shadow-sm">
                        <div class="relative">
                            <input type="hidden" name="wallet_enabled" value="0">
                            <input type="checkbox" name="wallet_enabled" value="1" {{ ($settings['wallet_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="peer hidden">
                            <div class="w-5 h-5 border-2 border-slate-300 dark:border-slate-600 rounded-lg peer-checked:bg-amber-500 peer-checked:border-amber-500 transition-all"></div>
                            <svg class="absolute top-1 left-1 w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <span class="text-xs font-black text-slate-800 dark:text-white block">Enable Wallet System</span>
                            <span class="text-[9px] text-slate-400 font-bold">Storefront & Cart</span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Reset Confirmation Banner --}}
            <template x-if="resetSuccessMessage">
                <div class="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 text-xs font-black flex items-center justify-between animate-in fade-in">
                    <div class="flex items-center gap-2">
                        <span class="text-base">✨</span>
                        <span>Standard e-commerce defaults applied (10 Coins = ₹1.00, 5% Cashback, 7 Days Escrow). Remember to click <strong>"Save Settings"</strong> at the top to save!</span>
                    </div>
                    <button type="button" @click="resetSuccessMessage = false" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-900 text-sm font-black">✕</button>
                </div>
            </template>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 pt-1">
                {{-- Field 1: Order Cashback % --}}
                <div class="space-y-1 bg-white dark:bg-slate-800/80 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm">
                    <div class="flex items-center justify-between">
                        <label class="text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                            Post-Order Cashback (%)
                        </label>
                        <span class="text-[10px] font-bold text-amber-600 bg-amber-50 dark:bg-amber-900/30 px-1.5 py-0.5 rounded">Default: 5%</span>
                    </div>
                    <div class="relative mt-1">
                        <input type="number" step="any" min="0" max="100" name="wallet_cashback_percent" x-model.number="cashbackPercent" value="{{ old('wallet_cashback_percent', $settings['wallet_cashback_percent'] ?? '5') }}"
                            class="w-full pl-3.5 pr-8 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-white outline-none focus:ring-2 focus:ring-amber-500">
                        <span class="absolute right-3 top-2 text-slate-400 font-bold text-xs">%</span>
                    </div>
                    <p class="text-[10px] text-slate-400">Percentage of order total credited back to customer wallet</p>
                </div>

                {{-- Field 2: Cashback Escrow Hold Days --}}
                <div class="space-y-1 bg-white dark:bg-slate-800/80 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm">
                    <div class="flex items-center justify-between">
                        <label class="text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                            Cashback Hold Days (Escrow)
                        </label>
                        <span class="text-[10px] font-bold text-slate-500 bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded">Default: 7 Days</span>
                    </div>
                    <div class="relative mt-1">
                        <input type="number" min="0" max="365" step="1" name="wallet_cashback_hold_days" x-model.number="cashbackHoldDays" value="{{ old('wallet_cashback_hold_days', $settings['wallet_cashback_hold_days'] ?? '7') }}"
                            class="w-full pl-3.5 pr-14 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-white outline-none focus:ring-2 focus:ring-amber-500">
                        <span class="absolute right-3 top-2 text-slate-400 font-bold text-xs">Days</span>
                    </div>
                    <p class="text-[10px] text-slate-400">Cashback matures into spendable balance after return period</p>
                </div>

                {{-- Field 3: Max Wallet Deduction % --}}
                <div class="space-y-1 bg-white dark:bg-slate-800/80 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm">
                    <div class="flex items-center justify-between">
                        <label class="text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                            Max Wallet Usage per Order
                        </label>
                        <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 px-1.5 py-0.5 rounded">Default: 100%</span>
                    </div>
                    <div class="relative mt-1">
                        <input type="number" min="1" max="100" step="any" name="wallet_max_usage_percent" x-model.number="maxWalletPercent" value="{{ old('wallet_max_usage_percent', $settings['wallet_max_usage_percent'] ?? '100') }}"
                            class="w-full pl-3.5 pr-8 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-white outline-none focus:ring-2 focus:ring-amber-500">
                        <span class="absolute right-3 top-2 text-slate-400 font-bold text-xs">%</span>
                    </div>
                    <p class="text-[10px] text-slate-400">100% allows customers to pay full bill from wallet</p>
                </div>

                {{-- Field 4: Reward Points Earn Rate --}}
                <div class="space-y-1 bg-white dark:bg-slate-800/80 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm">
                    <div class="flex items-center justify-between">
                        <label class="text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                            Coins Earned per ₹100 Spent
                        </label>
                        <span class="text-[10px] font-bold text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 px-1.5 py-0.5 rounded">Default: 10 Coins</span>
                    </div>
                    <div class="relative mt-1">
                        <input type="number" min="0" step="any" name="rewards_earn_per_100" x-model.number="earnPer100" value="{{ old('rewards_earn_per_100', $settings['rewards_earn_per_100'] ?? '10') }}"
                            class="w-full pl-3.5 pr-14 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-white outline-none focus:ring-2 focus:ring-amber-500">
                        <span class="absolute right-3 top-2 text-slate-400 font-bold text-xs">Coins</span>
                    </div>
                    <p class="text-[10px] text-slate-400">e.g. 10 coins per ₹100 = 100 coins on ₹1,000 order (10% reward)</p>
                </div>

                {{-- Field 5: Coin Conversion Rate (Human-Friendly with 1-Click Presets) --}}
                <div class="space-y-2 bg-white dark:bg-slate-800/80 p-4 rounded-2xl border-2 border-amber-400/50 dark:border-amber-600/50 shadow-sm relative">
                    <div class="flex items-center justify-between">
                        <label class="text-[11px] font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                            Coins Needed for ₹1.00 Value
                        </label>
                        <span class="text-[10px] font-black text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/40 px-2 py-0.5 rounded-full">
                            Coin Rate
                        </span>
                    </div>

                    {{-- 1-Click Presets --}}
                    <div class="grid grid-cols-3 gap-1.5">
                        <button type="button" @click="setCoinsPreset(10)"
                                :class="coinsPerRupee === 10 ? 'bg-amber-500 text-white shadow-sm ring-2 ring-amber-400' : 'bg-slate-100 dark:bg-slate-700/60 text-slate-700 dark:text-slate-300 hover:bg-amber-50'"
                                class="py-1 px-1.5 rounded-xl text-[10px] font-black transition-all text-center">
                            10 Coins = ₹1
                            <span class="block text-[8px] font-normal opacity-90">(Recommended)</span>
                        </button>
                        <button type="button" @click="setCoinsPreset(5)"
                                :class="coinsPerRupee === 5 ? 'bg-amber-500 text-white shadow-sm ring-2 ring-amber-400' : 'bg-slate-100 dark:bg-slate-700/60 text-slate-700 dark:text-slate-300 hover:bg-amber-50'"
                                class="py-1 px-1.5 rounded-xl text-[10px] font-black transition-all text-center">
                            5 Coins = ₹1
                            <span class="block text-[8px] font-normal opacity-90">(2x Value)</span>
                        </button>
                        <button type="button" @click="setCoinsPreset(1)"
                                :class="coinsPerRupee === 1 ? 'bg-amber-500 text-white shadow-sm ring-2 ring-amber-400' : 'bg-slate-100 dark:bg-slate-700/60 text-slate-700 dark:text-slate-300 hover:bg-amber-50'"
                                class="py-1 px-1.5 rounded-xl text-[10px] font-black transition-all text-center">
                            1 Coin = ₹1
                            <span class="block text-[8px] font-normal opacity-90">(1:1 Ratio)</span>
                        </button>
                    </div>

                    <div class="relative">
                        <input type="number" min="0.1" max="1000" step="any" name="rewards_coins_per_rupee" x-model.number="coinsPerRupee" @input="onCoinsInput($event.target.value)"
                            class="w-full pl-3.5 pr-20 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-black text-slate-900 dark:text-white outline-none focus:ring-2 focus:ring-amber-500">
                        <span class="absolute right-3 top-2 text-slate-500 font-bold text-xs">Coins = ₹1</span>
                    </div>

                    {{-- Synchronized Backend Hidden Input --}}
                    <input type="hidden" name="rewards_conversion_rate" :value="conversionRate">

                    <div class="p-2 bg-amber-50 dark:bg-amber-950/30 rounded-xl border border-amber-200/60 dark:border-amber-900/40 text-[10px] font-bold text-amber-900 dark:text-amber-200 space-y-0.5">
                        <p>👉 <strong><span x-text="coinsPerRupee"></span> Coins = ₹1.00</strong> wallet credit</p>
                        <p class="text-slate-500 dark:text-slate-400 text-[9px]">100 Coins = <strong>₹<span x-text="(100 * conversionRate).toFixed(2)"></span></strong> | 1,000 Coins = <strong>₹<span x-text="(1000 * conversionRate).toFixed(2)"></span></strong></p>
                    </div>
                </div>

                {{-- Field 6: Minimum Points to Convert --}}
                <div class="space-y-1 bg-white dark:bg-slate-800/80 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm">
                    <div class="flex items-center justify-between">
                        <label class="text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                            Min Coins to Redeem
                        </label>
                        <span class="text-[10px] font-bold text-slate-500 bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded">Default: 10</span>
                    </div>
                    <div class="relative mt-1">
                        <input type="number" min="1" step="1" name="rewards_min_conversion_points" x-model.number="minPointsToConvert" value="{{ old('rewards_min_conversion_points', $settings['rewards_min_conversion_points'] ?? '10') }}"
                            class="w-full pl-3.5 pr-14 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-white outline-none focus:ring-2 focus:ring-amber-500">
                        <span class="absolute right-3 top-2 text-slate-400 font-bold text-xs">Coins</span>
                    </div>
                    <p class="text-[10px] text-slate-400">Customer accumulates at least this many coins before converting</p>
                </div>
            </div>

        </div>

        {{-- SECTION 4: THE DERMA CO STYLE PROMOTIONAL DRAWER & CART METERS --}}
        <div x-show="activeSection === 'all' || activeSection === 'coupons'" class="glass-card p-6 md:p-8 rounded-[2.5rem] border-2 border-teal-500/20 space-y-6 bg-gradient-to-br from-teal-50/20 via-white/40 to-emerald-50/20 dark:from-slate-900/60 dark:to-slate-900/40">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-teal-500 text-white flex items-center justify-center shadow-lg shadow-teal-500/20">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base md:text-lg font-black text-slate-900 dark:text-white tracking-tight uppercase">
                            Coupon Drawer & Promotional Offer Rules
                        </h2>
                        <p class="text-xs text-slate-500">Edit the title, subtitle, free delivery/gift meters, and extra prepaid discounts displayed in the storefront</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="submit" form="seed-coupons-form" class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-teal-50 hover:bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300 font-bold text-xs border border-teal-200 dark:border-teal-800 transition-all shadow-sm">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Seed Defaults</span>
                    </button>
                    <button type="button" @click="showCreateCouponModal = true" class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-black text-xs transition-all shadow-md shadow-teal-500/20">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>+ Add New Coupon</span>
                    </button>
                    <a href="{{ route('tenant.coupons.index') }}" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition-all">
                        Full Coupon Hub ➔
                    </a>
                </div>
            </div>

            {{-- 6 Configurable Dynamic Fields --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                <div class="space-y-1">
                    <label class="text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider ml-1">
                        Apply Coupon Modal Title
                    </label>
                    <input type="text" name="website_coupon_modal_title" value="{{ old('website_coupon_modal_title', $settings['website_coupon_modal_title']) }}"
                        placeholder="e.g. Apply Coupon & Offers"
                        class="w-full px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none">
                    <p class="text-[10px] text-slate-400 ml-1">Top heading of the popup drawer (e.g. Apply Coupon & Offers)</p>
                </div>

                <div class="space-y-1">
                    <label class="text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider ml-1">
                        Coupon Modal Subtitle / Tagline
                    </label>
                    <input type="text" name="website_coupon_modal_subtitle" value="{{ old('website_coupon_modal_subtitle', $settings['website_coupon_modal_subtitle']) }}"
                        placeholder="e.g. The Derma Co Verified Promo Codes"
                        class="w-full px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none">
                    <p class="text-[10px] text-slate-400 ml-1">Secondary text displayed below the modal title</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="space-y-1">
                    <label class="text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider ml-1">
                        Free Delivery Meter (₹)
                    </label>
                    <input type="number" step="1" name="website_free_shipping_threshold" value="{{ old('website_free_shipping_threshold', $settings['website_free_shipping_threshold']) }}"
                        placeholder="999"
                        class="w-full px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none">
                    <p class="text-[10px] text-slate-400 ml-1">Free delivery progress bar trigger</p>
                </div>

                <div class="space-y-1">
                    <label class="text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider ml-1">
                        Free Gift Unlock Meter (₹)
                    </label>
                    <input type="number" step="1" name="website_free_gift_threshold" value="{{ old('website_free_gift_threshold', $settings['website_free_gift_threshold']) }}"
                        placeholder="899"
                        class="w-full px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none">
                    <p class="text-[10px] text-slate-400 ml-1">Miniature sample unlock threshold</p>
                </div>

                <div class="space-y-1">
                    <label class="text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider ml-1">
                        Free Miniature Item Name
                    </label>
                    <input type="text" name="website_free_gift_name" value="{{ old('website_free_gift_name', $settings['website_free_gift_name']) }}"
                        placeholder="Free 10% Niacinamide Serum Mini 5ml"
                        class="w-full px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none">
                    <p class="text-[10px] text-slate-400 ml-1">Item added free when threshold reached</p>
                </div>

                <div class="space-y-1">
                    <label class="text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider ml-1">
                        Prepaid UPI / Card Discount (%)
                    </label>
                    <div class="relative">
                        <input type="number" step="0.5" name="website_prepaid_discount_percent" value="{{ old('website_prepaid_discount_percent', $settings['website_prepaid_discount_percent']) }}"
                            placeholder="5"
                            class="w-full pl-4 pr-8 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none">
                        <span class="absolute right-3 top-2.5 text-slate-400 font-bold text-xs">%</span>
                    </div>
                    <p class="text-[10px] text-slate-400 ml-1">Instant extra discount for online orders</p>
                </div>
            </div>

            {{-- Embedded Dynamic Coupons Table --}}
            <div class="mt-6 pt-4 border-t border-slate-200/60 dark:border-slate-800">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>Active Website Coupons ({{ $coupons->count() }})</span>
                        <span class="text-[10px] font-bold text-teal-600 bg-teal-50 dark:bg-teal-900/30 px-2 py-0.5 rounded-md">Live Sync</span>
                    </h3>
                    <span class="text-[11px] text-slate-400 font-bold">Click "Edit" on any coupon to dynamically update code, title, terms, or perks</span>
                </div>

                <div class="overflow-x-auto bg-white/70 dark:bg-slate-850 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <table class="w-full text-left text-xs font-bold text-slate-600 dark:text-slate-400">
                        <thead class="bg-slate-50 dark:bg-slate-800 text-[10px] text-slate-400 uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-4 rounded-l-xl">Code</th>
                                <th class="py-3 px-4">Headline / Title</th>
                                <th class="py-3 px-4">Discount</th>
                                <th class="py-3 px-4">Min Order</th>
                                <th class="py-3 px-4">Free Gift / Perk Tag</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 rounded-r-xl text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($coupons as $c)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="py-3 px-4">
                                        <span class="px-2.5 py-1 rounded-lg bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 font-black tracking-wider text-xs border border-teal-200 dark:border-teal-800/50">
                                            {{ $c->code }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <p class="text-xs font-bold text-slate-900 dark:text-white">{{ $c->name }}</p>
                                        @if($c->description)
                                            <p class="text-[10px] text-slate-400 line-clamp-1">{{ $c->description }}</p>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 font-black text-slate-900 dark:text-white">
                                        @if($c->discount_type === 'percent')
                                            <span class="text-emerald-600">{{ $c->discount_value }}% OFF</span>
                                            @if($c->max_discount)
                                                <span class="block text-[10px] text-slate-400 font-semibold">Max: ₹{{ number_format($c->max_discount) }}</span>
                                            @endif
                                        @else
                                            <span class="text-emerald-600">₹{{ number_format($c->discount_value) }} FLAT</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        {{ $c->min_order_value > 0 ? ('₹' . number_format($c->min_order_value)) : 'No Min' }}
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($c->free_gift_name)
                                            <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400">
                                                🎁 {{ $c->free_gift_name }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($c->is_active)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-600 border border-emerald-200">Active</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-50 text-rose-600 border border-rose-200">Paused</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" @click="openEdit({{ json_encode($c) }})"
                                                    class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 font-bold transition-colors">
                                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                <span>Edit</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-10 text-center">
                                        <div class="flex flex-col items-center justify-center gap-3">
                                            <div class="w-12 h-12 rounded-2xl bg-teal-50 dark:bg-teal-900/30 text-teal-600 flex items-center justify-center">
                                                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                            </div>
                                            <div>
                                                <p class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-wider">No Website Coupons Configured</p>
                                                <p class="text-[11px] text-slate-400 mt-0.5">Add your custom promotional discounts or populate ready-to-use Derma Co starter codes.</p>
                                            </div>
                                            <div class="flex items-center gap-2.5 mt-1">
                                                <button type="submit" form="seed-coupons-form" class="px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-black text-xs transition-all shadow-md shadow-teal-500/20 flex items-center gap-1.5">
                                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                                    <span>Load 4 Starter Coupons (FLAT30, REDEEM35, etc.)</span>
                                                </button>
                                                <button type="button" @click="showCreateCouponModal = true" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs transition-all">
                                                    + Create Custom Coupon
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- SECTION 5: GIFT CARDS & DIGITAL VOUCHERS (ONLINE & POS) --}}
        <div x-show="activeSection === 'all' || activeSection === 'gift_cards'" class="glass-card p-6 md:p-8 rounded-[2.5rem] border-2 border-orange-500/20 space-y-6 bg-gradient-to-br from-amber-50/20 via-white/40 to-orange-50/20 dark:from-slate-900/60 dark:to-slate-900/40">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center shadow-lg shadow-amber-500/20">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V4.5a2.5 2.5 0 115 0v3.5h-5zm0 0H7.5a2.5 2.5 0 110-5H12v5zm-7 4h14a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base md:text-lg font-black text-slate-900 dark:text-white tracking-tight uppercase">
                            Gift Cards & Digital Vouchers
                        </h2>
                        <p class="text-xs text-slate-500">Configure online storefront voucher redemption, POS checkout applicability, and view active customer balances</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="submit" form="seed-gift-cards-form" class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 font-bold text-xs border border-amber-200 dark:border-amber-800 transition-all shadow-sm">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Seed Vouchers</span>
                    </button>
                    <a href="{{ route('tenant.gift-cards.index') }}" class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-black text-xs transition-all shadow-md shadow-amber-500/20">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>+ Issue Gift Card</span>
                    </a>
                    <a href="{{ route('tenant.gift-cards.index') }}" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition-all">
                        Full Gift Card Hub ➔
                    </a>
                </div>
            </div>

            {{-- 4 Metric Summary Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-4 rounded-2xl bg-white/80 dark:bg-slate-800/80 border border-amber-200 dark:border-amber-800/40">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Active Cards</span>
                    <span class="text-xl font-black text-amber-600 dark:text-amber-400 mt-1 block">{{ $giftCardStats['active'] ?? 0 }}</span>
                    <span class="text-[9px] text-slate-400">Available for customer use</span>
                </div>
                <div class="p-4 rounded-2xl bg-white/80 dark:bg-slate-800/80 border border-amber-200 dark:border-amber-800/40">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Redeemable Balance</span>
                    <span class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1 block">₹{{ number_format($giftCardStats['balance'] ?? 0, 2) }}</span>
                    <span class="text-[9px] text-slate-400">Total unspent card credit</span>
                </div>
                <div class="p-4 rounded-2xl bg-white/80 dark:bg-slate-800/80 border border-amber-200 dark:border-amber-800/40">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Issued</span>
                    <span class="text-xl font-black text-slate-900 dark:text-white mt-1 block">{{ $giftCardStats['total'] ?? 0 }}</span>
                    <span class="text-[9px] text-slate-400">Generated vouchers</span>
                </div>
                <div class="p-4 rounded-2xl bg-white/80 dark:bg-slate-800/80 border border-amber-200 dark:border-amber-800/40">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Redeemed Cards</span>
                    <span class="text-xl font-black text-blue-600 dark:text-blue-400 mt-1 block">{{ $giftCardStats['redeemed'] ?? 0 }}</span>
                    <span class="text-[9px] text-slate-400">Utilized by shoppers</span>
                </div>
            </div>

            {{-- Gift Card Settings Inputs & Toggles --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                {{-- Toggle 1: Enable Gift Card System --}}
                <label class="p-3.5 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 flex items-center justify-between cursor-pointer hover:border-amber-300 transition-colors">
                    <div>
                        <span class="text-xs font-black text-slate-900 dark:text-white block">Enable Gift Cards</span>
                        <span class="text-[10px] text-slate-400 font-medium">Activate voucher functionality storewide</span>
                    </div>
                    <input type="checkbox" name="website_gift_card_enabled" value="1"
                           {{ ($settings['website_gift_card_enabled'] ?? '1') === '1' ? 'checked' : '' }}
                           class="w-5 h-5 rounded-md text-amber-600 focus:ring-amber-500 cursor-pointer">
                </label>

                {{-- Toggle 2: Storefront Checkout Redemption --}}
                <label class="p-3.5 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 flex items-center justify-between cursor-pointer hover:border-amber-300 transition-colors">
                    <div>
                        <span class="text-xs font-black text-slate-900 dark:text-white block">Online Storefront Redemption</span>
                        <span class="text-[10px] text-slate-400 font-medium">Allow customers to pay via gift card code at checkout</span>
                    </div>
                    <input type="checkbox" name="website_gift_card_checkout_redeem" value="1"
                           {{ ($settings['website_gift_card_checkout_redeem'] ?? '1') === '1' ? 'checked' : '' }}
                           class="w-5 h-5 rounded-md text-amber-600 focus:ring-amber-500 cursor-pointer">
                </label>

                {{-- Toggle 3: POS Quick Billing Redemption --}}
                <label class="p-3.5 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 flex items-center justify-between cursor-pointer hover:border-amber-300 transition-colors">
                    <div>
                        <span class="text-xs font-black text-slate-900 dark:text-white block">POS Quick Billing Redemption</span>
                        <span class="text-[10px] text-slate-400 font-medium">Allow cashiers to redeem gift cards during billing</span>
                    </div>
                    <input type="checkbox" name="website_gift_card_pos_redeem" value="1"
                           {{ ($settings['website_gift_card_pos_redeem'] ?? '1') === '1' ? 'checked' : '' }}
                           class="w-5 h-5 rounded-md text-amber-600 focus:ring-amber-500 cursor-pointer">
                </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="space-y-1">
                    <label class="text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider ml-1">
                        Default Validity (Days)
                    </label>
                    <input type="number" step="1" name="website_gift_card_default_validity_days" value="{{ old('website_gift_card_default_validity_days', $settings['website_gift_card_default_validity_days'] ?? '365') }}"
                        placeholder="365"
                        class="w-full px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 outline-none">
                    <p class="text-[10px] text-slate-400 ml-1">Number of days before issued card expires (e.g. 365 = 1 year)</p>
                </div>

                <div class="space-y-1">
                    <label class="text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider ml-1">
                        Minimum Purchase Amount (₹)
                    </label>
                    <input type="number" step="1" name="website_gift_card_min_purchase" value="{{ old('website_gift_card_min_purchase', $settings['website_gift_card_min_purchase'] ?? '100') }}"
                        placeholder="100"
                        class="w-full px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 outline-none">
                    <p class="text-[10px] text-slate-400 ml-1">Minimum denomination allowed when generating gift cards</p>
                </div>

                <div class="space-y-1">
                    <label class="text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider ml-1">
                        Maximum Card Value (₹)
                    </label>
                    <input type="number" step="1" name="website_gift_card_max_purchase" value="{{ old('website_gift_card_max_purchase', $settings['website_gift_card_max_purchase'] ?? '10000') }}"
                        placeholder="10000"
                        class="w-full px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 outline-none">
                    <p class="text-[10px] text-slate-400 ml-1">Maximum single card balance cap for risk protection</p>
                </div>
            </div>

            {{-- Recent Active Gift Cards Preview Table --}}
            <div class="mt-6 pt-4 border-t border-slate-200/60 dark:border-slate-800">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>Recent Active Gift Cards ({{ $giftCards->count() }})</span>
                        <span class="text-[10px] font-bold text-amber-600 bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 rounded-md">Live Balances</span>
                    </h3>
                    <a href="{{ route('tenant.gift-cards.index') }}" class="text-[11px] text-amber-600 dark:text-amber-400 font-bold hover:underline">
                        View All {{ $giftCardStats['total'] ?? 0 }} Cards ➔
                    </a>
                </div>

                <div class="overflow-x-auto bg-white/70 dark:bg-slate-850 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <table class="w-full text-left text-xs font-bold text-slate-600 dark:text-slate-400">
                        <thead class="bg-slate-50 dark:bg-slate-800 text-[10px] text-slate-400 uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-4 rounded-l-xl">Card Code</th>
                                <th class="py-3 px-4">Initial Value</th>
                                <th class="py-3 px-4">Current Balance</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Expires</th>
                                <th class="py-3 px-4 rounded-r-xl text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($giftCards as $card)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="py-3 px-4 font-mono font-black text-slate-900 dark:text-white tracking-wider">
                                        {{ $card->code }}
                                    </td>
                                    <td class="py-3 px-4 text-slate-500">
                                        ₹{{ number_format($card->initial_amount, 2) }}
                                    </td>
                                    <td class="py-3 px-4 font-black text-emerald-600 dark:text-emerald-400">
                                        ₹{{ number_format($card->current_balance, 2) }}
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($card->isLocked())
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-50 text-rose-600 border border-rose-200">Locked</span>
                                        @elseif($card->status === 'active')
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-600 border border-emerald-200">Active</span>
                                        @elseif($card->status === 'redeemed')
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-50 text-blue-600 border border-blue-200">Redeemed</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-100 text-slate-600">{{ ucfirst($card->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-slate-400 text-[11px]">
                                        {{ $card->expires_at ? $card->expires_at->format('M d, Y') : 'Never' }}
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <a href="{{ route('tenant.gift-cards.index', ['search' => $card->code]) }}"
                                           class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 font-bold transition-colors">
                                            <span>Manage</span>
                                            <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-10 text-center">
                                        <div class="flex flex-col items-center justify-center gap-3">
                                            <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 flex items-center justify-center">
                                                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V4.5a2.5 2.5 0 115 0v3.5h-5zm0 0H7.5a2.5 2.5 0 110-5H12v5zm-7 4h14a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2z"/></svg>
                                            </div>
                                            <div>
                                                <p class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-wider">No Active Gift Vouchers Found</p>
                                                <p class="text-[11px] text-slate-400 mt-0.5">Generate your first batch of retail gift cards or populate ready-to-use demo balances.</p>
                                            </div>
                                            <div class="flex items-center gap-2.5 mt-1">
                                                <button type="submit" form="seed-gift-cards-form" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-black text-xs transition-all shadow-md shadow-amber-500/20 flex items-center gap-1.5">
                                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                                    <span>Load 3 Starter Vouchers (₹500, ₹1000, ₹2500)</span>
                                                </button>
                                                <a href="{{ route('tenant.gift-cards.index') }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs transition-all">
                                                    + Issue New Gift Card
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>





        {{-- Bottom Action Bar --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-5 glass-card rounded-[2rem] shadow-sm sticky bottom-6 z-20 backdrop-blur-md">
            <div class="flex items-center gap-3">
                <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs text-slate-500 dark:text-slate-400 font-bold">All settings & promotional rules will instantly sync with the storefront</span>
            </div>
            <button type="submit" class="px-8 py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all shadow-xl shadow-blue-500/20 hover:scale-[1.02]">
                Save Website Settings
            </button>
        </div>
    </form>

    {{-- Standalone Seeding Forms (Placed outside website-settings-form to avoid invalid nested form tags) --}}
    <form id="seed-coupons-form" method="POST" action="{{ route('tenant.website-settings.seed-samples') }}" class="hidden">
        @csrf
        <input type="hidden" name="type" value="coupons">
    </form>

    <form id="seed-gift-cards-form" method="POST" action="{{ route('tenant.website-settings.seed-samples') }}" class="hidden">
        @csrf
        <input type="hidden" name="type" value="gift_cards">
    </form>

    {{-- Edit Coupon Modal --}}
    <div x-show="showEditCouponModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="showEditCouponModal = false" class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 md:p-8 space-y-6 shadow-2xl border border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-black text-slate-900 dark:text-white">Edit Coupon Details</h3>
                    <p class="text-xs text-slate-400">Changes immediately reflect in the storefront Apply Coupon drawer</p>
                </div>
                <button type="button" @click="showEditCouponModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form :action="'/coupons/' + editingCoupon.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Coupon Code *</label>
                        <input type="text" name="code" x-model="editingCoupon.code" required
                            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white uppercase focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Discount Type *</label>
                        <select name="discount_type" x-model="editingCoupon.discount_type" required class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                            <option value="percent">Percentage (% OFF)</option>
                            <option value="fixed">Fixed Amount (₹ FLAT)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Discount Value *</label>
                        <input type="number" step="0.01" name="discount_value" x-model="editingCoupon.discount_value" required
                            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Min Order Value (₹)</label>
                        <input type="number" step="1" name="min_order_value" x-model="editingCoupon.min_order_value"
                            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Max Discount Cap (₹)</label>
                        <input type="number" step="1" name="max_discount" x-model="editingCoupon.max_discount" placeholder="e.g. 600"
                            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Headline / Name *</label>
                        <input type="text" name="name" x-model="editingCoupon.name" placeholder="e.g. Redeem 35% Special" required
                            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Free Gift Perk Tag (Optional)</label>
                    <input type="text" name="free_gift_name" x-model="editingCoupon.free_gift_name" placeholder="e.g. Free 10% Niacinamide Serum Mini 5ml"
                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Description / Conditions</label>
                    <textarea name="description" rows="2" x-model="editingCoupon.description" placeholder="e.g. Save 35% on orders above ₹899 + Free Miniature Serum."
                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4 items-center">
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Usage Limit</label>
                        <input type="number" step="1" name="usage_limit" x-model="editingCoupon.usage_limit" placeholder="Unlimited if empty"
                            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Status</label>
                        <select name="is_active" x-model="editingCoupon.is_active" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                            <option value="1">Active (Live in Storefront)</option>
                            <option value="0">Paused / Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showEditCouponModal = false" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-black bg-teal-600 hover:bg-teal-700 text-white shadow-lg shadow-teal-200">Save Coupon</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Create Coupon Modal --}}
    <div x-show="showCreateCouponModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="showCreateCouponModal = false" class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 md:p-8 space-y-6 shadow-2xl border border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-black text-slate-900 dark:text-white">Create New Coupon</h3>
                <button type="button" @click="showCreateCouponModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form action="{{ route('tenant.coupons.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Coupon Code *</label>
                        <input type="text" name="code" placeholder="e.g. FLAT30" required
                            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white uppercase focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Discount Type *</label>
                        <select name="discount_type" required class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                            <option value="percent">Percentage (% OFF)</option>
                            <option value="fixed">Fixed Amount (₹ FLAT)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Discount Value *</label>
                        <input type="number" step="0.01" name="discount_value" placeholder="e.g. 30" required
                            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Min Order Value (₹)</label>
                        <input type="number" step="1" name="min_order_value" placeholder="e.g. 1499"
                            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Max Discount Cap (₹)</label>
                        <input type="number" step="1" name="max_discount" placeholder="e.g. 1000"
                            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Headline / Name *</label>
                        <input type="text" name="name" placeholder="e.g. Flat 30% Off Skincare" required
                            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Free Gift Perk Tag (Optional)</label>
                    <input type="text" name="free_gift_name" placeholder="e.g. Free 10g Sunscreen Miniature"
                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Description / Conditions</label>
                    <textarea name="description" rows="2" placeholder="e.g. Applicable on orders above ₹1,499. Cannot be combined with other offers."
                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showCreateCouponModal = false" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-black bg-teal-600 hover:bg-teal-700 text-white shadow-lg shadow-teal-200">Save Coupon</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
