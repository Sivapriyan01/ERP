@extends('layouts.tenant')
@section('title', 'Website Products')
@section('page-title', 'Website Products')

@section('content')
<div class="space-y-6 md:space-y-8 animate-in fade-in duration-500" x-data="{
    websiteUrl: window.location.protocol + '//' + window.location.hostname + ':5173',
    selected: [],
    selectAllMatching: false,
    totalMatching: {{ (int) $products->total() }},
    pageCount: {{ (int) $products->count() }},
    pageIds: {{ json_encode($products->pluck('id')->map(fn($id) => (string)$id)->values()->all()) }},

    get isAllPageSelected() {
        return this.pageIds.length > 0 && this.pageIds.every(id => this.selected.includes(String(id)));
    },

    toggleSelect(id) {
        id = String(id);
        if (this.selectAllMatching) {
            this.selectAllMatching = false;
            this.selected = this.pageIds.filter(pid => pid !== id);
            return;
        }
        const idx = this.selected.indexOf(id);
        if (idx > -1) {
            this.selected.splice(idx, 1);
        } else {
            this.selected.push(id);
        }
    },

    toggleSelectPage() {
        if (this.isAllPageSelected || this.selectAllMatching) {
            this.selected = this.selected.filter(id => !this.pageIds.includes(String(id)));
            this.selectAllMatching = false;
        } else {
            const combined = new Set([...this.selected, ...this.pageIds]);
            this.selected = Array.from(combined);
        }
    },

    selectAllProductsAcrossPages() {
        const combined = new Set([...this.selected, ...this.pageIds]);
        this.selected = Array.from(combined);
        this.selectAllMatching = true;
    },

    toggleSelectAllAcrossPages() {
        if (this.selectAllMatching) {
            this.clearAllSelection();
        } else {
            this.selectAllProductsAcrossPages();
        }
    },

    clearAllSelection() {
        this.selected = [];
        this.selectAllMatching = false;
    },

    submitBulk(action) {
        if (this.selected.length === 0 && !this.selectAllMatching) {
            alert('Please select at least one product using the checkboxes.');
            return;
        }

        let confirmMsg = '';
        if (this.selectAllMatching) {
            if (action === 'publish_selected') {
                confirmMsg = 'Publish ALL ' + this.totalMatching.toLocaleString() + ' products in this view to the website front site?';
            } else if (action === 'hide_selected') {
                confirmMsg = 'Hide / Remove ALL ' + this.totalMatching.toLocaleString() + ' products in this view from the website front site?';
            }
        }

        if (confirmMsg && !confirm(confirmMsg)) {
            return;
        }

        document.getElementById('bulk-action-input').value = action;
        document.getElementById('bulk-select-all-input').value = this.selectAllMatching ? '1' : '0';
        document.getElementById('bulkForm').submit();
    }
}">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-fuchsia-50 dark:bg-fuchsia-900/20 text-fuchsia-600 dark:text-fuchsia-400 flex items-center justify-center shadow-sm">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Website Products</h1>
                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500">Manage all store products and publish them directly to your front site</p>
                </div>
            </div>
        </div>

        <div class="flex items-center flex-wrap gap-2">
            <form action="{{ route('tenant.website-products.bulk') }}" method="POST" class="inline" onsubmit="return confirm('Publish ALL {{ number_format($totalProducts) }} store products to the website front site?');">
                @csrf
                <input type="hidden" name="action" value="publish_all">
                <button type="submit" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-2xl font-black text-xs uppercase tracking-wider shadow-lg shadow-indigo-200 dark:shadow-none transition-all hover:scale-[1.02]">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span>Publish All to Front Site</span>
                </button>
            </form>

            <form action="{{ route('tenant.website-products.bulk') }}" method="POST" class="inline" onsubmit="return confirm('Hide ALL {{ number_format($totalProducts) }} store products from the website front site?');">
                @csrf
                <input type="hidden" name="action" value="hide_all">
                <button type="submit" class="flex items-center gap-2 bg-rose-50 hover:bg-rose-100 text-rose-600 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-900/60 px-4 py-2.5 rounded-2xl font-black text-xs uppercase tracking-wider border border-rose-200 dark:border-rose-900/40 transition-all hover:scale-[1.02]">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                    <span>Hide All</span>
                </button>
            </form>

            <a :href="websiteUrl" target="_blank" class="flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white dark:bg-slate-700 dark:hover:bg-slate-600 px-4 py-2.5 rounded-2xl font-black text-xs uppercase tracking-wider transition-all hover:scale-[1.02]">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                <span>Live Front Site :5173</span>
            </a>
        </div>
    </div>

    {{-- Metrics Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card p-5 rounded-[2rem] flex items-center gap-4 border-l-4 border-fuchsia-500">
            <div class="w-12 h-12 rounded-2xl bg-fuchsia-50 dark:bg-fuchsia-900/20 text-fuchsia-600 dark:text-fuchsia-400 flex items-center justify-center shrink-0">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Store Products</p>
                <p class="text-xl md:text-2xl font-black text-slate-900 dark:text-white">{{ number_format($totalProducts) }}</p>
            </div>
        </div>

        <div class="glass-card p-5 rounded-[2rem] flex items-center gap-4 border-l-4 border-indigo-600">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Live on Front Site</p>
                <p class="text-xl md:text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ number_format($liveOnWebsite) }}</p>
            </div>
        </div>

        <div class="glass-card p-5 rounded-[2rem] flex items-center gap-4 border-l-4 border-slate-400">
            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 flex items-center justify-center shrink-0">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Hidden from Website</p>
                <p class="text-xl md:text-2xl font-black text-slate-600 dark:text-slate-300">{{ number_format($hiddenProducts) }}</p>
            </div>
        </div>

        <div class="glass-card p-5 rounded-[2rem] flex items-center gap-4 border-l-4 border-emerald-500">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Store Stock Available</p>
                <p class="text-xl md:text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format($inStockCount) }}</p>
            </div>
        </div>
    </div>

    {{-- Hidden Standalone Bulk Form --}}
    <form id="bulkForm" action="{{ route('tenant.website-products.bulk') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="action" id="bulk-action-input" value="">
        <input type="hidden" name="select_all_matching" id="bulk-select-all-input" value="0">
        <input type="hidden" name="filter" value="{{ request('filter', 'all') }}">
        <input type="hidden" name="search" value="{{ request('search', '') }}">
        <input type="hidden" name="category" value="{{ request('category', '') }}">
        <template x-for="id in selected" :key="id">
            <input type="hidden" name="selected_ids[]" :value="id">
        </template>
    </form>

    {{-- Main Products Container --}}
    <div class="glass-card p-6 md:p-8 rounded-[2.5rem] space-y-6">
        {{-- Filters & Search Header --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            {{-- Filter Tabs & Select All Quick Button --}}
            <div class="flex flex-wrap items-center gap-2">
                @php $curFilter = request('filter', 'all'); @endphp
                @foreach([
                    'all' => 'All Products (' . number_format($totalProducts) . ')',
                    'website' => 'Live on Front Site (' . number_format($liveOnWebsite) . ')',
                    'hidden' => 'Hidden (' . number_format($hiddenProducts) . ')',
                    'instock' => 'In Stock (' . number_format($inStockCount) . ')'
                ] as $fKey => $fLabel)
                    <a href="{{ route('tenant.website-products.index', array_merge(request()->query(), ['filter' => $fKey])) }}"
                       class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all {{ $curFilter === $fKey ? 'bg-indigo-600 text-white shadow-md shadow-indigo-100 dark:shadow-none' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        {{ $fLabel }}
                    </a>
                @endforeach

                {{-- Prominent Quick Select All Button --}}
                <button type="button" 
                        @click="toggleSelectAllAcrossPages()" 
                        :class="selectAllMatching ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-md shadow-emerald-200 dark:shadow-none' : 'bg-slate-900 hover:bg-slate-800 text-white dark:bg-slate-800 dark:hover:bg-slate-700'"
                        class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1.5 shadow-sm hover:scale-[1.02]">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-text="selectAllMatching ? '✓ All {{ number_format($products->total()) }} Selected' : 'Select All {{ number_format($products->total()) }} Products'"></span>
                </button>
            </div>

            {{-- Search & Category Filter --}}
            <form method="GET" action="{{ route('tenant.website-products.index') }}" class="flex items-center gap-2 flex-wrap">
                <input type="hidden" name="filter" value="{{ $curFilter }}">
                
                @if($categories->count() > 0)
                <select name="category" onchange="this.form.submit()" class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-[10px] font-bold outline-none focus:ring-2 focus:ring-indigo-100 text-slate-700 dark:text-slate-300">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
                @endif

                <div class="relative flex items-center">
                    <svg width="14" height="14" class="absolute left-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products, barcode, brand..." class="pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-[10px] font-bold outline-none focus:ring-2 focus:ring-indigo-100 w-52 md:w-64">
                </div>

                <button type="submit" class="bg-indigo-600 text-white px-3 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider hover:bg-indigo-700 transition">
                    Search
                </button>
                @if(request('search') || request('category'))
                <a href="{{ route('tenant.website-products.index', ['filter' => $curFilter]) }}" class="text-slate-400 hover:text-slate-600 text-[10px] font-bold p-2">
                    Clear
                </a>
                @endif
            </form>
        </div>

        {{-- Bulk Selection Bar (Shows whenever products are checked OR select all across pages is active) --}}
        <div x-show="selected.length > 0 || selectAllMatching" x-cloak class="p-4 bg-indigo-50/90 dark:bg-indigo-950/60 rounded-2xl border border-indigo-200 dark:border-indigo-800/80 flex flex-wrap items-center justify-between gap-4 animate-in fade-in duration-300 shadow-sm">
            <div class="flex items-center flex-wrap gap-2">
                <span class="w-3 h-3 rounded-full bg-indigo-600 animate-pulse"></span>
                <template x-if="selectAllMatching">
                    <span class="text-xs font-black text-indigo-950 dark:text-indigo-200 uppercase tracking-wider flex items-center gap-2">
                        <span>All {{ number_format($products->total()) }} Products Selected across all pages</span>
                        <button type="button" @click="clearAllSelection()" class="text-[10px] underline lowercase text-indigo-600 dark:text-indigo-400 font-bold hover:text-indigo-800">
                            (click to clear)
                        </button>
                    </span>
                </template>
                <template x-if="!selectAllMatching">
                    <div class="flex items-center flex-wrap gap-2 text-xs font-black text-indigo-950 dark:text-indigo-200 uppercase tracking-wider">
                        <span><span x-text="selected.length"></span> Product<span x-show="selected.length > 1">s</span> Selected (This Page)</span>
                        @if($products->total() > $products->count())
                        <button type="button" 
                                @click="selectAllProductsAcrossPages()" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-lg transition shadow-sm">
                            👉 Select all {{ number_format($products->total()) }} products across all pages
                        </button>
                        @endif
                    </div>
                </template>
            </div>

            <div class="flex items-center flex-wrap gap-2">
                <button type="button" @click="submitBulk('publish_selected')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider transition shadow-md shadow-indigo-200 dark:shadow-none hover:scale-[1.02] flex items-center gap-1.5">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span x-text="selectAllMatching ? '+ Add All {{ number_format($products->total()) }} to Front Site' : '+ Add Selected to Front Site'"></span>
                </button>
                <button type="button" @click="submitBulk('hide_selected')" class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider transition hover:scale-[1.02] flex items-center gap-1.5 shadow-sm">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span x-text="selectAllMatching ? 'Hide All {{ number_format($products->total()) }} from Front Site' : 'Hide Selected'"></span>
                </button>
                <button type="button" @click="clearAllSelection()" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-white dark:hover:bg-slate-800 transition">
                    Deselect All
                </button>
            </div>
        </div>

        {{-- Products Table --}}
        <div class="overflow-x-auto rounded-2xl border border-slate-100 dark:border-slate-800/80">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50/70 dark:bg-slate-900/70 border-b border-slate-100 dark:border-slate-800">
                        <th class="px-5 py-4 w-12 text-center">
                            <input type="checkbox" 
                                   :checked="isAllPageSelected || selectAllMatching"
                                   @change="toggleSelectPage()" 
                                   title="Select / Deselect all products on this page"
                                   class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer w-4 h-4">
                        </th>
                        <th class="px-5 py-4 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                            <div class="flex items-center gap-2">
                                <span>Product Information</span>
                                <button type="button" 
                                        @click="selectAllProductsAcrossPages()" 
                                        x-show="!selectAllMatching" 
                                        title="Select all {{ number_format($products->total()) }} products across all pages"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 text-[9px] font-black uppercase tracking-wider transition">
                                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Select All ({{ number_format($products->total()) }})</span>
                                </button>
                            </div>
                        </th>
                        <th class="px-5 py-4 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Brand / Category</th>
                        <th class="px-5 py-4 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Price / MRP</th>
                        <th class="px-5 py-4 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Stock Level</th>
                        <th class="px-5 py-4 text-center text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Front Site Status</th>
                        <th class="px-5 py-4 text-right text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($products as $product)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition-colors {{ $product->show_on_website ? 'bg-indigo-50/20 dark:bg-indigo-950/10' : '' }}"
                            :class="selected.includes('{{ $product->id }}') || selectAllMatching ? 'bg-indigo-50/40 dark:bg-indigo-900/20' : ''">
                            {{-- Checkbox Column --}}
                            <td class="px-5 py-4 text-center">
                                <input type="checkbox" 
                                       value="{{ $product->id }}" 
                                       :checked="selected.includes('{{ $product->id }}') || selectAllMatching" 
                                       @change="toggleSelect('{{ $product->id }}')" 
                                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer w-4 h-4">
                            </td>

                            {{-- Product Information --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center overflow-hidden shrink-0">
                                        @if($product->image)
                                            <img src="{{ tenant_asset($product->image) }}" class="w-full h-full object-cover">
                                        @else
                                            <svg width="22" height="22" class="text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-black text-slate-900 dark:text-white truncate">{{ $product->product_name }}</p>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[9px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">{{ $product->barcode ?: 'NO BARCODE' }}</span>
                                            @if($product->model)
                                                <span class="text-[9px] font-bold text-slate-400">Model: {{ $product->model }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Brand & Category --}}
                            <td class="px-5 py-4">
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ $product->brand ?: 'Square Store' }}</p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase mt-0.5">{{ $product->product_type ?: 'General' }}</p>
                            </td>

                            {{-- Price & MRP --}}
                            <td class="px-5 py-4">
                                <p class="text-sm font-black text-slate-900 dark:text-white">₹{{ number_format($product->mrp ?: 0, 2) }}</p>
                                @if($product->dealer_price)
                                    <p class="text-[9px] font-bold text-emerald-600 uppercase">Cost: ₹{{ number_format($product->dealer_price, 2) }}</p>
                                @endif
                            </td>

                            {{-- Stock Level --}}
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $product->stock > 0 ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400' : 'bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400' }}">
                                    {{ $product->stock }} in stock
                                </span>
                            </td>

                            {{-- Front Site Status Pill --}}
                            <td class="px-5 py-4 text-center">
                                @if($product->show_on_website)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>Live on Front Site</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>Hidden</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Single Toggle Action Button --}}
                            <td class="px-5 py-4 text-right">
                                <form action="{{ route('tenant.website-products.toggle', $product->id) }}" method="POST" class="inline">
                                    @csrf
                                    @if($product->show_on_website)
                                        <button type="submit" 
                                                title="Click to remove from front site"
                                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-900/60 transition-all border border-rose-200 dark:border-rose-900/40">
                                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span>Remove</span>
                                        </button>
                                    @else
                                        <button type="submit" 
                                                title="Click to add to front site website"
                                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-100 dark:shadow-none transition-all hover:scale-[1.03]">
                                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                            <span>+ Add to Front Site</span>
                                        </button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12">
                                <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                    <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <p class="text-sm font-black text-slate-800 dark:text-slate-200">No store products found</p>
                                <p class="text-xs font-bold text-slate-400 mt-1">Try changing your search or filter options, or add products in Product Master</p>
                                <a href="{{ route('tenant.products.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-xl bg-indigo-600 text-white font-black text-xs uppercase tracking-wider hover:bg-indigo-700 transition">
                                    + Create New Product
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
        <div class="pt-4 flex items-center justify-between">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                Showing {{ $products->firstItem() }} - {{ $products->lastItem() }} of {{ number_format($products->total()) }} store products
            </p>
            <div>
                {{ $products->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
