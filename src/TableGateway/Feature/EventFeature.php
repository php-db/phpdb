<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\EventsCapableInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Select;
use PhpDb\Sql\Update;
use PhpDb\TableGateway\TableGateway;

use function get_class;

class EventFeature extends AbstractFeature implements
    EventFeatureEventsInterface,
    EventsCapableInterface
{
    protected EventManagerInterface $eventManager;

    protected ?EventFeature\TableGatewayEvent $event;

    public function __construct(
        ?EventManagerInterface $eventManager = null,
        ?EventFeature\TableGatewayEvent $tableGatewayEvent = null
    ) {
        $this->eventManager = $eventManager instanceof EventManagerInterface
                            ? $eventManager
                            : new EventManager();

        $this->eventManager->addIdentifiers([
            TableGateway::class,
        ]);

        $this->event = $tableGatewayEvent ?: new EventFeature\TableGatewayEvent();
    }

    /**
     * Retrieve composed event manager instance
     */
    public function getEventManager(): EventManagerInterface
    {
        return $this->eventManager;
    }

    /**
     * Retrieve composed event instance
     */
    public function getEvent(): EventFeature\TableGatewayEvent
    {
        return $this->event;
    }

    /**
     * Initialize feature and trigger "preInitialize" event
     *
     * Ensures that the composed TableGateway has identifiers based on the
     * class name, and that the event target is set to the TableGateway
     * instance. It then triggers the "preInitialize" event.
     */
    public function preInitialize(): void
    {
        if (get_class($this->tableGateway) !== TableGateway::class) {
            $this->eventManager->addIdentifiers([get_class($this->tableGateway)]);
        }

        $this->event->setTarget($this->tableGateway);
        $this->event->setName(static::EVENT_PRE_INITIALIZE);
        $this->eventManager->triggerEvent($this->event);
    }

    /**
     * Trigger the "postInitialize" event
     */
    public function postInitialize(): void
    {
        $this->event->setName(static::EVENT_POST_INITIALIZE);
        $this->eventManager->triggerEvent($this->event);
    }

    /**
     * Trigger the "preSelect" event
     *
     * Triggers the "preSelect" event mapping the following parameters:
     * - $select as "select"
     */
    public function preSelect(Select $select): void
    {
        $this->event->setName(static::EVENT_PRE_SELECT);
        $this->event->setParams(['select' => $select]);
        $this->eventManager->triggerEvent($this->event);
    }

    /**
     * Trigger the "postSelect" event
     *
     * Triggers the "postSelect" event mapping the following parameters:
     * - $statement as "statement"
     * - $result as "result"
     * - $resultSet as "result_set"
     */
    public function postSelect(
        StatementInterface $statement,
        ResultInterface $result,
        ResultSetInterface $resultSet
    ): void {
        $this->event->setName(static::EVENT_POST_SELECT);
        $this->event->setParams([
            'statement'  => $statement,
            'result'     => $result,
            'result_set' => $resultSet,
        ]);
        $this->eventManager->triggerEvent($this->event);
    }

    /**
     * Trigger the "preInsert" event
     *
     * Triggers the "preInsert" event mapping the following parameters:
     * - $insert as "insert"
     */
    public function preInsert(Insert $insert): void
    {
        $this->event->setName(static::EVENT_PRE_INSERT);
        $this->event->setParams(['insert' => $insert]);
        $this->eventManager->triggerEvent($this->event);
    }

    /**
     * Trigger the "postInsert" event
     *
     * Triggers the "postInsert" event mapping the following parameters:
     * - $statement as "statement"
     * - $result as "result"
     */
    public function postInsert(StatementInterface $statement, ResultInterface $result): void
    {
        $this->event->setName(static::EVENT_POST_INSERT);
        $this->event->setParams([
            'statement' => $statement,
            'result'    => $result,
        ]);
        $this->eventManager->triggerEvent($this->event);
    }

    /**
     * Trigger the "preUpdate" event
     *
     * Triggers the "preUpdate" event mapping the following parameters:
     * - $update as "update"
     */
    public function preUpdate(Update $update): void
    {
        $this->event->setName(static::EVENT_PRE_UPDATE);
        $this->event->setParams(['update' => $update]);
        $this->eventManager->triggerEvent($this->event);
    }

    /**
     * Trigger the "postUpdate" event
     *
     * Triggers the "postUpdate" event mapping the following parameters:
     * - $statement as "statement"
     * - $result as "result"
     */
    public function postUpdate(StatementInterface $statement, ResultInterface $result): void
    {
        $this->event->setName(static::EVENT_POST_UPDATE);
        $this->event->setParams([
            'statement' => $statement,
            'result'    => $result,
        ]);
        $this->eventManager->triggerEvent($this->event);
    }

    /**
     * Trigger the "preDelete" event
     *
     * Triggers the "preDelete" event mapping the following parameters:
     * - $delete as "delete"
     */
    public function preDelete(Delete $delete): void
    {
        $this->event->setName(static::EVENT_PRE_DELETE);
        $this->event->setParams(['delete' => $delete]);
        $this->eventManager->triggerEvent($this->event);
    }

    /**
     * Trigger the "postDelete" event
     *
     * Triggers the "postDelete" event mapping the following parameters:
     * - $statement as "statement"
     * - $result as "result"
     */
    public function postDelete(StatementInterface $statement, ResultInterface $result): void
    {
        $this->event->setName(static::EVENT_POST_DELETE);
        $this->event->setParams([
            'statement' => $statement,
            'result'    => $result,
        ]);
        $this->eventManager->triggerEvent($this->event);
    }
}
