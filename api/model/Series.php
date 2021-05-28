<?php
namespace api\model;

//Check if CONSTANT called SITE_URL is defined.
if(!defined('SITE_URL')) {
    //Send 403 Forbidden response.
    header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
    //Kill the script.
    exit;
}

class Series{

    // database connection and table name
    private $conn;
    private $table_name = "series";
  
    // object properties
    public $id;
    public $name;
    public $brand_id;
    public $created;
    public $modified;
    public $status;
  
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    public function __destruct()
    {
        $this->conn = null;
    }
    
    //retrieve all picture to remove pic
    public function readAllRemoveID(){
        // select all query
        $query = "SELECT id
                FROM ". $this->table_name." 
                WHERE brand_id=:brand_id";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // sanitize
        $this->brand_id=htmlspecialchars(strip_tags($this->brand_id));
        // bind values
        $stmt->bindParam(":brand_id", $this->brand_id);
        // execute query
        $stmt->execute();        
        // get retrieved row
        $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // set values to object properties
        if(count($row) > 0)
        {
            foreach($row as $x)
                $this->id[] = $x['id'];
        }
    }

    // create category
    public function create(){
        // query to insert record
        $query = "INSERT INTO ".$this->table_name." SET name=:name, brand_id=:brand_id, created=:created";
    
        // prepare query
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->name=htmlspecialchars(strip_tags($this->name));
        $this->brand_id=htmlspecialchars(strip_tags($this->brand_id));
        $this->created=htmlspecialchars(strip_tags($this->created));
    
        // bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":brand_id", $this->brand_id);
        $stmt->bindParam(":created", $this->created);
    
        // execute query
        if($stmt->execute()){
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // update the type
    function update(){
    
        // update query
        $query = "UPDATE
                    " . $this->table_name . "
                SET "
                    .((isset($this->brand_id) && !empty($this->brand_id))? "brand_id=:brand_id,":"")
                    .((isset($this->name) && !empty($this->name))? "name = :name,":"")
                    ."modified=:modified
                WHERE
                    id = :id";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->name=(isset($this->name) && !empty($this->name))? htmlspecialchars(strip_tags($this->name)) : $this->name;
        $this->brand_id=(isset($this->brand_id) && !empty($this->brand_id))? htmlspecialchars(strip_tags($this->brand_id)) : $this->brand_id;
        $this->id=htmlspecialchars(strip_tags($this->id)); 
        $this->modified=htmlspecialchars(strip_tags($this->modified));
    
        // bind new values
        if(isset($this->name) && !empty($this->name))
            $stmt->bindParam(':name', $this->name);
        if(isset($this->brand_id) && !empty($this->brand_id))
            $stmt->bindParam(':brand_id', $this->brand_id);
        $stmt->bindParam(':modified', $this->modified);
        $stmt->bindParam(':id', $this->id);    
    
        // execute the query
        if($stmt->execute()){
            return true;
        }
    
        return false;
    }
    // delete the type by id
    function delete(){
    
        if(is_array($this->id) && !empty($this->id))
        {
            $flag = false;
            foreach($this->id as $x)
            {
                // delete query
                $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            
                // prepare query
                $stmt = $this->conn->prepare($query);
            
                // sanitize
                $x=htmlspecialchars(strip_tags($x));
            
                // bind id of record to delete
                $stmt->bindParam(1, $x);
            
                // execute query
                if($stmt->execute()){
                    $flag = true;
                }
            }
            return $flag;
        }
        else if(!is_array($this->id) && !empty($this->id))
        {
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
        }
        else
            return true;
    }
}
?>