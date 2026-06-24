<?php

namespace App\Http\Controllers;

use App\Models\TrendingProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use App\GraphQL\Exceptions\ExceptionHandler;
use Exception;

class TrendingProductController extends Controller
{
    public function index()
    {
        try {
            return TrendingProduct::with(['product' => function($query) {
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

            $trending = [];
            $maxOrder = TrendingProduct::max('order') ?? 0;
            foreach (array_unique($productIds) as $productId) {
                $maxOrder++;
                $trending[] = TrendingProduct::firstOrCreate(
                    ['product_id' => $productId],
                    ['order' => $maxOrder]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Products added to trending successfully',
                'data' => $trending
            ], 201);
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), 500);
        }
    }

    public function destroy($productId)
    {
        try {
            $deleted = TrendingProduct::where('product_id', $productId)->delete();
            if (!$deleted) {
                return response()->json(['success' => false, 'message' => 'Trending product not found'], 404);
            }
            return response()->json(['success' => true, 'message' => 'Product removed from trending successfully']);
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), 500);
        }
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'trending_product_ids' => 'required|array',
            'trending_product_ids.*' => 'exists:trending_products,id'
        ]);

        try {
            foreach ($request->input('trending_product_ids') as $index => $id) {
                TrendingProduct::where('id', $id)->update(['order' => $index + 1]);
            }
            return response()->json(['success' => true, 'message' => 'Order updated successfully']);
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), 500);
        }
    }
}
