<?php

declare(strict_types=1);

use Yiisoft\Composer\Config\Builder;
use Yiisoft\EventDispatcher\Provider\ListenerCollection;
use Yiisoft\Yii\Event\ListenerCollectionFactory as Factory;

return [
    ListenerCollection::class => static fn (Factory $factory) => $factory->create(require Builder::path('events')),
    EventDispatcherInterface::class => Yiisoft\EventDispatcher\Dispatcher\Dispatcher::class,
    ListenerProviderInterface::class => [
        '__class' => Provider::class,
        '__construct()' => [Reference::to(ListenerCollection::class)]
    ]
];
