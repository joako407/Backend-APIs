<?php
namespace api\model;
use utility\Encryptor\PasswordEncryptor;
use \Firebase\JWT\JWT;

require '../../vendor/autoload.php';

//Check if CONSTANT called SITE_URL is defined.
if(!defined('SITE_URL')) {
    //Send 403 Forbidden response.
    header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
    //Kill the script.
    exit;
}

class User{

    // database connection and table name
    private $conn;
    private $table_name = "users";
    private $table_name2 = "users_temp";
  
    // object properties
    public $id;
    public $username;
    public $firstname;
    public $lastname;
    public $gender;
    public $dob;
    public $email;
    public $password;
    public $hashed_password;
    public $created;
    public $modified;
    public $lastlogin;
    public $date_requested;
    public $token; // for store new token for reset password request
    public $token2; // for store token from client email reset pwd link 
    public $decrypt_key;
    public $action;
    public $user_id;
    public $executor_id;
    public $type;
    public $status;
    public $action_mode;
  
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    public function __destruct()
    {
        $this->conn = null;
    }

    public function read(){
        // select all user query
        $query = "SELECT 
                    id, 
                    username, 
                    firstname, 
                    lastname, 
                    gender, 
                    type, 
                    dob, 
                    email, 
                    status 
                FROM ". $this->table_name." 
                where type <> ? AND " 
                .((isset($this->type) && $this->type == "SUPERUSER")? "type <> 'CLIENT' ORDER BY type ASC": "")
                .((isset($this->type) && $this->type == "ADMIN")? "type <> 'CLIENT' AND type <> 'SUPERUSER' ORDER BY type ASC": "")
                .((isset($this->type) && $this->type == "MANAGER")? "type <> 'CLIENT' AND type <> 'ADMIN'  AND type <> 'SUPERUSER' ORDER BY type ASC": "");
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // sanitize
        $this->type=htmlspecialchars(strip_tags($this->type));

        $stmt->bindParam(1, $this->type);
        
        // execute query
        $stmt->execute(); 
        return $stmt;
    }

    public function read2(){
        // select all user query
        $query = "SELECT 
                    ut.id, 
                    ut.user_id,
                    ut.username, 
                    ut.firstname as fname, 
                    ut.lastname as lname, 
                    ut.gender, 
                    ut.type, 
                    ut.dob, 
                    ut.email, 
                    ut.action,
                    ut.executor_id,
                    u.firstname,
                    u.lastname,
                    ut.status ,
                    ut.created
                FROM $this->table_name2 ut
                LEFT JOIN users u
                ON ut.executor_id = u.id
                WHERE ut.executor_id=?";
                
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // sanitize
        $this->executor_id=htmlspecialchars(strip_tags($this->executor_id));

        $stmt->bindParam(1, $this->executor_id);
        
        // execute query
        $stmt->execute(); 
        return $stmt;
    }
    
    public function getAll(){
        // select all user query
        $query = "SELECT 
                    ut.id, 
                    ut.user_id,
                    ut.username, 
                    ut.firstname as fname, 
                    ut.lastname as lname, 
                    ut.gender, 
                    ut.type, 
                    ut.dob, 
                    ut.email, 
                    ut.action,
                    ut.executor_id,
                    u.firstname,
                    u.lastname,
                    ut.status,
                    ut.created 
                FROM $this->table_name2 ut
                LEFT JOIN users u
                ON ut.executor_id = u.id";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        
        // execute query
        $stmt->execute(); 
        return $stmt;
    }

    public function readClient(){
        // select all user query
        $query = "SELECT 
                    id, 
                    username, 
                    firstname, 
                    lastname, 
                    gender, 
                    type, 
                    dob, 
                    email, 
                    status 
                FROM ". $this->table_name." 
                where type='CLIENT'";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id));
        $this->t1="CLIENT";
        $this->t1=htmlspecialchars(strip_tags($this->t1));

        $stmt->bindParam(1, $this->id);
        $stmt->bindParam(2, $this->t1);
        // execute query
        $stmt->execute(); 
        return $stmt;
    }

