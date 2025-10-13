<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/add_feature_request.php';
require_once __DIR__ . '/vote_for_feature_request.php';
require_once __DIR__ . '/get_setting.php';
require_once __DIR__ . '/guard_asset.php';

function fg_public_feature_request_controller(): void
{
    fg_bootstrap();
    $user = fg_require_login();
    fg_guard_asset('assets/php/feature_request_controller.php', [
        'role' => $user['role'] ?? null,
        'user_id' => $user['id'] ?? null,
    ]);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /index.php');
        return;
    }

    $action = $_POST['action'] ?? 'create_feature_request';
    $policy = strtolower((string) fg_get_setting('feature_request_policy', 'members'));
    if ($policy === 'enabled') {
        $policy = 'members';
    }

    $role = strtolower((string) ($user['role'] ?? 'member'));
    $canModerate = in_array($role, ['admin', 'moderator'], true);
    $userId = (int) ($user['id'] ?? 0);
    $redirectBase = '/index.php';

    $statusOptions = fg_get_setting('feature_request_statuses', ['open', 'researching', 'planned', 'in_progress', 'completed', 'declined']);
    if (!is_array($statusOptions) || empty($statusOptions)) {
        $statusOptions = ['open'];
    }
    $statusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $statusOptions)));
    if (empty($statusOptions)) {
        $statusOptions = ['open'];
    }

    $priorityOptions = fg_get_setting('feature_request_priorities', ['low', 'medium', 'high', 'critical']);
    if (!is_array($priorityOptions) || empty($priorityOptions)) {
        $priorityOptions = ['medium'];
    }
    $priorityOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $priorityOptions)));
    if (empty($priorityOptions)) {
        $priorityOptions = ['medium'];
    }

    $defaultVisibility = strtolower((string) fg_get_setting('feature_request_default_visibility', 'members'));
    if (!in_array($defaultVisibility, ['public', 'members', 'private'], true)) {
        $defaultVisibility = 'members';
    }

    if ($action === 'create_feature_request') {
        $allowed = $policy !== 'disabled'
            && (
                $policy === 'members'
                || ($policy === 'moderators' && $canModerate)
                || ($policy === 'admins' && $role === 'admin')
            );

        if (!$allowed) {
            header('Location: ' . $redirectBase . '?error=feature-request-unauthorised');
            return;
        }

        $status = $statusOptions[0];
        if ($canModerate) {
            $candidateStatus = strtolower(trim((string) ($_POST['status'] ?? $status)));
            if (in_array($candidateStatus, $statusOptions, true)) {
                $status = $candidateStatus;
            }
        }

        $priority = strtolower(trim((string) ($_POST['priority'] ?? $priorityOptions[0])));
        if (!in_array($priority, $priorityOptions, true)) {
            $priority = $priorityOptions[0];
        }

        $visibility = $defaultVisibility;
        if ($canModerate) {
            $candidateVisibility = strtolower(trim((string) ($_POST['visibility'] ?? $defaultVisibility)));
            if (in_array($candidateVisibility, ['public', 'members', 'private'], true)) {
                $visibility = $candidateVisibility;
            }
        }

        $impact = (int) ($_POST['impact'] ?? 3);
        if ($impact < 1) {
            $impact = 1;
        }
        if ($impact > 5) {
            $impact = 5;
        }

        $effort = (int) ($_POST['effort'] ?? 3);
        if ($effort < 1) {
            $effort = 1;
        }
        if ($effort > 5) {
            $effort = 5;
        }

        $referenceLinksInput = $_POST['reference_links'] ?? '';
        $tagsInput = $_POST['tags'] ?? '';

        try {
            $record = fg_add_feature_request([
                'title' => $_POST['title'] ?? '',
                'summary' => $_POST['summary'] ?? '',
                'details' => $_POST['details'] ?? '',
                'status' => $status,
                'priority' => $priority,
                'visibility' => $visibility,
                'impact' => $impact,
                'effort' => $effort,
                'tags' => $tagsInput,
                'reference_links' => $referenceLinksInput,
                'requestor_user_id' => $userId > 0 ? $userId : null,
                'performed_by' => $userId > 0 ? $userId : null,
                'trigger' => 'feature_request_submission',
            ]);
        } catch (Throwable $exception) {
            header('Location: ' . $redirectBase . '?error=feature-request-invalid');
            return;
        }

        $anchor = '#feature-request-' . (int) ($record['id'] ?? 0);
        header('Location: ' . $redirectBase . '?notice=feature-request-created' . $anchor);
        return;
    }

    if ($action === 'toggle_support') {
        $requestId = (int) ($_POST['feature_request_id'] ?? 0);
        if ($requestId <= 0 || $userId <= 0) {
            header('Location: ' . $redirectBase . '?error=feature-request-invalid');
            return;
        }

        try {
            $updated = fg_vote_for_feature_request($requestId, $userId, 'toggle');
        } catch (Throwable $exception) {
            header('Location: ' . $redirectBase . '?error=feature-request-invalid');
            return;
        }

        $hasSupported = in_array($userId, $updated['supporters'] ?? [], true);
        $notice = $hasSupported ? 'feature-request-supported' : 'feature-request-withdrawn';
        header('Location: ' . $redirectBase . '?notice=' . $notice . '#feature-request-' . $requestId);
        return;
    }

    header('Location: ' . $redirectBase);
}

