<?php
// lib/resource_validator.php
declare(strict_types=1);

/**
 * Validates that each resource is not overspent.
 *
 * @param array<string, int> $available e.g., ['bm' => 5, 'luck' => 2]
 * @param array<string, int> $costs     e.g., ['bm' => 3, 'luck' => 4]
 * @return array<string>                list of errors for each overspent resource
 */
function validate_resources(array $available, array $costs): array {
    $errors = [];
    foreach ($costs as $key => $amount) {
        if (!isset($available[$key])) {
            $errors[] = "Unknown resource: $key";
        } elseif ($amount < 0) {
            $errors[] = "Negative $key spent";
        } elseif ($amount > $available[$key]) {
            $errors[] = "Not enough $key (needed $amount, have {$available[$key]})";
        }
    }
    return $errors;
}
