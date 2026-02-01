<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\ImageBinaries;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * Composer plugin to automatically install image binaries when package is installed or updated
 */
class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    private const PACKAGE_NAME = 'hryvinskyi/image-binaries';
    private const BINARIES = ['cwebp', 'cavif'];

    private Composer $composer;
    private IOInterface $io;

    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * {@inheritDoc}
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'onPackageInstall',
            PackageEvents::POST_PACKAGE_UPDATE => 'onPackageUpdate',
        ];
    }

    /**
     * Handle package install event
     *
     * @param PackageEvent $event
     * @return void
     */
    public function onPackageInstall(PackageEvent $event): void
    {
        $operation = $event->getOperation();

        if (!$operation instanceof InstallOperation) {
            return;
        }

        if ($operation->getPackage()->getName() !== self::PACKAGE_NAME) {
            return;
        }

        $this->installBinaries();
    }

    /**
     * Handle package update event
     *
     * @param PackageEvent $event
     * @return void
     */
    public function onPackageUpdate(PackageEvent $event): void
    {
        $operation = $event->getOperation();

        if (!$operation instanceof UpdateOperation) {
            return;
        }

        if ($operation->getTargetPackage()->getName() !== self::PACKAGE_NAME) {
            return;
        }

        $this->installBinaries();
    }

    /**
     * Install binaries to vendor/bin
     *
     * @return void
     */
    private function installBinaries(): void
    {
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $binDir = $this->composer->getConfig()->get('bin-dir');

        if (!is_dir($binDir)) {
            mkdir($binDir, 0755, true);
        }

        $platform = $this->getPlatform();
        $packageDir = $vendorDir . '/hryvinskyi/image-binaries';
        $binarySourceDir = $packageDir . '/binaries/' . $platform;

        $this->io->write("<info>Installing image binaries for platform: {$platform}</info>");

        if (!is_dir($binarySourceDir)) {
            $this->io->write("<warning>No binaries available for platform {$platform}</warning>");
            $this->io->write("<comment>Available platforms: " . implode(', ', $this->getAvailablePlatforms($packageDir)) . "</comment>");
            return;
        }

        foreach (self::BINARIES as $binary) {
            $sourcePath = $binarySourceDir . '/' . $binary;
            $targetPath = $binDir . '/' . $binary;

            if (!file_exists($sourcePath)) {
                $this->io->write("<warning>{$binary} not found in package for platform {$platform}</warning>");
                continue;
            }

            if (file_exists($targetPath)) {
                unlink($targetPath);
            }

            if (copy($sourcePath, $targetPath)) {
                chmod($targetPath, 0755);
                $this->io->write("<info>{$binary} installed successfully</info>");
            } else {
                $this->io->write("<error>Failed to install {$binary}</error>");
            }
        }
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
     * Get list of available platforms
     *
     * @param string $packageDir
     * @return array<string>
     */
    private function getAvailablePlatforms(string $packageDir): array
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
