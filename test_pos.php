<?php
try {
    \Illuminate\Support\Facades\Auth::loginUsingId(1);
    $c = new App\Livewire\Orders\PosScreen();
    $c->mount();
    echo "SUCCESS\n";
} catch (\Throwable $e) {
    echo $e->getMessage() . "\n" . $e->getFile() . "\n" . $e->getLine() . "\n";
}
