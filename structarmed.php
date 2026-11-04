<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;

return Architecture::define()
    ->withPresets(Preset::PSR4(), Preset::CODEQUALITY())
    ->layer('Exception', 'src/Exception')
    ->layer('Feature', 'src/Feature')
    ->layer('ConfigProvider', 'src/ConfigProvider.php')
    ->layer('Container', 'src/Container')
    ->layer('AdapterException', 'src/Adapter/Exception')
    ->layer('AdapterPlatform', 'src/Adapter/Platform')
    ->layer('AdapterProfiler', 'src/Adapter/Profiler')
    ->layer('AdapterDriverFeature', 'src/Adapter/Driver/Feature')
    ->layer('AdapterDriverPdo', 'src/Adapter/Driver/Pdo')
    ->layer('AdapterDriver', 'src/Adapter/Driver', [
        'src/Adapter/Driver/Feature',
        'src/Adapter/Driver/Pdo',
    ])
    ->layer('Adapter', 'src/Adapter', [
        'src/Adapter/Driver',
        'src/Adapter/Exception',
        'src/Adapter/Platform',
        'src/Adapter/Profiler',
    ])
    ->layer('MetadataException', 'src/Metadata/Exception')
    ->layer('MetadataObject', 'src/Metadata/Object')
    ->layer('MetadataSource', 'src/Metadata/Source')
    ->layer('Metadata', 'src/Metadata', [
        'src/Metadata/Exception',
        'src/Metadata/Object',
        'src/Metadata/Source',
    ])
    ->layer('ResultSetException', 'src/ResultSet/Exception')
    ->layer('ResultSet', 'src/ResultSet', 'src/ResultSet/Exception')
    ->layer('RowGatewayException', 'src/RowGateway/Exception')
    ->layer('RowGatewayFeature', 'src/RowGateway/Feature')
    ->layer('RowGateway', 'src/RowGateway', [
        'src/RowGateway/Exception',
        'src/RowGateway/Feature',
    ])
    ->layer('SqlException', 'src/Sql/Exception')
    ->layer('SqlArgument', 'src/Sql/Argument')
    ->layer('SqlPlatform', 'src/Sql/Platform')
    ->layer('SqlPredicateException', 'src/Sql/Predicate/Exception')
    ->layer('SqlPredicate', 'src/Sql/Predicate', 'src/Sql/Predicate/Exception')
    ->layer('SqlDdlConstraint', 'src/Sql/Ddl/Constraint')
    ->layer('SqlDdlColumn', 'src/Sql/Ddl/Column')
    ->layer('SqlDdlIndex', 'src/Sql/Ddl/Index')
    ->layer('SqlDdl', 'src/Sql/Ddl', [
        'src/Sql/Ddl/Column',
        'src/Sql/Ddl/Constraint',
        'src/Sql/Ddl/Index',
    ])
    ->layer('Sql', 'src/Sql', [
        'src/Sql/Argument',
        'src/Sql/Ddl',
        'src/Sql/Exception',
        'src/Sql/Platform',
        'src/Sql/Predicate',
    ])
    ->layer('TableGatewayException', 'src/TableGateway/Exception')
    ->layer('TableGatewayEventFeature', 'src/TableGateway/Feature/EventFeature')
    ->layer('TableGatewayFeature', 'src/TableGateway/Feature', 'src/TableGateway/Feature/EventFeature')
    ->layer('TableGateway', 'src/TableGateway', [
        'src/TableGateway/Exception',
        'src/TableGateway/Feature',
    ])
    ->ruleset([
        'Exception'                => [],
        'Feature'                  => [],
        'ConfigProvider'           => ['Adapter', 'Container', 'Sql'],
        'Container'                => ['+SqlException', 'Adapter', 'AdapterDriver', 'AdapterPlatform', 'AdapterProfiler', 'ConfigProvider', 'ResultSet', 'Sql'],
        'AdapterException'         => ['Exception'],
        'AdapterPlatform'          => ['+AdapterException', 'SqlPlatform'],
        'AdapterProfiler'          => ['+AdapterException', 'Adapter'],
        'AdapterDriverFeature'     => ['+AdapterException', 'AdapterDriver'],
        'AdapterDriverPdo'         => ['+AdapterDriver'],
        'AdapterDriver'            => ['+AdapterProfiler', 'ResultSet'],
        'Adapter'                  => ['+AdapterDriver', 'AdapterPlatform'],
        'MetadataException'        => ['Exception'],
        'MetadataObject'           => [],
        'MetadataSource'           => ['+Metadata', '+MetadataException', 'Adapter'],
        'Metadata'                 => ['MetadataObject'],
        'ResultSetException'       => ['Exception'],
        'ResultSet'                => ['+ResultSetException', 'AdapterDriver'],
        'RowGatewayException'      => ['Exception'],
        'RowGatewayFeature'        => ['+RowGateway', 'Feature'],
        'RowGateway'               => ['+RowGatewayException', 'Adapter', 'ResultSet', 'RowGatewayFeature', 'Sql'],
        'SqlException'             => ['Exception'],
        'SqlArgument'              => ['Sql'],
        'SqlPlatform'              => ['+SqlException', 'Adapter', 'AdapterPlatform', 'Sql'],
        'SqlPredicateException'    => ['+SqlException'],
        'SqlPredicate'             => ['+SqlArgument', '+SqlPredicateException'],
        'SqlDdlConstraint'         => ['+SqlArgument', '+SqlException'],
        'SqlDdlColumn'             => ['+SqlDdlConstraint'],
        'SqlDdlIndex'              => ['+SqlDdlConstraint'],
        'SqlDdl'                   => ['+SqlDdlColumn', '+SqlPlatform', 'AdapterDriver'],
        'Sql'                      => ['+SqlPredicate', '+SqlPlatform', 'AdapterDriver'],
        'TableGatewayException'    => ['Exception'],
        'TableGatewayEventFeature' => ['TableGateway'],
        'TableGatewayFeature'      => ['+TableGateway', '+TableGatewayEventFeature', '+Metadata', 'AdapterDriver', 'Feature', 'RowGateway'],
        'TableGateway'             => ['+TableGatewayException', 'Adapter', 'ResultSet', 'Sql', 'TableGatewayFeature'],
    ]);
