@extends('layouts.tenant')
@section('title', 'Agreements & Contracts')
@section('page-title', 'Agreements Command Center')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
@endpush

@section('content')
<div class="space-y-6" x-data="agreementsDashboard()">

    <!-- Header & Action Row -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-indigo-600/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <i class="ti ti-file-certificate text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Agreements & Contracts</h1>
                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Create, manage, and print official business contracts</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button @click="openCreateModal()" class="flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs uppercase tracking-wider transition-all duration-300 shadow-lg shadow-indigo-600/20 active:scale-95">
                <i class="ti ti-plus text-sm"></i> New Agreement
            </button>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <a href="{{ route('tenant.agreements.index') }}" class="glass-card rounded-3xl p-5 border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900/40 flex flex-col gap-2 group hover:-translate-y-1 transition-all duration-300 {{ !request('status') ? 'ring-2 ring-indigo-500' : '' }}">
            <div class="flex justify-between items-start">
                <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Total Agreements</span>
                <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 flex items-center justify-center">
                    <i class="ti ti-files text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-900 dark:text-white">{{ $stats['total'] }}</p>
        </a>

        <a href="{{ route('tenant.agreements.index', ['status' => 'active']) }}" class="glass-card rounded-3xl p-5 border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900/40 flex flex-col gap-2 group hover:-translate-y-1 transition-all duration-300 {{ request('status') === 'active' ? 'ring-2 ring-emerald-500' : '' }}">
            <div class="flex justify-between items-start">
                <span class="text-[9px] font-black uppercase tracking-widest text-emerald-500">Active</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 flex items-center justify-center">
                    <i class="ti ti-circle-check text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ $stats['active'] }}</p>
        </a>

        <a href="{{ route('tenant.agreements.index', ['status' => 'draft']) }}" class="glass-card rounded-3xl p-5 border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900/40 flex flex-col gap-2 group hover:-translate-y-1 transition-all duration-300 {{ request('status') === 'draft' ? 'ring-2 ring-amber-500' : '' }}">
            <div class="flex justify-between items-start">
                <span class="text-[9px] font-black uppercase tracking-widest text-amber-500">Drafts</span>
                <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 flex items-center justify-center">
                    <i class="ti ti-edit text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ $stats['draft'] }}</p>
        </a>

        <a href="{{ route('tenant.agreements.index', ['status' => 'expired']) }}" class="glass-card rounded-3xl p-5 border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900/40 flex flex-col gap-2 group hover:-translate-y-1 transition-all duration-300 {{ request('status') === 'expired' ? 'ring-2 ring-rose-500' : '' }}">
            <div class="flex justify-between items-start">
                <span class="text-[9px] font-black uppercase tracking-widest text-rose-500">Expired</span>
                <div class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 flex items-center justify-center">
                    <i class="ti ti-clock-stop text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-rose-600 dark:text-rose-400">{{ $stats['expired'] }}</p>
        </a>

        <div class="glass-card rounded-3xl p-5 border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900/40 flex flex-col gap-2 col-span-2 lg:col-span-1">
            <div class="flex justify-between items-start">
                <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Total Value</span>
                <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 flex items-center justify-center">
                    <i class="ti ti-currency-rupee text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-900 dark:text-white">₹{{ number_format($stats['total_value'], 0) }}</p>
        </div>
    </div>

    <!-- Main List Card -->
    <div class="glass-card rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-xl overflow-hidden bg-white dark:bg-slate-900/40">
        
        <!-- Filters & Search Toolbar -->
        <div class="p-6 border-b border-slate-50 dark:border-slate-800/60 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/30 dark:bg-slate-950/30">
            <div class="flex flex-wrap items-center gap-2">
                @php
                    $categories = ['All', 'Customer', 'Vendor', 'Rental', 'Service', 'General'];
                @endphp
                @foreach($categories as $cat)
                    <a href="{{ route('tenant.agreements.index', array_merge(request()->query(), ['category' => $cat === 'All' ? null : $cat])) }}"
                       class="px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all {{ (request('category') == $cat || ($cat == 'All' && !request('category'))) ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:text-slate-800 dark:hover:text-white' }}">
                        {{ $cat }}
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('tenant.agreements.index') }}" class="flex items-center gap-2">
                @if(request('status')) <input type="hidden" name="status" value="{{ request('status') }}"> @endif
                @if(request('category')) <input type="hidden" name="category" value="{{ request('category') }}"> @endif
                <div class="relative">
                    <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search agreement #, title, party..." 
                           class="pl-9 pr-4 py-2 bg-white dark:bg-slate-950/50 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-800 dark:text-slate-200 outline-none focus:ring-2 focus:ring-indigo-500 w-64 placeholder:text-slate-400">
                </div>
                @if(request('search'))
                    <a href="{{ route('tenant.agreements.index', array_merge(request()->except('search'))) }}" class="p-2 text-slate-400 hover:text-rose-500">
                        <i class="ti ti-x"></i>
                    </a>
                @endif
            </form>
        </div>

        <!-- Agreements Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/60 bg-slate-50/10 dark:bg-slate-950/20">
                        <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Agreement</th>
                        <th class="px-4 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Second Party</th>
                        <th class="px-4 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Category</th>
                        <th class="px-4 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Period</th>
                        <th class="px-4 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Contract Value</th>
                        <th class="px-4 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/40">
                    @forelse($agreements as $agr)
                        <tr class="hover:bg-indigo-50/30 dark:hover:bg-indigo-950/20 transition-colors group">
                            <!-- Agreement Title & Number -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-black text-xs shrink-0">
                                        <i class="ti ti-certificate"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-black text-slate-900 dark:text-white uppercase leading-tight">{{ $agr->title }}</p>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 tracking-wider">{{ $agr->agreement_number }}</span>
                                            <span class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800" title="AES-256 Encrypted & SHA-256 Tamper Protected">
                                                🔒 Encrypted
                                            </span>
                                            @if($agr->digital_signature)
                                                <span class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800" title="Digitally Signed">
                                                    ✍️ Signed
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Second Party Details -->
                            <td class="px-4 py-4">
                                <p class="text-xs font-black text-slate-800 dark:text-slate-200">{{ $agr->party_name }}</p>
                                @if($agr->party_phone)
                                    <p class="text-[9px] font-bold text-slate-400 flex items-center gap-1 mt-0.5">
                                        <i class="ti ti-phone text-[10px]"></i> {{ $agr->party_phone }}
                                    </p>
                                @endif
                            </td>

                            <!-- Category -->
                            <td class="px-4 py-4">
                                <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                    {{ $agr->category }}
                                </span>
                            </td>

                            <!-- Period -->
                            <td class="px-4 py-4 text-xs">
                                <p class="font-bold text-slate-700 dark:text-slate-300">{{ $agr->start_date ? $agr->start_date->format('d M, Y') : 'N/A' }}</p>
                                <p class="text-[9px] font-bold text-slate-400">to {{ $agr->end_date ? $agr->end_date->format('d M, Y') : 'Ongoing' }}</p>
                            </td>

                            <!-- Total Value -->
                            <td class="px-4 py-4">
                                <p class="text-xs font-black text-slate-900 dark:text-white">₹{{ number_format($agr->total_value, 2) }}</p>
                                @if($agr->payment_terms)
                                    <p class="text-[9px] font-bold text-slate-400 truncate max-w-[120px]" title="{{ $agr->payment_terms }}">{{ $agr->payment_terms }}</p>
                                @endif
                            </td>

                            <!-- Status Badge & Quick Change -->
                            <td class="px-4 py-4">
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" class="px-3 py-1 rounded-xl text-[9px] font-black uppercase tracking-wider flex items-center gap-1 border {{ $agr->status_badge['bg'] }} {{ $agr->status_badge['text'] }} {{ $agr->status_badge['border'] }}">
                                        <span>{{ $agr->status }}</span>
                                        <i class="ti ti-chevron-down text-[10px]"></i>
                                    </button>
                                    <div x-show="open" @click.away="open = false" x-cloak class="absolute left-0 mt-1 w-32 bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-100 dark:border-slate-800 p-1.5 z-20 space-y-1">
                                        @foreach(['active', 'draft', 'completed', 'expired', 'terminated'] as $st)
                                            <button @click="changeStatus({{ $agr->id }}, '{{ $st }}'); open = false" class="w-full text-left px-2.5 py-1 rounded-lg text-[9px] font-black uppercase hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                                                {{ $st }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Print Official Document -->
                                    <a href="{{ route('tenant.agreements.print', $agr->id) }}" target="_blank" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-indigo-600 hover:text-white transition-all shadow-sm" title="View & Print Contract">
                                        <i class="ti ti-printer text-sm"></i>
                                    </a>

                                    <!-- Security Vault & Audit Trail -->
                                    <button @click="openAuditModal({{ $agr->id }})" class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-600 hover:text-white transition-all shadow-sm" title="Security Vault & Cryptographic Audit Trail">
                                        <i class="ti ti-shield-check text-sm"></i>
                                    </button>

                                    <!-- Edit -->
                                    <button @click="openEditModal({{ $agr->toJson() }})" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-amber-500 hover:text-white transition-all shadow-sm" title="Edit Agreement">
                                        <i class="ti ti-edit text-sm"></i>
                                    </button>

                                    <!-- 30-min Expiring Signed Link (OWASP Rule #3) -->
                                    <button @click="copySecureLink({{ $agr->id }})" class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-600 hover:text-white transition-all shadow-sm" title="Copy 30-Min Encrypted Expiring Link">
                                        <i class="ti ti-lock-access text-sm"></i>
                                    </button>

                                    @if($agr->attachment_path)
                                        <!-- Download Physical Signed Document (Decrypted on the fly) -->
                                        <a href="{{ route('tenant.agreements.download-attachment', $agr->id) }}" class="p-2 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 hover:bg-blue-600 hover:text-white transition-all shadow-sm" title="Download Signed Attachment (AES-256 Vault / SHA-256 Verified)">
                                            <i class="ti ti-paperclip text-sm"></i>
                                        </a>
                                    @endif

                                    <!-- WhatsApp Share -->
                                    @if($agr->party_phone)
                                        <button @click="sendWhatsApp('{{ $agr->party_phone }}', '{{ $agr->party_name }}', '{{ $agr->agreement_number }}', '{{ $agr->title }}', '{{ number_format($agr->total_value, 2) }}')" class="p-2 rounded-xl bg-green-50 dark:bg-green-950/40 text-green-600 dark:text-green-400 hover:bg-green-600 hover:text-white transition-all shadow-sm" title="Share via WhatsApp">
                                            <i class="ti ti-brand-whatsapp text-sm"></i>
                                        </button>
                                    @endif

                                    <!-- Delete -->
                                    <form method="POST" action="{{ route('tenant.agreements.destroy', $agr->id) }}" onsubmit="return confirm('Are you sure you want to delete this agreement?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 hover:bg-rose-500 hover:text-white transition-all shadow-sm" title="Delete">
                                            <i class="ti ti-trash text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-20 text-center opacity-40">
                                <i class="ti ti-file-certificate text-5xl text-indigo-300 dark:text-slate-600 mb-3 block"></i>
                                <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">No agreements found</p>
                                <p class="text-[10px] text-slate-400 mt-1">Click "New Agreement" above to create your first contract.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($agreements->hasPages())
            <div class="px-6 py-4 border-t border-slate-50 dark:border-slate-800 bg-slate-50/10 dark:bg-slate-950/20">
                {{ $agreements->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-[100] flex items-center justify-center p-4">
        <div @click.away="closeModal()" class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-[2.5rem] shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in duration-200">
            
            <!-- Modal Header -->
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/60 dark:bg-slate-950/40">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-black">
                        <i class="ti ti-file-text text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider" x-text="isEdit ? 'Edit Agreement' : 'Create New Agreement'"></h3>
                        <p class="text-[10px] font-bold text-slate-400 tracking-wide mt-0.5">Fill in contract specifications, party details, and clauses</p>
                    </div>
                </div>
                <button @click="closeModal()" class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-rose-500 flex items-center justify-center">
                    <i class="ti ti-x"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <form :action="isEdit ? `{{ url('agreements') }}/${form.id}` : `{{ route('tenant.agreements.store') }}`" method="POST" enctype="multipart/form-data" class="flex-1 overflow-y-auto p-6 space-y-5 custom-scrollbar">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <!-- Template Selector (Only for New) -->
                <div x-show="!isEdit" class="p-4 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/40 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-[10px] font-black text-indigo-700 dark:text-indigo-300 uppercase tracking-widest">
                            <i class="ti ti-sparkles mr-1"></i> Quick Contract Template
                        </label>
                        <span class="text-[9px] text-indigo-500 font-bold">Auto-fills title & clauses</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        <template x-for="(tpl, idx) in templates" :key="idx">
                            <button type="button" @click="applyTemplate(tpl)" class="p-2.5 rounded-xl border border-indigo-200 dark:border-indigo-800/80 bg-white dark:bg-slate-900 text-left hover:border-indigo-600 transition-all text-xs font-bold group">
                                <span class="block text-[11px] font-black text-slate-800 dark:text-slate-100 group-hover:text-indigo-600" x-text="tpl.title"></span>
                                <span class="block text-[9px] text-slate-400 capitalize" x-text="tpl.category"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Title & Category Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2 space-y-1">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Agreement Title *</label>
                        <input type="text" name="title" x-model="form.title" required placeholder="e.g. Equipment Rental & Maintenance Agreement"
                               class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-800 dark:text-slate-200 outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Category *</label>
                        <select name="category" x-model="form.category" required class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-800 dark:text-slate-200 outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="Customer">Customer</option>
                            <option value="Vendor">Vendor</option>
                            <option value="Rental">Rental / Lease</option>
                            <option value="Service">Service / SLA</option>
                            <option value="Employee">Employee / NDA</option>
                            <option value="General">General</option>
                        </select>
                    </div>
                </div>

                <!-- Party Type & Auto-Selection -->
                <div class="p-4 rounded-2xl bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider">Second Party Information</label>
                        <div class="flex gap-2">
                            <label class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-400 cursor-pointer">
                                <input type="radio" name="party_type" value="customer" x-model="form.party_type" @change="onPartyTypeChange()" class="accent-indigo-600"> Customer
                            </label>
                            <label class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-400 cursor-pointer">
                                <input type="radio" name="party_type" value="supplier" x-model="form.party_type" @change="onPartyTypeChange()" class="accent-indigo-600"> Vendor
                            </label>
                            <label class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-400 cursor-pointer">
                                <input type="radio" name="party_type" value="other" x-model="form.party_type" @change="onPartyTypeChange()" class="accent-indigo-600"> Other
                            </label>
                        </div>
                    </div>

                    <!-- Auto-pick dropdown -->
                    <div x-show="form.party_type === 'customer'" class="space-y-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Select Existing Customer (Optional)</label>
                        <select @change="pickCustomer($event.target.value)" class="w-full px-4 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-800 dark:text-slate-200">
                            <option value="">-- Or enter manually below --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" data-name="{{ $c->name }}" data-phone="{{ $c->phone }}" data-email="{{ $c->email }}" data-address="{{ $c->address }}">
                                    {{ $c->name }} ({{ $c->phone ?? 'No Phone' }})
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="customer_id" :value="form.customer_id">
                    </div>

                    <div x-show="form.party_type === 'supplier'" class="space-y-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Select Existing Vendor (Optional)</label>
                        <select @change="pickSupplier($event.target.value)" class="w-full px-4 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-800 dark:text-slate-200">
                            <option value="">-- Or enter manually below --</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" data-name="{{ $s->name }}" data-phone="{{ $s->phone }}" data-email="{{ $s->email }}" data-address="{{ $s->address }}">
                                    {{ $s->name }} ({{ $s->phone ?? 'No Phone' }})
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="supplier_id" :value="form.supplier_id">
                    </div>

                    <!-- Party Inputs -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="text-[9px] font-black text-slate-400 uppercase">Party Name *</label>
                            <input type="text" name="party_name" x-model="form.party_name" required placeholder="Full Name / Company Name"
                                   class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-bold">
                        </div>
                        <div>
                            <label class="text-[9px] font-black text-slate-400 uppercase">Phone Number</label>
                            <input type="text" name="party_phone" x-model="form.party_phone" placeholder="Phone"
                                   class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-bold">
                        </div>
                        <div>
                            <label class="text-[9px] font-black text-slate-400 uppercase">Email Address</label>
                            <input type="email" name="party_email" x-model="form.party_email" placeholder="email@domain.com"
                                   class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-bold">
                        </div>
                    </div>

                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase">Address / Location</label>
                        <input type="text" name="party_address" x-model="form.party_address" placeholder="Registered address"
                               class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-bold">
                    </div>
                </div>

                <!-- Financial & Timeline Details -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Start Date *</label>
                        <input type="date" name="start_date" x-model="form.start_date" required
                               class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs font-bold">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">End Date (Expiry)</label>
                        <input type="date" name="end_date" x-model="form.end_date"
                               class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs font-bold">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Contract Value (₹)</label>
                        <input type="number" step="0.01" min="0" name="total_value" x-model="form.total_value" placeholder="0.00"
                               class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs font-bold">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Status *</label>
                        <select name="status" x-model="form.status" required class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs font-bold">
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="expired">Expired</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                </div>

                <!-- Payment Terms & Witness -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Payment Terms</label>
                        <input type="text" name="payment_terms" x-model="form.payment_terms" placeholder="e.g. 50% Advance, balance upon delivery within 30 days"
                               class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs font-bold">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Witness Name</label>
                        <input type="text" name="witness_name" x-model="form.witness_name" placeholder="Full name of witness"
                               class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs font-bold">
                    </div>
                </div>

                <!-- Agreement Terms & Clauses -->
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Agreement Clauses & Content</label>
                        <span class="text-[9px] text-slate-400">Formatted text will print in the official contract</span>
                    </div>
                    <textarea name="content" x-model="form.content" rows="8" placeholder="Enter terms and clauses here..."
                              class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs font-mono leading-relaxed outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <!-- Encrypted Physical Document Attachment -->
                <div class="p-4 rounded-2xl bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="ti ti-lock text-emerald-600 dark:text-emerald-400"></i> Encrypted Vault Attachment (Optional)
                        </label>
                        <span class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded border border-emerald-200 dark:border-emerald-800">
                            AES-256 Storage Encryption
                        </span>
                    </div>
                    <p class="text-[10px] text-slate-400">Attach signed paper contract (PDF, PNG, JPG - max 10MB). Uploaded files are immediately encrypted at rest on disk with SHA-256 tamper verification.</p>
                    <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png"
                           class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-indigo-600 file:text-white hover:file:bg-indigo-700">
                </div>

                <!-- Internal Notes -->
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Internal Notes (Optional)</label>
                    <input type="text" name="notes" x-model="form.notes" placeholder="Confidential internal notes"
                           class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs font-bold">
                </div>

                <!-- Form Action Buttons -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                    <button type="button" @click="closeModal()" class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 text-xs font-bold uppercase text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                        Cancel
                    </button>
                    <button type="submit" class="px-7 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-wider shadow-lg shadow-indigo-600/30">
                        <span x-text="isEdit ? 'Save Changes' : 'Create Agreement'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Security Vault & Cryptographic Audit Trail Modal -->
    <div x-show="showAuditModal" x-cloak class="fixed inset-0 bg-slate-950/85 backdrop-blur-md z-[110] flex items-center justify-center p-4">
        <div @click.away="closeAuditModal()" class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-[2.5rem] shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden animate-in fade-in zoom-in duration-200">
            <!-- Header -->
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/60 dark:bg-slate-950/40">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-black">
                        <i class="ti ti-shield-lock text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Cryptographic Security Vault</h3>
                        <p class="text-[10px] font-bold text-slate-400 mt-0.5" x-text="auditData ? auditData.agreement_number + ' • Verification Report' : 'Loading security proof...'"></p>
                    </div>
                </div>
                <button @click="closeAuditModal()" class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-rose-500 flex items-center justify-center">
                    <i class="ti ti-x"></i>
                </button>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6 space-y-5 custom-scrollbar" x-show="auditData">
                <!-- Status Badges Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800">
                        <span class="text-[8px] font-black uppercase text-emerald-600 tracking-wider block">Storage Encryption</span>
                        <p class="text-xs font-black text-emerald-700 dark:text-emerald-300 mt-1" x-text="auditData ? auditData.algorithm : 'AES-256-CBC'"></p>
                        <span class="text-[9px] text-emerald-500">At-Rest Protection</span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800">
                        <span class="text-[8px] font-black uppercase text-blue-600 tracking-wider block">Integrity Status</span>
                        <p class="text-xs font-black text-blue-700 dark:text-blue-300 mt-1" x-text="auditData && auditData.integrity_valid ? '✓ Verified Intact' : 'Tamper Detected'"></p>
                        <span class="text-[9px] text-blue-500">SHA-256 Certified</span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800">
                        <span class="text-[8px] font-black uppercase text-purple-600 tracking-wider block">Digital Signature</span>
                        <p class="text-xs font-black text-purple-700 dark:text-purple-300 mt-1" x-text="auditData && auditData.has_signature ? '✓ Digitally Signed' : 'Pending'"></p>
                        <span class="text-[9px] text-purple-500" x-text="auditData && auditData.signed_at ? new Date(auditData.signed_at).toLocaleDateString() : 'Unsigned'"></span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                        <span class="text-[8px] font-black uppercase text-slate-500 tracking-wider block">Signer Origin</span>
                        <p class="text-xs font-black text-slate-800 dark:text-slate-200 mt-1 truncate" x-text="auditData && auditData.signed_ip ? auditData.signed_ip : 'Internal'"></p>
                        <span class="text-[9px] text-slate-400">IP Verified</span>
                    </div>
                </div>

                <!-- Checksum Display -->
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-1">
                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest block">SHA-256 Cryptographic Document Checksum</span>
                    <p class="text-[11px] font-mono text-indigo-600 dark:text-indigo-400 break-all select-all font-bold" x-text="auditData ? auditData.checksum : 'N/A'"></p>
                </div>

                <!-- Audit Log History -->
                <div class="space-y-2">
                    <h4 class="text-[10px] font-black uppercase tracking-wider text-slate-400">Immutable Audit Trail (Recent Activity)</h4>
                    <div class="border border-slate-100 dark:border-slate-800 rounded-2xl overflow-hidden divide-y divide-slate-100 dark:divide-slate-800/60">
                        <template x-for="(log, idx) in (auditData ? auditData.logs : [])" :key="idx">
                            <div class="p-3 bg-white dark:bg-slate-900 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-2 h-2 rounded-full" :class="log.severity === 'critical' || log.severity === 'warning' ? 'bg-rose-500' : 'bg-emerald-500'"></span>
                                    <div>
                                        <p class="font-bold text-slate-800 dark:text-slate-200 capitalize text-xs" x-text="log.action ? log.action.replaceAll('_', ' ') : 'Action'"></p>
                                        <p class="text-[9px] text-slate-400" x-text="log.created_at ? new Date(log.created_at).toLocaleString() : ''"></p>
                                    </div>
                                </div>
                                <span class="text-[9px] font-mono font-bold text-slate-400" x-text="log.ip_address || 'Internal'"></span>
                            </div>
                        </template>
                        <div x-show="!auditData || !auditData.logs || auditData.logs.length === 0" class="p-6 text-center text-slate-400 text-xs">
                            No external security events recorded yet.
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t border-slate-100 dark:border-slate-800 flex justify-end bg-slate-50/50 dark:bg-slate-950/20">
                <button type="button" @click="closeAuditModal()" class="px-5 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-xs font-bold uppercase text-slate-700 dark:text-slate-300">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function agreementsDashboard() {
        return {
            showModal: false,
            isEdit: false,
            showAuditModal: false,
            auditData: null,
            form: {
                id: null,
                title: '',
                category: 'Customer',
                party_type: 'customer',
                customer_id: null,
                supplier_id: null,
                party_name: '',
                party_phone: '',
                party_email: '',
                party_address: '',
                start_date: new Date().toISOString().split('T')[0],
                end_date: '',
                status: 'draft',
                total_value: '',
                payment_terms: 'Full payment due within 30 days',
                content: '',
                witness_name: '',
                notes: ''
            },
            templates: [
                {
                    title: 'Customer Sales Contract',
                    category: 'Customer',
                    payment_terms: '50% advance upon signing, remaining 50% on final delivery.',
                    content: `1. SCOPE OF AGREEMENT: The Seller agrees to provide the products/services listed herein to the Buyer in accordance with standard quality specifications.\n\n2. DELIVERY & INSPECTION: All goods shall be delivered to the specified address. The Buyer shall inspect goods within 48 hours of receipt and report any defects in writing.\n\n3. PAYMENT TERMS: Payments shall be made strictly as per the agreed schedule. Overdue amounts will accrue interest at 1.5% per month.\n\n4. WARRANTY & REPLACEMENT: Products are covered by standard manufacturer warranty against defective craftsmanship.\n\n5. JURISDICTION: This contract is governed by applicable commercial laws in the jurisdiction of the Seller's registered office.`
                },
                {
                    title: 'Equipment Rental & Lease',
                    category: 'Rental',
                    payment_terms: 'Monthly rent payable in advance by the 5th of each calendar month. Refundable security deposit required.',
                    content: `1. LEASE PURPOSE: The Lessor hereby leases the specified equipment to the Lessee solely for authorized business operations.\n\n2. SECURITY DEPOSIT: The Lessee shall maintain a refundable security deposit with the Lessor against damages or defaults.\n\n3. MAINTENANCE & OPERATION: The Lessee agrees to operate equipment strictly according to standard manufacturer guidelines and maintain it in clean, working condition.\n\n4. RETURN OF EQUIPMENT: Upon expiry or termination of this lease, the equipment shall be returned in the condition received, subject to normal wear and tear.\n\n5. INDEMNITY: The Lessee shall hold the Lessor harmless from any liabilities, claims, or damages arising out of the Lessee's use of the equipment.`
                },
                {
                    title: 'Service Level Agreement (SLA)',
                    category: 'Service',
                    payment_terms: 'Billed monthly/quarterly upon certification of service milestones.',
                    content: `1. SERVICES: The Service Provider shall deliver prompt, professional support services in compliance with industry best practices.\n\n2. SERVICE AVAILABILITY: The Provider guarantees a target uptime/response time of 99.5% for all critical components.\n\n3. ESCALATION & RESOLUTION: Support tickets will be acknowledged within 2 hours and resolution efforts initiated without delay.\n\n4. CONFIDENTIALITY: Both parties agree to protect and maintain confidential all non-public proprietary information.\n\n5. TERMINATION: Either party may terminate this agreement with 30 days written notice.`
                },
                {
                    title: 'Vendor Supply & NDA',
                    category: 'Vendor',
                    payment_terms: 'Net 30 days following receipt and verified QC inspection of invoice and goods.',
                    content: `1. SUPPLY COMMITMENT: The Vendor covenants to supply materials meeting certified specifications and guaranteed quantities.\n\n2. QUALITY CONTROL: Non-conforming goods will be rejected and replaced at Vendor's sole expense within 5 business days.\n\n3. CONFIDENTIALITY: The Vendor shall not disclose trade secrets, design specifications, or customer data to any third party.\n\n4. PRICE VALIDITY: Agreed supply prices shall remain firm for the entire duration of this agreement.\n\n5. FORCE MAJEURE: Neither party shall be liable for delays caused by circumstances beyond reasonable control.`
                },
                {
                    title: 'Instalment Payment Agreement',
                    category: 'Customer',
                    payment_terms: 'Divided into equal monthly instalments as per the agreed repayment schedule.',
                    content: `1. FINANCING DETAILS: The Creditor agrees to extend instalment financing for the purchase value specified in this contract.\n\n2. REPAYMENT SCHEDULE: The Debtor covenants to make timely payments on or before the due date of each month.\n\n3. DEFAULT & PENALTY: Default in payment of any instalment for more than 15 days shall entitle the Creditor to recall the entire outstanding balance.\n\n4. TITLE RETENTION: Ownership of the goods shall remain with the Creditor until all instalment payments are cleared in full.\n\n5. LEGAL RECOURSE: Legal and recovery expenses incurred in collection of unpaid sums shall be borne by the Debtor.`
                }
            ],

            openCreateModal() {
                this.isEdit = false;
                this.resetForm();
                this.applyTemplate(this.templates[0]);
                this.showModal = true;
            },

            openEditModal(data) {
                this.isEdit = true;
                this.form = {
                    id: data.id,
                    title: data.title,
                    category: data.category,
                    party_type: data.party_type || 'customer',
                    customer_id: data.customer_id,
                    supplier_id: data.supplier_id,
                    party_name: data.party_name,
                    party_phone: data.party_phone,
                    party_email: data.party_email,
                    party_address: data.party_address,
                    start_date: data.start_date ? data.start_date.split('T')[0] : '',
                    end_date: data.end_date ? data.end_date.split('T')[0] : '',
                    status: data.status,
                    total_value: data.total_value,
                    payment_terms: data.payment_terms,
                    content: data.content,
                    witness_name: data.witness_name,
                    notes: data.notes
                };
                this.showModal = true;
            },

            closeModal() {
                this.showModal = false;
            },

            resetForm() {
                this.form = {
                    id: null,
                    title: '',
                    category: 'Customer',
                    party_type: 'customer',
                    customer_id: null,
                    supplier_id: null,
                    party_name: '',
                    party_phone: '',
                    party_email: '',
                    party_address: '',
                    start_date: new Date().toISOString().split('T')[0],
                    end_date: '',
                    status: 'draft',
                    total_value: '',
                    payment_terms: '',
                    content: '',
                    witness_name: '',
                    notes: ''
                };
            },

            applyTemplate(tpl) {
                this.form.title = tpl.title;
                this.form.category = tpl.category;
                this.form.payment_terms = tpl.payment_terms;
                this.form.content = tpl.content;
            },

            onPartyTypeChange() {
                this.form.customer_id = null;
                this.form.supplier_id = null;
            },

            pickCustomer(id) {
                if (!id) return;
                const opt = document.querySelector(`select option[value="${id}"]`);
                if (opt) {
                    this.form.customer_id = id;
                    this.form.party_name = opt.dataset.name || '';
                    this.form.party_phone = opt.dataset.phone || '';
                    this.form.party_email = opt.dataset.email || '';
                    this.form.party_address = opt.dataset.address || '';
                }
            },

            pickSupplier(id) {
                if (!id) return;
                const opt = document.querySelector(`select option[value="${id}"]`);
                if (opt) {
                    this.form.supplier_id = id;
                    this.form.party_name = opt.dataset.name || '';
                    this.form.party_phone = opt.dataset.phone || '';
                    this.form.party_email = opt.dataset.email || '';
                    this.form.party_address = opt.dataset.address || '';
                }
            },

            async changeStatus(id, newStatus) {
                try {
                    const res = await fetch(`{{ url('agreements') }}/${id}/status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ status: newStatus })
                    });
                    const data = await res.json();
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error changing status');
                    }
                } catch(e) {
                    alert('Network error');
                }
            },

            async copySecureLink(id) {
                try {
                    const res = await fetch(`{{ url('agreements') }}/${id}/share-link`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ duration: 30 })
                    });
                    const data = await res.json();
                    if (data.secure_url) {
                        navigator.clipboard.writeText(data.secure_url);
                        alert('🔐 Secure Time-Limited Link Copied!\n\n• Valid for 30 minutes.\n• Cryptographically signed via HMAC-SHA256.\n• Expiration strictly enforced (OWASP Non-Negotiable Rule #3).');
                    } else {
                        alert(data.message || 'Error generating secure link');
                    }
                } catch(e) {
                    alert('Network error generating secure link');
                }
            },

            async openAuditModal(id) {
                this.auditData = null;
                this.showAuditModal = true;
                try {
                    const res = await fetch(`{{ url('agreements') }}/${id}/audit-trail`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    this.auditData = await res.json();
                } catch(e) {
                    alert('Network error loading security vault.');
                }
            },

            closeAuditModal() {
                this.showAuditModal = false;
            },

            sendWhatsApp(phone, name, agreementNo, title, value) {
                const cleanPhone = phone.replace(/[^0-9]/g, '');
                const finalPhone = cleanPhone.length === 10 ? '91' + cleanPhone : cleanPhone;
                const msg = `📜 *Official Agreement Notification*\n\nHello *${name}*,\nYour contract/agreement *${agreementNo}* (*${title}*) for value ₹${value} has been documented in our system.\n\nPlease contact our office for a signed copy or any inquiries. Thank you! 🙏`;
                window.open(`https://wa.me/${finalPhone}?text=${encodeURIComponent(msg)}`, '_blank');
            }
        };
    }
</script>
@endpush
@endsection
