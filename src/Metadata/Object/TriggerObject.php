<?php

declare(strict_types=1);

namespace PhpDb\Metadata\Object;

use DateTime;

class TriggerObject
{
    protected ?string $name = null;

    protected ?string $eventManipulation = null;

    protected ?string $eventObjectCatalog = null;

    protected ?string $eventObjectSchema = null;

    protected ?string $eventObjectTable = null;

    protected ?string $actionOrder = null;

    protected ?string $actionCondition = null;

    protected ?string $actionStatement = null;

    protected ?string $actionOrientation = null;

    protected ?string $actionTiming = null;

    protected ?string $actionReferenceOldTable = null;

    protected ?string $actionReferenceNewTable = null;

    protected ?string $actionReferenceOldRow = null;

    protected ?string $actionReferenceNewRow = null;

    protected ?DateTime $created = null;

    /**
     * Get Name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set Name.
     */
    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get Event Manipulation.
     */
    public function getEventManipulation(): ?string
    {
        return $this->eventManipulation;
    }

    /**
     * Set Event Manipulation.
     */
    public function setEventManipulation(string $eventManipulation): static
    {
        $this->eventManipulation = $eventManipulation;
        return $this;
    }

    /**
     * Get Event Object Catalog.
     */
    public function getEventObjectCatalog(): ?string
    {
        return $this->eventObjectCatalog;
    }

    /**
     * Set Event Object Catalog.
     */
    public function setEventObjectCatalog(string $eventObjectCatalog): static
    {
        $this->eventObjectCatalog = $eventObjectCatalog;
        return $this;
    }

    /**
     * Get Event Object Schema.
     */
    public function getEventObjectSchema(): ?string
    {
        return $this->eventObjectSchema;
    }

    /**
     * Set Event Object Schema.
     */
    public function setEventObjectSchema(string $eventObjectSchema): static
    {
        $this->eventObjectSchema = $eventObjectSchema;
        return $this;
    }

    /**
     * Get Event Object Table.
     */
    public function getEventObjectTable(): ?string
    {
        return $this->eventObjectTable;
    }

    /**
     * Set Event Object Table.
     */
    public function setEventObjectTable(string $eventObjectTable): static
    {
        $this->eventObjectTable = $eventObjectTable;
        return $this;
    }

    /**
     * Get Action Order.
     */
    public function getActionOrder(): ?string
    {
        return $this->actionOrder;
    }

    /**
     * Set Action Order.
     */
    public function setActionOrder(string $actionOrder): static
    {
        $this->actionOrder = $actionOrder;
        return $this;
    }

    /**
     * Get Action Condition.
     */
    public function getActionCondition(): ?string
    {
        return $this->actionCondition;
    }

    /**
     * Set Action Condition.
     */
    public function setActionCondition(?string $actionCondition): static
    {
        $this->actionCondition = $actionCondition;
        return $this;
    }

    /**
     * Get Action Statement.
     */
    public function getActionStatement(): ?string
    {
        return $this->actionStatement;
    }

    /**
     * Set Action Statement.
     */
    public function setActionStatement(string $actionStatement): static
    {
        $this->actionStatement = $actionStatement;
        return $this;
    }

    /**
     * Get Action Orientation.
     */
    public function getActionOrientation(): ?string
    {
        return $this->actionOrientation;
    }

    /**
     * Set Action Orientation.
     */
    public function setActionOrientation(string $actionOrientation): static
    {
        $this->actionOrientation = $actionOrientation;
        return $this;
    }

    /**
     * Get Action Timing.
     */
    public function getActionTiming(): ?string
    {
        return $this->actionTiming;
    }

    /**
     * Set Action Timing.
     */
    public function setActionTiming(string $actionTiming): static
    {
        $this->actionTiming = $actionTiming;
        return $this;
    }

    /**
     * Get Action Reference Old Table.
     */
    public function getActionReferenceOldTable(): ?string
    {
        return $this->actionReferenceOldTable;
    }

    /**
     * Set Action Reference Old Table.
     */
    public function setActionReferenceOldTable(?string $actionReferenceOldTable): static
    {
        $this->actionReferenceOldTable = $actionReferenceOldTable;
        return $this;
    }

    /**
     * Get Action Reference New Table.
     */
    public function getActionReferenceNewTable(): ?string
    {
        return $this->actionReferenceNewTable;
    }

    /**
     * Set Action Reference New Table.
     */
    public function setActionReferenceNewTable(?string $actionReferenceNewTable): static
    {
        $this->actionReferenceNewTable = $actionReferenceNewTable;
        return $this;
    }

    /**
     * Get Action Reference Old Row.
     */
    public function getActionReferenceOldRow(): ?string
    {
        return $this->actionReferenceOldRow;
    }

    /**
     * Set Action Reference Old Row.
     *
     * @return $this Provides a fluent interface
     */
    public function setActionReferenceOldRow(string $actionReferenceOldRow): static
    {
        $this->actionReferenceOldRow = $actionReferenceOldRow;
        return $this;
    }

    /**
     * Get Action Reference New Row.
     */
    public function getActionReferenceNewRow(): ?string
    {
        return $this->actionReferenceNewRow;
    }

    /**
     * Set Action Reference New Row.
     *
     * @return $this Provides a fluent interface
     */
    public function setActionReferenceNewRow(string $actionReferenceNewRow): static
    {
        $this->actionReferenceNewRow = $actionReferenceNewRow;
        return $this;
    }

    /**
     * Get Created.
     */
    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    /**
     * Set Created.
     *
     * @return $this Provides a fluent interface
     */
    public function setCreated(?DateTime $created): static
    {
        $this->created = $created;
        return $this;
    }
}
