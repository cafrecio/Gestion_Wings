<?php
require __DIR__ . '/../../../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$u = App\Models\User::where('email', 'admin@wings.com')->first();
echo "ROL: " . $u?->rol . "\n";
echo "isAdmin: " . ($u?->isAdmin() ? 'true' : 'false') . "\n";
