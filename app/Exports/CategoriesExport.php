<?php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromCollection;

class CategoriesExport implements FromCollection, WithMapping, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Category::where('type', 'product')->whereNull('deleted_at')->get();
    }

    public function columns(): array
    {
        return ["id","name", "description", "slug", "type", "parent_id", "commission_rate", "status", "category_image_url", "category_icon_url", "created_at"];
    }

    public function map($category): array
    {
        return [
            $category->id,
            $category->name,
            $category->description,
            $category->slug,
            $category->type,
            $category->parent_id,
            $category->commission_rate,
            $category->status,
            $category->category_image?->original_url,
            $category->category_icon?->original_url,
            $category->created_at
        ];
    }

    public function headings(): array
    {
        return $this->columns();
    }
}
