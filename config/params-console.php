<?php

declare(strict_types=1);

use Yiisoft\Yii\Event\Command\DebugEventsCommand;

return [
    'yiisoft/yii-event' => [
        'eventsConfigGroup' => 'events-console',
    ],
    'yiisoft/yii-debug' => [
        'ignoredCommands' => [
            'debug:events',
        ],
    ],
    'yiisoft/yii-console' => [
        'debug:events' => DebugEventsCommand::class,
    ],
];
