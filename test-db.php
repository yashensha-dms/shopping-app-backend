<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $res = DB::table('categories')->limit(5)->get();
    echo "Connected successfully! Count: " . count($res) . "\n";
    print_r($res->toArray());
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
