@extends('layouts.tenant')

@section('title', 'User Management')

@section('content')
<div class="h-full flex flex-col space-y-6" x-data="{ 
    showModal: false, 
    editingUser: null,
    search: '',
    users: {{ $users->toJson() }},
    branches: {{ \App\Models\Branch::all()->toJson() }},
    roles: ['admin', 'manager', 'staff', 'cashier'],
    filterOpen: false,
    selectedRole: 'all',
    selectedStatus: 'all',
    
    newUser: {
        name: '',
        username: '',
        email: '',
        phone: '',
        address: '',
        password: '',
        password_confirmation: '',
        role: 'staff',
        branch_id: '',
        is_active: true,
        full_access: false,
        permissions: []
    },

    modules: [
        'Command Center', 'All Modules', 'Sales', 'Inventory', 'Manufacturing',
        'Customers', 'Reports', 'Settings', 'Quick Bill', 'Billing',
        'Purchase Orders', 'Outward', 'Inward', 'Pre Orders', 'Production',
        'Vendors', 'User', 'Stock Transfer', 'Product Master', 'WhatsApp',
        'Mail', 'Calendar', 'CRM', 'Instalments', 'Due Invoices',
        'Tally ERP', 'Business Reports', 'Deliveries', 'Anniversary',
        'Setup', 'Service', 'Membership', 'Accounting', 'Agreements', 'Website', 'Website Orders',
        'Gift Cards', 'Discount Coupons'
    ],

    toggleSelectAll() {
        if(this.newUser.permissions.length === this.modules.length) {
            this.newUser.permissions = [];
        } else {
            this.newUser.permissions = [...this.modules];
        }
    },

    resetForm() {
        this.newUser = { 
            name: '', username: '', email: '', phone: '', address: '', 
            password: '', password_confirmation: '', role: 'staff', branch_id: '', 
            is_active: true, full_access: false, permissions: [] 
        };
        this.editingUser = null;
    },

    editUser(user) {
        this.editingUser = user;
        this.newUser = { ...user, password: '', password_confirmation: '', permissions: user.permissions || [] };
        this.showModal = true;
    }
}" x-init="
    @if(isset($user))
        editUser({{ $user->toJson() }});
    @endif
