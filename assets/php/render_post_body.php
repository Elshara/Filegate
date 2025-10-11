<?php

require_once __DIR__ . '/collect_embeds.php';
require_once __DIR__ . '/render_embed.php';
require_once __DIR__ . '/get_setting.php';
require_once __DIR__ . '/calculate_post_statistics.php';
require_once __DIR__ . '/load_template_options.php';
require_once __DIR__ . '/normalize_content_module.php';
require_once __DIR__ . '/content_module_task_progress.php';

function fg_render_post_body(array $post): string
{
    $content = $post['content'] ?? '';
    $embeds = $post['embeds'] ?? fg_collect_embeds($content);
    $statistics = $post['statistics'] ?? [];
    if ($statistics === [] && $content !== '') {
        $statistics = fg_calculate_post_statistics($content, $embeds);
    }
    $display_options = $post['display_options'] ?? ['show_statistics' => true, 'show_embeds' => true];
    $embed_policy = fg_get_setting('rich_embed_policy', 'enabled');
    $statistics_policy = fg_get_setting('statistics_visibility', 'public');
    $should_render_embeds = ($display_options['show_embeds'] ?? true) && $embed_policy !== 'disabled';
    $renderable_embeds = $should_render_embeds ? $embeds : [];

    $payload = htmlspecialchars(json_encode($renderable_embeds, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
    $html = '<div class="post-content" data-embeds="' . $payload . '" data-embeds-rendered="' . (!empty($renderable_embeds) ? 'true' : 'false') . '">';

    if (!empty($post['summary'])) {
        $html .= '<p class="post-summary">' . htmlspecialchars($post['summary']) . '</p>';
    }

    $module_assignment = null;
    $taskToday = strtotime('today');
    if ($taskToday === false) {
        $taskToday = strtotime(date('Y-m-d'));
    }
    if ($taskToday === false) {
        $taskToday = time();
    }
    $taskSoonThreshold = $taskToday + (3 * 86400);
    if (!empty($post['content_module']) && is_array($post['content_module'])) {
        $module_assignment = fg_normalize_content_module_definition($post['content_module']);
    }
    if ($module_assignment !== null) {
        $html .= '<section class="post-module" aria-label="Guided module">';
        $html .= '<header class="post-module-header">';
        $html .= '<h2>' . htmlspecialchars($module_assignment['label']) . '</h2>';
        if (!empty($module_assignment['stage'])) {
            $html .= '<p class="post-module-stage">Stage: ' . htmlspecialchars($module_assignment['stage']) . '</p>';
        }
        $html .= '</header>';
        if (!empty($module_assignment['description'])) {
            $html .= '<p class="post-module-description">' . htmlspecialchars($module_assignment['description']) . '</p>';
        }
        if (!empty($module_assignment['categories'])) {
            $html .= '<ul class="post-module-categories" aria-label="Module categories">';
            foreach ($module_assignment['categories'] as $category) {
                $html .= '<li>' . htmlspecialchars($category) . '</li>';
            }
            $html .= '</ul>';
        }
        $moduleGuides = $module_assignment['guides'] ?? [];
        $microGuides = is_array($moduleGuides['micro'] ?? null) ? $moduleGuides['micro'] : [];
        $macroGuides = is_array($moduleGuides['macro'] ?? null) ? $moduleGuides['macro'] : [];
        $moduleRelationships = $module_assignment['relationships'] ?? [];
        if (!is_array($moduleRelationships)) {
            $moduleRelationships = [];
        }
        if (!empty($microGuides) || !empty($macroGuides)) {
            $html .= '<details class="post-module-guides"><summary>Guidance</summary>';
            if (!empty($microGuides)) {
                $html .= '<h3>Micro</h3><ul>';
                foreach ($microGuides as $guide) {
                    if (!is_array($guide)) {
                        continue;
                    }
                    $title = trim((string) ($guide['title'] ?? ''));
                    $prompt = trim((string) ($guide['prompt'] ?? ''));
                    if ($title === '' && $prompt === '') {
                        continue;
                    }
                    $html .= '<li>' . htmlspecialchars($title === '' ? $prompt : $title);
                    if ($title !== '' && $prompt !== '') {
                        $html .= '<span> — ' . htmlspecialchars($prompt) . '</span>';
                    }
                    $html .= '</li>';
                }
                $html .= '</ul>';
            }
            if (!empty($macroGuides)) {
                $html .= '<h3>Macro</h3><ul>';
                foreach ($macroGuides as $guide) {
                    if (!is_array($guide)) {
                        continue;
                    }
                    $title = trim((string) ($guide['title'] ?? ''));
                    $prompt = trim((string) ($guide['prompt'] ?? ''));
                    if ($title === '' && $prompt === '') {
                        continue;
                    }
                    $html .= '<li>' . htmlspecialchars($title === '' ? $prompt : $title);
                    if ($title !== '' && $prompt !== '') {
                        $html .= '<span> — ' . htmlspecialchars($prompt) . '</span>';
                    }
                    $html .= '</li>';
                }
                $html .= '</ul>';
            }
            $html .= '</details>';
        }
        if (!empty($moduleRelationships)) {
            $html .= '<details class="post-module-relationships"><summary>Connected modules</summary><ul>';
            foreach ($moduleRelationships as $relationship) {
                if (!is_array($relationship)) {
                    continue;
                }
                $type = trim((string) ($relationship['type'] ?? 'related'));
                if ($type === '') {
                    $type = 'related';
                }
                $targetKey = trim((string) ($relationship['module_key'] ?? $relationship['module_reference'] ?? ''));
                if ($targetKey === '') {
                    continue;
                }
                $targetLabel = trim((string) ($relationship['module_label'] ?? ''));
                $description = trim((string) ($relationship['description'] ?? ''));
                $html .= '<li><strong>' . htmlspecialchars(ucfirst($type)) . '</strong>: ' . htmlspecialchars($targetLabel !== '' ? $targetLabel : $targetKey);
                if ($targetLabel !== '' && strcasecmp($targetLabel, $targetKey) !== 0) {
                    $html .= ' <code>' . htmlspecialchars($targetKey) . '</code>';
                }
                if ($description !== '') {
                    $html .= '<span> — ' . htmlspecialchars($description) . '</span>';
                }
                $html .= '</li>';
            }
            $html .= '</ul></details>';
        }
        if (!empty($module_assignment['tasks'])) {
            $html .= '<section class="post-module-tasks" aria-label="Module checklist"><h3>Checklist</h3>';
            $taskProgress = $module_assignment['task_progress'] ?? fg_content_module_task_progress($module_assignment['tasks']);
            if (!empty($taskProgress['summary'])) {
                $stateClass = $taskProgress['state'] ?? 'unknown';
                $statusLabel = trim((string) ($taskProgress['status_label'] ?? ''));
                $summaryText = $statusLabel !== '' ? $statusLabel . ' — ' . $taskProgress['summary'] : $taskProgress['summary'];
                $html .= '<p class="post-module-task-summary task-state-' . htmlspecialchars($stateClass) . '">' . htmlspecialchars($summaryText) . '</p>';
            }
            $html .= '<ul>';
            foreach ($module_assignment['tasks'] as $task) {
                if (!is_array($task)) {
                    continue;
                }
                $taskLabel = trim((string) ($task['label'] ?? ''));
                if ($taskLabel === '') {
                    continue;
                }
                $taskDescription = trim((string) ($task['description'] ?? ''));
                $owner = trim((string) ($task['owner'] ?? ''));
                $dueDisplay = trim((string) ($task['due_display'] ?? ($task['due_date'] ?? '')));
                $priorityLabel = trim((string) ($task['priority_label'] ?? ($task['priority'] ?? '')));
                $notes = trim((string) ($task['notes'] ?? ''));
                $dueTimestamp = isset($task['due_timestamp']) && is_numeric($task['due_timestamp']) ? (int) $task['due_timestamp'] : null;
                $isCompleted = !empty($task['completed']);
                $stateLabel = $isCompleted ? 'Completed' : 'Pending';
                $stateClass = $isCompleted ? 'complete' : 'pending';
                if (!$isCompleted && $dueTimestamp !== null) {
                    if ($dueTimestamp < $taskToday) {
                        $stateClass = 'overdue';
                        $stateLabel = 'Overdue';
                    } elseif ($dueTimestamp <= $taskSoonThreshold) {
                        $stateClass = 'due-soon';
                        $stateLabel = 'Due soon';
                    }
                }
                $metaBits = [];
                if ($owner !== '') {
                    $metaBits[] = 'Owner: ' . $owner;
                }
                if ($dueDisplay !== '') {
                    $metaBits[] = 'Due: ' . $dueDisplay;
                }
                if ($priorityLabel !== '') {
                    $metaBits[] = $priorityLabel;
                }

                $html .= '<li class="task-' . htmlspecialchars($stateClass) . '"><span class="task-label">' . htmlspecialchars($taskLabel) . '</span>';
                if ($taskDescription !== '') {
                    $html .= '<span class="task-description">' . htmlspecialchars($taskDescription) . '</span>';
                }
                $html .= '<span class="task-state">' . htmlspecialchars($stateLabel) . '</span>';
                if (!empty($metaBits)) {
                    $html .= '<small class="task-meta">' . htmlspecialchars(implode(' · ', $metaBits)) . '</small>';
                }
                if ($notes !== '') {
                    $html .= '<small class="task-notes">' . htmlspecialchars($notes) . '</small>';
                }
                $html .= '</li>';
            }
            $html .= '</ul></section>';
        }
        if (!empty($module_assignment['fields'])) {
            $html .= '<dl class="post-module-fields">';
            foreach ($module_assignment['fields'] as $field) {
                $value = trim((string) ($field['value'] ?? ''));
                if ($value === '') {
                    continue;
                }
                $html .= '<div><dt>' . htmlspecialchars($field['label'] ?? '') . '</dt><dd>' . nl2br(htmlspecialchars($value)) . '</dd></div>';
            }
            $html .= '</dl>';
        }
        if (!empty($module_assignment['profile_prompts'])) {
            $html .= '<details class="post-module-prompts"><summary>Profile prompts</summary><ul>';
            foreach ($module_assignment['profile_prompts'] as $prompt) {
                $html .= '<li>' . htmlspecialchars($prompt) . '</li>';
            }
            $html .= '</ul></details>';
        }
        if (!empty($module_assignment['css_tokens'])) {
            $html .= '<details class="post-module-css"><summary>CSS tokens</summary><p>';
            foreach ($module_assignment['css_tokens'] as $token) {
                $html .= '<code>' . htmlspecialchars($token) . '</code> ';
            }
            $html .= '</p></details>';
        }
        $html .= '</section>';
    }

    if ($content !== '') {
        $html .= '<div class="post-body-text">' . $content . '</div>';
    }

    if (!empty($renderable_embeds)) {
        $html .= '<div class="post-embeds">';
        foreach ($renderable_embeds as $embed) {
            $html .= fg_render_embed($embed);
        }
        $html .= '</div>';
    }

    $attachments = $post['attachments'] ?? [];
    if (!empty($attachments)) {
        $html .= '<ul class="post-attachments" aria-label="Attachments">';
        foreach ($attachments as $attachment) {
            $label = $attachment['original_name'] ?? ('File #' . ($attachment['id'] ?? '?'));
            $id = isset($attachment['id']) ? (int) $attachment['id'] : 0;
            $html .= '<li><a href="/media.php?upload=' . $id . '" target="_blank" rel="noopener">' . htmlspecialchars($label) . '</a>';
            if (isset($attachment['size'])) {
                $html .= ' <small>(' . round(((int) $attachment['size']) / 1024, 1) . ' KB)</small>';
            }
            $html .= '</li>';
        }
        $html .= '</ul>';
    }

    if (!empty($post['tags'])) {
        $html .= '<ul class="post-tags" aria-label="Tags">';
        foreach ($post['tags'] as $tag) {
            $html .= '<li>' . htmlspecialchars($tag) . '</li>';
        }
        $html .= '</ul>';
    }

    $templates = fg_load_template_options();
    $template_label = null;
    foreach ($templates as $template) {
        if (($template['name'] ?? '') === ($post['template'] ?? '')) {
            $template_label = $template['label'] ?? $template['name'];
            break;
        }
    }
    if ($template_label) {
        $html .= '<p class="post-template">Template: ' . htmlspecialchars($template_label) . '</p>';
    }

    if (!empty($statistics) && $statistics_policy !== 'hidden' && ($display_options['show_statistics'] ?? true)) {
        $html .= '<dl class="post-statistics" aria-label="Post statistics">';
        if (isset($statistics['word_count'])) {
            $html .= '<div><dt>Words</dt><dd>' . (int) $statistics['word_count'] . '</dd></div>';
        }
        if (isset($statistics['character_count'])) {
            $html .= '<div><dt>Characters</dt><dd>' . (int) $statistics['character_count'] . '</dd></div>';
        }
        if (isset($statistics['embed_count'])) {
            $html .= '<div><dt>Embeds</dt><dd>' . (int) $statistics['embed_count'] . '</dd></div>';
        }
        if (isset($statistics['heading_count'])) {
            $html .= '<div><dt>Headings</dt><dd>' . (int) $statistics['heading_count'] . '</dd></div>';
        }
        $html .= '</dl>';
    }

    $html .= '</div>';

    return $html;
}

