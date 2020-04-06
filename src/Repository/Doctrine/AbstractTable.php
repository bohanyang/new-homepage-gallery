<?php

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
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

    public const FIELD_MAPPINGS = [];

    /** @var array */
    protected $fieldMappings;

    public const COLUMN_NAME_MAPPINGS = [];

    /** @var array */
    protected $columnNameMappings;

    abstract protected function initialize($params) : void;

    final public function __construct(AbstractPlatform $platform, ...$params)
    {
        $this->platform = $platform;
        $this->name = static::NAME;
        $this->columns = static::COLUMNS;
        $this->setFieldMappings();
        $this->setColumnNameMappings();
        $this->initialize($params);
    }

    protected function convertToPHPValue($value, string $type)
    {
        return Type::getType($type)->convertToPHPValue($value, $this->platform);
    }

    private static function getArrayType(Type $type)
    {
        $type = $type->getBindingType();

        if ($type === ParameterType::STRING) {
            return Connection::PARAM_STR_ARRAY;
        } elseif ($type === ParameterType::INTEGER) {
            return Connection::PARAM_INT_ARRAY;
        }

        throw new InvalidArgumentException('The parameter list only supports string and integer type');
    }

    public function getColumnType(string $column) : Type
    {
        if (!isset($this->columns[$column])) {
            throw new InvalidArgumentException("Column ${column} does not exist in table {$this->name}");
        }

        return Type::getType($this->columns[$column]);
    }

    public function getName() : string
    {
        return $this->name;
    }

    /** @see Connection::getBindingInfo() */
    public function getArrayParam(string $column, array $values)
    {
        $type = $this->getColumnType($column);

        foreach ($values as $i => $value) {
            $values[$i] = $type->convertToDatabaseValue($value, $this->platform);
        }

        return [$values, self::getArrayType($type)];
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
                $params[] = $value;
                $types[] = $this->getColumnType($column);
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
                $type = $this->getColumnType($column);
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

    public function setFieldMappings(array $mappings = null) : void
    {
        if ($mappings === null) {
            $mappings = static::FIELD_MAPPINGS;
        }

        $this->fieldMappings = $mappings;
    }

    public function setColumnNameMappings(array $mappings = null) : void
    {
        if ($mappings === null) {
            $mappings = static::COLUMN_NAME_MAPPINGS;
        }

        $this->columnNameMappings = $mappings;
    }
}
