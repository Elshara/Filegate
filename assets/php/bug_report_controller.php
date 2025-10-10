<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/add_bug_report.php';
require_once __DIR__ . '/vote_for_bug_report.php';
require_once __DIR__ . '/get_setting.php';
require_once __DIR__ . '/guard_asset.php';

function fg_public_bug_report_controller(): void
{
    fg_bootstrap();
    $user = fg_require_login();
    fg_guard_asset('assets/php/bug_report_controller.php', [
        'role' => $user['role'] ?? null,
        'user_id' => $user['id'] ?? null,
    ]);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /index.php');
        return;
    }

    $action = $_POST['action'] ?? 'create_bug_report';
    $policy = strtolower((string) fg_get_setting('bug_report_policy', 'members'));
    if ($policy === 'enabled') {
        $policy = 'members';
    }
    if (!in_array($policy, ['disabled', 'members', 'moderators', 'admins'], true)) {
        $policy = 'members';
    }

    $role = strtolower((string) ($user['role'] ?? 'member'));
    $canModerate = in_array($role, ['admin', 'moderator'], true);
    $userId = (int) ($user['id'] ?? 0);
    $redirectBase = '/index.php';

    $statusOptions = fg_get_setting('bug_report_statuses', ['new', 'triaged', 'in_progress', 'resolved', 'wont_fix', 'duplicate']);
    if (!is_array($statusOptions) || empty($statusOptions)) {
        $statusOptions = ['new'];
    }
    $statusOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $statusOptions)));
    if (empty($statusOptions)) {
        $statusOptions = ['new'];
    }

    $severityOptions = fg_get_setting('bug_report_severities', ['low', 'medium', 'high', 'critical']);
    if (!is_array($severityOptions) || empty($severityOptions)) {
        $severityOptions = ['medium'];
    }
    $severityOptions = array_values(array_unique(array_map(static function ($value) {
        return strtolower(trim((string) $value));
    }, $severityOptions)));
    if (empty($severityOptions)) {
        $severityOptions = ['medium'];
    }

    $defaultVisibility = strtolower((string) fg_get_setting('bug_report_default_visibility', 'members'));
    if (!in_array($defaultVisibility, ['public', 'members', 'private'], true)) {
        $defaultVisibility = 'members';
    }

    $defaultOwnerRole = trim((string) fg_get_setting('bug_report_default_owner_role', 'moderator'));
    if ($defaultOwnerRole === '') {
        $defaultOwnerRole = 'moderator';
    }

    if ($action === 'create_bug_report') {
        $allowed = $policy !== 'disabled'
            && (
                $policy === 'members'
                || ($policy === 'moderators' && $canModerate)
                || ($policy === 'admins' && $role === 'admin')
            );

        if (!$allowed) {
            header('Location: ' . $redirectBase . '?error=bug-report-unauthorised');
            return;
        }

        $status = $statusOptions[0];
        if ($canModerate) {
            $candidateStatus = strtolower(trim((string) ($_POST['status'] ?? $status)));
            if (in_array($candidateStatus, $statusOptions, true)) {
                $status = $candidateStatus;
            }
        }

        $severity = strtolower(trim((string) ($_POST['severity'] ?? $severityOptions[0])));
        if (!in_array($severity, $severityOptions, true)) {
            $severity = $severityOptions[0];
        }

        $visibility = $defaultVisibility;
        if ($canModerate) {
            $candidateVisibility = strtolower(trim((string) ($_POST['visibility'] ?? $defaultVisibility)));
            if (in_array($candidateVisibility, ['public', 'members', 'private'], true)) {
                $visibility = $candidateVisibility;
            }
        }

        $ownerRole = $defaultOwnerRole;
        if ($canModerate) {
            $candidateOwnerRole = trim((string) ($_POST['owner_role'] ?? $defaultOwnerRole));
            if ($candidateOwnerRole !== '') {
                $ownerRole = $candidateOwnerRole;
            }
        }

        try {
            $record = fg_add_bug_report([
                'title' => $_POST['title'] ?? '',
                'summary' => $_POST['summary'] ?? '',
                'details' => $_POST['details'] ?? '',
                'status' => $status,
                'severity' => $severity,
                'visibility' => $visibility,
                'environment' => $_POST['environment'] ?? '',
                'resolution_notes' => $_POST['resolution_notes'] ?? '',
                'steps_to_reproduce' => $_POST['steps_to_reproduce'] ?? '',
                'affected_versions' => $_POST['affected_versions'] ?? '',
                'tags' => $_POST['tags'] ?? '',
                'reference_links' => $_POST['reference_links'] ?? '',
                'attachments' => $_POST['attachments'] ?? '',
                'reporter_user_id' => $userId > 0 ? $userId : null,
                'owner_role' => $ownerRole,
                'owner_user_id' => $canModerate ? ($_POST['owner_user_id'] ?? null) : null,
                'watchers' => '',
                'performed_by' => $userId > 0 ? $userId : null,
                'trigger' => 'bug_report_submission',
            ]);
        } catch (Throwable $exception) {
            header('Location: ' . $redirectBase . '?error=bug-report-invalid');
            return;
        }

        $anchor = '#bug-report-' . (int) ($record['id'] ?? 0);
        header('Location: ' . $redirectBase . '?notice=bug-report-created' . $anchor);
        return;
    }

    if ($action === 'toggle_watch') {
        $bugId = (int) ($_POST['bug_report_id'] ?? 0);
        if ($bugId <= 0 || $userId <= 0) {
            header('Location: ' . $redirectBase . '?error=bug-report-invalid');
            return;
        }

        try {
            $updated = fg_vote_for_bug_report($bugId, $userId, 'toggle');
        } catch (Throwable $exception) {
            header('Location: ' . $redirectBase . '?error=bug-report-invalid');
            return;
        }

        $isWatching = in_array($userId, $updated['watchers'] ?? [], true);
        $notice = $isWatching ? 'bug-report-watching' : 'bug-report-unwatched';
        header('Location: ' . $redirectBase . '?notice=' . $notice . '#bug-report-' . $bugId);
        return;
    }

    header('Location: ' . $redirectBase);
}
