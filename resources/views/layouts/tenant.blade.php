@php
    $hideSidebar = request()->routeIs('tenant.billing.index') || 
                   request()->routeIs('tenant.billing.quick') || 
                   request()->routeIs('tenant.purchase.inward') || 
                   request()->routeIs('tenant.purchase.create') || 
                   request()->routeIs('tenant.billing.outward');
    $isBusinessReport = request()->routeIs('tenant.businessreport.*');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Trex ERP')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- CSS Fallback & Alpine & Vite -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            primary: '#2563eb',
                            secondary: '#0ea5e9',
                            accent: '#8b5cf6',
                        }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Outfit', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    @stack('head')
    @stack('styles')


    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style type="text/tailwindcss">
        @layer components {
            .glass-card {
                @apply bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border border-white/20 dark:border-slate-800 shadow-[0_8px_32px_0_rgba(31,38,135,0.07)];
            }
            .nav-item {
                @apply flex items-center gap-3 px-3 py-2 rounded-xl transition-all duration-300 font-bold text-slate-600 dark:text-slate-400 hover:bg-blue-50 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400 text-[11px];
            }
            .nav-item.active {
                @apply bg-blue-600 text-white shadow-md shadow-blue-100 dark:shadow-none;
            }
            .nav-section-title {
                @apply text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mt-6 mb-2 px-3;
            }
            .custom-scrollbar::-webkit-scrollbar {
                width: 6px;
            }
            .custom-scrollbar::-webkit-scrollbar-track {
                @apply bg-transparent;
            }
            .custom-scrollbar::-webkit-scrollbar-thumb {
                @apply bg-slate-200 dark:bg-slate-700 rounded-full;
            }
            .no-scrollbar::-webkit-scrollbar,
            .scrollbar-hide::-webkit-scrollbar {
                display: none !important;
            }
            .no-scrollbar,
            .scrollbar-hide {
                -ms-overflow-style: none !important;
                scrollbar-width: none !important;
            }
            [x-cloak] { display: none !important; }

            /* Global Input Visibility Fix */
            input:not([type="checkbox"]):not([type="radio"]), 
            select, 
            textarea {
                @apply text-black font-black !important;
            }

            .dark input:not([type="checkbox"]):not([type="radio"]), 
            .dark select, 
            .dark textarea {
                @apply text-white !important;
            }
        }
    </style>

    <style>
        body {
            zoom: 0.9 !important; /* Premium high-density ERP scaling */
            height: 111.11vh !important;
            min-height: 111.11vh !important;
            max-height: 111.11vh !important;
        }
        .flex.h-screen {
            height: 111.11vh !important;
            max-height: 111.11vh !important;
        }
        @if($hideSidebar)
        body {
            zoom: 0.85 !important; /* Extra compact POS scaling */
            height: 117.647vh !important;
            min-height: 117.647vh !important;
            max-height: 117.647vh !important;
        }
        .flex.h-screen {
            height: 117.647vh !important;
            max-height: 117.647vh !important;
        }
        main {
            padding: 0 !important;
            margin: 0 !important;
            height: calc(117.647vh - 3.5rem) !important;
            max-height: calc(117.647vh - 3.5rem) !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            display: flex !important;
            flex-direction: column !important;
        }
        @endif

        .bg-pattern {
            background-color: #f8fafc;
            transition: all 0.5s ease;
        }

        .dark .bg-pattern {
            background-color: #020617;
        }

        /* Global Dark Mode Overrides (Non-Tailwind for stability) */
        .dark body {
            background-color: #0f172a !important;
            color: #f1f5f9 !important;
        }

        .dark .text-slate-900,
        .dark .text-slate-800,
        .dark .text-slate-700 {
            color: #ffffff !important;
        }

        .dark .bg-white {
            background-color: rgba(15, 23, 42, 0.8) !important;
        }

        .dark .glass-card {
            background-color: rgba(15, 23, 42, 0.8) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        .blob {
            position: fixed;
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(14, 165, 233, 0.1) 100%);
            filter: blur(80px);
            border-radius: 50%;
            z-index: -1;
            animation: move 20s infinite alternate;
        }

        @keyframes move {
            from {
                transform: translate(-10%, -10%);
            }

            to {
                transform: translate(20%, 20%);
            }
        }

        /* ── Premium Print & Export Buttons Override for all 34 reports ── */
        .page-header .btn,
        .page-header button.btn,
        .page-header a.btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            padding: 8px 14px !important;
            border-radius: 12px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            cursor: pointer !important;
            border: none !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            font-family: 'Sora', sans-serif !important;
            height: auto !important;
            line-height: normal !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        }

        /* Print Button styling (Slate theme) */
        .page-header .btn-outline,
        .page-header button.btn-outline,
        .page-header a.btn-outline {
            background: #f1f5f9 !important;
            color: #475569 !important;
            border: none !important;
        }
        .dark .page-header .btn-outline,
        .dark .page-header button.btn-outline,
        .dark .page-header a.btn-outline {
            background: #1e293b !important;
            color: #cbd5e1 !important;
        }
        .page-header .btn-outline:hover,
        .page-header button.btn-outline:hover,
        .page-header a.btn-outline:hover {
            background: #e2e8f0 !important;
        }
        .dark .page-header .btn-outline:hover,
        .dark .page-header button.btn-outline:hover,
        .dark .page-header a.btn-outline:hover {
            background: #334155 !important;
        }

        /* Export Button styling (Blue transparent theme) */
        .page-header .btn-primary,
        .page-header button.btn-primary,
        .page-header a.btn-primary,
        .page-header button.btn:not(.btn-outline),
        .page-header a.btn:not(.btn-outline) {
            background: rgba(59, 130, 246, 0.1) !important;
            color: #2563eb !important;
            border: none !important;
        }
        .dark .page-header .btn-primary,
        .dark .page-header button.btn-primary,
        .dark .page-header a.btn-primary,
        .dark .page-header button.btn:not(.btn-outline),
        .dark .page-header a.btn:not(.btn-outline) {
            background: rgba(59, 130, 246, 0.15) !important;
            color: #60a5fa !important;
        }
        .page-header .btn-primary:hover,
        .page-header button.btn-primary:hover,
        .page-header a.btn-primary:hover,
        .page-header button.btn:not(.btn-outline):hover,
        .page-header a.btn:not(.btn-outline):hover {
            background: rgba(59, 130, 246, 0.2) !important;
        }
        .dark .page-header .btn-primary:hover,
        .dark .page-header button.btn-primary:hover,
        .dark .page-header a.btn-primary:hover,
        .dark .page-header button.btn:not(.btn-outline):hover,
        .dark .page-header a.btn:not(.btn-outline):hover {
            background: rgba(59, 130, 246, 0.25) !important;
        }
    </style>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('setup', {
                activeTab: localStorage.getItem('setup_active_tab') || 'billing',
                setActiveTab(tab) {
                    this.activeTab = tab;
                    localStorage.setItem('setup_active_tab', tab);
                },
                showSizeModal: false,
                showInvModal: false,
                showA4Modal: false,
                showPOModal: false,
                showPOSModal: false,
                showBranchModal: false,
                showBarNumModal: false,
                showBarLabelModal: false,
                showInvTypeModal: false,
                showStatusModal: false,
                showInvSettingsModal: false,
                showPrinterSettingsModal: false,
                isGstinValidating: false,
                isGstinVerified: false,
                async verifyGstin() {
                    if (!this.mockData.GSTIN) {
                        this.addToast('Please enter a GSTIN first', 'error');
                        return;
                    }
                    
                    const pattern = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}[A-Z0-9]{1}[0-9A-Z]{1}$/i;
                    if (!pattern.test(this.mockData.GSTIN)) {
                        this.addToast('Invalid GSTIN format', 'error');
                        return;
                    }

                    this.isGstinValidating = true;
                    try {
                        const response = await fetch("{{ route('tenant.gst.validate') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ gstin: this.mockData.GSTIN })
                        });
                        
                        const result = await response.json();
                        if (result.success) {
                            this.isGstinVerified = true;
                            this.addToast('GSTIN Verified!', 'success');
                            
                            this.mockData.BUSINESS_NAME = result.data.legal_name;
                            this.mockData.ADDRESS = result.data.address;
                            this.mockData.CITY = result.data.city || this.mockData.CITY;
                            this.mockData.STATE = result.data.state || this.mockData.STATE;
                            this.mockData.PINCODE = result.data.pincode || this.mockData.PINCODE;
                            this.mockData.GSTIN = result.data.gstin || this.mockData.GSTIN;
                            // Also update the input fields in the Branch modal if open
                            const fieldMap = {
                                'branch_name': result.data.legal_name,
                                'branch_address': result.data.address,
                                'branch_city': result.data.city,
                                'branch_state': result.data.state,
                                'branch_pincode': result.data.pincode,
                            };

                            for (const [name, val] of Object.entries(fieldMap)) {
                                const input = document.querySelector(`[name="${name}"]`);
                                if (input && val) input.value = val;
                            }
                        } else {
                            this.isGstinVerified = false;
                            this.addToast(result.message || 'Verification failed', 'error');
                        }
                    } catch (error) {
                        console.error('GSTIN Verification error:', error);
                        this.addToast('Verification failed. Check connection.', 'error');
                    } finally {
                        this.isGstinValidating = false;
                    }
                },
                toasts: [],
                addToast(message, type = 'success') {
                    const id = Date.now();
                    this.toasts.push({ id, message, type });
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 3000);
                },
                invSettings: {
                    headerHeight: 40,
                    footerHeight: 30,
                    watermarkType: 'text',
                    watermarkPos: 'center',
                    watermarkOpacity: 10,
                    headerImg: null,
                    footerImg: null,
                    watermarkImg: null,
                    headerUrl: null,
                    footerUrl: null,
                    watermarkUrl: null,
                    watermarkText: 'TREX ERP'
                },
                updateLogo(type, file) {
                    if(!file) return;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        if(type === 'header') {
                            this.invSettings.headerUrl = e.target.result;
                        } else if(type === 'footer') {
                            this.invSettings.footerUrl = e.target.result;
                        }
                    };
                    reader.readAsDataURL(file);
                },
                labelSettings: {
                    printBarcode: true,
                    printQrCode: false,
                    qrCodeType: '',
                    barcodeType: '',
                    totalWidth: 100,
                    qrPerLine: 2,
                    qrTop: -7.2,
                    qrLeft: -3.9,
                    qrSize: 60,
                    barPerLine: 2,
                    barTop: -7.2,
                    barLeft: -3.9,
                    barHeight: 21,
                    barWidth: 1,
                    barNumberSize: 13,
                    barNumAlign: 'Center',
                    barNumPos: 'Top',
                    width: 50,
                    height: 25,
                    innerSpace: 0,
                    spaceTop: 0,
                    spaceBottom: 0,
                    spaceLeft: 0,
                    spaceRight: 0
                },
                labelDetails: [
                    { type: 'Store Name', input: 'SPOT MENS WEAR', size: 4.8, weight: 800, top: -3.9, left: 0 },
                    { type: 'MRP', input: 'MRP : ', size: 4.8, weight: 600, top: -6, left: -21.4 },
                    { type: 'Product Name', input: 'SHIRT', size: 3.7, weight: 500, top: -11.5, left: -21.4 },
                    { type: 'Barcode', input: '123456', size: 3.5, weight: 500, top: -19, left: -21.4 },
                    { type: 'Size', input: 'XL', size: 3.4, weight: 500, top: -15.2, left: -21.4 }
                ],
                sizes: [],
                invFields: [],
                prodFields: [],
                numbers: [],
                invoiceTypes: [
                    { name: 'Billing', icon: '📄', prefix: 'INV1', gstPrefix: 'INV-G1', format: '{PREFIX}.{YY}.{SEQ}', sequence: 1, reset: 'Daily', sales: true, stock: true, quick: false },
                    { name: 'Outward', icon: '↑', prefix: 'OUT', gstPrefix: 'OUT-G', format: '{PREFIX}-{YYYYMMDD}-{SEQ}', sequence: 1, reset: 'Never', sales: false, stock: false, quick: false },
                    { name: 'Quotation', icon: '💎', prefix: 'QT', gstPrefix: 'QT-G', format: '{PREFIX}-{YYYYMMDD}-{SEQ}', sequence: 1, reset: 'Never', sales: false, stock: true, quick: false },
                    { name: 'DC (No stock No Sale)', icon: '📦', prefix: 'VE-INV', gstPrefix: 'VE-INVG', format: '{PREFIX}-{YYYYMMDD}-{SEQ}', sequence: 1, reset: 'Never', sales: false, stock: false, quick: false },
                    { name: 'SALES', icon: '🛒', prefix: 'SAL', gstPrefix: 'SAL-G', format: '{PREFIX}/{FY}/{SEQ}', sequence: 2, reset: 'Daily', sales: true, stock: true, quick: false },
                ],
                newInvType: { name: '', icon: '📄', prefix: '', gstPrefix: '', format: '{PREFIX}.{YY}.{SEQ}', sequence: 1, reset: 'Daily', sales: false, stock: false, quick: false },
                editingInvIndex: null,
                addInvoiceType() {
                    if (!this.newInvType.name || !this.newInvType.prefix) return;
                    if (this.editingInvIndex !== null) {
                        this.invoiceTypes[this.editingInvIndex] = { ...this.newInvType };
                        this.editingInvIndex = null;
                    } else {
                        this.invoiceTypes.push({ ...this.newInvType });
                    }
                    this.newInvType = { name: '', icon: '📄', prefix: '', gstPrefix: '', format: '{PREFIX}.{YY}.{SEQ}', sequence: 1, reset: 'Daily', sales: false, stock: false, quick: false };
                },
                editInvoiceType(index) {
                    this.newInvType = { ...this.invoiceTypes[index] };
                    this.editingInvIndex = index;
                },
                removeInvoiceType(index) {
                    this.invoiceTypes.splice(index, 1);
                    if (this.editingInvIndex === index) this.editingInvIndex = null;
                },
                orderStatuses: [
                    { name: 'sample', icon: '📋', color: '#000000', orderCreated: false, invoiceGenerate: true, completed: true, rejected: false },
                    { name: 'orderd', icon: '', color: '#000000', orderCreated: false, invoiceGenerate: false, completed: false, rejected: false },
                    { name: 'processing', icon: '', color: '#000000', orderCreated: false, invoiceGenerate: false, completed: false, rejected: false },
                    { name: 'completed', icon: '', color: '#000000', orderCreated: false, invoiceGenerate: false, completed: false, rejected: false },
                    { name: 'Rejected', icon: '✕', color: '#000000', orderCreated: false, invoiceGenerate: false, completed: false, rejected: true },
                ],
                pendingStatuses: [
                    { name: '', icon: '', color: '#000000', orderCreated: false, invoiceGenerate: false, completed: false, rejected: false, showIconPicker: false }
                ],
                statusIcons: ['✕', '✓', '👤', '💼', '✉️', '📞', '💬', '🏷️', '💰', '⏰', '📦', '🔔', '📋', '🛒', '✅', '❌', '⭐', '🔧', '📊', '🎯', '🚀', '📌', '🔄', '⛔'],
                barcodeRows: [
                    { name: 'UNIT', numChars: 3, specialPos: '', specialChar: '' }
                ],
                addPendingStatus() {
                    this.pendingStatuses.push({ name: '', icon: '', color: '#000000', orderCreated: false, invoiceGenerate: false, completed: false, rejected: false, showIconPicker: false });
                },
                removePendingStatus(index) {
                    this.pendingStatuses.splice(index, 1);
                },
                saveStatuses() {
                    this.pendingStatuses.forEach(s => {
                        if (s.name.trim()) {
                            this.orderStatuses.push({ name: s.name, icon: s.icon, color: s.color, orderCreated: s.orderCreated, invoiceGenerate: s.invoiceGenerate, completed: s.completed, rejected: s.rejected });
                        }
                    });
                    this.pendingStatuses = [{ name: '', icon: '', color: '#000000', orderCreated: false, invoiceGenerate: false, completed: false, rejected: false, showIconPicker: false }];
                },
                removeOrderStatus(index) {
                    this.orderStatuses.splice(index, 1);
                },
                invoiceLayout: {
                    settings: {
                        totalWidth: 210, // mm
                        marginTop: 15,
                        marginBottom: 15,
                        marginLeft: 15,
                        marginRight: 15,
                        logoHeight: 25,
                        logoWidth: 44,
                        showProductTable: true,
                        showGstTable: true,
                        showSocialMedia: false,
                        primaryColor: '#2563eb',
                        fontFamily: 'Inter',
                        fontSize: 10,
                        format: 'default'
                    },
                    updateLogo(type, file) {
                        if (!file) return;
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            const dataUrl = e.target.result;
                            const store = Alpine.store('setup');
                            if (type === 'header') store.invSettings.headerUrl = dataUrl;
                            if (type === 'footer') store.invSettings.footerUrl = dataUrl;
                            if (type === 'watermark') store.invSettings.watermarkUrl = dataUrl;
                            console.log('Architect Logo Updated:', type, dataUrl.substring(0, 30));
                        };
                        reader.readAsDataURL(file);
                    },
                    sections: [
                        { 
                            id: 1, 
                            name: 'HEADER SECTION', 
                            columns: 2, 
                            rows: [
                                // Left Column
                                { source: 'LOGO', label: '', col: 1, fontSize: 12, bold: false, align: 'left' },
                                { source: 'BUSINESS_NAME', label: '', col: 1, fontSize: 16, bold: true, align: 'left' },
                                { source: 'PHONE', label: 'PHONE:', col: 1, fontSize: 10, bold: true, align: 'left' },
                                { source: 'ADDRESS', label: '', col: 1, fontSize: 10, bold: false, align: 'left' },
                                { source: 'GSTIN', label: 'GSTIN:', col: 1, fontSize: 10, bold: true, align: 'left' },
                                // Right Column
                                { source: 'DATE', label: 'DATE:', col: 2, fontSize: 10, bold: true, align: 'right' },
                                { source: 'TIME', label: 'TIME:', col: 2, fontSize: 10, bold: true, align: 'right' },
                                { source: 'COUNTER', label: 'COUNTER:', col: 2, fontSize: 10, bold: true, align: 'right' },
                                { source: 'EMPLOYEE', label: 'EMPLOYEE:', col: 2, fontSize: 10, bold: true, align: 'right' },
                                { source: 'CUSTOMER_PHONE', label: 'CUSTOMER PHONE:', col: 2, fontSize: 10, bold: true, align: 'right' },
                                { source: 'CUSTOMER_NAME', label: 'CUSTOMER NAME:', col: 2, fontSize: 10, bold: true, align: 'right' },
                                { source: 'CUSTOMER_ADDRESS', label: 'CUSTOMER ADDRESS:', col: 2, fontSize: 10, bold: true, align: 'right' }
                            ] 
                        }
                    ],
                    tableSettings: {
                        headerSize: 14,
                        contentSize: 14,
                        topSpace: 0,
                        bottomSpace: 0,
                        headerPadding: 1,
                        bodyPadding: 2,
                        headerAlign: 'left',
                        bodyAlign: 'left'
                    },
                    tableColumns: [
                        { id: 1, header: '#', source: 'SERIAL_NO', width: 5, fontSize: 10, bold: false, leftSpace: 0, topSpace: 0, align: 'left' },
                        { id: 2, header: 'ITEM DESCRIPTION', source: 'PRODUCT_NAME', width: 45, fontSize: 10, bold: false, leftSpace: 0, topSpace: 0, align: 'left' },
                        { id: 3, header: 'HSN/SAC', source: 'HSN_CODE', width: 10, fontSize: 10, bold: false, leftSpace: 0, topSpace: 0, align: 'left' },
                        { id: 4, header: 'QTY', source: 'QUANTITY', width: 10, fontSize: 10, bold: false, leftSpace: 0, topSpace: 0, align: 'center' },
                        { id: 5, header: 'RATE', source: 'UNIT_PRICE', width: 15, fontSize: 10, bold: false, leftSpace: 0, topSpace: 0, align: 'right' },
                        { id: 6, header: 'AMOUNT', source: 'LINE_TOTAL', width: 15, fontSize: 10, bold: false, leftSpace: 0, topSpace: 0, align: 'right' }
                    ],
                    watermark: {
                        text: 'TREX ERP',
                        opacity: 0.1,
                        copies: ['ORIGINAL FOR BUYER', 'DUPLICATE FOR TRANSPORTER']
                    },
                    footer: {
                        declaration: '1. Goods once sold will not be taken back.',
                        signatureLabel: 'Authorized Signatory'
                    },
                    gstTable: {
                        fontSize: 10,
                        columns: [
                            { label: 'HSN/SAC', enabled: true },
                            { label: 'TAXABLE', enabled: true },
                            { label: 'CGST', enabled: true },
                            { label: 'SGST', enabled: true },
                            { label: 'IGST', enabled: false },
                            { label: 'TOTAL', enabled: true }
                        ]
                    },
                    socialMedia: {
                        fontSize: 10,
                        platforms: [
                            { type: 'Facebook', handle: 'fb.com/brand', enabled: true },
                            { type: 'Instagram', handle: '@brand', enabled: true },
                            { type: 'WhatsApp', handle: '+91 00000 00000', enabled: false }
                        ]
                    }
                },
                addTableColumn() {
                    this.invoiceLayout.tableColumns.push({ id: Date.now(), header: 'NEW COL', source: 'PRODUCT_NAME', width: 10, fontSize: 10, bold: false, leftSpace: 0, topSpace: 0, align: 'left' });
                },
                removeTableColumn(id) {
                    this.invoiceLayout.tableColumns = this.invoiceLayout.tableColumns.filter(c => c.id !== id);
                },
                selectedField: null,
                draggedItem: null,
                reorderField(section, fromIndex, toIndex) {
                    if (fromIndex === toIndex) return;
                    let targetSection;
                    if (section === 'company') targetSection = this.a4Layout.company.fields;
                    else if (section === 'customer1') targetSection = this.a4Layout.customer1.fields;
                    else if (section === 'customer2') targetSection = this.a4Layout.customer2.fields;
                    else if (section === 'invoiceMeta') targetSection = this.a4Layout.invoiceMeta.fields;
                    else if (section === 'totals') targetSection = this.a4Layout.totals.fields;
                    
                    if (targetSection) {
                        const item = targetSection.splice(fromIndex, 1)[0];
                        targetSection.splice(toIndex, 0, item);
                    }
                },
                reorderEnterpriseFieldByIndex(sectionId, colId, fromFilteredIndex, toFilteredIndex) {
                    if (fromFilteredIndex === toFilteredIndex) return;
                    const section = this.invoiceLayout.sections.find(s => s.id === sectionId);
                    if (!section) return;
                    
                    colId = parseInt(colId);
                    let fromIndex = -1, toIndex = -1;
                    let currentCount = 0;
                    
                    // Find actual indices in the raw section.rows array
                    for(let i=0; i<section.rows.length; i++) {
                        if(parseInt(section.rows[i].col) === colId) {
                            if(currentCount === fromFilteredIndex) fromIndex = i;
                            if(currentCount === toFilteredIndex) toIndex = i;
                            currentCount++;
                        }
                    }
                    
                    // Allow dropping at the end of the list
                    if (toFilteredIndex >= currentCount) {
                        toIndex = section.rows.length; // push to end (technically might not be perfect for mixed cols, but safe)
                    }

                    if(fromIndex === -1) return;

                    const item = section.rows.splice(fromIndex, 1)[0];
                    
                    // Recalculate toIndex because splice shifted elements
                    toIndex = -1;
                    currentCount = 0;
                    for(let i=0; i<section.rows.length; i++) {
                        if(parseInt(section.rows[i].col) === colId) {
                            if(currentCount === toFilteredIndex) toIndex = i;
                            currentCount++;
                        }
                    }
                    if (toIndex === -1) toIndex = section.rows.length;

                    section.rows.splice(toIndex, 0, item);
                },
                fieldSources: [
                    { category: 'Business', fields: ['LOGO', 'BUSINESS_NAME', 'ADDRESS', 'PHONE', 'EMAIL', 'GSTIN', 'STATE', 'PAN_NO', 'BANK_NAME', 'ACC_NO', 'IFSC'] },
                    { category: 'Invoice', fields: ['INVOICE_NO', 'DATE', 'TIME', 'COUNTER', 'EMPLOYEE', 'TYPE', 'PAYMENT_MODE'] },
                    { category: 'Customer', fields: ['CUSTOMER_NAME', 'CUSTOMER_PHONE', 'CUSTOMER_ADDRESS', 'CUSTOMER_GSTIN', 'CUSTOMER_STATE'] },
                    { category: 'Totals', fields: ['SUBTOTAL', 'TAX_TOTAL', 'DISCOUNT_TOTAL', 'ROUND_OFF', 'GRAND_TOTAL', 'TOTAL_IN_WORDS', 'SAVING_TEXT'] },
                    { category: 'Misc', fields: ['FOOTER_TERMS', 'SIGNATURE_BOX', 'QR_PAYMENT', 'GREETING', 'BLANK_SPACE', 'CUSTOM_TEXT'] }
                ],
                getFieldOptionsHTML() {
                    let html = '<option value="" class="text-slate-900 bg-white dark:text-white dark:bg-slate-800">Select Field</option>';
                    this.fieldSources.forEach(cat => {
                        html += '<optgroup label="' + cat.category + '" class="font-black text-slate-400 bg-slate-100 dark:bg-slate-900 dark:text-slate-300">';
                        cat.fields.forEach(field => {
                            html += '<option value="' + field + '" class="font-bold text-slate-900 bg-white dark:text-white dark:bg-slate-800">' + field.replace(/_/g, ' ') + '</option>';
                        });
                        html += '</optgroup>';
                    });
                    return html;
                },
                tableFieldSources: ['SERIAL_NO', 'PRODUCT_NAME', 'HSN_CODE', 'QUANTITY', 'UNIT_PRICE', 'DISCOUNT', 'TAX_RATE', 'TAX_AMOUNT', 'LINE_TOTAL', 'BATCH_NO', 'EXPIRY_DATE'],
                addSection() {
                    const newId = this.invoiceLayout.sections.length ? Math.max(...this.invoiceLayout.sections.map(s => s.id)) + 1 : 1;
                    this.invoiceLayout.sections.push({ id: newId, name: 'NEW SECTION', columns: 1, rows: [] });
                },
                removeSection(id) {
                    this.invoiceLayout.sections = this.invoiceLayout.sections.filter(s => s.id !== id);
                },
                addRow(sectionId) {
                    const section = this.invoiceLayout.sections.find(s => s.id === sectionId);
                    if (section) section.rows.push({ source: 'BLANK_SPACE', label: '', col: 1, fontSize: 10, bold: false, align: 'left' });
                },
                removeRow(sectionId, rowIndex) {
                    const section = this.invoiceLayout.sections.find(s => s.id === sectionId);
                    if (section) section.rows.splice(rowIndex, 1);
                },
                moveSection(index, direction) {
                    const newIndex = index + direction;
                    if (newIndex >= 0 && newIndex < this.invoiceLayout.sections.length) {
                        const temp = this.invoiceLayout.sections[index];
                        this.invoiceLayout.sections[index] = this.invoiceLayout.sections[newIndex];
                        this.invoiceLayout.sections[newIndex] = temp;
                    }
                },
                a4Layout: {
                    format: 'modern',
                    general: {
                        title: 'TAX INVOICE',
                        subTitle: 'BILL COPY',
                        logoWidth: 50,
                        logoHeight: 50,
                        showLogo: true,
                        fontSize: 10,
                        fontFamily: 'Inter',
                        primaryColor: '#2563eb',
                        pageMarginTop: 10,
                        pageMarginBottom: 10,
                        pageMarginLeft: 10,
                        pageMarginRight: 10,
                        swapColumns: false,
                        logoOnRight: false
                    },
                    company: {
                        title: 'Company Information',
                        showTitle: true,
                        fontSize: 10,
                        bold: true,
                        align: 'left',
                        marginTop: 0,
                        marginBottom: 5,
                        marginLeft: 0,
                        marginRight: 0,
                        fields: []
                    },
                    customer1: {
                        title: 'Bill To',
                        showTitle: true,
                        fontSize: 10,
                        bold: true,
                        align: 'left',
                        marginTop: 5,
                        marginBottom: 5,
                        marginLeft: 0,
                        marginRight: 0,
                        fields: []
                    },
                    customer2: {
                        title: 'Ship To',
                        showTitle: false,
                        fontSize: 10,
                        bold: true,
                        align: 'left',
                        marginTop: 5,
                        marginBottom: 5,
                        marginLeft: 0,
                        marginRight: 0,
                        fields: []
                    },
                    invoiceMeta: {
                        title: '',
                        showTitle: false,
                        fontSize: 10,
                        bold: false,
                        align: 'right',
                        marginTop: 5,
                        marginBottom: 5,
                        marginLeft: 0,
                        marginRight: 0,
                        fields: []
                    },
                    productTable: {
                        columns: [
                            { source: 'SERIAL_NO', name: 'Sl', width: 10, align: 'center', enabled: true },
                            { source: 'PRODUCT_NAME', name: 'Product Name', width: 50, align: 'left', enabled: true },
                            { source: 'QUANTITY', name: 'Qty', width: 10, align: 'center', enabled: true },
                            { source: 'UNIT_PRICE', name: 'MRP', width: 15, align: 'right', enabled: true },
                            { source: 'LINE_TOTAL', name: 'Amount', width: 15, align: 'right', enabled: true }
                        ],
                        fontSize: 9,
                        headerBold: true,
                        rowHeight: 8,
                        showTaxSummary: true
                    },
                    footer: {
                        terms: '1. Goods once sold will not be taken back.\n2. Subject to Chennai Jurisdiction.',
                        showSignature: true,
                        showBankDetails: true,
                        fontSize: 9,
                        marginTop: 10
                    },
                    totals: {
                        fields: []
                    },
                    gstTable: {
                        fontSize: 10,
                        columns: [
                            { label: 'HSN/SAC', enabled: true },
                            { label: 'Taxable Value', enabled: true },
                            { label: 'CGST Rate', enabled: true },
                            { label: 'CGST Amount', enabled: true },
                            { label: 'SGST Rate', enabled: true },
                            { label: 'SGST Amount', enabled: true },
                            { label: 'IGST Rate', enabled: false },
                            { label: 'IGST Amount', enabled: false },
                            { label: 'Total Tax Amount', enabled: true }
                        ]
                    }
                },
                poLayout: {
                    format: 'modern',
                    general: {
                        title: 'PURCHASE ORDER',
                        subTitle: 'OFFICIAL COPY',
                        logoWidth: 50,
                        logoHeight: 50,
                        showLogo: true,
                        fontSize: 10,
                        fontFamily: 'Inter',
                        primaryColor: '#0ea5e9',
                        pageMarginTop: 10,
                        pageMarginBottom: 10,
                        pageMarginLeft: 10,
                        pageMarginRight: 10
                    },
                    company: {
                        title: 'Ship To',
                        showTitle: true,
                        fontSize: 10,
                        bold: true,
                        align: 'left',
                        marginTop: 0,
                        marginBottom: 5,
                        marginLeft: 0,
                        marginRight: 0,
                        fields: []
                    },
                    vendor: {
                        title: 'Vendor Details',
                        showTitle: true,
                        fontSize: 10,
                        bold: true,
                        align: 'left',
                        marginTop: 5,
                        marginBottom: 5,
                        marginLeft: 0,
                        marginRight: 0,
                        fields: []
                    },
                    invoiceMeta: {
                        title: '',
                        showTitle: false,
                        fontSize: 10,
                        bold: false,
                        align: 'right',
                        marginTop: 5,
                        marginBottom: 5,
                        marginLeft: 0,
                        marginRight: 0,
                        fields: []
                    },
                    productTable: {
                        columns: [
                            { source: 'SERIAL_NO', name: 'Sl', width: 10, align: 'center', enabled: true },
                            { source: 'PRODUCT_NAME', name: 'Item Description', width: 50, align: 'left', enabled: true },
                            { source: 'QUANTITY', name: 'Qty', width: 10, align: 'center', enabled: true },
                            { source: 'UNIT_PRICE', name: 'Price', width: 15, align: 'right', enabled: true },
                            { source: 'LINE_TOTAL', name: 'Total', width: 15, align: 'right', enabled: true }
                        ],
                        fontSize: 9,
                        headerBold: true,
                        rowHeight: 8
                    },
                    footer: {
                        terms: '1. Please acknowledge receipt of this PO.\n2. Payment terms: Net 30 days.',
                        showSignature: true,
                        fontSize: 9,
                        marginTop: 10
                    },
                    totals: {
                        fields: []
                    }
                },
                posLayout: {
                    general: {
                        title: 'TAX INVOICE',
                        showLogo: true,
                        logoWidth: 40,
                        logoHeight: 40,
                        fontSize: 10,
                        fontFamily: 'monospace',
                        printWidth: 80, // mm
                    },
                    company: {
                        showPhone: true,
                        showEmail: false,
                        showAddress: false,
                        showGstin: false,
                        fontSize: 10,
                        bold: true,
                        customRows: []
                    },
                    productTable: {
                        columns: [
                            { source: 'PRODUCT_NAME', name: 'ITEM', width: 50, align: 'left', enabled: true },
                            { source: 'QUANTITY', name: 'QTY', width: 20, align: 'center', enabled: true },
                            { source: 'LINE_TOTAL', name: 'AMT', width: 30, align: 'right', enabled: true }
                        ],
                        fontSize: 9,
                        headerBold: true,
                    },
                    totals: {
                        showSubtotal: true,
                        showTax: true,
                        showDiscount: true,
                        showGrandTotal: true,
                        fontSize: 10,
                        bold: true,
                    },
                    footer: {
                        message: 'Thank You For Shopping!',
                        showPoweredBy: true,
                        fontSize: 9,
                        customRows: []
                    }
                },
                addPOSColumn() {
                    this.posLayout.productTable.columns.push({ source: 'BLANK_SPACE', name: 'NEW COL', width: 10, align: 'left', enabled: true });
                },
                removePOSColumn(index) {
                    this.posLayout.productTable.columns.splice(index, 1);
                },
                addPOSRow(section) {
                    if (section === 'company') {
                        this.posLayout.company.customRows.push({ source: 'CUSTOM_TEXT', label: 'New Field', value: '', align: 'center', fontSize: 10, bold: false });
                    } else if (section === 'footer') {
                        this.posLayout.footer.customRows.push({ source: 'CUSTOM_TEXT', label: 'New Field', value: '', align: 'center', fontSize: 10, bold: false });
                    }
                },
                removePOSRow(section, index) {
                    if (section === 'company') {
                        this.posLayout.company.customRows.splice(index, 1);
                    } else if (section === 'footer') {
                        this.posLayout.footer.customRows.splice(index, 1);
                    }
                },
                getMockValue(source, fallback = '') {
                    if (source === 'CUSTOM_TEXT') return fallback;
                    return this.mockData[source] || fallback;
                },
                mockData: {
                    BUSINESS_NAME: '',
                    ADDRESS: '',
                    PHONE: '',
                    EMAIL: '',
                    GSTIN: '',
                    STATE: '',
                    PAN_NO: '',
                    BANK_NAME: '',
                    ACC_NO: '',
                    IFSC: '',
                    INVOICE_NO: '',
                    DATE: '',
                    TIME: '',
                    COUNTER: '',
                    EMPLOYEE: '',
                    TYPE: '',
                    PAYMENT_MODE: '',
                    CUSTOMER_NAME: '',
                    CUSTOMER_PHONE: '',
                    CUSTOMER_ADDRESS: '',
                    CUSTOMER_GSTIN: '',
                    CUSTOMER_STATE: '',
                    SUBTOTAL: '',
                    TAX_TOTAL: '',
                    DISCOUNT_TOTAL: '',
                    ROUND_OFF: '',
                    GRAND_TOTAL: '',
                    TOTAL_IN_WORDS: '',
                    SAVING_TEXT: '',
                    FOOTER_TERMS: '',
                    SIGNATURE_BOX: '',
                    GREETING: '',
                    QR_PAYMENT: '',
                    VENDOR_NAME: '',
                    VENDOR_PHONE: '',
                    VENDOR_ADDRESS: '',
                    VENDOR_GSTIN: '',
                    PO_NO: '',
                    SERIAL_NO: '',
                    PRODUCT_NAME: '',
                    HSN_CODE: '',
                    QUANTITY: '',
                    UNIT_PRICE: '',
                    DISCOUNT: '',
                    TAX_RATE: '',
                    TAX_AMOUNT: '',
                    LINE_TOTAL: '',
                    BATCH_NO: '',
                    EXPIRY_DATE: ''
                },
                getMockValue(source, fieldContent = '') {
                    if (source === 'CUSTOM_TEXT') return fieldContent;
                    if (source === 'BLANK_SPACE') return '';
                    return source ? '[' + source.replace(/_/g, ' ') + ']' : '';
                },
                get barcodePreview() {
                    let preview = '';
                    this.barcodeRows.forEach(row => {
                        let segment = '';
                        const chars = parseInt(row.numChars) || 0;
                        if (row.name === 'UNIT') segment = '1'.repeat(chars);
                        else if (row.name === 'BUYING VALUE') segment = '5'.repeat(chars);
                        else if (row.name === 'QUANTITY') segment = '3'.repeat(chars);
                        else if (row.name === 'PROFIT PERCENTAGE') segment = '2'.repeat(chars);
                        else if (row.name === 'PROFIT AMOUNT') segment = '7'.repeat(chars);
                        else if (row.name === 'MRP') segment = '9'.repeat(chars);
                        else if (row.name === 'SELLING PRICE') segment = '4'.repeat(chars);
                        else segment = '0'.repeat(chars);

                        if (row.specialChar) {
                            if (row.specialPos === 'FRONT') segment = row.specialChar + segment;
                            else if (row.specialPos === 'BACK') segment = segment + row.specialChar;
                        }
                        preview += segment;
                    });
                    return preview || '543';
                },
                addBarcodeRow() {
                    this.barcodeRows.push({ name: '', numChars: 1, specialPos: '', specialChar: '' });
                },
                removeBarcodeRow(index) {
                    this.barcodeRows.splice(index, 1);
                },
                dragging: null,
                startX: 0,
                startY: 0,
                initialLeft: 0,
                initialTop: 0,
                startDrag(event, type, index = null) {
                    this.dragging = { type, index };
                    this.startX = event.clientX;
                    this.startY = event.clientY;

                    if (type === 'qr') {
                        this.initialLeft = parseFloat(this.labelSettings.qrLeft) || 0;
                        this.initialTop = parseFloat(this.labelSettings.qrTop) || 0;
                    } else if (type === 'barcode') {
                        this.initialLeft = parseFloat(this.labelSettings.barLeft) || 0;
                        this.initialTop = parseFloat(this.labelSettings.barTop) || 0;
                    } else if (type === 'text') {
                        this.initialLeft = parseFloat(this.labelDetails[index].left) || 0;
                        this.initialTop = parseFloat(this.labelDetails[index].top) || 0;
                    }

                    const moveHandler = (e) => this.onDrag(e);
                    const upHandler = () => {
                        this.dragging = null;
                        window.removeEventListener('mousemove', moveHandler);
                        window.removeEventListener('mouseup', upHandler);
                    };

                    window.addEventListener('mousemove', moveHandler);
                    window.addEventListener('mouseup', upHandler);
                },
                onDrag(event) {
                    if (!this.dragging) return;

                    // Canvas is scaled 5x in preview, so divide delta by 5 to get mm movement
                    const dx = (event.clientX - this.startX) / 5;
                    const dy = (event.clientY - this.startY) / 5;

                    if (this.dragging.type === 'qr') {
                        this.labelSettings.qrLeft = Number((this.initialLeft + dx).toFixed(1));
                        this.labelSettings.qrTop = Number((this.initialTop + dy).toFixed(1));
                    } else if (this.dragging.type === 'barcode') {
                        this.labelSettings.barLeft = Number((this.initialLeft + dx).toFixed(1));
                        this.labelSettings.barTop = Number((this.initialTop + dy).toFixed(1));
                    } else if (this.dragging.type === 'text') {
                        this.labelDetails[this.dragging.index].left = Number((this.initialLeft + dx).toFixed(1));
                        this.labelDetails[this.dragging.index].top = Number((this.initialTop + dy).toFixed(1));
                    }
                },
                onWheel(event, type, index = null) {
                    event.preventDefault();
                    // Determine scroll direction: up = larger, down = smaller
                    const delta = event.deltaY < 0 ? 1 : -1;

                    if (type === 'qr') {
                        let newSize = Number(this.labelSettings.qrSize) + delta * 2;
                        this.labelSettings.qrSize = Math.max(10, newSize);
                    } else if (type === 'barcode') {
                        if (event.shiftKey) {
                            let newWidth = Number(this.labelSettings.barWidth) + delta * 0.1;
                            this.labelSettings.barWidth = Math.max(0.1, Number(newWidth.toFixed(1)));
                        } else {
                            let newHeight = Number(this.labelSettings.barHeight) + delta;
                            this.labelSettings.barHeight = Math.max(5, newHeight);
                        }
                    } else if (type === 'text') {
                        let newSize = Number(this.labelDetails[index].size) + delta * 0.2;
                        this.labelDetails[index].size = Math.max(1, Number(newSize.toFixed(1)));
                    }
                }
            });
        });
    </script>
