<?php

namespace LaminasTest\Db\TableGateway\Feature;

use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Delete;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Update;
use Laminas\Db\TableGateway\Feature\EventFeature;
use Laminas\Db\TableGateway\Feature\EventFeatureEventsInterface;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\EventManager\EventManager;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventFeatureTest extends TestCase
{
    protected EventManager $eventManager;

    protected EventFeature $feature;

    protected EventFeature\TableGatewayEvent $event;

    protected TableGateway&MockObject $tableGateway;

    public function testGetEventManager(): void
    {
        self::assertSame($this->eventManager, $this->feature->getEventManager());
    }

    public function testGetEvent(): void
    {
        self::assertSame($this->event, $this->feature->getEvent());
    }

    public function testPreInitialize(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = null;
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_PRE_INITIALIZE,
            function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            }
        );

        $this->feature->preInitialize();
        self::assertTrue($closureHasRun);
        self::assertInstanceOf(TableGateway::class, $event->getTarget());
        self::assertEquals(EventFeatureEventsInterface::EVENT_PRE_INITIALIZE, $event->getName());
    }

    public function testPostInitialize(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = null;
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_POST_INITIALIZE,
            function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            }
        );

        $this->feature->postInitialize();
        self::assertTrue($closureHasRun);
        self::assertInstanceOf(TableGateway::class, $event->getTarget());
        self::assertEquals(EventFeatureEventsInterface::EVENT_POST_INITIALIZE, $event->getName());
    }

    public function testPreSelect(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = null;
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_PRE_SELECT,
            function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            }
        );

        $this->feature->preSelect($select = $this->getMockBuilder(Select::class)->getMock());
        self::assertTrue($closureHasRun);
        self::assertInstanceOf(TableGateway::class, $event->getTarget());
        self::assertEquals(EventFeatureEventsInterface::EVENT_PRE_SELECT, $event->getName());
        self::assertSame($select, $event->getParam('select'));
    }

    public function testPostSelect(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = null;
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_POST_SELECT,
            function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            }
        );

        $this->feature->postSelect(
            $stmt      = $this->getMockBuilder(StatementInterface::class)->getMock(),
            $result    = $this->getMockBuilder(ResultInterface::class)->getMock(),
            $resultset = $this->getMockBuilder(ResultSet::class)->getMock()
        );
        self::assertTrue($closureHasRun);
        self::assertInstanceOf(TableGateway::class, $event->getTarget());
        self::assertEquals(EventFeatureEventsInterface::EVENT_POST_SELECT, $event->getName());
        self::assertSame($stmt, $event->getParam('statement'));
        self::assertSame($result, $event->getParam('result'));
        self::assertSame($resultset, $event->getParam('result_set'));
    }

    public function testPreInsert(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = null;
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_PRE_INSERT,
            function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            }
        );

        $this->feature->preInsert($insert = $this->getMockBuilder(Insert::class)->getMock());
        self::assertTrue($closureHasRun);
        self::assertInstanceOf(TableGateway::class, $event->getTarget());
        self::assertEquals(EventFeatureEventsInterface::EVENT_PRE_INSERT, $event->getName());
        self::assertSame($insert, $event->getParam('insert'));
    }

    public function testPostInsert(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = null;
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_POST_INSERT,
            function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            }
        );

        $this->feature->postInsert(
            $stmt   = $this->getMockBuilder(StatementInterface::class)->getMock(),
            $result = $this->getMockBuilder(ResultInterface::class)->getMock()
        );
        self::assertTrue($closureHasRun);
        self::assertInstanceOf(TableGateway::class, $event->getTarget());
        self::assertEquals(EventFeatureEventsInterface::EVENT_POST_INSERT, $event->getName());
        self::assertSame($stmt, $event->getParam('statement'));
        self::assertSame($result, $event->getParam('result'));
    }

    public function testPreUpdate(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = null;
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_PRE_UPDATE,
            function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            }
        );

        $this->feature->preUpdate($update = $this->getMockBuilder(Update::class)->getMock());
        self::assertTrue($closureHasRun);
        self::assertInstanceOf(TableGateway::class, $event->getTarget());
        self::assertEquals(EventFeatureEventsInterface::EVENT_PRE_UPDATE, $event->getName());
        self::assertSame($update, $event->getParam('update'));
    }

    public function testPostUpdate(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = null;
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_POST_UPDATE,
            function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            }
        );

        $this->feature->postUpdate(
            $stmt   = $this->getMockBuilder(StatementInterface::class)->getMock(),
            $result = $this->getMockBuilder(ResultInterface::class)->getMock()
        );
        self::assertTrue($closureHasRun);
        self::assertInstanceOf(TableGateway::class, $event->getTarget());
        self::assertEquals(EventFeatureEventsInterface::EVENT_POST_UPDATE, $event->getName());
        self::assertSame($stmt, $event->getParam('statement'));
        self::assertSame($result, $event->getParam('result'));
    }

    public function testPreDelete(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = null;
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_PRE_DELETE,
            function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            }
        );

        $this->feature->preDelete($delete = $this->getMockBuilder(Delete::class)->getMock());
        self::assertTrue($closureHasRun);
        self::assertInstanceOf(TableGateway::class, $event->getTarget());
        self::assertEquals(EventFeatureEventsInterface::EVENT_PRE_DELETE, $event->getName());
        self::assertSame($delete, $event->getParam('delete'));
    }

    public function testPostDelete(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = null;
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_POST_DELETE,
            function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            }
        );

        $this->feature->postDelete(
            $stmt   = $this->getMockBuilder(StatementInterface::class)->getMock(),
            $result = $this->getMockBuilder(ResultInterface::class)->getMock()
        );
        self::assertTrue($closureHasRun);
        self::assertInstanceOf(TableGateway::class, $event->getTarget());
        self::assertEquals(EventFeatureEventsInterface::EVENT_POST_DELETE, $event->getName());
        self::assertSame($stmt, $event->getParam('statement'));
        self::assertSame($result, $event->getParam('result'));
    }

    /**
     * @throws Exception
     */
    #[\Override]
    protected function setUp(): void
    {
        $this->eventManager = new EventManager();
        $this->event        = new EventFeature\TableGatewayEvent();
        $this->feature      = new EventFeature($this->eventManager, $this->event);
        $this->tableGateway = $this->getMockBuilder(TableGateway::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $this->feature->setTableGateway($this->tableGateway);

        // typically runs before everything else
        $this->feature->preInitialize();
    }
}
