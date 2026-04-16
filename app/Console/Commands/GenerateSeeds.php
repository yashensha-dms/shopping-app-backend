<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GenerateSeeds extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:generate-seeds
                            {--seeds-path= : Path to the XLSX files directory (default: seeds/)}
                            {--dry-run    : Parse and show counts without writing any files}';

    /**
     * The console command description.
     */
    protected $description = 'Convert XLSX seed files into SQL _import.sql seed files for app:import-products';

    /**
     * Column mapping: XLSX header → DB column.
     * MRP  = max retail price  → price
     * CCP  = selling price     → sale_price
     */
    private array $columnMap = [
        'name'      => 'name',
        'code'      => 'barcode',
        'hsncode'   => 'hsn_code',
        'mrp'       => 'price',
        'ccp'       => 'sale_price',
        'total qty' => 'quantity',
    ];

    /**
     * Default values applied to every product row.
     */
    private array $defaults = [
        'type'                     => 'simple',
        'status'                   => 1,
        'is_approved'              => 1,
        'is_cod'                   => 1,
        'stock_status'             => 'in_stock',
        'is_featured'              => 0,
        'is_sale_enable'           => 0,
        'is_return'                => 0,
        'is_trending'              => 0,
        'is_external'              => 0,
        'is_free_shipping'         => 0,
        'is_random_related_products' => 0,
        'safe_checkout'            => 1,
        'secure_checkout'          => 1,
        'social_share'             => 1,
        'encourage_order'          => 1,
        'encourage_view'           => 1,
        'shipping_days'            => 0,
        'quantity'                 => 1,
        'created_by_id'            => 1,
    ];

    public function handle(): int
    {
        $seedsPath = rtrim(
            $this->option('seeds-path') ?? base_path('seeds'),
            DIRECTORY_SEPARATOR
        );

        if (! is_dir($seedsPath)) {
            $this->error("Seeds directory not found: {$seedsPath}");
            return self::FAILURE;
        }

        $xlsxFiles = glob($seedsPath . DIRECTORY_SEPARATOR . '*.xlsx');

        // Filter out temp files (Excel lock files start with ~$)
        $xlsxFiles = array_filter($xlsxFiles, fn($f) => ! str_starts_with(basename($f), '~$'));
        $xlsxFiles = array_values($xlsxFiles);

        if (empty($xlsxFiles)) {
            $this->warn("No .xlsx files found in: {$seedsPath}");
            return self::SUCCESS;
        }

        $dryRun = $this->option('dry-run');

        $this->info('Found ' . count($xlsxFiles) . ' XLSX file(s):');

        $totalRows    = 0;
        $filesWritten = 0;

        foreach ($xlsxFiles as $xlsxFile) {
            $categoryName = $this->categoryNameFromFile($xlsxFile);
            $outputFile   = $seedsPath . DIRECTORY_SEPARATOR . pathinfo($xlsxFile, PATHINFO_FILENAME) . '_import.sql';

            try {
                [$headers, $rows] = $this->parseXlsx($xlsxFile);
            } catch (\Throwable $e) {
                $this->error("  ✗ [{$xlsxFile}] " . $e->getMessage());
                continue;
            }

            $rowCount = count($rows);
            $totalRows += $rowCount;

            $this->line(
                "  • <comment>{$categoryName}</comment> → {$rowCount} row(s)"
                . ($dryRun ? '' : " → " . basename($outputFile))
            );

            if ($dryRun) {
                continue;
            }

            $sql = $this->buildSql($headers, $rows);
            file_put_contents($outputFile, $sql);
            $filesWritten++;
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn("[DRY RUN] Would generate " . count($xlsxFiles) . " SQL file(s) with {$totalRows} total product rows.");
            $this->warn("[DRY RUN] No files were written.");
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info("✔ Done. Generated {$filesWritten} SQL seed file(s) with {$totalRows} total product rows.");
        $this->line("  Run <comment>php artisan app:import-products --dry-run</comment> to preview, then <comment>--force</comment> to import.");

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Load an XLSX file, auto-detect the header row, and return
     * [mappedHeaders, dataRows].
     *
     * - Header row: first row whose cells contain at least 3 of the expected
     *   header keywords (name, code, hsncode, mrp, ccp, total qty …).
     * - Data rows: all non-empty rows below the header row.
     */
    private function parseXlsx(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();

        $headerRowIndex = null;
        $rawHeaders     = [];
        $allRows        = [];

        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            $cells = [];
            foreach ($row->getCellIterator('A', 'Z') as $cell) {
                $cells[] = $cell->getValue();
            }

            // Trim trailing nulls
            while (!empty($cells) && $cells[array_key_last($cells)] === null) {
                array_pop($cells);
            }

            if (empty($cells)) {
                continue;
            }

            // Detect header row: at least 3 of our expected column names present
            if ($headerRowIndex === null) {
                $lowerCells = array_map(fn($c) => strtolower(trim((string) $c)), $cells);
                $knownKeys  = array_keys($this->columnMap);
                $matches    = count(array_intersect($lowerCells, $knownKeys));

                if ($matches >= 3) {
                    $headerRowIndex = $rowIndex;
                    $rawHeaders     = $lowerCells;   // store lowercased for mapping
                    continue;
                }

                // Not a header row, keep scanning
                continue;
            }

            // Data row
            $allRows[] = $cells;
        }

        if ($headerRowIndex === null) {
            throw new \RuntimeException('Could not detect header row (expected columns: Name, Code, Hsncode, MRP, CCP, Total Qty).');
        }

        // Map raw headers to DB columns (only the ones we care about)
        $mappedHeaders = [];
        foreach ($rawHeaders as $idx => $rawHeader) {
            $dbCol = $this->columnMap[$rawHeader] ?? null;
            if ($dbCol !== null) {
                $mappedHeaders[$idx] = $dbCol;
            }
        }

        // Filter out completely empty rows and rows where Name is missing
        $nameIdx = array_search('name', $mappedHeaders);
        $dataRows = array_filter($allRows, function ($cells) use ($nameIdx) {
            if ($nameIdx === false) return false;
            $val = trim((string) ($cells[$nameIdx] ?? ''));
            return $val !== '' && strtolower($val) !== 'null';
        });

        return [$mappedHeaders, array_values($dataRows)];
    }

    /**
     * Build the full SQL file content for a given set of data rows.
     */
    private function buildSql(array $mappedHeaders, array $rows): string
    {
        $lines = [];

        // Fixed columns (defaults that don't come from XLSX)
        $defaultCols = array_keys($this->defaults);

        // All DB columns that will appear in every INSERT
        $xlsxCols  = array_values($mappedHeaders); // e.g. ['name', 'barcode', 'hsn_code', ...]
        $allCols   = array_unique(array_merge($xlsxCols, $defaultCols, ['slug', 'created_at', 'updated_at']));
        $allCols   = array_values($allCols);

        foreach ($rows as $cells) {
            // Build a keyed row from XLSX data
            $row = [];
            foreach ($mappedHeaders as $colIdx => $dbCol) {
                $row[$dbCol] = $cells[$colIdx] ?? null;
            }

            // Apply defaults (only if not set by XLSX)
            foreach ($this->defaults as $col => $default) {
                $row[$col] = $row[$col] ?? $default;
            }

            // Generate slug from name
            $name = trim((string) ($row['name'] ?? ''));
            $row['slug']       = Str::slug($name);
            $row['created_at'] = null; // will be set by import command
            $row['updated_at'] = null;

            // Build column list and value list
            $colList = implode(', ', $allCols);
            $valList = implode(', ', array_map(fn($col) => $this->sqlValue($row[$col] ?? null), $allCols));

            $lines[] = "INSERT INTO products ({$colList}) VALUES ({$valList});";
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Format a PHP value as a SQL literal.
     */
    private function sqlValue(mixed $value): string
    {
        if ($value === null || (string) $value === '') {
            return 'NULL';
        }

        // Numeric
        if (is_numeric($value)) {
            return (string) $value;
        }

        // String: escape single quotes
        return "'" . addslashes((string) $value) . "'";
    }

    /**
     * Derive a human-readable, title-cased category name from the XLSX filename.
     *
     * "BABY CARE.xlsx"         → "Baby Care"
     * "GROCERY & COOKING.xlsx" → "Grocery & Cooking"
     */
    private function categoryNameFromFile(string $filePath): string
    {
        $basename = pathinfo($filePath, PATHINFO_FILENAME); // e.g. "BABY CARE"
        return Str::title(strtolower($basename));
    }
}
