# 14. Plugins

With phpMyFAQ 4.0 and later, we have a new, currently experimental plugin system.
This system allows you to extend phpMyFAQ with new features.
The plugin system is based on the Symfony EventDispatcher and Dependency Injection components.
The core classes are located in `phpmyfaq/src/phpMyFAQ/Plugin` (`PluginInterface`, `PluginManager`, `PluginEvent`).

## 14.1 Installation

Plugins are installed in the `content/plugins` directory of your phpMyFAQ installation.
The plugin directory should contain a subdirectory for each plugin, e.g. `content/plugins/HelloWorld`.
The plugin directory must contain a `<Name>Plugin.php` file, e.g. `HelloWorldPlugin.php`, with a class of the same
name that implements the `PluginInterface` interface.
The name of the plugin directory must match the name returned by `getName()`, because stylesheets, scripts, and
translations are resolved relative to `content/plugins/<getName()>`.

You can see all installed plugins and the plugins that could not be loaded (incompatible version or missing
dependencies) in the admin backend at **Configuration → Plugins**.

In production mode, the list of discovered plugins is cached in `cache/plugins/manifest.php`. The cache is refreshed
automatically when a plugin directory is added or removed; in debug mode, the cache is not used.

## 14.2 Configuration

Plugins can have configuration options, implemented via the `PluginConfigurationInterface` interface.
`PluginConfigurationInterface` is an empty marker interface. Configuration options can be defined in the plugin
configuration class with Constructor Property Promotion by adding public properties. The plugin returns an instance
of this class from `getConfig()`, and the plugin manager stores it under the plugin name
(`PluginManager::getPluginConfig()`). There are no other options to configure plugins at this time.

### 14.2.1 Example configuration class

```php
use phpMyFAQ\Plugin\PluginConfigurationInterface;

class MyPluginConfiguration implements PluginConfigurationInterface
{
    public function __construct(
        public int $hooraysPerMinute = 200,
        public bool $showIcon = true,
    ) {
    }
}
```

The `ReadingTime` example plugin in `content/plugins/ReadingTime` shows how to use a configuration class.

## 14.3 Plugin development

To develop a plugin, you need to create a new directory in the `content/plugins` directory.
The main plugin class must implement the `PluginInterface` interface with the following methods:

| Method                                                            | Description                                       |
|-------------------------------------------------------------------|---------------------------------------------------|
| `getName(): string`                                               | Plugin name, must match the plugin directory name |
| `getVersion(): string`                                            | Plugin API version the plugin is written for      |
| `getDescription(): string`                                        | Short description                                 |
| `getAuthor(): string`                                             | Author                                            |
| `getDependencies(): array`                                        | Names of other plugins this plugin depends on     |
| `getConfig(): ?PluginConfigurationInterface`                      | Configuration object or `null`                    |
| `registerEvents(EventDispatcherInterface $eventDispatcher): void` | Registers the event listeners                     |
| `getStylesheets(): array`                                         | CSS files relative to the plugin directory        |
| `getTranslationsPath(): ?string`                                  | Translations directory or `null`                  |
| `getScripts(): array`                                             | JavaScript files relative to the plugin directory |

The `PluginManager` creates the plugin class with `new` and without constructor arguments, so the constructor must
not have required parameters.
A plugin is only loaded if `getVersion()` is equal to or greater than the plugin API version of phpMyFAQ
(currently `0.2.0`, see `System::getPluginVersion()`).
Dependencies are only met if the required plugins have been loaded before the dependent plugin.

Event listeners receive a `PluginEvent` object. Use `getData()` to read the data passed from the template and
`setOutput()` to append HTML to the output.

## 14.4 Plugin uninstallation

To uninstall a plugin, you can delete the plugin directory from the `content/plugins` directory.

## 14.5 Examples