">
    <!-- Session Alerts -->
    <div class="fixed top-6 left-1/2 -translate-x-1/2 z-[200] w-[400px] space-y-3">
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" 
                 class="bg-emerald-500 text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center justify-between border border-emerald-400/20 backdrop-blur-md">
                <div class="flex items-center gap-3">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    <p class="text-xs font-black uppercase tracking-widest">{{ session('success') }}</p>
                </div>
                <button @click="show = false" class="opacity-50 hover:opacity-100"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
        @endif
        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" 
                 class="bg-rose-500 text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center justify-between border border-rose-400/20 backdrop-blur-md">
                <div class="flex items-center gap-3">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p class="text-xs font-black uppercase tracking-widest">{{ session('error') }}</p>
                </div>
                <button @click="show = false" class="opacity-50 hover:opacity-100"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
        @endif
    </div>

    <!-- Header Command Center -->
    <div class="flex items-center justify-between bg-white dark:bg-slate-900 p-6 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-xl">
        <div class="flex items-center gap-6">
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight leading-none uppercase">Staff Management</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">Control system access and branch permissions</p>
            </div>
            
            <div class="h-10 w-px bg-slate-100 dark:bg-slate-800 mx-2"></div>

            <div class="flex items-center gap-8">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Total Staff</p>
                    <p class="text-lg font-black text-slate-900 dark:text-white leading-none">{{ count($users) }}</p>
                </div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Active Now</p>
                    <div class="flex items-center gap-2">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                        <p class="text-lg font-black text-slate-900 dark:text-white leading-none">{{ $users->where('is_active', true)->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button @click="resetForm(); showModal = true" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all shadow-lg shadow-blue-100 flex items-center gap-2">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                Add New User
            </button>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="flex items-center justify-between">
        <div class="relative group w-96">
            <input type="text" x-model="search" placeholder="Search staff by name, email or role..." 
                   class="w-full bg-white dark:bg-slate-900 border-none rounded-2xl px-12 py-3.5 text-xs font-bold text-slate-600 dark:text-slate-300 shadow-sm focus:ring-2 focus:ring-blue-500 transition-all uppercase tracking-tight placeholder:text-slate-300">
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-blue-500 transition-colors" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>

        <div class="flex items-center gap-2 relative">
            <button @click="filterOpen = !filterOpen" :class="filterOpen || selectedRole !== 'all' || selectedStatus !== 'all' ? 'text-blue-600 border-blue-500/30 bg-blue-50/50 dark:bg-blue-950/30' : 'text-slate-400 border-transparent bg-white dark:bg-slate-900'" class="p-3 rounded-xl transition-all shadow-sm border hover:text-blue-600 hover:border-slate-100 dark:hover:border-slate-800">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 4.5h18M3 12h18M3 19.5h18"/></svg>
            </button>
            
            <!-- Beautiful Glassmorphic Dropdown -->
            <div x-show="filterOpen" @click.away="filterOpen = false" x-cloak 
                 class="absolute right-0 top-14 w-64 bg-white/95 dark:bg-slate-950/95 border border-slate-100 dark:border-slate-800/80 rounded-[1.5rem] shadow-2xl p-5 z-50 backdrop-blur-md space-y-4">
                <div>
                    <p class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Filter by Role</p>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" @click="selectedRole = 'all'; filterOpen = false" :class="selectedRole === 'all' ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20' : 'bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-2.5 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all">All</button>
                        <template x-for="role in roles">
                            <button type="button" @click="selectedRole = role; filterOpen = false" :class="selectedRole === role ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20' : 'bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-2.5 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all" x-text="role"></button>
                        </template>
                    </div>
                </div>
                
                <div class="border-t border-slate-100 dark:border-slate-800/60 pt-3">
                    <p class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Filter by Status</p>
                    <div class="flex gap-1.5">
                        <button type="button" @click="selectedStatus = 'all'; filterOpen = false" :class="selectedStatus === 'all' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-500/20' : 'bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-2.5 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all">All</button>
                        <button type="button" @click="selectedStatus = 'active'; filterOpen = false" :class="selectedStatus === 'active' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-500/20' : 'bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-2.5 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all">Active</button>
                        <button type="button" @click="selectedStatus = 'locked'; filterOpen = false" :class="selectedStatus === 'locked' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-500/20' : 'bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-2.5 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all">Locked</button>
                    </div>
                </div>
                
                <div x-show="selectedRole !== 'all' || selectedStatus !== 'all'" class="border-t border-slate-100 dark:border-slate-800/60 pt-2 flex justify-end">
                    <button type="button" @click="selectedRole = 'all'; selectedStatus = 'all'; filterOpen = false" class="text-[8px] font-black text-rose-500 hover:text-rose-600 uppercase tracking-widest transition-colors">Clear Filters</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="flex-1 glass-card bg-white dark:bg-slate-900 rounded-[3rem] border border-slate-100 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col">
        <div class="overflow-x-auto flex-1 custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead class="sticky top-0 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md z-10">
                    <tr class="border-b border-slate-50 dark:border-slate-800">
                        <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">S.No</th>
                        <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">User ID</th>
                        <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Role</th>
                        <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Name</th>
                        <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Phone</th>
                        <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Address</th>
                        <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Access</th>
                        <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50/50 dark:divide-slate-800/50">
                    @foreach($users as $index => $user)
                    <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all cursor-default"
                        x-show="(search === '' || '{{ strtolower($user->name) }}'.includes(search.toLowerCase()) || '{{ strtolower($user->email) }}'.includes(search.toLowerCase()) || '{{ strtolower($user->role) }}'.includes(search.toLowerCase())) && (selectedRole === 'all' || '{{ strtolower($user->role) }}' === selectedRole) && (selectedStatus === 'all' || (selectedStatus === 'active' && {{ $user->is_active ? 'true' : 'false' }}) || (selectedStatus === 'locked' && {{ !$user->is_active ? 'true' : 'false' }}))">
                        <td class="px-6 py-3">
                            <span class="text-[10px] font-black text-slate-400">{{ $index + 1 }}</span>
                        </td>
                        <td class="px-6 py-3">
                            <span class="text-[10px] font-bold text-blue-600 uppercase tracking-tight">{{ $user->username ?? 'U-' . $user->id }}</span>
                        </td>
                        <td class="px-6 py-3">
                            <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-lg text-[9px] font-black uppercase tracking-widest border border-slate-100 dark:border-slate-700">
                                {{ $user->role }}
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            <div>
                                <p class="text-[10px] font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ $user->name }}</p>
                                <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">{{ $user->email }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-3">
                            <span class="text-[10px] font-bold text-slate-600 dark:text-slate-400 tracking-tight">{{ $user->phone ?? '---' }}</span>
                        </td>
                        <td class="px-6 py-3 max-w-[150px] truncate">
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-tight">{{ $user->address ?? '---' }}</span>
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full {{ $user->is_active ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-slate-300' }}"></div>
                                <span class="text-[9px] font-black uppercase tracking-widest {{ $user->is_active ? 'text-emerald-600' : 'text-slate-400' }}">
                                    {{ $user->is_active ? 'Active' : 'Locked' }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all translate-x-2 group-hover:translate-x-0">
                                <button @click="editUser({{ $user->toJson() }})" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-xl transition-all" title="Edit User">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                                @if ($user->isStoreAdmin())
                                    <span class="p-2 text-amber-500 bg-amber-50 dark:bg-amber-900/30 rounded-xl inline-flex items-center cursor-not-allowed" title="Store Admin (Protected from deletion)">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    </span>
                                @elseif (auth('tenant')->check() && auth('tenant')->id() === $user->id)
                                    <span class="p-2 text-slate-400 bg-slate-100 dark:bg-slate-800 rounded-xl inline-flex items-center cursor-not-allowed" title="Current Account">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </span>
                                @else
                                    <form action="{{ route('tenant.users.destroy', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete user \'{{ $user->name }}\'? This action cannot be undone.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-xl transition-all" title="Delete User">
                                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- User Modal (Add/Edit) -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-[3rem] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-white/20">
                
                <form :action="editingUser ? '/users/' + editingUser.id : '{{ route('tenant.users.store') }}'" method="POST" class="p-10 max-h-[85vh] overflow-y-auto custom-scrollbar">
                    @csrf
                    <template x-if="editingUser">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight" x-text="editingUser ? 'Edit Staff Profile' : 'Register New Staff'"></h3>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Configure system access and branch binding</p>
                        </div>
                        <button type="button" @click="showModal = false" class="text-slate-300 hover:text-slate-500 transition-colors">
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-10">
                        <!-- Left Column: Personal & Login Info -->
                        <div class="space-y-6">
                            <div class="space-y-4">
                                <p class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] border-b border-blue-50 dark:border-blue-900/30 pb-2">Login Credentials</p>
                                
                                <div class="space-y-2">
                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Access Role</label>
                                    <select name="role" x-model="newUser.role" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl px-5 py-3.5 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 uppercase tracking-widest">
                                        <option value="branch admin">Branch Admin</option>
                                        <template x-for="role in roles">
                                            <option :value="role" x-text="role"></option>
                                        </template>
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">User Name</label>
                                    <input type="text" name="username" x-model="newUser.username" placeholder="Enter User Name" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl px-5 py-3.5 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Password</label>
                                        <input type="password" name="password" placeholder="Enter Password" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl px-5 py-3.5 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Confirm Password</label>
                                        <input type="password" name="password_confirmation" placeholder="Enter Password" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl px-5 py-3.5 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4 pt-4">
                                <p class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] border-b border-blue-50 dark:border-blue-900/30 pb-2">Profile Details</p>
                                
                                <div class="space-y-2">
                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Full Name</label>
                                    <input type="text" name="name" x-model="newUser.name" placeholder="Enter Full Name" required class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl px-5 py-3.5 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Phone Number</label>
                                        <input type="text" name="phone" x-model="newUser.phone" placeholder="Enter Phone Number" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl px-5 py-3.5 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Email Address</label>
                                        <input type="email" name="email" x-model="newUser.email" placeholder="Enter Email" required class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl px-5 py-3.5 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Physical Address</label>
                                    <textarea name="address" x-model="newUser.address" rows="2" placeholder="Enter Address" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl px-5 py-3.5 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Permissions Architect -->
                        <div class="space-y-6">
                            <div class="flex items-center justify-between border-b border-blue-50 dark:border-blue-900/30 pb-2">
                                <p class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em]">Access Tab Permissions</p>
                                <button type="button" @click="toggleSelectAll()" class="text-[9px] font-black text-blue-600 uppercase tracking-widest hover:underline">Select All</button>
                            </div>

                            <div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/20 rounded-2xl">
                                <div>
                                    <p class="text-[10px] font-black text-blue-900 dark:text-blue-200 uppercase tracking-tight">Full access to all features</p>
                                    <p class="text-[8px] font-bold text-blue-400 uppercase tracking-widest">Master override for all modules</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="full_access" x-model="newUser.full_access" @change="if(newUser.full_access) newUser.permissions = [...modules]; else newUser.permissions = []" class="sr-only peer">
                                    <div class="w-10 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <div class="flex items-center justify-between p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl">
                                <div>
                                    <p class="text-[10px] font-black text-emerald-900 dark:text-emerald-200 uppercase tracking-tight">Account Status</p>
                                    <p class="text-[8px] font-bold text-emerald-400 uppercase tracking-widest">Enable or disable system access</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_active" x-model="newUser.is_active" class="sr-only peer">
                                    <div class="w-10 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>

                            <div class="grid grid-cols-2 gap-x-6 gap-y-3 p-2 h-[400px] overflow-y-auto custom-scrollbar">
                                <template x-for="module in modules" :key="module">
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative flex items-center">
                                            <input type="checkbox" name="permissions[]" :value="module" x-model="newUser.permissions"
                                                   class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 transition-all cursor-pointer">
                                        </div>
                                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-tight group-hover:text-blue-600 transition-colors" x-text="module"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="mt-12 flex items-center gap-4 pt-6 border-t border-slate-50 dark:border-slate-800">
                        <button type="button" @click="showModal = false" class="flex-1 px-8 py-4 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition-all">Cancel</button>
                        <button type="submit" class="flex-[2] px-8 py-4 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-xl shadow-blue-100 dark:shadow-none" x-text="editingUser ? 'Update Staff Permissions' : 'Deploy Staff Account'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

