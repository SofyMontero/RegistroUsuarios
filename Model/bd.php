<?php

if (basename($_SERVER['PHP_SELF']) == "bd.php")
    exit();
$dbConfig = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

class bd {

    private $con;
    private $stm;
    private $rs;

    public function __construct() {
        try {
             global $dbConfig;
             $dsn = sprintf(
                 'mysql:host=%s;dbname=%s;charset=%s',
                 $dbConfig['host'],
                 $dbConfig['database'],
                 $dbConfig['charset']
             );
             $this->con = new PDO($dsn, $dbConfig['username'], $dbConfig['password'],
                    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
            $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception $e) {
            echo json_encode($e->getMessage());
            die();
        }
    }

    public function desconectar() {
        $this->rs = null;
        $this->stm = null;
        $this->con = null;
    }

    public function findAll($query, $opc = "") {
        $this->stm = $this->con->prepare($query);
        $this->stm->execute();
        if ($opc) {
            $this->rs = $this->stm->fetchAll(PDO::FETCH_OBJ);
        } else {
            $this->rs = $this->stm->fetchAll(PDO::FETCH_ASSOC);
        }
        return $this->rs;
    }
    
    
    public function exec($query){
         $this->stm = $this->con->prepare($query);
         $this->stm->execute();
         return $this->stm->rowCount();        
    }

}