    //read logined user
    public function readOne(){
        // select all query
        $query = "SELECT username, firstname, lastname, gender, dob, email, status FROM ". $this->table_name. " WHERE id=?";
        // prepare query statement
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(1, $this->id);
        // execute query
        $stmt->execute(); 

        $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // set values to object properties
        if(count($row) > 0)
        {
            $this->username = $row[0]['username'];
            $this->firstname = $row[0]['firstname'];
            $this->lastname = $row[0]['lastname'];
            $this->gender = $row[0]['gender'];
            $this->dob = $row[0]['dob'];
            $this->email = $row[0]['email'];
            $this->status = $row[0]['status'];                
        }
    }

    public function login(){
        $query = "SELECT id,type, password, status FROM ". $this->table_name. " 
                    WHERE (type='SUPERUSER' OR type='ADMIN' OR type='MANAGER' OR type='STAFF') AND "
                    .((isset($this->username) && !empty($this->username))? "username=:username" : "")
                    .((isset($this->email) && !empty($this->email))? "email=:email" : "");    

        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->username=(isset($this->username) && !empty($this->username))? htmlspecialchars(strip_tags($this->username)) : $this->username;
        $this->email=(isset($this->email) && !empty($this->email))? htmlspecialchars(strip_tags($this->email)) : $this->email;

        if(isset($this->username) && !empty($this->username))
            $stmt->bindParam(':username', $this->username);
        if(isset($this->email) && !empty($this->email))
            $stmt->bindParam(':email', $this->email); 

        $stmt->execute(); 

        $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // set values to object properties
        if(count($row) > 0)
        {
            $this->hashed_password = $row[0]['password'];
            $this->type = $row[0]['type'];
            $this->id = $row[0]['id'];
            $this->status = $row[0]['status'];
            if(PasswordEncryptor::decrypt($this->password, $this->hashed_password))
            {
                $this->lastlogin = date('Y-m-d H:i:s');
                $query = "UPDATE 
                            ". $this->table_name. " 
                        SET lastlogin=:lastlogin 
                        WHERE id=:id";

                // prepare query
                $stmt = $this->conn->prepare($query);
            
                // sanitize
                $this->lastlogin=htmlspecialchars(strip_tags($this->lastlogin));
                $this->id=htmlspecialchars(strip_tags($this->id));

                $stmt->bindParam(':lastlogin', $this->lastlogin);
                $stmt->bindParam(':id', $this->id);
                if($stmt->execute()){
                    return 1;
                }   
                else
                    return 2;
            }
            return 3;               
        }
        else
            return 4;
    }

    public function loginClient(){
        $query = "SELECT id, username, type, password, status FROM ". $this->table_name. " 
                    WHERE type='CLIENT' AND "
                    .((isset($this->username) && !empty($this->username))? "username=:username" : "")
                    .((isset($this->email) && !empty($this->email))? "email=:email" : "");    

        // prepare query
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->username=(isset($this->username) && !empty($this->username))? htmlspecialchars(strip_tags($this->username)) : $this->username;
        $this->email=(isset($this->email) && !empty($this->email))? htmlspecialchars(strip_tags($this->email)) : $this->email;

        if(isset($this->username) && !empty($this->username))
            $stmt->bindParam(':username', $this->username);
        if(isset($this->email) && !empty($this->email))
            $stmt->bindParam(':email', $this->email); 

        $stmt->execute(); 

        $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // set values to object properties
        if(count($row) > 0)
        {
            $this->hashed_password = $row[0]['password'];
            $this->username = $row[0]['username'];
            $this->type = $row[0]['type'];
            $this->id = $row[0]['id'];
            $this->status = $row[0]['status'];
            if(PasswordEncryptor::decrypt($this->password, $this->hashed_password))
            {
                $this->lastlogin = date('Y-m-d H:i:s');
                $query = "UPDATE 
                            ". $this->table_name. " 
                        SET lastlogin=:lastlogin 
                        WHERE id=:id";

                // prepare query
                $stmt = $this->conn->prepare($query);
            
                // sanitize
                $this->lastlogin=htmlspecialchars(strip_tags($this->lastlogin));
                $this->id=htmlspecialchars(strip_tags($this->id));

                $stmt->bindParam(':lastlogin', $this->lastlogin);
                $stmt->bindParam(':id', $this->id);
                if($stmt->execute()){
                    return 1;
                }   
                else
                    return 2;
            }
            return 3;               
        }
        else
            return 4;
    }

