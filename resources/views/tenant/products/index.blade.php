@extends('layouts.tenant')
@section('title', 'Product Master')
@section('page-title', 'Product Master')

@section('content')
<div class="space-y-6 md:space-y-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">Product Master</h1>
            <p class="text-sm text-slate-500 mt-1">Manage your inventory and product catalogue.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.website-products.index') }}" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest text-center shadow-xl shadow-indigo-100 dark:shadow-none hover:scale-[1.02] transition flex items-center gap-1.5 justify-center">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                <span>Website Products</span>
            </a>
            <button type="button" onclick="document.getElementById('importModal').classList.remove('hidden')" class="w-full md:w-auto bg-slate-800 text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest text-center shadow-xl shadow-slate-200 hover:scale-[1.02] transition dark:bg-slate-700 dark:shadow-none">
                Import
            </button>
            <a href="{{ route('tenant.products.create') }}" class="w-full md:w-auto bg-fuchsia-600 text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest text-center shadow-xl shadow-fuchsia-100 hover:scale-[1.02] transition">
                + New Product
            </a>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="glass-card p-4 rounded-2xl flex items-center gap-3">
            <div class="w-10 h-10 bg-fuchsia-100 text-fuchsia-600 rounded-xl flex items-center justify-center shrink-0">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Total SKUs</p>
                <p class="text-lg md:text-xl font-black text-slate-900">{{ $products->count() }}</p>
            </div>
        </div>
        <div class="glass-card p-4 rounded-2xl flex items-center gap-3 border-l-4 border-indigo-500">
             <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center shrink-0">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Live on Website</p>
                <p class="text-lg md:text-xl font-black text-indigo-600">{{ $products->filter(fn($p) => $p->show_on_website)->count() }}</p>
            </div>
        </div>
        <div class="glass-card p-4 rounded-2xl flex items-center gap-3">
            <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center shrink-0">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Total Stock</p>
                <p class="text-lg md:text-xl font-black text-slate-900">{{ $products->sum('stock') }}</p>
            </div>
        </div>
        <div class="glass-card p-4 rounded-2xl flex items-center gap-3 border-l-4 border-rose-500">
            <div class="w-10 h-10 bg-rose-100 text-rose-600 rounded-xl flex items-center justify-center shrink-0">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Low Stock</p>
                <p class="text-lg md:text-xl font-black text-rose-600">{{ $products->filter(fn($p) => $p->stock <= $p->low_stock_alert)->count() }}</p>
            </div>
        </div>
        <div class="glass-card p-4 rounded-2xl flex items-center gap-3 border-l-4 border-rose-500">
             <div class="w-10 h-10 bg-rose-100 text-rose-600 rounded-xl flex items-center justify-center shrink-0 animate-pulse">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Expired</p>
                <p class="text-lg md:text-xl font-black text-rose-600">{{ $products->filter(fn($p) => $p->expiry_date && \Carbon\Carbon::parse($p->expiry_date)->isPast())->count() }}</p>
            </div>
        </div>
    </div>

    {{-- Products Table --}}
    <div class="glass-card rounded-[2rem] overflow-hidden" x-data="{ filterWebsite: 'all', searchQuery: '' }">
        <div class="p-5 md:p-6 border-b border-slate-100 dark:border-slate-800/80 flex flex-col md:flex-row md:items-center justify-between bg-white/50 dark:bg-slate-900/40 gap-4">
            <div class="flex items-center gap-3">
                <h3 class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Inventory Master</h3>
                <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl">
                    <button type="button" @click="filterWebsite = 'all'" :class="filterWebsite === 'all' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500'" class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all">
                        All ({{ $products->count() }})
                    </button>
                    <button type="button" @click="filterWebsite = 'website'" :class="filterWebsite === 'website' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500'" class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-300"></span>
                        Website ({{ $products->filter(fn($p) => $p->show_on_website)->count() }})
                    </button>
                    <button type="button" @click="filterWebsite = 'hidden'" :class="filterWebsite === 'hidden' ? 'bg-slate-800 dark:bg-slate-600 text-white shadow-sm' : 'text-slate-500'" class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                        Hidden ({{ $products->filter(fn($p) => !$p->show_on_website)->count() }})
                    </button>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="text" x-model="searchQuery" placeholder="Search by name, brand, barcode..." class="bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/80 rounded-xl px-4 py-2 text-[10px] focus:ring-2 focus:ring-fuchsia-100 dark:focus:ring-fuchsia-900 outline-none w-full md:w-64">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-950/40">
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Product Info</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Brand/Model</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Pricing</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Stock</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Website</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    @forelse ($products as $product)
                        <tr x-show="(filterWebsite === 'all' || (filterWebsite === 'website' && {{ $product->show_on_website ? 'true' : 'false' }}) || (filterWebsite === 'hidden' && {{ !$product->show_on_website ? 'true' : 'false' }})) && (!searchQuery || '{{ strtolower(addslashes($product->product_name . ' ' . $product->brand . ' ' . $product->barcode)) }}'.includes(searchQuery.toLowerCase()))" class="hover:bg-fuchsia-50/30 dark:hover:bg-slate-950/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-950/50 border border-slate-200 dark:border-slate-850 flex items-center justify-center overflow-hidden">
                                        @if($product->image)
                                            <img src="{{ tenant_asset($product->image) }}" class="w-full h-full object-cover">
                                        @else
                                            <svg width="24" height="24" class="text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-slate-900 leading-tight">{{ $product->product_name }}</p>
                                        <p class="text-[9px] font-black text-blue-600 tracking-widest">{{ $product->barcode ?: 'NO BARCODE' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-xs font-bold text-slate-700">{{ $product->brand }}</p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase">{{ $product->product_type }} | {{ $product->size }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-black text-slate-900">₹{{ number_format($product->mrp, 2) }}</p>
                                <p class="text-[9px] font-bold text-emerald-600 uppercase">Cost: ₹{{ number_format($product->dealer_price, 2) }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase text-center {{ $product->stock <= $product->low_stock_alert ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600' }}">
                                        {{ $product->stock }} in stock
                                    </span>
                                    @if($product->stock <= $product->low_stock_alert)
                                        <span class="text-[8px] font-black text-rose-500 uppercase text-center animate-pulse">Low Alert!</span>
                                    @endif
                                    @if($product->expiry_date && \Carbon\Carbon::parse($product->expiry_date)->isPast())
                                        <span class="text-[8px] font-black text-rose-500 uppercase text-center animate-pulse">Expired!</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <form action="{{ route('tenant.products.toggle-website', $product->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            title="{{ $product->show_on_website ? 'Live on website. Click to remove/hide from website.' : 'Hidden from website. Click to remove from hide / publish to website.' }}"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-wider transition-all {{ $product->show_on_website ? 'bg-indigo-100 text-indigo-700 hover:bg-rose-100 hover:text-rose-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'bg-slate-100 text-slate-500 hover:bg-emerald-100 hover:text-emerald-700 dark:bg-slate-800 dark:text-slate-400' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $product->show_on_website ? 'bg-indigo-600 animate-pulse' : 'bg-slate-400' }}"></span>
                                        {{ $product->show_on_website ? 'On Website' : 'Hidden (Unhide)' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('tenant.products.edit', $product) }}" class="p-2 text-slate-400 hover:text-fuchsia-600 transition">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <form action="{{ route('tenant.products.destroy', $product) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-2 text-slate-400 hover:text-rose-500 transition" onclick="return confirm('Delete this product?')">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-8 py-12 text-center">
                                <p class="text-sm font-bold text-slate-400 italic">No products found in master list.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Import Modal --}}
<div id="importModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl relative">
        <div class="p-6">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Import Products</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Upload an Excel file to import products and stock.</p>
            <form action="{{ route('tenant.products.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-6">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Excel File</label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-fuchsia-50 file:text-fuchsia-600 hover:file:bg-fuchsia-100 transition">
                    <p class="text-xs text-slate-400 mt-2">Required columns: product_name, mrp. Optional: barcode, brand, product_type, model, size, stock, etc.</p>
                </div>
                <div class="flex items-center gap-3 justify-end">
                    <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-500 bg-slate-100 hover:bg-slate-200 transition dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-fuchsia-600 hover:bg-fuchsia-700 transition shadow-lg shadow-fuchsia-200 dark:shadow-none">Upload & Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

