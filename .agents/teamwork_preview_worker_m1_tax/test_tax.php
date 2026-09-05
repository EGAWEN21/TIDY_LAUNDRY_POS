<?php
require 'vendor/autoload.php';

// Test 1: Inclusive tax math
$amount = 110.0;
$rate = 10.0;
$incTax = $rate > 0 ? ($amount - ($amount / (1 + ($rate / 100)))) : 0;
$incBefore = $amount - $incTax;
echo "Inclusive Tax: " . round($incTax, 2) . ", Before: " . round($incBefore, 2) . PHP_EOL;
assert(abs($incTax - 10.0) < 0.0001);
assert(abs($incBefore - 100.0) < 0.0001);

// Test 2: Exclusive tax math
$excAmount = 100.0;
$excRate = 10.0;
$excTax = $excAmount * ($excRate / 100);
$excBefore = $excAmount;
$excTotal = $excBefore + $excTax;
echo "Exclusive Tax: " . round($excTax, 2) . ", Before: " . round($excBefore, 2) . ", Total: " . round($excTotal, 2) . PHP_EOL;
assert(abs($excTax - 10.0) < 0.0001);
assert(abs($excBefore - 100.0) < 0.0001);
assert(abs($excTotal - 110.0) < 0.0001);

// Test 3: Zero tax
$zeroAmount = 50.0;
$zeroRate = 0.0;
$zeroIncTax = $zeroRate > 0 ? ($zeroAmount - ($zeroAmount / (1 + ($zeroRate / 100)))) : 0;
assert($zeroIncTax == 0.0);
$zeroExcTax = $zeroAmount * ($zeroRate / 100);
assert($zeroExcTax == 0.0);

echo "All tax formula tests passed successfully!" . PHP_EOL;
