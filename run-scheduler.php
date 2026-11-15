
<?php

chdir('/home/h400904/laravel_core');

require '/home/h400904/laravel_core/vendor/autoload.php';

$app = require_once '/home/h400904/laravel_core/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$status = $kernel->call('schedule:run');

exit($status);
