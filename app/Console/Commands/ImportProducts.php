<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-products
                            {--seeds-path= : Path to the SQL seed files directory (default: seeds/)}
                            {--force : Skip confirmation prompt}
                            {--dry-run : Parse and count records without inserting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products from SQL seed files and create categories from filenames';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $seedsPath = $this->option('seeds-path')
            ? rtrim($this->option('seeds-path'), DIRECTORY_SEPARATOR)
            : base_path('seeds');

        if (! is_dir($seedsPath)) {
            $this->error("Seeds directory not found: {$seedsPath}");
            return self::FAILURE;
        }

        $sqlFiles = glob($seedsPath . DIRECTORY_SEPARATOR . '*_import.sql');

        if (empty($sqlFiles)) {
            $this->warn("No *_import.sql files found in: {$seedsPath}");
            return self::SUCCESS;
        }

        $this->info('Found ' . count($sqlFiles) . ' SQL seed file(s):');
        foreach ($sqlFiles as $file) {
            $categoryName = $this->categoryNameFromFile($file);
            $count        = $this->countInserts(file_get_contents($file));
            $this->line("  • <comment>{$categoryName}</comment> → {$count} product(s)  [{$file}]");
        }

        if ($this->option('dry-run')) {
            $this->warn('[DRY RUN] No data was written to the database.');
            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            if (! $this->confirm('Proceed with import? Existing products with the same IDs will be skipped.')) {
                return self::SUCCESS;
            }
        }

        $bar = $this->output->createProgressBar(count($sqlFiles));
        $bar->start();

        $totalProducts  = 0;
        $totalCategories = 0;

        foreach ($sqlFiles as $file) {
            $categoryName = $this->categoryNameFromFile($file);
            $sql          = file_get_contents($file);

            DB::beginTransaction();
            try {
                // 1. Upsert the category (insert or get existing by name)
                $category = $this->ensureCategory($categoryName);
                $isNew    = $category['_is_new'];
                unset($category['_is_new']);

                if ($isNew) {
                    $totalCategories++;
                }

                // 2. Parse INSERT statements and re-insert without hardcoded IDs
                $productIds = $this->importProducts($sql);
                $imported   = count($productIds);
                $totalProducts += $imported;

                // 3. Link all imported products to the category
                if (! empty($productIds)) {
                    $this->linkProductsToCategory($productIds, $category['id']);
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->newLine();
                $this->error("Failed on file [{$file}]: " . $e->getMessage());
                return self::FAILURE;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✔ Import complete.");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Files processed',       count($sqlFiles)],
                ['Categories created',    $totalCategories],
                ['Products imported',     $totalProducts],
            ]
        );

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Derive a human-readable, title-cased category name from the SQL filename.
     *
     * File example:  BABY CARE_import.sql            → Baby Care
     * File example:  BABY_CARE_import.sql            → Baby Care
     * File example:  GROCERY & COOKING_import.sql    → Grocery & Cooking
     */
    protected function categoryNameFromFile(string $filePath): string
    {
        $basename = pathinfo($filePath, PATHINFO_FILENAME);     // e.g. "BABY CARE_import"
        $basename = preg_replace('/_import$/i', '', $basename); // strip _import suffix
        $basename = str_replace('_', ' ', $basename);           // underscores → spaces
        return Str::title(strtolower($basename));               // proper Title Case
    }

    /**
     * Count the number of INSERT INTO products… lines in an SQL file.
     */
    protected function countInserts(string $sql): int
    {
        preg_match_all('/^\s*INSERT\s+INTO\s+`?products`?/im', $sql, $matches);
        return count($matches[0]);
    }

    /**
     * Find or create a category by name, returning its record array.
     * Adds a synthetic `_is_new` flag (removed before use).
     */
    protected function ensureCategory(string $name): array
    {
        $slug     = Str::slug($name);
        $existing = DB::table('categories')
            ->whereNull('deleted_at')
            ->where('name', $name)
            ->first();

        if ($existing) {
            return array_merge((array) $existing, ['_is_new' => false]);
        }

        // Ensure slug is unique
        $slug = $this->uniqueSlug('categories', $slug);

        $id = DB::table('categories')->insertGetId([
            'name'       => $name,
            'slug'       => $slug,
            'status'     => 1,
            'type'       => 'product',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = (array) DB::table('categories')->find($id);
        return array_merge($record, ['_is_new' => true]);
    }

    /**
     * Parse all INSERT INTO products (…) VALUES (…) statements from the SQL,
     * strip the hardcoded `id` value so MySQL auto-increments, and insert each
     * row. Returns the array of newly-inserted product IDs.
     */
    protected function importProducts(string $sql): array
    {
        // Split by lines; each line is one INSERT statement
        $lines = array_filter(
            array_map('trim', explode("\n", $sql)),
            fn(string $line) => stripos($line, 'INSERT INTO') !== false
        );

        if (empty($lines)) {
            return [];
        }

        $insertedIds = [];

        foreach ($lines as $line) {
            // Parse column list and values from the INSERT statement
            if (! preg_match(
                '/INSERT\s+INTO\s+`?products`?\s*\(([^)]+)\)\s*VALUES\s*\((.+)\);?\s*$/is',
                $line,
                $m
            )) {
                continue;
            }

            $columns = array_map('trim', explode(',', $m[1]));
            $values  = $this->splitValues($m[2]);

            if (count($columns) !== count($values)) {
                $this->warn('Column/value count mismatch — skipping row.');
                continue;
            }

            $row = array_combine($columns, $values);

            // Remove the hardcoded id so the DB auto-assigns a new one
            unset($row['id']);

            // Cast SQL NULLs
            $row = array_map(fn($v) => ($v === 'NULL' ? null : $v), $row);

            // Ensure timestamps exist
            $now = now()->toDateTimeString();
            $row['created_at'] = $row['created_at'] ?? $now;
            $row['updated_at'] = $now;

            // Make slug unique (the SQL slugs may collide if re-running)
            if (isset($row['slug'])) {
                $row['slug'] = $this->uniqueSlug(
                    'products',
                    trim($row['slug'], "'\"")
                );
            }

            // Strip surrounding quotes from string values
            $row = array_map(function ($v) {
                if ($v !== null && preg_match("/^'(.*)'$/s", $v, $qm)) {
                    return stripslashes($qm[1]);
                }
                return $v;
            }, $row);

            try {
                $newId = DB::table('products')->insertGetId($row);
                $insertedIds[] = $newId;
            } catch (\Throwable $e) {
                // Skip rows that violate constraints (e.g. duplicate slug)
                $this->warn('  Skipped a row: ' . $e->getMessage());
            }
        }

        return $insertedIds;
    }

    /**
     * Insert pivot rows to link products to a category.
     */
    protected function linkProductsToCategory(array $productIds, int $categoryId): void
    {
        $pivotRows = array_map(
            fn(int $pid) => ['product_id' => $pid, 'category_id' => $categoryId],
            $productIds
        );

        foreach (array_chunk($pivotRows, 500) as $chunk) {
            DB::table('product_categories')->insertOrIgnore($chunk);
        }
    }

    /**
     * Generate a unique slug for a given table by appending a numeric suffix
     * when a collision is detected.
     */
    protected function uniqueSlug(string $table, string $slug): string
    {
        $original = $slug;
        $i        = 1;

        while (DB::table($table)->where('slug', $slug)->whereNull('deleted_at')->exists()) {
            $slug = $original . '-' . $i++;
        }

        return $slug;
    }

    /**
     * Split a SQL VALUES string into individual values, respecting quoted strings
     * that may contain commas.
     */
    protected function splitValues(string $valuesString): array
    {
        $values  = [];
        $current = '';
        $inQuote = false;
        $len     = strlen($valuesString);

        for ($i = 0; $i < $len; $i++) {
            $char = $valuesString[$i];

            if ($char === "'" && ($i === 0 || $valuesString[$i - 1] !== '\\')) {
                $inQuote  = ! $inQuote;
                $current .= $char;
            } elseif ($char === ',' && ! $inQuote) {
                $values[] = trim($current);
                $current  = '';
            } else {
                $current .= $char;
            }
        }

        if ($current !== '') {
            $values[] = trim($current);
        }

        return $values;
    }
}
