<?php

require_once __DIR__ . '/dataset_definition.php';

function fg_dataset_is_exposable(string $name): bool
{
    $definition = fg_dataset_definition($name);
    return ($definition['expose_via_api'] ?? false) === true;
}

