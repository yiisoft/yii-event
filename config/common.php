<?php

declare(strict_types=1);

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Yiisoft\Composer\Config\Builder;
use Yiisoft\EventDispatcher\Provider\Provider;

return [
    EventDispatcherInterface::class => Yiisoft\EventDispatcher\Dispatcher\Dispatcher::class,
    ListenerProviderInterface::class => Provider::class,
];
