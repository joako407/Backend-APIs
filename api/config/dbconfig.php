<?php
    try{
        $app_config = [
            "host" => "localhost",
            "dbname" => "cheannyo_chean",
            "dbusername" => "cheannyo_jeffindonchanty",
            "dbpassword" => "S]!gjl!S@M_h",
        ];
        
        $host = $app_config['host'];
        $conn = new PDO("mysql:host=$host;charset=utf8", $app_config['dbusername'], $app_config['dbpassword']);
        $sql = 'CREATE DATABASE $app_config["dbname"]';
        // use exec() because no results are returned
        $conn->exec($sql);
        //echo "Database created successfully<br>";
    }catch(\PDOException $e) {
        echo $sql . "<br>" . $e->getMessage();
    }
    $conn = null;
?>