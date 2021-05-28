<?php
namespace api\model;
use utility\Compressor\ImageCompressor;

//Check if CONSTANT called SITE_URL is defined.
if(!defined('SITE_URL')) {
    //Send 403 Forbidden response.
    header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
    //Kill the script.
    exit;
}

class Picture{

    // database connection and table name
    private $conn;
    private $table_name = "pictures";
    private $table_name2 = "pictures_temp";
  
    // object properties
    public $id;
    public $target_id;
    public $folder_name;
    public $type;
    public $pictures;
    public $remove_pic;
    public $compress;
  
    // constructor with $db as database connection
    public function __construct($db, $type){
        $this->conn = $db;
        $this->type = $type;
        switch($this->type)
        {
            case "p":
                $this->folder_name = "products";
                break;
            case "c":
                $this->folder_name = "categories";
                break;
            case "b":
                $this->folder_name = "brands";
                break;
            case "t":
                $this->folder_name = "types";
                break;
            case "d":
                $this->folder_name = "product features";
                break;
            case "t":
                $this->folder_name = "types";
                break;
            default:
                echo "invalid type";
        }
    }

    public function __destruct()
    {
        $this->conn = null;
    }
    
    //read picture
    public function read(){
        // select all query
        $query = "SELECT id, target_id, pic_path, type 
                FROM ". $this->table_name." 
                WHERE target_id=:target_id AND type=:type";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // sanitize
        $this->target_id=htmlspecialchars(strip_tags($this->target_id));
        $this->type=htmlspecialchars(strip_tags($this->type));
        // bind values
        $stmt->bindParam(":target_id", $this->target_id);
        $stmt->bindParam(":type", $this->type);
        // execute query
        $stmt->execute();        
        // get retrieved row
        $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // set values to object properties
        if(count($row) > 0)
        {
            foreach($row as $x)
                $this->pictures[] = $x['pic_path'];
        }
    }
    
    public function read2(){
        // select all query
        $query = "SELECT id, target_id, pic_path, type 
                FROM ". $this->table_name2." 
                WHERE target_id=:target_id AND type=:type";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // sanitize
        $this->target_id=htmlspecialchars(strip_tags($this->target_id));
        $this->type=htmlspecialchars(strip_tags($this->type));
        // bind values
        $stmt->bindParam(":target_id", $this->target_id);
        $stmt->bindParam(":type", $this->type);
        // execute query
        $stmt->execute();        
        // get retrieved row
        $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // set values to object properties
        if(count($row) > 0)
        {
            foreach($row as $x)
                $this->pictures[] = $x['pic_path'];
        }
    }

    //retrieve all picture to remove pic
    public function readAllRemovePicture(){
        // select all query
        $query = "SELECT id, target_id, pic_path, type 
                FROM ". $this->table_name." 
                WHERE target_id=:target_id AND type=:type";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // sanitize
        $this->target_id=htmlspecialchars(strip_tags($this->target_id));
        $this->type=htmlspecialchars(strip_tags($this->type));
        // bind values
        $stmt->bindParam(":target_id", $this->target_id);
        $stmt->bindParam(":type", $this->type);
        // execute query
        $stmt->execute();        
        // get retrieved row
        $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // set values to object properties
        if(count($row) > 0)
        {
            foreach($row as $x)
                $this->remove_pic[] = $x['pic_path'];
        }
    }
    
    public function readAllRemovePicture2(){
        // select all query
        $query = "SELECT id, target_id, pic_path, type 
                FROM ". $this->table_name2." 
                WHERE target_id=:target_id AND type=:type";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // sanitize
        $this->target_id=htmlspecialchars(strip_tags($this->target_id));
        $this->type=htmlspecialchars(strip_tags($this->type));
        // bind values
        $stmt->bindParam(":target_id", $this->target_id);
        $stmt->bindParam(":type", $this->type);
        // execute query
        $stmt->execute();        
        // get retrieved row
        $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // set values to object properties
        if(count($row) > 0)
        {
            foreach($row as $x)
                $this->remove_pic[] = $x['pic_path'];
        }
    }

    // create picture
    public function create(){
        $flag = false;
        if(isset($this->target_id) && 
            isset($this->pictures) && 
            isset($this->compress) && 
            isset($this->folder_name)){
            $this->uploadPicture();
            foreach($this->pictures as $x)
            {
                // query to insert record
                $query = "INSERT INTO ".$this->table_name." 
                SET 
                    target_id=:target_id, 
                    pic_path=:pic_path,
                    type=:type";

                // prepare query
                $stmt = $this->conn->prepare($query);

                // sanitize
                $this->target_id=htmlspecialchars(strip_tags($this->target_id));
                $x=htmlspecialchars(strip_tags($x));
                $this->type=htmlspecialchars(strip_tags($this->type));

                // bind values
                $stmt->bindParam(":target_id", $this->target_id);
                $stmt->bindParam(":pic_path", $x);
                $stmt->bindParam(":type", $this->type);
                // execute query
                if($stmt->execute()){
                    $flag = true;
                }
                else
                    $flag = false;
            }
        }
        else
            $flag = true;
        return $flag;
    }
    
