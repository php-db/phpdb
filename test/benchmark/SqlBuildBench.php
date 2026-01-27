<?php

declare(strict_types=1);

namespace PhpDbBenchmark;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Select;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

#[Groups(['sql-caching'])]
class SqlBuildBench
{
    private Sql92 $platformWithCache;
    private Sql92 $platformWithoutCache;

    #[BeforeMethods('setUp')]
    public function setUp(): void
    {
        $this->platformWithCache = new Sql92();
        $this->platformWithCache->setCache(new Psr16Cache(new ArrayAdapter()));

        $this->platformWithoutCache = new Sql92();
    }

    private function createSimpleSelect(): Select
    {
        $select = new Select('users');
        $select->columns(['id', 'name', 'email']);

        return $select;
    }

    private function createComplexSelect(): Select
    {
        $select = new Select('users');
        $select->columns(['id', 'name', 'email', 'created_at'])
            ->join('orders', 'orders.user_id = users.id', ['order_id' => 'id', 'total'])
            ->join('order_items', 'order_items.order_id = orders.id', ['item_count' => 'quantity'])
            ->where('users.status IS NOT NULL')
            ->where('orders.total > 0')
            ->order(['users.created_at DESC', 'orders.total DESC']);

        return $select;
    }

    private function createSelectWithMultipleJoins(): Select
    {
        $select = new Select('products');
        $select->columns(['id', 'name', 'sku', 'price'])
            ->join('categories', 'categories.id = products.category_id', ['category_name' => 'name'])
            ->join('brands', 'brands.id = products.brand_id', ['brand_name' => 'name'])
            ->join('inventory', 'inventory.product_id = products.id', ['stock_quantity' => 'quantity'])
            ->join('prices', 'prices.product_id = products.id', ['sale_price', 'regular_price'])
            ->order(['products.name ASC']);

        return $select;
    }

    private function createVeryComplexSelect(): Select
    {
        // Subquery for total order amount per user
        $orderTotalSubquery = new Select('orders');
        $orderTotalSubquery->columns(['total_amount' => new Expression('SUM(total)')])
            ->where('orders.user_id = users.id');

        $select = new Select('users');
        $select->columns([
            'id',
            'name',
            'email',
            'status',
            'created_at',
            'order_total' => $orderTotalSubquery,
            'order_count' => new Expression('COUNT(DISTINCT orders.id)'),
        ])
            ->join('orders', 'orders.user_id = users.id', [])
            ->join('order_items', 'order_items.order_id = orders.id', [])
            ->join('products', 'products.id = order_items.product_id', [])
            ->join('categories', 'categories.id = products.category_id', [])
            ->where('users.status IS NOT NULL')
            ->where('users.created_at > 0')
            ->group(['users.id', 'users.name', 'users.email', 'users.status', 'users.created_at'])
            ->having('COUNT(DISTINCT orders.id) > 0')
            ->order(['order_count DESC', 'users.name ASC']);

        return $select;
    }

    #[Revs(2000)]
    #[Iterations(20)]
    #[Warmup(2)]
    #[BeforeMethods('setUp')]
    public function benchSimpleSelectWithoutCache(): void
    {
        $select = $this->createSimpleSelect();
        $select->getSqlString($this->platformWithoutCache);
    }

    #[Revs(2000)]
    #[Iterations(20)]
    #[Warmup(2)]
    #[BeforeMethods('setUp')]
    public function benchSimpleSelectWithCache(): void
    {
        $select = $this->createSimpleSelect();
        $select->getSqlString($this->platformWithCache);
    }

    #[Revs(2000)]
    #[Iterations(20)]
    #[Warmup(2)]
    #[BeforeMethods('setUp')]
    public function benchSimpleSelectWithCacheColdStart(): void
    {
        $platform = new Sql92();
        $platform->setCache(new Psr16Cache(new ArrayAdapter()));

        $select = $this->createSimpleSelect();
        $select->getSqlString($platform);
    }

    #[Revs(2000)]
    #[Iterations(20)]
    #[Warmup(2)]
    #[BeforeMethods('setUp')]
    public function benchComplexSelectWithoutCache(): void
    {
        $select = $this->createComplexSelect();
        $select->getSqlString($this->platformWithoutCache);
    }

    #[Revs(2000)]
    #[Iterations(20)]
    #[Warmup(2)]
    #[BeforeMethods('setUp')]
    public function benchComplexSelectWithCache(): void
    {
        $select = $this->createComplexSelect();
        $select->getSqlString($this->platformWithCache);
    }

    #[Revs(2000)]
    #[Iterations(20)]
    #[Warmup(2)]
    #[BeforeMethods('setUp')]
    public function benchComplexSelectWithCacheColdStart(): void
    {
        $platform = new Sql92();
        $platform->setCache(new Psr16Cache(new ArrayAdapter()));

        $select = $this->createComplexSelect();
        $select->getSqlString($platform);
    }

    #[Revs(2000)]
    #[Iterations(20)]
    #[Warmup(2)]
    #[BeforeMethods('setUp')]
    public function benchMultiJoinSelectWithoutCache(): void
    {
        $select = $this->createSelectWithMultipleJoins();
        $select->getSqlString($this->platformWithoutCache);
    }

    #[Revs(2000)]
    #[Iterations(20)]
    #[Warmup(2)]
    #[BeforeMethods('setUp')]
    public function benchMultiJoinSelectWithCache(): void
    {
        $select = $this->createSelectWithMultipleJoins();
        $select->getSqlString($this->platformWithCache);
    }

    #[Revs(2000)]
    #[Iterations(20)]
    #[Warmup(2)]
    #[BeforeMethods('setUp')]
    public function benchVeryComplexSelectWithoutCache(): void
    {
        $select = $this->createVeryComplexSelect();
        $select->getSqlString($this->platformWithoutCache);
    }

    #[Revs(2000)]
    #[Iterations(20)]
    #[Warmup(2)]
    #[BeforeMethods('setUp')]
    public function benchVeryComplexSelectWithCache(): void
    {
        $select = $this->createVeryComplexSelect();
        $select->getSqlString($this->platformWithCache);
    }

    #[Revs(2000)]
    #[Iterations(20)]
    #[Warmup(2)]
    #[BeforeMethods('setUp')]
    public function benchVeryComplexSelectWithCacheColdStart(): void
    {
        $platform = new Sql92();
        $platform->setCache(new Psr16Cache(new ArrayAdapter()));

        $select = $this->createVeryComplexSelect();
        $select->getSqlString($platform);
    }
}
