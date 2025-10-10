<?php

function fg_load_content_blueprints(): array
{
    $baseDir = realpath(__DIR__ . '/../xml');
    $result = [
        'categories' => [],
        'module_blueprints' => [],
        'contexts' => [
            'labels' => [],
            'descriptions' => [],
        ],
        'profile_fields' => [],
        'css_components' => [
            'prefix' => '#',
            'shades' => [],
        ],
        'css_values' => [],
        'css_defaults' => [],
        'html_elements' => [],
        'html_attributes' => [],
    ];

    if ($baseDir === false) {
        return $result;
    }

    $parseLines = static function (string $value): array {
        $parts = preg_split('/\R+/u', $value);
        if ($parts === false) {
            $parts = [$value];
        }

        $parts = array_map(static function ($line) {
            return trim((string) $line);
        }, $parts);

        return array_values(array_filter($parts, static function ($line) {
            return $line !== '';
        }));
    };

    $categoriesPath = $baseDir . '/example_content_categories.xml';
    if (is_file($categoriesPath)) {
        $xml = @simplexml_load_file($categoriesPath);
        if ($xml instanceof SimpleXMLElement) {
            foreach ($xml->category as $node) {
                $lines = $parseLines((string) $node);
                if (empty($lines)) {
                    continue;
                }
                $title = array_shift($lines);
                $description = trim(implode(' ', $lines));
                $result['categories'][] = [
                    'title' => $title,
                    'description' => $description,
                ];
            }
        }
    }

    $placeholdersPath = $baseDir . '/example_content_placeholders.xml';
    if (is_file($placeholdersPath)) {
        $xml = @simplexml_load_file($placeholdersPath);
        if ($xml instanceof SimpleXMLElement) {
            $contextNodes = $xml->content->context ?? [];
            $contextLines = [];
            foreach ($contextNodes as $contextNode) {
                $contextLines[] = $parseLines((string) $contextNode);
            }
            $result['contexts']['labels'] = $contextLines[0] ?? [];
            $result['contexts']['descriptions'] = $contextLines[1] ?? [];

            $currentModule = null;
            foreach ($xml->types->type ?? [] as $typeNode) {
                $lines = $parseLines((string) $typeNode);
                if (empty($lines)) {
                    continue;
                }

                $entry = [
                    'format' => $lines[0] ?? '',
                    'group' => $lines[1] ?? '',
                    'title' => $lines[2] ?? '',
                    'description' => $lines[3] ?? '',
                ];

                if (strcasecmp($entry['group'], 'main') === 0) {
                    if ($currentModule !== null) {
                        $result['module_blueprints'][] = $currentModule;
                    }
                    $currentModule = [
                        'format' => $entry['format'],
                        'title' => $entry['title'],
                        'description' => $entry['description'],
                        'fields' => [],
                    ];
                } elseif ($currentModule !== null) {
                    $currentModule['fields'][] = $entry;
                }
            }

            if ($currentModule !== null) {
                $result['module_blueprints'][] = $currentModule;
            }
        }
    }

    $profileFieldsPath = $baseDir . '/example_profile_fields.xml';
    if (is_file($profileFieldsPath)) {
        $xml = @simplexml_load_file($profileFieldsPath);
        if ($xml instanceof SimpleXMLElement) {
            foreach ($xml->field as $fieldNode) {
                $lines = $parseLines((string) $fieldNode);
                if (empty($lines)) {
                    continue;
                }
                $name = array_shift($lines);
                $result['profile_fields'][] = [
                    'name' => $name,
                    'description' => trim(implode(' ', $lines)),
                ];
            }
        }
    }

    $cssComponentsPath = $baseDir . '/css_components.xml';
    if (is_file($cssComponentsPath)) {
        $xml = @simplexml_load_file($cssComponentsPath);
        if ($xml instanceof SimpleXMLElement) {
            $prefix = trim((string) ($xml->pretext->hex ?? '#'));
            if ($prefix === '') {
                $prefix = '#';
            }
            $shades = [];
            foreach ($xml->shades->shade ?? [] as $shadeNode) {
                $value = trim((string) $shadeNode);
                if ($value !== '') {
                    $shades[] = $value;
                }
            }
            $result['css_components'] = [
                'prefix' => $prefix,
                'shades' => $shades,
            ];
        }
    }

    $cssValuesPath = $baseDir . '/css_values.xml';
    if (is_file($cssValuesPath)) {
        $xml = @simplexml_load_file($cssValuesPath);
        if ($xml instanceof SimpleXMLElement) {
            $values = [];
            foreach ($xml->value as $valueNode) {
                $value = trim((string) $valueNode);
                if ($value !== '') {
                    $values[] = $value;
                }
            }
            $result['css_values'] = $values;
        }
    }

    $cssDefaultsPath = $baseDir . '/css_default_styles.xml';
    if (is_file($cssDefaultsPath)) {
        $xml = @simplexml_load_file($cssDefaultsPath);
        if ($xml instanceof SimpleXMLElement) {
            $defaults = [];
            foreach ($xml->defaults->default ?? [] as $defaultNode) {
                $lines = $parseLines((string) $defaultNode);
                if (empty($lines)) {
                    continue;
                }
                $selector = array_shift($lines);
                $defaults[] = [
                    'selector' => $selector,
                    'rules' => implode(' ', $lines),
                ];
            }
            $result['css_defaults'] = $defaults;
        }
    }

    $htmlElementsPath = $baseDir . '/html_elements.xml';
    if (is_file($htmlElementsPath)) {
        $xml = @simplexml_load_file($htmlElementsPath);
        if ($xml instanceof SimpleXMLElement) {
            $elements = [];
            foreach ($xml->element as $elementNode) {
                $value = trim((string) $elementNode);
                if ($value !== '') {
                    $elements[] = $value;
                }
            }
            $result['html_elements'] = $elements;
        }
    }

    $htmlAttributesPath = $baseDir . '/html_attributes.xml';
    if (is_file($htmlAttributesPath)) {
        $xml = @simplexml_load_file($htmlAttributesPath);
        if ($xml instanceof SimpleXMLElement) {
            $attributes = [];
            foreach ($xml->attribute as $attributeNode) {
                $value = trim((string) $attributeNode);
                if ($value !== '') {
                    $attributes[] = $value;
                }
            }
            $result['html_attributes'] = $attributes;
        }
    }

    return $result;
}
