<?php
class DB {
    private string $host;
    private string $port;
    private string $user;
    private string $password;
    private string $dbname;
    private string $ssl_ca;

    public function __construct() {
        // Use environment variables (set on Render) with fallback for local testing
        $this->host = getenv('DB_HOST') ?: 'mysql-1b21f137-enthongy04-e0ef.i.aivencloud.com';
        $this->port = getenv('DB_PORT') ?: '25125';
        $this->user = getenv('DB_USER') ?: 'avnadmin';
        $this->password = getenv('DB_PASSWORD') ?: 'AVNS_QvCkUaydRczGuCSg0-1';
        $this->dbname = getenv('DB_NAME') ?: 'defaultdb';
        $this->ssl_ca = getenv('DB_SSL_CA') ?: __DIR__ . '/../ca.pem';
    }

    public function connect(): PDO {
        // Build DSN with SSL
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_SSL_CA => $this->ssl_ca,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
        ];

        return new PDO($dsn, $this->user, $this->password, $options);
    }
}
?>
