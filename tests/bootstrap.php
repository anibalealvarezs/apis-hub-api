<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$configFile = getenv('CONFIG_FILE') ?: __DIR__ . '/../config/config.yaml';

if (!file_exists($configFile)) {
    echo "⚠️  Test config file not found: $configFile\n";
    echo "👉  Please copy config/config.yaml.example to config/config.yaml and fill in your credentials.\n";
    // We don't exit here so the unit tests can still run if the integration ones are skipped.
} else {
    $GLOBALS['app_config'] = Yaml::parseFile($configFile);
}

function app_config(string $key = null, $default = null)
{
    $config = $GLOBALS['app_config'] ?? [];
    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}
