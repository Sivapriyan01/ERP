@extends('layouts.tenant')
@section('title', 'Discount Coupons & Offers')
@section('page-title', 'Discount Coupons & Offers')

@section('content')
<div class="space-y-6 md:space-y-8 animate-in fade-in duration-500" 
     x-data="{ 
         showCreateModal: false, 
         showEditModal: false,
         showSettingsDrawer: false,
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
             this.showEditModal = true;
         }
     }">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-teal-50 dark:bg-teal-900/20 text-teal-600 dark:text-teal-400 flex items-center justify-center shadow-sm">
                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Coupons & Promo Offers</h1>
                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500">The Derma Co style discount codes, free gift unlocks & storefront coupon modal</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button @click="showSettingsDrawer = !showSettingsDrawer" class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 px-4 py-2.5 rounded-2xl font-black text-xs tracking-wide transition-all">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span x-text="showSettingsDrawer ? 'Hide Modal Settings' : 'Edit Modal Titles & Rules'">Edit Modal Titles & Rules</span>
            </button>
            <button @click="showCreateModal = true" class="flex items-center gap-2 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white px-5 py-2.5 rounded-2xl font-black text-xs tracking-wide shadow-lg shadow-teal-200 dark:shadow-none hover:scale-[1.02] transition-all">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Create New Coupon</span>
            </button>
        </div>
    </div>

    {{-- Storefront Modal & Offer Settings Box --}}
    <div x-show="showSettingsDrawer" x-cloak class="glass-card p-6 md:p-8 rounded-[2.5rem] border-2 border-teal-500/20 space-y-6 bg-teal-50/20 dark:bg-slate-900/60">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-teal-100 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300 flex items-center justify-center font-bold">
                    ⚙️
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Storefront Coupon Modal & Promotional Rules</h3>
                    <p class="text-xs text-slate-500">Edit the modal title, verified promo subline, free delivery and gift unlock thresholds shown to shoppers.</p>
                </div>
            </div>
            <button @click="showSettingsDrawer = false" class="text-slate-400 hover:text-slate-600 text-xs font-bold">Close</button>
        </div>

        <form action="{{ route('tenant.coupons.settings') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Coupon Drawer Main Title</label>
                    <input type="text" name="website_coupon_modal_title" value="{{ $settings['website_coupon_modal_title'] }}" required
                        placeholder="e.g. Apply Coupon & Offers"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Coupon Drawer Subtitle / Tagline</label>
                    <input type="text" name="website_coupon_modal_subtitle" value="{{ $settings['website_coupon_modal_subtitle'] }}" required
                        placeholder="e.g. The Derma Co Verified Promo Codes"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-teal-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Free Delivery Min (₹)</label>
                    <input type="number" step="1" name="website_free_shipping_threshold" value="{{ $settings['website_free_shipping_threshold'] }}"
                        placeholder="e.g. 999"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Free Gift Min (₹)</label>
                    <input type="number" step="1" name="website_free_gift_threshold" value="{{ $settings['website_free_gift_threshold'] }}"
                        placeholder="e.g. 899"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Free Gift Sample Name</label>
                    <input type="text" name="website_free_gift_name" value="{{ $settings['website_free_gift_name'] }}"
                        placeholder="e.g. Free 10% Niacinamide Serum Mini 5ml"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Prepaid UPI/Card Disc (%)</label>
                    <input type="number" step="1" name="website_prepaid_discount_percent" value="{{ $settings['website_prepaid_discount_percent'] }}"
                        placeholder="e.g. 5"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-teal-500">
                </div>
            </div>

            <!-- Free Gift Custom Image Uploader Banner -->
            <div class="p-3.5 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 flex items-center justify-center shrink-0 overflow-hidden p-1 shadow-sm">
                        <img src="{{ $settings['website_free_gift_image'] ?? '/gift-icon.png' }}" alt="Gift" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <div class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-wider">Free Gift Product Image</div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400">Shown in the cart drawer when customer unlocks free gift. Defaults to store gift box.</div>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <input type="file" name="website_free_gift_image" accept="image/*"
                        class="text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-teal-600 file:text-white hover:file:bg-teal-700 cursor-pointer">
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-2.5 rounded-xl font-black text-xs shadow-md shadow-teal-200 dark:shadow-none transition-all">
                    Save Storefront Modal Settings
                </button>
            </div>
        </form>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="glass-card p-5 rounded-[2rem] flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-teal-50 dark:bg-teal-900/20 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Active Coupons</p>
                <p class="text-xl md:text-2xl font-black text-teal-600">{{ number_format($activeCoupons) }} / {{ number_format($totalCoupons) }}</p>
            </div>
        </div>

        <div class="glass-card p-5 rounded-[2rem] flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Redemptions</p>
                <p class="text-xl md:text-2xl font-black text-indigo-600">{{ number_format($totalRedemptions) }}</p>
            </div>
        </div>

        <div class="glass-card p-5 rounded-[2rem] flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V4a2 2 0 10-2 2h2zm0 13a2 2 0 100-4 2 2 0 000 4z"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Prepaid Promotion</p>
                <p class="text-xl md:text-2xl font-black text-amber-600">{{ $settings['website_prepaid_discount_percent'] }}% Extra Discount</p>
            </div>
        </div>
    </div>

    {{-- Coupons List Table --}}
    <div class="glass-card p-6 md:p-8 rounded-[2.5rem] space-y-6">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <form action="{{ route('tenant.coupons.index') }}" method="GET" class="relative w-full sm:w-80">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search coupon code or name..."
                    class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold text-slate-800 dark:text-white pl-9 pr-4 py-2.5 focus:ring-2 focus:ring-teal-500">
                <svg class="absolute left-3 top-3 text-slate-400" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </form>

            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.coupons.index') }}" class="px-3 py-1.5 rounded-xl text-xs font-bold {{ !request('status') ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600' }}">All</a>
                <a href="{{ route('tenant.coupons.index', ['status' => 'active']) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold {{ request('status') === 'active' ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600' }}">Active</a>
                <a href="{{ route('tenant.coupons.index', ['status' => 'inactive']) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold {{ request('status') === 'inactive' ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600' }}">Inactive</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-bold text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800 text-[10px] text-slate-400 uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4 rounded-l-xl">Coupon Code & Details</th>
                        <th class="py-3 px-4">Discount</th>
                        <th class="py-3 px-4">Min Order</th>
                        <th class="py-3 px-4">Free Gift Offer</th>
                        <th class="py-3 px-4">Usage</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 rounded-r-xl text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($coupons as $coupon)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-lg bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 font-black tracking-wider text-xs border border-teal-200 dark:border-teal-800/50">
                                        {{ $coupon->code }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-900 dark:text-white font-bold mt-1">{{ $coupon->name }}</p>
                                @if($coupon->description)
                                    <p class="text-[10px] text-slate-400 line-clamp-1 mt-0.5">{{ $coupon->description }}</p>
                                @endif
                            </td>
                            <td class="py-4 px-4 font-black text-slate-900 dark:text-white">
                                @if($coupon->discount_type === 'percent')
                                    <span class="text-emerald-600">{{ $coupon->discount_value }}% OFF</span>
                                    @if($coupon->max_discount)
                                        <span class="block text-[10px] text-slate-400 font-semibold">Max saving: ₹{{ number_format($coupon->max_discount) }}</span>
                                    @endif
                                @else
                                    <span class="text-emerald-600">₹{{ number_format($coupon->discount_value) }} FLAT</span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                @if($coupon->min_order_value > 0)
                                    ₹{{ number_format($coupon->min_order_value) }}
                                @else
                                    <span class="text-slate-400">No Min</span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                @if($coupon->free_gift_name)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-600 dark:text-amber-400">
                                        🎁 {{ $coupon->free_gift_name }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <span class="font-black text-slate-900 dark:text-white">{{ $coupon->used_count }}</span>
                                @if($coupon->usage_limit)
                                    <span class="text-[10px] text-slate-400"> / {{ $coupon->usage_limit }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                @if($coupon->is_active)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-600 border border-emerald-200">Active</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-50 text-rose-600 border border-rose-200">Inactive</span>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- Edit Button --}}
                                    <button type="button" @click="openEdit({{ json_encode($coupon) }})"
                                            class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-400 transition-colors font-bold"
                                            title="Edit this coupon">
                                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        <span>Edit</span>
                                    </button>

                                    <form action="{{ route('tenant.coupons.toggle', $coupon->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-xs px-2.5 py-1 rounded-lg {{ $coupon->is_active ? 'bg-amber-50 text-amber-600 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' }} transition-colors">
                                            {{ $coupon->is_active ? 'Pause' : 'Activate' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('tenant.coupons.destroy', $coupon->id) }}" method="POST" onsubmit="return confirm('Delete this coupon?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs px-2 py-1 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                No coupons found. Click "Create New Coupon" to add one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($coupons->hasPages())
            <div class="pt-4">
                {{ $coupons->links() }}
            </div>
        @endif
    </div>

    {{-- Edit Coupon Modal --}}
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="showEditModal = false" class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 md:p-8 space-y-6 shadow-2xl border border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-black text-slate-900 dark:text-white">Edit Coupon</h3>
                    <p class="text-xs text-slate-400">Update code, title, discount or free gift rules</p>
                </div>
                <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600">
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
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Coupon Title / Name *</label>
                        <input type="text" name="name" x-model="editingCoupon.name" placeholder="e.g. Redeem 35% Special" required
                            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Free Gift Offer / Perk Tag</label>
                    <input type="text" name="free_gift_name" x-model="editingCoupon.free_gift_name" placeholder="e.g. Free 10% Niacinamide Serum Mini 5ml"
                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Description / Terms (Shown in modal)</label>
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
                            <option value="1">Active (Visible in Storefront)</option>
                            <option value="0">Inactive / Paused</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showEditModal = false" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-black bg-teal-600 hover:bg-teal-700 text-white shadow-lg shadow-teal-200">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Create Coupon Modal --}}
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="showCreateModal = false" class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 md:p-8 space-y-6 shadow-2xl border border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-black text-slate-900 dark:text-white">Create New Coupon</h3>
                <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600">
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
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Coupon Title / Name *</label>
                        <input type="text" name="name" placeholder="e.g. Flat 30% Off Skincare" required
                            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Free Gift Offer (Optional)</label>
                    <input type="text" name="free_gift_name" placeholder="e.g. Free 10g Sunscreen Miniature"
                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2.5 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Description / Terms</label>
                    <textarea name="description" rows="2" placeholder="e.g. Applicable on orders above ₹1,499. Cannot be combined with other offers."
                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-3 py-2 text-slate-800 dark:text-white focus:ring-2 focus:ring-teal-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showCreateModal = false" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-black bg-teal-600 hover:bg-teal-700 text-white shadow-lg shadow-teal-200">Save Coupon</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
