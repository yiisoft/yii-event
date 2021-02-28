<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event\Tests;

use Yiisoft\Yii\Event\InvalidListenerConfigurationException;
use PHPUnit\Framework\TestCase;

class InvalidListenerConfigurationExceptionTest extends TestCase
{
    public function testGetName(): void
    {
        $exception = new InvalidListenerConfigurationException();
        self::assertNotEmpty($exception->getName());
    }

    public function testGetSolution(): void
    {
        $exception = new InvalidListenerConfigurationException();
        self::assertNotEmpty($exception->getSolution());
    }
}
