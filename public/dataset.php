<?php

require_once __DIR__ . '/../assets/php/global/bootstrap.php';
require_once __DIR__ . '/../assets/php/global/require_login.php';
require_once __DIR__ . '/../assets/php/global/render_json_response.php';
require_once __DIR__ . '/../assets/php/global/dataset_is_exposable.php';
require_once __DIR__ . '/../assets/php/global/dataset_definition.php';
require_once __DIR__ . '/../assets/php/global/load_json.php';
require_once __DIR__ . '/../assets/php/global/is_ajax_request.php';
require_once __DIR__ . '/../assets/php/global/guard_asset.php';

fg_bootstrap();
$user = fg_require_login();
fg_guard_asset('public/dataset.php', [
    'role' => $user['role'] ?? null,
    'user_id' => $user['id'] ?? null,
]);

if (!fg_is_ajax_request()) {
    fg_render_json_response(['status' => 'error', 'message' => 'AJAX requests only.'], 406);
    return;
}

$name = isset($_GET['name']) ? trim((string) $_GET['name']) : '';
$definition = $name !== '' ? fg_dataset_definition($name) : [];

if ($name === '' || $definition === []) {
    fg_render_json_response(['status' => 'error', 'message' => 'Dataset not found.'], 404);
    return;
}

if (!fg_dataset_is_exposable($name)) {
    fg_render_json_response(['status' => 'error', 'message' => 'Dataset is not exposed.'], 403);
    return;
}

$data = fg_load_json($name);
fg_render_json_response(['status' => 'ok', 'dataset' => $name, 'data' => $data]);

