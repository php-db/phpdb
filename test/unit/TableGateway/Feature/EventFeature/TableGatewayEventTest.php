<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\Feature\EventFeature;

use PhpDb\TableGateway\AbstractTableGateway;
use PhpDb\TableGateway\Feature\EventFeature\TableGatewayEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class TableGatewayEventTest extends TestCase
{
    private TableGatewayEvent $event;

    #[Test]
    public function getParamWithDefault(): void
    {
        $result = $this->event->getParam('nonExistent', 'defaultValue');

        static::assertSame('defaultValue', $result);
    }

    #[Test]
    public function propagationIsStoppedAlwaysReturnsFalse(): void
    {
        /** @phpstan-ignore staticMethod.impossibleType */
        static::assertFalse($this->event->propagationIsStopped());

        $this->event->stopPropagation(true);

        // Still returns false as per implementation
        /** @phpstan-ignore staticMethod.impossibleType */
        static::assertFalse($this->event->propagationIsStopped());
    }

    #[Test]
    public function setNameAndGetName(): void
    {
        static::assertNull($this->event->getName());

        $this->event->setName('test.event');

        static::assertSame('test.event', $this->event->getName());
    }

    #[Test]
    public function setParamAndGetParam(): void
    {
        static::assertNull($this->event->getParam('unknown'));
        static::assertSame('default', $this->event->getParam('unknown', 'default'));

        $this->event->setParam('myParam', 'myValue');

        static::assertSame('myValue', $this->event->getParam('myParam'));
    }

    #[Test]
    public function setParamsAndGetParams(): void
    {
        static::assertEquals([], $this->event->getParams());

        $params = ['key1' => 'value1', 'key2' => 'value2'];
        $this->event->setParams($params);

        static::assertEquals($params, $this->event->getParams());
    }

    #[Test]
    public function setParamsWithObject(): void
    {
        $params      = new stdClass();
        $params->key = 'value';

        $this->event->setParams($params);

        static::assertSame($params, $this->event->getParams());
    }

    #[Test]
    public function setTargetAndGetTarget(): void
    {
        /** @var AbstractTableGateway&MockObject $tableGateway */
        $tableGateway = $this->getMockBuilder(AbstractTableGateway::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->event->setTarget($tableGateway);

        static::assertSame($tableGateway, $this->event->getTarget());
    }

    #[Test]
    public function stopPropagation(): void
    {
        // stopPropagation should do nothing, just ensure it doesn't throw
        $this->event->stopPropagation(true);
        $this->event->stopPropagation(false);

        /** @phpstan-ignore staticMethod.impossibleType */
        static::assertFalse($this->event->propagationIsStopped());
    }

    protected function setUp(): void
    {
        $this->event = new TableGatewayEvent();
    }
}
