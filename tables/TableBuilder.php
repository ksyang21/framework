<?php

namespace tables;

use Database\Database;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * A class to check all tables columns defined in namespace `tables` matched with table column in MySQL database
 * Any updates to class file in `tables` will be reflected on each run
 * TODO: Drop table or column as an optional parameter / function to ensure database conformity
 */
class TableBuilder
{
    private Database $conn;

    public function __construct(Database $conn)
    {
        $this->conn = $conn;

        $namespace = rtrim(__NAMESPACE__, '\\');
        $directory = new RecursiveDirectoryIterator(__DIR__); // Change __DIR__ to the base directory of your project
        $iterator  = new RecursiveIteratorIterator($directory);
        $regex     = new RegexIterator($iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH);

        foreach ($regex as $file) {
            $file_path    = $file[0];
            $file_content = file_get_contents($file_path);

            $namespace_pattern = sprintf('/namespace\s+%s\s*;/i', preg_quote($namespace));
            if (preg_match($namespace_pattern, $file_content)) {
                $class_name      = str_replace('.php', '', basename($file_path));
                $full_class_name = $namespace . '\\' . $class_name;
                if ($full_class_name !== 'tables\TableBuilder') {
                    $table_name = lcfirst(explode('\\', $full_class_name)[1]);
                    $query      = sprintf('SHOW TABLES LIKE "%s"', $table_name);
                    $result     = $this->conn->single($query);
                    // Table not created
                    if (empty($result)) {
                        $columns = get_class_vars($full_class_name)['columns'];
                        if (!$this->checkPrimaryKey($columns)) {
                            die(sprintf('ERROR: %s has multiple primary keys', $table_name));
                        }
                        if (!$this->checkType($columns)) {
                            die(sprintf('ERROR: %s has type error', $table_name));
                        }
                        $create_table_columns = [];
                        foreach ($columns as $column => $attributes) {
                            if (isset($attributes['pk']) && $attributes['pk'] == 1) {
                                $create_table_columns[] = sprintf('PRIMARY KEY (`%s`)', $column);
                            }
                            $create_table_columns[] = sprintf(
                                '`%s` %s %s %s %s %s',
                                $column,
                                $attributes['type'],
                                (isset($attributes['not_null']) && $attributes['not_null'] == 1) ? 'NOT NULL' : '',
                                (isset($attributes['default']) && $attributes['default'] !== '') ? sprintf('DEFAULT \'%s\'', $attributes['default']) : '',
                                (isset($attributes['ai']) && $attributes['ai'] == 1) ? 'AUTO_INCREMENT' : '',
                                (isset($attributes['comment']) && $attributes['comment'] !== '') ? sprintf('COMMENT \'%s\'', $attributes['comment']) : '',
                            );
                        }
                        $query = sprintf('CREATE TABLE `%s`(%s) ENGINE = InnoDB;', $table_name, implode(',', $create_table_columns));
                        $this->conn->execute($query);
                    } else {
                        // Get table columns from PHP file
                        $table_columns = get_class_vars($full_class_name)['columns'];
                        if (!$this->checkPrimaryKey($table_columns)) {
                            die(sprintf('ERROR: %s has multiple primary keys', $table_name));
                        }
                        if (!$this->checkType($table_columns)) {
                            die(sprintf('ERROR: %s has type error', $table_name));
                        }

                        // Get table columns from existing DB
                        $query      = sprintf('SHOW FULL COLUMNS FROM %s', $table_name);
                        $db_columns = $this->conn->select($query);

                        /**
                         * Only check for columns that exists in PHP class, but not in database
                         * If you want to drop columns that does not exist in PHP class, run drop() function
                         */
                        foreach ($table_columns as $table_column => $table_attributes) {
                            $column_found   = FALSE;
                            $column_changed = FALSE;
                            foreach ($db_columns as $db_column) {
                                if ($table_column === $db_column['Field']) {
                                    $column_found = TRUE;
                                    /**
                                     * Check for attributes
                                     */
                                    // Check data type
                                    $db_column_extracted_data_type    = $this->extractDataType($db_column['Type'])['data'];
                                    $table_column_extracted_data_type = $this->extractDataType($table_attributes['type'])['data'];
                                    if ($db_column_extracted_data_type['type'] !== $table_column_extracted_data_type['type']) {
                                        $column_changed = TRUE;
                                        break;
                                    }
                                    if ($db_column_extracted_data_type['size'] !== $table_column_extracted_data_type['size']) {
                                        $column_changed = TRUE;
                                        break;
                                    }

                                    // Check NOT NULL
                                    if ($db_column['Null'] === 'NO') {
                                        if (!isset($table_attributes['not_null'])) {
                                            $column_changed = TRUE;
                                            break;
                                        } else {
                                            if ($table_attributes['not_null'] != 1) {
                                                $column_changed = TRUE;
                                                break;
                                            }
                                        }
                                    } else if ($db_column['Null'] === 'YES') {
                                        if ((isset($table_attributes['not_null']) && $table_attributes['not_null'] == 1)) {
                                            $column_changed = TRUE;
                                            break;
                                        }
                                    }

                                    // Check Primary Key
                                    // TODO : Check foreign key
                                    if ($db_column['Key'] === 'PRI') {
                                        if (!isset($table_attributes['pk'])) {
                                            $column_changed = TRUE;
                                            break;
                                        } else {
                                            if ($table_attributes['pk'] != 1) {
                                                $column_changed = TRUE;
                                                break;
                                            }
                                        }
                                    }

                                    // Check Default
                                    if (!empty($db_column['Default'])) {
                                        if (!isset($table_attributes['default'])) {
                                            $column_changed = TRUE;
                                            break;
                                        } else {
                                            if ($db_column['Default'] != $table_attributes['default']) {
                                                $column_changed = TRUE;
                                                break;
                                            }
                                        }
                                    } else {
                                        if (!empty($table_attributes['default'])) {
                                            $column_changed = TRUE;
                                            break;
                                        }
                                    }

                                    // Check Auto Increment
                                    if (!empty($db_column['Extra'])) {
                                        if ($db_column['Extra'] == 'auto_increment') {
                                            if (!isset($table_attributes['ai'])) {
                                                $column_changed = TRUE;
                                                break;
                                            } else {
                                                if ($table_attributes['ai'] != 1) {
                                                    $column_changed = TRUE;
                                                    break;
                                                }
                                            }
                                        }
                                    }

                                    //Check Comment
                                    if($db_column['Comment'] != $table_attributes['comment']) {
                                        $column_changed = TRUE;
                                        break;
                                    }
                                    break;
                                }
                            }
                            if ($column_changed) {
                                $query = sprintf(
                                    'ALTER TABLE `%s` CHANGE `%s` `%s` %s %s %s %s %s;',
                                    $table_name,
                                    $table_column,
                                    $table_column,
                                    $table_attributes['type'],
                                    (isset($table_attributes['not_null']) && $table_attributes['not_null'] == 1) ? 'NOT NULL' : '',
                                    (isset($table_attributes['default']) && $table_attributes['default'] !== '') ? sprintf('DEFAULT \'%s\'', $table_attributes['default']) : '',
                                    (isset($table_attributes['ai']) && $table_attributes['ai'] == 1) ? 'AUTO_INCREMENT' : '',
                                    (isset($table_attributes['comment']) && $table_attributes['comment'] !== '') ? sprintf('COMMENT \'%s\'', $table_attributes['comment']) : '',
                                );
                                $this->conn->execute($query);
                            }
                            if (!$column_found) {
                                $query = sprintf(
                                    'ALTER TABLE `%s` ADD `%s` %s %s %s %s %s; ',
                                    $table_name,
                                    $table_column,
                                    $table_attributes['type'],
                                    (isset($table_attributes['not_null']) && $table_attributes['not_null'] == 1) ? 'NOT NULL' : '',
                                    (isset($table_attributes['default']) && $table_attributes['default'] !== '') ? sprintf('DEFAULT \'%s\'', $table_attributes['default']) : '',
                                    (isset($table_attributes['ai']) && $table_attributes['ai'] == 1) ? 'AUTO_INCREMENT' : '',
                                    (isset($table_attributes['comment']) && $table_attributes['comment'] !== '') ? sprintf('COMMENT \'%s\'', $table_attributes['comment']) : '',
                                );
                                $this->conn->execute($query);
                            }
                        }
                    }
                }
            }
        }
    }

