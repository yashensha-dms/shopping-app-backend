<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use Illuminate\Contracts\Validation\Validator;

class UpdateCategoryRequest extends FormRequest
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
        $category = $this->route('category');
        $id = $category instanceof \App\Models\Category ? $category->id : $category;
        $id = $id ?: $this->id;
        file_put_contents(base_path('debug.txt'), print_r([
            'route_category' => is_object($category) ? get_class($category) . '#' . $category->id : $category,
            'request_id' => $this->id,
            'resolved_id' => $id,
            'request_type' => $this->type,
            'request_all' => $this->all(),
        ], true) . "\n", FILE_APPEND);
        if ($id == $this->parent_id) {
            throw new ExceptionHandler("Can't insert same category for parent", 400);
        }

        return [
            'name'  => ['max:255', Rule::unique('categories')->where('type', $this->type)->whereNull('deleted_at')->ignore($id)],
            'description' => ['nullable','string'],
            'parent_id' => ['nullable','exists:categories,id,deleted_at,NULL'],
            'commission_rate' => ['nullable', 'regex:/^([0-9]{1,2}){1}(\.[0-9]{1,2})?$/'],
            'category_image_id' => ['nullable','exists:attachments,id'],
            'category_icon_id' => ['nullable','exists:attachments,id'],
            'type' => ['in:post,product']
        ];
    }

    public function messages()
    {
        return [
            'commission_rate.regex' => 'Enter commission rate percentage between 0 to 99.99',
            'type.in' => 'Category type can be either post or product',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new ExceptionHandler($validator->errors()->first(), 422);
    }
}
