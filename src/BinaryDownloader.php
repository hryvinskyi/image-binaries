<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\ImageBinaries;

/**
 * Installs bundled image processing binaries from package to vendor/bin
 */
class BinaryDownloader
{
    private const BINARIES = ['cwebp', 'cavif'];

    private string $targetDir;
    private string $sourceDir;

    /**
     * @param string $targetDir Target directory (vendor/bin)
     * @param string $sourceDir Source directory (package binaries dir)
     */
    public function __construct(string $targetDir, string $sourceDir)
    {
        $this->targetDir = rtrim($targetDir, DIRECTORY_SEPARATOR);
        $this->sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR);
    }

    /**
     * Install all binaries for current platform
     *
     * @param bool $force Force reinstall even if binaries exist
     * @return void
     */
    public function installAll(bool $force = false): void
    {
        foreach (self::BINARIES as $binary) {
            $this->installBinary($binary, $force);
        }
    }

    /**
     * Install a specific binary
     *
     * @param string $name Binary name
     * @param bool $force Force reinstall
     * @return bool
     */
    public function installBinary(string $name, bool $force = false): bool
    {
        if (!in_array($name, self::BINARIES, true)) {
            echo "Unknown binary: {$name}\n";
            return false;
        }

        $platform = $this->getPlatform();
        $sourcePath = $this->sourceDir . '/' . $platform . '/' . $name;
        $targetPath = $this->targetDir . '/' . $name;

        if (!file_exists($sourcePath)) {
            echo "Binary {$name} not available for platform {$platform}\n";
            return false;
        }

        if (file_exists($targetPath) && !$force) {
            echo "{$name} already exists (use --force to reinstall)\n";
            return true;
        }

        if (file_exists($targetPath)) {
            unlink($targetPath);
        }

        if (!is_dir($this->targetDir)) {
            mkdir($this->targetDir, 0755, true);
        }

        if (copy($sourcePath, $targetPath)) {
            chmod($targetPath, 0755);
            echo "{$name} installed successfully\n";
            return true;
        }

        echo "Failed to install {$name}\n";
        return false;
    }

    /**
     * Get current platform identifier
     *
     * @return string
     */
    private function getPlatform(): string
    {
        $os = PHP_OS_FAMILY === 'Darwin' ? 'darwin' : 'linux';
        $arch = php_uname('m');

        if (in_array($arch, ['arm64', 'aarch64'], true)) {
            return $os . '-arm64';
        }

        return $os . '-x64';
    }

    /**
     * Get list of available binaries
     *
     * @return array<string>
     */
    public static function getAvailableBinaries(): array
    {
        return self::BINARIES;
    }
}