    public function create2($id){
        $flag = false;
        if(isset($this->target_id) && 
            isset($this->pictures) && 
            isset($this->compress) && 
            isset($this->folder_name)){
            $this->uploadPicture2($id);
            foreach($this->pictures as $x)
            {
                // query to insert record
                $query = "INSERT INTO ".$this->table_name2." 
                SET 
                    target_id=:target_id, 
                    pic_path=:pic_path,
                    type=:type";

                // prepare query
                $stmt = $this->conn->prepare($query);

                // sanitize
                $this->target_id=htmlspecialchars(strip_tags($this->target_id));
                $x=htmlspecialchars(strip_tags($x));
                $this->type=htmlspecialchars(strip_tags($this->type));

                // bind values
                $stmt->bindParam(":target_id", $this->target_id);
                $stmt->bindParam(":pic_path", $x);
                $stmt->bindParam(":type", $this->type);
                // execute query
                if($stmt->execute()){
                    $flag = true;
                }
                else
                    $flag = false;
            }
        }
        else
            $flag = true;
        return $flag;
    }

    //update picture
    public function update(){
        $flag = false;
        if(isset($this->pictures) && !empty($this->pictures))
        {
            $flag = $this->create();
        }
        if(isset($this->remove_pic) && !empty($this->remove_pic))
        {
            $flag = $this->delete();
        }
        if(is_null($this->pictures) && is_null($this->remove_pic))
            $flag = true;
        return $flag;
    }
    
    public function update2($id){
        $flag = false;
        if(isset($this->pictures) && !empty($this->pictures))
        {
            $flag = $this->create2($id);
        }
        if(isset($this->remove_pic) && !empty($this->remove_pic))
        {
            $flag = $this->delete2();
        }
        if(is_null($this->pictures) && is_null($this->remove_pic))
            $flag = true;
        return $flag;
    }

    public function delete(){
        $flag = false;
        //only for update action 
        if(isset($this->remove_pic)){
            $this->deletePicture();
            foreach($this->remove_pic as $x)
            {
                // delete query
                $query = "DELETE FROM " . $this->table_name . 
                " WHERE pic_path = ?";

                // prepare query
                $stmt = $this->conn->prepare($query);

                // sanitize
                $x=htmlspecialchars(strip_tags($x));

                // bind pic_path of record to delete
                $stmt->bindParam(1, $x);

                // execute query
                if($stmt->execute()){
                    $flag = true;
                }
                else
                    $flag = false;
            }
        }else{
            $flag = true;
        }
        //only for delete action 
        return $flag;
    }
    
    public function delete2(){
        $flag = false;
        //only for update action 
        if(isset($this->remove_pic)){
            $this->deletePicture2();
            foreach($this->remove_pic as $x)
            {
                // delete query
                $query = "DELETE FROM " . $this->table_name2 . 
                " WHERE pic_path = ?";

                // prepare query
                $stmt = $this->conn->prepare($query);

                // sanitize
                $x=htmlspecialchars(strip_tags($x));

                // bind pic_path of record to delete
                $stmt->bindParam(1, $x);

                // execute query
                if($stmt->execute()){
                    $flag = true;
                }
                else
                    $flag = false;
            }
        }else{
            $flag = true;
        }
        //only for delete action 
        return $flag;
    }

    //delete picture from server
    private function deletePicture(){

        //multiple file to loop
        foreach($this->remove_pic as $x)
        {
            if(!empty($x))
            {
                $x = "/home/cheannyo/public_html".DIRECTORY_SEPARATOR.$x;
                if(is_file($x)){
                    unlink($x);
                }    
                else{
                    echo($x." doesn't exists!"); 
                }
            }
        }
    }
    //delete picture from server
    private function deletePicture2(){

        //multiple file to loop
        foreach($this->remove_pic as $x)
        {
            if(!empty($x))
            {
                $x = "/home/cheannyo/public_html".DIRECTORY_SEPARATOR.$x;
                if(is_file($x)){
                    unlink($x);
                }    
                else{
                    echo($x." doesn't exists!"); 
                }
            }
        }
    }

