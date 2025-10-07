<?php

require_once __DIR__ . '/load_pages.php';
require_once __DIR__ . '/save_pages.php';
require_once __DIR__ . '/normalize_page_slug.php';
require_once __DIR__ . '/sanitize_html.php';

function fg_update_page(int $id, array $attributes): array
{
    $pages = fg_load_pages();
    $records = $pages['records'] ?? [];
    $found = null;
    foreach ($records as $index => $record) {
        if ((int) ($record['id'] ?? 0) === $id) {
            $found = $index;
            break;
        }
    }

    if ($found === null) {
        throw new RuntimeException('Page not found.');
    }

    $record = $records[$found];

    $title = trim((string) ($attributes['title'] ?? $record['title'] ?? 'Untitled Page'));
    if ($title === '') {
        $title = $record['title'] ?? 'Untitled Page';
    }

    $slugBase = fg_normalize_page_slug((string) ($attributes['slug'] ?? $record['slug'] ?? $title));
    $slug = $slugBase;
    $existingSlugs = [];
    foreach ($records as $index => $candidate) {
        if ($index === $found) {
            continue;
        }
        $existingSlugs[] = (string) ($candidate['slug'] ?? '');
    }
    $suffix = 1;
    while (in_array($slug, $existingSlugs, true)) {
        $slug = $slugBase . '-' . $suffix;
        $suffix++;
    }

    $format = (string) ($attributes['format'] ?? $record['format'] ?? 'html');
    $allowedFormats = ['html', 'text'];
    if (!in_array($format, $allowedFormats, true)) {
        $format = $record['format'] ?? 'html';
    }

    $contentRaw = (string) ($attributes['content'] ?? $record['content'] ?? '');
    $content = $format === 'html' ? fg_sanitize_html($contentRaw) : $contentRaw;

    $summary = trim((string) ($attributes['summary'] ?? $record['summary'] ?? ''));
    $visibility = (string) ($attributes['visibility'] ?? $record['visibility'] ?? 'public');
    $allowedVisibilities = ['public', 'members', 'roles'];
    if (!in_array($visibility, $allowedVisibilities, true)) {
        $visibility = $record['visibility'] ?? 'public';
    }

    $allowedRolesInput = $attributes['allowed_roles'] ?? $record['allowed_roles'] ?? [];
    if (!is_array($allowedRolesInput)) {
        $allowedRolesInput = [];
    }
    $allowedRoles = array_values(array_unique(array_filter(array_map('strval', $allowedRolesInput))));

    $showInNavigation = array_key_exists('show_in_navigation', $attributes)
        ? !empty($attributes['show_in_navigation'])
        : !empty($record['show_in_navigation']);
    $template = (string) ($attributes['template'] ?? $record['template'] ?? 'standard');
    if ($template === '') {
        $template = $record['template'] ?? 'standard';
    }

    $variablesInput = $attributes['variables'] ?? ($record['variables'] ?? []);
    if (!is_array($variablesInput)) {
        $variablesInput = [];
    }

    $record['title'] = $title;
    $record['slug'] = $slug;
    $record['summary'] = $summary;
    $record['content'] = $content;
    $record['format'] = $format;
    $record['visibility'] = $visibility;
    $record['allowed_roles'] = $allowedRoles;
    $record['show_in_navigation'] = $showInNavigation;
    $record['template'] = $template;
    $record['variables'] = $variablesInput;
    $record['updated_at'] = date(DATE_ATOM);

    $records[$found] = $record;
    $pages['records'] = $records;

    fg_save_pages($pages);

    return $record;
}

