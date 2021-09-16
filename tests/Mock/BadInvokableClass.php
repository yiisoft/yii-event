<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event\Tests\Mock;

final class BadInvokableClass
{
    public function __construct(NotExistClass $notExistClass)
    {
    }

    public function __invoke()
    {
        // do nothing, just for instantiation checks
    }
}
