<?php

namespace Sitchco\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;

class WpComposerAutomator implements PluginInterface, EventSubscriberInterface
{
    private const MU_PLUGINS_DIR_NAME = 'mu-plugins';
    private const PLUGINS_PRO_DIR_NAME = 'plugins-pro';
    private const PLUGINS_DIR_NAME = 'plugins';
    private const AUTOLOADER_TEMPLATE = 'autoloader.php';
    private const MULOADER_TEMPLATE = 'mu-loader.php';
    private const WP_CONTENT_DIR_NAME = 'wp-content';

    /** @var Composer */
    private $composer;

    /** @var IOInterface */
    private $io;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'post-autoload-dump' => 'onPostAutoloadDump',
            'pre-install-cmd' => 'onPreInstall',
            'post-install-cmd' => 'onPostInstall',
        ];
    }

    /**
     * Handles the 'post-autoload-dump' event in Composer.
     *
     * @param Event $event The Composer event that triggers the function.
     */
    public function onPostAutoloadDump(Event $event): void
    {
        $wpContentPath = $this->getWpContentPath();
        if (! $wpContentPath) {
            $event->getIO()->write("Not inside a wp-content directory.");

            return;
        }

        $muPluginsDir = $wpContentPath . '/' . self::MU_PLUGINS_DIR_NAME;

        if (! $this->ensureDirectoryExists($muPluginsDir, $event)) {
            return;
        }

        $sourceLoaderFile = __DIR__ . '/../' . self::AUTOLOADER_TEMPLATE;
        $loaderFilePath = $muPluginsDir . '/' . self::AUTOLOADER_TEMPLATE;

        if (! file_exists($sourceLoaderFile)) {
            $event->getIO()->write("Source autoloader.php does not exist.");

            return;
        }

        if (! copy($sourceLoaderFile, $loaderFilePath)) {
            $event->getIO()->write("Failed to copy autoloader.php to mu-plugins.");
            return;
        }
        $items = scandir($muPluginsDir);
        if ($items === false) {
            return;
        }

        $subdirectories = array_filter($items, function ($item) use ($muPluginsDir) {
            return $item !== '.' && $item !== '..' && is_dir($muPluginsDir . '/' . $item);
        });

        if (!empty($subdirectories)) {
            $muLoaderTemplateFile = __DIR__ . '/../' . self::MULOADER_TEMPLATE;

            if (! file_exists($muLoaderTemplateFile)) {
                return;
            }

            $muLoaderContent = file_get_contents($muLoaderTemplateFile);
            if ($muLoaderContent === false) {
                return;
            }

            file_put_contents($loaderFilePath, PHP_EOL . $muLoaderContent, FILE_APPEND);
        }
    }

    /**
     * Handles the 'pre-install-cmd' event in Composer.
     *
     * @param Event $event The Composer event that triggers the function.
     */
    public function onPreInstall(Event $event): void
    {
        $wpContentPath = $this->getWpContentPath();
        if (! $wpContentPath) {
            $event->getIO()->write("Not inside a wp-content directory.");

            return;
        }

        $pluginsProDir = $wpContentPath . '/' . self::PLUGINS_PRO_DIR_NAME;
        $pluginsDir = $wpContentPath . '/' . self::PLUGINS_DIR_NAME;

        if ($this->directoriesExist([$pluginsProDir, $pluginsDir])) {
            $this->removeConflictingPlugins($pluginsProDir, $pluginsDir, $event);
        }
    }

    /**
     * Handles the 'post-install-cmd' event in Composer.
     *
     * @param Event $event The Composer event that triggers the function.
     */
    public function onPostInstall(Event $event): void
    {
        $wpContentPath = $this->getWpContentPath();
        if (! $wpContentPath) {
            $event->getIO()->write("Not inside a wp-content directory.");

            return;
        }

        $pluginsProDir = $wpContentPath . '/' . self::PLUGINS_PRO_DIR_NAME;
        $pluginsDir = $wpContentPath . '/' . self::PLUGINS_DIR_NAME;

        if ($this->directoriesExist([$pluginsProDir, $pluginsDir])) {
            $this->copyPlugins($pluginsProDir, $pluginsDir, $event);
        }
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * Retrieves the wp-content path based on Composer's vendor directory.
     * @return string|null The wp-content path or null if not found.
     */
    private function getWpContentPath(): ?string
    {
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $wpContentPath = dirname($vendorDir);

        if (basename($wpContentPath) === self::WP_CONTENT_DIR_NAME) {
            return $wpContentPath;
        }

        return null;
    }

    /**
     * Ensures that a directory exists, attempting to create it if it doesn't.
     *
     * @param string $directory The directory path.
     * @param Event  $event     The Composer event for IO access.
     *
     * @return bool True if the directory exists or was created successfully, false otherwise.
     */
    private function ensureDirectoryExists(string $directory, Event $event): bool
    {
        if (! is_dir($directory)) {
            if (! mkdir($directory, 0755, true)) {
                $event->getIO()->write("Failed to create directory: {$directory}");

                return false;
            }
        }

        return true;
    }

    /**
     * Checks if all provided directories exist.
     *
     * @param array $directories An array of directory paths.
     *
     * @return bool True if all directories exist, false otherwise.
     */
    private function directoriesExist(array $directories): bool
    {
        foreach ($directories as $dir) {
            if (! is_dir($dir)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Removes conflicting plugins from the plugins directory.
     *
     * @param string $pluginsProDir Source plugins-pro directory.
     * @param string $pluginsDir    Target plugins directory.
     * @param Event  $event         The Composer event for IO access.
     */
    private function removeConflictingPlugins(string $pluginsProDir, string $pluginsDir, Event $event): void
    {
        $plugins = $this->getDirectoryContents($pluginsProDir);
        $event->getIO()->write(json_encode($plugins));

        foreach ($plugins as $plugin) {
            $pluginDir = $pluginsDir . '/' . $plugin;
            if (is_dir($pluginDir)) {
                $this->removeDirectory($pluginDir, $event);
            }
        }
    }

    /**
     * Copies plugins from the plugins-pro directory to the plugins directory.
     *
     * @param string $pluginsProDir Source plugins-pro directory.
     * @param string $pluginsDir    Target plugins directory.
     * @param Event  $event         The Composer event for IO access.
     */
    private function copyPlugins(string $pluginsProDir, string $pluginsDir, Event $event): void
    {
        $plugins = $this->getDirectoryContents($pluginsProDir);

        foreach ($plugins as $plugin) {
            $source = $pluginsProDir . '/' . $plugin;
            $destination = $pluginsDir . '/' . $plugin;

            if (is_dir($source)) {
                $this->copyDirectory($source, $destination, $event);
            }
        }
    }

    /**
     * Retrieves the contents of a directory, excluding '.' and '..'.
     *
     * @param string $directory The directory path.
     *
     * @return array The list of directory contents.
     */
    private function getDirectoryContents(string $directory): array
    {
        return array_values(array_filter(scandir($directory), function ($item) {
            return ! in_array($item, ['.', '..'], true);
        }));
    }

    /**
     * Recursively removes a directory with error handling.
     *
     * @param string $directory The directory path.
     * @param Event  $event     The Composer event for IO access.
     */
    private function removeDirectory(string $directory, Event $event): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = $this->getDirectoryContents($directory);
        foreach ($items as $item) {
            $path = "{$directory}/{$item}";
            if (is_dir($path)) {
                $this->removeDirectory($path, $event);
            } else {
                if (! unlink($path)) {
                    $event->getIO()->write("Failed to delete file: {$path}");
                }
            }
        }

        if (! rmdir($directory)) {
            $event->getIO()->write("Failed to remove directory: {$directory}");
        }
    }

    /**
     * Recursively copies a directory with error handling.
     *
     * @param string $source      The source directory path.
     * @param string $destination The destination directory path.
     * @param Event  $event       The Composer event for IO access.
     */
    private function copyDirectory(string $source, string $destination, Event $event): void
    {
        if (! is_dir($source)) {
            $event->getIO()->write("Source directory does not exist: {$source}");

            return;
        }

        if (! is_dir($destination)) {
            if (! mkdir($destination, 0755, true)) {
                $event->getIO()->write("Failed to create directory: {$destination}");

                return;
            }
        }

        $items = $this->getDirectoryContents($source);
        foreach ($items as $item) {
            $sourcePath = "{$source}/{$item}";
            $destinationPath = "{$destination}/{$item}";

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destinationPath, $event);
            } else {
                if (! copy($sourcePath, $destinationPath)) {
                    $event->getIO()->write("Failed to copy file: {$sourcePath} to {$destinationPath}");
                }
            }
        }
    }
}