<?php
/**
 * Version helpers shared by public/admin endpoints.
 */

function pbVersionFilePath(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'version.json';
}

function pbReadVersionFile(): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $path = pbVersionFilePath();
    if (!is_file($path) || !is_readable($path)) {
        return $cached = [];
    }

    $json = json_decode((string)file_get_contents($path), true);
    return $cached = is_array($json) ? $json : [];
}

function pbVersionValue(string $key, array $fromFile, ?string $default = null): ?string
{
    $envMap = [
        'version' => 'APP_VERSION',
        'asset_version' => 'ASSET_VERSION',
        'fingerprint' => 'APP_FINGERPRINT',
        'commit' => 'GIT_COMMIT',
        'branch' => 'GIT_BRANCH',
        'built_at' => 'BUILT_AT',
        'deployed_at' => 'DEPLOYED_AT',
    ];

    $envKey = $envMap[$key] ?? null;
    if ($envKey !== null && function_exists('pbEnv')) {
        $fromEnv = pbEnv($envKey, null);
        if ($fromEnv !== null && trim((string)$fromEnv) !== '') {
            return trim((string)$fromEnv);
        }
    }

    if (array_key_exists($key, $fromFile) && trim((string)$fromFile[$key]) !== '') {
        return trim((string)$fromFile[$key]);
    }

    return $default;
}

function pbGetVersionInfo(): array
{
    $fromFile = pbReadVersionFile();
    $version = pbVersionValue('version', $fromFile, 'unknown');
    $assetVersion = pbVersionValue('asset_version', $fromFile, $version);

    return [
        'app' => 'printingbruno',
        'version' => $version,
        'asset_version' => $assetVersion !== null && $assetVersion !== '' ? $assetVersion : 'unknown',
        'fingerprint' => pbVersionValue('fingerprint', $fromFile, 'unknown'),
        'commit' => pbVersionValue('commit', $fromFile, 'unknown'),
        'branch' => pbVersionValue('branch', $fromFile, 'unknown'),
        'built_at' => pbVersionValue('built_at', $fromFile, $fromFile['generated_at'] ?? null),
        'deployed_at' => pbVersionValue('deployed_at', $fromFile, null),
    ];
}
