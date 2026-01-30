<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\ImageBinaries;

use Composer\Script\Event;

/**
 * Composer script handler for installing bundled image processing binaries
 */
class Installer
{
    private const BINARIES = ['cwebp', 'cavif', 'magick'];

    /**
     * Install binaries via composer script
     *
     * @param Event $event
     * @return void
     */
    public static function install(Event $event): void
    {
        $io = $event->getIO();
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $binDir = $vendorDir . '/bin';

        if (!is_dir($binDir)) {
            mkdir($binDir, 0755, true);
        }

        $platform = self::getPlatform();
        $packageDir = self::getPackageDir($vendorDir);
        $binarySourceDir = $packageDir . '/binaries/' . $platform;

        $io->write("<info>Installing image binaries for platform: {$platform}</info>");

        if (!is_dir($binarySourceDir)) {
            $io->write("<warning>No binaries available for platform {$platform}</warning>");
            $io->write("<comment>Available platforms: " . implode(', ', self::getAvailablePlatforms($packageDir)) . "</comment>");
            return;
        }

        foreach (self::BINARIES as $binary) {
            $sourcePath = $binarySourceDir . '/' . $binary;
            $targetPath = $binDir . '/' . $binary;

            if (!file_exists($sourcePath)) {
                $io->write("<warning>{$binary} not found in package for platform {$platform}</warning>");
                continue;
            }

            if (file_exists($targetPath)) {
                $io->write("<info>{$binary} already exists, updating...</info>");
                unlink($targetPath);
            }

            if (copy($sourcePath, $targetPath)) {
                chmod($targetPath, 0755);
                $io->write("<info>{$binary} installed successfully</info>");
            } else {
                $io->write("<error>Failed to install {$binary}</error>");
            }
        }
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
