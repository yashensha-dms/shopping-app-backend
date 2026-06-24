<?php

namespace App\Http\Controllers;

use App\Models\BestsellingProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use App\GraphQL\Exceptions\ExceptionHandler;
use Exception;

class BestsellingProductController extends Controller
{
    public function index()
    {
        try {
            return BestsellingProduct::with(['product' => function($query) {
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

            $bestselling = [];
            $maxOrder = BestsellingProduct::max('order') ?? 0;
            foreach (array_unique($productIds) as $productId) {
                $maxOrder++;
                $bestselling[] = BestsellingProduct::firstOrCreate(
                    ['product_id' => $productId],
                    ['order' => $maxOrder]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Products added to bestselling successfully',
                'data' => $bestselling
            ], 201);
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), 500);
        }
    }

    public function destroy($productId)
    {
        try {
            $deleted = BestsellingProduct::where('product_id', $productId)->delete();
            if (!$deleted) {
                return response()->json(['success' => false, 'message' => 'Bestselling product not found'], 404);
            }
            return response()->json(['success' => true, 'message' => 'Product removed from bestselling successfully']);
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), 500);
        }
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'bestselling_product_ids' => 'required|array',
            'bestselling_product_ids.*' => 'exists:bestselling_products,id'
        ]);

        try {
            foreach ($request->input('bestselling_product_ids') as $index => $id) {
                BestsellingProduct::where('id', $id)->update(['order' => $index + 1]);
            }
            return response()->json(['success' => true, 'message' => 'Order updated successfully']);
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), 500);
        }
    }
}
