<?php

namespace PhpDbTest\Metadata\Object;

use DateTime;
use PhpDb\Metadata\Object\TriggerObject;
use PHPUnit\Framework\TestCase;

final class TriggerObjectTest extends TestCase
{
    public function testSetNameAndGetNameWithFluentInterface(): void
    {
        $trigger = new TriggerObject();
        $result = $trigger->setName('trigger_name');

        self::assertSame($trigger, $result);
        self::assertSame('trigger_name', $trigger->getName());
    }

    public function testSetEventManipulationAndGetEventManipulationWithFluentInterface(): void
    {
        $trigger = new TriggerObject();
        $result = $trigger->setEventManipulation('INSERT');

        self::assertSame($trigger, $result);
        self::assertSame('INSERT', $trigger->getEventManipulation());
    }

    public function testSetEventObjectCatalogAndGetEventObjectCatalogWithFluentInterface(): void
    {
        $trigger = new TriggerObject();
        $result = $trigger->setEventObjectCatalog('catalog_name');

        self::assertSame($trigger, $result);
        self::assertSame('catalog_name', $trigger->getEventObjectCatalog());
    }

    public function testSetEventObjectSchemaAndGetEventObjectSchemaWithFluentInterface(): void
    {
        $trigger = new TriggerObject();
        $result = $trigger->setEventObjectSchema('schema_name');

        self::assertSame($trigger, $result);
        self::assertSame('schema_name', $trigger->getEventObjectSchema());
    }

    public function testSetEventObjectTableAndGetEventObjectTableWithFluentInterface(): void
    {
        $trigger = new TriggerObject();
        $result = $trigger->setEventObjectTable('table_name');

        self::assertSame($trigger, $result);
        self::assertSame('table_name', $trigger->getEventObjectTable());
    }

    public function testSetActionOrderAndGetActionOrderWithFluentInterface(): void
    {
        $trigger = new TriggerObject();
        $result = $trigger->setActionOrder('1');

        self::assertSame($trigger, $result);
        self::assertSame('1', $trigger->getActionOrder());
    }

    public function testSetActionConditionAndGetActionConditionWithFluentInterface(): void
    {
        $trigger = new TriggerObject();
        $result = $trigger->setActionCondition('WHEN (NEW.amount > 100)');

        self::assertSame($trigger, $result);
        self::assertSame('WHEN (NEW.amount > 100)', $trigger->getActionCondition());
    }

    public function testSetActionStatementAndGetActionStatementWithFluentInterface(): void
    {
        $trigger = new TriggerObject();
        $result = $trigger->setActionStatement('BEGIN ... END');

        self::assertSame($trigger, $result);
        self::assertSame('BEGIN ... END', $trigger->getActionStatement());
    }

    public function testSetActionOrientationAndGetActionOrientationWithFluentInterface(): void
    {
        $trigger = new TriggerObject();
        $result = $trigger->setActionOrientation('ROW');

        self::assertSame($trigger, $result);
        self::assertSame('ROW', $trigger->getActionOrientation());
    }

    public function testSetActionTimingAndGetActionTimingWithFluentInterface(): void
    {
        $trigger = new TriggerObject();
        $result = $trigger->setActionTiming('BEFORE');

        self::assertSame($trigger, $result);
        self::assertSame('BEFORE', $trigger->getActionTiming());
    }

    public function testSetActionReferenceOldTableAndGetActionReferenceOldTableWithFluentInterface(): void
    {
        $trigger = new TriggerObject();
        $result = $trigger->setActionReferenceOldTable('old_table');

        self::assertSame($trigger, $result);
        self::assertSame('old_table', $trigger->getActionReferenceOldTable());
    }

    public function testSetActionReferenceNewTableAndGetActionReferenceNewTableWithFluentInterface(): void
    {
        $trigger = new TriggerObject();
        $result = $trigger->setActionReferenceNewTable('new_table');

        self::assertSame($trigger, $result);
        self::assertSame('new_table', $trigger->getActionReferenceNewTable());
    }

    public function testSetActionReferenceOldRowAndGetActionReferenceOldRowWithFluentInterface(): void
    {
        $trigger = new TriggerObject();
        $result = $trigger->setActionReferenceOldRow('OLD');

        self::assertSame($trigger, $result);
        self::assertSame('OLD', $trigger->getActionReferenceOldRow());
    }

