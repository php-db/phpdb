<?php

declare(strict_types=1);

namespace PhpDbBenchmark;

use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpDb\Sql\Argument;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Identifiers;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Argument\Values;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Predicate\Between;
use PhpDb\Sql\Predicate\In;
use PhpDb\Sql\Predicate\Operator;
use PhpDb\Sql\Select;

/**
 * Benchmarks comparing Argument::factory() static methods vs direct instantiation.
 *
 * Groups:
 *   - individual: Single argument creation (Value, Values, Identifier, Identifiers, Literal)
 *   - realistic:  Full Select query building with Arguments
 *   - factory:    Uses Argument::value(), Argument::identifier(), etc.
 *   - direct:     Uses new Value(), new Identifier(), etc.
 *
 * Run: ./vendor/bin/phpbench run benchmark --report=aggregate
 */
#[Iterations(5)]
#[Revs(10000)]
class ArgumentBench
{
    #[Groups(['factory', 'individual', 'value'])]
    public function benchValueFactory(): void
    {
        Argument::value('test string');
    }

    #[Groups(['direct', 'individual', 'value'])]
    public function benchValueDirect(): void
    {
        new Value('test string');
    }

    #[Groups(['factory', 'individual', 'values'])]
    public function benchValuesFactory(): void
    {
        Argument::values([1, 2, 3, 4, 5]);
    }

    #[Groups(['direct', 'individual', 'values'])]
    public function benchValuesDirect(): void
    {
        new Values([1, 2, 3, 4, 5]);
    }

    #[Groups(['factory', 'individual', 'identifier'])]
    public function benchIdentifierFactory(): void
    {
        Argument::identifier('column_name');
    }

    #[Groups(['direct', 'individual', 'identifier'])]
    public function benchIdentifierDirect(): void
    {
        new Identifier('column_name');
    }

    #[Groups(['factory', 'individual', 'identifiers'])]
    public function benchIdentifiersFactory(): void
    {
        Argument::identifiers(['id', 'name', 'email', 'created_at']);
    }

    #[Groups(['direct', 'individual', 'identifiers'])]
    public function benchIdentifiersDirect(): void
    {
        new Identifiers(['id', 'name', 'email', 'created_at']);
    }

    #[Groups(['factory', 'individual', 'literal'])]
    public function benchLiteralFactory(): void
    {
        Argument::literal('NOW()');
    }

    #[Groups(['direct', 'individual', 'literal'])]
    public function benchLiteralDirect(): void
    {
        new Literal('NOW()');
    }

    #[Groups(['factory', 'realistic', 'scenario1'])]
    public function benchScenario1SelectWhereOrderLimitFactory(): void
    {
        $select = new Select();
        $select->from('users')
            ->columns([
                'id',
                'name',
                'email',
                'created_at',
            ])
            ->where([
                'status' => 'active',
                new Operator('age', Operator::OP_GT, 18),
                new In('country', ['US', 'UK', 'CA']),
            ])
            ->order([
                new Expression('? DESC', [Argument::identifier('created_at')]),
            ])
            ->limit(25)
            ->offset(50);
    }

    #[Groups(['direct', 'realistic', 'scenario1'])]
    public function benchScenario1SelectWhereOrderLimitDirect(): void
    {
        $select = new Select();
        $select->from('users')
            ->columns([
                'id',
                'name',
                'email',
                'created_at',
            ])
            ->where([
                'status' => 'active',
                new Operator('age', Operator::OP_GT, 18),
                new In('country', ['US', 'UK', 'CA']),
            ])
            ->order([
                new Expression('? DESC', [new Identifier('created_at')]),
            ])
            ->limit(25)
            ->offset(50);
    }

    #[Groups(['factory', 'realistic', 'scenario2'])]
    public function benchScenario2JoinGroupHavingFactory(): void
    {
        $select = new Select();
        $select->from('categories')
            ->columns([
                'name',
                'product_count' => new Expression(
                    'COUNT(?)',
                    [Argument::identifier('products.id')]
                ),
                'avg_price'     => new Expression(
                    'AVG(?)',
                    [Argument::identifier('products.price')]
                ),
            ])
            ->join(
                'products',
                'categories.id = products.category_id',
                []
            )
            ->where(['products.is_active' => 1])
            ->group(['categories.id', 'categories.name'])
            ->having([
                new Expression(
                    'COUNT(?) > ?',
                    [Argument::identifier('products.id'), Argument::value(5)]
                ),
            ])
            ->order([
                new Expression('? DESC', [Argument::identifier('product_count')]),
            ]);
    }