    public function checkExistUser(){
        $query = "SELECT id 
                FROM ". $this->table_name." 
                WHERE " 
                .((isset($this->username) && !empty($this->username))? "username=:username" : "")
                .((isset($this->email) && !empty($this->email))? "email=:email" : "")
                ." limit 1";

        // prepare query statement
        $stmt = $this->conn->prepare($query);


        // sanitize
        $this->username=(isset($this->username) && !empty($this->username))? htmlspecialchars(strip_tags($this->username)) : $this->username;
        $this->email=(isset($this->email) && !empty($this->email))? htmlspecialchars(strip_tags($this->email)) : $this->email;

        if(isset($this->username) && !empty($this->username))
            $stmt->bindParam(':username', $this->username);
        if(isset($this->email) && !empty($this->email))
            $stmt->bindParam(':email', $this->email); 

        // execute query
        $stmt->execute(); 
        return $stmt->rowCount();

    }
    public function create(){
        $query = "INSERT INTO ".$this->table_name." 
                    SET 
                        username=:username, 
                        firstname=:firstname,
                        lastname=:lastname,
                        gender=:gender,
                        dob=:dob,
                        type=:type,
                        email=:email,
                        password=:password,
                        created=:created,
                        status=:status";
    
        // prepare query
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->username=htmlspecialchars(strip_tags($this->username));
        $this->firstname=htmlspecialchars(strip_tags($this->firstname));
        $this->lastname=htmlspecialchars(strip_tags($this->lastname));
        $this->gender=htmlspecialchars(strip_tags($this->gender));
        $this->dob=htmlspecialchars(strip_tags($this->dob));
        $this->type=htmlspecialchars(strip_tags($this->type));
        $this->email=htmlspecialchars(strip_tags($this->email));
        $this->password=htmlspecialchars(strip_tags($this->password));
        $this->password=PasswordEncryptor::encrypt($this->password);
        $this->created=htmlspecialchars(strip_tags($this->created));
        $this->status=htmlspecialchars(strip_tags($this->status));

        // bind values
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":firstname", $this->firstname);
        $stmt->bindParam(":lastname", $this->lastname);
        $stmt->bindParam(":gender", $this->gender);
        $stmt->bindParam(":dob", $this->dob);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":created", $this->created);
        $stmt->bindParam(":status", $this->status);

        return $stmt->execute();
    }
    
    public function create2(){
        $query = "INSERT INTO ".$this->table_name2." 
                    SET 
                        username=:username, 
                        firstname=:firstname,
                        lastname=:lastname,
                        gender=:gender,
                        dob=:dob,
                        type=:type,
                        email=:email,
                        password=:password,
                        action=:action,
                        executor_id=:executor_id,
                        created=:created,
                        status=:status";
    
        // prepare query
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->username=htmlspecialchars(strip_tags($this->username));
        $this->firstname=htmlspecialchars(strip_tags($this->firstname));
        $this->lastname=htmlspecialchars(strip_tags($this->lastname));
        $this->gender=htmlspecialchars(strip_tags($this->gender));
        $this->dob=htmlspecialchars(strip_tags($this->dob));
        $this->type=htmlspecialchars(strip_tags($this->type));
        $this->email=htmlspecialchars(strip_tags($this->email));
        $this->password=htmlspecialchars(strip_tags($this->password));
        $this->password=PasswordEncryptor::encrypt($this->password);
        $this->action=htmlspecialchars(strip_tags($this->action));
        $this->executor_id=htmlspecialchars(strip_tags($this->executor_id));
        $this->created=htmlspecialchars(strip_tags($this->created));
        $this->status=htmlspecialchars(strip_tags($this->status));

        // bind values
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":firstname", $this->firstname);
        $stmt->bindParam(":lastname", $this->lastname);
        $stmt->bindParam(":gender", $this->gender);
        $stmt->bindParam(":dob", $this->dob);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":action", $this->action);
        $stmt->bindParam(":executor_id", $this->executor_id);
        $stmt->bindParam(":created", $this->created);
        $stmt->bindParam(":status", $this->status);

        return $stmt->execute();
    }

    // create user
    public function register(){
        // query to insert record
        $query = "INSERT INTO ".$this->table_name." 
                    SET 
                        username=:username, 
                        firstname=:firstname,
                        lastname=:lastname,
                        gender=:gender,
                        dob=:dob,
                        type=:type,
                        email=:email,
                        password=:password,
                        created=:created,
                        status=:status";
    
        // prepare query
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->username=htmlspecialchars(strip_tags($this->username));
        $this->firstname=htmlspecialchars(strip_tags($this->firstname));
        $this->lastname=htmlspecialchars(strip_tags($this->lastname));
        $this->gender=htmlspecialchars(strip_tags($this->gender));
        $this->dob=htmlspecialchars(strip_tags($this->dob));
        $this->type=htmlspecialchars(strip_tags($this->type));
        $this->email=htmlspecialchars(strip_tags($this->email));
        $this->password=htmlspecialchars(strip_tags($this->password));
        $this->password=PasswordEncryptor::encrypt($this->password);
        $this->created=htmlspecialchars(strip_tags($this->created));
        $this->status=htmlspecialchars(strip_tags($this->status));

        // bind values
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":firstname", $this->firstname);
        $stmt->bindParam(":lastname", $this->lastname);
        $stmt->bindParam(":gender", $this->gender);
        $stmt->bindParam(":dob", $this->dob);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":created", $this->created);
        $stmt->bindParam(":status", $this->status);

        // execute query
        if($stmt->execute()){
            $this->id = $this->conn->lastInsertId();
            $this->token = bin2hex(openssl_random_pseudo_bytes(24));

            $query = "INSERT INTO verify 
                    SET 
                        user_id=:user_id, 
                        token=:token";
            // prepare query
            $stmt = $this->conn->prepare($query);
            
            // bind values
            $stmt->bindParam(":user_id", $this->id);
            $stmt->bindParam(":token", $this->token);

            return $stmt->execute();
        }
        return false;
    }

    // update the user
    public function update(){
    
        // update query
        $query = "UPDATE
                    " . $this->table_name . "
                SET "
                    .((isset($this->firstname) && !empty($this->firstname))? "firstname = :firstname," : "")
                    .((isset($this->lastname) && !empty($this->lastname))? "lastname = :lastname," : "")
                    .((isset($this->email) && !empty($this->email))? "email = :email," : "")
                    .((isset($this->gender) && !empty($this->gender))? "gender = :gender," : "")
                    .((isset($this->dob) && !empty($this->dob))? "dob = :dob," : "")
                    .((isset($this->type) && !empty($this->type))? "type = :type," : "")
                    .((isset($this->status))? "status = :status," : "")
                    ."modified = :modified
                WHERE
                    id = :id";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // sanitize\
        $this->id=htmlspecialchars(strip_tags($this->id)); 
        $this->modified=htmlspecialchars(strip_tags($this->modified));  
        $this->firstname=(isset($this->firstname) && !empty($this->firstname))? htmlspecialchars(strip_tags($this->firstname)) : $this->firstname;
        $this->lastname=(isset($this->lastname) && !empty($this->lastname))? htmlspecialchars(strip_tags($this->lastname)) : $this->lastname;
        $this->email=(isset($this->email) && !empty($this->email))? htmlspecialchars(strip_tags($this->email)) : $this->email;
        $this->gender=(isset($this->gender) && !empty($this->gender))? htmlspecialchars(strip_tags($this->gender)) : $this->gender;
        $this->dob=(isset($this->dob) && !empty($this->dob))? htmlspecialchars(strip_tags($this->dob)) : $this->dob;
        $this->type=(isset($this->type) && !empty($this->type))? htmlspecialchars(strip_tags($this->type)) : $this->type;
        $this->status=(isset($this->status))? htmlspecialchars(strip_tags($this->status)) : $this->status;
        
    
        // bind new values
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':modified', $this->modified);
        if(isset($this->firstname) && !empty($this->firstname))
            $stmt->bindParam(':firstname', $this->firstname);
        if(isset($this->lastname) && !empty($this->lastname))
            $stmt->bindParam(':lastname', $this->lastname);  
        if(isset($this->email) && !empty($this->email))
            $stmt->bindParam(':email', $this->email);  
        if(isset($this->gender) && !empty($this->gender))
            $stmt->bindParam(':gender', $this->gender);
        if(isset($this->dob) && !empty($this->dob))
            $stmt->bindParam(':dob', $this->dob);
        if(isset($this->type) && !empty($this->type))
            $stmt->bindParam(':type', $this->type);
        if(isset($this->status))
            $stmt->bindParam(':status', $this->status);
        // execute the query
        if($stmt->execute()){
            return true;
        }
        return false;
    }
    
    // update the user
    public function update2(){
        
        $this->action_mode = $this->checkExists();
        if($this->action_mode === 1){
            $this->user_id = $this->id;
            $this->copyUserInfo("edit");
        }
        else if($this->action_mode === 2){
            $this->action = "create";
        }
        else if($this->action_mode === 3){
            $this->action = "edit";
        }
       
        // update query
        $query = "UPDATE
                    " . $this->table_name2 . "
                SET "
                    .((isset($this->firstname) && !empty($this->firstname))? "firstname = :firstname," : "")
                    .((isset($this->lastname) && !empty($this->lastname))? "lastname = :lastname," : "")
                    .((isset($this->email) && !empty($this->email))? "email = :email," : "")
                    .((isset($this->gender) && !empty($this->gender))? "gender = :gender," : "")
                    .((isset($this->dob) && !empty($this->dob))? "dob = :dob," : "")
                    .((isset($this->type) && !empty($this->type))? "type = :type," : "")
                    .((isset($this->action) && !empty($this->action))? "action = :action," : "")
                    .((isset($this->executor_id) && !empty($this->executor_id))? "executor_id = :executor_id," : "")     
                    .((isset($this->status))? "status = :status," : "")
                    ."modified = :modified
                    WHERE id = :id";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // sanitize\
        $this->id=htmlspecialchars(strip_tags($this->id)); 
        $this->created=htmlspecialchars(strip_tags($this->created));  
        $this->modified=htmlspecialchars(strip_tags($this->modified));  
        $this->firstname=(isset($this->firstname) && !empty($this->firstname))? htmlspecialchars(strip_tags($this->firstname)) : $this->firstname;
        $this->lastname=(isset($this->lastname) && !empty($this->lastname))? htmlspecialchars(strip_tags($this->lastname)) : $this->lastname;
        $this->email=(isset($this->email) && !empty($this->email))? htmlspecialchars(strip_tags($this->email)) : $this->email;
        $this->gender=(isset($this->gender) && !empty($this->gender))? htmlspecialchars(strip_tags($this->gender)) : $this->gender;
        $this->dob=(isset($this->dob) && !empty($this->dob))? htmlspecialchars(strip_tags($this->dob)) : $this->dob;
        $this->type=(isset($this->type) && !empty($this->type))? htmlspecialchars(strip_tags($this->type)) : $this->type;
        $this->status=(isset($this->status))? htmlspecialchars(strip_tags($this->status)) : $this->status;
        $this->executor_id=(isset($this->executor_id) && $this->executor_id !== '')? htmlspecialchars(strip_tags($this->executor_id)) : $this->executor_id;       
        $this->action=(isset($this->action) && $this->action !== '')? htmlspecialchars(strip_tags($this->action)) : $this->action; 
        
    
        // bind new values
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':modified', $this->modified);
        if(isset($this->firstname) && !empty($this->firstname))
            $stmt->bindParam(':firstname', $this->firstname);
        if(isset($this->lastname) && !empty($this->lastname))
            $stmt->bindParam(':lastname', $this->lastname);  
        if(isset($this->email) && !empty($this->email))
            $stmt->bindParam(':email', $this->email);  
        if(isset($this->gender) && !empty($this->gender))
            $stmt->bindParam(':gender', $this->gender);
        if(isset($this->dob) && !empty($this->dob))
            $stmt->bindParam(':dob', $this->dob);
        if(isset($this->type) && !empty($this->type))
            $stmt->bindParam(':type', $this->type);
        if(isset($this->status))
            $stmt->bindParam(':status', $this->status);
        if(isset($this->executor_id) && $this->executor_id !== '')
            $stmt->bindParam(':executor_id', $this->executor_id);
        if(isset($this->action) && $this->action !== '')
            $stmt->bindParam(':action', $this->action);
        // execute the query
        if($stmt->execute()){
            return true;
        }
        return false;
    }
    // delete the category
    public function delete(){
    
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
    
    public function delete2(){
    
        // delete query
        $query = "DELETE FROM " . $this->table_name2 . " WHERE id = ?";
    
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
    //reset password
    public function resetpassword(){
        // this query => check this email is exists or not
        $query = "SELECT id, username, email FROM users WHERE type = 'CLIENT' AND "
                .((isset($this->username) && !empty($this->username))? "username=:username" : "")
                .((isset($this->email) && !empty($this->email))? "email=:email" : "")
                ." LIMIT 1";

        //prepare query
        $stmt = $this->conn->prepare($query);

        //sanitize
        $this->email=(isset($this->email) && !empty($this->email))? htmlspecialchars(strip_tags($this->email)) : $this->email;
        $this->username=(isset($this->username) && !empty($this->username))? htmlspecialchars(strip_tags($this->username)) : $this->username;

        //bind data to check
        if(isset($this->email) && !empty($this->email))
            $stmt->bindParam(':email', $this->email);
        if(isset($this->username) && !empty($this->username))
            $stmt->bindParam(':username', $this->username);

        //execute query
        $stmt->execute();

        $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // set values to object properties
        if(count($row) > 0)
        {
            $this->id = $row[0]['id'];
            $this->username = strtolower($row[0]['username']);
            $this->email = $row[0]['email'];
            $this->date_requested = date('Y-m-d H:i:s');
            $this->decrypt_key = bin2hex(openssl_random_pseudo_bytes(24)); 
            // provide authority for valid user
            $payload = array(
                "iss" => "cheannyong",
                "user_id" => $this->id,
                "name" =>$this->username,
                "email" =>$this->email,  
                "iat" => time(),
                "nbf" => time(),
                "exp" => time() + 3600
            );
            $this->token = JWT::encode($payload, $this->decrypt_key);
            
            if(!$this->readresetrequest())
            {  
                $query="INSERT INTO reset_password_request 
                        SET 
                            user_id=:user_id, 
                            token=:token,
                            decrypt_key=:decrypt_key, 
                            date_requested=:date_requested";
                
                $stmt = $this->conn->prepare($query);

                // sanitize
                $this->id=htmlspecialchars(strip_tags($this->id));
                $this->token=htmlspecialchars(strip_tags($this->token));
                $this->decrypt_key=htmlspecialchars(strip_tags($this->decrypt_key));
                $this->date_requested=htmlspecialchars(strip_tags($this->date_requested));
                
                //bind data to check
                $stmt->bindParam(":user_id", $this->id);
                $stmt->bindParam(":token", $this->token);
                $stmt->bindParam(":decrypt_key", $this->decrypt_key);
                $stmt->bindParam(":date_requested", $this->date_requested);
                
                //execute query
                if($stmt->execute())
                    return 1;
                else
                    return 2;
            }
            else
            {
                return 3;
            }    
        }
        else
            return 4;
    }
    public function changepassword(){
        // update query
        $query = "UPDATE
                    " . $this->table_name . "
                SET 
                    password=:password,
                    modified=:modified             
                WHERE
                    id = :id";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id)); 
        $this->modified=htmlspecialchars(strip_tags($this->modified));  
        $this->password=PasswordEncryptor::encrypt($this->password);
        
        // bind new values
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':modified', $this->modified);
        $stmt->bindParam(':password', $this->password);
    
        // execute the query
        if($stmt->execute()){
            return true;
        }
        return false;
    }
    
    public function changepassword2(){
        // update query
        $query = "UPDATE
                    " . $this->table_name2 . "
                SET 
                    password=:password,
                    modified=:modified             
                WHERE
                    id = :id";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id)); 
        $this->modified=htmlspecialchars(strip_tags($this->modified));  
        $this->password=PasswordEncryptor::encrypt($this->password);
        
        // bind new values
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':modified', $this->modified);
        $stmt->bindParam(':password', $this->password);
    
        // execute the query
        if($stmt->execute()){
            return true;
        }
        return false;
    }
    
    private function readresetrequest()
    {
        // this query => check this email is exists or not
        $query = "SELECT id FROM reset_password_request WHERE "
                .((isset($this->id) && !empty($this->id))? "user_id=:user_id" : "")
                .((isset($this->token2) && !empty($this->token2))? "token=:token" : "")
                ." LIMIT 1";
                
        //prepare query
        $stmt = $this->conn->prepare($query);
        
        //sanitize
        $this->id=(isset($this->id) && !empty($this->id))? htmlspecialchars(strip_tags($this->id)) : $this->id;
        $this->token2=(isset($this->token2) && !empty($this->token2))? htmlspecialchars(strip_tags($this->token2)) : $this->token2;
        
        //bind data to check
        if(isset($this->id) && !empty($this->id))
            $stmt->bindParam(':user_id', $this->id);
        if(isset($this->token2) && !empty($this->token2))
            $stmt->bindParam(':token', $this->token2);
            
        //execute query
        $stmt->execute();

        $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // set values to object properties
        if(count($row) > 0)
            return true;
        else
            return false;
    }

    public function checkExists(){
        //$query = "SELECT EXISTS(SELECT id FROM products_temp WHERE id=:id AND ISNULL(product_id)) count";
        $query = "SELECT user_id, action FROM users_temp WHERE id=:id LIMIT 1";

        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id));

        // bind id of record to delete
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // set values to object properties
        if(count($row) > 0)
        {
            if(is_null($row[0]['user_id'])){
                $this->action = $row[0]['action'];
                return 2; // product id is null => edit new product 
            }
            else {
                $this->user_id = $row[0]['user_id'];
                $this->action = $row[0]['action'];
                return 3; // product id is not null => edit the existings product
            }
        }
        else{
            return 1; // not found in database => new update product
        }
    }

    public function checkExistsById(){
        $query = "SELECT id 
                FROM ". $this->table_name." 
                WHERE id=? limit 1";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->user_id=htmlspecialchars(strip_tags($this->user_id));
        $stmt->bindParam(1, $this->user_id); 

        // execute query
        $stmt->execute(); 
        return $stmt->rowCount();
    }

    public function copyUserInfo($action)
    {
        $query = "INSERT INTO users_temp (
                    user_id, username, firstname, lastname, gender, dob, 
                    type, email, password, action, executor_id,created, status)
                SELECT id, username, firstname, lastname, gender, dob, 
                    type, email, password, '$action', $this->executor_id, CURRENT_TIMESTAMP, status
                    FROM users WHERE id=?";

        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->user_id=htmlspecialchars(strip_tags($this->user_id));

        // bind id of record to delete
        $stmt->bindParam(1, $this->user_id);
        $stmt->execute();

        $this->id = $this->conn->lastInsertId();
    }

    public function cloneForUpdate()
    {
        $query = "UPDATE $this->table_name as u, 
                    (
                        SELECT id, user_id, username,firstname, lastname, gender, dob, type, email, password, status 
                        FROM $this->table_name2
                        WHERE id=?
                    ) as t
                    SET u.username = t.username, 
                        u.firstname = t.firstname, 
                        u.lastname = t.lastname, 
                        u.gender = t.gender, 
                        u.dob = t.dob, 
                        u.type = t.type, 
                        u.email = t.email, 
                        u.password = t.password, 
                        u.status = t.status
                    WHERE u.id = t.user_id";
        
        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id));

        // bind id of record to delete
        $stmt->bindParam(1, $this->id);
        return $stmt->execute();
    }

    public function cloneForCreate()
    {
        $query = "INSERT INTO $this->table_name (
                    username, firstname, lastname, gender, dob, type, email,password, created, status)
                SELECT username, firstname, lastname, gender, dob, type, email, password, CURRENT_TIMESTAMP, status
                    FROM $this->table_name2 WHERE id=?";

        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id));

        // bind id of record to delete
        $stmt->bindParam(1, $this->id);
        $flag = $stmt->execute();
        if($flag)
        {
            $this->user_id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
}
?>