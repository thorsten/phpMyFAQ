# 9. Plugins

With phpMyFAQ 4.0 and later, we have a new, currently experimental plugin system.
This system allows you to extend phpMyFAQ with new features.
The plugin system is based on the Symfony Dependency Injection component.

## 9.1 Plugin installation

Plugins are installed in the `content/plugins` directory of your phpMyFAQ installation.
The plugin directory should contain a subdirectory for each plugin, e.g. `content/plugins/HelloWorld`.
The plugin directory should contain a `HelloWorldPlugin.php` file that implements the `PluginInterface` interface.
If you want to remove a plugin, you can delete the plugin in the plugin directory.

## 9.2 Plugin configuration

Plugins can have configuration options, implemented via the `PluginConfigurationInterface` interface.
Configuration options can be defined in the plugin configuration class with Constructor Property Promotion by adding 
public properties.

### 9.3.1 Example configuration class

```php
class MyPluginConfiguration implements PluginConfigurationInterface
{
    public function __construct(
        public int $hooraysPerMinute = 200,
        public bool $showIcon = true,
    ) {
    }
}
```

## 9.3 Plugin development

To develop a plugin, you need to create a new directory in the `content/plugins` directory.
The main plugin class should implement the `PluginInterface` interface.
The plugin class should have a constructor that accepts the plugin manager as an argument.
The plugin manager is an instance of the `PluginManager` class.

## 9.4 Plugin uninstallation

To uninstall a plugin, you can delete the plugin directory from the `content/plugins` directory.

## 9.5 Plugin examples

phpMyFAQ comes with an example plugin that demonstrates how to use the plugin system called `HelloWorldPlugin`.

### 9.5.1 PHP code

```php
<?php

namespace App\Plugins\Plugin1;

use App\Core\PluginInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MyPlugin implements PluginInterface
{
    private $manager;

    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    public function getName(): string
    {
        return 'MyPlugin';
    }

    public function getVersion(): string
    {
        return '0.2.0';
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getConfig(): ?PluginConfigurationInterface
    {
        return null;
    }

    public function registerEvents(EventDispatcherInterface $dispatcher): void
    {
        $dispatcher->addListener('hello.world', [$this, 'onContentLoaded']);
        $dispatcher->addListener('user.login', [$this, 'onUserLogin']);
    }

    public function onContentLoaded($event): void
    {
        $content = $event->data;
        $output = "MyPlugin: Content Loaded: " . $content . "<br>";
        $event->setOutput($output);
    }

    public function onUserLogin($event): void
    {
        $user = $event->data;
        $output = "MyPlugin: User Logged In: " . $user . "<br>";
        $event->setOutput($output);
    }
}
```

### 9.5.2 Twig template

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

## 9.6 Plugin stylesheets

Plugins can provide pre-compiled CSS files that will be automatically injected into both frontend and admin pages.

### 9.6.1 Adding stylesheets to your plugin

Implement the `getStylesheets()` method in your plugin class:

```php
public function getStylesheets(): array
{
    return [
        'assets/style.css',        // Frontend styles
        'assets/admin-style.css'   // Admin-specific styles
    ];
}
```

**Important notes:**
- Paths are relative to your plugin directory
- Provide **pre-compiled CSS files** only (not SCSS)
- CSS files are loaded after core styles (can override if needed)
- Stylesheets are automatically injected into page `<head>`
- Works in both frontend and admin areas

### 9.6.2 Plugin directory structure for CSS

```
/content/plugins/YourPlugin/
├── YourPluginPlugin.php
└── assets/
    ├── style.css
    └── admin-style.css
```

## 9.7 Plugin translations

Plugins can provide translations in multiple languages that integrate seamlessly with phpMyFAQ's translation system.

### 9.7.1 Adding translations to your plugin

1. Implement the `getTranslationsPath()` method:

```php
public function getTranslationsPath(): ?string
{
    return 'translations';  // Path relative to plugin directory
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

### 9.7.2 Using plugin translations

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

### 9.7.3 Translation key format

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

### 9.7.4 Plugin directory structure for translations

```
/content/plugins/YourPlugin/
├── YourPluginPlugin.php
└── translations/
    ├── language_en.php
    ├── language_de.php
    ├── language_fr.php
    └── language_es.php
```

## 9.8 Complete plugin example with CSS and translations

See the `EnhancedExample` plugin for a complete working example demonstrating both features:

```
/content/plugins/EnhancedExample/
├── EnhancedExamplePlugin.php
├── assets/
│   ├── style.css
│   └── admin-style.css
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

## 9.9 Plugin version history

- 0.1.0: Initial version, shipped with phpMyFAQ 4.0.0
- 0.2.0: Added support for plugin configuration options, plugin stylesheets and translations, shipped with phpMyFAQ 4.1.0
