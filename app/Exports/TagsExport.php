<?php

namespace App\Exports;

use App\Models\Tag;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class TagsExport implements FromCollection, WithMapping, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Tag::where('type', 'product')->whereNull('deleted_at')->get();
    }

    public function columns(): array
    {
        return ["id","name", "description", "type", "slug","status", "created_at"];
    }

    public function map($tag): array
    {
        return [
            $tag->id,
            $tag->name,
            $tag->description,
            $tag->type,
            $tag->slug,
            $tag->status,
            $tag->created_at,
        ];
    }

    public function headings(): array
    {
        return $this->columns();
    }
}
