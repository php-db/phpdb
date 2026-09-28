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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EventFeatureTest extends TestCase
{
    protected EventManager $eventManager;

    protected EventFeature $feature;

    protected EventFeature\TableGatewayEvent $event;

    protected TableGateway&MockObject $tableGateway;

    #[Test]
    public function constructorWithDefaults(): void
    {
        $feature = new EventFeature();

        static::assertInstanceOf(EventManagerInterface::class, $feature->getEventManager());
        static::assertInstanceOf(EventFeature\TableGatewayEvent::class, $feature->getEvent());
    }

    #[Test]
    public function getEvent(): void
    {
        static::assertSame($this->event, $this->feature->getEvent());
    }

    #[Test]
    public function getEventManager(): void
    {
        static::assertSame($this->eventManager, $this->feature->getEventManager());
    }

    #[Test]
    public function postDelete(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = new EventFeature\TableGatewayEvent();
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_POST_DELETE,
            static function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            },
        );

        $stmt   = $this->getMockBuilder(StatementInterface::class)->getMock();
        $result = $this->getMockBuilder(ResultInterface::class)->getMock();

        $this->feature->postDelete($stmt, $result);
        static::assertTrue($closureHasRun);
        static::assertInstanceOf(TableGateway::class, $event->getTarget());
        static::assertEquals(EventFeatureEventsInterface::EVENT_POST_DELETE, $event->getName());
        static::assertSame($stmt, $event->getParam('statement'));
        static::assertSame($result, $event->getParam('result'));
    }

    #[Test]
    public function postInitialize(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = new EventFeature\TableGatewayEvent();
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_POST_INITIALIZE,
            static function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            },
        );

        $this->feature->postInitialize();
        static::assertTrue($closureHasRun);
        static::assertInstanceOf(TableGateway::class, $event->getTarget());
        static::assertEquals(EventFeatureEventsInterface::EVENT_POST_INITIALIZE, $event->getName());
    }

    #[Test]
    public function postInsert(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = new EventFeature\TableGatewayEvent();
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_POST_INSERT,
            static function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            },
        );

        $stmt   = $this->getMockBuilder(StatementInterface::class)->getMock();
        $result = $this->getMockBuilder(ResultInterface::class)->getMock();

        $this->feature->postInsert($stmt, $result);
        static::assertTrue($closureHasRun);
        static::assertInstanceOf(TableGateway::class, $event->getTarget());
        static::assertEquals(EventFeatureEventsInterface::EVENT_POST_INSERT, $event->getName());
        static::assertSame($stmt, $event->getParam('statement'));
        static::assertSame($result, $event->getParam('result'));
    }

    #[Test]
    public function postSelect(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = new EventFeature\TableGatewayEvent();
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_POST_SELECT,
            static function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            },
        );

        $stmt      = $this->getMockBuilder(StatementInterface::class)->getMock();
        $result    = $this->getMockBuilder(ResultInterface::class)->getMock();
        $resultset = $this->getMockBuilder(ResultSet::class)->getMock();

        $this->feature->postSelect($stmt, $result, $resultset);
        static::assertTrue($closureHasRun);
        static::assertInstanceOf(TableGateway::class, $event->getTarget());
        static::assertEquals(EventFeatureEventsInterface::EVENT_POST_SELECT, $event->getName());
        static::assertSame($stmt, $event->getParam('statement'));
        static::assertSame($result, $event->getParam('result'));
        static::assertSame($resultset, $event->getParam('result_set'));
    }

    #[Test]
    public function postUpdate(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = new EventFeature\TableGatewayEvent();
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_POST_UPDATE,
            static function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            },
        );

        $stmt   = $this->getMockBuilder(StatementInterface::class)->getMock();
        $result = $this->getMockBuilder(ResultInterface::class)->getMock();

        $this->feature->postUpdate($stmt, $result);
        static::assertTrue($closureHasRun);
        static::assertInstanceOf(TableGateway::class, $event->getTarget());
        static::assertEquals(EventFeatureEventsInterface::EVENT_POST_UPDATE, $event->getName());
        static::assertSame($stmt, $event->getParam('statement'));
        static::assertSame($result, $event->getParam('result'));
    }

    #[Test]
    public function preDelete(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = new EventFeature\TableGatewayEvent();
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_PRE_DELETE,
            static function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            },
        );

        $delete = $this->getMockBuilder(Delete::class)->getMock();

        $this->feature->preDelete($delete);
        static::assertTrue($closureHasRun);
        static::assertInstanceOf(TableGateway::class, $event->getTarget());
        static::assertEquals(EventFeatureEventsInterface::EVENT_PRE_DELETE, $event->getName());
        static::assertSame($delete, $event->getParam('delete'));
    }

    #[Test]
    public function preInitialize(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = new EventFeature\TableGatewayEvent();
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_PRE_INITIALIZE,
            static function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            },
        );

        $this->feature->preInitialize();
        static::assertTrue($closureHasRun);
        static::assertInstanceOf(TableGateway::class, $event->getTarget());
        static::assertEquals(EventFeatureEventsInterface::EVENT_PRE_INITIALIZE, $event->getName());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function preInitializeAddsIdentifiersForCustomTableGatewayClass(): void
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
        static::assertContains(TableGateway::class, $identifiers);
        static::assertContains($customTableGateway::class, $identifiers);
    }

    #[Test]
    public function preInsert(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = new EventFeature\TableGatewayEvent();
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_PRE_INSERT,
            static function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            },
        );

        $insert = $this->getMockBuilder(Insert::class)->getMock();

        $this->feature->preInsert($insert);
        static::assertTrue($closureHasRun);
        static::assertInstanceOf(TableGateway::class, $event->getTarget());
        static::assertEquals(EventFeatureEventsInterface::EVENT_PRE_INSERT, $event->getName());
        static::assertSame($insert, $event->getParam('insert'));
    }

    #[Test]
    public function preSelect(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = new EventFeature\TableGatewayEvent();
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_PRE_SELECT,
            static function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            },
        );

        $select = $this->getMockBuilder(Select::class)->getMock();

        $this->feature->preSelect($select);
        static::assertTrue($closureHasRun);
        static::assertInstanceOf(TableGateway::class, $event->getTarget());
        static::assertEquals(EventFeatureEventsInterface::EVENT_PRE_SELECT, $event->getName());
        static::assertSame($select, $event->getParam('select'));
    }

    #[Test]
    public function preUpdate(): void
    {
        $closureHasRun = false;

        /** @var EventFeature\TableGatewayEvent $event */
        $event = new EventFeature\TableGatewayEvent();
        $this->eventManager->attach(
            EventFeatureEventsInterface::EVENT_PRE_UPDATE,
            static function (EventFeature\TableGatewayEvent $e) use (&$closureHasRun, &$event): void {
                $event         = $e;
                $closureHasRun = true;
            },
        );

        $update = $this->getMockBuilder(Update::class)->getMock();

        $this->feature->preUpdate($update);
        static::assertTrue($closureHasRun);
        static::assertInstanceOf(TableGateway::class, $event->getTarget());
        static::assertEquals(EventFeatureEventsInterface::EVENT_PRE_UPDATE, $event->getName());
        static::assertSame($update, $event->getParam('update'));
    }

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
}
