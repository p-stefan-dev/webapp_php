<?php
/**
 * DataSource - Improved database handler with full UTF-8 support
 * @version 3.2 - Enhanced for Unicode and error handling
 */
namespace Phppot;

class DataSource
{
    // Database configuration
    const HOST = 'localhost';
    const USERNAME = 'root';
    const PASSWORD = '';
    const DATABASENAME = 'cru';

    private $connection;

    public function __construct()
    {
        $this->connection = $this->getConnection();
    }

    public function getConnection()
    {
        $connection = new \mysqli(self::HOST, self::USERNAME, self::PASSWORD, self::DATABASENAME);
        
        if ($connection->connect_errno) {
            throw new \RuntimeException("Database connection failed: " . $connection->connect_error);
        }
        
        $connection->set_charset("utf8mb4");
        return $connection;
    }

    public function getPdoConnection()
    {
        try {
            $dsn = 'mysql:host='.self::HOST.';dbname='.self::DATABASENAME.';charset=utf8mb4';
            $connection = new \PDO($dsn, self::USERNAME, self::PASSWORD, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ]);
            return $connection;
        } catch (\PDOException $e) {
            throw new \RuntimeException("PDO connection failed: " . $e->getMessage());
        }
    }

    private function bindQueryParams($statement, $paramType, $paramArray = array())
    {
        $params = array();
        $params[] = &$paramType;
        
        for ($i = 0; $i < count($paramArray); $i++) {
            $params[] = &$paramArray[$i];
        }
        
        call_user_func_array(array($statement, 'bind_param'), $params);
    }

    public function execute($query, $paramType = "", $paramArray = array())
    {
        $statement = $this->connection->prepare($query);
        if (!$statement) {
            throw new \RuntimeException("Prepare failed: " . $this->connection->error);
        }

        if (!empty($paramType) && !empty($paramArray)) {
            $this->bindQueryParams($statement, $paramType, $paramArray);
        }

        if (!$statement->execute()) {
            throw new \RuntimeException("Execute failed: " . $statement->error);
        }

        return $statement;
    }

    public function select($query, $paramType = "", $paramArray = array())
    {
        try {
            $statement = $this->execute($query, $paramType, $paramArray);
            $result = $statement->get_result();
            
            $dataset = array();
            while ($row = $result->fetch_assoc()) {
                $dataset[] = $row;
            }
            
            $statement->close();
            return $dataset;
            
        } catch (\Exception $e) {
            error_log("Database select error: " . $e->getMessage());
            return array();
        }
    }

    public function insert($query, $paramType, $paramArray)
    {
        try {
            $statement = $this->execute($query, $paramType, $paramArray);
            $insertId = $statement->insert_id;
            $statement->close();
            return $insertId;
        } catch (\Exception $e) {
            error_log("Database insert error: " . $e->getMessage());
            return 0;
        }
    }

    public function update($query, $paramType, $paramArray)
    {
        try {
            $statement = $this->execute($query, $paramType, $paramArray);
            $affectedRows = $statement->affected_rows;
            $statement->close();
            return $affectedRows;
        } catch (\Exception $e) {
            error_log("Database update error: " . $e->getMessage());
            return 0;
        }
    }

    public function delete($query, $paramType, $paramArray)
    {
        try {
            $statement = $this->execute($query, $paramType, $paramArray);
            $affectedRows = $statement->affected_rows;
            $statement->close();
            return $affectedRows;
        } catch (\Exception $e) {
            error_log("Database delete error: " . $e->getMessage());
            return 0;
        }
    }

    public function getRecordCount($query, $paramType = "", $paramArray = array())
    {
        try {
            $statement = $this->execute($query, $paramType, $paramArray);
            $statement->store_result();
            $count = $statement->num_rows;
            $statement->close();
            return $count;
        } catch (\Exception $e) {
            error_log("Database count error: " . $e->getMessage());
            return 0;
        }
    }
public function beginTransaction() {
    $this->getConnection()->begin_transaction();
}

public function commit() {
    $this->getConnection()->commit();
}

public function rollback() {
    $this->getConnection()->rollback();
}
    public function __destruct()
    {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}