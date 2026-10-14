<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Category;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceMail;
use App\Services\WhatsappService;

class BillingController extends Controller
{
    /**
     * Main POS Entry Point
     */
    public function index(Request $request)
    {
        $today = now()->format('Y-m-d');
        $productTypes = Category::select('product_type')
                                ->distinct()
                                ->whereNotNull('product_type')
                                ->pluck('product_type');

        $stockHealth = [
            'total' => Category::count(),
            'low' => Category::where('stock', '>', 0)->where('stock', '<=', 10)->count(),
            'out' => Category::where('stock', '<=', 0)->count(),
        ];
        $stockHealth['percentage'] = $stockHealth['total'] > 0 ? round((($stockHealth['total'] - $stockHealth['out']) / $stockHealth['total']) * 100) : 100;

        // Fetch System Settings
        $settings = [
            'gst_enabled'      => \App\Models\Setting::get('gst_enabled', \App\Models\Setting::get('bill_gst_enabled', '1')) == '1',
            'discount_enabled' => \App\Models\Setting::get('bill_discount_enabled', '1') == '1',
            'default_gst'      => \App\Models\Setting::get('gst_default_percent', \App\Models\Setting::get('billing_tax_percent', 0)),
            'gst_app_type'     => \App\Models\Setting::get('gst_app_type', 'per_product'),
            'gst_calc_type'    => \App\Models\Setting::get('gst_calc_type', 'inclusive'),
            'payment_modes'    => [
                'cash'   => \App\Models\Setting::get('pay_cash', '1') == '1',
                'qr'     => \App\Models\Setting::get('pay_qr', '1') == '1',
                'card'   => \App\Models\Setting::get('pay_card', '1') == '1',
                'credit' => \App\Models\Setting::get('pay_credit', '1') == '1',
                'coupon' => \App\Models\Setting::get('pay_coupon', '0') == '1',
            ],
            'inv_print_pos'    => \App\Models\Setting::get('inv_print_pos', '1') == '1',
            'inv_print_a4'     => \App\Models\Setting::get('inv_print_a4', '0') == '1',
            'inv_auto_print'   => \App\Models\Setting::get('inv_auto_print', '0') == '1',
        ];

        $draftBill = null;
        if ($request->has('draft')) {
            $draftBill = Bill::with('items.category')->find($request->draft);
        }

        $exchangeRma = null;
        $creditNote = null;
        if ($request->has('exchange_rma')) {
            $exchangeRma = \App\Models\RmaRequest::with('bill')->find($request->exchange_rma);
            if ($exchangeRma) {
                // Find the associated credit note
                $creditNote = \App\Models\Bill::whereIn('bill_type', ['credit_note', 'exchange', 'replacement'])
                    ->where('remarks', 'LIKE', "%{$exchangeRma->rma_number}%")
                    ->first();
            }
        }

        return view('tenant.billing.index', compact('today', 'productTypes', 'stockHealth', 'settings', 'draftBill', 'exchangeRma', 'creditNote'));
    }

