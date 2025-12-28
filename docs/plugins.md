# 9. Plugins

Starting with phpMyFAQ 4.0, we have a new, currently experimental plugin system.
This system allows you to extend phpMyFAQ with new features.
The plugin system is based on the Symfony Dependency Injection component.

## 9.1 Plugin installation

Plugins are installed in the `content/plugins` directory of your phpMyFAQ installation.
The plugin directory should contain a subdirectory for each plugin, e.g. `content/plugins/HelloWorld`.
The plugin directory should contain a `HelloWorldPlugin.php` file that implements the `PluginInterface` interface.

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
