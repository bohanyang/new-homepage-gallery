<?php

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;

use function implode;

abstract class AbstractTable
{
    protected const PRIMARY_KEY_INDEX = 'setPrimaryKey';

    protected const NORMAL_INDEX = 'addIndex';

    protected const UNIQUE_INDEX = 'addUniqueIndex';

    /** @var AbstractPlatform */
    protected $platform;

    /** @var string */
    protected $name;

    /** @var array */
    protected $columns;

    /** @var array */
    protected $indexes = [];

    /** @var array */
    protected $columnOptions = [];

    /** @var array */
    protected $queryCallbacks = [];

    /** @var array */
    protected $resultCallbacks = [];

    /** @var array */
    protected $fieldMappings = [];

    /** @var array */
    protected $columnNameMappings = [];

    abstract protected function initialize($params) : void;

    final public function __construct(AbstractPlatform $platform, ...$params)
    {
        $this->platform = $platform;
        $this->name = static::NAME;
        $this->columns = static::COLUMNS;
        $this->initialize($params);
    }

    protected function convertToPHPValue($value, string $type)
    {
        return Type::getType($type)->convertToPHPValue($value, $this->platform);
    }

    protected function applyQueryCallback(string $column, $value)
    {
        if (isset($this->queryCallbacks[$column])) {
            $value = $this->{$this->queryCallbacks[$column]}($value);
        }

        return $value;
    }

    public function applyResultCallback(string $column, $value)
    {
        if (isset($this->resultCallbacks[$column])) {
            $type = $this->getColumnType($column);
            $value = $this->{$this->resultCallbacks[$column]}($value, $type);
        }

        return $value;
    }

    protected function arrayApplyQueryCallback(string $column, array $values)
    {
        if (isset($this->queryCallbacks[$column])) {
            foreach ($values as $i => $value) {
                $values[$i] = $this->{$this->queryCallbacks[$column]}($value);
            }
        }

        return $values;
    }

    protected function getArrayType(string $column)
    {
        $type = $this->getColumnType($column);

        return Type::getType($type)->getBindingType() + Connection::ARRAY_PARAM_OFFSET;
    }

    protected function getColumnType(string $column)
    {
        if (isset($this->columns[$column])) {
            return $this->columns[$column];
        }

        throw new InvalidArgumentException("Column ${column} does not exist in table {$this->name}");
    }

    public function getQueryParam(string $column, $value)
    {
        $type = $this->getColumnType($column);
        $value = $this->applyQueryCallback($column, $value);

        return [$value, $type];
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getArrayParam(string $column, array $values)
    {
        $type = $this->getArrayType($column);
        $values = $this->arrayApplyQueryCallback($column, $values);

        return [$values, $type];
    }

    protected function getColumnName(string $field) : string
    {
        return $this->columnNameMappings[$field] ?? $field;
    }

    public function getField(string $column) : string
    {
        return $this->fieldMappings[$column] ?? $column;
    }

    public function getInsertParams(array $data)
    {
        $params = [];
        $types = [];
        $columns = [];
        $placeholders = [];

        foreach ($data as $column => $value) {
            if ($value !== null) {
                $column = $this->getColumnName($column);
                [$value, $type] = $this->getQueryParam($column, $value);
                $params[] = $value;
                $types[] = $type;
                $columns[] = $column;
                $placeholders[] = '?';
            }
        }

        $columns = implode(', ', $columns);
        $placeholders = implode(', ', $placeholders);

        return [$params, $types, $columns, $placeholders];
    }

    public function getSubQueryInsertParams(array $data, array $subQueryColumns)
    {
        $params = [];
        $types = [];
        $columns = [];
        $placeholders = [];
        $values = [];
        $columnTypes = [];

        foreach ($data as $column => $value) {
            if ($value !== null) {
                $column = $this->getColumnName($column);
                [$value, $type] = $this->getQueryParam($column, $value);
                $params[] = $value;
                $values[$column] = $value;
                $types[] = $type;
                $columnTypes[$column] = $type;
                $columns[] = $column;
                $placeholders[] = '?';
            }
        }

        $columns = implode(', ', $columns);
        $placeholders = $this->platform->getDummySelectSQL(implode(', ', $placeholders));

        foreach ($subQueryColumns as $column) {
            if (!isset($values[$column])) {
                throw new InvalidArgumentException("Failed to get sub-query parameter for column ${column}");
            }

            $params[] = $values[$column];
            $types[] = $columnTypes[$column];
        }

        return [$params, $types, $columns, $placeholders];
    }

    public function getAllColumns() : array
    {
        return array_keys($this->columns);
    }

    public function addToSchema(Schema $schema) : Table
    {
        $table = $schema->createTable($this->name);

        foreach ($this->columns as $column => $type) {
            $options = $this->columnOptions[$column] ?? [];
            $table->addColumn($column, $type, $options);
        }

        foreach ($this->indexes as [$index, $columns]) {
            $table->{$index}($columns);
        }

        return $table;
    }
}
