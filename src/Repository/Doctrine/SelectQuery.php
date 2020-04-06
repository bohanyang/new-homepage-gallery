<?php

declare(strict_types=1);

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;

final class SelectQuery
{
    /** @var int */
    private $counter = 0;

    /** @var string[] */
    private $selects = [];

    /** @var array */
    private $aliasMap = [];

    /** @var AbstractPlatform */
    private $platform;

    /** @var Connection */
    private $conn;

    /** @var QueryBuilder */
    private $builder;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
        $this->platform = $conn->getDatabasePlatform();
    }

    /**
     * @return string The table name to be used in the FROM clause and the array key of results
     */
    public function addTable(AbstractTable $table, array $columns, string $tableAlias = null) : string
    {
        $tableAlias = $tableAlias === null ? '' : $tableAlias . '.';

        foreach ($columns as $column) {
            $this->addAlias($table, $tableAlias, $column);
        }

        return $table->getName();
    }

    private function addAlias(AbstractTable $table, string $tableAlias, string $column) : void
    {
        $alias = $this->platform->getSQLResultCasing('c' . $this->counter++);
        $this->selects[] = "${tableAlias}${column} ${alias}";
        $this->aliasMap[$alias] = [
            $table->getName(),
            $table->getField($column),
            $table->getColumnType($column)
        ];
    }

    public function getBuilder() : QueryBuilder
    {
        return $this->builder = $this->conn->createQueryBuilder()->select($this->selects);
    }

    public function fetchAll()
    {
        $results = $this->builder->execute()->fetchAll(FetchMode::ASSOCIATIVE);

        foreach ($results as $i => $result) {
            foreach ($this->aliasMap as $alias => [$table, $field, $type]) {
                /** @var Type $type */
                $results[$i][$table][$field] = $type->convertToPHPValue($result[$alias], $this->platform);
            }
        }

        return $results;
    }
}
