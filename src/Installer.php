<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\ImageBinaries;

/**
 * Composer script handler for installing bundled image processing binaries
 */
class Installer
{
    private const BINARIES = ['cwebp', 'cavif'];

    /**
     * Install binaries via composer script
     *
     * @return void
     */
    public static function install(): void
    {
        $vendorDir = self::getVendorDir();
        $binDir = $vendorDir . '/bin';

        if (!is_dir($binDir)) {
            mkdir($binDir, 0755, true);
        }

        $platform = self::getPlatform();
        $packageDir = self::getPackageDir($vendorDir);
        $binarySourceDir = $packageDir . '/binaries/' . $platform;

        echo "Installing image binaries for platform: {$platform}\n";

        if (!is_dir($binarySourceDir)) {
            echo "Warning: No binaries available for platform {$platform}\n";
            echo "Available platforms: " . implode(', ', self::getAvailablePlatforms($packageDir)) . "\n";
            return;
        }

        foreach (self::BINARIES as $binary) {
            $sourcePath = $binarySourceDir . '/' . $binary;
            $targetPath = $binDir . '/' . $binary;

            if (!file_exists($sourcePath)) {
                echo "Warning: {$binary} not found in package for platform {$platform}\n";
                continue;
            }

            if (file_exists($targetPath)) {
                echo "{$binary} already exists, updating...\n";
                unlink($targetPath);
            }

            if (copy($sourcePath, $targetPath)) {
                chmod($targetPath, 0755);
                echo "{$binary} installed successfully\n";
            } else {
                echo "Error: Failed to install {$binary}\n";
            }
        }
    }

    /**
     * Get vendor directory path
     *
     * @return string
     */
    private static function getVendorDir(): string
    {
        // Package is at vendor/hryvinskyi/image-binaries/src/Installer.php
        return dirname(__DIR__, 3);
    }

    /**
     * Get current platform identifier
     *
     * @return string
     */
    private static function getPlatform(): string
    {
        $os = PHP_OS_FAMILY === 'Darwin' ? 'darwin' : 'linux';
        $arch = php_uname('m');

        if (in_array($arch, ['arm64', 'aarch64'], true)) {
            return $os . '-arm64';
        }

        return $os . '-x64';
    }

    /**
     * Get package directory path
     *
     * @param string $vendorDir
     * @return string
     */
    private static function getPackageDir(string $vendorDir): string
    {
        return $vendorDir . '/hryvinskyi/image-binaries';
    }

    /**
     * Get list of available platforms
     *
     * @param string $packageDir
     * @return array<string>
     */
    private static function getAvailablePlatforms(string $packageDir): array
    {
        $binariesDir = $packageDir . '/binaries';

        if (!is_dir($binariesDir)) {
            return [];
        }

        $platforms = [];
        foreach (scandir($binariesDir) as $item) {
            if ($item !== '.' && $item !== '..' && is_dir($binariesDir . '/' . $item)) {
                $platforms[] = $item;
            }
        }

        return $platforms;
    }
}
