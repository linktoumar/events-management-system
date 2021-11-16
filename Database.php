<?php

class Database{
    
    private static $instance = null;
    private $connection;
    
    private function __construct(){
        $config = include('config.php');
        try{
            $this->connection = new PDO(
                "mysql:host={$config->db['HOST']};dbname={$config->db['NAME']}", 
                $config->db['USERNAME'],
                $config->db['PASSWORD']
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    public static function getInstance(){
        if (self::$instance == null){
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(){ 
        return $this->connection;
    }

}

?>