phpMyFAQ comes with three example plugins in `content/plugins`: `HelloWorld` (minimal plugin), `ReadingTime`
(plugin configuration), and `EnhancedExample` (stylesheets, JavaScript, and translations).
The following example shows a minimal plugin.

### 14.5.1 PHP code

```php
<?php

declare(strict_types=1);

namespace phpMyFAQ\Plugin\MyPlugin;

use phpMyFAQ\Plugin\PluginEvent;
use phpMyFAQ\Plugin\PluginInterface;
use phpMyFAQ\Plugin\PluginConfigurationInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MyPlugin implements PluginInterface
{
    public function getName(): string
    {
        return 'MyPlugin';
    }

    public function getVersion(): string
    {
        return '0.2.0';
    }

    public function getDescription(): string
    {
        return 'My first phpMyFAQ plugin';
    }

    public function getAuthor(): string
    {
        return 'Jane Doe';
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getConfig(): ?PluginConfigurationInterface
    {
        return null;
    }

    public function getStylesheets(): array
    {
        return [];
    }

    public function getTranslationsPath(): ?string
    {
        return null;
    }

    public function getScripts(): array
    {
        return [];
    }

    public function registerEvents(EventDispatcherInterface $eventDispatcher): void
    {
        $eventDispatcher->addListener('hello.world', [$this, 'onContentLoaded']);
        $eventDispatcher->addListener('user.login', [$this, 'onUserLogin']);
    }

    public function onContentLoaded(PluginEvent $event): void
    {
        $content = $event->getData();
        $output = 'MyPlugin: Content Loaded: ' . $content . '<br>';
        $event->setOutput($output);
    }

    public function onUserLogin(PluginEvent $event): void
    {
        $user = $event->getData();
        $output = 'MyPlugin: User Logged In: ' . $user . '<br>';
        $event->setOutput($output);
    }
}
```

### 14.5.2 Twig template

The `phpMyFAQPlugin(eventName, data)` Twig function dispatches the event and returns the collected output.
Event names are free-form strings defined by the plugin; phpMyFAQ does not dispatch them on its own.

```twig
<div>
    <h2>Content Loaded Event</h2>
    {{ phpMyFAQPlugin('hello.world', 'Hello, World!') | raw }}
</div>
<div>
    <h2>User Login Event</h2>
    {{ phpMyFAQPlugin('user.login', 'John Doe') | raw }}
</div>
```

## 14.6 Stylesheets

Plugins can provide pre-compiled CSS files that will be automatically injected into both frontend and admin pages.

### 14.6.1 Adding stylesheets to your plugin

Implement the `getStylesheets()` method in your plugin class:

```php
public function getStylesheets(): array
{
    return [
        'assets/style.css',
        'assets/admin-style.css',
    ];
}
```

**Important notes:**
- Paths are relative to your plugin directory
- Provide **pre-compiled CSS files** only (not SCSS)
- CSS files are loaded after core styles and custom CSS (can override if needed)
- Stylesheets are automatically injected into page `<head>`
- Every returned stylesheet is loaded in both the frontend and the admin area; there is no separate list for admin
  pages, so scope admin-only rules with suitable selectors

### 14.6.2 Plugin directory structure for CSS

```
/content/plugins/YourPlugin/
├── YourPluginPlugin.php
└── assets/
    ├── style.css
    └── admin-style.css
```

## 14.7 Translations

Plugins can provide translations in multiple languages that integrate seamlessly with phpMyFAQ's translation system.

### 14.7.1 Adding translations to your plugin

1. Implement the `getTranslationsPath()` method:

```php
public function getTranslationsPath(): ?string
{
    return 'translations';  // Path relative to the plugin directory
}
```

2. Create translation files following phpMyFAQ's naming convention:

**File**: `/content/plugins/YourPlugin/translations/language_en.php`
```php
<?php
$PMF_LANG['greeting'] = 'Hello';
$PMF_LANG['message'] = 'Welcome to my plugin!';
```

