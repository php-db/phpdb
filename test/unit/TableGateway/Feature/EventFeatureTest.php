<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\Feature;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerInterface;
use Override;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\ResultSet\ResultSet;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Select;
use PhpDb\Sql\Update;
use PhpDb\TableGateway\Feature\EventFeature;
use PhpDb\TableGateway\Feature\EventFeatureEventsInterface;
use PhpDb\TableGateway\TableGateway;
use PhpDbTest\TableGateway\Feature\TestAsset\TestTableGateway;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EventFeatureTest extends TestCase
{
    protected EventManager $eventManager;

    protected EventFeature $feature;

    protected EventFeature\TableGatewayEvent $event;

    protected TableGateway&MockObject $tableGateway;

    /**
     * @throws Exception
     */
    #[Override]
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
        $event = new EventFeature\TableGatewayEvent();
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
        $event = new EventFeature\TableGatewayEvent();
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
        $event = new EventFeature\TableGatewayEvent();
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
        $event = new EventFeature\TableGatewayEvent();
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
        $event = new EventFeature\TableGatewayEvent();
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
        $event = new EventFeature\TableGatewayEvent();
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
        $event = new EventFeature\TableGatewayEvent();
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
        $event = new EventFeature\TableGatewayEvent();
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
        $event = new EventFeature\TableGatewayEvent();
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
        $event = new EventFeature\TableGatewayEvent();
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

    public function testConstructorWithDefaults(): void
    {
        $feature = new EventFeature();

        self::assertInstanceOf(EventManagerInterface::class, $feature->getEventManager());
        self::assertInstanceOf(EventFeature\TableGatewayEvent::class, $feature->getEvent());
    }

    /**
     * @throws Exception
     */
    public function testPreInitializeAddsIdentifiersForCustomTableGatewayClass(): void
    {
        // Create a custom subclass of TableGateway (using anonymous class)
        $customTableGateway = new TestTableGateway();

        $eventManager = new EventManager();
        $feature      = new EventFeature($eventManager);
        $feature->setTableGateway($customTableGateway);

        // The custom class name should be added as an identifier
        $feature->preInitialize();

        // Get the identifiers from the event manager
        $identifiers = $eventManager->getIdentifiers();

        // Should contain both TableGateway::class and the anonymous class name
        self::assertContains(TableGateway::class, $identifiers);
        self::assertContains($customTableGateway::class, $identifiers);
    }
}
