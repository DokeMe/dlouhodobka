<?php

class Database {
    private $pdo;

    public function __construct() {
        $configFile = __DIR__ . '/../config.ini';

        if (!file_exists($configFile)) {
            die("Configuration file (config.ini) not found in the root directory.");
        }
        $config = parse_ini_file($configFile);
        if (!$config) {
            die("Unable to parse configuration file.");
        }

        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;charset=%s",
            $config['host'],
            $config['dbname'],
            $config['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
        } catch (\PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollBack() {
        return $this->pdo->rollBack();
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function single($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function all($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert($table, $data) {
        $keys = array_keys($data);
        $fields = implode(", ", $keys);
        $placeholders = ":" . implode(", :", $keys);

        $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";
        
        $this->query($sql, $data);
        return $this->pdo->lastInsertId();
    }
}
