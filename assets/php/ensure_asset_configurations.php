<?php

require_once __DIR__ . '/load_asset_configurations.php';
require_once __DIR__ . '/save_asset_configurations.php';
require_once __DIR__ . '/list_asset_files.php';
require_once __DIR__ . '/asset_scope.php';
require_once __DIR__ . '/asset_label.php';
require_once __DIR__ . '/default_asset_parameters.php';

function fg_ensure_asset_configurations(): void
{
    $configurations = fg_load_asset_configurations();
    $files = fg_list_asset_files();
    $updated = false;

    if (!isset($configurations['records']) || !is_array($configurations['records'])) {
        $configurations['records'] = [];
        $updated = true;
    }

    foreach ($files as $file) {
        if (!isset($configurations['records'][$file])) {
            $configurations['records'][$file] = [
                'label' => fg_asset_label($file),
                'scope' => fg_asset_scope($file),
                'extension' => strtolower(pathinfo($file, PATHINFO_EXTENSION)),
                'allow_user_override' => fg_asset_scope($file) === 'local',
                'allowed_roles' => fg_asset_scope($file) === 'global' ? ['admin'] : ['admin', 'moderator'],
                'parameters' => fg_default_asset_parameters($file),
            ];
            $updated = true;
        } else {
            $existingParameters = $configurations['records'][$file]['parameters'] ?? [];
            $defaults = fg_default_asset_parameters($file);
            foreach ($defaults as $key => $definition) {
                if (!isset($existingParameters[$key])) {
                    $existingParameters[$key] = $definition;
                    $updated = true;
                } else {
                    $existingParameters[$key] = array_merge($definition, $existingParameters[$key]);
                    if (!isset($existingParameters[$key]['baseline_allow_user_override'])) {
                        $existingParameters[$key]['baseline_allow_user_override'] = $definition['baseline_allow_user_override'] ?? false;
                    }
                }
            }
            $configurations['records'][$file]['parameters'] = $existingParameters;
            if (!isset($configurations['records'][$file]['allow_user_override'])) {
                $configurations['records'][$file]['allow_user_override'] = fg_asset_scope($file) === 'local';
            }
        }
    }

    $existingFiles = array_keys($configurations['records']);
    foreach ($existingFiles as $file) {
        if (!in_array($file, $files, true)) {
            unset($configurations['records'][$file]);
            $updated = true;
        }
    }

    if ($updated) {
        fg_save_asset_configurations($configurations);
    }
}
