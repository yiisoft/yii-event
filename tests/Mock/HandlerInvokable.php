<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event\Tests\Mock;

final class HandlerInvokable
{
    public function __invoke()
    {
        // do nothing, just for instantiation checks
    }

    public function handle(): void
    {
        // do nothing, just for instantiation checks
    }
}
