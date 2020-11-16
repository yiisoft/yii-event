<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event\Tests;

final class Event
{
    private array $registered = [];

    public function register(object $value): void
    {
        $this->registered[] = $value;
    }

    public function registered(): array
    {
        return $this->registered;
    }
}
