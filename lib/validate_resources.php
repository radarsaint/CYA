<?php
// lib/validate_resources.php

declare(strict_types=1);

/**
 * Validates whether a set of resource costs can be afforded.
 *
 * @param array $current Example: ['bm' => 3, 'luck' => 1]
 * @param array $costs   Example: ['bm' => 2, 'luck' => 1]
 * @throws RuntimeException if any resource would be overspent
 */
function validate_resources(array $current, array $costs): void
{
    foreach ($costs as $resource => $cost) {
        $available = $current[$resource] ?? 0;
        if (!is_int($cost) || $cost < 0) {
            throw new RuntimeException("Invalid cost for resource: $resource");
        }
        if ($cost > $available) {
            throw new RuntimeException("Not enough $resource (needed $cost, have $available)");
        }
    }
}