<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
foreach (App\Models\SopDocument::with(['sections','variables'])->get() as $document) {
    echo "Document {$document->id} sections {$document->sections->count()} variables {$document->variables->count()}\n";
}
