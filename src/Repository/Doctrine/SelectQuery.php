<?php

declare(strict_types=1);

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;

final class SelectQuery
{
    private $counter = 0;

    /** @var string[] */
    private $selects = [];

    /** @var string[] */
    private $tableMap = [];

    /** @var string[] */
    private $fieldMap = [];

    /** @var Type[] */
    private $typeMap = [];

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
        $alias = 'f' . $this->counter++;
        $this->selects[] = "$tableAlias$column $alias";
        $alias = $this->platform->getSQLResultCasing($alias);
        $this->tableMap[$alias] = $table->getName();
        $this->fieldMap[$alias] = $table->getField($column);
        $this->typeMap[$alias] = $table->getColumnType($column);
    }

    public function getBuilder() : QueryBuilder
    {
        return $this->builder = $this->conn->createQueryBuilder()->select($this->selects);
    }

    public function fetchAll() : array
    {
        $results = $this->builder->execute()->fetchAll(FetchMode::ASSOCIATIVE);

        foreach ($results as $i => $result) {
            foreach ($result as $alias => $value) {
                if (isset($this->tableMap[$alias]) && $value !== null) {
                    $table = $this->tableMap[$alias];
                    $field = $this->fieldMap[$alias];
                    $value = $this->typeMap[$alias]->convertToPHPValue($value, $this->platform);
                    $results[$i][$table][$field] = $value;
                }
            }
        }

        return $results;
    }
}