    public function conn(): Database
    {
        return $this->conn;
    }

    /**
     * Check to ensure only 1 primary key can exist in table
     * @param array $columns
     * @return bool
     */
    public function checkPrimaryKey(array $columns): bool
    {
        $primary_key_count = 0;
        foreach ($columns as $column) {
            if (isset($column['pk']) && $column['pk'] == 1) {
                $primary_key_count++;
            }
        }
        return !($primary_key_count > 1);
    }

    public function checkType(array $columns): bool
    {
        $allowed_types = [
            'tinyint',
            'smallint',
            'mediumint',
            'int',
            'bigint',
            'float',
            'double',
            'decimal',
            'char',
            'varchar',
            'text',
            'enum',
            'set',
            'date',
            'time',
            'datetime',
            'timestamp',
            'year',
            'boolean',
            'bool',
            'binary',
            'varbinary',
            'blob',
            'geometry',
            'point',
            'linestring',
            'polygon',
        ];
        foreach ($columns as $column) {
            if (!isset($column['type'])) {
                return FALSE;
            } else {
                $data_type_response = $this->extractDataType($column['type']);
                if ($data_type_response['status'] === 0) {
                    return false;
                } else {
                    $extracted_data = $data_type_response['data'];
                    $data_type      = $extracted_data['type'];
                    if (!in_array($data_type, $allowed_types)) {
                        return FALSE;
                    }
                    $size = $extracted_data['size'];
                    if ($size !== null && !is_int($size)) {
                        var_dump($size);
                        return FALSE;
                    }
                }
            }
        }
        return TRUE;
    }

    private function extractDataType(string $data_type): array
    {
        $pattern = '/^(\w+)(?:\((\d+)\))?$/';
        if (preg_match($pattern, $data_type, $matches)) {
            return [
                'status' => 1,
                'data'   => [
                    'type' => $matches[1],
                    'size' => $matches[2] ? intval($matches[2]) : null
                ],
            ];
        }

        return [
            'status' => 0,
        ];
    }
}