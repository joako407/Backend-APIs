<?php
namespace api\model;
//Check if CONSTANT called SITE_URL is defined.
if(!defined('SITE_URL')) {
    //Send 403 Forbidden response.
    header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
    //Kill the script.
    exit;
}

class Brand{

    // database connection and table name
    private $conn;
    private $table_name = "brands";
  
    // object properties
    public $id;
    public $name;
    public $picture;
    public $series;
    public $created;
    public $modified;
  
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }
    
    public function __destruct()
    {
        $this->conn = null;
    }
    //read category
    public function read(){
        // select all query
        $query = "SELECT 
                    b.id as brandID, 
                    b.name, 
                    i.pic_path,
                    s.id as seriesID,
                    s.name as seriesName
                FROM ". $this->table_name." 
                b LEFT JOIN pictures i
                ON b.id = i.target_id
                LEFT JOIN series s
                ON s.brand_id = b.id
                WHERE i.type='b'";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // execute query
        $stmt->execute();        
        return $stmt;
    }
    
    public function getAll(){
        // select all query
        $query = "SELECT 
                    b.id as brandID, 
                    b.name, 
                    i.pic_path,
                    s.id as seriesID,
                    s.name as seriesName,
                    c.id as categoryID,
                    c.name as categoryName
                FROM ". $this->table_name." 
                b LEFT JOIN pictures i
                ON b.id = i.target_id
                LEFT JOIN series s
                ON s.brand_id = b.id
                LEFT JOIN products p
                ON p.brand_id = b.id
                LEFT JOIN categories c
                ON p.category_id = c.id 
                WHERE i.type='b'";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // execute query
        $stmt->execute();        
        return $stmt;
    }
    
    //read one brand 
    public function readOne(){
        // select all query
        $query = "SELECT 
                    b.id as brandID, 
                    b.name, 
                    i.pic_path,
                    s.id as seriesID,
                    s.name as seriesName
                FROM ". $this->table_name." 
                b LEFT JOIN pictures i
                ON b.id = i.target_id
                LEFT JOIN series s
                ON s.brand_id = b.id
                WHERE i.type='b'
                AND b.id = ?";
        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id));

        // bind values
        $stmt->bindParam(1, $this->id);

        // execute query
        $stmt->execute();

        $this->series = Array();

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
            // extract row
            // this will make $row['name'] to
            // just $name only
            extract($row);
            $this->id = $brandID;
            $this->name = $name;
            $this->picture = $pic_path;
            array_push($this->series,array(
                "id" => $seriesID,
                "name" => $seriesName,
            )); 
        }
    }

    // create category
    public function create(){
        // query to insert record
        $query = "INSERT INTO ".$this->table_name." SET name=:name, created=:created";
    
        // prepare query
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->name=htmlspecialchars(strip_tags($this->name));
        $this->created=htmlspecialchars(strip_tags($this->created));
    
        // bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":created", $this->created);
    
        // execute query
        if($stmt->execute()){
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // update the category
    function update(){
    
        // update query
        $query = "UPDATE
                    " . $this->table_name . "
                SET
                    name = :name,
                    modified = :modified
                WHERE
                    id = :id";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->name=htmlspecialchars(strip_tags($this->name));
        $this->modified=htmlspecialchars(strip_tags($this->modified));
        $this->id=htmlspecialchars(strip_tags($this->id)); 
    
        // bind new values
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':modified', $this->modified);
        $stmt->bindParam(':id', $this->id);    
    
        // execute the query
        if($stmt->execute()){
            return true;
        }
    
        return false;
    }
    // delete the category
    function delete(){
    
        // delete query
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
    
        // prepare query
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id));
    
        // bind id of record to delete
        $stmt->bindParam(1, $this->id);
    
        // execute query
        if($stmt->execute()){
            return true;
        }
    
        return false;
    }

}
?>