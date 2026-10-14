<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class WebsiteProductsController extends Controller
{
    /**
     * Display listing of store products with website publish status
     */
    public function index(Request $request)
    {
        $query = Category::query();

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('product_type', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        // Status / Website filter
        $filter = $request->input('filter', 'all');
        if ($filter === 'website') {
            $query->where('show_on_website', true);
        } elseif ($filter === 'hidden') {
            $query->where(function ($q) {
                $q->where('show_on_website', false)->orWhereNull('show_on_website');
            });
        } elseif ($filter === 'instock') {
            $query->where('stock', '>', 0);
        } elseif ($filter === 'outofstock') {
            $query->where(function ($q) {
                $q->where('stock', '<=', 0)->orWhereNull('stock');
            });
        }

        // Category filter
        if ($category = $request->input('category')) {
            $query->where('product_type', $category);
        }

        $products = $query->latest()->paginate(20)->withQueryString();

        // Summary counts
        $totalProducts = Category::count();
        $liveOnWebsite = Category::where('show_on_website', true)->count();
        $hiddenProducts = Category::where(function ($q) {
            $q->where('show_on_website', false)->orWhereNull('show_on_website');
        })->count();
        $inStockCount = Category::where('stock', '>', 0)->count();

        // Unique categories for filter dropdown
        $categories = Category::whereNotNull('product_type')
            ->where('product_type', '!=', '')
            ->distinct()
            ->pluck('product_type');

        return view('tenant.website_products.index', compact(
            'products',
            'totalProducts',
            'liveOnWebsite',
            'hiddenProducts',
            'inStockCount',
            'categories',
            'filter'
        ));
    }

    /**
     * Toggle individual product website status
     */
    public function toggle(Request $request, $id)
    {
        $product = Category::findOrFail($id);
        $product->show_on_website = !$product->show_on_website;
        $product->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'show_on_website' => (bool) $product->show_on_website,
                'message' => $product->show_on_website 
                    ? "'{$product->product_name}' is now live on front site!" 
                    : "'{$product->product_name}' hidden from front site.",
            ]);
        }

        $msg = $product->show_on_website 
            ? "Product '{$product->product_name}' added to website successfully!" 
            : "Product '{$product->product_name}' removed from website.";

        return back()->with('success', $msg);
    }

    /**
     * Bulk actions for store products (Publish All, Hide All, Selected)
     */
    public function bulk(Request $request)
    {
        $action = $request->input('action');
        $selectedIds = $request->input('selected_ids', []);

        // If user chose to select all matching items across all pages
        if ($request->boolean('select_all_matching')) {
            $query = Category::query();

            // Search filter
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                      ->orWhere('barcode', 'like', "%{$search}%")
                      ->orWhere('brand', 'like', "%{$search}%")
                      ->orWhere('product_type', 'like', "%{$search}%")
                      ->orWhere('model', 'like', "%{$search}%");
                });
            }

            // Status / Website filter
            $filter = $request->input('filter', 'all');
            if ($filter === 'website') {
                $query->where('show_on_website', true);
            } elseif ($filter === 'hidden') {
                $query->where(function ($q) {
                    $q->where('show_on_website', false)->orWhereNull('show_on_website');
                });
            } elseif ($filter === 'instock') {
                $query->where('stock', '>', 0);
            } elseif ($filter === 'outofstock') {
                $query->where(function ($q) {
                    $q->where('stock', '<=', 0)->orWhereNull('stock');
                });
            }

            // Category filter
            if ($category = $request->input('category')) {
                $query->where('product_type', $category);
            }

            $count = $query->count();
            if ($action === 'publish_selected' || $action === 'publish_all') {
                $query->update(['show_on_website' => true]);
                return back()->with('success', "All {$count} matching products published to front site successfully!");
            }

            if ($action === 'hide_selected' || $action === 'hide_all') {
                $query->update(['show_on_website' => false]);
                return back()->with('success', "All {$count} matching products hidden from front site.");
            }
        }

        if ($action === 'publish_all') {
            Category::query()->update(['show_on_website' => true]);
            return back()->with('success', 'All store products published to front site successfully!');
        }

        if ($action === 'hide_all') {
            Category::query()->update(['show_on_website' => false]);
            return back()->with('success', 'All products hidden from front site.');
        }

        if ($action === 'publish_instock') {
            Category::where('stock', '>', 0)->update(['show_on_website' => true]);
            return back()->with('success', 'All in-stock products published to front site!');
        }

        if ($action === 'publish_selected' && !empty($selectedIds)) {
            Category::whereIn('id', $selectedIds)->update(['show_on_website' => true]);
            return back()->with('success', count($selectedIds) . ' products published to front site!');
        }

        if ($action === 'hide_selected' && !empty($selectedIds)) {
            Category::whereIn('id', $selectedIds)->update(['show_on_website' => false]);
            return back()->with('success', count($selectedIds) . ' products hidden from front site.');
        }

        return back()->with('error', 'Please select products or choose a valid bulk action.');
    }
}
