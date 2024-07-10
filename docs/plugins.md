# 7. Plugins

Starting with phpMyFAQ 4.0, we have a new, currently experimental plugin system.
This system allows you to extend phpMyFAQ with new features.
The plugin system is based on the Symfony Dependency Injection component.

## 7.1 Plugin installation

## 7.2 Plugin configuration

## 7.3 Plugin development

## 7.4 Plugin management

## 7.5 Plugin uninstallation

## 7.6 Plugin examples

### 7.6.1 PHP code

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
        return '1.0.0';
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getConfig(): array
    {
        return [
            'option1' => 'value1'
        ];
    }

    public function registerEvents(EventDispatcherInterface $dispatcher): void
    {
        $dispatcher->addListener('content.loaded', [$this, 'onContentLoaded']);
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

### 7.6.2 Twig template

´´´ html

<div>
    <h2>Content Loaded Event</h2>
    {{ phpMyFAQPlugin('content.loaded', 'Hello, World!') | raw }}
</div>

<div>
    <h2>User Login Event</h2>
    {{ phpMyFAQPlugin('user.login', 'John Doe') | raw }}
</div>
´´´
