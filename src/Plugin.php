<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\ImageBinaries;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

/**
 * Composer plugin for automatic binary installation
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{
    private Composer $composer;
    private IOInterface $io;

    /**
     * @inheritDoc
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * @inheritDoc
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * @inheritDoc
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
        $vendorDir = $composer->getConfig()->get('vendor-dir');
        $binDir = $vendorDir . '/bin';

        $binaries = ['cwebp', 'cavif', 'magick'];

        foreach ($binaries as $binary) {
            $path = $binDir . '/' . $binary;
            if (file_exists($path)) {
                unlink($path);
                $io->write("<info>Removed {$binary}</info>");
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'onPostInstall',
            ScriptEvents::POST_UPDATE_CMD => 'onPostUpdate',
        ];
    }

    /**
     * Handle post-install event
     *
     * @param Event $event
     * @return void
     */
    public function onPostInstall(Event $event): void
    {
        $this->installBinaries($event);
    }

    /**
     * Handle post-update event
     *
     * @param Event $event
     * @return void
     */
    public function onPostUpdate(Event $event): void
    {
        $this->installBinaries($event);
    }

    /**
     * Install binaries
     *
     * @param Event $event
     * @return void
     */
    private function installBinaries(Event $event): void
    {
        Installer::install($event);
    }
}
