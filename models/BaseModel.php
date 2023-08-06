<?php

namespace models;

use Database\Database;

abstract class BaseModel
{
    public int       $primary_id = 0;
    protected string $id         = '';
    protected string $table      = '';
    private Database $conn;
    private string   $query      = ''; // for debugging purpose only
    private array    $functions  = []; // TODO : Add in functions
    private array    $counts     = [];
    private array    $select     = [];
    private array    $where      = [];
    private array    $groups     = [];
    private array    $orders     = [];
    private array    $properties = [];
    private array    $limit      = [];
    private array    $havings    = [];

    public function __construct(Database $conn)
    {
        $this->conn = $conn;
    }

    public function conn(): Database
    {
        return $this->conn;
    }

    public function where(string $column, string $operator = '=', string $data = ''): BaseModel
    {
        $allowed_operators = ['<', '>', '>=', '<=', '=', '<>', '!='];
        if (in_array($operator, $allowed_operators)) {
            $this->where[] = sprintf("%s %s '%s'", $column, $operator, $data);
        } else {
            // Pass in $column & $data only
            $this->where[] = sprintf("%s = '%s'", $column, $operator);
        }
        return $this;
    }

    public function isNull(string $column): BaseModel
    {
        $this->where[] = sprintf('%s IS NULL', $column);
        return $this;
    }

    public function notNull(string $column): BaseModel
    {
        $this->where[] = sprintf('%s IS NOT NULL', $column);
        return $this;
    }

    public function like(string $column, string $data): BaseModel
    {
        $this->where[] = sprintf("%s LIKE '%s'", $column, $data);
        return $this;
    }

    public function notLike(string $column, string $data): BaseModel
    {
        $this->where[] = sprintf("%s NOT LIKE '%s'", $column, $data);
        return $this;
    }

    public function between(string $column, string $start, string $end): BaseModel
    {
        $this->where[] = sprintf("%s BETWEEN '%s' AND '%s'", $column, $start, $end);
        return $this;
    }

    public function notBetween(string $column, string $start, string $end): BaseModel
    {
        $this->where[] = sprintf("%s NOT BETWEEN '%s' AND '%s'", $column, $start, $end);
        return $this;
    }

    public function in(string $column, array $data): BaseModel
    {
        foreach ($data as &$value) {
            $value = sprintf("'%s'", $value);
        }
        $this->where[] = sprintf('%s IN (%s)', $column, implode(',', $data));
        return $this;
    }

    public function having(string $having_condition): BaseModel
    {
        $this->having($having_condition);
        return $this;
    }

    public function group(string $data): BaseModel
    {
        $this->groups[] = $data;
        return $this;
    }

    public function order(string $data): BaseModel
    {
        $this->orders[] = $data;
        return $this;
    }

    public function prop(string $column, string $data): BaseModel
    {
        $this->properties[$column] = sprintf("'%s'", $data);
        return $this;
    }

    public function limit(int $limit, int $offset = 0): BaseModel
    {
        $this->limit = [$limit, $offset];
        return $this;
    }

    /**
     * Get total count of results
     * @return array
     */
    public function count(): array
    {
        $query       = sprintf(
            'SELECT COUNT(1) as total FROM %s WHERE %s',
            $this->table,
            !empty($this->where) ? implode(' AND ', $this->where) : 1
        );
        $this->query = $query;
        $results     = $this->conn->select($query)[0];
        $this->reset();
        return $results;
    }

    public function select(array $selects): BaseModel
    {
        $this->select = $selects;
        return $this;
    }

    public function reset(): void
    {
        $this->primary_id = 0;
        $this->properties = [];
        $this->counts     = [];
        $this->select     = [];
        $this->where      = [];
        $this->groups     = [];
        $this->orders     = [];
        $this->limit      = [];
    }

    /**
     * Set this->counts to get specific count
     * @param string $column
     * @param string $data
     * @return $this
     */
    public function counts(string $column = '1', string $data = 'total'): BaseModel
    {
        $this->counts[] = sprintf("COUNT(%s) as %s", $column, $data);
        return $this;
    }

    public function get(): array
    {
        if (empty($this->where)) {
            return $this->all();
        } else {
            $this->buildQuery('select');
            $results = $this->conn->select($this->query);
            $this->reset();
            return $results;
        }
    }

    public function all(): array
    {
        $this->buildQuery('select');
        $results = $this->conn->select($this->query);
        $this->reset();
        return $results;
    }

    public function first(): array
    {
        if (empty($this->where)) {
            return $this->all()[0];
        } else {
            $this->buildQuery('select');
            $result      = $this->conn->single($this->query);
            $this->reset();
            return $result;
        }
    }

    public function save(): void
    {
        if ($this->primary_id > 0) {
            // Update data
            $this->buildQuery('update');
        } else {
            // Insert data
            $this->buildQuery('insert');
        }
        $this->conn->execute($this->query);
        $this->reset();
    }

    public function delete(): void
    {
        $this->buildQuery('delete');
        $this->conn->execute($this->query );
        $this->reset();
    }

    /**
     * Get last insert ID
     * @return int
     */
    public function id(): int
    {
        return $this->conn()->getLastInsertId();
    }

    public function buildQuery(string $build_condition): void
    {
        switch ($build_condition) {
            case 'select':
                $this->query = sprintf(
                    'SELECT %s %s FROM %s WHERE 1 %s %s %s ORDER BY %s %s',
                    !empty($this->select) ? implode(',', $this->select) : '*',
                    !empty($this->counts) ? sprintf(',%s', implode(',', $this->counts)) : '',
                    $this->table,
                    implode(' AND ', $this->where),
                    !empty($this->groups) ? sprintf(' GROUP BY %s', implode(',', $this->groups)) : '',
                    !empty($this->havings) ? sprintf('HAVING %s', implode(',', $this->havings)) : '',
                    !empty($this->orders) ? sprintf(' %s', implode(',', $this->orders)) : $this->id,
                    !empty($this->limit) ? sprintf(' LIMIT %d OFFSET %d', $this->limit[0], $this->limit[1]) : ''
                );
                break;

            case 'insert' :
                // Insert data
                $columns = $values = [];
                foreach ($this->properties as $column => $data) {
                    $columns[] = $this->conn->conn()->real_escape_string($column);
                    $values[]  = $this->conn->conn()->real_escape_string($data);
                }
                $this->query = sprintf(
                    'INSERT INTO %s(%s) VALUES(%s)',
                    $this->table,
                    implode(',', $columns),
                    implode(',', $values)
                );
                break;

            case 'update' :
                // Update data
                $properties = [];
                foreach ($this->properties as $column => $data) {
                    $properties[] = sprintf('%s = %s', $column, $data);
                }
                $this->query = sprintf(
                    'UPDATE %s SET %s WHERE %s = %s',
                    $this->table,
                    implode(',', $properties),
                    $this->id,
                    $this->primary_id
                );
                break;

            case 'delete' :
                $this->query = sprintf(
                    'DELETE FROM %s WHERE %s %s',
                    $this->table,
                    implode(',', $this->where),
                    !empty($this->limit) ? sprintf(' LIMIT %d OFFSET %d', $this->limit[0], $this->limit[1]) : ''
                );
                break;

            default:
                break;
        }
    }
}