<?php

namespace App\Repositories\Eloquents;

use Exception;
use Carbon\Carbon;
use App\Models\Product;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use App\Models\Variation;
use App\Enums\StockStatus;
use App\Imports\ProductImport;
use App\Exports\ProductsExport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\GraphQL\Exceptions\ExceptionHandler;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;

class ProductRepository extends BaseRepository
{
    protected $variations;

    protected $fieldSearchable = [
        'name' => 'like',
        'sku' => 'like',
        'variations.sku' => 'like',
        'stock_status' => 'like',
        'store.store_name' => 'like'
    ];

    public function boot()
    {
        try {

            $this->pushCriteria(app(RequestCriteria::class));

        } catch (ExceptionHandler $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    function model()
    {
        $this->variations = new Variation();
        return Product::class;
    }

    public function show($id)
    {
        try {

            return $this->model->with(config('enums.product.with'))
                ->get()
                ->makeVisible(config('enums.product.visible'))
                ->find($id)
                ->setAppends(config('enums.product.appends'));

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function getMinPriceVariation($request, $price)
    {
        return head(array_filter($request['variations'], function ($variation) use ($price) {
            return $variation['price'] == $price;
        }));
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {

            $quantity = 0;
            $roleName = Helpers::getCurrentRoleName();

            if ($roleName != RoleEnum::ADMIN) {
                $settings = Helpers::getSettings();
                if ($roleName == RoleEnum::VENDOR && !Helpers::isMultiVendorEnable()) {
                    throw new Exception('The multi-vendor feature is currently deactivated.', 403);
                }

                $isAutoApprove = $settings['activation']['product_auto_approve'];
            }

            if (isset($request['variations']) && !empty($request['variations']) && $request->type == 'classified') {
                $price = min(array_column($request['variations'], 'price'));
                $minPriceVariation = $this->getMinPriceVariation($request, $price);
                $discount = $minPriceVariation['discount'];
                $sale_price = round($price  - (($price  * $discount)/100), 2);
                $quantity = max(array_column($request['variations'], 'quantity'));
                $stock_status = StockStatus::OUT_OF_STOCK;

                if ($quantity > 0) {
                    $stock_status = StockStatus::IN_STOCK;
                }
            }

            if (isset($request->quantity)) {
                $stock_status = StockStatus::OUT_OF_STOCK;
                if ($request->quantity > 0) {
                    $stock_status = StockStatus::IN_STOCK;
                }
            }

            if (isset($request->discount)) {
                $mrpPrice = $request->price ?? $price;
                $sale_price = round($mrpPrice - (($mrpPrice * $request->discount)/100), 2);
            }

            $product = $this->model->create([
                'name' => $request->name,
                'short_description' => $request->short_description,
                'description' => $request->description,
                'type' => $request->type,
                'unit' => $request->unit,
                'quantity' => $request->quantity ?? $quantity,
                'weight' => $request->weight,
                'price' => $price ?? $request->price,
                'sale_price' => $sale_price ?? $request->price,
                'discount' => $discount ?? $request->discount,
                'sku' => $request->sku,
                'is_external' => $request->is_external,
                'external_url' => $request->external_url,
                'external_button_text' => $request->external_button_text,
                'is_featured' => $request->is_featured,
                'shipping_days' => $request->shipping_days,
                'is_free_shipping' => $request->is_free_shipping,
                'is_sale_enable' => $request->is_sale_enable,
                'sale_starts_at' => $request->sale_starts_at,
                'sale_expired_at' => $request->sale_expired_at,
                'is_trending' => $request->is_trending,
                'stock_status' => $stock_status ?? $request->stock_status,
                'meta_title' => $request->meta_title,
                'is_return' => $request->is_return,
                'meta_description' => $request->meta_description,
                'is_random_related_products' => $request->is_random_related_products,
                'product_meta_image_id' => $request->product_meta_image_id,
                'product_thumbnail_id'  => $request->product_thumbnail_id,
                'size_chart_image_id' => $request->size_chart_image_id,
                'estimated_delivery_text' => $request->estimated_delivery_text,
                'return_policy_text' => $request->return_policy_text,
                'safe_checkout' => $request->safe_checkout,
                'secure_checkout' => $request->secure_checkout,
                'social_share' => $request->social_share,
                'encourage_order' => $request->encourage_order,
                'encourage_view' => $request->encourage_view,
                'tax_id' => $request->tax_id,
                'status' => $request->status,
                'is_approved' => $isAutoApprove ?? true,
                'store_id' => $request->store_id,
            ]);

            $this->relationProductModels($request, $product);
            if (isset($request['variations']) && !empty($request['variations']) && $request->type == 'classified'){
                foreach ($request['variations'] as $variation) {
                    $this->createProductVariation($product, $variation);
                }

                $product->variations;
            }

            DB::commit();
            return $product;

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function update($request, $id)
    {
        DB::beginTransaction();
        try {

            if (isset($request['variations']) && !empty($request['variations']) && $request['type'] == 'classified') {
                $request['price'] = min(array_column($request['variations'], 'price'));
                $minPriceVariation = $this->getMinPriceVariation($request,  $request['price']);
                $request['discount']  = $minPriceVariation['discount'];
                $request['quantity'] = max(array_column($request['variations'], 'quantity'));
            }

            if (isset($request['quantity'])) {
                $request['stock_status'] = StockStatus::OUT_OF_STOCK;
                if ($request['quantity'] > 0) {
                    $request['stock_status'] = StockStatus::IN_STOCK;
                }
            }

            if (isset($request['discount'])) {
                $request['sale_price'] = round($request['price'] - (($request['price'] * $request['discount'])/100),2);
            }

            $product = $this->model->findOrFail($id);
            $product->update($request);

            if (isset($request['product_thumbnail_id'])) {
                $product->product_thumbnail()->associate($request['product_thumbnail_id']);
                $product->product_thumbnail;
            }

            if (isset($request['product_meta_image_id'])) {
                $product->product_meta_image()->associate($request['product_meta_image_id']);
                $product->product_meta_image;
            }

            if (isset($request['product_galleries_id'])) {
                $product->product_galleries()->sync($request['product_galleries_id']);
                $product->product_galleries;
            }

            if (isset($request['categories'])){
                $product->categories()->sync($request['categories']);
                $product->categories;
            }

            if (isset($request['tags'])){
                $product->tags()->sync($request['tags']);
                $product->tags;
            }

            if (isset($request['attributes_ids'])){
                $product->attributes()->sync($request['attributes_ids']);
                $product->attributes;
            }

            if (isset($request['related_products'])) {
                $product->similar_products()->sync($request['related_products']);
                $product->similar_products;
            }

            if (isset($request['cross_sell_products'])) {
                $product->cross_products()->sync($request['cross_sell_products']);
                $product->cross_products;
            }

            if ($request['is_random_related_products']) {
                $rand_category_id = $request['categories'][array_rand($request['categories'])];
                $request['related_products'] = Helpers::getRelatedProductId($product, $rand_category_id, $product->id);
                $product->similar_products()->sync($request['related_products']);
            }

            if (isset($request['variations']) && !empty($request['variations']) && $request['type'] == 'classified') {
                foreach ($request['variations'] as $variation) {
                    $variation['sale_price'] = $variation['price'];
                    if (isset($variation['discount'])) {
                        $variation['sale_price'] = round($variation['price'] - (($variation['price'] * $variation['discount'])/100),2);
                    }

                    if (isset($variation['quantity'])) {
                        $variation['stock_status'] = StockStatus::OUT_OF_STOCK;
                        if ($variation['quantity'] > 0) {
                            $variation['stock_status'] = StockStatus::IN_STOCK;
                        }
                    }

                    if (empty($variation['id']) && isset($variation['name'])) {
                        $variationData = $product->variations()->create($variation);
                        $variationsIds[] = $variationData->id;
                        $variationData->attribute_values()->attach($variation['attribute_values']);

                    } else if(isset($variation['id']) && isset($variation['attribute_values'])) {
                        $variations = $this->variations->findOrFail($variation['id']);
                        $variationsIds[] = $variation['id'];
                        $variations->update($variation);
                        $variations->attribute_values()->sync($variation['attribute_values']);
                    }
                }

                $product->variations()->whereNotIn('id', $variationsIds)->delete();
                $product->variations;
            }

            $product->tax;
            DB::commit();

            $product = $product->fresh();

            return $product;

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function destroy($id)
    {
        try {

            return $this->model->findOrFail($id)->destroy($id);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function status($id, $status)
    {
        try {

            $product = $this->model->with(config('enums.product.with'))
                ->findOrFail($id)
                ->makeVisible(config('enums.product.visible'))
                ->setAppends(config('enums.product.appends'));

            $product->update(['status' => $status]);
            return $product;

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function approve($id, $approve)
    {
        try {

            $product = $this->model->with(config('enums.product.with'))
                ->findOrFail($id)
                ->makeVisible(config('enums.product.visible'))
                ->setAppends(config('enums.product.appends'));

            $product->update(['is_approved' => $approve]);
            $product->total_in_approved_products = $this->model->where('is_approved', false)->count();

            return $product;

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function deleteAll($ids)
    {
        try {

            return $this->model->whereIn('id', $ids)->delete();

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function import()
    {
        DB::beginTransaction();
        try {

            $productImport = new ProductImport();
            Excel::import($productImport, request()->file('products'));
            DB::commit();

            return $productImport->getImportedProducts();

        } catch (Exception $e){

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function getProductsExportUrl()
    {
        try {

            return route('products.export');

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function export()
    {
        try {

            return Excel::download(new ProductsExport, 'products.csv');

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function getRelicateProductName($name)
    {
        $i = 1;
        do {

            $name = $name.str_repeat(' (COPY)', $i++);

        } while ($this->model->where('name', $name)->exists());

        return $name;
    }

    public function getVariationSKU($sku)
    {
        $i = 1;
        do {

            $sku = $sku.str_repeat(' (COPY)', $i++);

        } while ($this->model->variations()->where("sku", $sku)->exists());

        return $sku;
    }

    public function relationProductModels($request, $product)
    {
        $related_product_ids = null;
        if (!is_null($request->related_products) && $request->is_random_related_products) {
            if (isset($request->categories) && is_array($request->categories)) {
                $rand_category_id = $request->categories[array_rand($request->categories)];
                $related_product_ids = Helpers::getRelatedProductId($this->model, $rand_category_id);
                $product->similar_products()->attach($related_product_ids);
                $product->related_products;
            }
        }

        if (isset($request->product_galleries_id)) {
            $product->product_galleries()->attach($request->product_galleries_id);
            $product->product_galleries;
        }

        if (isset($request->categories)) {
            $product->categories()->attach($request->categories);
            $product->categories;
        }

        if (isset($request->tags)) {
            $product->tags()->attach($request->tags);
            $product->tags;
        }

        if (isset($request->attributes_ids)) {
            $product->attributes()->attach($request->attributes_ids);
            $product->attributes;
        }

        if (!is_null($request->related_products) && !$request->is_random_related_products) {
            $product->similar_products()->attach($request->related_products ?? $related_product_ids);
            $product->related_products;
        }

        if (isset($request->cross_sell_products)) {
            $product->cross_products()->attach($request->cross_sell_products);
            $product->cross_products;
        }
    }

    public function createProductVariation($product, $variation)
    {
        if (isset($variation['attribute_values'])) {
            $variation['sale_price'] = $variation['price'];
            if (isset($variation['discount'])) {
                $variation['sale_price'] = round($variation['price'] - (($variation['price'] * $variation['discount'])/100),2);
            }

            if (isset($variation['quantity'])) {
                $variation['stock_status'] = StockStatus::OUT_OF_STOCK;
                if ($variation['quantity'] > 0) {
                    $variation['stock_status'] = StockStatus::IN_STOCK;
                }
            }

            $variationData = $product->variations()->create([
                'name' => $variation['name'],
                'price' => $variation['price'],
                'quantity' => $variation['quantity'],
                'sku'  =>  $this->getVariationSKU($variation['sku']),
                'sale_price' => $variation['sale_price'],
                'discount' => $variation['discount'] ?? null,
                'stock_status' => $variation['stock_status'],
                'variation_image_id' => $variation['variation_image_id'] ?? null,
                'status' => $variation['status'],
                'product_id' => $product['id']
            ]);

            $variationData->attribute_values()->attach($variation['attribute_values']);
        }
    }

    public function replicate($ids)
    {
        DB::beginTransaction();
        try {

            foreach($ids as $id) {
                $product = $this->model->findOrFail($id);
                $clone = $product->replicate(['orders_count', 'reviews_count']);
                $clone->name = $this->getRelicateProductName($clone->name);
                $clone->created_at = Carbon::now();
                $clone->save();

                $this->relationProductModels($product, $clone);
                if (isset($product->variations) && $product->type == 'classified'){
                    foreach ($product->variations as $variation) {
                        $this->createProductVariation($clone, $variation);
                    }

                    $clone->variations;
                }

                $products[] = $clone->fresh();
            }

            DB::commit();
            return $products;

        } catch (Exception $e){

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function getProductBySlug($slug)
    {
        try {

            return $this->model->where('slug',$slug)
            ->with(config('enums.product.with'))
            ->firstOrFail()
            ->setAppends(config('enums.product.appends'))
            ->makeVisible(config('enums.product.visible'));

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }
}
