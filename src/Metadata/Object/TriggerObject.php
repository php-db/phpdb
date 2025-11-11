<?php

namespace PhpDb\Metadata\Object;

use DateTime;

class TriggerObject
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $eventManipulation;

    /** @var string */
    protected $eventObjectCatalog;

    /** @var string */
    protected $eventObjectSchema;

    /** @var string */
    protected $eventObjectTable;

    /** @var string */
    protected $actionOrder;

    /** @var string */
    protected $actionCondition;

    /** @var string */
    protected $actionStatement;

    /** @var string */
    protected $actionOrientation;

    /** @var string */
    protected $actionTiming;

    /** @var string */
    protected $actionReferenceOldTable;

    /** @var string */
    protected $actionReferenceNewTable;

    /** @var string */
    protected $actionReferenceOldRow;

    /** @var string */
    protected $actionReferenceNewRow;

    /** @var DateTime */
    protected $created;

    /**
     * Set Name.
     *
     * @param string $name
     * @return $this Provides a fluent interface
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set Event Manipulation.
     *
     * @param string $eventManipulation
     * @return $this Provides a fluent interface
     */
    public function setEventManipulation($eventManipulation)
    {
        $this->eventManipulation = $eventManipulation;
        return $this;
    }

    /**
     * Set Event Object Catalog.
     *
     * @param string $eventObjectCatalog
     * @return $this Provides a fluent interface
     */
    public function setEventObjectCatalog($eventObjectCatalog)
    {
        $this->eventObjectCatalog = $eventObjectCatalog;
        return $this;
    }

    /**
     * Set Event Object Schema.
     *
     * @param string $eventObjectSchema
     * @return $this Provides a fluent interface
     */
    public function setEventObjectSchema($eventObjectSchema)
    {
        $this->eventObjectSchema = $eventObjectSchema;
        return $this;
    }

    /**
     * Set Event Object Table.
     *
     * @param string $eventObjectTable
     * @return $this Provides a fluent interface
     */
    public function setEventObjectTable($eventObjectTable)
    {
        $this->eventObjectTable = $eventObjectTable;
        return $this;
    }

    /**
     * Set Action Order.
     *
     * @param string $actionOrder
     * @return $this Provides a fluent interface
     */
    public function setActionOrder($actionOrder)
    {
        $this->actionOrder = $actionOrder;
        return $this;
    }

    /**
     * Set Action Condition.
     *
     * @param string $actionCondition
     * @return $this Provides a fluent interface
     */
    public function setActionCondition($actionCondition)
    {
        $this->actionCondition = $actionCondition;
        return $this;
    }

    /**
     * Set Action Statement.
     *
     * @param string $actionStatement
     * @return $this Provides a fluent interface
     */
    public function setActionStatement($actionStatement)
    {
        $this->actionStatement = $actionStatement;
        return $this;
    }

    /**
     * Set Action Orientation.
     *
     * @param string $actionOrientation
     * @return $this Provides a fluent interface
     */
    public function setActionOrientation($actionOrientation)
    {
        $this->actionOrientation = $actionOrientation;
        return $this;
    }

    /**
     * Set Action Timing.
     *
     * @param string $actionTiming
     * @return $this Provides a fluent interface
     */
    public function setActionTiming($actionTiming)
    {
        $this->actionTiming = $actionTiming;
        return $this;
    }

    /**
     * Set Action Reference Old Table.
     *
     * @param string $actionReferenceOldTable
     * @return $this Provides a fluent interface
     */
    public function setActionReferenceOldTable($actionReferenceOldTable)
    {
        $this->actionReferenceOldTable = $actionReferenceOldTable;
        return $this;
    }

    /**
     * Set Action Reference New Table.
     *
     * @param string $actionReferenceNewTable
     * @return $this Provides a fluent interface
     */
    public function setActionReferenceNewTable($actionReferenceNewTable)
    {
        $this->actionReferenceNewTable = $actionReferenceNewTable;
        return $this;
    }

    /**
     * Set Action Reference Old Row.
     *
     * @param string $actionReferenceOldRow
     * @return $this Provides a fluent interface
     */
    public function setActionReferenceOldRow($actionReferenceOldRow)
    {
        $this->actionReferenceOldRow = $actionReferenceOldRow;
        return $this;
    }

    /**
     * Set Action Reference New Row.
     *
     * @param string $actionReferenceNewRow
     * @return $this Provides a fluent interface
     */
    public function setActionReferenceNewRow($actionReferenceNewRow)
    {
        $this->actionReferenceNewRow = $actionReferenceNewRow;
        return $this;
    }

    /**
     * Set Created.
     *
     * @param DateTime $created
     * @return $this Provides a fluent interface
     */
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }
}
