<?php

class Config
{
    private $db;
    private $config = [];

    public function __construct()
    {
        $this->loadEnv();
        $this->connectDB();
        
    }

    private function loadEnv()
    {
        $envPath = __DIR__ . '/../.env';

        if (!file_exists($envPath)) {
            throw new Exception(".env file not found at $envPath");
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;

            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }

    private function connectDB()
    {
        $host = getenv('DB_HOST');
        $dbname = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASS');

        try {
            $this->db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    private function loadConfigFromDB()
    {
        $stmt = $this->db->query("SELECT config_key, config_value FROM settings");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->config[$row['config_key']] = $row['config_value'];
        }
    }

    public function get($key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function all()
    {
        return $this->config;
    }
    public function getDB()
{
    return $this->db;
}




   public function createTable($sql)
{
    try {
        $result = $this->db->exec($sql);

        // Extract table name from the SQL statement
        if (preg_match('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?(\w+)`?/i', $sql, $matches) ||
            preg_match('/CREATE\s+TABLE\s+`?(\w+)`?/i', $sql, $matches)) {
            $tableName = $matches[1];
        } else {
            $tableName = "Unknown";
        }

        echo "Table '$tableName' created successfully.\n<br/>";
    } catch (PDOException $e) {
        die("Table creation failed: " . $e->getMessage());
    }
}


}
