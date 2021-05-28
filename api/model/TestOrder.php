<?php
namespace api\model;
//Check if CONSTANT called SITE_URL is defined.
if(!defined('SITE_URL')) {
    //Send 403 Forbidden response.
    header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
    //Kill the script.
    exit;
}

class TestOrder{

    // database connection and table name
    private $conn;
    private $table_name = "testorder";
  
    // object properties
    public $id;
    public $status;
    public $created;

    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    // create category
    public function create(){
        // query to insert record
        $query = "INSERT INTO ".$this->table_name." 
            SET status=:status";
    
        // prepare query
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->status=htmlspecialchars(strip_tags($this->status));

        // bind values
        $stmt->bindParam(":status", $this->status);

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
                SET "
                    .((isset($this->name) && !empty($this->name))? "name = :name,":"")
                    .((isset($this->description) && !empty($this->description))? "description = :description,":"")
                    ."modified = :modified
                WHERE
                    id = :id";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->name=(isset($this->name) && !empty($this->name))? htmlspecialchars(strip_tags($this->name)) : $this->name;
        $this->description=(isset($this->description) && !empty($this->description))? htmlspecialchars(strip_tags($this->description)) : $this->description;
        $this->modified=htmlspecialchars(strip_tags($this->modified));
        $this->id=htmlspecialchars(strip_tags($this->id)); 
    
        // bind new values
        if(isset($this->name) && !empty($this->name))
            $stmt->bindParam(':name', $this->name);
        if(isset($this->description) && !empty($this->description))
            $stmt->bindParam(':description', $this->description);
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