    //update picture to server
    private function uploadPicture(){

        // create the directory if the directory doesn't exist
        //$root_path = "..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."images".DIRECTORY_SEPARATOR.$this->folder_name.DIRECTORY_SEPARATOR;
        $root_path = "/home/cheannyo/public_html/images".DIRECTORY_SEPARATOR.$this->folder_name.DIRECTORY_SEPARATOR;
        if (!file_exists($root_path)) {
            mkdir($root_path, 0777, true);
        }

        //multiple file to loop
        foreach($this->pictures as $key=>$file_tmp_name)
        {
            if(is_uploaded_file($file_tmp_name))
            {
                $valid_ext = array('png','jpeg','jpg'); // Valid extension
                $picName = uniqid().".jpg"; // generate a unique name for picture
                $pic_path = $root_path.$picName; // location

                // file extension
                $file_extension = pathinfo($pic_path, PATHINFO_EXTENSION);
                $file_extension = strtolower($file_extension);
                // Check extension
                if(in_array($file_extension,$valid_ext)){
                    // Compress Image
                    if($this->compress)
                        ImageCompressor::compress($file_tmp_name,$pic_path,15);
                    else
                        move_uploaded_file($file_tmp_name, $pic_path);
                }else{
                    echo "Invalid file type.";
                }

                $pic_path = str_replace('/home/cheannyo/public_html/', '', $pic_path); // change the format as to store into database
                $this->pictures[$key] = $pic_path;            
            }
        }
    } 

    private function uploadPicture2($id){

        // create the directory if the directory doesn't exist
        //$root_path = "..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."images".DIRECTORY_SEPARATOR.$this->folder_name.DIRECTORY_SEPARATOR;
        $root_path = "/home/cheannyo/public_html/images".DIRECTORY_SEPARATOR.$this->folder_name.DIRECTORY_SEPARATOR;
        if (!file_exists($root_path)) {
            mkdir($root_path, 0777, true);
        }

        //multiple file to loop
        foreach($this->pictures as $key=>$file_tmp_name)
        {
            if(is_uploaded_file($file_tmp_name))
            {
                $valid_ext = array('png','jpeg','jpg'); // Valid extension
                $picName = "temp_".$id."_".uniqid().".jpg"; // generate a unique name for picture
                $pic_path = $root_path.$picName; // location

                // file extension
                $file_extension = pathinfo($pic_path, PATHINFO_EXTENSION);
                $file_extension = strtolower($file_extension);
                // Check extension
                if(in_array($file_extension,$valid_ext)){
                    // Compress Image
                    if($this->compress)
                        ImageCompressor::compress($file_tmp_name,$pic_path,15);
                    else
                        move_uploaded_file($file_tmp_name, $pic_path);
                }else{
                    echo "Invalid file type.";
                }

                $pic_path = str_replace('/home/cheannyo/public_html/', '', $pic_path); // change the format as to store into database
                $this->pictures[$key] = $pic_path;            
            }
        }
    } 

    public function copyPicture($id){

        //multiple file to loop
        foreach($this->pictures as $pic)
        {
            // copy file from original product
            $filename = basename($pic);
            $path = str_replace($filename, "", $pic);
            
            // prepare for paths
            $source_path = "/home/cheannyo/public_html/$pic";
            $destination_path = "/home/cheannyo/public_html/$path"."temp_$id"."_".$filename;
            $path_in_db = $path."temp_$id"."_".$filename;

            
            copy($source_path,$destination_path);

            //insert into database
            $query = "INSERT INTO ".$this->table_name2." 
                SET 
                    target_id=:target_id, 
                    pic_path=:pic_path,
                    type=:type";

                // prepare query
                $stmt = $this->conn->prepare($query);

                // sanitize
                $this->target_id=htmlspecialchars(strip_tags($this->target_id));
                $path_in_db=htmlspecialchars(strip_tags($path_in_db));
                $this->type=htmlspecialchars(strip_tags($this->type));

                // bind values
                $stmt->bindParam(":target_id", $this->target_id);
                $stmt->bindParam(":pic_path", $path_in_db);
                $stmt->bindParam(":type", $this->type);
                // execute query
                $stmt->execute();
        }
    }
    public function copyTempPicture(){

        //multiple file to loop
        foreach($this->pictures as $pic)
        {
            // copy file from original product
            $filename = basename($pic);
            $path = str_replace($filename, "", $pic);

            $fileNameSplit = explode("_",$filename);
            $filename = $fileNameSplit[2]; 
            
            // prepare for paths
            $source_path = "/home/cheannyo/public_html/$pic";
            $destination_path = "/home/cheannyo/public_html/$path".$filename;
            $path_in_db = $path.$filename;

            
            copy($source_path,$destination_path);

            //insert into database
            $query = "INSERT INTO ".$this->table_name." 
                SET 
                    target_id=:target_id, 
                    pic_path=:pic_path,
                    type=:type";

                // prepare query
                $stmt = $this->conn->prepare($query);

                // sanitize
                $this->target_id=htmlspecialchars(strip_tags($this->target_id));
                $path_in_db=htmlspecialchars(strip_tags($path_in_db));
                $this->type=htmlspecialchars(strip_tags($this->type));

                // bind values
                $stmt->bindParam(":target_id", $this->target_id);
                $stmt->bindParam(":pic_path", $path_in_db);
                $stmt->bindParam(":type", $this->type);
                // execute query
                $stmt->execute();
        }
    }
}
?>