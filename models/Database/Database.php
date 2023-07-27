<?php

namespace Database;

use Exception;
use mysqli;

class Database
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function conn(): mysqli
    {
        return $this->conn;
    }

    public function execute($query): void
    {
        try {
            $this->conn->query($query);
        } catch (Exception $e) {
            die();
        }
    }

    public function select($query): array
    {
        try {
            $all_results = [];
            $results     = $this->conn->query($query);
            while ($row = $results->fetch_assoc()) {
                $all_results[] = $row;
            }
            return $all_results;
        } catch (Exception $e) {
            return array();
        }
    }

    public function single($query): array
    {
        try {
            $results       = $this->conn->query($query);
            if($results->num_rows > 0) {
                return $results->fetch_assoc();
            } else {
                return array();
            }
        } catch (Exception $e) {
            return array();
        }
    }

    public function numRows($query): int
    {
        try {
            $results = $this->conn->query($query);
            return $results->num_rows;
        } catch (Exception $e) {
            return 0;
        }
    }

	public function getLastInsertId() : int {
		return $this->conn->insert_id;
	}
}