<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Material;

class UpdateMaterialTitles extends Command
{
    // The name and signature of the console command
    protected $signature = 'materials:update-titles';

    // The console command description
    protected $description = 'Update material titles by extracting from filename';

    public function handle()
    {
        $materials = Material::all();

        foreach ($materials as $material) {
            // Extract title from filename (remove extension)
            $title = pathinfo($material->filename, PATHINFO_FILENAME);

            $material->title = $title;
            $material->save();

            $this->info("Updated material ID {$material->id} with title '{$title}'");
        }

        $this->info('All material titles updated successfully.');

        return 0;
    }
}