**File**: `/content/plugins/YourPlugin/translations/language_de.php`
```php
<?php
$PMF_LANG['greeting'] = 'Hallo';
$PMF_LANG['message'] = 'Willkommen zu meinem Plugin!';
```

### 14.7.2 Using plugin translations

**In PHP code:**
```php
use phpMyFAQ\Translation;

$greeting = Translation::get('plugin.YourPlugin.greeting');
$message = Translation::get('plugin.YourPlugin.message');
```

**In Twig templates:**
```twig
{{ 'plugin.YourPlugin.greeting' | translate }}
{{ 'plugin.YourPlugin.message' | translate }}
```

### 14.7.3 Translation key format

Plugin translations use a namespaced format:
```
plugin.{PluginName}.{messageKey}
```

- `plugin.` - Fixed namespace prefix
- `{PluginName}` - Your plugin's name from `getName()`
- `{messageKey}` - Key from your translation file's `$PMF_LANG` array

**Important:**
- Plugin translations **cannot override** core phpMyFAQ translations
- Translations are isolated per plugin
- Automatic fallback to English if translation missing in current language
- Support all 45+ phpMyFAQ languages

### 14.7.4 Plugin directory structure for translations

```
/content/plugins/YourPlugin/
├── YourPluginPlugin.php
└── translations/
    ├── language_en.php
    ├── language_de.php
    ├── language_fr.php
    └── language_es.php
```

## 14.8 JavaScript

Plugins can provide pre-compiled JavaScript files that will be automatically injected into both frontend and admin pages.

### 14.8.1 Adding JavaScript to your plugin

Implement the `getScripts()` method in your plugin class:

```php
public function getScripts(): array
{
    return [
        'assets/script.js',
        'assets/admin-script.js',
    ];
}
```

**Important notes:**
- Paths are relative to your plugin directory
- Provide **pre-compiled JavaScript files** only (not TypeScript source)
- JavaScript files are loaded as classic scripts before the core module scripts
- Scripts are automatically injected at the end of `<body>`
- Every returned script is loaded in both the frontend and the admin area; there is no separate list for admin pages

### 14.8.2 Plugin directory structure for JavaScript

```
/content/plugins/YourPlugin/
├── YourPluginPlugin.php
└── assets/
    ├── script.js
    └── admin-script.js
```

### 14.8.3 JavaScript best practices

**Example frontend script:**
```javascript
(function() {
    'use strict';

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        console.log('My Plugin: Loaded');
        // Your plugin functionality here
    }
})();
```

**Security notes:**
- Always use strict mode (`'use strict'`)
- Lint your code with oxlint, you can run `pnpm oxlint phpmyfaq/content/plugins/YourPlugin/assets/` from the
  phpMyFAQ root
- Wrap code in IIFE to avoid global namespace pollution
- Validate and sanitize user input
- Use DOM API safely

## 14.9 Complete plugin example with CSS, JavaScript, and translations

See the `EnhancedExample` plugin for a complete working example demonstrating all features:

```
/content/plugins/EnhancedExample/
├── EnhancedExamplePlugin.php
├── assets/
│   ├── style.css
│   ├── admin-style.css
│   ├── script.js
│   └── admin-script.js
└── translations/
    ├── language_en.php
    ├── language_de.php
    └── language_fr.php
```

**Usage in Twig templates:**
```twig
{# Use the plugin event #}
{{ phpMyFAQPlugin('enhanced.greeting', 'John') | raw }}

{# Access plugin translations directly #}
<p>{{ 'plugin.EnhancedExample.adminMessage' | translate }}</p>
```

## 14.10 Plugin version history

- 0.1.0: Initial version, shipped with phpMyFAQ 4.0.0
- 0.2.0: Added support for plugin configuration, stylesheets, JavaScript, and translations, shipped with phpMyFAQ 4.1.0
  and still the current plugin API version in phpMyFAQ 4.2