    public function testSetActionReferenceNewRowAndGetActionReferenceNewRowWithFluentInterface(): void
    {
        $trigger = new TriggerObject();
        $result = $trigger->setActionReferenceNewRow('NEW');

        self::assertSame($trigger, $result);
        self::assertSame('NEW', $trigger->getActionReferenceNewRow());
    }

    public function testSetCreatedAndGetCreatedWithFluentInterface(): void
    {
        $trigger = new TriggerObject();
        $dateTime = new DateTime('2025-01-01 12:00:00');
        $result = $trigger->setCreated($dateTime);

        self::assertSame($trigger, $result);
        self::assertSame($dateTime, $trigger->getCreated());
    }

    public function testSetCreatedWithDifferentDateTime(): void
    {
        $trigger = new TriggerObject();
        $dateTime1 = new DateTime('2025-01-01 12:00:00');
        $dateTime2 = new DateTime('2025-12-31 23:59:59');

        $trigger->setCreated($dateTime1);
        self::assertSame($dateTime1, $trigger->getCreated());

        $trigger->setCreated($dateTime2);
        self::assertSame($dateTime2, $trigger->getCreated());
    }

    public function testNullValuesForAllProperties(): void
    {
        $trigger = new TriggerObject();

        self::assertNull($trigger->getName());
        self::assertNull($trigger->getEventManipulation());
        self::assertNull($trigger->getEventObjectCatalog());
        self::assertNull($trigger->getEventObjectSchema());
        self::assertNull($trigger->getEventObjectTable());
        self::assertNull($trigger->getActionOrder());
        self::assertNull($trigger->getActionCondition());
        self::assertNull($trigger->getActionStatement());
        self::assertNull($trigger->getActionOrientation());
        self::assertNull($trigger->getActionTiming());
        self::assertNull($trigger->getActionReferenceOldTable());
        self::assertNull($trigger->getActionReferenceNewTable());
        self::assertNull($trigger->getActionReferenceOldRow());
        self::assertNull($trigger->getActionReferenceNewRow());
        self::assertNull($trigger->getCreated());
    }

    public function testCompleteTriggerObject(): void
    {
        $trigger = new TriggerObject();
        $created = new DateTime('2025-11-13 10:30:00');

        $trigger->setName('audit_trigger')
            ->setEventManipulation('UPDATE')
            ->setEventObjectCatalog('main_catalog')
            ->setEventObjectSchema('public')
            ->setEventObjectTable('orders')
            ->setActionOrder('1')
            ->setActionCondition('WHEN (OLD.status != NEW.status)')
            ->setActionStatement('BEGIN INSERT INTO audit_log VALUES (OLD.id, NOW()); END')
            ->setActionOrientation('ROW')
            ->setActionTiming('AFTER')
            ->setActionReferenceOldTable('old_orders')
            ->setActionReferenceNewTable('new_orders')
            ->setActionReferenceOldRow('OLD')
            ->setActionReferenceNewRow('NEW')
            ->setCreated($created);

        self::assertSame('audit_trigger', $trigger->getName());
        self::assertSame('UPDATE', $trigger->getEventManipulation());
        self::assertSame('main_catalog', $trigger->getEventObjectCatalog());
        self::assertSame('public', $trigger->getEventObjectSchema());
        self::assertSame('orders', $trigger->getEventObjectTable());
        self::assertSame('1', $trigger->getActionOrder());
        self::assertSame('WHEN (OLD.status != NEW.status)', $trigger->getActionCondition());
        self::assertSame('BEGIN INSERT INTO audit_log VALUES (OLD.id, NOW()); END', $trigger->getActionStatement());
        self::assertSame('ROW', $trigger->getActionOrientation());
        self::assertSame('AFTER', $trigger->getActionTiming());
        self::assertSame('old_orders', $trigger->getActionReferenceOldTable());
        self::assertSame('new_orders', $trigger->getActionReferenceNewTable());
        self::assertSame('OLD', $trigger->getActionReferenceOldRow());
        self::assertSame('NEW', $trigger->getActionReferenceNewRow());
        self::assertSame($created, $trigger->getCreated());
    }
}