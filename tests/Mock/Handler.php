<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event\Tests\Mock;

final class Handler
{
    private function __construct()
    {
    }

    public static function handleStatic(Event $event): void
    {
        // do nothing, just for instantiation checks
    }

    public function handle(): void
    {
        // do nothing, just for instantiation checks
    }
}
