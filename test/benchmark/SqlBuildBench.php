<?php

declare(strict_types=1);

namespace PhpDbBenchmark;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use PhpDb\Adapter\Platform\Sql92;
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

    #[Revs(1000)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[BeforeMethods('setUp')]
    public function benchSimpleSelectWithoutCache(): void
    {
        $select = $this->createSimpleSelect();
        $select->getSqlString($this->platformWithoutCache);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[BeforeMethods('setUp')]
    public function benchSimpleSelectWithCache(): void
    {
        $select = $this->createSimpleSelect();
        $select->getSqlString($this->platformWithCache);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[BeforeMethods('setUp')]
    public function benchSimpleSelectWithCacheColdStart(): void
    {
        $platform = new Sql92();
        $platform->setCache(new Psr16Cache(new ArrayAdapter()));

        $select = $this->createSimpleSelect();
        $select->getSqlString($platform);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[BeforeMethods('setUp')]
    public function benchComplexSelectWithoutCache(): void
    {
        $select = $this->createComplexSelect();
        $select->getSqlString($this->platformWithoutCache);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[BeforeMethods('setUp')]
    public function benchComplexSelectWithCache(): void
    {
        $select = $this->createComplexSelect();
        $select->getSqlString($this->platformWithCache);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[BeforeMethods('setUp')]
    public function benchComplexSelectWithCacheColdStart(): void
    {
        $platform = new Sql92();
        $platform->setCache(new Psr16Cache(new ArrayAdapter()));

        $select = $this->createComplexSelect();
        $select->getSqlString($platform);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[BeforeMethods('setUp')]
    public function benchMultiJoinSelectWithoutCache(): void
    {
        $select = $this->createSelectWithMultipleJoins();
        $select->getSqlString($this->platformWithoutCache);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[BeforeMethods('setUp')]
    public function benchMultiJoinSelectWithCache(): void
    {
        $select = $this->createSelectWithMultipleJoins();
        $select->getSqlString($this->platformWithCache);
    }
}
