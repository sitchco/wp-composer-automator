# WP Composer Automator

WP Composer Automator is a Composer plugin designed to streamline the management of [WordPress](https://wordpress.org/) must-use (
MU) plugins and pro plugins (ie plugins that are commited to the project repository and not included via composer). By
automating the deployment of MU plugins and handling pro plugins, it ensures that essential plugins are always active
and loaded without manual intervention. WP Composer Automator leverages
the [Roots Bedrock Autoloader](https://github.com/roots/bedrock-autoloader) to efficiently handle the autoloading of MU
plugins.

## Features

- **Automated MU Plugin Deployment**: Automatically copies the `autoloader.php` file to the `mu-plugins` directory after
  Composer installation.
- **Integration with Roots Bedrock Autoloader**: Utilizes the `roots/bedrock-autoloader` package to manage the
  autoloading of MU plugins.
- **Pro Plugins Management**: Automatically handles the copying of plugins from the `plugins-pro` directory to the
  `plugins` directory after installation.
- **Conflict Resolution**: Removes conflicting plugins from the `plugins` directory before installation to prevent
  version conflicts.
- **Composer Lifecycle Integration**: Integrates with Composer's lifecycle events (`pre-install-cmd`,
  `post-install-cmd`, and `post-autoload-dump`) to ensure plugins and autoloaders are properly managed.
- **Simplified Plugin Management**: Eliminates the need for manual copying or scanning of plugin directories.

## Installation

### Prerequisites

- **PHP**: Version 7.2 or higher.
- **Composer**: Ensure you have [Composer](https://getcomposer.org/) installed globally.
- **WordPress**: A WordPress installation with the `wp-content` directory accessible.

### Composer Configuration

To use this plugin, you need to add a custom repository to your `composer.json`. Include the following in the
`repositories` section:

```json
{
    "type": "composer",
    "url": "https://packages.situationinteractive.com"
}
```

### Using Composer

1. **Require the Plugin**

   Add the `sitchco/mu-loader` plugin to your project:

   ```bash
   composer require sitchco/mu-loader:dev-master
   ```

   This will also install the `roots/bedrock-autoloader` package, as it is a dependency.

## Usage

Once installed, WP Composer Automator will automatically manage your MU plugins and pro plugins. Here's how it works:

1. **Composer Autoloader Inclusion**

   If Composer's autoloader is present in your project (`wp-content/vendor`), WP Composer Automator will include it to autoload
   classes.
2. **Roots Bedrock Autoloader Initialization**

   The `autoloader.php` script in the `mu-plugins` directory initializes the Roots Bedrock Autoloader, which handles the
   autoloading of MU plugins.

3. **Pro Plugins Management**

   WP Composer Automator manages your pro plugins stored in the `plugins-pro` directory by copying them to the `plugins` directory
   during the Composer post-install command.

   **Conflict Resolution**

   Before the installation begins, WP Composer Automator will remove any conflicting plugins from the `plugins` directory that
   exist in the `plugins-pro` directory. This ensures that there are no version conflicts between different versions of
   the same plugin.

4. **Composer Lifecycle Integration**

   WP Composer Automator integrates with Composer's lifecycle events:

    - **pre-install-cmd**: Before the installation, it removes conflicting plugins from the `plugins` directory.
    - **post-install-cmd**: After installation, it copies pro plugins from `plugins-pro` to `plugins`.
    - **post-autoload-dump**: Ensures that the `autoloader.php` is copied to the `mu-plugins` directory.

**Example Directory Structure:**

```
wp-content/
├── mu-plugins/
│   └── autoloader.php
│   └── mu-plugin-a/
│       └── plugin-file-a.php
│   └── mu-plugin-b/
│       └── plugin-file-b.php
├── plugins-pro/
│   ├── plugin-a/
│   └── plugin-b/
```

In this structure:

- **MU Plugins**: Any plugins in the `mu-plugins` directory and its immediate subdirectories will be autoloaded by the
  Roots Bedrock Autoloader.
- **Pro Plugins**: Plugins in the `plugins-pro` directory will be copied to the `plugins` directory.

## Benefits

By automating the management of MU plugins and pro plugins, WP Composer Automator simplifies the maintenance of your WordPress
installation. It provides an efficient and standardized way to load essential plugins without the overhead of manual
copying or conflict resolution.

## Notes

- **Pro Plugins Directory**: Ensure that your pro plugins are placed in the `plugins-pro` directory within `wp-content`.
- **Compatibility**: This setup is compatible with standard WordPress installations and does not require Bedrock.

## Troubleshooting

- **Composer Plugins Disabled**: If you encounter issues, verify that Composer plugins are enabled:

  ```bash
  composer config --global allow-plugins true
  ```

## Contributing

Contributions are welcome! Please submit issues or pull requests to
the [GitHub repository](https://github.com/sitchco/mu-loader).

## License

This project is licensed under the MIT License.