<?php
// define('SECURE_ACCESS', true);
require_once 'config.php';

class Database {
    private $connection;
    private static $instance = null;

    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            die("Query failed: " . $e->getMessage());
        }
    }

    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert($table, $data) {
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        
        $sql = "INSERT INTO $table (" . implode(',', $fields) . ") VALUES ($placeholders)";
        $this->query($sql, $values);
        return $this->connection->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = []) {
        $fields = array_keys($data);
        $values = array_values($data);
        
        $set = implode('=?,', $fields) . '=?';
        $sql = "UPDATE $table SET $set WHERE $where";
        
        $this->query($sql, array_merge($values, $whereParams));
    }

    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $this->query($sql, $params);
    }

    public function execute($sql, $params = []) {
        return $this->query($sql, $params);
    }
} 