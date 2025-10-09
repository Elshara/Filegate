<?php

function fg_parse_automation_lines($input, array $allowedTypes = [], string $defaultType = 'custom', ?array &$issues = null): array
{
    $issueList = [];

    $normalise = static function (array $items) use ($allowedTypes, $defaultType, &$issueList): array {
        $results = [];
        foreach ($items as $item) {
            $type = $defaultType;
            $options = [];

            if (is_string($item)) {
                $line = trim($item);
                if ($line === '') {
                    continue;
                }

                $optionsPart = '';
                if (strpos($line, '|') !== false) {
                    [$typeCandidate, $optionsPart] = explode('|', $line, 2);
                } else {
                    $typeCandidate = $line;
                }

                $typeCandidate = strtolower(trim((string) $typeCandidate));
                if ($typeCandidate !== '') {
                    if (empty($allowedTypes) || in_array($typeCandidate, $allowedTypes, true)) {
                        $type = $typeCandidate;
                    } else {
                        $issueList[] = sprintf('Type "%s" is not allowed. Falling back to "%s".', $typeCandidate, $defaultType);
                        $optionsPart = $optionsPart !== '' ? $optionsPart : $line;
                    }
                }

                if ($optionsPart !== '') {
                    $pairs = preg_split('/[,]+/', $optionsPart) ?: [];
                    foreach ($pairs as $pair) {
                        $pair = trim($pair);
                        if ($pair === '') {
                            continue;
                        }
                        if (strpos($pair, '=') !== false) {
                            [$key, $value] = explode('=', $pair, 2);
                            $key = strtolower(trim($key));
                            if ($key === '') {
                                $issueList[] = 'An option key is missing for "' . $pair . '".';
                                continue;
                            }
                            $options[$key] = trim($value);
                        } else {
                            $issueList[] = 'Option segment "' . $pair . '" is missing a key=value pair.';
                            $options[] = $pair;
                        }
                    }
                }
            } elseif (is_array($item)) {
                $typeCandidate = strtolower(trim((string) ($item['type'] ?? '')));
                if ($typeCandidate !== '' && (empty($allowedTypes) || in_array($typeCandidate, $allowedTypes, true))) {
                    $type = $typeCandidate;
                } elseif ($typeCandidate !== '') {
                    $issueList[] = sprintf('Type "%s" is not allowed. Falling back to "%s".', $typeCandidate, $defaultType);
                }

                $rawOptions = $item['options'] ?? $item;
                if (is_array($rawOptions)) {
                    foreach ($rawOptions as $key => $value) {
                        if ($key === 'type') {
                            continue;
                        }
                        if (is_array($value)) {
                            $options[$key] = $value;
                        } elseif (is_scalar($value) || $value === null) {
                            $options[$key] = $value === null ? null : (string) $value;
                        }
                    }
                }
            } else {
                continue;
            }

            $results[] = [
                'type' => $type,
                'options' => $options,
            ];
        }

        return $results;
    };

    if (is_string($input)) {
        $trimmed = trim($input);
        if ($trimmed === '') {
            if ($issues !== null) {
                $issues = $issueList;
            }
            return [];
        }

        $firstChar = $trimmed[0];
        if ($firstChar === '[' || $firstChar === '{') {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $issueList[] = 'Unable to decode automation JSON: ' . json_last_error_msg();
            } elseif (is_array($decoded)) {
                if (isset($decoded['type']) || isset($decoded['options'])) {
                    $decoded = [$decoded];
                }
                $results = $normalise(is_array($decoded) ? $decoded : []);
                if ($issues !== null) {
                    $issues = $issueList;
                }
                return $results;
            }
        }

        $lines = preg_split('/\r?\n/', $trimmed) ?: [];
        $results = $normalise($lines);
        if ($issues !== null) {
            $issues = $issueList;
        }
        return $results;
    }

    if (is_array($input)) {
        if (isset($input['type']) || isset($input['options'])) {
            $input = [$input];
        }
        $results = $normalise($input);
        if ($issues !== null) {
            $issues = $issueList;
        }
        return $results;
    }

    if ($issues !== null) {
        $issues = $issueList;
    }

    return [];
}

