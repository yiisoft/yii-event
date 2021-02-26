<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event\Tests\Mock;

final class Event
{
    private array $registered = [];

    public function register(object $value): void
    {
        $this->registered[] = $value;
    }

    public function __invoke(object $value): void
    {
        $this->registered[] = $value;
    }

    public function registered(): array
    {
        return $this->registered;
    }
}
