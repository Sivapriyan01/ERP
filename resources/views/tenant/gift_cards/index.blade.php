@extends('layouts.tenant')
@section('title', 'Gift Cards Management')
@section('page-title', 'Gift Cards Command Center')

@section('content')
<div class="space-y-6 md:space-y-8 animate-in fade-in duration-500"
     x-data="{
        showCreateModal: false,
        showBulkModal: false,
        showCheckModal: false,
        showPrintModal: false,
        activeCardForPrint: null,
        checkForm: { code: '', result: null, loading: false, error: '' },
        async checkCard() {
            this.checkForm.loading = true;
            this.checkForm.error = '';
            this.checkForm.result = null;
            try {
                const res = await fetch('{{ route('tenant.gift-cards.check') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ code: this.checkForm.code })
                });
                const data = await res.json();
                if (data.success) {
                    this.checkForm.result = data;
                } else {
                    this.checkForm.error = data.message || 'Unable to verify gift card.';
                }
            } catch (err) {
                this.checkForm.error = 'Network error while checking card.';
            } finally {
                this.checkForm.loading = false;
            }
        },
        openPrint(card) {
            this.activeCardForPrint = card;
            this.showPrintModal = true;
        },
        copyText(text) {
            navigator.clipboard.writeText(text);
            alert('Copied to clipboard: ' + text);
        }
     }">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center shadow-sm">
                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v13m0-13V4.5a2.5 2.5 0 115 0v3.5h-5zm0 0H7.5a2.5 2.5 0 110-5H12v5zm-7 4h14a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Gift Cards & Store Vouchers</h1>
                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500">Issue custom gift cards, generate bulk batches & manage retail redemption</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <button @click="showCheckModal = true; checkForm.result = null; checkForm.error = '';"
                    class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 px-4 py-2.5 rounded-2xl font-black text-xs tracking-wide transition-all">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span>Verify Balance</span>
            </button>
            <button @click="showBulkModal = true"
                    class="flex items-center gap-2 bg-indigo-50 dark:bg-indigo-950/40 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60 px-4 py-2.5 rounded-2xl font-black text-xs tracking-wide transition-all">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span>Bulk Generate</span>
            </button>
            <button @click="showCreateModal = true"
                    class="flex items-center gap-2 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white px-5 py-2.5 rounded-2xl font-black text-xs tracking-wide shadow-lg shadow-amber-200 dark:shadow-none hover:scale-[1.02] transition-all">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Issue Gift Card</span>
            </button>
        </div>
    </div>

    {{-- Stats Cards Row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Active Balance -->
        <div class="glass-card p-5 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/80 dark:bg-slate-900/40">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Live Active Balance</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 flex items-center justify-center font-bold text-xs">₹</span>
            </div>
            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400">₹{{ number_format($activeBalance, 2) }}</p>
            <p class="text-[10px] font-bold text-slate-400 mt-1">{{ $activeCards }} active cards circulating</p>
        </div>

        <!-- Total Cards -->
        <div class="glass-card p-5 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/80 dark:bg-slate-900/40">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Total Cards Issued</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 flex items-center justify-center font-bold text-xs">🏷️</span>
            </div>
            <p class="text-2xl font-black text-slate-900 dark:text-white">{{ $totalCards }}</p>
            <p class="text-[10px] font-bold text-slate-400 mt-1">Across all campaigns</p>
        </div>

        <!-- Total Redeemed -->
        <div class="glass-card p-5 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/80 dark:bg-slate-900/40">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Total Redeemed</span>
                <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 flex items-center justify-center font-bold text-xs">✓</span>
            </div>
            <p class="text-2xl font-black text-blue-600 dark:text-blue-400">₹{{ number_format($redeemedTotal, 2) }}</p>
            <p class="text-[10px] font-bold text-slate-400 mt-1">{{ $redeemedCount }} vouchers redeemed</p>
        </div>

        <!-- Locked Cards -->
        <div class="glass-card p-5 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/80 dark:bg-slate-900/40">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Security Locked</span>
                <span class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 flex items-center justify-center font-bold text-xs">🔒</span>
            </div>
            <p class="text-2xl font-black text-rose-600 dark:text-rose-400">{{ $lockedCount }}</p>
            <p class="text-[10px] font-bold text-slate-400 mt-1">Due to 5 incorrect PIN attempts</p>
        </div>
    </div>

    {{-- Filter & Search Bar --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Status Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-1">
            @php $currentStatus = request('status', ''); @endphp
            <a href="{{ route('tenant.gift-cards.index', request()->except('status', 'page')) }}"
               class="px-4 py-2 rounded-xl text-xs font-black transition-all whitespace-nowrap {{ $currentStatus === '' ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900 shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200' }}">
                All Cards
            </a>
            <a href="{{ route('tenant.gift-cards.index', array_merge(request()->except('page'), ['status' => 'active'])) }}"
               class="px-4 py-2 rounded-xl text-xs font-black transition-all whitespace-nowrap {{ $currentStatus === 'active' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200' }}">
                Active
            </a>
            <a href="{{ route('tenant.gift-cards.index', array_merge(request()->except('page'), ['status' => 'redeemed'])) }}"
               class="px-4 py-2 rounded-xl text-xs font-black transition-all whitespace-nowrap {{ $currentStatus === 'redeemed' ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200' }}">
                Redeemed
            </a>
            <a href="{{ route('tenant.gift-cards.index', array_merge(request()->except('page'), ['status' => 'locked'])) }}"
               class="px-4 py-2 rounded-xl text-xs font-black transition-all whitespace-nowrap {{ $currentStatus === 'locked' ? 'bg-amber-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200' }}">
                Locked
            </a>
            <a href="{{ route('tenant.gift-cards.index', array_merge(request()->except('page'), ['status' => 'expired'])) }}"
               class="px-4 py-2 rounded-xl text-xs font-black transition-all whitespace-nowrap {{ $currentStatus === 'expired' ? 'bg-rose-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200' }}">
                Expired
            </a>
            <a href="{{ route('tenant.gift-cards.index', array_merge(request()->except('page'), ['status' => 'disabled'])) }}"
               class="px-4 py-2 rounded-xl text-xs font-black transition-all whitespace-nowrap {{ $currentStatus === 'disabled' ? 'bg-slate-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200' }}">
                Disabled
            </a>
        </div>

        <!-- Search Input -->
        <form method="GET" action="{{ route('tenant.gift-cards.index') }}" class="relative w-full md:w-72">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Code, Notes, Customer..."
                   class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-xs font-bold text-slate-900 dark:text-white placeholder-slate-400 outline-none focus:ring-2 focus:ring-amber-500 shadow-sm">
            <svg class="absolute left-3.5 top-3 text-slate-400" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            @if(request('search'))
                <a href="{{ route('tenant.gift-cards.index', request()->except('search')) }}" class="absolute right-3 top-2.5 text-xs text-slate-400 hover:text-slate-600">✕</a>
            @endif
        </form>
    </div>

    {{-- Gift Cards Table --}}
    <div class="glass-card rounded-[2.5rem] border border-slate-100 dark:border-slate-800 bg-white/90 dark:bg-slate-900/60 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/30 text-slate-400 dark:text-slate-500 uppercase tracking-widest text-[9px] font-black">
                        <th class="py-4 px-6">Gift Card Code & PIN</th>
                        <th class="py-4 px-6">Balance / Face Value</th>
                        <th class="py-4 px-6">Status</th>
                        <th class="py-4 px-6">Expiry</th>
                        <th class="py-4 px-6">Notes / Recipient</th>
                        <th class="py-4 px-6 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($giftCards as $card)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all font-medium">
                        <!-- Code & PIN -->
                        <td class="py-4 px-6">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 flex items-center justify-center font-black">
                                    🎁
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-black text-sm text-slate-900 dark:text-white">{{ $card->code }}</span>
                                        <button type="button" @click="copyText('{{ $card->code }}')" class="text-slate-400 hover:text-amber-600" title="Copy Code">
                                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Balance & Initial Amount -->
                        <td class="py-4 px-6">
                            <div class="font-black text-slate-900 dark:text-white text-sm">
                                ₹{{ number_format($card->current_balance, 2) }}
                            </div>
                            <div class="text-[10px] text-slate-400 font-bold">
                                of ₹{{ number_format($card->initial_amount, 2) }}
                            </div>
                        </td>

                        <!-- Status Badge -->
                        <td class="py-4 px-6">
                            @if($card->isLocked())
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800 text-[10px] font-black uppercase tracking-wider">
                                    🔒 Locked ({{ $card->failed_attempts }} fails)
                                </span>
                            @elseif($card->isExpired())
                                <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 text-[10px] font-black uppercase tracking-wider">
                                    Expired
                                </span>
                            @elseif($card->status === 'redeemed' || $card->current_balance <= 0)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800 text-[10px] font-black uppercase tracking-wider">
                                    Redeemed
                                </span>
                            @elseif($card->status === 'active')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 text-[10px] font-black uppercase tracking-wider">
                                    ● Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 text-[10px] font-black uppercase tracking-wider">
                                    Disabled
                                </span>
                            @endif
                        </td>

                        <!-- Expiry -->
                        <td class="py-4 px-6 text-slate-500 font-bold">
                            @if($card->expires_at)
                                <div>{{ $card->expires_at->toFormattedDateString() }}</div>
                                <div class="text-[9px] text-slate-400">{{ $card->expires_at->diffForHumans() }}</div>
                            @else
                                <span class="text-slate-400 italic">No Expiry (Lifetime)</span>
                            @endif
                        </td>

                        <!-- Notes & Recipient -->
                        <td class="py-4 px-6">
                            @if($card->redeemedByCustomer)
                                <div class="font-bold text-slate-800 dark:text-slate-200">
                                    Redeemed by {{ $card->redeemedByCustomer->name }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-bold">
                                    {{ $card->redeemedByCustomer->phone }} ({{ $card->redeemed_at ? $card->redeemed_at->diffForHumans() : 'Redeemed' }})
                                </div>
                            @elseif($card->notes)
                                <div class="text-slate-700 dark:text-slate-300 max-w-xs truncate font-medium">
                                    {{ $card->notes }}
                                </div>
                            @else
                                <span class="text-slate-400 italic">—</span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-6 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                {{-- Print / Share Slip --}}
                                <button type="button" @click="openPrint(@js($card))"
                                        class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-amber-500 hover:text-white transition-all shadow-sm"
                                        title="Print Customer Voucher Card">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                </button>

                                {{-- Unlock button if locked --}}
                                @if($card->isLocked())
                                <form method="POST" action="{{ route('tenant.gift-cards.unlock', $card->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="p-2 rounded-xl bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 hover:bg-amber-200 transition-all shadow-sm" title="Unlock Card">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                    </button>
                                </form>
                                @endif

                                {{-- Toggle Active / Disabled --}}
                                @if($card->status !== 'redeemed')
                                <form method="POST" action="{{ route('tenant.gift-cards.toggle', $card->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="p-2 rounded-xl {{ $card->status === 'active' ? 'bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-amber-600' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 hover:bg-emerald-100' }} transition-all shadow-sm"
                                            title="{{ $card->status === 'active' ? 'Disable Card' : 'Enable Card' }}">
                                        @if($card->status === 'active')
                                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        @else
                                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        @endif
                                    </button>
                                </form>
                                @endif

                                {{-- Delete --}}
                                <form method="POST" action="{{ route('tenant.gift-cards.destroy', $card->id) }}" class="inline" onsubmit="return confirm('Permanently delete gift card {{ $card->code }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 hover:bg-rose-500 hover:text-white transition-all shadow-sm" title="Delete">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-16 text-center text-slate-400">
                            <div class="text-4xl mb-3">🎁</div>
                            <p class="font-black text-sm uppercase tracking-wider text-slate-600 dark:text-slate-300">No Gift Cards Found</p>
                            <p class="text-xs text-slate-400 mt-1">Issue a single gift card or bulk generate promotional batches using the buttons above.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($giftCards->hasPages())
        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $giftCards->links() }}
        </div>
        @endif
    </div>

    {{-- Create Single Card Modal --}}
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="showCreateModal = false" class="bg-white dark:bg-slate-900 rounded-[2.5rem] max-w-lg w-full p-6 md:p-8 space-y-6 shadow-2xl border border-slate-100 dark:border-slate-800 animate-in fade-in zoom-in-95 duration-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 flex items-center justify-center text-xl font-black">
                        🎁
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">Issue New Gift Card</h3>
                        <p class="text-xs text-slate-400">Generate a custom or auto-numbered voucher code</p>
                    </div>
                </div>
                <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form action="{{ route('tenant.gift-cards.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Face Value / Balance (₹) *</label>
                        <input type="number" step="1" name="initial_amount" placeholder="e.g. 1000" required
                               class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Expiry Date (Optional)</label>
                        <input type="date" name="expires_at" min="{{ date('Y-m-d') }}"
                               class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Custom Code (Optional)</label>
                    <input type="text" name="code" placeholder="Leave empty for auto GC-XXXX"
                           class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-4 py-2.5 text-slate-900 dark:text-white uppercase focus:ring-2 focus:ring-amber-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Campaign Notes / Recipient Name</label>
                    <textarea name="notes" rows="2" placeholder="e.g. Birthday Gift for Rahul Sharma / Corporate Festive Voucher"
                              class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-4 py-2 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showCreateModal = false" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-black bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-lg shadow-amber-200 dark:shadow-none hover:scale-[1.02] transition-all">Issue Card</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Bulk Generation Modal --}}
    <div x-show="showBulkModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="showBulkModal = false" class="bg-white dark:bg-slate-900 rounded-[2.5rem] max-w-lg w-full p-6 md:p-8 space-y-6 shadow-2xl border border-slate-100 dark:border-slate-800 animate-in fade-in zoom-in-95 duration-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center text-xl font-black">
                        ⚡
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">Bulk Batch Generation</h3>
                        <p class="text-xs text-slate-400">Generate up to 100 unique cards in one click</p>
                    </div>
                </div>
                <button type="button" @click="showBulkModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form action="{{ route('tenant.gift-cards.bulk') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Batch Quantity *</label>
                        <input type="number" step="1" min="1" max="100" name="quantity" value="10" required
                               class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Card Denomination (₹) *</label>
                        <input type="number" step="1" name="amount" placeholder="e.g. 500" required
                               class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Code Prefix (e.g. FEST)</label>
                        <input type="text" name="prefix" placeholder="GC (default)" maxlength="8"
                               class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-4 py-2.5 text-slate-900 dark:text-white uppercase focus:ring-2 focus:ring-indigo-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Expiry Date (Optional)</label>
                        <input type="date" name="expires_at" min="{{ date('Y-m-d') }}"
                               class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Campaign Label / Batch Tag</label>
                    <input type="text" name="notes" placeholder="e.g. Festive Promotional Giveaway Batch Q4"
                           class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showBulkModal = false" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-black bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-200 dark:shadow-none transition-all">Generate Batch</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Verify Card Modal --}}
    <div x-show="showCheckModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="showCheckModal = false" class="bg-white dark:bg-slate-900 rounded-[2.5rem] max-w-md w-full p-6 md:p-8 space-y-6 shadow-2xl border border-slate-100 dark:border-slate-800 animate-in fade-in zoom-in-95 duration-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center font-bold">
                        🔍
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">Verify Gift Card</h3>
                        <p class="text-xs text-slate-400">Instant lookup for card balance and validity</p>
                    </div>
                </div>
                <button type="button" @click="showCheckModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form @submit.prevent="checkCard()" class="space-y-4">
                <div>
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase mb-1">Gift Card Code</label>
                    <input type="text" x-model="checkForm.code" required placeholder="e.g. GC-A942-8821-1029"
                           class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold px-4 py-2.5 text-slate-900 dark:text-white font-mono uppercase focus:ring-2 focus:ring-emerald-500">
                </div>

                <div x-show="checkForm.error" class="p-3 rounded-xl bg-rose-50 text-rose-700 text-xs font-bold border border-rose-200" x-text="checkForm.error"></div>

                <div x-show="checkForm.result" class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300">Live Available Balance:</span>
                        <span class="text-xl font-black text-emerald-600" x-text="'₹' + Number(checkForm.result?.current_balance || 0).toFixed(2)"></span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                        <span>Original Value:</span>
                        <span class="font-bold" x-text="'₹' + Number(checkForm.result?.initial_amount || 0).toFixed(2)"></span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                        <span>Expiry Date:</span>
                        <span class="font-bold" x-text="checkForm.result?.expires_at"></span>
                    </div>
                </div>

                <button type="submit" :disabled="checkForm.loading"
                        class="w-full py-3 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-200 dark:shadow-none transition-all disabled:opacity-50">
                    <span x-text="checkForm.loading ? 'Verifying...' : 'Check Balance Now'">Check Balance Now</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Printable Voucher Card Modal --}}
    <div x-show="showPrintModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="showPrintModal = false" class="bg-white dark:bg-slate-900 rounded-[2.5rem] max-w-md w-full p-6 md:p-8 space-y-6 shadow-2xl border border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-black text-slate-900 dark:text-white">Customer Gift Voucher</h3>
                <button type="button" @click="showPrintModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            {{-- Voucher Presentation Card --}}
            <div id="printableGiftCard" class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-900 via-amber-950 to-slate-900 text-white p-6 shadow-xl border border-amber-500/30 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">🎁</span>
                        <span class="text-xs font-black uppercase tracking-widest text-amber-300">Official Gift Card</span>
                    </div>
                    <div class="text-right">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-amber-200/80 block">Value</span>
                        <span class="text-2xl font-black text-white" x-text="'₹' + Number(activeCardForPrint?.initial_amount || 0).toFixed(2)"></span>
                    </div>
                </div>

                <div class="py-3 px-4 rounded-xl bg-white/10 backdrop-blur-md border border-white/10 space-y-1">
                    <span class="text-[9px] uppercase tracking-widest text-slate-300 font-bold block">Card Code</span>
                    <span class="font-mono text-base font-black tracking-widest text-amber-300 block select-all" x-text="activeCardForPrint?.code"></span>
                </div>

                <div class="py-2 px-3 rounded-lg bg-black/30 text-xs">
                    <span class="text-[9px] text-slate-400 uppercase font-bold block">Valid Until</span>
                    <span class="text-[11px] font-bold text-slate-200" x-text="activeCardForPrint?.expires_at ? new Date(activeCardForPrint.expires_at).toLocaleDateString() : 'No Expiry'"></span>
                </div>

                <p class="text-[9px] text-slate-400 text-center font-medium">Redeemable online at website checkout or at retail counter using this code.</p>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" @click="window.print()" class="flex-1 py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-black text-xs shadow-md transition-all">
                    🖨️ Print Voucher Slip
                </button>
                <button type="button" @click="copyText(`Gift Card Code: ${activeCardForPrint?.code}\nAmount: ₹${activeCardForPrint?.initial_amount}`)"
                        class="px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-black text-xs hover:bg-slate-200 transition-all">
                    📋 Copy Text
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
