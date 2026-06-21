<?php

class DB
{
    private string $host;
    private string $port;
    private string $user;
    private string $password;
    private string $dbname;

    public function __construct()
    {
        $this->host = getenv('DB_HOST') ?: 'mysql-1b21f137-enthongy04-e0ef.i.aivencloud.com';
        $this->port = getenv('DB_PORT') ?: '25125';
        $this->user = getenv('DB_USER') ?: 'avnadmin';
        $this->password = getenv('DB_PASSWORD') ?: '';
        $this->dbname = getenv('DB_NAME') ?: 'defaultdb';
    }

    public function connect(): PDO
    {
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset=utf8mb4";
        return new PDO($dsn, $this->user, $this->password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ]);
    }
}
?>