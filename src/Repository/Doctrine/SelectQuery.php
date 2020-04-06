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

    /** @var Statement */
    private $results;

    /** @var array */
    private $result;

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
        $alias = 'c' . $this->counter++;
        $this->selects[] = "${tableAlias}${column} ${alias}";
        $alias = $this->platform->getSQLResultCasing($alias);
        $this->aliasMap[$table->getName()][$table->getField($column)] = [
            $alias,
            $table->getColumnType($column)
        ];
    }

    public function getBuilder() : QueryBuilder
    {
        return $this->builder = $this->conn->createQueryBuilder()->select($this->selects);
    }

    public function execute() : void
    {
        $this->results = $this->builder->execute()->fetchAll(FetchMode::ASSOCIATIVE);
        unset($this->builder);
    }

    public function fetch()
    {
        return $this->result = $this->results->fetch();
    }

    public function getField($table, $field)
    {
        [$alias] = $this->aliasMap[$table][$field];

        return $this->result[$alias];
    }

    public function getResult(string $table)
    {
        $result = [];

        /** @var Type $type */
        foreach ($this->aliasMap[$table] as $field => [$alias, $type]) {
            if ($this->result[$alias] !== null) {
                $result[$field] = $type->convertToPHPValue($this->result[$alias], $this->platform);
            }
        }

        return $result;
    }
}
