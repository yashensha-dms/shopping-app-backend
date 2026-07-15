<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$productsCount = \App\Models\Product::onlyTrashed()->forceDelete();
$variationsCount = \App\Models\Variation::onlyTrashed()->forceDelete();

echo "Hard deleted {$productsCount} products and {$variationsCount} variations." . PHP_EOL;
