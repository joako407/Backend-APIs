<?php
namespace api\model;
//Check if CONSTANT called SITE_URL is defined.
if(!defined('SITE_URL')) {
    //Send 403 Forbidden response.
    header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
    //Kill the script.
    exit;
}

class Category{

    // database connection and table name
    private $conn;
    private $table_name = "categories";
  
    // object properties
    public $id;
    public $name;
    public $description;
    public $picture;
    public $types;
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
   
        $query = "SELECT * FROM 
                    (SELECT 
                        c.id as categoryID, 
                        c.name, 
                        c.description, 
                        c.created, 
                        c.modified, 
                        i.pic_path 
                        FROM categories c 
                        LEFT JOIN pictures i 
                        ON c.id = i.target_id 
                        WHERE i.type ='c') t1 
                        LEFT JOIN 
                        (SELECT
                            types.id as typeID, 
                            types.category_id,
                            types.name as typeName,
                            pictures.pic_path as typePic 
                            FROM types,pictures 
                            WHERE types.id = pictures.target_id 
                            AND pictures.type='t')t2 
                            ON t1.categoryID = t2.category_id ";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // execute query
        $stmt->execute();        
        return $stmt;
    }

    //read one category
    public function readOne(){
        // select all query
   
        $query = "SELECT * FROM 
                    (SELECT 
                        c.id as categoryID, 
                        c.name, 
                        c.description, 
                        c.created, 
                        c.modified, 
                        i.pic_path 
                        FROM categories c 
                        LEFT JOIN pictures i 
                        ON c.id = i.target_id 
                        WHERE i.type ='c') t1 
                        LEFT JOIN 
                        (SELECT
                            types.id as typeID, 
                            types.category_id,
                            types.name as typeName,
                            pictures.pic_path as typePic 
                            FROM types,pictures 
                            WHERE types.id = pictures.target_id 
                            AND pictures.type='t')t2 
                            ON t1.categoryID = t2.category_id WHERE categoryID = ?";
        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id));
    
        // bind values
        $stmt->bindParam(1, $this->id);

        // execute query
        $stmt->execute();        
        
        $this->types = Array();

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
            // extract row
            // this will make $row['name'] to
            // just $name only
            extract($row);
            $this->id = $categoryID;
            $this->name = $name;
            $this->description = $description;
            $this->picture = $pic_path;
            array_push($this->types,array(
                "id" => $typeID,
                "name" => $typeName,
                "picture" => $typePic
            )); 
        }
    }

    // create category
    public function create(){
        // query to insert record
        $query = "INSERT INTO ".$this->table_name." SET name=:name, description=:description, created=:created";
    
        // prepare query
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->name=htmlspecialchars(strip_tags($this->name));
        $this->description=htmlspecialchars(strip_tags($this->description));
        $this->created=htmlspecialchars(strip_tags($this->created));
    
        // bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
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