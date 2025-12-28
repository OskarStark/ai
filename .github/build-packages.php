<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$baseDir = realpath(__DIR__.'/..');

/**
 * Find all composer.json files in the given directories.
 *
 * @return list<string>
 */
function findComposerFiles(string $baseDir): array
{
    $composerFiles = [];

    // src/*/composer.json (packages)
    foreach (glob($baseDir.'/src/*/composer.json') as $file) {
        $composerFiles[] = $file;
    }

    // src/*/src/Bridge/*/composer.json (bridges)
    foreach (glob($baseDir.'/src/*/src/Bridge/*/composer.json') as $file) {
        $composerFiles[] = $file;
    }

    // examples/composer.json
    if (file_exists($baseDir.'/examples/composer.json')) {
        $composerFiles[] = $baseDir.'/examples/composer.json';
    }

    // demo/composer.json
    if (file_exists($baseDir.'/demo/composer.json')) {
        $composerFiles[] = $baseDir.'/demo/composer.json';
    }

    return $composerFiles;
}

$composerFiles = findComposerFiles($baseDir);

// 1. Find all AI packages
$aiPackages = [];
foreach ($composerFiles as $composerFile) {
    $json = file_get_contents($composerFile);
    if (null === $packageData = json_decode($json, true)) {
        passthru(sprintf('composer validate %s', $composerFile));
        exit(1);
    }

    if (str_starts_with($composerFile, $baseDir.'/src/')) {
        $packageName = $packageData['name'];

        $aiPackages[$packageName] = [
            'path' => realpath(dirname($composerFile)),
        ];
    }
}

// 2. Update all composer.json files from the repository, to use the local version of the AI packages
foreach ($composerFiles as $composerFile) {
    $json = file_get_contents($composerFile);
    if (null === $packageData = json_decode($json, true)) {
        passthru(sprintf('composer validate %s', $composerFile));
        exit(1);
    }

    $repositories = $packageData['repositories'] ?? [];

    foreach ($aiPackages as $packageName => $packageInfo) {
        if (isset($packageData['require'][$packageName])
            || isset($packageData['require-dev'][$packageName])
        ) {
            $repositories[] = [
                'type' => 'path',
                'url' => $packageInfo['path'],
            ];
            $key = isset($packageData['require'][$packageName]) ? 'require' : 'require-dev';
            $packageData[$key][$packageName] = '@dev';
        }
    }

    if ($repositories) {
        $packageData['repositories'] = $repositories;
    }

    $json = json_encode($packageData, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
    file_put_contents($composerFile, $json."\n");
}
