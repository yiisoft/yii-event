<?php

declare(strict_types=1);

use Yiisoft\EventDispatcher\Provider\ListenerCollection;
use Yiisoft\Yii\Event\ListenerCollectionFactory as Factory;

/** @var \Yiisoft\Config\Config $config */
/** @var array $params */

return [
    ListenerCollection::class => static fn (Factory $factory) => $factory->create($config->get($params['yiisoft/yii-event']['eventsConfigGroup'])),
];
