<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agreement {{ $agreement->agreement_number }} - {{ $agreement->title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Cinzel:wght@700&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #1e293b;
            background-color: #f1f5f9;
            font-size: 13px;
            line-height: 1.6;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Top Action Toolbar */
        .no-print {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 24px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            border: 1px solid transparent;
        }
        .btn-primary {
            background-color: #4f46e5;
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: #4338ca;
        }
        .btn-secondary {
            background-color: #f8fafc;
            color: #475569;
            border-color: #cbd5e1;
        }
        .btn-secondary:hover {
            background-color: #f1f5f9;
            color: #1e293b;
        }
        .btn-action {
            background-color: #eef2ff;
            color: #4338ca;
            border-color: #c7d2fe;
        }
        .btn-action:hover {
            background-color: #e0e7ff;
        }
        .btn-danger {
            background-color: #fff1f2;
            color: #e11d48;
            border-color: #fecdd3;
        }
        .btn-danger:hover {
            background-color: #ffe4e6;
        }

        .edit-hint {
            font-size: 11px;
            font-weight: 600;
            color: #6366f1;
            background: #eef2ff;
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px dashed #818cf8;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .page-container {
            max-width: 820px;
            margin: 28px auto 40px auto;
            background: #ffffff;
            padding: 48px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            position: relative;
        }

        /* Editable Areas - Screen Styling */
        [contenteditable="true"] {
            outline: none;
            transition: all 0.2s ease;
            cursor: text;
        }
        [contenteditable="true"]:hover {
            background-color: rgba(99, 102, 241, 0.05);
            box-shadow: 0 0 0 1px #818cf8;
            border-radius: 4px;
        }
        [contenteditable="true"]:focus {
            background-color: rgba(99, 102, 241, 0.08);
            box-shadow: 0 0 0 2px #6366f1;
            border-radius: 4px;
        }

        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 64px;
            font-weight: 900;
            color: rgba(148, 163, 184, 0.07);
            text-transform: uppercase;
            letter-spacing: 0.15em;
            pointer-events: none;
            user-select: none;
            white-space: nowrap;
        }

        /* Header Layout */
        .header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 22px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }
        .header-brand {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            flex: 1;
        }
        .logo-wrapper {
            position: relative;
            cursor: pointer;
            flex-shrink: 0;
        }
        .logo-img {
            max-height: 68px;
            max-width: 170px;
            object-fit: contain;
            display: block;
            border-radius: 6px;
        }
        .logo-placeholder {
            width: 72px;
            height: 60px;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            font-weight: 700;
            color: #64748b;
            background: #f8fafc;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            padding: 4px;
        }
        .logo-placeholder:hover {
            border-color: #6366f1;
            color: #6366f1;
            background: #eef2ff;
        }
        .logo-badge {
            display: inline-block;
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-top: 3px;
        }

        .brand-text-col {
            flex: 1;
        }
        .brand-title {
            font-size: 22px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.02em;
            text-transform: uppercase;
            line-height: 1.2;
            padding: 2px 4px;
        }
        .brand-details {
            font-size: 11px;
            color: #64748b;
            margin-top: 5px;
            line-height: 1.45;
            padding: 2px 4px;
        }

        .contract-badge {
            text-align: right;
            flex-shrink: 0;
        }
        .contract-title {
            font-family: 'Cinzel', serif;
            font-size: 16px;
            font-weight: 700;
            color: #1e1b4b;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .contract-ref {
            display: inline-block;
            background: #eef2ff;
            color: #4338ca;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.05em;
            margin-top: 5px;
            border: 1px solid #c7d2fe;
        }

        .agreement-heading {
            text-align: center;
            margin: 24px 0 20px 0;
        }
        .agreement-main-title {
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #0f172a;
            display: inline-block;
            padding: 2px 8px;
        }
        .agreement-sub-text {
            font-size: 11px;
            color: #64748b;
            font-style: italic;
            margin-top: 4px;
        }

        /* Parties Grid */
        .parties-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 24px 0;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
        }
        .party-card h4 {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #4f46e5;
            margin-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }
        .party-name {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            padding: 2px 4px;
        }
        .party-info {
            font-size: 11px;
            color: #475569;
            margin-top: 3px;
            line-height: 1.45;
            padding: 2px 4px;
        }

        /* Key Metrics Row */
        .meta-strip {
            display: flex;
            justify-content: space-between;
            background: #f1f5f9;
            border-left: 4px solid #4f46e5;
            padding: 10px 16px;
            margin: 20px 0;
            border-radius: 0 6px 6px 0;
            font-size: 11px;
        }
        .meta-strip div span:first-child {
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.05em;
            display: block;
        }
        .meta-strip div span:last-child {
            font-weight: 800;
            color: #0f172a;
        }

        /* Clauses & Body */
        .clauses-container {
            margin: 24px 0;
            font-size: 12px;
            line-height: 1.7;
            color: #334155;
            white-space: pre-wrap;
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
            padding-left: 4px;
            padding-right: 4px;
        }

        /* Signatures Section */
        .signature-section {
            margin-top: 48px;
            border-top: 1px solid #cbd5e1;
            padding-top: 24px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 24px;
            page-break-inside: avoid;
        }
        .sig-box {
            text-align: center;
        }
        .sig-line {
            height: 60px;
            border-bottom: 1px dashed #94a3b8;
            margin-bottom: 8px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            font-size: 10px;
            color: #cbd5e1;
        }
        .sig-label {
            font-size: 11px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .sig-sub {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }

        .seal-box {
            display: inline-block;
            border: 1px dashed #cbd5e1;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            line-height: 50px;
            font-size: 8px;
            color: #94a3b8;
            text-transform: uppercase;
            margin: 6px auto;
        }

        .footer-note {
            margin-top: 36px;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        /* Print Media Overrides */
        @media print {
            body {
                background: #ffffff;
            }
            .no-print {
                display: none !important;
            }
            .logo-placeholder {
                display: none !important;
            }
            .page-container {
                margin: 0 !important;
                padding: 24px !important;
                border: none !important;
                box-shadow: none !important;
                max-width: 100% !important;
                width: 100% !important;
            }
            [contenteditable="true"] {
                box-shadow: none !important;
                background-color: transparent !important;
            }
        }
    </style>
</head>
<body>

    @php
        $bizName = $settings['branch_name'] ?? (tenant('name') ?? 'Business Name');
        $bizAddress = $settings['branch_address'] ?? ($settings['store_address'] ?? '');
        $bizPhone = $settings['branch_phone'] ?? ($settings['store_phone'] ?? '');
        $bizEmail = $settings['store_email'] ?? '';
        $bizGstin = $settings['bill_gst_no'] ?? '';
        $defaultLogo = !empty($settings['business_logo']) ? tenant_asset($settings['business_logo']) : (!empty($settings['inv_header_img']) ? tenant_asset($settings['inv_header_img']) : '');
    @endphp

    <!-- Non-printable Toolbar -->
    <div class="no-print">
        <div class="toolbar-group">
            <a href="{{ route('tenant.agreements.index') }}" class="btn btn-secondary">
                ← Back
            </a>
            <span style="font-size: 12px; font-weight: 800; color: #4338ca; background: #eef2ff; padding: 4px 10px; border-radius: 6px;">
                {{ $agreement->agreement_number }}
            </span>
            <span class="edit-hint">
                ✏️ Click any header text or logo to edit directly
            </span>
        </div>

        <div class="toolbar-group">
            <!-- Hidden file input for logo -->
            <input type="file" id="logo_file_input" accept="image/*" style="display: none;" onchange="handleLogoUpload(event)">
            
            <button type="button" onclick="document.getElementById('logo_file_input').click()" class="btn btn-action" title="Upload custom logo for this agreement">
                📷 Upload Logo
            </button>
            <button type="button" id="remove_logo_btn" onclick="removeLogo()" class="btn btn-danger" style="display: none;" title="Remove logo from header">
                ✕ Remove Logo
            </button>
            <button type="button" onclick="resetHeaderToDefault()" class="btn btn-secondary" title="Reset header back to store settings">
                ↺ Reset Header
            </button>
            <button type="button" onclick="saveHeaderPermanently()" class="btn btn-action" title="Save this header & logo as default for all future agreements">
                💾 Save Header
            </button>
            <button type="button" onclick="copyExpiringShareLink()" class="btn btn-action" style="background: #f0fdf4; color: #166534; border-color: #bbf7d0;" title="Generate 30-min expiring secure link (OWASP Rule #3)">
                🔒 Share Expiring Link (30m)
            </button>
            <button type="button" onclick="window.print()" class="btn btn-primary">
                🖨️ Print Agreement
            </button>
        </div>
    </div>

    <div class="page-container">
        <div class="watermark">{{ strtoupper($agreement->status) }}</div>

        <!-- Header -->
        <div class="header">
            <div class="header-brand">
                <!-- Logo Area -->
                <div class="logo-wrapper" id="logo_container" onclick="document.getElementById('logo_file_input').click()" title="Click to upload or change logo">
                    <img id="header_logo_img" src="{{ $defaultLogo }}" class="logo-img" alt="Company Logo" style="{{ empty($defaultLogo) ? 'display: none;' : '' }}">
                    <div id="header_logo_placeholder" class="logo-placeholder" style="{{ !empty($defaultLogo) ? 'display: none;' : '' }}">
                        <span style="font-size: 16px;">📷</span>
                        <span>Add Logo</span>
                    </div>
                </div>

                <!-- Company Name & Details -->
                <div class="brand-text-col">
                    <div id="header_biz_name" class="brand-title" contenteditable="true" spellcheck="false" title="Click to edit company name" oninput="syncFirstParty()">{{ $bizName }}</div>
                    <div id="header_biz_details" class="brand-details" contenteditable="true" spellcheck="false" title="Click to edit address, phone, email, and GSTIN" oninput="syncFirstPartyInfo()">@if($bizAddress){{ $bizAddress }}<br>@endif @if($bizPhone)Phone: {{ $bizPhone }} @endif @if($bizEmail)| Email: {{ $bizEmail }}@endif @if($bizGstin)<br><strong>GSTIN:</strong> {{ $bizGstin }}@endif</div>
                </div>
            </div>

            <!-- Contract Metadata -->
            <div class="contract-badge">
                <div class="contract-title">Official Agreement</div>
                <div class="contract-ref">{{ $agreement->agreement_number }}</div>
                <div style="font-size: 10px; color: #64748b; margin-top: 5px;">
                    Date: {{ $agreement->start_date ? $agreement->start_date->format('d F, Y') : date('d F, Y') }}
                </div>
            </div>
        </div>

        <!-- Agreement Title -->
        <div class="agreement-heading">
            <h1 id="agreement_title_display" class="agreement-main-title" contenteditable="true" spellcheck="false" title="Click to edit title">{{ $agreement->title }}</h1>
            <p class="agreement-sub-text">Category: {{ $agreement->category }} | Status: {{ strtoupper($agreement->status) }}</p>
        </div>

        <!-- Parties Information -->
        <div class="parties-grid">
            <div class="party-card">
                <h4>First Party (Discloser / Provider)</h4>
                <div id="first_party_name_display" class="party-name" contenteditable="true" spellcheck="false" title="Click to edit First Party name">{{ $bizName }}</div>
                <div id="first_party_info_display" class="party-info" contenteditable="true" spellcheck="false" title="Click to edit First Party info">
                    @if($bizAddress) {{ $bizAddress }}<br> @endif
                    @if($bizPhone) Tel: {{ $bizPhone }}<br> @endif
                    @if($bizEmail) Email: {{ $bizEmail }} @endif
                </div>
            </div>
            <div class="party-card">
                <h4>Second Party (Client / Contractor)</h4>
                <div class="party-name" contenteditable="true" spellcheck="false" title="Click to edit Second Party name">{{ $agreement->party_name }}</div>
                <div class="party-info" contenteditable="true" spellcheck="false" title="Click to edit Second Party info">
                    @if($agreement->party_address) {{ $agreement->party_address }}<br> @endif
                    @if($agreement->party_phone) Tel: {{ $agreement->party_phone }}<br> @endif
                    @if($agreement->party_email) Email: {{ $agreement->party_email }} @endif
                </div>
            </div>
        </div>

        <!-- Key Financial & Timeline Metadata Strip -->
        <div class="meta-strip">
            <div>
                <span>Effective Date</span>
                <span contenteditable="true" spellcheck="false">{{ $agreement->start_date ? $agreement->start_date->format('d M, Y') : 'Immediate' }}</span>
            </div>
            <div>
                <span>Termination / Expiry</span>
                <span contenteditable="true" spellcheck="false">{{ $agreement->end_date ? $agreement->end_date->format('d M, Y') : 'Indefinite / Ongoing' }}</span>
            </div>
            <div>
                <span>Contract Value</span>
                <span contenteditable="true" spellcheck="false">₹{{ number_format($agreement->total_value, 2) }}</span>
            </div>
            <div>
                <span>Payment Schedule</span>
                <span contenteditable="true" spellcheck="false">{{ $agreement->payment_terms ?: 'As per invoice' }}</span>
            </div>
        </div>

        <!-- Agreement Body / Terms -->
        <div class="clauses-container" contenteditable="true" spellcheck="false" title="Click to edit clauses and conditions">
{{ $agreement->content ?: '1. SCOPE OF AGREEMENT: This agreement constitutes the entire understanding between the parties. Any modifications must be executed in writing and endorsed by authorized representatives of both parties.' }}
        </div>

        <!-- Signatures & Acceptance -->
        <div class="signature-section">
            <!-- First Party Sign -->
            <div class="sig-box">
                <div class="sig-line">
                    <span class="seal-box">SEAL</span>
                </div>
                <div id="first_party_sig_label" class="sig-label">For {{ $bizName }}</div>
                <div class="sig-sub">Authorized Signatory & Stamp</div>
            </div>

            <!-- Second Party Sign -->
            <div class="sig-box">
                <div class="sig-line">
                    @if(!empty($agreement->digital_signature))
                        <img src="{{ $agreement->digital_signature }}" alt="Digital Signature" style="max-height: 48px; max-width: 140px; margin: 0 auto; display: block;">
                        <div style="font-size: 8px; color: #10b981; font-weight: 700; margin-top: 2px;">✓ Digitally Signed & Sealed</div>
                    @endif
                </div>
                <div class="sig-label">For {{ $agreement->party_name }}</div>
                <div class="sig-sub">Accepted & Agreed (Second Party)</div>
            </div>

            <!-- Witness Sign -->
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-label" contenteditable="true" spellcheck="false">{{ $agreement->witness_name ?: 'Witness' }}</div>
                <div class="sig-sub">Witness Signature</div>
            </div>
        </div>

        <!-- Cryptographic Integrity & Encryption Seal (OWASP A02 & SRS Section 14 & 18) -->
        <div style="margin-top: 26px; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; gap: 12px; font-size: 10px;">
            <div>
                <div style="font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                    <span style="color: #10b981; font-size: 14px;">🔒</span> Cryptographically Secured (AES-256-CBC Encrypted at Rest)
                    @if(isset($isIntegrityValid) && $isIntegrityValid)
                        <span style="background: #dcfce7; color: #15803d; font-size: 9px; font-weight: 800; padding: 2px 6px; border-radius: 4px;">✓ Integrity Verified</span>
                    @endif
                </div>
                <div style="color: #64748b; font-family: monospace; font-size: 9px; margin-top: 3px; word-break: break-all;">
                    SHA-256 Checksum: {{ $agreement->document_checksum ?: $agreement->calculateChecksum() }}
                </div>
            </div>
            <div style="text-align: right; color: #64748b; font-size: 9px; white-space: nowrap;">
                @if($agreement->signed_at)
                    Signed: {{ $agreement->signed_at->format('d M Y, H:i') }}<br>
                    IP: {{ $agreement->signed_ip ?: 'Recorded' }}
                @else
                    Status: {{ strtoupper($agreement->status) }}
                @endif
            </div>
        </div>

        <div class="footer-note">
            This document is an official legally recognized business agreement executed on {{ date('d M, Y') }} | Ref # {{ $agreement->agreement_number }}
        </div>
    </div>

    <script>
        const STORAGE_KEY = 'agreement_header_branding_custom';
        const DEFAULT_NAME = @json($bizName);
        const DEFAULT_DETAILS = @json(($bizAddress ? $bizAddress . "\n" : '') . ($bizPhone ? "Phone: " . $bizPhone . " " : '') . ($bizEmail ? "| Email: " . $bizEmail . "\n" : '') . ($bizGstin ? "GSTIN: " . $bizGstin : ''));
        const DEFAULT_LOGO = @json($defaultLogo);

        // Load saved branding from localStorage on load
        window.addEventListener('DOMContentLoaded', () => {
            loadSavedHeader();
            updateRemoveLogoButtonState();
        });

        function handleLogoUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 3 * 1024 * 1024) {
                alert('Logo image should be under 3MB');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                setLogoSource(e.target.result);
                saveHeaderPermanently(false);
            };
            reader.readAsDataURL(file);
        }

        function setLogoSource(src) {
            const img = document.getElementById('header_logo_img');
            const placeholder = document.getElementById('header_logo_placeholder');

            if (src && src.trim() !== '') {
                img.src = src;
                img.style.display = 'block';
                placeholder.style.display = 'none';
            } else {
                img.src = '';
                img.style.display = 'none';
                placeholder.style.display = 'flex';
            }
            updateRemoveLogoButtonState();
        }

        function removeLogo() {
            setLogoSource('');
            saveHeaderPermanently(false);
        }

        function updateRemoveLogoButtonState() {
            const img = document.getElementById('header_logo_img');
            const btn = document.getElementById('remove_logo_btn');
            if (img && img.style.display !== 'none' && img.src && img.src !== '') {
                btn.style.display = 'inline-flex';
            } else {
                btn.style.display = 'none';
            }
        }

        function syncFirstParty() {
            const name = document.getElementById('header_biz_name').innerText.trim();
            const fp = document.getElementById('first_party_name_display');
            const sig = document.getElementById('first_party_sig_label');
            if (fp) fp.innerText = name;
            if (sig) sig.innerText = 'For ' + name;
        }

        function syncFirstPartyInfo() {
            const details = document.getElementById('header_biz_details').innerText.trim();
            const fpInfo = document.getElementById('first_party_info_display');
            if (fpInfo) fpInfo.innerText = details;
        }

        function saveHeaderPermanently(showAlert = true) {
            const name = document.getElementById('header_biz_name').innerHTML;
            const details = document.getElementById('header_biz_details').innerHTML;
            const img = document.getElementById('header_logo_img');
            const logoSrc = (img && img.style.display !== 'none') ? img.src : '';

            const data = {
                name: name,
                details: details,
                logo: logoSrc
            };

            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
                if (showAlert) {
                    alert('✅ Header & Logo saved! It will automatically load for all future agreement prints.');
                }
            } catch(e) {
                console.warn('Storage limit reached', e);
            }
        }

        function loadSavedHeader() {
            try {
                const saved = localStorage.getItem(STORAGE_KEY);
                if (saved) {
                    const data = JSON.parse(saved);
                    if (data.name) {
                        document.getElementById('header_biz_name').innerHTML = data.name;
                        syncFirstParty();
                    }
                    if (data.details) {
                        document.getElementById('header_biz_details').innerHTML = data.details;
                        syncFirstPartyInfo();
                    }
                    if (data.logo !== undefined) {
                        setLogoSource(data.logo);
                    }
                }
            } catch(e) {
                console.warn('Error loading saved header', e);
            }
        }

        function resetHeaderToDefault() {
            if (confirm('Reset company name, details, and logo back to store defaults?')) {
                localStorage.removeItem(STORAGE_KEY);
                document.getElementById('header_biz_name').innerText = DEFAULT_NAME;
                document.getElementById('header_biz_details').innerText = DEFAULT_DETAILS;
                setLogoSource(DEFAULT_LOGO);
                syncFirstParty();
                syncFirstPartyInfo();
            }
        }

        function copyExpiringShareLink() {
            fetch('{{ route("tenant.agreements.share-link", $agreement->id) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.secure_url) {
                    navigator.clipboard.writeText(data.secure_url);
                    alert('🔐 Secure Time-Limited Link copied to clipboard!\n\n• Link valid for 30 minutes.\n• Cryptographically signed via HMAC-SHA256.\n• Expiration strictly enforced (OWASP Non-Negotiable Rule #3).');
                } else {
                    alert('Could not generate secure link: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(() => alert('Failed to generate secure link.'));
        }
    </script>
</body>
</html>
