<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use Illuminate\Contracts\Validation\Validator;

class CalculateCheckoutRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'consumer_id' => ['exists:users,id'],
            'products' => ['required','array'],
            'products.*.product_id' => ['required','exists:products,id,deleted_at,NULL'],
            'products.*.variation_id' => ['nullable','exists:variations,id,deleted_at,NULL'],
            'coupon' => ['nullable','exists:coupons,code,deleted_at,NULL'],
            'payment_method' => ['required'],
            'billing_address_id' => ['required', 'exists:addresses,id,deleted_at,NULL'],
            'shipping_address_id'=>['required', 'exists:addresses,id,deleted_at,NULL'],
        ];
    }

    public function messages()
    {
        return [
            'coupon.exists' => "We could not find an {$this->coupon} coupon",
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new ExceptionHandler($validator->errors()->first(), 422);
    }
}
