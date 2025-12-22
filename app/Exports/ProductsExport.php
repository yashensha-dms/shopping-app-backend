<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class ProductsExport implements FromCollection, WithMapping,  WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Product::whereNull('deleted_at')->get();
    }

    public function columns(): array
    {
        return [
            "id",
            "name",
            "short_description",
            "description",
            "type",
            "unit",
            "quantity",
            "weight",
            "price",
            "sale_price",
            "discount",
            "sku",
            "stock_status",
            "meta_title",
            "meta_description",
            "store_id",
            "is_free_shipping",
            "is_featured",
            "is_return",
            "is_trending",
            "is_sale_enable",
            "is_random_related_products",
            "is_external",
            "external_url",
            "external_button_text",
            "shipping_days",
            "sale_starts_at",
            "sale_expired_at",
            "show_stock_quantity",
            "estimated_delivery_text",
            "return_policy_text",
            "safe_checkout",
            "secure_checkout",
            "social_share",
            "encourage_order",
            "encourage_view",
            "is_approved",
            "created_at",
            "updated_at",
            "deleted_at",
            "status",
            "product_thumbnail_url",
            "product_meta_image_url",
            "size_chart_image_url",
            "product_galleries_url",
            "attributes",
            "categories",
            "tags",
            "variations"
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->short_description,
            $product->description,
            $product->type,
            $product->unit,
            $product->quantity,
            $product->weight,
            $product->price,
            $product->sale_price,
            $product->discount,
            $product->sku,
            $product->stock_status,
            $product->meta_title,
            $product->meta_description,
            $product->store_id,
            $product->is_free_shipping,
            $product->is_featured,
            $product->is_return,
            $product->is_trending,
            $product->is_sale_enable,
            $product->is_random_related_products,
            $product->is_external,
            $product->external_url,
            $product->external_button_text,
            $product->shipping_days,
            $product->sale_starts_at,
            $product->sale_expired_at,
            $product->show_stock_quantity,
            $product->estimated_delivery_text,
            $product->return_policy_text,
            $product->safe_checkout,
            $product->secure_checkout,
            $product->social_share,
            $product->encourage_order,
            $product->encourage_view,
            $product->is_approved,
            $product->created_at,
            $product->updated_at,
            $product->deleted_at,
            $product->status,
            $product->product_thumbnail?->original_url,
            $product->product_meta_image?->original_url,
            $product->size_chart_image?->original_url,
            $product->product_galleries->pluck('original_url')->implode(','),
            $product->attributes->pluck('id')->implode(','),
            $product->categories->pluck('id')->implode(','),
            $product->tags->pluck('id')->implode(','),
            $product->variations,
        ];
    }

    public function headings(): array
    {
        return $this->columns();
    }
}
