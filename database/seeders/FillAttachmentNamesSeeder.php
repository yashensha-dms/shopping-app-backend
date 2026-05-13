<?php
namespace Database\Seeders;

use App\Models\Attachment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FillAttachmentNamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $attachments = Attachment::whereNull('name')
            ->orWhere('name', '')
            ->get();

        foreach ($attachments as $attachment) {
            $name = pathinfo($attachment->file_name, PATHINFO_FILENAME);
            $attachment->update([
                'name' => Str::title(str_replace(['-', '_'], ' ', $name))
            ]);
        }
    }
}
