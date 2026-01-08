<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\Feature\EventFeature;

use PhpDb\TableGateway\AbstractTableGateway;
use PhpDb\TableGateway\Feature\EventFeature\TableGatewayEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class TableGatewayEventTest extends TestCase
{
    private TableGatewayEvent $event;

    protected function setUp(): void
    {
        $this->event = new TableGatewayEvent();
    }

    public function testSetNameAndGetName(): void
    {
        self::assertNull($this->event->getName());

        $this->event->setName('test.event');

        self::assertEquals('test.event', $this->event->getName());
    }

    public function testSetTargetAndGetTarget(): void
    {
        /** @var AbstractTableGateway&MockObject $tableGateway */
        $tableGateway = $this->getMockBuilder(AbstractTableGateway::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->event->setTarget($tableGateway);

        self::assertSame($tableGateway, $this->event->getTarget());
    }

    public function testSetParamsAndGetParams(): void
    {
        self::assertEquals([], $this->event->getParams());

        $params = ['key1' => 'value1', 'key2' => 'value2'];
        $this->event->setParams($params);

        self::assertEquals($params, $this->event->getParams());
    }

    public function testSetParamsWithObject(): void
    {
        $params      = new stdClass();
        $params->key = 'value';

        $this->event->setParams($params);

        self::assertSame($params, $this->event->getParams());
    }

    public function testSetParamAndGetParam(): void
    {
        self::assertNull($this->event->getParam('unknown'));
        self::assertEquals('default', $this->event->getParam('unknown', 'default'));

        $this->event->setParam('myParam', 'myValue');

        self::assertEquals('myValue', $this->event->getParam('myParam'));
    }

    public function testGetParamWithDefault(): void
    {
        $result = $this->event->getParam('nonExistent', 'defaultValue');

        self::assertEquals('defaultValue', $result);
    }

    public function testStopPropagation(): void
    {
        // stopPropagation should do nothing, just ensure it doesn't throw
        $this->event->stopPropagation(true);
        $this->event->stopPropagation(false);

        /** @phpstan-ignore staticMethod.impossibleType */
        self::assertFalse($this->event->propagationIsStopped());
    }

    public function testPropagationIsStoppedAlwaysReturnsFalse(): void
    {
        /** @phpstan-ignore staticMethod.impossibleType */
        self::assertFalse($this->event->propagationIsStopped());

        $this->event->stopPropagation(true);

        // Still returns false as per implementation
        /** @phpstan-ignore staticMethod.impossibleType */
        self::assertFalse($this->event->propagationIsStopped());
    }
}
