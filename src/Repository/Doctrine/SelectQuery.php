<?php

declare(strict_types=1);

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\QueryBuilder;

class SelectQuery
{
    private $counter = 0;

    private $selects = [];

    private $tableMap = [];

    private $columnMap = [];

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

    public function addTable(AbstractTable $table, array $columns, ?string $tableAlias = null) : string
    {
        $tableAlias = ($tableAlias === null) ? '' : $tableAlias . '.';

        foreach ($columns as $column) {
            $this->addAlias($table, $tableAlias, $column);
        }

        return $table->getName();
    }

    private function addAlias(AbstractTable $table, string $tableAlias, string $column) : void
    {
        $alias = 'c' . $this->counter++;
        $this->selects[] = "$tableAlias$column $alias";
        $alias = $this->platform->getSQLResultCasing($alias);
        $this->tableMap[$alias] = $table;
        $this->columnMap[$alias] = $column;
    }

    public function getBuilder() : QueryBuilder
    {
        return $this->builder = $this->conn->createQueryBuilder()->select($this->selects);
    }

    public function fetchAll() : array
    {
        return $this->builder->execute()->fetchAll(FetchMode::ASSOCIATIVE);
    }

    public function getData() : array
    {
        $results = $this->fetchAll();

        foreach ($results as $i => $result) {
            foreach ($result as $alias => $value) {
                if (isset($this->tableMap[$alias]) && $value !== null) {
                    /** @var AbstractTable $table */
                    $table = $this->tableMap[$alias];
                    $column = $this->columnMap[$alias];
                    $field = $table->getField($column);
                    $results[$i][$table->getName()][$field] = $table->applyResultCallback($column, $value);
                }
            }
        }

        return $results;
    }

    public function getResults() : array
    {
        $results = $this->fetchAll();

        foreach ($results as $i => $result) {
            foreach ($result as $alias => $value) {
                if (isset($this->tableMap[$alias]) && $value !== null) {
                    /** @var AbstractTable $table */
                    $table = $this->tableMap[$alias];
                    $column = $this->columnMap[$alias];
                    $results[$i][$table->getName()][$column] = $table->applyResultCallback($column, $value);
                }
            }
        }

        return $results;
    }
}
