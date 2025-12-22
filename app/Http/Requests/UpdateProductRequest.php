<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use Illuminate\Contracts\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = $this->route('product') ? $this->route('product')->id : $this->id;
        if (isset($this->related_products)) {
            foreach ($this->related_products as $related_product) {
                if ($id == $related_product) {
                    throw new ExceptionHandler("Can't insert same Product in Related Products", 400);
                }
            }
        }

        if (isset($this->cross_sell_products)) {
            foreach ($this->cross_sell_products as $cross_sell_product) {
                if ($id == $cross_sell_product) {
                    throw new ExceptionHandler("Can't insert same Product in Cross Sell Products", 400);
                }
            }
        }

        $rules = [
            'name'  => ['required', 'string', 'max:255'],
            'store_id' => ['nullable','exists:stores,id,deleted_at,NULL'],
            'type' => ['required','in:simple,classified'],
            'discount' => ['nullable','numeric','regex:/^([0-9]{1,2}){1}(\.[0-9]{1,2})?$/'],
            'stock_status' => ['required_if:type,==,simple', 'in:in_stock,out_of_stock'],
            'sku' => ['required_if:type,==,simple', 'unique:products,sku,'.$id.',id,deleted_at,NULL'],
            'is_external' => ['min:0', 'max:1'],
            'external_url' => ['required_if:is_external,==,1', 'nullable'],
            'external_button_text' => ['required_if:type,==,external', 'nullable'],
            'price' => ['required_if:type,==,simple'],
            'quantity' => ['required_if:type,==,simple'],
            'tax_id' => ['nullable','exists:taxes,id,deleted_at,NULL'],
            'show_stock_quantity' => ['min:0', 'max:1'],
            'is_featured' => ['min:0', 'max:1'],
            'is_cod' => ['min:0', 'max:1'],
            'is_return' => ['min:0', 'max:1'],
            'is_free_shipping' => ['min:0', 'max:1'],
            'is_changeable' => ['min:0', 'max:1'],
            'sale_starts_at' => ['nullable', 'date'],
            'sale_expired_at' => ['nullable','date', 'after:sale_starts_at'],
            'categories'=>['exists:categories,id,deleted_at,NULL'],
            'tags' => ['exists:tags,id,deleted_at,NULL'],
            'product_meta_image_id' =>['nullable','exists:attachments,id,deleted_at,NULL'],
            'product_thumbnail_id' => ['nullable','exists:attachments,id,deleted_at,NULL'],
            'product_galleries_id.*' => ['nullable','exists:attachments,id,deleted_at,NULL'],
            'attributes_ids' => ['nullable','required_if:type,==,classified','exists:attributes,id,deleted_at,NULL'],
            'is_random_related_products' => ['min:0', 'max:1'],
            'related_products' => ['nullable','exists:products,id,deleted_at,NULL'],
            'cross_sell_products' => ['nullable', 'exists:products,id,deleted_at,NULL'],
            'visible_time' => ['nullable','date'],
            'variations.*.id' => ['nullable','exists:variations,id,deleted_at,NULL'],
            'variations.*.name' => ['nullable','required_if:type,==,classified','string'],
            'variations.*.price' => ['nullable','required_if:type,==,classified','numeric'],
            'variations.*.sale_price' => ['nullable','min:'. (float)$this->input('variations.*.price')],
            'variations.*.stock_status' => ['nullable','required_if:type,==,classified', 'in:in_stock,out_of_stock,coming_soon'],
            'variations.*.discount' => ['nullable', 'numeric', 'regex:/^([0-9]{1,2}){1}(\.[0-9]{1,2})?$/'],
            'variations.*.attribute_values' => ['nullable','required_if:type,==,classified','exists:attribute_values,id'],
            'variations.*.variation_image_id' => ['nullable','exists:attachments,id,deleted_at,NULL'],
            'variations.*.status' => ['required_if:type,==,classified','min:0','max:1'],
        ];

        if (!empty($this->input('variations'))) {
            return $this->withUniqueVariationSkuRule($rules, $this->input('variations', []));
        }

        return $rules;
    }

    public function withUniqueVariationSkuRule($rules, $variations)
    {
        foreach ($variations as $key => $variation) {
            $rules['variations.'.$key.'.sku'] = ['nullable','required_if:type,==,classified', 'string', 'unique:variations,sku,NULL,id,deleted_at,NULL'];
            if (!empty($variation['id'])) {
                $rules['variations.'.$key.'.sku'] = ['nullable','required_if:type,==,classified', 'string', 'unique:variations,sku,'.$variation['id'].',id,deleted_at,NULL'];
            }
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'discount.regex' => 'Enter discount between 0 to 99.99',
            'video_provider.in' => 'Video Provider can in youtube or vimeo or daily_motion',
            'type.in' => 'Product type can be either simple or classified or external',
            'variations.*.stock_status.in' => 'Variations Stock status can be either in_stock or out_of_stock or coming_soon',
            'stock_status.in' => 'Stock status can be either in_stock or out_of_stock',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new ExceptionHandler($validator->errors()->first(), 422);
    }
}