    public function outward()
    {
        $today = now()->format('Y-m-d');
        $productTypes = Category::select('product_type')
                                ->distinct()
                                ->whereNotNull('product_type')
                                ->pluck('product_type');

        $stockHealth = [
            'total' => Category::count(),
            'low' => Category::where('stock', '>', 0)->where('stock', '<=', 10)->count(),
            'out' => Category::where('stock', '<=', 0)->count(),
        ];
        $stockHealth['percentage'] = $stockHealth['total'] > 0 ? round((($stockHealth['total'] - $stockHealth['out']) / $stockHealth['total']) * 100) : 100;

        // Fetch System Settings
        $settings = [
            'gst_enabled'      => \App\Models\Setting::get('gst_enabled', \App\Models\Setting::get('bill_gst_enabled', '1')) == '1',
            'discount_enabled' => \App\Models\Setting::get('bill_discount_enabled', '1') == '1',
            'default_gst'      => \App\Models\Setting::get('gst_default_percent', \App\Models\Setting::get('billing_tax_percent', 0)),
            'gst_app_type'     => \App\Models\Setting::get('gst_app_type', 'per_product'),
            'gst_calc_type'    => \App\Models\Setting::get('gst_calc_type', 'inclusive'),
            'payment_modes'    => [
                'cash'   => \App\Models\Setting::get('pay_cash', '1') == '1',
                'qr'     => \App\Models\Setting::get('pay_qr', '1') == '1',
                'card'   => \App\Models\Setting::get('pay_card', '1') == '1',
                'credit' => \App\Models\Setting::get('pay_credit', '1') == '1',
                'coupon' => \App\Models\Setting::get('pay_coupon', '0') == '1',
            ],
            'inv_print_pos'    => \App\Models\Setting::get('inv_print_pos', '1') == '1',
            'inv_print_a4'     => \App\Models\Setting::get('inv_print_a4', '0') == '1',
            'inv_auto_print'   => \App\Models\Setting::get('inv_auto_print', '0') == '1',
        ];

        $products = \App\Models\Category::where('is_active', true)->get();
        $outwards = \App\Models\Bill::with('items')->where('bill_type', 'outward')->orderBy('id', 'desc')->get();
        
        $todayOutwards = $outwards->filter(fn($b) => \Carbon\Carbon::parse($b->bill_date)->isToday());
        $summary = [
            'today_orders' => $todayOutwards->count(),
            'today_qty' => $todayOutwards->sum(fn($b) => $b->items->sum('quantity')),
            'pending_orders' => $outwards->where('status', 'pending')->count(),
            'total_value' => $outwards->sum('grand_total')
        ];

        return view('tenant.billing.outward', compact('today', 'productTypes', 'stockHealth', 'settings', 'products', 'outwards', 'summary'));
    }
    public function exportOutward(Request $request)
    {
        $outwards = \App\Models\Bill::with('items')->where('bill_type', 'outward')->orderBy('id', 'desc')->get();
        
        $directory = sys_get_temp_dir();
        
        $zipFile = $directory . '/outwards_export_' . date('Y_m_d_H_i_s') . '.zip';
        $zip = new \ZipArchive();
        
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
            $isPublic = false;

            foreach ($outwards as $bill) {
                // Using the basic print view which is dompdf friendly
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('tenant.billing.print', compact('bill', 'settings', 'isPublic'));
                
                $pdfContent = $pdf->output();
                // Clean the invoice number to use as filename
                $cleanInvoiceNo = preg_replace('/[^A-Za-z0-9\-]/', '_', $bill->invoice_no);
                $filename = 'Outward_' . $cleanInvoiceNo . '.pdf';
                
                $zip->addFromString($filename, $pdfContent);
            }
            $zip->close();
        } else {
            return back()->with('error', 'Could not create zip file.');
        }

