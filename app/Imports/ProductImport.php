<?php

namespace App\Imports;

use Exception;
use App\Enums\RoleEnum;
use App\Models\Product;
use App\Helpers\Helpers;
use App\Models\Variation;
use App\Enums\StockStatus;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use App\GraphQL\Exceptions\ExceptionHandler;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError
{
    private $products = [];

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'short_description' => ['required'],
            'type' => ['required','in:simple,classified'],
            'price' => ['required_if:type,==,simple'],
            'stock_status' => ['required_if:type,==,simple', 'in:in_stock,out_of_stock'],
            'quantity' => ['numeric','required_if:type,==,simple'],
            'sku' => ['required_if:type,==,simple', 'unique:products,sku,NULL,id,deleted_at,NULL'],
            'discount' => ['nullable','numeric','regex:/^([0-9]{1,2}){1}(\.[0-9]{1,2})?$/'],
            'show_stock_quantity' => ['min:0', 'max:1'],
            'is_featured' => ['min:0', 'max:1'],
            'secure_checkout' => ['min:0', 'max:1'],
            'safe_checkout' => ['min:0', 'max:1'],
            'social_share' => ['min:0', 'max:1'],
            'encourage_order' => ['min:0', 'max:1'],
            'encourage_view' => ['min:0', 'max:1'],
            'is_cod' => ['min:0', 'max:1'],
            'is_return' => ['min:0', 'max:1'],
            'is_free_shipping' => ['min:0', 'max:1'],
            'is_changeable' => ['min:0', 'max:1'],
            'is_sale_enable' => ['min:0', 'max:1'],
            'sale_starts_at' => ['nullable', 'date'],
            'store_id' => ['nullable','exists:stores,id,deleted_at,NULL'],
            'sale_expired_at' => ['nullable','date', 'after:sale_starts_at'],
            'status' => ['required','min:0','max:1'],
            'visible_time' => ['nullable','date'],
            'variations' => ['required_if:type,==,classified'],
        ];
    }

    public function customValidationMessages()
    {
        return [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name must not exceed 255 characters.',
            'name.unique' => 'The name has already been taken.',

            'description.required' => 'The description field is required.',
            'description.string' => 'The description must be a string.',
            'description.min' => 'The description must be at least 10 characters.',

            'short_description.required' => 'The short description field is required.',

            'type.required' => 'The type field is required.',
            'type.in' => 'The product type can be either simple or classified.',

            'price.required_if' => 'The price field is required for a simple type.',
            'price.numeric' => 'The price must be a number.',

            'stock_status.required_if' => 'The stock status field is required for a simple type.',
            'stock_status.in' => 'The stock status must be either in_stock or out_of_stock.',

            'quantity.numeric' => 'The quantity must be a number.',
            'quantity.required_if' => 'The quantity field is required for a simple type.',

            'sku.required_if' => 'The SKU field is required for a simple type.',
            'sku.unique' => 'The SKU has already been taken.',

            'discount.numeric' => 'The discount must be a number.',
            'discount.regex' => 'The discount must be in the correct format.',

            'show_stock_quantity.min' => 'The show stock quantity must be 0 or 1.',
            'show_stock_quantity.max' => 'The show stock quantity must be 0 or 1.',

            'is_featured.min' => 'The is featured field must be 0 or 1.',
            'is_featured.max' => 'The is featured field must be 0 or 1.',

            'secure_checkout.min' => 'The secure checkout field must be 0 or 1.',
            'secure_checkout.max' => 'The secure checkout field must be 0 or 1.',

            'safe_checkout.min' => 'The safe checkout field must be 0 or 1.',
            'safe_checkout.max' => 'The safe checkout field must be 0 or 1.',

            'social_share.min' => 'The social share field must be 0 or 1.',
            'social_share.max' => 'The social share field must be 0 or 1.',

            'encourage_order.min' => 'The encourage order field must be 0 or 1.',
            'encourage_order.max' => 'The encourage order field must be 0 or 1.',

            'encourage_view.min' => 'The encourage view field must be 0 or 1.',
            'encourage_view.max' => 'The encourage view field must be 0 or 1.',

            'is_cod.min' => 'The is COD field must be 0 or 1.',
            'is_cod.max' => 'The is COD field must be 0 or 1.',

            'is_return.min' => 'The is return field must be 0 or 1.',
            'is_return.max' => 'The is return field must be 0 or 1.',

            'is_free_shipping.min' => 'The is free shipping field must be 0 or 1.',
            'is_free_shipping.max' => 'The is free shipping field must be 0 or 1.',

            'is_changeable.min' => 'The is changeable field must be 0 or 1.',
            'is_changeable.max' => 'The is changeable field must be 0 or 1.',

            'is_sale_enable.min' => 'The is sale enable field must be 0 or 1.',
            'is_sale_enable.max' => 'The is sale enable field must be 0 or 1.',

            'sale_starts_at.date' => 'The sale starts at must be a valid date.',

            'store_id.exists' => 'The selected store is invalid.',

            'sale_expired_at.date' => 'The sale expired at must be a valid date.',
            'sale_expired_at.after' => 'The sale expired date must be after the sale start date.',

            'status.required' => 'The status field is required.',
            'status.min' => 'The status field must be either 0 or 1.',
            'status.max' => 'The status field must be either 0 or 1.',

            'visible_time.date' => 'The visible time must be a valid date.',

            'variations.required_if' => 'Variations are required for a classified type.',
        ];
    }

    /**
     * @param \Throwable $e
     */
    public function onError(\Throwable $e)
    {
        throw new ExceptionHandler($e->getMessage() , 422);
    }

    public function getImportedProducts()
    {
        return $this->products;
    }

    public function getMinPriceVariation($request, $price)
    {
        return head(array_filter(json_decode($request['variations']), function ($variation) use ($price) {
            return $variation->price == $price;
        }));
    }

    public function model(array $row)
    {
        DB::beginTransaction();
        try {
            $store_id = null;
            $roleName = Helpers::getCurrentRoleName();
            if ($roleName != RoleEnum::ADMIN) {
                $settings = Helpers::getSettings();
                if ($roleName == RoleEnum::VENDOR) {
                    if (!Helpers::isMultiVendorEnable()) {
                        throw new Exception('The multi-vendor feature is currently deactivated.', 403);
                    }

                    $store_id = Helpers::getCurrentVendorStoreId();
                }

                $isAutoApprove = $settings['activation']['product_auto_approve'];
            }

            if(isset($row['variations']) && !empty($row['variations']) && $row['type'] == 'classified') {
                $price = min(array_column(json_decode($row['variations']), 'price'));
                $minPriceVariation = $this->getMinPriceVariation($row, $price);
                $discount = $minPriceVariation->discount;
                $sale_price = round($price  - (($price  * $discount)/100), 2);
                $quantity = max(array_column(json_decode($row['variations']), 'quantity'));
                $stock_status = StockStatus::OUT_OF_STOCK;

                if ($quantity > 0) {
                    $stock_status = StockStatus::IN_STOCK;
                }
            }

            if (isset($row['quantity']) && !is_null($row['quantity'])) {
                $stock_status = StockStatus::OUT_OF_STOCK;
                if ($row['quantity'] > 0) {
                    $stock_status = StockStatus::IN_STOCK;
                }
            }

            if (isset($row['discount']) && !is_null($row['discount'])) {
                $mrpPrice = $row['price'] ?? $price;
                $sale_price = round($mrpPrice - (($mrpPrice * $row['discount'])/100), 2);
            }

            $product = new Product([
                'name' => $row['name'],
                'short_description' => $row['short_description'],
                'description' => $row['description'],
                'type' => $row['type'],
                'unit' => $row['unit'],
                'quantity' => $row['quantity'] ?? $quantity,
                'weight' => $row['weight'],
                'price' => $price ?? $row['price'],
                'sale_price' => $sale_price ?? $row['sale_price'],
                'discount' => $discount ?? $row['discount'],
                'sku' => $row['sku'],
                'stock_status' => $stock_status ?? $row['stock_status'],
                'meta_title' => $row['meta_title'],
                'meta_description' => $row['meta_description'],
                'store_id' => $store_id ?? $row['store_id'],
                'is_free_shipping' => $row['is_free_shipping'],
                'is_external' => $row['is_external'],
                'external_button_text' => $row['external_button_text'],
                'external_url'=> $row['external_url'],
                'is_featured' => $row['is_featured'],
                'is_return' => $row['is_return'],
                'is_trending' => $row['is_trending'],
                'is_sale_enable' => $row['is_sale_enable'],
                'is_random_related_products' => $row['is_random_related_products'],
                'sale_starts_at' => $row['sale_starts_at'],
                'sale_expired_at' => $row['sale_expired_at'],
                'shipping_days' => $row['shipping_days'],
                'show_stock_quantity' => $row['show_stock_quantity'],
                'estimated_delivery_text' => $row['estimated_delivery_text'],
                'return_policy_text' => $row['return_policy_text'],
                'safe_checkout' => $row['safe_checkout'],
                'secure_checkout' => $row['secure_checkout'],
                'social_share' => $row['social_share'],
                'encourage_order' => $row['encourage_order'],
                'encourage_view' => $row['encourage_view'],
                'is_approved' => $isAutoApprove ?? $row['is_approved'],
                'status' => $row['status'],
            ]);

            if (isset($row['product_thumbnail_url']) && !is_null($row['product_thumbnail_url'])) {
                $media = $product->addMediaFromUrl($row['product_thumbnail_url'])->toMediaCollection('attachment');
                $media->save();
                $product->product_thumbnail_id = $media->id;
            }

            if (isset($row['product_meta_image_url']) && !is_null($row['product_meta_image_url'])) {
                $media = $product->addMediaFromUrl($row['product_meta_image_url'])->toMediaCollection('attachment');
                $media->save();
                $product->product_meta_image_id = $media->id;
            }

            if (isset($row['size_chart_image_url']) && !is_null($row['size_chart_image_url'])) {
                $media = $product->addMediaFromUrl($row['size_chart_image_url'])->toMediaCollection('attachment');
                $media->save();
                $product->size_chart_image_id = $media->id;
            }

            $product->save();
            if (isset($row['product_galleries']) && !is_null($row['product_galleries'])) {
                $product_galleries_urls = explode(',', $row['product_galleries']);
                $product_galleries_ids = [];
                foreach ($product_galleries_urls as $product_galleries_url) {
                    $media = $product->addMediaFromUrl($product_galleries_url)->toMediaCollection('attachment');
                    $media->save();
                    $product_galleries_ids[] = $media->id;
                }

                $product->product_galleries()->attach($product_galleries_ids);
                $product->product_galleries;
            }

            if (isset($row['categories']) && !is_null($row['categories'])) {
                $product->categories()->attach(explode(',', $row['categories']));
                $product->categories;
            }

            if (isset($row['tags']) && !is_null($row['tags'])) {
                $product->tags()->attach(explode(',', $row['tags']));
                $product->tags;
            }

            if (isset($row['attributes']) && !is_null($row['attributes'])) {
                $product->attributes()->attach(explode(',', $row['attributes']));
                $product->attributes;
            }

            if (isset($row['variations']) && !is_null($row['variations']) && $row['type'] == 'classified'){
                foreach (json_decode($row['variations']) as $variation) {
                    $this->createProductVariation($product, $variation);
                }

                $product->variations;
            }

            $this->products[] = [
                'id' => $product->id,
                'name' => $product->name,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'type' => $product->type,
                'unit' => $product->unit,
                'quantity' => $product->quantity,
                'weight' => $product->weight,
                'price' => $product->price,
                'sale_price' =>$product->price,
                'discount' => $product->discount,
                'sku' => $product->sku,
                'is_featured' => $product->is_featured,
                'shipping_days' => $product->shipping_days,
                'is_free_shipping' => $product->is_free_shipping,
                'is_sale_enable' => $product->is_sale_enable,
                'sale_starts_at' => $product->sale_starts_at,
                'sale_expired_at' => $product->sale_expired_at,
                'is_trending' => $product->is_trending,
                'stock_status' => $product->stock_status,
                'meta_title' => $product->meta_title,
                'is_return' => $product->is_return,
                'is_external' =>  $product->is_external,
                'external_url' => $product->external_url,
                'external_button_text' => $product->external_button_text,
                'meta_description' => $product->meta_description,
                'is_random_related_products' => $product->is_random_related_products,
                'estimated_delivery_text' => $product->estimated_delivery_text,
                'return_policy_text' => $product->return_policy_text,
                'safe_checkout' => $product->safe_checkout,
                'secure_checkout' => $product->secure_checkout,
                'social_share' => $product->social_share,
                'encourage_order' => $product->encourage_order,
                'encourage_view' => $product->encourage_view,
                'is_approved' => $product->is_approved,
                'status' => $product->status,
                'product_thumbnail' => $product->product_thumbnail,
                'product_meta_image' => $product->product_meta_image,
                'product_galleries' =>  $product->product_galleries,
                'categories' => $product->categories,
                'attributes' => $product->attributes,
                'tags' => $product->tags,
                'variations' => $product->variations,
            ];

            DB::commit();
            return $product;

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function getVariationSKU($sku)
    {
        $i = 1;
        do {

            $sku = $sku.str_repeat(' (COPY)', $i++);

        } while (Variation::where('sku', $sku)->whereNull('deleted_at')->exists());

        return $sku;
    }


    public function createProductVariation($product, $variation)
    {

        if (isset($variation->attribute_values)) {
            $variation->sale_price = $variation->price;
            if (isset($variation->discount)) {
                $variation->sale_price = round($variation->price - (($variation->price * $variation->discount)/100),2);
            }

            if (isset($variation->quantity)) {
                $variation->stock_status = StockStatus::OUT_OF_STOCK;
                if ($variation->quantity > 0) {
                    $variation->stock_status = StockStatus::IN_STOCK;
                }
            }

            $variationData = $product->variations()->create([
                'name' => $variation->name,
                'price' => $variation->price,
                'quantity' => $variation->quantity,
                'sku'  =>  $this->getVariationSKU($variation->sku),
                'sale_price' => $variation->sale_price,
                'discount' => $variation->discount ?? null,
                'stock_status' => $variation->stock_status,
                'status' => $variation->status,
                'product_id' => $product->id
            ]);

            if (isset($variation->variation_image_url)) {
                $media = $variationData->addMediaFromUrl($variation->variation_image_url)->toMediaCollection('attachment');
                $media->save();
                $variationData->variation_image_id = $media->id;
                $variationData->save();
            }

            $variationData->attribute_values()->attach($variation->attribute_values);
        }
    }
}
