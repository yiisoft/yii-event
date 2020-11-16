<?php

declare(strict_types=1);

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Yiisoft\Composer\Config\Builder;
use Yiisoft\EventDispatcher\Provider\ListenerCollection;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Yii\Event\ListenerCollectionFactory as Factory;

return [
    ListenerCollection::class => static fn (Factory $factory) => $factory->create(require Builder::path('events')),
    EventDispatcherInterface::class => Yiisoft\EventDispatcher\Dispatcher\Dispatcher::class,
    ListenerProviderInterface::class => Provider::class,
];