        return response()->download($zipFile)->deleteFileAfterSend(true);
    }

    public function quick()
    {
        $products = Category::where('is_active', true)->get();
        $categories = Category::select('product_type')
                              ->distinct()
                              ->whereNotNull('product_type')
                              ->pluck('product_type');
        
        $settings = [
            'gst_enabled'      => \App\Models\Setting::get('gst_enabled', '1') == '1',
            'default_gst'      => \App\Models\Setting::get('gst_default_percent', 0),
            'gst_app_type'     => \App\Models\Setting::get('gst_app_type', 'per_product'),
            'gst_calc_type'    => \App\Models\Setting::get('gst_calc_type', 'inclusive'),
            'inv_print_pos'    => \App\Models\Setting::get('inv_print_pos', '1') == '1',
            'inv_print_a4'     => \App\Models\Setting::get('inv_print_a4', '0') == '1',
            'inv_auto_print'   => \App\Models\Setting::get('inv_auto_print', '0') == '1',
        ];

        return view('tenant.billing.quick', compact('products', 'categories', 'settings'));
    }

    /**
     * AJAX: Search Products
     */
    public function searchProducts(Request $request)
    {
        $term = $request->query('q');
        $type = $request->query('type', 'All');
        
        $query = Category::where('is_active', true);
        
        if ($term) {
            $query->search($term);
        }
        
        if ($type !== 'All') {
            $query->where('product_type', $type);
        }

        $products = $query->take(20)->get();
        return response()->json($products);
    }

    /**
     * AJAX: Scan Barcode
     */
    public function scanBarcode(Request $request)
    {
        $barcode = $request->query('barcode');
        
        // 1. Try exact barcode match
        $product = Category::where('barcode', $barcode)->where('is_active', true)->first();
        
        // 2. If not found, try smart search (name or brand)
        if (!$product) {
            $product = Category::where('is_active', true)
                               ->where(function($q) use ($barcode) {
                                   $q->where('product_name', 'ilike', "%{$barcode}%")
                                     ->orWhere('brand', 'ilike', "%{$barcode}%");
                               })
                               ->first();
        }
        
        return response()->json([
            'found'   => (bool)$product,
            'product' => $product
        ]);
    }

    /**
     * AJAX: Customer Lookup
     */
    public function lookupCustomer(Request $request)
    {
        $phone = $request->query('phone');
        $customer = Customer::with('activeMembership.plan')->where('phone', $phone)->first();

        // Automatically create new customer if not found by phone
        $wasCreated = false;
        if (!$customer && strlen($phone) >= 10 && preg_match('/^\d{10}$/', $phone)) {
            $customer = Customer::create(['phone' => $phone]);
            $wasCreated = true;
        }

        $creditBalance = 0;
        if ($customer) {
            $creditBalance = Bill::where('customer_id', $customer->id)
                ->where('payment_mode', 'credit')
                ->where('balance', '>', 0)
                ->sum('balance');
        }

        return response()->json([
            'found'          => (bool)$customer,
            'was_created'    => $wasCreated,
            'customer'       => $customer,
            'credit_balance' => round($creditBalance, 2),
        ]);
    }

    /**
     * POST: Generate Invoice
     */
    public function generateInvoice(Request $request)
    {
        // Normalize items array to support both 'id' and 'category_id'
        if ($request->has('items')) {
            $normalizedItems = array_map(function($item) {
                if (!isset($item['id']) && isset($item['category_id'])) {
                    $item['id'] = $item['category_id'];
                }
                if (!isset($item['qty']) && isset($item['quantity'])) {
                    $item['qty'] = $item['quantity'];
                }
                if (!isset($item['price']) && isset($item['mrp'])) {
                    $item['price'] = $item['mrp'];
                }
                return $item;
            }, $request->input('items'));
            $request->merge(['items' => $normalizedItems]);
        }

        $data = $request->validate([
            'customer_phone' => 'nullable|string',
            'customer_gstin' => 'nullable|string',
            'subtotal'       => 'nullable|numeric',
            'discount_amt'   => 'nullable|numeric',
            'discount_amount' => 'nullable|numeric',
            'tax_amt'        => 'nullable|numeric',
            'gst_amount'     => 'nullable|numeric',
            'grand_total'    => 'nullable|numeric',
            'customer_email' => 'nullable|email',
            'payment_mode'   => 'required|string',
            'amount_paid'    => 'nullable|numeric',
            'items'          => 'required|array',
            'items.*.id'       => 'required|exists:categories,id',
            'items.*.qty'      => 'required|integer|min:1',
            'items.*.price'    => 'required|numeric',
            'reminder_enabled' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($data, $request) {
            // Find or create customer
            $customer = null;
            if ($data['customer_phone']) {
                $customer = Customer::updateOrCreate(
                    ['phone' => $data['customer_phone']],
                    [
                        'name'    => $request->input('customer_name'),
                        'address' => $request->input('customer_address'),
                        'gstin'   => $request->input('customer_gstin'),
                    ]
                );
                $customer->increment('bill_count');
            }

            $billType = $request->input('bill_type', 'billing');
            
            // Create Bill
            $bill = Bill::create([
                'invoice_no'       => Bill::generateInvoiceNo($billType),
                'bill_type'        => $billType,
                'customer_id'      => $customer?->id,
                'customer_phone'   => $data['customer_phone'],
                'customer_email'   => $data['customer_email'] ?? $request->input('customer_email'),
                'customer_name'    => $request->input('customer_name'),
                'customer_address' => $request->input('customer_address'),
                'customer_gstin'   => $request->input('customer_gstin'),
                'bill_date'        => $request->input('bill_date', now()),
                'subtotal'         => $data['subtotal'] ?? 0,
                'discount_amount'  => $data['discount_amt'] ?? $data['discount_amount'] ?? 0,
                'discount_percent' => $request->input('discount_percent', 0),
                'gst_amount'       => $data['tax_amt'] ?? $data['gst_amount'] ?? 0,
                'gst_percent'      => $request->input('gst_percent', 18),
                'grand_total'      => $data['grand_total'] ?? 0,
                'paid_amount'      => $data['amount_paid'] ?? $data['grand_total'] ?? 0,
                'payment_mode'     => strtolower($data['payment_mode']),
                'status'           => 'completed',
                'reminder_enabled' => $request->input('reminder_enabled', false),
                'created_by'       => auth()->id(),
            ]);

            // Create Items & Update Stock
            foreach ($data['items'] as $item) {
                $product = Category::find($item['id']);

                if (!$product) {
                    continue; // Skip if product not found (should be caught by validation, but safety first)
                }

                BillItem::create([
                    'bill_id'      => $bill->id,
                    'category_id'  => $product->id,
                    'barcode'      => $product->barcode,
                    'product_name' => $product->product_name,
                    'brand'        => $product->brand,
                    'size'         => $product->size,
                    'mrp'          => $item['price'],
                    'quantity'     => $item['qty'],
                ]);

                // Log Stock Change
                \App\Models\StockLog::log($product->id, $item['qty'], 'out', $bill->id, 'bill');

                // Deduct Stock
                $product->decrement('stock', $item['qty']);
            }

            // Recalculate totals from items to ensure accuracy
            $bill->recalculate();

            // Award Loyalty Points (1 point per ₹100 spent)
            if ($customer) {
                $pointsEarned = floor($bill->grand_total / 100);
                if ($pointsEarned > 0) {
                    $customer->increment('points', $pointsEarned);
                }
            }

            // If it's a credit sale, paid_amount should be 0 unless specified otherwise
            if (strtolower($bill->payment_mode) === 'credit') {
                $bill->update(['paid_amount' => $request->input('amount_paid', 0)]);
            } elseif (strtolower($bill->payment_mode) === 'wallet') {
                // Legacy entire bill wallet payment support
                if ($customer && $customer->wallet_balance >= $bill->grand_total) {
                    $customer->decrement('wallet_balance', $bill->grand_total);
                    $bill->update(['paid_amount' => $bill->grand_total]);
                } else {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'payment_mode' => 'Customer does not have enough wallet balance.'
                    ]);
                }
            } elseif (strtolower($bill->payment_mode) === 'gift_card') {
                $gcCode = strtoupper(trim($request->input('gift_card_code', '')));
                if ($gcCode) {
                    $gc = \App\Models\GiftCard::where('code', $gcCode)->first();
                    if ($gc && $gc->status === 'active' && $gc->current_balance > 0) {
                        $deduct = min($gc->current_balance, $bill->grand_total);
                        $gc->decrement('current_balance', $deduct);
                        if ($gc->current_balance <= 0) {
                            $gc->update([
                                'status' => 'redeemed',
                                'redeemed_at' => now(),
                                'redeemed_by_customer_id' => $customer?->id,
                            ]);
                        }
                        $existingRemarks = $bill->remarks ?? '';
                        $bill->update([
                            'paid_amount' => $deduct,
                            'remarks' => trim($existingRemarks . " [Gift Card {$gc->code}: ₹{$deduct}]"),
                        ]);
                    }
                }
            } elseif (!$request->filled('amount_paid')) {
                // For other modes, assume full payment if not provided
                $bill->update(['paid_amount' => $bill->grand_total]);
            }

            // Process partial wallet usage
            $walletUsed = $request->input('wallet_used', 0);
            $exchangeCreditUsed = $request->input('exchange_credit_used', 0);
            
            if ($walletUsed > 0 && $customer) {
                if ($customer->wallet_balance >= $walletUsed) {
                    $customer->decrement('wallet_balance', $walletUsed);
                    
                    // Add note to remarks about wallet usage
                    $existingRemarks = $bill->remarks ?? '';
                    $newRemarks = trim($existingRemarks . " [Wallet Used: ₹{$walletUsed}]");
                    
                    $newPaidAmount = $bill->paid_amount + $walletUsed;
                    $bill->update([
                        'paid_amount' => $newPaidAmount, // Total value satisfied
                        'balance' => max(0, $bill->grand_total - $newPaidAmount),
                        'remarks' => $newRemarks
                    ]);
                }
            }
            
            // Process exchange credit usage
            $exchangeCreditUsed = $request->input('exchange_credit_used', 0);
            $exchangeCreditTotal = $request->input('exchange_credit_total', 0);
            
            if ($exchangeCreditUsed > 0) {
                $existingRemarks = $bill->remarks ?? '';
                $newRemarks = trim($existingRemarks . " [Exchange Credit Used: ₹{$exchangeCreditUsed}]");
                
                $newPaidAmount = $bill->paid_amount + $exchangeCreditUsed;
                $bill->update([
                    'paid_amount' => $newPaidAmount,
                    'balance' => max(0, $bill->grand_total - $newPaidAmount),
                    'remarks' => $newRemarks
                ]);
            }
            
            // Mark RMA as completed if exchange_rma_id is present, and process remaining credit
            $exchangeRmaId = $request->input('exchange_rma_id');
            if ($exchangeRmaId) {
                $rmaToComplete = \App\Models\RmaRequest::find($exchangeRmaId);
                if ($rmaToComplete) {
                    $rmaToComplete->status = 'completed';
                    $existingNotes = $rmaToComplete->notes ?? '';
                    $rmaToComplete->notes = trim($existingNotes . "\n[" . now()->format('Y-m-d H:i') . "] Exchange completed via Invoice: " . $bill->invoice_no);
                    $rmaToComplete->save();
                }
                
                // Credit remaining amount to the selected customer's wallet
                $remainingExchangeCredit = max(0, $exchangeCreditTotal - $exchangeCreditUsed);
                if ($remainingExchangeCredit > 0 && $customer) {
                    $customer->increment('wallet_balance', $remainingExchangeCredit);
                    
                    $bill->update([
                        'remarks' => trim($bill->remarks . " [Remaining Exchange Credit ₹{$remainingExchangeCredit} added to Wallet]")
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Bill generated successfully!',
                'bill_id' => $bill->id,
                'invoice_no' => $bill->invoice_no,
                'grand_total' => $bill->grand_total
            ]);
        });
    }

    /**
     * View specific invoice
     */
    public function viewInvoice(Bill $bill)
    {
        $bill->load('items');
        $bill->recalculate();
        $layout = json_decode(\App\Models\Setting::get('a4_layout_json', '{}'), true);
        $settings = [
            'name'    => \App\Models\Setting::get('business_name', config('app.name')),
            'address' => \App\Models\Setting::get('business_address', ''),
            'phone'   => \App\Models\Setting::get('business_phone', ''),
            'gst'     => \App\Models\Setting::get('business_gst', ''),
            'logo'    => \App\Models\Setting::get('business_logo', ''),
            'inv_header_img' => \App\Models\Setting::get('inv_header_img', ''),
            'terms'   => \App\Models\Setting::get('billing_terms', 'Thank You For Shopping!'),
        ];
        return view('tenant.billing.a4-advanced', compact('bill', 'layout', 'settings'));
    }

    /**
     * Public view for specific invoice via signed route
     */
    public function publicInvoice(Request $request, Bill $bill)
    {
        if (!$request->hasValidSignature()) {
            abort(401, 'This invoice link has expired or is invalid.');
        }

        $bill->load('items');
        $bill->recalculate();
        $layout = json_decode(\App\Models\Setting::get('a4_layout_json', '{}'), true);
        $settings = [
            'name'    => \App\Models\Setting::get('business_name', config('app.name')),
            'address' => \App\Models\Setting::get('business_address', ''),
            'phone'   => \App\Models\Setting::get('business_phone', ''),
            'gst'     => \App\Models\Setting::get('business_gst', ''),
            'logo'    => \App\Models\Setting::get('business_logo', ''),
            'inv_header_img' => \App\Models\Setting::get('inv_header_img', ''),
            'terms'   => \App\Models\Setting::get('billing_terms', 'Thank You For Shopping!'),
        ];
        
        $isPublic = true;
        return view('tenant.billing.a4-advanced', compact('bill', 'layout', 'settings', 'isPublic'));
    }

    public function print(Bill $bill)
    {
        $bill->load('items');
        $bill->recalculate();
        $layout = json_decode(\App\Models\Setting::get('pos_layout_json', '{}'), true);
        $settings = [
            'name'    => \App\Models\Setting::get('business_name', config('app.name')),
            'address' => \App\Models\Setting::get('business_address', ''),
            'phone'   => \App\Models\Setting::get('business_phone', ''),
            'gst'     => \App\Models\Setting::get('business_gst', ''),
            'logo'    => \App\Models\Setting::get('business_logo', ''),
            'inv_header_img' => \App\Models\Setting::get('inv_header_img', ''),
            'terms'   => \App\Models\Setting::get('billing_terms', 'Thank You For Shopping!'),
        ];

        return view('tenant.billing.print', compact('bill', 'settings', 'layout'));
    }

    /**
     * Email Invoice to Customer
     */
    public function emailInvoice(Request $request, Bill $bill)
    {
        $email = $request->input('email', $bill->customer_email);
        
        if (!$email && $bill->customer_id) {
            $email = $bill->customer?->email;
        }

        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Customer email address not found.'
            ], 422);
        }

        try {
            Mail::to($email)->send(new InvoiceMail($bill));
            
            // Update bill with email if it was missing
            if (!$bill->customer_email) {
                $bill->update(['customer_email' => $email]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Invoice emailed successfully to ' . $email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send Invoice via WhatsApp API
     */
    public function sendWhatsapp(Bill $bill, WhatsappService $whatsapp)
    {
        if (!$bill->customer_phone) {
            return response()->json([
                'success' => false,
                'message' => 'Customer phone number not found.'
            ], 422);
        }

        $result = $whatsapp->sendMessage($bill->customer_phone, $bill->whatsapp_message);

        return response()->json($result);
    }

    /**
     * Billing History
     */
    public function previousBills()
    {
        $bills = Bill::with('customer')->latest()->take(10)->get();
        return response()->json([
            'data' => $bills
        ]);
    }

    /**
     * Process Return
     */
    public function returnInvoice(Request $request)
    {
        $data = $request->validate([
            'bill_id'       => 'required|exists:bills,id',
            'items'         => 'required|array|min:1',
            'items.*.id'    => 'required|exists:bill_items,id',
            'items.*.qty'   => 'required|integer|min:1',
            'reason'        => 'nullable|string|max:500',
            'refund_mode'   => 'nullable|in:cash,wallet,store_credit',
        ]);

        return DB::transaction(function () use ($data) {
            $bill = Bill::with('items.category')->find($data['bill_id']);

            $returnTotal = 0;
            $returnedItems = [];

            foreach ($data['items'] as $ret) {
                $billItem = $bill->items->firstWhere('id', $ret['id']);
                if (!$billItem) continue;

                $qty = min($ret['qty'], $billItem->quantity);
                $lineTotal = $billItem->mrp * $qty;
                $returnTotal += $lineTotal;

                // Restore stock
                if ($billItem->category) {
                    $billItem->category->increment('stock', $qty);
                    \App\Models\StockLog::log($billItem->category_id, $qty, 'in', $bill->id, 'return');
                }

                $returnedItems[] = [
                    'product'  => $billItem->product_name,
                    'qty'      => $qty,
                    'subtotal' => $lineTotal,
                ];
            }

            if ($returnTotal <= 0) {
                return response()->json(['success' => false, 'message' => 'No valid items to return.'], 422);
            }

            // Create RMA/return record
            $rma = \App\Models\RmaRequest::create([
                'bill_id'      => $bill->id,
                'rma_number'   => 'RMA-' . strtoupper(\Illuminate\Support\Str::random(8)),
                'reason'       => $data['reason'] ?? 'Customer return',
                'refund_amount' => $returnTotal,
                'refund_mode'  => $data['refund_mode'] ?? 'cash',
                'status'       => 'completed',
                'requested_at' => now(),
            ]);

            // If wallet refund, credit customer wallet
            if (($data['refund_mode'] ?? 'cash') === 'wallet' && $bill->customer_id) {
                $customer = \App\Models\Customer::find($bill->customer_id);
                $customer?->increment('wallet_balance', $returnTotal);
            }

            return response()->json([
                'success'        => true,
                'message'        => 'Return processed successfully.',
                'rma_number'     => $rma->rma_number,
                'refund_amount'  => $returnTotal,
                'items_returned' => $returnedItems,
            ]);
        });
    }

    public function preOrders()
    {
        // Fetch all recent bills (not just drafts) for the Pre Orders Dashboard
        $drafts = Bill::with('customer')->latest()->limit(500)->get();
        
        $invoiceTypes = json_decode(\App\Models\Setting::get('invoice_types_json', '[]'), true) ?: [];
        $orderStatuses = json_decode(\App\Models\Setting::get('order_statuses_json', '[]'), true) ?: [];
        
        return view('tenant.billing.pre-orders', compact('drafts', 'invoiceTypes', 'orderStatuses'));
    }

    public function updateOrderStatus(Request $request, Bill $bill)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        $bill->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'status' => $bill->status
        ]);
    }

    public function updateExpectedDeliveryDate(Request $request, Bill $bill)
    {
        $request->validate([
            'expected_delivery_date' => 'required|date'
        ]);

        $bill->update([
            'expected_delivery_date' => $request->expected_delivery_date
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expected delivery date updated successfully.',
            'expected_delivery_date' => $bill->expected_delivery_date->format('Y-m-d')
        ]);
    }

    public function convertToSalesOrder(Request $request, Bill $bill)
    {
        $request->validate([
            'amount' => 'nullable|numeric|min:0',
            'payment_mode' => 'nullable|string'
        ]);

        $amount = (float) $request->input('amount', 0);
        $paymentMode = $request->input('payment_mode', 'cash');

        $result = \App\Services\PreOrderService::convertToSalesOrder($bill->id, $amount, $paymentMode);

        return response()->json($result);
    }

    public function searchCustomer(Request $request)
    {
        $query = $request->input('query');
        $customers = Customer::where('phone', 'LIKE', "%{$query}%")
            ->orWhere('name', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name', 'phone', 'address']);

        // Auto create if exactly 10 digits and no exact phone match exists
        if (preg_match('/^\d{10}$/', $query) && !$customers->contains('phone', $query)) {
            $customer = Customer::create(['phone' => $query]);
            $customer->was_created = true;
            
            // Re-fetch to return proper format
            $customers = Customer::where('phone', 'LIKE', "%{$query}%")
                ->orWhere('name', 'LIKE', "%{$query}%")
                ->limit(5)
                ->get(['id', 'name', 'phone', 'address']);
                
            // Add flag to the newly created one
            $customers->transform(function ($c) use ($query) {
                if ($c->phone === $query) $c->was_created = true;
                return $c;
            });
        }

        return response()->json($customers);
    }

    /**
     * Store alias for generateInvoice
     */
    public function store(Request $request)
    {
        return $this->generateInvoice($request);
    }
}
