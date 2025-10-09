<?php

require_once __DIR__ . '/load_pages.php';
require_once __DIR__ . '/save_pages.php';
require_once __DIR__ . '/normalize_page_slug.php';
require_once __DIR__ . '/sanitize_html.php';

function fg_add_page(array $attributes): array
{
    $pages = fg_load_pages();
    if (!isset($pages['records']) || !is_array($pages['records'])) {
        $pages['records'] = [];
    }
    if (!isset($pages['next_id'])) {
        $pages['next_id'] = 1;
    }

    $records = $pages['records'];
    $id = (int) $pages['next_id'];
    $pages['next_id'] = $id + 1;

    $title = trim((string) ($attributes['title'] ?? ''));
    if ($title === '') {
        $title = 'Untitled Page ' . $id;
    }

    $slugBase = fg_normalize_page_slug((string) ($attributes['slug'] ?? $title));
    $slug = $slugBase;
    $existingSlugs = [];
    foreach ($records as $record) {
        $existingSlugs[] = (string) ($record['slug'] ?? '');
    }
    $suffix = 1;
    while (in_array($slug, $existingSlugs, true)) {
        $slug = $slugBase . '-' . $suffix;
        $suffix++;
    }

    $format = (string) ($attributes['format'] ?? 'html');
    $allowedFormats = ['html', 'text'];
    if (!in_array($format, $allowedFormats, true)) {
        $format = 'html';
    }

    $contentRaw = (string) ($attributes['content'] ?? '');
    $content = $format === 'html' ? fg_sanitize_html($contentRaw) : $contentRaw;

    $summary = trim((string) ($attributes['summary'] ?? ''));
    $visibility = (string) ($attributes['visibility'] ?? 'public');
    $allowedVisibilities = ['public', 'members', 'roles'];
    if (!in_array($visibility, $allowedVisibilities, true)) {
        $visibility = 'public';
    }

    $allowedRolesInput = $attributes['allowed_roles'] ?? [];
    if (!is_array($allowedRolesInput)) {
        $allowedRolesInput = [];
    }
    $allowedRoles = array_values(array_unique(array_filter(array_map('strval', $allowedRolesInput))));

    $showInNavigation = !empty($attributes['show_in_navigation']);
    $template = (string) ($attributes['template'] ?? 'standard');
    if ($template === '') {
        $template = 'standard';
    }

    $variablesInput = $attributes['variables'] ?? [];
    if (!is_array($variablesInput)) {
        $variablesInput = [];
    }

    $page = [
        'id' => $id,
        'slug' => $slug,
        'title' => $title,
        'summary' => $summary,
        'content' => $content,
        'format' => $format,
        'visibility' => $visibility,
        'allowed_roles' => $allowedRoles,
        'show_in_navigation' => $showInNavigation,
        'template' => $template,
        'variables' => $variablesInput,
        'owner_id' => $attributes['owner_id'] ?? null,
        'created_at' => date(DATE_ATOM),
        'updated_at' => date(DATE_ATOM),
    ];

    $records[] = $page;
    $pages['records'] = $records;

    fg_save_pages($pages);

    return $page;
}

