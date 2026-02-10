<?php

namespace App\Exports;

use App\Models\Attribute;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class AttributesExport implements FromCollection, WithMapping, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Attribute::with('attribute_values')->whereNull('deleted_at')->get();
    }

    public function columns(): array
    {
        return ["id","name", "values", "slug", "style", "status", "created_at"];
    }

    public function map($attribute): array
    {
        return [
            $attribute->id,
            $attribute->name,
            $attribute->attribute_values->pluck('value')->implode(','),
            $attribute->slug,
            $attribute->style,
            $attribute->status,
            $attribute->created_at,
        ];
    }

    public function headings(): array
    {
        return $this->columns();
    }
}
