<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event\Tests\Mock;

use Psr\Container\ContainerInterface;
use Yiisoft\Test\Support\Container\Exception\NotFoundException;

class ExceptionalContainer implements ContainerInterface
{
    public function get($id)
    {
        throw new NotFoundException("Dependency $id is not found");
    }

    public function has($id): bool
    {
        return true;
    }
}
