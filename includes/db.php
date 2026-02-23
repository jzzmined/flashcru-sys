<?php
/**
 * FlashCru Emergency Response System
 * Database Connection Class
 * 
 * @package FlashCru
 * @version 1.0.0
 */

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $conn = null;
    private $error = null;
    
    /**
     * Create database connection using PDO
     * 
     * @return PDO|null Database connection or null on failure
     */
    public function connect() {
        // Return existing connection if available
        if ($this->conn !== null) {
            return $this->conn;
        }
        
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false
            ];
            
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
            
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            error_log("FlashCru Database Connection Error: " . $this->error);
            die("Database connection failed. Please check your configuration.");
        }
        
        return $this->conn;
    }
    
    /**
     * Get last error message
     * 
     * @return string|null Error message
     */
    public function getError() {
        return $this->error;
    }
    
    /**
     * Close database connection
     */
    public function disconnect() {
        $this->conn = null;
    }
    
    /**
     * Execute a SQL query
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return PDOStatement|false Statement object or false on failure
     */
    public function query($sql, $params = []) {
        try {
            $conn = $this->connect();
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            error_log("FlashCru Query Error: " . $this->error . " | SQL: " . $sql);
            return false;
        }
    }
    
    /**
     * Fetch all rows from query result
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array Array of results
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    /**
     * Fetch single row from query result
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array|false Single row or false
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : false;
    }
    
    /**
     * Insert record into table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int|false Last insert ID or false on failure
     */
    public function insert($table, $data) {
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        
        if ($this->query($sql, $data)) {
            return $this->connect()->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update record in table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $where WHERE clause (e.g., "user_id = :user_id")
     * @param array $whereParams WHERE clause parameters
     * @return bool True on success, false on failure
     */
    public function update($table, $data, $where, $whereParams = []) {
        $fields = [];
        foreach (array_keys($data) as $field) {
            $fields[] = "{$field} = :{$field}";
        }
        $fieldsStr = implode(', ', $fields);
        
        $sql = "UPDATE {$table} SET {$fieldsStr} WHERE {$where}";
        
        $params = array_merge($data, $whereParams);
        
        return $this->query($sql, $params) !== false;
    }
    
    /**
     * Delete record from table
     * 
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params WHERE clause parameters
     * @return bool True on success, false on failure
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params) !== false;
    }
    
    /**
     * Count records in table
     * 
     * @param string $table Table name
     * @param string $where WHERE clause (default: '1=1')
     * @param array $params WHERE clause parameters
     * @return int Record count
     */
    public function count($table, $where = '1=1', $params = []) {
        $sql = "SELECT COUNT(*) as total FROM {$table} WHERE {$where}";
        $result = $this->fetchOne($sql, $params);
        return $result ? (int)$result['total'] : 0;
    }
    
    /**
     * Check if record exists
     * 
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params WHERE clause parameters
     * @return bool True if exists, false otherwise
     */
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }
    
    /**
     * Begin transaction
     * 
     * @return bool True on success
     */
    public function beginTransaction() {
        return $this->connect()->beginTransaction();
    }
    
    /**
     * Commit transaction
     * 
     * @return bool True on success
     */
    public function commit() {
        return $this->connect()->commit();
    }
    
    /**
     * Rollback transaction
     * 
     * @return bool True on success
     */
    public function rollback() {
        return $this->connect()->rollBack();
    }
    
    /**
     * Get last insert ID
     * 
     * @return string Last insert ID
     */
    public function lastInsertId() {
        return $this->connect()->lastInsertId();
    }
    
    /**
     * Escape string for use in LIKE queries
     * 
     * @param string $string String to escape
     * @return string Escaped string
     */
    public function escapeLike($string) {
        return str_replace(['%', '_'], ['\\%', '\\_'], $string);
    }
}

?>