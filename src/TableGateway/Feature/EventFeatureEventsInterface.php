<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature;

/**
 * EventFeature event constants.
 *
 * This moves the constants introduced in {@link https://github.com/zendframework/zf2/pull/7066}
 * into a separate interface that EventFeature implements; the change keeps
 * backwards compatibility, while simultaneously removing the need to add
 * another hard dependency to the component.
 *
 * @api
 */
interface EventFeatureEventsInterface
{
    public const string EVENT_PRE_INITIALIZE  = 'preInitialize';
    public const string EVENT_POST_INITIALIZE = 'postInitialize';

    public const string EVENT_PRE_SELECT  = 'preSelect';
    public const string EVENT_POST_SELECT = 'postSelect';

    public const string EVENT_PRE_INSERT  = 'preInsert';
    public const string EVENT_POST_INSERT = 'postInsert';

    public const string EVENT_PRE_DELETE  = 'preDelete';
    public const string EVENT_POST_DELETE = 'postDelete';

    public const string EVENT_PRE_UPDATE  = 'preUpdate';
    public const string EVENT_POST_UPDATE = 'postUpdate';
}