    #[Groups(['direct', 'realistic', 'scenario2'])]
    public function benchScenario2JoinGroupHavingDirect(): void
    {
        $select = new Select();
        $select->from('categories')
            ->columns([
                'name',
                'product_count' => new Expression(
                    'COUNT(?)',
                    [new Identifier('products.id')]
                ),
                'avg_price'     => new Expression(
                    'AVG(?)',
                    [new Identifier('products.price')]
                ),
            ])
            ->join(
                'products',
                'categories.id = products.category_id',
                []
            )
            ->where(['products.is_active' => 1])
            ->group(['categories.id', 'categories.name'])
            ->having([
                new Expression(
                    'COUNT(?) > ?',
                    [new Identifier('products.id'), new Value(5)]
                ),
            ])
            ->order([
                new Expression('? DESC', [new Identifier('product_count')]),
            ]);
    }

    #[Groups(['factory', 'realistic', 'scenario3'])]
    public function benchScenario3ComplexMultiJoinFactory(): void
    {
        $select = new Select();
        $select->from(['o' => 'orders'])
            ->columns([
                'id',
                'order_date',
                'customer_name' => new Expression('?', [Argument::identifier('u.name')]),
                'email'         => new Expression('?', [Argument::identifier('u.email')]),
                'total'         => new Expression(
                    'SUM(? * ?)',
                    [Argument::identifier('oi.quantity'), Argument::identifier('oi.unit_price')]
                ),
            ])
            ->join(
                ['u' => 'users'],
                'o.user_id = u.id',
                []
            )
            ->join(
                ['oi' => 'order_items'],
                'o.id = oi.order_id',
                []
            )
            ->join(
                ['d' => 'discounts'],
                'o.discount_code = d.code',
                [],
                Select::JOIN_LEFT
            )
            ->where([
                new In('o.status', ['completed', 'shipped', 'processing']),
                new Between('o.order_date', '2024-01-01', '2024-12-31'),
            ])
            ->group(['o.id', 'o.order_date', 'u.name', 'u.email'])
            ->having([
                new Expression(
                    'SUM(? * ?) > ?',
                    [
                        Argument::identifier('oi.quantity'),
                        Argument::identifier('oi.unit_price'),
                        Argument::value(100.00),
                    ]
                ),
            ])
            ->order([
                new Expression('? DESC', [Argument::identifier('o.order_date')]),
                new Expression('? DESC', [Argument::identifier('total')]),
            ])
            ->limit(50);
    }

    #[Groups(['direct', 'realistic', 'scenario3'])]
    public function benchScenario3ComplexMultiJoinDirect(): void
    {
        $select = new Select();
        $select->from(['o' => 'orders'])
            ->columns([
                'id',
                'order_date',
                'customer_name' => new Expression('?', [new Identifier('u.name')]),
                'email'         => new Expression('?', [new Identifier('u.email')]),
                'total'         => new Expression(
                    'SUM(? * ?)',
                    [new Identifier('oi.quantity'), new Identifier('oi.unit_price')]
                ),
            ])
            ->join(
                ['u' => 'users'],
                'o.user_id = u.id',
                []
            )
            ->join(
                ['oi' => 'order_items'],
                'o.id = oi.order_id',
                []
            )
            ->join(
                ['d' => 'discounts'],
                'o.discount_code = d.code',
                [],
                Select::JOIN_LEFT
            )
            ->where([
                new In('o.status', ['completed', 'shipped', 'processing']),
                new Between('o.order_date', '2024-01-01', '2024-12-31'),
            ])
            ->group(['o.id', 'o.order_date', 'u.name', 'u.email'])
            ->having([
                new Expression(
                    'SUM(? * ?) > ?',
                    [
                        new Identifier('oi.quantity'),
                        new Identifier('oi.unit_price'),
                        new Value(100.00),
                    ]
                ),
            ])
            ->order([
                new Expression('? DESC', [new Identifier('o.order_date')]),
                new Expression('? DESC', [new Identifier('total')]),
            ])
            ->limit(50);
    }
}
