<?php
declare(strict_types=1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Migrations\Db\Adapter;

use BadMethodCallException;
use Cake\Database\Schema\TableSchema;
use InvalidArgumentException;
use Migrations\Db\AlterInstructions;
use Migrations\Db\Expression;
use Migrations\Db\Literal;
use Migrations\Db\Table\Column;
use Migrations\Db\Table\ForeignKey;
use Migrations\Db\Table\Index;
use Migrations\Db\Table\Table;
use PDOException;
use RuntimeException;
use const FILTER_VALIDATE_BOOLEAN;

/**
 * Migrations SQLite Adapter.
 */
class SqliteAdapter extends AbstractAdapter
{
    public const MEMORY = ':memory:';

    /**
     * List of supported Phinx column types with their SQL equivalents
     * some types have an affinity appended to ensure they do not receive NUMERIC affinity
     *
     * @var string[]
     */
    protected static array $supportedColumnTypes = [
        self::PHINX_TYPE_BIG_INTEGER => 'biginteger',
        self::PHINX_TYPE_BINARY => 'binary_blob',
        self::PHINX_TYPE_BINARYUUID => 'uuid_blob',
        self::PHINX_TYPE_BLOB => 'blob',
        self::PHINX_TYPE_BOOLEAN => 'boolean_integer',
        self::PHINX_TYPE_CHAR => 'char',
        self::PHINX_TYPE_DATE => 'date_text',
        self::PHINX_TYPE_DATETIME => 'datetime_text',
        self::PHINX_TYPE_DECIMAL => 'decimal',
        self::PHINX_TYPE_DOUBLE => 'double',
        self::PHINX_TYPE_FLOAT => 'float',
        self::PHINX_TYPE_INTEGER => 'integer',
        self::PHINX_TYPE_JSON => 'json_text',
        self::PHINX_TYPE_JSONB => 'jsonb_text',
        self::PHINX_TYPE_SMALL_INTEGER => 'smallinteger',
        self::PHINX_TYPE_STRING => 'varchar',
        self::PHINX_TYPE_TEXT => 'text',
        self::PHINX_TYPE_TIME => 'time_text',
        self::PHINX_TYPE_TIMESTAMP => 'timestamp_text',
        self::PHINX_TYPE_TINY_INTEGER => 'tinyinteger',
        self::PHINX_TYPE_UUID => 'uuid_text',
        self::PHINX_TYPE_VARBINARY => 'varbinary_blob',
    ];

    /**
     * List of aliases of supported column types
     *
     * @var string[]
     */
    protected static array $supportedColumnTypeAliases = [
        'varchar' => self::PHINX_TYPE_STRING,
        'tinyint' => self::PHINX_TYPE_TINY_INTEGER,
        'tinyinteger' => self::PHINX_TYPE_TINY_INTEGER,
        'smallint' => self::PHINX_TYPE_SMALL_INTEGER,
        'int' => self::PHINX_TYPE_INTEGER,
        'mediumint' => self::PHINX_TYPE_INTEGER,
        'mediuminteger' => self::PHINX_TYPE_INTEGER,
        'bigint' => self::PHINX_TYPE_BIG_INTEGER,
        'tinytext' => self::PHINX_TYPE_TEXT,
        'mediumtext' => self::PHINX_TYPE_TEXT,
        'longtext' => self::PHINX_TYPE_TEXT,
        'tinyblob' => self::PHINX_TYPE_BLOB,
        'mediumblob' => self::PHINX_TYPE_BLOB,
        'longblob' => self::PHINX_TYPE_BLOB,
        'real' => self::PHINX_TYPE_FLOAT,
    ];

    /**
     * List of known but unsupported Phinx column types
     *
     * @var string[]
     */
    protected static array $unsupportedColumnTypes = [
        self::PHINX_TYPE_BIT,
        self::PHINX_TYPE_CIDR,
        self::PHINX_TYPE_ENUM,
        self::PHINX_TYPE_FILESTREAM,
        self::PHINX_TYPE_GEOMETRY,
        self::PHINX_TYPE_INET,
        self::PHINX_TYPE_INTERVAL,
        self::PHINX_TYPE_LINESTRING,
        self::PHINX_TYPE_MACADDR,
        self::PHINX_TYPE_POINT,
        self::PHINX_TYPE_POLYGON,
        self::PHINX_TYPE_SET,
    ];

    /**
     * @var string[]
     */
    protected array $definitionsWithLimits = [
        'CHAR',
        'CHARACTER',
        'VARCHAR',
        'VARYING CHARACTER',
        'NCHAR',
        'NATIVE CHARACTER',
        'NVARCHAR',
    ];

    /**
     * @var string
     */
    protected string $suffix = '.sqlite3';

    /**
     * Indicates whether the database library version is at least the specified version
     *
     * @param string $ver The version to check against e.g. '3.28.0'
     * @return bool
     */
    public function databaseVersionAtLeast(string $ver): bool
    {
        $actual = $this->query('SELECT sqlite_version()')->fetchColumn(0);

        return version_compare($actual, $ver, '>=');
    }

    /**
     * @inheritDoc
     */
    public function setOptions(array $options): AdapterInterface
    {
        parent::setOptions($options);

        if (isset($options['suffix'])) {
            $this->suffix = $options['suffix'];
        }
        //don't "fix" the file extension if it is blank, some people
        //might want a SQLITE db file with absolutely no extension.
        if ($this->suffix !== '' && strpos($this->suffix, '.') !== 0) {
            $this->suffix = '.' . $this->suffix;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function quoteTableName($tableName): string
    {
        $driver = $this->getConnection()->getDriver();

        return $driver->quoteIdentifier($tableName);
    }

    /**
     * Generates a regular expression to match identifiers that may or
     * may not be quoted with any of the supported quotes.
     *
     * @param string $identifier The identifier to match.
     * @param bool $spacedNoQuotes Whether the non-quoted identifier requires to be surrounded by whitespace.
     * @return string
     */
    protected function possiblyQuotedIdentifierRegex(string $identifier, bool $spacedNoQuotes = true): string
    {
        $identifiers = [];
        $identifier = preg_quote($identifier, '/');

        $hasTick = str_contains($identifier, '`');
        $hasDoubleQuote = str_contains($identifier, '"');
        $hasSingleQuote = str_contains($identifier, "'");

        $identifiers[] = '\[' . $identifier . '\]';
        $identifiers[] = '`' . ($hasTick ? str_replace('`', '``', $identifier) : $identifier) . '`';
        $identifiers[] = '"' . ($hasDoubleQuote ? str_replace('"', '""', $identifier) : $identifier) . '"';
        $identifiers[] = "'" . ($hasSingleQuote ? str_replace("'", "''", $identifier) : $identifier) . "'";

        if (!$hasTick && !$hasDoubleQuote && !$hasSingleQuote) {
            if ($spacedNoQuotes) {
                $identifiers[] = "\s+$identifier\s+";
            } else {
                $identifiers[] = $identifier;
            }
        }

        return '(' . implode('|', $identifiers) . ')';
    }

    /**
     * @param string $tableName Table name
     * @param bool $quoted Whether to return the schema name and table name escaped and quoted. If quoted, the schema (if any) will also be appended with a dot
     * @return array
     */
    protected function getSchemaName(string $tableName, bool $quoted = false): array
    {
        if (preg_match("/.\.([^\.]+)$/", $tableName, $match)) {
            $table = $match[1];
            $schema = substr($tableName, 0, strlen($tableName) - strlen($match[0]) + 1);
            $result = ['schema' => $schema, 'table' => $table];
        } else {
            $result = ['schema' => '', 'table' => $tableName];
        }

        if ($quoted) {
            $result['schema'] = $result['schema'] !== '' ? $this->quoteColumnName($result['schema']) . '.' : '';
            $result['table'] = $this->quoteColumnName($result['table']);
        }

        return $result;
    }

    /**
     * Searches through all available schemata to find a table and returns an array
     * containing the bare schema name and whether the table exists at all.
     * If no schema was specified and the table does not exist the "main" schema is returned
     *
     * @param string $tableName The name of the table to find
     * @return array
     */
    protected function resolveTable(string $tableName): array
    {
        $info = $this->getSchemaName($tableName);
        if ($info['schema'] === '') {
            // if no schema is specified we search all schemata
            $rows = $this->fetchAll('PRAGMA database_list;');
            // the temp schema is always first to be searched
            $schemata = ['temp'];
            foreach ($rows as $row) {
                if (strtolower($row['name']) !== 'temp') {
                    $schemata[] = $row['name'];
                }
            }
            $defaultSchema = 'main';
        } else {
            // otherwise we search just the specified schema
            $schemata = (array)$info['schema'];
            $defaultSchema = $info['schema'];
        }

        $table = strtolower($info['table']);
        foreach ($schemata as $schema) {
            if (strtolower($schema) === 'temp') {
                $master = 'sqlite_temp_master';
            } else {
                $master = sprintf('%s.%s', $this->quoteColumnName($schema), 'sqlite_master');
            }
            $rows = [];
            try {
                $result = $this->query(
                    "SELECT name FROM {$master} WHERE type = 'table' AND lower(name) = ?",
                    [$table],
                );
                // null on error
                if ($result !== null) {
                    $rows = $result->fetchAll('assoc');
                }
            } catch (PDOException $e) {
                // an exception can occur if the schema part of the table refers to a database which is not attached
                break;
            }

            // this somewhat pedantic check with strtolower is performed because the SQL lower function may be redefined,
            // and can act on all Unicode characters if the ICU extension is loaded, while SQL identifiers are only case-insensitive for ASCII
            foreach ($rows as $row) {
                if (strtolower($row['name']) === $table) {
                    return ['schema' => $schema, 'table' => $row['name'], 'exists' => true];
                }
            }
        }

        return ['schema' => $defaultSchema, 'table' => $info['table'], 'exists' => false];
    }

    /**
     * @inheritDoc
     */
    public function hasTable(string $tableName): bool
    {
        return $this->hasCreatedTable($tableName) || $this->resolveTable($tableName)['exists'];
    }

    /**
     * @inheritDoc
     */
    public function createTable(Table $table, array $columns = [], array $indexes = []): void
    {
        // Add the default primary key
        $options = $table->getOptions();
        if (!isset($options['id']) || ($options['id'] === true)) {
            $options['id'] = 'id';
        }

        if (is_string($options['id'])) {
            // Handle id => "field_name" to support AUTO_INCREMENT
            $column = new Column();
            $column->setName($options['id'])
                   ->setType('integer')
                   ->setOptions(['identity' => true]);

            array_unshift($columns, $column);
        }

        $sql = 'CREATE TABLE ';
        $sql .= $this->quoteTableName($table->getName()) . ' (';
        if (isset($options['primary_key'])) {
            $options['primary_key'] = (array)$options['primary_key'];
        }
        $dialect = $this->getSchemaDialect();

        foreach ($columns as $column) {
            $columnData = $column->toArray();
            $sql .= $dialect->columnDefinitionSql($columnData) . ', ';
            if (isset($options['primary_key']) && $column->getIdentity()) {
                //remove column from the primary key array as it is already defined as an autoincrement
                //primary id
                $identityColumnIndex = array_search($column->getName(), $options['primary_key'], true);
                if ($identityColumnIndex !== false) {
                    unset($options['primary_key'][$identityColumnIndex]);

                    if (empty($options['primary_key'])) {
                        //The last primary key has been removed
                        unset($options['primary_key']);
                    }
                }
            }
        }

        // set the primary key(s)
        if (isset($options['primary_key'])) {
            $sql = rtrim($sql);
            $sql .= ' PRIMARY KEY (';
            if (is_string($options['primary_key'])) { // handle primary_key => 'id'
                $sql .= $this->quoteColumnName($options['primary_key']);
            } elseif (is_array($options['primary_key'])) { // handle primary_key => array('tag_id', 'resource_id')
                $sql .= implode(',', array_map([$this, 'quoteColumnName'], $options['primary_key']));
            }
            $sql .= ')';
        } else {
            $sql = substr(rtrim($sql), 0, -1); // no primary keys
        }

        $sql = rtrim($sql) . ');';
        // execute the sql
        $this->execute($sql);

        foreach ($indexes as $index) {
            $this->addIndex($table, $index);
        }

        $this->addCreatedTable($table->getName());
    }

    /**
     * {@inheritDoc}
     *
     * @throws \InvalidArgumentException
     */
    protected function getChangePrimaryKeyInstructions(Table $table, $newColumns): AlterInstructions
    {
        $instructions = new AlterInstructions();

        // Drop the existing primary key
        $primaryKey = $this->getPrimaryKey($table->getName());
        if ($primaryKey) {
            $instructions->merge(
                // FIXME: array access is a hack to make this incomplete implementation work with a correct getPrimaryKey implementation
                $this->getDropPrimaryKeyInstructions($table, $primaryKey[0]),
            );
        }

        // Add the primary key(s)
        if ($newColumns) {
            if (!is_string($newColumns)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid value for primary key: %s',
                    json_encode($newColumns),
                ));
            }

            $instructions->merge(
                $this->getAddPrimaryKeyInstructions($table, $newColumns),
            );
        }

        return $instructions;
    }

    /**
     * {@inheritDoc}
     *
     * SQLiteAdapter does not implement this functionality, and so will always throw an exception if used.
     *
     * @throws \BadMethodCallException
     */
    protected function getChangeCommentInstructions(Table $table, $newComment): AlterInstructions
    {
        throw new BadMethodCallException('SQLite does not have table comments');
    }

    /**
     * @inheritDoc
     */
    protected function getRenameTableInstructions(string $tableName, string $newTableName): AlterInstructions
    {
        $this->updateCreatedTableName($tableName, $newTableName);
        $sql = sprintf(
            'ALTER TABLE %s RENAME TO %s',
            $this->quoteTableName($tableName),
            $this->quoteTableName($newTableName),
        );

        return new AlterInstructions([], [$sql]);
    }

    /**
     * @inheritDoc
     */
    protected function getDropTableInstructions(string $tableName): AlterInstructions
    {
        $this->removeCreatedTable($tableName);
        $sql = sprintf('DROP TABLE %s', $this->quoteTableName($tableName));

        return new AlterInstructions([], [$sql]);
    }

    /**
     * @inheritDoc
     */
    public function truncateTable(string $tableName): void
    {
        $info = $this->resolveTable($tableName);
        // first try deleting the rows
        $this->execute(sprintf(
            'DELETE FROM %s.%s',
            $this->quoteColumnName($info['schema']),
            $this->quoteColumnName($info['table']),
        ));

        // assuming no error occurred, reset the autoincrement (if any)
        if ($this->hasTable($info['schema'] . '.sqlite_sequence')) {
            $sql = sprintf(
                'DELETE FROM %s.sqlite_sequence where name = ?',
                $this->quoteColumnName($info['schema']),
            );
            $this->execute($sql, [$info['table']]);
        }
    }

    /**
     * Parses a default-value expression to yield either a Literal representing
     * a string value, a string representing an expression, or some other scalar
     *
     * @param mixed $default The default-value expression to interpret
     * @param string $columnType The Phinx type of the column
     * @return mixed
     */
    protected function parseDefaultValue(mixed $default, string $columnType): mixed
    {
        if ($default === null) {
            return null;
        }

        // split the input into tokens
        $trimChars = " \t\n\r\0\x0B";
        $pattern = <<<PCRE_PATTERN
            /
                '(?:[^']|'')*'|                 # String literal
                "(?:[^"]|"")*"|                 # Standard identifier
                `(?:[^`]|``)*`|                 # MySQL identifier
                \[[^\]]*\]|                     # SQL Server identifier
                --[^\r\n]*|                     # Single-line comment
                \/\*(?:\*(?!\/)|[^\*])*\*\/|    # Multi-line comment
                [^\/\-]+|                       # Non-special characters
                .                               # Any other single character
            /sx
PCRE_PATTERN;
        preg_match_all($pattern, $default, $matches);
        // strip out any comment tokens
        $matches = array_map(function ($v) {
            return preg_match('/^(?:\/\*|--)/', $v) ? ' ' : $v;
        }, $matches[0]);
        // reconstitute the string, trimming whitespace as well as parentheses
        $defaultClean = trim(implode('', $matches));
        $defaultBare = rtrim(ltrim($defaultClean, $trimChars . '('), $trimChars . ')');

        // match the string against one of several patterns
        if ($columnType === 'text' || $columnType === 'string') {
            // string literal
            return Literal::from($default);
        } elseif (preg_match('/^CURRENT_(?:DATE|TIME|TIMESTAMP)$/i', $default)) {
            // magic date or time
            return strtoupper($default);
        } elseif (preg_match('/^[+-]?\d+$/i', $default)) {
            $int = (int)$default;
            // integer literal
            if ($columnType === self::PHINX_TYPE_BOOLEAN && ($int === 0 || $int === 1)) {
                return (bool)$int;
            } else {
                return $int;
            }
        } elseif (preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:e[+-]?\d+)?$/i', $default)) {
            // float literal
            return (float)$default;
        } elseif (preg_match('/^0x[0-9a-f]+$/i', $default)) {
            // hexadecimal literal
            return hexdec(substr($default, 2));
        } elseif (preg_match('/^null$/i', $defaultBare)) {
            // null literal
            return null;
        } elseif (preg_match('/^true|false$/i', $defaultBare)) {
            // boolean literal
            return filter_var($defaultClean, FILTER_VALIDATE_BOOLEAN);
        } else {
            // any other expression: return the expression with parentheses, but without comments
            return Expression::from($default);
        }
    }

    /**
     * Returns the name of the specified table's identity column, or null if the table has no identity
     *
     * The process of finding an identity column is somewhat convoluted as SQLite has no direct way of querying whether a given column is an alias for the table's row ID
     *
     * @param string $tableName The name of the table
     * @return string|null
     */
    protected function resolveIdentity(string $tableName): ?string
    {
        $result = null;
        // make sure the table has only one primary key column which is of type integer
        foreach ($this->getColumnData($tableName) as $col) {
            $type = strtolower($col['type']);
            if ($col['pk'] > 1) {
                // the table has a composite primary key
                return null;
            } elseif ($col['pk'] == 0) {
                // the column is not a primary key column and is thus not relevant
                continue;
            } elseif ($type !== 'integer') {
                // if the primary key's type is not exactly INTEGER, it cannot be a row ID alias
                return null;
            } else {
                // the column is a candidate for a row ID alias
                $result = $col['name'];
            }
        }
        // if there is no suitable PK column, stop now
        if ($result === null) {
            return null;
        }

        return $result;
    }

    /**
     * Get column metadata
     *
     * @param string $tableName The table to get column data from.
     * @return array
     */
    protected function getColumnData(string $tableName): array
    {
        $dialect = $this->getSchemaDialect();

        return $dialect->describeColumns($tableName);
    }

    /**
     * @inheritDoc
     */
    public function getColumns(string $tableName): array
    {
        $columns = [];
        foreach ($this->getColumnData($tableName) as $columnInfo) {
            $default = $this->parseDefaultValue($columnInfo['default'], $columnInfo['type']);
            $column = new Column();
            $column
                ->setName($columnInfo['name'])
                ->setType($columnInfo['type'])
                ->setNull($columnInfo['null'])
                ->setDefault($default)
                ->setLimit($columnInfo['length'])
                // cakephp uses precision not scale
                ->setScale($columnInfo['precision'] ?? null)
                ->setComment($columnInfo['comment'])
                ->setIdentity($columnInfo['autoIncrement'] ?? false);

            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * @inheritDoc
     */
    public function hasColumn(string $tableName, string $columnName): bool
    {
        foreach ($this->getColumnData($tableName) as $column) {
            if (strcasecmp($column['name'], $columnName) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    protected function getAddColumnInstructions(Table $table, Column $column): AlterInstructions
    {
        $tableName = $table->getName();

        $instructions = $this->beginAlterByCopyTable($tableName);

        $instructions->addPostStep(function ($state) use ($tableName, $column) {
            // we use the final column to anchor our regex to insert the new column,
            // as the alternative is unwinding all possible table constraints which
            // gets messy quickly with CHECK constraints.
            $columns = $this->getColumns($tableName);
            $dialect = $this->getSchemaDialect();
            if (!$columns) {
                return $state;
            }
            $finalColumnName = end($columns)->getName();
            $sql = preg_replace(
                sprintf(
                    "/(%s(?:\/\*.*?\*\/|\([^)]+\)|'[^']*?'|[^,])+)([,)])/",
                    $this->quoteColumnName((string)$finalColumnName),
                ),
                sprintf(
                    '$1, %s$2',
                    $dialect->columnDefinitionSql($column->toArray()),
                ),
                (string)$state['createSQL'],
                1,
            );
            $this->execute($sql);

            return $state;
        });

        $instructions->addPostStep(function ($state) use ($tableName) {
            $newState = $this->calculateNewTableColumns($tableName, false, false);

            return $newState + $state;
        });

        return $this->endAlterByCopyTable($instructions, $tableName);
    }

    /**
     * Returns the original CREATE statement for the give table
     *
     * @param string $tableName The table name to get the create statement for
     * @return string
     */
    protected function getDeclaringSql(string $tableName): string
    {
        $rows = $this->fetchAll("SELECT * FROM sqlite_master WHERE \"type\" = 'table'");

        $sql = '';
        foreach ($rows as $table) {
            if ($table['tbl_name'] === $tableName) {
                $sql = $table['sql'];
            }
        }

        $columnsInfo = $this->getColumnData($tableName);
        foreach ($columnsInfo as $column) {
            $columnName = preg_quote($column['name'], '#');
            $columnNamePattern = "\"$columnName\"|`$columnName`|\\[$columnName\\]|$columnName";
            $columnNamePattern = "#([\(,]+\\s*)($columnNamePattern)(\\s)#iU";

            $sql = preg_replace_callback(
                $columnNamePattern,
                function ($matches) use ($column) {
                    return $matches[1] . $this->quoteColumnName($column['name']) . $matches[3];
                },
                $sql,
            );
        }

        $tableNamePattern = "\"$tableName\"|`$tableName`|\\[$tableName\\]|$tableName";
        $tableNamePattern = "#^(CREATE TABLE)\s*($tableNamePattern)\s*(\()#Ui";

        $sql = preg_replace($tableNamePattern, "$1 `$tableName` $3", $sql, 1);

        return $sql;
    }

    /**
     * Returns the original CREATE statement for the give index
     *
     * @param string $tableName The table name to get the create statement for
     * @param string $indexName The table index
     * @return string
     */
    protected function getDeclaringIndexSql(string $tableName, string $indexName): string
    {
        $rows = $this->fetchAll("SELECT * FROM sqlite_master WHERE \"type\" = 'index'");

        $sql = '';
        foreach ($rows as $table) {
            if ($table['tbl_name'] === $tableName && $table['name'] === $indexName) {
                $sql = $table['sql'] . '; ';
            }
        }

        return $sql;
    }

    /**
     * Obtains index and trigger information for a table.
     *
     * They will be stored in the state as arrays under the `indices` and `triggers`
     * keys accordingly.
     *
     * Index columns defined as expressions, as for example in `ON (ABS(id), other)`,
     * will appear as `null`, so for the given example the columns for the index would
     * look like `[null, 'other']`.
     *
     * @param \Migrations\Db\AlterInstructions $instructions The instructions to modify
     * @param string $tableName The name of table being processed
     * @return \Migrations\Db\AlterInstructions
     */
    protected function bufferIndicesAndTriggers(AlterInstructions $instructions, string $tableName): AlterInstructions
    {
        $instructions->addPostStep(function (array $state) use ($tableName): array {
            $state['indices'] = [];
            $state['triggers'] = [];

            $params = [$tableName];
            $rows = $this->query(
                "SELECT *
                FROM sqlite_master
                WHERE
                    (\"type\" = 'index' OR \"type\" = 'trigger')
                    AND tbl_name = ?
                    AND sql IS NOT NULL
                ",
                $params,
            )->fetchAll('assoc');

            $indexes = $this->getIndexes($tableName);
            $indexMap = [];
            foreach ($indexes as $index) {
                $indexMap[$index['name']] = $index;
            }

            foreach ($rows as $row) {
                switch ($row['type']) {
                    case 'index':
                        $info = $indexMap[$row['name']];
                        $columns = array_map(
                            function ($column) {
                                if ($column === null) {
                                    return null;
                                }

                                return strtolower($column);
                            },
                            $info['columns'],
                        );
                        $hasExpressions = in_array(null, $columns, true);

                        $index = [
                            'columns' => $columns,
                            'hasExpressions' => $hasExpressions,
                        ];

                        $state['indices'][] = $index + $row;
                        break;

                    case 'trigger':
                        $state['triggers'][] = $row;
                        break;
                }
            }

            return $state;
        });

        return $instructions;
    }

    /**
     * Filters out indices that reference a removed column.
     *
     * @param \Migrations\Db\AlterInstructions $instructions The instructions to modify
     * @param string $columnName The name of the removed column
     * @return \Migrations\Db\AlterInstructions
     */
    protected function filterIndicesForRemovedColumn(
        AlterInstructions $instructions,
        string $columnName,
    ): AlterInstructions {
        $instructions->addPostStep(function (array $state) use ($columnName): array {
            foreach ($state['indices'] as $key => $index) {
                if (
                    !$index['hasExpressions'] &&
                    in_array(strtolower($columnName), $index['columns'], true)
                ) {
                    unset($state['indices'][$key]);
                }
            }

            return $state;
        });

        return $instructions;
    }

    /**
     * Updates indices that reference a renamed column.
     *
     * @param \Migrations\Db\AlterInstructions $instructions The instructions to modify
     * @param string $oldColumnName The old column name
     * @param string $newColumnName The new column name
     * @return \Migrations\Db\AlterInstructions
     */
    protected function updateIndicesForRenamedColumn(
        AlterInstructions $instructions,
        string $oldColumnName,
        string $newColumnName,
    ): AlterInstructions {
        $instructions->addPostStep(function (array $state) use ($oldColumnName, $newColumnName): array {
            foreach ($state['indices'] as $key => $index) {
                if (
                    !$index['hasExpressions'] &&
                    in_array(strtolower($oldColumnName), $index['columns'], true)
                ) {
                    $pattern = '
                        /
                            (INDEX.+?ON\s.+?)
                                (\(\s*|,\s*)        # opening parenthesis or comma
                                (?:`|"|\[)?         # optional opening quote
                                (%s)                # column name
                                (?:`|"|\])?         # optional closing quote
                                (\s+COLLATE\s+.+?)? # optional collation
                                (\s+(?:ASC|DESC))?  # optional order
                                (\s*,|\s*\))        # comma or closing parenthesis
                        /isx';

                    $newColumnName = $this->quoteColumnName($newColumnName);

                    $state['indices'][$key]['sql'] = preg_replace(
                        sprintf($pattern, preg_quote($oldColumnName, '/')),
                        "\\1\\2$newColumnName\\4\\5\\6",
                        $index['sql'],
                    );
                }
            }

            return $state;
        });

        return $instructions;
    }

    /**
     * Recreates indices and triggers.
     *
     * @param \Migrations\Db\AlterInstructions $instructions The instructions to process
     * @return \Migrations\Db\AlterInstructions
     */
    protected function recreateIndicesAndTriggers(AlterInstructions $instructions): AlterInstructions
    {
        $instructions->addPostStep(function (array $state): array {
            foreach ($state['indices'] as $index) {
                $this->execute($index['sql']);
            }

            foreach ($state['triggers'] as $trigger) {
                $this->execute($trigger['sql']);
            }

            return $state;
        });

        return $instructions;
    }

    /**
     * Returns instructions for validating the foreign key constraints of
     * the given table, and of those tables whose constraints are
     * targeting it.
     *
     * @param \Migrations\Db\AlterInstructions $instructions The instructions to process
     * @param string $tableName The name of the table for which to check constraints.
     * @return \Migrations\Db\AlterInstructions
     */
    protected function validateForeignKeys(AlterInstructions $instructions, string $tableName): AlterInstructions
    {
        $instructions->addPostStep(function ($state) use ($tableName) {
            $tablesToCheck = [
                $tableName,
            ];

            $otherTables = $this
                ->query(
                    "SELECT name FROM sqlite_master WHERE type = 'table' AND name != ?",
                    [$tableName],
                )
                ->fetchAll('assoc');

            foreach ($otherTables as $otherTable) {
                $foreignKeyList = $this->getForeignKeys($otherTable['name']);
                foreach ($foreignKeyList as $foreignKey) {
                    if (strcasecmp($foreignKey['references'][0], $tableName) === 0) {
                        $tablesToCheck[] = $otherTable['name'];
                        break;
                    }
                }
            }

            $tablesToCheck = array_unique(array_map('strtolower', $tablesToCheck));

            foreach ($tablesToCheck as $tableToCheck) {
                $schema = $this->getSchemaName($tableToCheck, true)['schema'];

                $stmt = $this->query(
                    sprintf('PRAGMA %sforeign_key_check(%s)', $schema, $this->quoteTableName($tableToCheck)),
                );
                $row = $stmt->fetch();
                $stmt->closeCursor();

                if (is_array($row)) {
                    throw new RuntimeException(sprintf(
                        'Integrity constraint violation: FOREIGN KEY constraint on `%s` failed.',
                        $tableToCheck,
                    ));
                }
            }

            return $state;
        });

        return $instructions;
    }

    /**
     * Copies all the data from a tmp table to another table
     *
     * @param string $tableName The table name to copy the data to
     * @param string $tmpTableName The tmp table name where the data is stored
     * @param string[] $writeColumns The list of columns in the target table
     * @param string[] $selectColumns The list of columns in the tmp table
     * @return void
     */
    protected function copyDataToNewTable(string $tableName, string $tmpTableName, array $writeColumns, array $selectColumns): void
    {
        $sql = sprintf(
            'INSERT INTO %s(%s) SELECT %s FROM %s',
            $this->quoteTableName($tableName),
            implode(', ', $writeColumns),
            implode(', ', $selectColumns),
            $this->quoteTableName($tmpTableName),
        );
        $this->execute($sql);
    }

    /**
     * Modifies the passed instructions to copy all data from the table into
     * the provided tmp table and then drops the table and rename tmp table.
     *
     * @param \Migrations\Db\AlterInstructions $instructions The instructions to modify
     * @param string $tableName The table name to copy the data to
     * @return \Migrations\Db\AlterInstructions
     */
    protected function copyAndDropTmpTable(AlterInstructions $instructions, string $tableName): AlterInstructions
    {
        $instructions->addPostStep(function ($state) use ($tableName) {
            $this->copyDataToNewTable(
                $state['tmpTableName'],
                $tableName,
                $state['writeColumns'],
                $state['selectColumns'],
            );

            $this->execute(sprintf('DROP TABLE %s', $this->quoteTableName($tableName)));
            $this->execute(sprintf(
                'ALTER TABLE %s RENAME TO %s',
                $this->quoteTableName($state['tmpTableName']),
                $this->quoteTableName($tableName),
            ));

            return $state;
        });

        return $instructions;
    }

    /**
     * Returns the columns and type to use when copying a table to another in the process
     * of altering a table
     *
     * @param string $tableName The table to modify
     * @param string|false $columnName The column name that is about to change
     * @param string|false $newColumnName Optionally the new name for the column
     * @throws \InvalidArgumentException
     * @return array
     */
    protected function calculateNewTableColumns(string $tableName, string|false $columnName, string|false $newColumnName): array
    {
        $columns = $this->getColumnData($tableName);
        /** @var array<string> $selectColumns */
        $selectColumns = [];
        /** @var array<string> $writeColumns */
        $writeColumns = [];
        $columnType = null;
        $found = false;

        foreach ($columns as $column) {
            $selectName = $column['name'];
            $writeName = $selectName;

            if ($selectName === $columnName) {
                $writeName = $newColumnName;
                $found = true;
                $columnType = $column['type'];
                $selectName = $newColumnName === false ? $newColumnName : $selectName;
            }

            if ($selectName) {
                $selectColumns[] = $selectName;
            }
            if ($writeName) {
                $writeColumns[] = $writeName;
            }
        }
        $selectColumns = array_filter($selectColumns, fn($value) => $value !== '');
        $writeColumns = array_filter($writeColumns, fn($value) => $value !== '');
        $selectColumns = array_map([$this, 'quoteColumnName'], $selectColumns);
        $writeColumns = array_map([$this, 'quoteColumnName'], $writeColumns);

        if ($columnName && !$found) {
            throw new InvalidArgumentException(sprintf(
                'The specified column doesn\'t exist: %s',
                $columnName,
            ));
        }

        return compact('writeColumns', 'selectColumns', 'columnType');
    }

    /**
     * Returns the initial instructions to alter a table using the
     * create-copy-drop strategy
     *
     * @param string $tableName The table to modify
     * @return \Migrations\Db\AlterInstructions
     */
    protected function beginAlterByCopyTable(string $tableName): AlterInstructions
    {
        $instructions = new AlterInstructions();
        $instructions->addPostStep(function ($state) use ($tableName) {
            $tmpTableName = "tmp_{$tableName}";
            $createSQL = $this->getDeclaringSql($tableName);

            // Table name in SQLite can be hilarious inside declaring SQL:
            // - tableName
            // - `tableName`
            // - "tableName"
            // - [this is a valid table name too!]
            // - etc.
            // Just remove all characters before first "(" and build them again
            $createSQL = preg_replace(
                "/^CREATE TABLE .* \(/Ui",
                '',
                $createSQL,
            );

            $createSQL = "CREATE TABLE {$this->quoteTableName($tmpTableName)} ({$createSQL}";

            return compact('createSQL', 'tmpTableName') + $state;
        });

        return $instructions;
    }

    /**
     * Returns the final instructions to alter a table using the
     * create-copy-drop strategy.
     *
     * @param \Migrations\Db\AlterInstructions $instructions The instructions to modify
     * @param string $tableName The name of table being processed
     * @param ?string $renamedOrRemovedColumnName The name of the renamed or removed column when part of a column
     *  rename/drop operation.
     * @param ?string $newColumnName The new column name when part of a column rename operation.
     * @param bool $validateForeignKeys Whether to validate foreign keys after the copy and drop operations. Note that
     *  enabling this option only has an effect when the `foreign_keys` PRAGMA is set to `ON`!
     * @return \Migrations\Db\AlterInstructions
     */
    protected function endAlterByCopyTable(
        AlterInstructions $instructions,
        string $tableName,
        ?string $renamedOrRemovedColumnName = null,
        ?string $newColumnName = null,
        bool $validateForeignKeys = true,
    ): AlterInstructions {
        $instructions = $this->bufferIndicesAndTriggers($instructions, $tableName);

        if ($renamedOrRemovedColumnName !== null) {
            if ($newColumnName !== null) {
                $this->updateIndicesForRenamedColumn($instructions, $renamedOrRemovedColumnName, $newColumnName);
            } else {
                $this->filterIndicesForRemovedColumn($instructions, $renamedOrRemovedColumnName);
            }
        }

        $result = $this->fetchRow('PRAGMA foreign_keys');
        $foreignKeysEnabled = $result ? (bool)$result['foreign_keys'] : false;

        if ($foreignKeysEnabled) {
            $instructions->addPostStep('PRAGMA foreign_keys = OFF');
        }

        $instructions = $this->copyAndDropTmpTable($instructions, $tableName);
        $instructions = $this->recreateIndicesAndTriggers($instructions);

        if ($foreignKeysEnabled) {
            $instructions->addPostStep('PRAGMA foreign_keys = ON');
        }

        if (
            $foreignKeysEnabled &&
            $validateForeignKeys
        ) {
            $instructions = $this->validateForeignKeys($instructions, $tableName);
        }

        return $instructions;
    }

    /**
     * @inheritDoc
     */
    protected function getRenameColumnInstructions(string $tableName, string $columnName, string $newColumnName): AlterInstructions
    {
        $instructions = $this->beginAlterByCopyTable($tableName);

        $instructions->addPostStep(function ($state) use ($columnName, $newColumnName) {
            $sql = str_replace(
                $this->quoteColumnName($columnName),
                $this->quoteColumnName($newColumnName),
                (string)$state['createSQL'],
            );
            $this->execute($sql);

            return $state;
        });

        $instructions->addPostStep(function ($state) use ($columnName, $newColumnName, $tableName) {
            $newState = $this->calculateNewTableColumns($tableName, $columnName, $newColumnName);

            return $newState + $state;
        });

        return $this->endAlterByCopyTable($instructions, $tableName, $columnName, $newColumnName);
    }

    /**
     * @inheritDoc
     */
    protected function getChangeColumnInstructions(string $tableName, string $columnName, Column $newColumn): AlterInstructions
    {
        $instructions = $this->beginAlterByCopyTable($tableName);

        $newColumnName = (string)$newColumn->getName();
        $instructions->addPostStep(function ($state) use ($columnName, $newColumn) {
            $dialect = $this->getSchemaDialect();
            $sql = preg_replace(
                sprintf("/%s(?:\/\*.*?\*\/|\([^)]+\)|'[^']*?'|[^,])+([,)])/", $this->quoteColumnName($columnName)),
                sprintf('%s$1', $dialect->columnDefinitionSql($newColumn->toArray())),
                (string)$state['createSQL'],
                1,
            );
            $this->execute($sql);

            return $state;
        });

        $instructions->addPostStep(function ($state) use ($columnName, $newColumnName, $tableName) {
            $newState = $this->calculateNewTableColumns($tableName, $columnName, $newColumnName);

            return $newState + $state;
        });

        return $this->endAlterByCopyTable($instructions, $tableName);
    }

    /**
     * @inheritDoc
     */
    protected function getDropColumnInstructions(string $tableName, string $columnName): AlterInstructions
    {
        $instructions = $this->beginAlterByCopyTable($tableName);

        $instructions->addPostStep(function ($state) use ($tableName, $columnName) {
            $newState = $this->calculateNewTableColumns($tableName, $columnName, false);

            return $newState + $state;
        });

        $instructions->addPostStep(function ($state) use ($columnName) {
            $sql = preg_replace(
                sprintf("/%s\s\w+.*(,\s(?!')|\)$)/U", preg_quote($this->quoteColumnName($columnName))),
                '',
                (string)$state['createSQL'],
            );

            if (substr($sql, -2) === ', ') {
                $sql = substr($sql, 0, -2) . ')';
            }

            $this->execute($sql);

            return $state;
        });

        return $this->endAlterByCopyTable($instructions, $tableName, $columnName);
    }

    /**
     * Get an array of indexes from a particular table.
     *
     * @param string $tableName Table name
     * @return array
     */
    protected function getIndexes(string $tableName): array
    {
        $dialect = $this->getSchemaDialect();
        $indexes = $dialect->describeIndexes($tableName);

        return $indexes;
    }

    /**
     * Finds the names of a table's indexes matching the supplied columns
     *
     * @param string $tableName The table to which the index belongs
     * @param string|string[] $columns The columns of the index
     * @return array
     */
    protected function resolveIndex(string $tableName, string|array $columns): array
    {
        $columns = array_map('strtolower', (array)$columns);
        $indexes = $this->getIndexes($tableName);
        $matches = [];

        foreach ($indexes as $index) {
            $indexCols = array_map('strtolower', $index['columns']);
            if ($columns == $indexCols) {
                $matches[] = $index['name'];
            }
        }

        return $matches;
    }

    /**
     * @inheritDoc
     */
    public function hasIndex(string $tableName, string|array $columns): bool
    {
        return (bool)$this->resolveIndex($tableName, $columns);
    }

    /**
     * @inheritDoc
     */
    public function hasIndexByName(string $tableName, string $indexName): bool
    {
        $indexName = strtolower($indexName);
        $indexes = $this->getIndexes($tableName);

        foreach ($indexes as $index) {
            if ($indexName === strtolower($index['name'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    protected function getAddIndexInstructions(Table $table, Index $index): AlterInstructions
    {
        $indexColumnArray = [];
        foreach ((array)$index->getColumns() as $column) {
            $indexColumnArray[] = sprintf('%s ASC', $this->quoteColumnName($column));
        }
        $indexColumns = implode(',', $indexColumnArray);
        $where = (string)$index->getWhere();
        if ($where) {
            $where = ' WHERE ' . $where;
        }
        $sql = sprintf(
            'CREATE %s ON %s (%s)%s',
            $this->getIndexSqlDefinition($table, $index),
            $this->quoteTableName($table->getName()),
            $indexColumns,
            $where,
        );

        return new AlterInstructions([], [$sql]);
    }

    /**
     * @inheritDoc
     */
    protected function getDropIndexByColumnsInstructions(string $tableName, $columns): AlterInstructions
    {
        $instructions = new AlterInstructions();
        $indexNames = $this->resolveIndex($tableName, $columns);
        $schema = $this->getSchemaName($tableName, true)['schema'];
        foreach ($indexNames as $indexName) {
            // Skip the implicit primary key that is reflected.
            if ($indexName === 'primary') {
                continue;
            }
            if (strpos($indexName, 'sqlite_autoindex_') !== 0) {
                $instructions->addPostStep(sprintf(
                    'DROP INDEX %s%s',
                    $schema,
                    $this->quoteColumnName($indexName),
                ));
            }
        }

        return $instructions;
    }

    /**
     * @inheritDoc
     */
    protected function getDropIndexByNameInstructions(string $tableName, string $indexName): AlterInstructions
    {
        $instructions = new AlterInstructions();
        $indexName = strtolower($indexName);
        $indexes = $this->getIndexes($tableName);

        $found = false;
        foreach ($indexes as $index) {
            if ($indexName === strtolower($index['name'])) {
                $found = true;
                break;
            }
        }

        if ($found) {
            $schema = $this->getSchemaName($tableName, true)['schema'];
            $instructions->addPostStep(sprintf(
                'DROP INDEX %s%s',
                $schema,
                $this->quoteColumnName($indexName),
            ));
        }

        return $instructions;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \InvalidArgumentException
     */
    public function hasPrimaryKey(string $tableName, $columns, ?string $constraint = null): bool
    {
        if ($constraint !== null) {
            throw new InvalidArgumentException('SQLite does not support named constraints.');
        }

        $columns = array_map('strtolower', (array)$columns);
        $primaryKey = array_map('strtolower', $this->getPrimaryKey($tableName));

        if (array_diff($primaryKey, $columns) || array_diff($columns, $primaryKey)) {
            return false;
        }

        return true;
    }

    /**
     * Get the primary key from a particular table.
     *
     * @param string $tableName Table name
     * @return string[]
     */
    protected function getPrimaryKey(string $tableName): array
    {
        $dialect = $this->getSchemaDialect();
        $indexes = $dialect->describeIndexes($tableName);
        foreach ($indexes as $index) {
            if ($index['type'] == TableSchema::CONSTRAINT_PRIMARY) {
                return $index['columns'];
            }
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function hasForeignKey(string $tableName, $columns, ?string $constraint = null): bool
    {
        $columns = array_map('mb_strtolower', (array)$columns);

        foreach ($this->getForeignKeys($tableName) as $key) {
            if ($constraint !== null && $key['name'] == $constraint) {
                return true;
            }
            if (array_map('mb_strtolower', $key['columns']) === $columns) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get an array of foreign keys from a particular table.
     *
     * @param string $tableName Table name
     * @return array
     */
    protected function getForeignKeys(string $tableName): array
    {
        $dialect = $this->getSchemaDialect();
        $keys = $dialect->describeForeignKeys($tableName);

        return $keys;
    }

    /**
     * @param \Migrations\Db\Table\Table $table The Table
     * @param string $column Column Name
     * @return \Migrations\Db\AlterInstructions
     */
    protected function getAddPrimaryKeyInstructions(Table $table, string $column): AlterInstructions
    {
        $instructions = $this->beginAlterByCopyTable($table->getName());

        $tableName = $table->getName();
        $instructions->addPostStep(function ($state) use ($column) {
            $quotedColumn = preg_quote($column);
            $columnPattern = "`{$quotedColumn}`|\"{$quotedColumn}\"|\[{$quotedColumn}\]";
            $matchPattern = "/($columnPattern)\s+(\w+(\(\d+\))?)(\s+(NOT )?NULL)?/";

            $sql = $state['createSQL'];

            if (preg_match($matchPattern, $state['createSQL'], $matches)) {
                if (isset($matches[2])) {
                    if ($matches[2] === 'INTEGER') {
                        $replace = '$1 INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT';
                    } else {
                        $replace = '$1 $2 NOT NULL PRIMARY KEY';
                    }

                    $sql = preg_replace($matchPattern, $replace, (string)$state['createSQL'], 1);
                }
            }

            $this->execute($sql);

            return $state;
        });

        $instructions->addPostStep(function ($state) {
            $columns = $this->fetchAll(sprintf('pragma table_info(%s)', $this->quoteTableName($state['tmpTableName'])));
            $names = array_map([$this, 'quoteColumnName'], array_column($columns, 'name'));
            $selectColumns = $writeColumns = $names;

            return compact('selectColumns', 'writeColumns') + $state;
        });

        return $this->endAlterByCopyTable($instructions, $tableName);
    }

    /**
     * @param \Migrations\Db\Table\Table $table Table
     * @param string $column Column Name
     * @return \Migrations\Db\AlterInstructions
     */
    protected function getDropPrimaryKeyInstructions(Table $table, string $column): AlterInstructions
    {
        $tableName = $table->getName();
        $instructions = $this->beginAlterByCopyTable($tableName);

        $instructions->addPostStep(function ($state) {
            $search = "/(,?\s*PRIMARY KEY\s*\([^\)]*\)|\s+PRIMARY KEY(\s+AUTOINCREMENT)?)/";
            $sql = preg_replace($search, '', (string)$state['createSQL'], 1);

            if ($sql) {
                $this->execute($sql);
            }

            return $state;
        });

        $instructions->addPostStep(function ($state) use ($column) {
            $newState = $this->calculateNewTableColumns($state['tmpTableName'], $column, $column);

            return $newState + $state;
        });

        return $this->endAlterByCopyTable($instructions, $tableName, null, null, false);
    }

    /**
     * @inheritDoc
     */
    protected function getAddForeignKeyInstructions(Table $table, ForeignKey $foreignKey): AlterInstructions
    {
        $instructions = $this->beginAlterByCopyTable($table->getName());

        $tableName = $table->getName();
        $instructions->addPostStep(function ($state) use ($foreignKey, $tableName) {
            $this->execute('pragma foreign_keys = ON');
            $sql = substr($state['createSQL'], 0, -1) . ',' . $this->getForeignKeySqlDefinition($foreignKey) . '); ';

            //Delete indexes from original table and recreate them in temporary table
            $schema = $this->getSchemaName($tableName, true)['schema'];
            $tmpTableName = $state['tmpTableName'];
            $indexes = $this->getIndexes($tableName);
            foreach ($indexes as $index) {
                if (strpos($index['name'], 'sqlite_autoindex_') !== 0) {
                    $sql .= sprintf(
                        'DROP INDEX %s%s; ',
                        $schema,
                        $this->quoteColumnName($index['name']),
                    );
                    $createIndexSQL = $this->getDeclaringIndexSQL($tableName, $index['name']);
                    $sql .= preg_replace(
                        "/\b{$tableName}\b/",
                        $tmpTableName,
                        $createIndexSQL,
                    );
                }
            }

            $this->execute($sql);

            return $state;
        });

        $instructions->addPostStep(function ($state) {
            $columns = $this->fetchAll(sprintf('pragma table_info(%s)', $this->quoteTableName($state['tmpTableName'])));
            $names = array_map([$this, 'quoteColumnName'], array_column($columns, 'name'));
            $selectColumns = $writeColumns = $names;

            return compact('selectColumns', 'writeColumns') + $state;
        });

        return $this->endAlterByCopyTable($instructions, $tableName);
    }

    /**
     * {@inheritDoc}
     *
     * SQLiteAdapter does not implement this functionality, and so will always throw an exception if used.
     *
     * @throws \BadMethodCallException
     */
    protected function getDropForeignKeyInstructions(string $tableName, string $constraint): AlterInstructions
    {
        throw new BadMethodCallException('SQLite does not have named foreign keys');
    }

    /**
     * {@inheritDoc}
     *
     * @throws \InvalidArgumentException
     */
    protected function getDropForeignKeyByColumnsInstructions(string $tableName, array $columns): AlterInstructions
    {
        if (!$this->hasForeignKey($tableName, $columns)) {
            throw new InvalidArgumentException(sprintf(
                'No foreign key on column(s) `%s` exists',
                implode(', ', $columns),
            ));
        }

        $instructions = $this->beginAlterByCopyTable($tableName);

        $instructions->addPostStep(function ($state) use ($columns) {
            $search = sprintf(
                "/,[^,]+?\(\s*%s\s*\)\s*REFERENCES[^,]*\([^\)]*\)[^,)]*/is",
                implode(
                    '\s*,\s*',
                    array_map(
                        fn($column) => $this->possiblyQuotedIdentifierRegex($column, false),
                        $columns,
                    ),
                ),
            );
            $sql = preg_replace($search, '', (string)$state['createSQL']);

            if ($sql) {
                $this->execute($sql);
            }

            return $state;
        });

        $instructions->addPostStep(function ($state) {
            $newState = $this->calculateNewTableColumns($state['tmpTableName'], false, false);

            return $newState + $state;
        });

        return $this->endAlterByCopyTable($instructions, $tableName);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Migrations\Db\Adapter\UnsupportedColumnTypeException
     */
    public function getSqlType(Literal|string $type, ?int $limit = null): array
    {
        if ($type instanceof Literal) {
            $name = $type;
        } else {
            $typeLC = strtolower($type);

            if (isset(static::$supportedColumnTypes[$typeLC])) {
                $name = static::$supportedColumnTypes[$typeLC];
            } elseif (in_array($typeLC, static::$unsupportedColumnTypes, true)) {
                throw new UnsupportedColumnTypeException('Column type "' . $type . '" is not supported by SQLite.');
            } else {
                throw new UnsupportedColumnTypeException('Column type "' . $type . '" is not known by SQLite.');
            }
        }

        return ['name' => $name, 'limit' => $limit];
    }

    /**
     * Returns Phinx type by SQL type
     *
     * @param string|null $sqlTypeDef SQL Type definition
     * @return array
     */
    public function getPhinxType(?string $sqlTypeDef): array
    {
        $limit = null;
        $scale = null;
        if ($sqlTypeDef === null) {
            // in SQLite columns can legitimately have null as a type, which is distinct from the empty string
            $name = null;
        } else {
            if (!preg_match('/^([a-z]+)(_(?:integer|float|text|blob))?(?:\((\d+)(?:,(\d+))?\))?$/i', $sqlTypeDef, $match)) {
                // doesn't match the pattern of a type we'd know about
                $name = Literal::from($sqlTypeDef);
            } else {
                // possibly a known type
                $type = $match[1];
                $typeLC = strtolower($type);
                $affinity = $match[2] ?? '';
                $limit = isset($match[3]) && strlen($match[3]) ? (int)$match[3] : null;
                $scale = isset($match[4]) && strlen($match[4]) ? (int)$match[4] : null;
                if (in_array($typeLC, ['tinyint', 'tinyinteger'], true) && $limit === 1) {
                    // the type is a MySQL-style boolean
                    $name = static::PHINX_TYPE_BOOLEAN;
                    $limit = null;
                } elseif (isset(static::$supportedColumnTypes[$typeLC])) {
                    // the type is an explicitly supported type
                    $name = $typeLC;
                } elseif (isset(static::$supportedColumnTypeAliases[$typeLC])) {
                    // the type is an alias for a supported type
                    $name = static::$supportedColumnTypeAliases[$typeLC];
                } elseif (in_array($typeLC, static::$unsupportedColumnTypes, true)) {
                    // unsupported but known types are passed through lowercased, and without appended affinity
                    $name = Literal::from($typeLC);
                } else {
                    // unknown types are passed through as-is
                    $name = Literal::from($type . $affinity);
                }
            }
        }

        return [
            'name' => $name,
            'limit' => $limit,
            'scale' => $scale,
        ];
    }

    /**
     * @inheritDoc
     */
    public function createDatabase(string $name, array $options = []): void
    {
        touch($name . $this->suffix);
    }

    /**
     * @inheritDoc
     */
    public function hasDatabase(string $name): bool
    {
        return is_file($name . $this->suffix);
    }

    /**
     * @inheritDoc
     */
    public function dropDatabase(string $name): void
    {
        $this->createdTables = [];
        if ($this->getOption('memory')) {
            $this->disconnect();
            $this->connect();
        }
        if (file_exists($name . $this->suffix)) {
            unlink($name . $this->suffix);
        }
    }

    /**
     * Gets the SQLite Index Definition for an Index object.
     *
     * @param \Migrations\Db\Table\Table $table Table
     * @param \Migrations\Db\Table\Index $index Index
     * @return string
     */
    protected function getIndexSqlDefinition(Table $table, Index $index): string
    {
        if ($index->getType() === Index::UNIQUE) {
            $def = 'UNIQUE INDEX';
        } else {
            $def = 'INDEX';
        }
        $indexName = $index->getName();
        if (!is_string($indexName)) {
            $indexName = $table->getName() . '_';
            foreach ((array)$index->getColumns() as $column) {
                $indexName .= $column . '_';
            }
            $indexName .= 'index';
        }
        $def .= ' ' . $this->quoteColumnName($indexName);

        return $def;
    }

    /**
     * @inheritDoc
     */
    public function getColumnTypes(): array
    {
        return array_keys(static::$supportedColumnTypes);
    }

    /**
     * Gets the SQLite Foreign Key Definition for an ForeignKey object.
     *
     * @param \Migrations\Db\Table\ForeignKey $foreignKey Foreign key
     * @return string
     */
    protected function getForeignKeySqlDefinition(ForeignKey $foreignKey): string
    {
        $def = '';
        if ($foreignKey->getName()) {
            $def .= ' CONSTRAINT ' . $this->quoteColumnName((string)$foreignKey->getName());
        }
        $columnNames = [];
        foreach ($foreignKey->getColumns() as $column) {
            $columnNames[] = $this->quoteColumnName($column);
        }
        $def .= ' FOREIGN KEY (' . implode(',', $columnNames) . ')';
        $refColumnNames = [];
        foreach ($foreignKey->getReferencedColumns() as $column) {
            $refColumnNames[] = $this->quoteColumnName($column);
        }
        $def .= ' REFERENCES ' . $this->quoteTableName($foreignKey->getReferencedTable()->getName()) . ' (' . implode(',', $refColumnNames) . ')';
        if ($foreignKey->getOnDelete()) {
            $def .= ' ON DELETE ' . $foreignKey->getOnDelete();
        }
        if ($foreignKey->getOnUpdate()) {
            $def .= ' ON UPDATE ' . $foreignKey->getOnUpdate();
        }

        return $def;
    }
}
