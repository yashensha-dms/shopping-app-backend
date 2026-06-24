<?php

namespace App\Http\Controllers;

use App\Models\FeaturedProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use App\GraphQL\Exceptions\ExceptionHandler;
use Exception;

class FeaturedProductController extends Controller
{
    public function index()
    {
        try {
            return FeaturedProduct::with(['product' => function($query) {
                $query->with(config('enums.product.with'));
            }])->orderBy('order', 'asc')->get();
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required_without:product_ids|exists:products,id',
            'product_ids' => 'required_without:product_id|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        try {
            $productIds = $request->input('product_ids', []);
            if ($request->has('product_id')) {
                $productIds[] = $request->input('product_id');
            }

            $featured = [];
            $maxOrder = FeaturedProduct::max('order') ?? 0;
            foreach (array_unique($productIds) as $productId) {
                $maxOrder++;
                $featured[] = FeaturedProduct::firstOrCreate(
                    ['product_id' => $productId],
                    ['order' => $maxOrder]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Products featured successfully',
                'data' => $featured
            ], 201);
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), 500);
        }
    }

    public function destroy($productId)
    {
        try {
            $deleted = FeaturedProduct::where('product_id', $productId)->delete();
            if (!$deleted) {
                return response()->json(['success' => false, 'message' => 'Featured product not found'], 404);
            }
            return response()->json(['success' => true, 'message' => 'Product unfeatured successfully']);
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), 500);
        }
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'featured_product_ids' => 'required|array',
            'featured_product_ids.*' => 'exists:featured_products,id'
        ]);

        try {
            foreach ($request->input('featured_product_ids') as $index => $id) {
                FeaturedProduct::where('id', $id)->update(['order' => $index + 1]);
            }
            return response()->json(['success' => true, 'message' => 'Order updated successfully']);
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), 500);
        }
    }
}