</head>

<body class="bg-pattern min-h-screen h-screen overflow-hidden text-slate-900 transition-colors duration-500" x-data="{ 
        sidebarOpen: false, 
        darkMode: localStorage.getItem('darkMode') === 'true',
        notificationsOpen: false,
        hotkeysOpen: false,
        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('darkMode', this.darkMode);
        }
      }" :class="{ 'dark': darkMode }" @keydown.window.f6.prevent="sidebarOpen = true; hotkeysOpen = !hotkeysOpen">

    <div class="fixed inset-0 z-[-1] transition-opacity duration-1000 bg-slate-900 opacity-0 pointer-events-none"
        :class="{ 'opacity-100': darkMode }"></div>

    <!-- Decorative Blobs -->
    <div class="blob" style="top: -100px; left: -100px;"></div>
    <div class="blob"
        style="bottom: -100px; right: -100px; animation-delay: -5s; background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(236, 72, 153, 0.1) 100%);">
    </div>


    <div class="flex h-screen relative overflow-hidden">
        <!-- Sidebar Overlay (Mobile Only / Full on Business Reports) -->
        <div x-show="sidebarOpen" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" @click="sidebarOpen = false"
            class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 {{ ($isBusinessReport || $hideSidebar) ? '' : 'lg:hidden' }}"></div>

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 w-64 -translate-x-full border-r border-slate-200/60 bg-white/95 lg:bg-white/50 dark:bg-slate-900/90 dark:border-slate-800/60 backdrop-blur-md transition-all duration-300 {{ ($isBusinessReport || $hideSidebar) ? 'lg:fixed lg:inset-y-0 lg:z-50' : 'lg:translate-x-0 lg:static' }} lg:inset-0 h-screen flex flex-col overflow-hidden">

            <div class="p-4 flex items-center justify-between lg:justify-start gap-2 mb-2">
                <div class="flex items-center gap-2">
                    <div class="flex items-center justify-center w-full">
                        <img src="/assets/images/trex-logo-light.png" alt="Trex ERP Logo" class="w-48 h-auto object-contain -ml-1 dark:hidden">
                        <img src="/assets/images/trex-logo.png" alt="Trex ERP Logo" class="w-48 h-auto object-contain drop-shadow-[0_0_8px_rgba(255,255,255,0.2)] -ml-1 hidden dark:block">
                    </div>
                </div>
                <!-- Close Button (Mobile Only) -->
                <button @click="sidebarOpen = false" class="lg:hidden p-2 text-slate-400 hover:text-slate-600">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 pb-4 custom-scrollbar space-y-1">
                @php 
                    $user = Auth::guard('tenant')->user(); 
                @endphp
                <!-- MAIN -->
                <p class="nav-section-title !mt-2">Main</p>
                <a href="{{ route('tenant.dashboard') }}" @click="if(window.innerWidth < 1024) sidebarOpen = false"
                    class="nav-item {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </a>

                @if($user->hasPermission('Quick Bill'))
                <a href="{{ route('tenant.billing.quick') }}"
                    class="nav-item {{ request()->routeIs('tenant.billing.quick') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Quick Bill
                </a>
                @endif

                @if($user->hasPermission('CRM'))
                <a href="{{ url('/crm') }}" @click="if(window.innerWidth < 1024) sidebarOpen = false"
                    class="nav-item {{ request()->routeIs('tenant.crm.*') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    CRM Workflow
                </a>
                @endif

                <!-- OUTWARD (SALES) -->
                @if($user->hasPermission('Billing') || $user->hasPermission('Customers') || $user->hasPermission('Pre Orders'))
                <p class="nav-section-title">Outward (Sales)</p>
                @if($user->hasPermission('Billing'))
                <a href="{{ route('tenant.billing.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.billing.index') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Billing
                </a>
                @endif
                @if($user->hasPermission('Pre Orders'))
                <a href="{{ route('tenant.billing.pre-orders') }}"
                    class="nav-item {{ request()->routeIs('tenant.billing.pre-orders') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Quotations
                </a>
                @endif
                @if($user->hasPermission('Billing'))
                <a href="{{ route('tenant.returns.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.returns.*') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    Returns & Exchange
                </a>
                @endif
                @if($user->hasPermission('Billing'))
                <a href="{{ route('tenant.delivery.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.delivery.index') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Deliveries
                </a>
                @endif
                @if($user->hasPermission('Billing'))
                <a href="{{ route('tenant.service.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.service.*') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Service Claims
                </a>
                @endif
                @if($user->hasPermission('Billing'))
                <a href="{{ route('tenant.billing.gst.gstr1') }}"
                    class="nav-item {{ request()->routeIs('tenant.billing.gst.*') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    GST Reports
                </a>
                @endif

                @if($user->hasPermission('Customers'))
                <a href="{{ route('tenant.customers.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.customers.index') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Customers
                </a>
                @endif

                @if($user->hasPermission('Daily Expense') || $user->hasPermission('CRM_DB:Daily Expense'))
                <a href="{{ route('tenant.crm.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.crm.index') && !request()->has('workflow') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Daily Expense
                </a>
                @endif
                @endif

                <!-- WEBSITE -->
                <p class="nav-section-title">Website</p>
                <a href="{{ route('tenant.website-orders.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.website-orders.*') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                    Website Orders
                </a>
                <a href="{{ route('tenant.website-products.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.website-products.*') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    Website Products
                </a>
                <a href="{{ route('tenant.coupons.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.coupons.*') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    Discount Coupons
                </a>
                <a href="{{ route('tenant.gift-cards.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.gift-cards.*') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v13m0-13V4.5a2.5 2.5 0 115 0v3.5h-5zm0 0H7.5a2.5 2.5 0 110-5H12v5zm-7 4h14a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2z" />
                    </svg>
                    Gift Cards
                </a>
                <a href="{{ route('tenant.website-settings.index', ['section' => 'store']) }}"
                    class="nav-item {{ request()->routeIs('tenant.website-settings.*') && request('section') === 'store' ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Store & Shipping
                </a>
                <a href="{{ route('tenant.website-settings.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.website-settings.*') && request('section') !== 'store' ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Website Settings
                </a>

                <!-- INVENTORY -->
                @if($user->hasPermission('Inventory') || $user->hasPermission('Stock Transfer') || $user->hasPermission('Category'))
                <p class="nav-section-title">Inventory</p>
                @if($user->hasPermission('Inventory'))
                <a href="{{ route('tenant.products.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.products.index') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    Product List
                </a>
                <a href="{{ route('tenant.inventory.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.inventory.index') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    Stock Manager
                </a>
                @endif
                @if($user->hasPermission('Stock Transfer'))
                <a href="{{ route('tenant.stock-transfer.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.stock-transfer.index') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    Stock Transfer
                </a>
                @if($user->hasPermission('Purchase Settlement') || $user->hasPermission('CRM_DB:Purchase Settlement'))
                <a href="{{ route('tenant.purchase.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.purchase.index') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Purchase Orders
                </a>
                @endif
                @endif
                @endif

                <!-- MANUFACTURING -->
                @if($user->hasPermission('Production'))
                <p class="nav-section-title">Manufacturing</p>
                <a href="{{ route('tenant.production.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.production.*') && !request()->has('stage') && !request()->routeIs('tenant.production.mis-costs') && !request()->routeIs('tenant.production.mis-costs.*') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Production Manager
                </a>
                <a href="{{ route('tenant.production.index', ['stage' => 'Analysis']) }}"
                    class="nav-item {{ request()->routeIs('tenant.production.index') && request()->get('stage') === 'Analysis' ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Production Analysis
                </a>
                <a href="{{ route('tenant.production.mis-costs') }}"
                    class="nav-item {{ request()->routeIs('tenant.production.mis-costs') || request()->routeIs('tenant.production.mis-costs.*') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    MIS Manufacturing Cost
                </a>
                @endif

                <!-- INWARD (PROCUREMENT) -->
                @if($user->hasPermission('Purchase') || $user->hasPermission('Vendors'))
                <p class="nav-section-title">Inward (Procurement)</p>
                @if($user->hasPermission('Purchase'))
                <a href="{{ route('tenant.purchase.create') }}"
                    class="nav-item {{ request()->routeIs('tenant.purchase.create') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Purchase Order
                </a>
                @endif
                @if($user->hasPermission('Vendors'))
                <a href="{{ route('tenant.suppliers.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.suppliers.index') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Suppliers
                </a>
                @endif
                @endif

                <!-- ACCOUNTING -->
                @if($user->hasPermission('Accounting'))
                <p class="nav-section-title">Accounting</p>
                <a href="{{ route('tenant.accounting.accounts') }}"
                    class="nav-item {{ request()->routeIs('tenant.accounting.accounts') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    Chart of Accounts
                </a>
                <a href="{{ route('tenant.accounting.journal-entries') }}"
                    class="nav-item {{ request()->routeIs('tenant.accounting.journal-entries*') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Journal Entries
                </a>
                <a href="{{ route('tenant.accounting.general-ledger') }}"
                    class="nav-item {{ request()->routeIs('tenant.accounting.general-ledger*') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                    General Ledger
                </a>
                @endif

                <!-- FINANCE -->
                @if($user->hasPermission('Instalments') || $user->hasPermission('Reports'))
                <p class="nav-section-title">Finance</p>
                @if($user->hasPermission('Instalments'))
                <a href="{{ route('tenant.instalments.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.instalments.index') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Instalments
                </a>
                @endif
                @if($user->hasPermission('Reports'))
                <a href="{{ route('tenant.due-dashboard.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.due-dashboard.index') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Due Date Dashboard
                </a>
                <a href="{{ route('tenant.businessreport.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.businessreport.index') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 8v8m-4-5v5m-4-2v2M2 5a2 2 0 012-2h16a2 2 0 012 2v14a2 2 0 01-2 2H4a2 2 0 01-2-2V5z" />
                    </svg>
                    Business Reports Hub
                </a>
                <a href="{{ route('tenant.reports.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.reports.index') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Profit & Loss
                </a>
                <a href="{{ route('tenant.reports.tax') }}"
                    class="nav-item {{ request()->routeIs('tenant.reports.tax') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Tax / GST Reports
                </a>
                @if($user->hasPermission('Summary') || $user->hasPermission('CRM_DB:Summary'))
                <a href="{{ route('tenant.reports.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.reports.index') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Daily Summary
                </a>
                @endif
                @endif
                @endif

                <!-- PEOPLE -->
                @if($user->hasPermission('User') || $user->hasPermission('Employee List') || $user->hasPermission('CRM_DB:Employee List') || $user->hasPermission('Employee Attendance') || $user->hasPermission('CRM_DB:Employee Attendance'))
                <p class="nav-section-title">People</p>
                @if($user->hasPermission('User'))
                <a href="{{ route('tenant.users.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.users.index') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Staff Manager
                </a>
                @endif
                @if($user->hasPermission('Employee List') || $user->hasPermission('CRM_DB:Employee List'))
                <a href="{{ route('tenant.employees.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.employees.*') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Employee List
                </a>
                @endif
                @if($user->hasPermission('Employee Attendance') || $user->hasPermission('CRM_DB:Employee Attendance'))
                <a href="{{ route('tenant.attendance.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.attendance.*') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Attendance
                </a>
                @endif
                @endif

                <!-- MARKETING -->
                @if($user->hasPermission('Mail') || $user->hasPermission('WhatsApp'))
                <p class="nav-section-title">Marketing</p>
                @if($user->hasPermission('Mail'))
                <a href="{{ route('tenant.mail.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.mail.index') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Mail Marketing
                </a>
                @endif
                @if($user->hasPermission('WhatsApp'))
                <a href="{{ route('tenant.whatsapp.index') }}"
                    class="nav-item {{ request()->routeIs('tenant.whatsapp.index') ? 'active' : '' }} text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    WhatsApp Marketing
                </a>
                @endif
                @endif

                <!-- SETTINGS -->
                @if($user->hasPermission('SetUp'))
                <p class="nav-section-title">System</p>
                <a href="/setup" @click="if(window.innerWidth < 1024) sidebarOpen = false" class="nav-item {{ request()->is('setup') ? 'active' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    System Setup
                </a>
                @endif
            </nav>


            <!-- Shortcuts Guide -->
            <div class="mt-6 px-4 py-4 bg-slate-50 dark:bg-slate-800/30 rounded-2xl border border-slate-100 dark:border-slate-800/50">
                <button @click="hotkeysOpen = !hotkeysOpen" class="w-full flex items-center justify-between text-left focus:outline-none">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Hotkeys</span>
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': hotkeysOpen }">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="hotkeysOpen" x-collapse class="mt-4 grid grid-cols-2 gap-y-2 gap-x-4" x-cloak>
                    <div class="flex items-center justify-between">
                        <span class="text-[8px] font-bold text-slate-500 uppercase">Dashboard</span>
                        <kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded text-[8px] font-black text-slate-600 dark:text-slate-300 shadow-sm">F1</kbd>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[8px] font-bold text-slate-500 uppercase">Billing</span>
                        <kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded text-[8px] font-black text-slate-600 dark:text-slate-300 shadow-sm">F2</kbd>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[8px] font-bold text-slate-500 uppercase">Products</span>
                        <kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded text-[8px] font-black text-slate-600 dark:text-slate-300 shadow-sm">F3</kbd>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[8px] font-bold text-slate-500 uppercase">CRM</span>
                        <kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded text-[8px] font-black text-slate-600 dark:text-slate-300 shadow-sm">F4</kbd>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[8px] font-bold text-slate-500 uppercase">Tally</span>
                        <kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded text-[8px] font-black text-slate-600 dark:text-slate-300 shadow-sm">F7</kbd>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[8px] font-bold text-slate-500 uppercase">Setup</span>
                        <kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded text-[8px] font-black text-slate-600 dark:text-slate-300 shadow-sm">F10</kbd>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[8px] font-bold text-slate-500 uppercase">Toggle Hotkeys</span>
                        <kbd class="px-1.5 py-0.5 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800/50 rounded text-[8px] font-black text-blue-600 dark:text-blue-400 shadow-sm">F6</kbd>
                    </div>
                </div>
            </div>

            <div class="mt-auto border-t border-slate-100 dark:border-slate-800/60 pt-6">
                <div
                    class="bg-blue-50 dark:bg-slate-800/50 rounded-2xl p-4 flex items-center gap-3 border border-transparent dark:border-slate-700/50">
                    <div
                        class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold text-xs shadow-lg shadow-blue-200 dark:shadow-none">
                        {{ substr(Auth::user()->name, 0, 2) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ Auth::user()->name }}
                        </p>
                        <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-tighter">
                            Admin Account</p>
                    </div>
                    <form method="POST" action="{{ route('tenant.logout') }}">
                        @csrf
                        <button type="submit"
                            class="p-2 text-slate-400 dark:text-slate-500 hover:text-rose-500 dark:hover:text-rose-400 transition">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>


        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 transition-colors duration-500">
            <header
                class="h-14 flex items-center justify-between px-4 lg:px-6 bg-white/30 dark:bg-slate-900/40 backdrop-blur-sm sticky top-0 z-30 border-b border-slate-200/40 dark:border-slate-800/40">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen"
                        class="{{ ($isBusinessReport || $hideSidebar) ? '' : 'lg:hidden' }} p-2 -ml-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    @if(!request()->routeIs('tenant.dashboard'))
                    <button onclick="window.history.back()"
                        class="p-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors" title="Go Back">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </button>
                    @endif
                    <div>
                        <h2 class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Current Page</h2>
                        <p class="text-sm lg:text-base font-black text-slate-900 dark:text-white tracking-tight">
                            @yield('page-title', 'Dashboard')</p>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div class="relative hidden sm:flex items-center group">
                        <svg width="14" height="14"
                            class="absolute left-4 text-slate-400 dark:text-slate-500 group-focus-within:text-blue-600 dark:group-focus-within:text-blue-400 transition-colors"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" id="global-search" placeholder="Search anything..."
                            class="pl-10 pr-20 py-2.5 bg-slate-50/50 dark:bg-slate-800/50 hover:bg-white dark:hover:bg-slate-800 focus:bg-white dark:focus:bg-slate-800 border border-slate-100 dark:border-slate-700 focus:border-blue-200 dark:focus:border-blue-500/50 rounded-2xl text-[10px] font-bold outline-none transition-all w-48 lg:w-80 shadow-sm dark:shadow-none focus:ring-4 focus:ring-blue-100 dark:focus:ring-blue-500/10 dark:text-white">
                        <div
                            class="absolute right-3 flex items-center gap-1 px-2 py-1 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-lg shadow-sm">
                            <span
                                class="text-[8px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-tighter">Ctrl
                                + K</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Theme Toggle -->
                        <button @click="toggleDarkMode()"
                            class="p-2.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all">
                            <svg x-show="!darkMode" width="20" height="20" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            <svg x-show="darkMode" width="20" height="20" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 3v1m0 16v1m9-9h-1M4 9h-1m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </button>

                        <!-- Open Website Storefront -->
                        <a href="http://localhost:5173" target="_blank"
                            class="p-2.5 text-indigo-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-800 rounded-xl transition-all relative group" title="Open Storefront Website">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                        </a>

                        <!-- WhatsApp Link -->
                        @if(Auth::guard('tenant')->user()->hasPermission('WhatsApp'))
                        <a href="{{ route('tenant.whatsapp.index') }}"
                            class="p-2.5 text-emerald-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-xl transition-all relative" title="WhatsApp Marketing">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </a>
                        @endif

                        <!-- Notifications -->
                        <div class="relative" @click.away="notificationsOpen = false">
                            <button @click="notificationsOpen = !notificationsOpen"
                                class="p-2.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all relative">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span
                                    class="absolute top-2.5 right-2.5 w-2 h-2 bg-rose-500 border-2 border-white rounded-full"></span>
                            </button>

                            <!-- Notification Dropdown -->
                            <div x-show="notificationsOpen" x-cloak
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                class="absolute right-0 mt-3 w-80 glass-card rounded-3xl overflow-hidden z-50"
                                style="display: none;">
                                <div class="p-4 border-b border-slate-100 flex justify-between items-center">
                                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest">
                                        Notifications</h3>
                                    <span
                                        class="px-2 py-0.5 bg-blue-50 text-blue-600 text-[8px] font-black rounded-lg">2
                                        NEW</span>
                                </div>
                                <div class="max-h-96 overflow-y-auto custom-scrollbar">
                                    <div
                                        class="p-4 hover:bg-slate-50 transition-colors cursor-pointer border-b border-slate-50">
                                        <div class="flex gap-3">
                                            <div
                                                class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                                <svg width="14" height="14" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-[10px] font-black text-slate-900 uppercase">System Sync
                                                    Successful</p>
                                                <p class="text-[8px] font-bold text-slate-400 mt-0.5 uppercase">2
                                                    Minutes Ago</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-4 hover:bg-slate-50 transition-colors cursor-pointer">
                                        <div class="flex gap-3">
                                            <div
                                                class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                                                <svg width="14" height="14" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-[10px] font-black text-slate-900 uppercase">Low Stock
                                                    Alert: Item #022</p>
                                                <p class="text-[8px] font-bold text-slate-400 mt-0.5 uppercase">1 Hour
                                                    Ago</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <a href="#"
                                    class="block p-3 text-center bg-slate-50 text-[9px] font-black text-slate-400 uppercase tracking-widest hover:text-blue-600 transition-colors">View
                                    All Notifications</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="{{ $hideSidebar ? '' : 'p-4 md:p-6' }} flex-1 h-full {{ $hideSidebar ? 'overflow-y-auto custom-scrollbar' : 'overflow-y-auto custom-scrollbar' }}">
                <!-- Session Alerts -->
                @if (session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                        class="mb-6 p-4 bg-emerald-50 border border-emerald-100 rounded-2xl flex items-center gap-3 text-emerald-700 shadow-sm transition-all">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-xs font-black uppercase tracking-tight">{{ session('success') }}</p>
                    </div>
                @endif

                @if (session('error'))
                    <div
                        class="mb-6 p-4 bg-rose-50 border border-rose-100 rounded-2xl flex items-center gap-3 text-rose-700 shadow-sm">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-xs font-black uppercase tracking-tight">{{ session('error') }}</p>
                    </div>
                @endif

                @include('tenant.partials.stock_health_modal')
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Global Toast Notifications -->
    <div class="fixed bottom-8 right-8 z-[200] space-y-4 pointer-events-none">
        <template x-for="toast in $store.setup.toasts" :key="toast.id">
            <div x-show="true" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-8 scale-90"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-90"
                 class="pointer-events-auto px-8 py-5 rounded-[2.5rem] shadow-2xl backdrop-blur-2xl border border-white/20 flex items-center gap-5 min-w-[320px] bg-blue-600 text-white">
                <div class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center shadow-inner">
                    <svg x-show="toast.type === 'success'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    <svg x-show="toast.type === 'error'" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] opacity-60 mb-0.5" x-text="toast.type === 'success' ? 'System Sync' : 'Attention'"></p>
                    <p class="text-[12px] font-black tracking-tight" x-text="toast.message"></p>
                </div>
            </div>
        </template>
    </div>

    @if(!$hideSidebar)
        @include('tenant.partials.ai_chatbot')
    @endif
    @stack('scripts')
    <script>
        document.addEventListener('keydown', function (e) {
            // 🚀 Global Navigation Shortcuts
            const shortcuts = {
                'F1': "{{ route('tenant.dashboard') }}",
                'F2': "{{ route('tenant.billing.index') }}",
                'F3': "{{ route('tenant.products.index') }}",
                'F4': "{{ route('tenant.crm.index') }}",
                'F7': "{{ route('tenant.tally.index') }}",
                'F10': "/setup"
            };

            if (shortcuts[e.key]) {
                e.preventDefault();
                window.location.href = shortcuts[e.key];
            }

            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('global-search')?.focus();
            }

            // ⚡ Stock Transfer Smart Shortcuts
            if (window.location.pathname.includes('stock-transfer')) {
                if (e.key === 'F8') {
                    e.preventDefault();
                    window.location.href = "{{ route('tenant.stock-transfer.report') }}";
                }
                if (e.key === 'F9') {
                    e.preventDefault();
                    // If on list or report, go to create. If on create, go to list.
                    if (window.location.pathname.endsWith('/list') || window.location.pathname.endsWith('/report')) {
                        window.location.href = "{{ route('tenant.stock-transfer.index') }}";
                    } else {
                        window.location.href = "{{ route('tenant.stock-transfer.list') }}";
                    }
                }
            }
        });
    </script>
</body>

</html>
