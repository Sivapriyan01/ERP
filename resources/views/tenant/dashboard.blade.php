@extends('layouts.tenant')
@section('title', 'Dashboard')

@section('content')
<div class="space-y-8 animate-in fade-in duration-700">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @php
            $statCards = [
                ['label' => 'Total Sales', 'value' => '₹' . number_format($totalSales, 0), 'trend' => '+2.5%', 'color' => 'blue', 'icon' => 'currency-rupee', 'route' => route('tenant.reports.index')],
                ['label' => 'Total Orders', 'value' => number_format($totalOrders), 'trend' => '+5.2%', 'color' => 'blue', 'icon' => 'shopping-bag', 'route' => route('tenant.billing.index')],
                ['label' => 'Active Productions', 'value' => number_format($activeProductions), 'trend' => 'Live', 'color' => 'emerald', 'icon' => 'manufacturing', 'route' => route('tenant.production.index')],
                ['label' => 'Due Today', 'value' => '₹' . number_format($dueStats['today_amount'], 0), 'trend' => $dueStats['today_count'] . ' items', 'color' => 'rose', 'icon' => 'clock', 'route' => route('tenant.due-dashboard.index')],
            ];
        @endphp

        @foreach($statCards as $card)
            <a href="{{ $card['route'] }}" class="glass-card p-6 rounded-[2.5rem] relative overflow-hidden group hover:-translate-y-1 transition-all duration-500 block">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-{{ $card['color'] }}-50 dark:bg-{{ $card['color'] }}-900/20 flex items-center justify-center text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400 shadow-sm transition-transform group-hover:scale-110">
                        @if($card['icon'] == 'currency-rupee')
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        @elseif($card['icon'] == 'shopping-bag')
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        @elseif($card['icon'] == 'users')
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        @elseif($card['icon'] == 'manufacturing')
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        @else
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ $card['label'] }}</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ $card['value'] }}</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-2 mt-2">
                    <span class="px-2 py-0.5 rounded-lg text-[9px] font-black {{ str_contains($card['trend'], '+') || $card['trend'] == 'Live' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600' : 'bg-rose-50 dark:bg-rose-900/20 text-rose-600' }}">
                        {{ $card['trend'] }}
                    </span>
                    <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">vs yesterday</span>
                </div>

                <div class="absolute bottom-0 left-0 right-0 h-12 opacity-30 group-hover:opacity-60 transition-opacity">
                    <canvas id="sparkline-{{ $loop->index }}" height="48"></canvas>
                </div>
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 flex flex-col gap-6">
            <div class="glass-card p-8 rounded-[3rem] relative overflow-hidden flex-1 flex flex-col">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white tracking-tight">Sales Overview</h3>
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Total Revenue: <span class="text-blue-600">₹{{ number_format($overviewSales, 0) }}</span> 
                            <span class="{{ $overviewSalesTrend >= 0 ? 'text-emerald-500' : 'text-rose-500' }} ml-1">
                                {{ $overviewSalesTrend >= 0 ? '+' : '' }}{{ $overviewSalesTrend }}% vs last month
                            </span>
                        </p>
                    </div>
                    <form method="GET" action="{{ route('tenant.dashboard') }}" id="salesPeriodForm">
                        <select name="period" onchange="document.getElementById('salesPeriodForm').submit()" class="bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-[10px] font-black uppercase tracking-widest px-4 py-2 outline-none dark:text-slate-400 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            <option value="this_month" {{ $period == 'this_month' ? 'selected' : '' }}>This Month</option>
                            <option value="last_month" {{ $period == 'last_month' ? 'selected' : '' }}>Last Month</option>
                        </select>
                    </form>
                </div>
                <div class="flex-1 w-full min-h-[16rem]">
                    <canvas id="salesMainChart"></canvas>
                </div>
            </div>

            <div class="glass-card p-8 rounded-[3rem] relative overflow-hidden flex-1 flex flex-col">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white tracking-tight">Orders Overview</h3>
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1"><span class="text-blue-600">{{ number_format($overviewOrders) }}</span> total orders 
                            <span class="{{ $overviewOrdersTrend >= 0 ? 'text-emerald-500' : 'text-rose-500' }} ml-1">
                                {{ $overviewOrdersTrend >= 0 ? '+' : '' }}{{ $overviewOrdersTrend }}% vs last month
                            </span>
                        </p>
                    </div>
                    <form method="GET" action="{{ route('tenant.dashboard') }}" id="ordersPeriodForm">
                        <select name="period" onchange="document.getElementById('ordersPeriodForm').submit()" class="bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-[10px] font-black uppercase tracking-widest px-4 py-2 outline-none dark:text-slate-400 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            <option value="this_month" {{ $period == 'this_month' ? 'selected' : '' }}>This Month</option>
                            <option value="last_month" {{ $period == 'last_month' ? 'selected' : '' }}>Last Month</option>
                        </select>
                    </form>
                </div>
                <div class="flex-1 w-full min-h-[16rem]">
                    <canvas id="ordersMainChart"></canvas>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-6">
            <div class="glass-card p-8 rounded-[3rem]">
                <h3 class="text-base font-black text-slate-900 dark:text-white tracking-tight mb-6">Quick Actions</h3>
                <div class="grid grid-cols-4 gap-3">
                    @php
                        $quickActions = [
                            ['label' => 'Setup', 'icon' => 'settings', 'color' => 'sky', 'route' => url('/setup')],
                            ['label' => 'Create Invoice', 'icon' => 'plus', 'color' => 'blue', 'route' => route('tenant.billing.index')],
                            ['label' => 'Add Customer', 'icon' => 'user-plus', 'color' => 'emerald', 'route' => route('tenant.customers.index')],
                            ['label' => 'Add Product', 'icon' => 'cube', 'color' => 'blue', 'route' => route('tenant.products.index')],
                            ['label' => 'Stock Transfer', 'icon' => 'refresh', 'color' => 'amber', 'route' => route('tenant.stock-transfer.index')],
                            ['label' => 'Add Purchase', 'icon' => 'shopping-cart', 'color' => 'rose', 'route' => route('tenant.purchase.create')],
                            ['label' => 'Production', 'icon' => 'manufacturing', 'color' => 'blue', 'route' => route('tenant.production.index')],
                            ['label' => 'Due Dates', 'icon' => 'calendar', 'color' => 'amber', 'route' => route('tenant.due-dashboard.index')],
                        ];
                    @endphp
                    @foreach($quickActions as $action)
                        <a href="{{ $action['route'] }}" class="flex flex-col items-center justify-center p-3 rounded-2xl bg-slate-50/50 dark:bg-slate-800/50 hover:bg-white dark:hover:bg-slate-800 border border-transparent hover:border-slate-100 dark:hover:border-slate-700 hover:shadow-xl transition-all duration-500 group">
                            <div class="w-10 h-10 rounded-xl bg-{{ $action['color'] }}-50 dark:bg-{{ $action['color'] }}-900/20 text-{{ $action['color'] }}-600 dark:text-{{ $action['color'] }}-400 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                                @if($action['icon'] == 'plus')
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                                @elseif($action['icon'] == 'user-plus')
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                @elseif($action['icon'] == 'cube')
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                @elseif($action['icon'] == 'refresh')
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                @elseif($action['icon'] == 'shopping-cart')
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                @elseif($action['icon'] == 'manufacturing')
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                @elseif($action['icon'] == 'calendar')
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                @elseif($action['icon'] == 'settings')
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                @endif
                            </div>
                            <span class="text-[8px] font-black text-slate-800 dark:text-white uppercase text-center leading-tight">{{ $action['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="glass-card p-8 rounded-[3rem] flex-1">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-base font-black text-slate-900 dark:text-white tracking-tight">Recent Activity</h3>
                    <a href="{{ route('tenant.billing.index') }}" class="text-[10px] font-black text-blue-600 uppercase tracking-widest hover:underline">View All</a>
                </div>
                <div class="space-y-6">
                    @forelse($recentBills as $bill)
                        <a href="{{ route('tenant.billing.print', $bill->id) }}" class="flex items-start gap-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 p-2 -mx-2 rounded-xl transition-colors">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-blue-600 flex items-center justify-center shrink-0">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-black text-slate-900 dark:text-slate-200 uppercase">Invoice #INV-{{ $bill->id }} created</p>
                                <p class="text-[8px] font-bold text-slate-400 dark:text-slate-500 uppercase mt-0.5">{{ $bill->created_at->diffForHumans() }}</p>
                            </div>
                        </a>
                    @empty
                        <div class="text-center py-8">
                            <p class="text-[10px] font-bold text-slate-400 uppercase">No recent activity</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="glass-card p-8 rounded-[3rem]">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-base font-black text-slate-900 dark:text-white tracking-tight">Low Stock Products</h3>
                    <a href="{{ route('tenant.inventory.index') }}" class="text-[10px] font-black text-rose-500 uppercase tracking-widest hover:underline">View All</a>
                </div>
                <div class="space-y-4">
                    @foreach($stockAlerts as $alert)
                        <a href="{{ route('tenant.products.edit', $alert->id) }}" class="flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800/50 p-2 -mx-2 rounded-xl transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                                <span class="text-[10px] font-black text-slate-800 dark:text-slate-300 uppercase">{{ $alert->product_name }}</span>
                            </div>
                            <span class="text-[10px] font-black text-rose-500">{{ $alert->stock }} left</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-4" x-data="{ activeTab: 'All Modules' }">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-black text-slate-900 dark:text-white tracking-tight">Command Center</h3>
            <div class="flex gap-2">
                @foreach(['All Modules', 'Sales', 'Inventory', 'Manufacturing', 'Customers', 'Agreements', 'Reports', 'Settings'] as $filter)
                    <button 
                        @click="activeTab = '{{ $filter }}'"
                        :class="activeTab === '{{ $filter }}' ? 'bg-blue-600 text-white shadow-lg shadow-blue-100' : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-100 dark:border-slate-700'"
                        class="px-4 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all">
                        {{ $filter }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @php
                $modules = [
                    ['title' => 'Quick Bill', 'icon' => 'lightning-bolt', 'color' => 'blue', 'category' => 'Sales', 'route' => route('tenant.billing.quick')],
                    ['title' => 'Billing', 'icon' => 'currency-dollar', 'color' => 'violet', 'category' => 'Sales', 'route' => route('tenant.billing.index')],
                    ['title' => 'Sales History', 'icon' => 'document-report', 'color' => 'indigo', 'category' => 'Sales', 'route' => route('tenant.sales.index')],
                    ['title' => 'Returns & Exchange', 'icon' => 'exchange', 'color' => 'rose', 'category' => 'Sales', 'route' => route('tenant.returns.index')],
                    ['title' => 'Purchase Orders', 'icon' => 'clipboard-list', 'color' => 'teal', 'category' => 'Inventory', 'route' => route('tenant.purchase.index')],
                    ['title' => 'Outward', 'icon' => 'arrow-up-right', 'color' => 'rose', 'category' => 'Sales', 'route' => route('tenant.billing.outward')],
                    ['title' => 'Inward', 'icon' => 'arrow-down', 'color' => 'blue', 'category' => 'Inventory', 'route' => route('tenant.purchase.inward')],
                    ['title' => 'Pre Orders', 'icon' => 'clipboard-list', 'color' => 'blue', 'category' => 'Sales', 'route' => route('tenant.billing.pre-orders')],
                    ['title' => 'Inventory', 'icon' => 'cube', 'color' => 'amber', 'category' => 'Inventory', 'route' => route('tenant.inventory.index')],
                    ['title' => 'Production', 'icon' => 'manufacturing', 'color' => 'blue', 'category' => 'Manufacturing', 'route' => route('tenant.production.index')],
                    ['title' => 'Customers', 'icon' => 'users', 'color' => 'blue', 'category' => 'Customers', 'route' => route('tenant.customers.index')],
                    ['title' => 'Vendors', 'icon' => 'store-front', 'color' => 'emerald', 'category' => 'Inventory', 'route' => route('tenant.suppliers.index')],
                    ['title' => 'Agreements', 'icon' => 'document-text', 'color' => 'indigo', 'category' => 'Agreements', 'permission' => 'Agreements', 'route' => route('tenant.agreements.index')],
                    ['title' => 'User', 'icon' => 'user-circle', 'color' => 'slate', 'category' => 'Settings', 'route' => route('tenant.users.index')],
                    ['title' => 'Reports', 'icon' => 'chart-bar', 'color' => 'blue', 'category' => 'Reports', 'route' => route('tenant.reports.index')],
                    ['title' => 'Stock Transfer', 'icon' => 'refresh', 'color' => 'sky', 'category' => 'Inventory', 'route' => route('tenant.stock-transfer.index')],
                    ['title' => 'Product Master', 'icon' => 'collection', 'color' => 'fuchsia', 'category' => 'Inventory', 'route' => route('tenant.products.index')],
                    ['title' => 'WhatsApp', 'icon' => 'chat', 'color' => 'green', 'category' => 'Customers', 'route' => route('tenant.whatsapp.index')],
                    ['title' => 'Mail', 'icon' => 'mail', 'color' => 'blue', 'category' => 'Customers', 'route' => route('tenant.mail.index')],
                    ['title' => 'Calendar', 'icon' => 'calendar', 'color' => 'rose', 'category' => 'Settings', 'route' => route('tenant.calendar.index')],
                    ['title' => 'CRM', 'icon' => 'user-group', 'color' => 'blue', 'category' => 'Customers', 'route' => auth('tenant')->user()->hasPermission('CRM_DB') ? route('tenant.crm.dashboard') : route('tenant.crm.index')],
                    ['title' => 'Instalments', 'icon' => 'credit-card', 'color' => 'amber', 'category' => 'Sales', 'route' => route('tenant.instalments.index')],
                    ['title' => 'Due Invoices', 'icon' => 'exclamation-circle', 'color' => 'rose', 'category' => 'Sales', 'route' => route('tenant.due-dashboard.index')],
                    ['title' => 'Tally ERP', 'icon' => 'tally', 'color' => 'indigo', 'category' => 'Reports', 'route' => route('tenant.tally.index')],
                    ['title' => 'Business Reports', 'icon' => 'document-report', 'color' => 'blue', 'category' => 'Reports', 'route' => route('tenant.businessreport.index')],
                    ['title' => 'Deliveries', 'icon' => 'clipboard-list', 'color' => 'amber', 'category' => 'Sales', 'route' => route('tenant.delivery.index')],
                    ['title' => 'Anniversary', 'icon' => 'sparkles', 'color' => 'rose', 'category' => 'Customers', 'route' => route('tenant.reminders.index')],
                    ['title' => 'Setup', 'icon' => 'cog', 'color' => 'slate', 'category' => 'Settings', 'route' => url('/setup')],
                    ['title' => 'Service', 'icon' => 'wrench', 'color' => 'cyan', 'category' => 'Sales', 'route' => route('tenant.service.index')],
                    ['title' => 'Membership', 'icon' => 'badge-check', 'color' => 'purple', 'category' => 'Customers', 'route' => route('tenant.membership.index')],
                    ['title' => 'Accounting', 'icon' => 'calculator', 'color' => 'teal', 'category' => 'Reports', 'route' => route('tenant.accounting.index')],
                ];
            @endphp

            @foreach($modules as $module)
                @if(!isset($module['permission']) || auth('tenant')->user()->hasPermission($module['permission']))
                <a href="{{ $module['route'] }}" 
                   @if(!empty($module['target'])) target="{{ $module['target'] }}" @endif
                   x-show="activeTab === 'All Modules' || activeTab === '{{ $module['category'] }}'"
                   class="glass-card group p-5 rounded-[2rem] flex flex-col items-center justify-center gap-3 text-center hover:shadow-2xl transition-all duration-500 border border-transparent">
                    <div class="w-12 h-12 rounded-2xl bg-{{ $module['color'] }}-50 dark:bg-{{ $module['color'] }}-900/20 text-{{ $module['color'] }}-600 dark:text-{{ $module['color'] }}-400 flex items-center justify-center shadow-sm">
                        @switch($module['icon'])
                            @case('lightning-bolt') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> @break
                            @case('exchange') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg> @break
                            @case('currency-dollar') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> @break
                            @case('shopping-cart') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg> @break
                            @case('cube') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg> @break
                            @case('users') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> @break
                            @case('cog') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg> @break
                            @case('clipboard-list') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg> @break
                            @case('arrow-up-right') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h10M18 7v10M18 7L6 19"/></svg> @break
                            @case('arrow-down') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg> @break
                            @case('manufacturing') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg> @break
                            @case('store-front') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16v10a2 2 0 01-2 2H6a2 2 0 01-2-2V10z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10v4m8-4v4" /></svg> @break
                            @case('user-circle') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> @break
                            @case('chart-bar') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg> @break
                            @case('refresh') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> @break
                            @case('collection') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg> @break
                            @case('chat') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg> @break
                            @case('mail') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> @break
                            @case('calendar') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg> @break
                            @case('user-group') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg> @break
                            @case('credit-card') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg> @break
                            @case('exclamation-circle') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> @break
                            @case('tally') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg> @break
                            @case('document-report') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> @break
                            @case('sparkles') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-7.714 2.143L11 21l-2.286-6.857L1 12l7.714-2.143L11 3z"/></svg> @break
                            @case('wrench') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A4 4 0 002 9.259V12h2a2 2 0 110 4H2v2.741a4 4 0 005.555 3.704l3.197-2.132M15 15l6-6m0 0l-3-3m3 3l-3 3"/></svg> @break
                            @case('badge-check') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg> @break
                            @case('calculator') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg> @break
                            @case('document-text') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> @break
                            @case('gift') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V4.5a2.5 2.5 0 115 0v3.5h-5zm0 0H7.5a2.5 2.5 0 110-5H12v5zm-7 4h14a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2z" /></svg> @break
                            @case('ticket') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg> @break
                            @case('globe') <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg> @break
                        @endswitch
                    </div>
                    <span class="text-[9px] font-black text-slate-800 dark:text-white uppercase tracking-tight">{{ $module['title'] }}</span>
                </a>
                @endif
            @endforeach
        </div>
    </div>

    <!-- Website Section -->
    <div class="space-y-4 mt-8">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white tracking-tight">Website</h3>
                <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">E-Commerce Storefront & Online Orders</p>
            </div>
            <a href="http://{{ request()->getHost() }}:5173" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 text-[9px] font-black uppercase tracking-wider hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-all border border-indigo-100 dark:border-indigo-800/40">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>Live Storefront :5173</span>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {{-- 1. Website Orders --}}
            <a href="{{ route('tenant.website-orders.index') }}" class="glass-card group p-6 rounded-[2rem] flex flex-col items-start gap-4 hover:shadow-2xl transition-all duration-500 border border-transparent hover:-translate-y-1">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-[13px] font-black text-slate-900 dark:text-white tracking-tight mb-1">Website Orders</h4>
                    <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 leading-relaxed">Manage live incoming orders, customer details & fulfillment</p>
                </div>
            </a>

            {{-- 2. Website Products --}}
            <a href="{{ route('tenant.website-products.index') }}" class="glass-card group p-6 rounded-[2rem] flex flex-col items-start gap-4 hover:shadow-2xl transition-all duration-500 border border-transparent hover:-translate-y-1">
                <div class="w-12 h-12 rounded-2xl bg-fuchsia-50 dark:bg-fuchsia-900/20 text-fuchsia-600 dark:text-fuchsia-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h4 class="text-[13px] font-black text-slate-900 dark:text-white tracking-tight">Website Products</h4>
                        <span class="px-2 py-0.5 rounded-full text-[8px] font-black bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300">
                            {{ \App\Models\Category::where('show_on_website', true)->count() }} Live
                        </span>
                    </div>
                    <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 leading-relaxed">Add store products to front site & manage live catalog</p>
                </div>
            </a>

            {{-- 3. Discount Coupons --}}
            <a href="{{ route('tenant.coupons.index') }}" class="glass-card group p-6 rounded-[2rem] flex flex-col items-start gap-4 hover:shadow-2xl transition-all duration-500 border border-transparent hover:-translate-y-1">
                <div class="w-12 h-12 rounded-2xl bg-teal-50 dark:bg-teal-900/20 text-teal-600 dark:text-teal-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h4 class="text-[13px] font-black text-slate-900 dark:text-white tracking-tight">Discount Coupons</h4>
                        <span class="px-2 py-0.5 rounded-full text-[8px] font-black bg-teal-50 text-teal-600 dark:bg-teal-900/30 dark:text-teal-300">
                            {{ \App\Models\Coupon::where('is_active', true)->count() }} Active
                        </span>
                    </div>
                    <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 leading-relaxed">Promo codes, cart thresholds & free gift tiers for storefront</p>
                </div>
            </a>

            {{-- 4. Gift Cards --}}
            <a href="{{ route('tenant.gift-cards.index') }}" class="glass-card group p-6 rounded-[2rem] flex flex-col items-start gap-4 hover:shadow-2xl transition-all duration-500 border border-transparent hover:-translate-y-1">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V4.5a2.5 2.5 0 115 0v3.5h-5zm0 0H7.5a2.5 2.5 0 110-5H12v5zm-7 4h14a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2z"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h4 class="text-[13px] font-black text-slate-900 dark:text-white tracking-tight">Gift Cards</h4>
                        <span class="px-2 py-0.5 rounded-full text-[8px] font-black bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300">
                            {{ \App\Models\GiftCard::where('status', 'active')->count() }} Active
                        </span>
                    </div>
                    <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 leading-relaxed">Issue vouchers, manage customer credits & POS/online redemption</p>
                </div>
            </a>

            {{-- 5. Store & Shipping --}}
            <a href="{{ route('tenant.website-settings.index', ['section' => 'store']) }}" class="glass-card group p-6 rounded-[2rem] flex flex-col items-start gap-4 hover:shadow-2xl transition-all duration-500 border border-transparent hover:-translate-y-1">
                <div class="w-12 h-12 rounded-2xl bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h4 class="text-[13px] font-black text-slate-900 dark:text-white tracking-tight">Store & Shipping</h4>
                        <span class="px-2 py-0.5 rounded-full text-[8px] font-black bg-sky-50 text-sky-600 dark:bg-sky-900/30 dark:text-sky-300">
                            Delivery Rules
                        </span>
                    </div>
                    <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 leading-relaxed">Storefront identity, free shipping threshold, pincode check & courier SLA</p>
                </div>
            </a>

            {{-- 6. Storefront Website --}}
            <a href="http://{{ request()->getHost() }}:5173" target="_blank" class="glass-card group p-6 rounded-[2rem] flex flex-col items-start gap-4 hover:shadow-2xl transition-all duration-500 border border-transparent hover:-translate-y-1">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-[13px] font-black text-slate-900 dark:text-white tracking-tight mb-1">Storefront Website</h4>
                    <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 leading-relaxed">Open customer-facing online catalog and checkout (:5173)</p>
                </div>
            </a>

            {{-- 6. Website Settings --}}
            <a href="{{ route('tenant.website-settings.index') }}" class="glass-card group p-6 rounded-[2rem] flex flex-col items-start gap-4 hover:shadow-2xl transition-all duration-500 border border-transparent hover:-translate-y-1">
                <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-[13px] font-black text-slate-900 dark:text-white tracking-tight mb-1">Website Settings</h4>
                    <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 leading-relaxed">Configure MSG91 OTP, coupons, gift cards, SEO & store policies</p>
                </div>
            </a>
        </div>
    </div>

    <div class="space-y-4 mt-8">
        <h3 class="text-base font-black text-slate-900 dark:text-white tracking-tight">Daily Operations</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            @php
                $crmModules = [
                    ['title' => 'Daily Expense', 'icon' => 'currency-dollar', 'color' => 'blue', 'route' => route('tenant.daily-expense.index'), 'description' => 'Manage and track daily operational expenses'],
                    ['title' => 'Employee Attendance', 'icon' => 'clipboard-list', 'color' => 'emerald', 'route' => route('tenant.attendance.index'), 'description' => 'Keeps track of your employee daily attendance'],
                    ['title' => 'Employee List', 'icon' => 'users', 'color' => 'blue', 'route' => route('tenant.employees.index'), 'description' => 'View and manage all employee records'],
                    ['title' => 'Purchase Settlement', 'icon' => 'shopping-cart', 'color' => 'amber', 'route' => route('tenant.purchase.settlement'), 'description' => 'Settle purchases and manage vendor payments'],
                    ['title' => 'Summary', 'icon' => 'chart-bar', 'color' => 'rose', 'route' => route('tenant.summary.index'), 'description' => "View today's sales, expenses, and settlements"],
                ];
            @endphp
            @foreach($crmModules as $module)
                <a href="{{ $module['route'] }}" class="glass-card group p-6 rounded-[2rem] flex flex-col items-start gap-4 hover:shadow-2xl transition-all duration-500 border border-transparent">
                    <div class="w-12 h-12 rounded-2xl bg-{{ $module['color'] }}-50 dark:bg-{{ $module['color'] }}-900/20 text-{{ $module['color'] }}-600 dark:text-{{ $module['color'] }}-400 flex items-center justify-center shadow-sm">
                        @if($module['icon'] == 'currency-dollar')
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @elseif($module['icon'] == 'clipboard-list')
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        @elseif($module['icon'] == 'users')
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        @elseif($module['icon'] == 'shopping-cart')
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        @elseif($module['icon'] == 'chart-bar')
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        @endif
                    </div>
                    <div>
                        <h4 class="text-[13px] font-black text-slate-900 dark:text-white tracking-tight mb-1">{{ $module['title'] }}</h4>
                        <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 leading-relaxed">{{ $module['description'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const commonOptions = {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            elements: { line: { tension: 0.4 }, point: { radius: 0 } },
            scales: { x: { display: false }, y: { display: false } }
        };

        const sparkColors = ['#6366f1', '#3b82f6', '#10b981', '#f43f5e'];
        for(let i=0; i<4; i++) {
            const el = document.getElementById('sparkline-' + i);
            if (el) {
                new Chart(el, {
                    type: 'line',
                    data: {
                        labels: [1,2,3,4,5,6,7],
                        datasets: [{
                            data: [10, 25, 15, 35, 20, 45, 30].map(v => v + (Math.random() * 20)),
                            borderColor: sparkColors[i], borderWidth: 2, fill: true,
                            backgroundColor: sparkColors[i] + '10'
                        }]
                    },
                    options: commonOptions
                });
            }
        }

        // Sales Main Chart
        const salesCtx = document.getElementById('salesMainChart');
        if (salesCtx) {
            new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: 'Sales Revenue',
                        data: @json($chartData),
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#4f46e5',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '₹ ' + context.raw.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.05)', borderDash: [5, 5] },
                            ticks: {
                                callback: function(value) { return '₹' + value; }
                            }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // Orders Main Chart
        const ordersCtx = document.getElementById('ordersMainChart');
        if (ordersCtx) {
            new Chart(ordersCtx, {
                type: 'bar',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: 'Orders',
                        data: @json($ordersChartData),
                        backgroundColor: '#0ea5e9',
                        borderRadius: 6,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.05)', borderDash: [5, 5] },
                            ticks: { stepSize: 1 }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection
