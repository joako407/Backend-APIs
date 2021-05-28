<?php
namespace api\model;

//Check if CONSTANT called SITE_URL is defined.
if(!defined('SITE_URL')) {
    //Send 403 Forbidden response.
    header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
    //Kill the script.
    exit;
}

class Product{

    // database connection and table name
    private $conn;
    private $table_name = "products";
    private $table_name_2 = "products_temp";

    // object properties
    public $id;
    public $product_id; // only for saving existing product id
    public $name;
    public $description;
    public $specification;
    public $label;
    public $price;
    public $quantity;
    public $brand_id;
    public $prom_brand_id;
    public $brand_name;
    public $category_id;
    public $prom_category_id;
    public $category_name;
    public $type_id;
    public $prom_type_id;
    public $type_name;
    public $series_id;
    public $series_name;
    public $sku;
    public $prom_sku_id;
    public $ram;
    public $color;
    public $storage;
    public $variant;
    public $combination;
    public $pictures;
    public $feature_pictures;
    public $release_date;
    public $created;
    public $modified;
    public $status;
    public $limit;
    public $page;
    public $keyword;
    public $order_by_price;
    public $order_by_name;
    public $order_by_release_date;
    public $user_id;
    public $version;
    public $action;
    public $action_mode;
  
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    public function __destruct()
    {
        $this->conn = null;
    }

    //read product (admin)
    public function read(){
        // select all query        

        $query = "SELECT * FROM (
            SELECT      
                p.id, 
                p.name, 
                p.description, 
                p.specification,
                p.label,
                p.brand_id,
                b.name as brand_name,
                p.category_id,
                c.name as category_name,
                p.type_id,
                t.name as type_name, 
                i.type,
                s.sku,
                s.price as sku_price,
                s.quantity as sku_quantity,
                o.name as option_name,
                ov.name as option_value,
                i.pic_path,
                p.series_id,
                ps.name as series_name,
                p.release_date,
                p.created,
                p.status,
                p.version,
                a.avgPrice 
            FROM ".$this->table_name." p
            LEFT JOIN brands b
            ON p.brand_id = b.id
            LEFT JOIN series ps
            ON p.series_id = ps.id
            LEFT JOIN categories c 
            ON p.category_id = c.id 
            LEFT JOIN types t
            ON p.type_id = t.id 
            LEFT JOIN sku_values sv 
            ON sv.product_id = p.id 
            LEFT JOIN options o 
            ON sv.option_id = o.id
            LEFT JOIN skus s 
            ON sv.sku_id = s.id
            LEFT JOIN option_values ov 
            ON sv.option_value_id = ov.id 
            LEFT JOIN pictures i
            ON i.target_id = p.id
            LEFT JOIN (SELECT product_id, avg(price) avgPrice FROM skus group by product_id) a
            ON a.product_id = p.id  
            WHERE i.type='p' OR i.type='d')q 
            WHERE";
        
        if(isset($this->keyword) && !empty($this->keyword))
        {
            $query .= " AND (q.name LIKE :name OR q.description LIKE :description OR q.category_name LIKE :category)";
        }
        //additional filter
        if(isset($this->category_id) && !empty($this->category_id))
        {
            $query .= " AND category_id=:category_id";
        }
        if(isset($this->brand_id) && !empty($this->brand_id))
        {
            $query .= " AND brand_id=:brand_id";
        }
        if(isset($this->type_id) && !empty($this->type_id))
        {
            $query .= " AND type_id=:type_id";
        }
        if(isset($this->series_id) && !empty($this->series_id))
        {
            $query .= " AND series_id=:series_id";
        }
        $query = str_replace("WHERE AND", "WHERE", $query);

        if(isset($this->order_by_name))
        {
            $query = (($this->order_by_name == 1) ? 
                    ($query." ORDER BY name DESC") : 
                    ($query." ORDER BY name ASC"));
        }
        else if(isset($this->order_by_price))
        {
            $query = (($this->order_by_price == 1) ? 
                    ($query." ORDER BY avgPrice DESC") : 
                    ($query." ORDER BY avgPrice ASC"));
        }
        else if(isset($this->order_by_release_date))
        {
            $query = (($this->order_by_release_date == 1) ? 
                    ($query." ORDER BY release_date DESC") : 
                    ($query." ORDER BY release_date ASC"));
        }
        else
            $query .= " ORDER BY id ASC";

        $query = str_replace("WHERE ORDER", "ORDER", $query);
        
        // prepare query statement
        $stmt = $this->conn->prepare($query);

        if(isset($this->keyword) && !empty($this->keyword))
        {
            //sanitize
            $this->keyword=htmlspecialchars(strip_tags($this->keyword));
            $this->keyword = "%{$this->keyword}%";

            // bind
            $stmt->bindParam(":name", $this->keyword);
            $stmt->bindParam(":description", $this->keyword);
            $stmt->bindParam(":category", $this->keyword);
        }
        if(isset($this->category_id) && !empty($this->category_id))
        {
            //sanitize
            $this->category_id=htmlspecialchars(strip_tags($this->category_id));
            // bind
            $stmt->bindParam(":category_id", $this->category_id);
        }
        if(isset($this->brand_id) && !empty($this->brand_id))
        {
            //sanitize
            $this->brand_id=htmlspecialchars(strip_tags($this->brand_id));
            // bind
            $stmt->bindParam(":brand_id", $this->brand_id);
        }
        if(isset($this->type_id) && !empty($this->type_id))
        {
            //sanitize
            $this->type_id=htmlspecialchars(strip_tags($this->type_id));
            // bind
            $stmt->bindParam(":type_id", $this->type_id);
        }
        if(isset($this->series_id) && !empty($this->series_id))
        {
            //sanitize
            $this->series_id=htmlspecialchars(strip_tags($this->series_id));
            // bind
            $stmt->bindParam(":series_id", $this->series_id);
        }
        //echo $query;
        // execute query
        $stmt->execute();        
        return $stmt;
    }
    
    //read temp create product
    public function read2(){
        // select all query        

        $query = "SELECT * FROM (
            SELECT      
                p.id, 
                p.product_id,
                p.name, 
                p.description, 
                p.specification,
                p.brand_id,
                b.name as brand_name,
                p.category_id,
                c.name as category_name,
                p.type_id,
                t.name as type_name, 
                i.type,
                s.sku,
                s.price as sku_price,
                s.quantity as sku_quantity,
                o.name as option_name,
                ov.name as option_value,
                i.pic_path,
                p.series_id,
                ps.name as series_name,
                p.release_date,
                p.created,
                p.user_id,
                p.action,
                p.status,
                a.avgPrice 
            FROM ".$this->table_name_2." p
            LEFT JOIN brands b
            ON p.brand_id = b.id
            LEFT JOIN series ps
            ON p.series_id = ps.id
            LEFT JOIN categories c 
            ON p.category_id = c.id 
            LEFT JOIN types t
            ON p.type_id = t.id 
            LEFT JOIN sku_values_temp sv 
            ON sv.product_id = p.id 
            LEFT JOIN options_temp o 
            ON sv.option_id = o.id
            LEFT JOIN skus_temp s 
            ON sv.sku_id = s.id
            LEFT JOIN option_values_temp ov 
            ON sv.option_value_id = ov.id 
            LEFT JOIN pictures_temp i
            ON p.id = i.target_id
            LEFT JOIN (SELECT product_id, avg(price) avgPrice FROM skus_temp group by product_id) a
            ON a.product_id = p.id  
            WHERE i.type='p' OR i.type='d')q 
            WHERE user_id=?";
        
        if(isset($this->keyword) && !empty($this->keyword))
        {
            $query .= " AND (q.name LIKE :name OR q.description LIKE :description OR q.category_name LIKE :category)";
        }
        //additional filter
        if(isset($this->category_id) && !empty($this->category_id))
        {
            $query .= " AND category_id=:category_id";
        }
        if(isset($this->brand_id) && !empty($this->brand_id))
        {
            $query .= " AND brand_id=:brand_id";
        }
        if(isset($this->type_id) && !empty($this->type_id))
        {
            $query .= " AND type_id=:type_id";
        }
        if(isset($this->series_id) && !empty($this->series_id))
        {
            $query .= " AND series_id=:series_id";
        }
        $query = str_replace("WHERE AND", "WHERE", $query);

        if(isset($this->order_by_name))
        {
            $query = (($this->order_by_name == 1) ? 
                    ($query." ORDER BY name DESC") : 
                    ($query." ORDER BY name ASC"));
        }
        else if(isset($this->order_by_price))
        {
            $query = (($this->order_by_price == 1) ? 
                    ($query." ORDER BY avgPrice DESC") : 
                    ($query." ORDER BY avgPrice ASC"));
        }
        else if(isset($this->order_by_release_date))
        {
            $query = (($this->order_by_release_date == 1) ? 
                    ($query." ORDER BY release_date DESC") : 
                    ($query." ORDER BY release_date ASC"));
        }
        else
            $query .= " ORDER BY id ASC";

        $query = str_replace("WHERE ORDER", "ORDER", $query);
        
        // prepare query statement
        $stmt = $this->conn->prepare($query);

        if(isset($this->keyword) && !empty($this->keyword))
        {
            //sanitize
            $this->keyword=htmlspecialchars(strip_tags($this->keyword));
            $this->keyword = "%{$this->keyword}%";

            // bind
            $stmt->bindParam(":name", $this->keyword);
            $stmt->bindParam(":description", $this->keyword);
            $stmt->bindParam(":category", $this->keyword);
        }
        if(isset($this->category_id) && !empty($this->category_id))
        {
            //sanitize
            $this->category_id=htmlspecialchars(strip_tags($this->category_id));
            // bind
            $stmt->bindParam(":category_id", $this->category_id);
        }
        if(isset($this->brand_id) && !empty($this->brand_id))
        {
            //sanitize
            $this->brand_id=htmlspecialchars(strip_tags($this->brand_id));
            // bind
            $stmt->bindParam(":brand_id", $this->brand_id);
        }
        if(isset($this->type_id) && !empty($this->type_id))
        {
            //sanitize
            $this->type_id=htmlspecialchars(strip_tags($this->type_id));
            // bind
            $stmt->bindParam(":type_id", $this->type_id);
        }
        if(isset($this->series_id) && !empty($this->series_id))
        {
            //sanitize
            $this->series_id=htmlspecialchars(strip_tags($this->series_id));
            // bind
            $stmt->bindParam(":series_id", $this->series_id);
        }

        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $stmt->bindParam(1, $this->user_id);
        
        //echo $query;
        // execute query
        $stmt->execute();        
        return $stmt;
    }

    //read product (client)
    public function getAll(){
        // select all query        

        $query = "SELECT * FROM (
            SELECT      
                p.id, 
                p.name, 
                p.description, 
                p.specification, 
                p.label,
                p.brand_id,
                b.name as brand_name,
                p.category_id,
                c.name as category_name,
                p.type_id,
                t.name as type_name, 
                i.type,
                s.id as sku_id,
                s.sku,
                s.price as sku_price,
                s.quantity as sku_quantity,
                o.name as option_name,
                ov.name as option_value,
                i.pic_path,
                p.series_id,
                ps.name as series_name,
                p.release_date,
                p.created,
                p.status,
                p.version,
                a.avgPrice 
            FROM ".$this->table_name." p
            LEFT JOIN brands b
            ON p.brand_id = b.id
            LEFT JOIN series ps
            ON p.series_id = ps.id
            LEFT JOIN categories c 
            ON p.category_id = c.id 
            LEFT JOIN types t
            ON p.type_id = t.id 
            LEFT JOIN sku_values sv 
            ON sv.product_id = p.id 
            LEFT JOIN options o 
            ON sv.option_id = o.id
            LEFT JOIN skus s 
            ON sv.sku_id = s.id
            LEFT JOIN option_values ov 
            ON sv.option_value_id = ov.id 
            LEFT JOIN pictures i
            ON i.target_id = p.id
            LEFT JOIN (SELECT product_id, avg(price) avgPrice FROM skus group by product_id) a
            ON a.product_id = p.id  
            WHERE i.type='p' OR i.type='d')q 
            WHERE status=1";
        
        if(isset($this->keyword) && !empty($this->keyword))
        {
            $query .= " AND (q.name LIKE :name OR q.description LIKE :description OR q.category_name LIKE :category)";
        }
        //additional filter
        if(isset($this->category_id) && !empty($this->category_id))
        {
            $query .= " AND category_id=:category_id";
        }
        if(isset($this->brand_id) && !empty($this->brand_id))
        {
            $query .= " AND brand_id=:brand_id";
        }
        if(isset($this->type_id) && !empty($this->type_id))
        {
            $query .= " AND type_id=:type_id";
        }
        if(isset($this->series_id) && !empty($this->series_id))
        {
            $query .= " AND series_id=:series_id";
        }
        if(isset($this->label) && !empty($this->label))
        {
            $query .= " AND label=:label";
        }
        //$query = str_replace("WHERE AND", "WHERE", $query);

        if(isset($this->order_by_name) && $this->order_by_name)
        {
            $query = (($this->order_by_name == 1) ? 
                    ($query." ORDER BY name DESC") : 
                    ($query." ORDER BY name ASC"));
        }
        else if(isset($this->order_by_price) && $this->order_by_price)
        {
            $query = (($this->order_by_price == 1) ? 
                    ($query." ORDER BY avgPrice DESC") : 
                    ($query." ORDER BY avgPrice ASC"));
        }
        else if(isset($this->order_by_release_date) && $this->order_by_release_date)
        {
            $query = (($this->order_by_release_date == 1) ? 
                    ($query." ORDER BY release_date DESC") : 
                    ($query." ORDER BY release_date ASC"));
        }
        else
            $query .= " ORDER BY id ASC";

        //$query = str_replace("WHERE ORDER", "ORDER", $query);

        // prepare query statement
        $stmt = $this->conn->prepare($query);
        
        if(isset($this->keyword) && !empty($this->keyword))
        {
            //sanitize
            $this->keyword=htmlspecialchars(strip_tags($this->keyword));
            $this->keyword = "%{$this->keyword}%";

            // bind
            $stmt->bindParam(":name", $this->keyword);
            $stmt->bindParam(":description", $this->keyword);
            $stmt->bindParam(":category", $this->keyword);
        }
        if(isset($this->category_id) && !empty($this->category_id))
        {
            //sanitize
            $this->category_id=htmlspecialchars(strip_tags($this->category_id));
            // bind
            $stmt->bindParam(":category_id", $this->category_id);
        }
        if(isset($this->brand_id) && !empty($this->brand_id))
        {
            //sanitize
            $this->brand_id=htmlspecialchars(strip_tags($this->brand_id));
            // bind
            $stmt->bindParam(":brand_id", $this->brand_id);
        }
        if(isset($this->type_id) && !empty($this->type_id))
        {
            //sanitize
            $this->type_id=htmlspecialchars(strip_tags($this->type_id));
            // bind
            $stmt->bindParam(":type_id", $this->type_id);
        }
        if(isset($this->series_id) && !empty($this->series_id))
        {
            //sanitize
            $this->series_id=htmlspecialchars(strip_tags($this->series_id));
            // bind
            $stmt->bindParam(":series_id", $this->series_id);
        }
        if(isset($this->label) && !empty($this->label))
        {
            //sanitize
            $this->label=htmlspecialchars(strip_tags($this->label));
            // bind
            $stmt->bindParam(":label", $this->label);
        }

        // execute query
        $stmt->execute();        
        return $stmt;
    }
    //read temp product (admin) for get all product requests
    public function getAll2(){
        // select all query        

        $query = "SELECT * FROM (
            SELECT      
                p.id, 
                p.product_id,
                p.name, 
                p.description, 
                p.specification,
                p.brand_id,
                b.name as brand_name,
                p.category_id,
                c.name as category_name,
                p.type_id,
                t.name as type_name, 
                i.type,
                s.sku,
                s.price as sku_price,
                s.quantity as sku_quantity,
                o.name as option_name,
                ov.name as option_value,
                i.pic_path,
                p.series_id,
                ps.name as series_name,
                p.release_date,
                p.created,
                p.user_id,
                u.firstname,
                u.lastname,
                p.action,
                p.status,
                a.avgPrice 
            FROM ".$this->table_name_2." p
            LEFT JOIN brands b
            ON p.brand_id = b.id
            LEFT JOIN series ps
            ON p.series_id = ps.id
            LEFT JOIN categories c 
            ON p.category_id = c.id 
            LEFT JOIN types t
            ON p.type_id = t.id 
            LEFT JOIN users u
            ON p.user_id = u.id
            LEFT JOIN sku_values_temp sv 
            ON sv.product_id = p.id 
            LEFT JOIN options_temp o 
            ON sv.option_id = o.id
            LEFT JOIN skus_temp s 
            ON sv.sku_id = s.id
            LEFT JOIN option_values_temp ov 
            ON sv.option_value_id = ov.id 
            LEFT JOIN pictures_temp i
            ON p.id = i.target_id
            LEFT JOIN (SELECT product_id, avg(price) avgPrice FROM skus_temp group by product_id) a
            ON a.product_id = p.id  
            WHERE i.type='p' OR i.type='d')q";
        
        if(isset($this->keyword) && !empty($this->keyword))
        {
            $query .= " AND (q.name LIKE :name OR q.description LIKE :description OR q.category_name LIKE :category)";
        }
        //additional filter
        if(isset($this->category_id) && !empty($this->category_id))
        {
            $query .= " AND category_id=:category_id";
        }
        if(isset($this->brand_id) && !empty($this->brand_id))
        {
            $query .= " AND brand_id=:brand_id";
        }
        if(isset($this->type_id) && !empty($this->type_id))
        {
            $query .= " AND type_id=:type_id";
        }
        if(isset($this->series_id) && !empty($this->series_id))
        {
            $query .= " AND series_id=:series_id";
        }
        //$query = str_replace("WHERE AND", "WHERE", $query);

        if(isset($this->order_by_name) && $this->order_by_name)
        {
            $query = (($this->order_by_name == 1) ? 
                    ($query." ORDER BY name DESC") : 
                    ($query." ORDER BY name ASC"));
        }
        else if(isset($this->order_by_price) && $this->order_by_price)
        {
            $query = (($this->order_by_price == 1) ? 
                    ($query." ORDER BY avgPrice DESC") : 
                    ($query." ORDER BY avgPrice ASC"));
        }
        else if(isset($this->order_by_release_date) && $this->order_by_release_date)
        {
            $query = (($this->order_by_release_date == 1) ? 
                    ($query." ORDER BY release_date DESC") : 
                    ($query." ORDER BY release_date ASC"));
        }
        else
            $query .= " ORDER BY id ASC";

        //$query = str_replace("WHERE ORDER", "ORDER", $query);

        // prepare query statement
        $stmt = $this->conn->prepare($query);
        
        if(isset($this->keyword) && !empty($this->keyword))
        {
            //sanitize
            $this->keyword=htmlspecialchars(strip_tags($this->keyword));
            $this->keyword = "%{$this->keyword}%";

            // bind
            $stmt->bindParam(":name", $this->keyword);
            $stmt->bindParam(":description", $this->keyword);
            $stmt->bindParam(":category", $this->keyword);
        }
        if(isset($this->category_id) && !empty($this->category_id))
        {
            //sanitize
            $this->category_id=htmlspecialchars(strip_tags($this->category_id));
            // bind
            $stmt->bindParam(":category_id", $this->category_id);
        }
        if(isset($this->brand_id) && !empty($this->brand_id))
        {
            //sanitize
            $this->brand_id=htmlspecialchars(strip_tags($this->brand_id));
            // bind
            $stmt->bindParam(":brand_id", $this->brand_id);
        }
        if(isset($this->type_id) && !empty($this->type_id))
        {
            //sanitize
            $this->type_id=htmlspecialchars(strip_tags($this->type_id));
            // bind
            $stmt->bindParam(":type_id", $this->type_id);
        }
        if(isset($this->series_id) && !empty($this->series_id))
        {
            //sanitize
            $this->series_id=htmlspecialchars(strip_tags($this->series_id));
            // bind
            $stmt->bindParam(":series_id", $this->series_id);
        }

        // execute query
        $stmt->execute();        
        return $stmt;
    }

    public function readVersion(){
        // query to read single record
        $query = "SELECT      
                    version
                FROM ".$this->table_name."
                WHERE id = ?";
        
        // prepare query statement
        $stmt = $this->conn->prepare( $query );
    
        // bind id of product to be updated
        $stmt->bindParam(1, $this->id);
        // execute query
        $stmt->execute();
    
        // get retrieved row
        $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // set values to object properties
        if(count($row) > 0)
        {
            $this->version = $row[0]['version'];
        }
    }

    
    public function readProductForPromotion(){
        
        $condition = "";
        foreach($this->prom_brand_id as $b){
            $condition.=" brand_id=$b OR";
        }
        foreach($this->prom_category_id as $c){
            $condition.=" category_id=$c OR";
        }
        foreach($this->prom_type_id as $t){
            $condition.=" type_id=$t OR";
        }
        foreach($this->prom_sku_id as $s){
            $condition.=" s.id=$s OR";
        }

        $query = "SELECT      
                p.id, 
                p.name,
                s.id as sku_id,
                s.sku
            FROM ".$this->table_name." p
            LEFT JOIN skus s
            ON s.product_id = p.id
            WHERE status=1 AND ($condition)";

        $query = str_replace("OR)", ")", $query);
        //echo $query;

        $stmt = $this->conn->prepare($query);
        // execute query
        $stmt->execute();        
        return $stmt;
    }

    //read options of the product
    public function readOption(){
        $query = "SELECT 
                    o.name option_name,
                    ov.name option_value
                  FROM options o
                  LEFT JOIN option_values ov
                  ON ov.option_id = o.id
                  WHERE o.product_id = ?";

        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id));

        // bind values
        $stmt->bindParam(1, $this->id);

        $stmt->execute();

        $this->variant = array();
        if($stmt->rowCount() > 0)
        {
            // get retrieved row
            while($row = $stmt->fetch(\PDO::FETCH_ASSOC))
            {
                extract($row); 

                if(array_search($row['option_name'], array_column($this->variant,'name')) === false)
                    $this->variant[] = Array("name"=> $row['option_name'], "list"=> [$row['option_value']]);
                    
                $index = array_search($row['option_name'], array_column($this->variant,'name'));
                if(array_search($row['option_value'], $this->variant[$index]['list'], true) === false)
                    $this->variant[$index]['list'][] = $row['option_value'];
            }
        }
    }
    
    //read options of the product
    public function readOption2(){
        $query = "SELECT 
                    o.name option_name,
                    ov.name option_value
                  FROM options_temp o
                  LEFT JOIN option_values_temp ov
                  ON ov.option_id = o.id
                  WHERE o.product_id = ?";

        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id));

        // bind values
        $stmt->bindParam(1, $this->id);

        $stmt->execute();

        $this->variant = array();
        if($stmt->rowCount() > 0)
        {
            // get retrieved row
            while($row = $stmt->fetch(\PDO::FETCH_ASSOC))
            {
                extract($row); 
                if(array_search($row['option_name'], array_column($this->variant,'name')) === false)
                    $this->variant[] = Array("name"=> $row['option_name'], "list"=> [$row['option_value']]);
                    
                $index = array_search($row['option_name'], array_column($this->variant,'name'));
                if(array_search($row['option_value'], $this->variant[$index]['list'], true) === false)
                    $this->variant[$index]['list'][] = $row['option_value'];
            }
        }
    }

    //read combination of product
    public function readCombination(){
        $query = "SELECT
                    s.id, 
                    s.sku,
                    s.price as sku_price,
                    s.quantity as sku_quantity,
                    o.name as option_name,
                    ov.name as option_value
                  FROM sku_values sv
                  LEFT JOIN options o 
                  ON sv.option_id = o.id
                  LEFT JOIN skus s 
                  ON sv.sku_id = s.id
                  LEFT JOIN option_values ov 
                  ON sv.option_value_id = ov.id
                  WHERE sv.product_id = ?";

        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id));

        // bind values
        $stmt->bindParam(1, $this->id);

        $stmt->execute();

        $this->quantity = 0;
        $this->price = null;
        $this->combination = array();

        if($stmt->rowCount() > 0)
        {
            // get retrieved row
            while($row = $stmt->fetch(\PDO::FETCH_ASSOC))
            {
                extract($row); 
                if(array_search($row['sku'], array_column($this->combination,'sku')) === false)
                {
                    $this->combination[] = Array(
                        "id"=>$row['id'],
                        "sku" => $row['sku'], 
                        "price" => $row['sku_price'], 
                        "quantity" => $row['sku_quantity'],
                        "items" => []
                    );
                    $this->quantity += $row['sku_quantity'];
                }
                //add option value to a sku
                $index = array_search($row['sku'], array_column($this->combination,'sku'));
                if(array_search($row['option_value'], $this->combination[$index]['items']) === false)
                    $this->combination[$index]['items'][] = $option_value;
                    
                
                
            }
            if(count(array_unique(array_column($this->combination,'price'))) === 1)
            {
                $this->price = $this->combination[0]['price'];
            }
            else
                $this->price = min(array_column($this->combination,'price'))."-".max(array_column($this->combination,'price'));
        }
    }
    
    //read combination of product
    public function readCombination2(){
        $query = "SELECT
                    s.id, 
                    s.sku,
                    s.price as sku_price,
                    s.quantity as sku_quantity,
                    o.name as option_name,
                    ov.name as option_value
                  FROM sku_values_temp sv
                  LEFT JOIN options_temp o 
                  ON sv.option_id = o.id
                  LEFT JOIN skus_temp s 
                  ON sv.sku_id = s.id
                  LEFT JOIN option_values_temp ov 
                  ON sv.option_value_id = ov.id
                  WHERE sv.product_id = ?";

        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id));

        // bind values
        $stmt->bindParam(1, $this->id);

        $stmt->execute();

        $this->quantity = 0;
        $this->price = null;
        $this->combination = array();

        if($stmt->rowCount() > 0)
        {
            // get retrieved row
            while($row = $stmt->fetch(\PDO::FETCH_ASSOC))
            {
                extract($row); 
                if(array_search($row['sku'], array_column($this->combination,'sku')) === false)
                {
                    $this->combination[] = Array(
                        "id"=>$row['id'],
                        "sku" => $row['sku'], 
                        "price" => $row['sku_price'], 
                        "quantity" => $row['sku_quantity'],
                        "items" => []
                    );
                    $this->quantity += $row['sku_quantity'];
                }
                //add option value to a sku
                $index = array_search($row['sku'], array_column($this->combination,'sku'));
                if(array_search($row['option_value'], $this->combination[$index]['items']) === false)
                    $this->combination[$index]['items'][] = $option_value;
                
                
            }
            if(count(array_unique(array_column($this->combination,'price'))) === 1)
            {
                $this->price = $this->combination[0]['price'];
            }
            else
                $this->price = min(array_column($this->combination,'price'))."-".max(array_column($this->combination,'price'));
        }
    }

    // create product
    public function create(){
        // query to insert record
        $query = "INSERT INTO ".$this->table_name." 
                SET 
                    name=:name,
                    description=:description, 
                    specification=:specification, 
                    label=:label, 
                    brand_id=:brand_id,
                    category_id=:category_id, 
                    type_id=:type_id," 
                    .((isset($this->series_id) && !empty($this->series_id))? "series_id = :series_id," : "")
                    .((isset($this->release_date) && !empty($this->release_date))? "release_date = :release_date," : "")
                    ."created=:created";
                    
                    
    
        // prepare query
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->name=htmlspecialchars(strip_tags($this->name));
        $this->description=htmlspecialchars(strip_tags($this->description));
        $this->specification=htmlspecialchars($this->specification);
        $this->label=htmlspecialchars($this->label);
        $this->brand_id=htmlspecialchars(strip_tags($this->brand_id));
        $this->category_id=htmlspecialchars(strip_tags($this->category_id));
        $this->type_id=htmlspecialchars(strip_tags($this->type_id));
        $this->series_id=(isset($this->series_id) && !empty($this->series_id))? htmlspecialchars(strip_tags($this->series_id)) : $this->series_id;
        $this->release_date=(isset($this->release_date) && !empty($this->release_date))? htmlspecialchars(strip_tags($this->release_date)) : $this->release_date;
        $this->created=htmlspecialchars(strip_tags($this->created));
    
        // bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":specification", $this->specification);
        $stmt->bindParam(":label", $this->label);
        $stmt->bindParam(":brand_id", $this->brand_id);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":type_id", $this->type_id);
        if(isset($this->series_id) && !empty($this->series_id))
            $stmt->bindParam(':series_id', $this->series_id);
        if(isset($this->release_date) && !empty($this->release_date))
            $stmt->bindParam(':release_date', $this->release_date);
        $stmt->bindParam(":created", $this->created);
    
        // execute query
        if($stmt->execute()){
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
    
    // create product
    public function create2(){
        // query to insert record
        $query = "INSERT INTO ".$this->table_name_2." 
                SET 
                    name=:name,
                    description=:description, 
                    specification=:specification, 
                    brand_id=:brand_id,
                    category_id=:category_id, 
                    type_id=:type_id," 
                    .((isset($this->series_id) && !empty($this->series_id))? "series_id = :series_id," : "")
                    .((isset($this->release_date) && !empty($this->release_date))? "release_date = :release_date," : "")
                    ."created=:created,
                    user_id=:user_id,
                    action=:action";
                    
                    
    
        // prepare query
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->name=htmlspecialchars(strip_tags($this->name));
        $this->description=htmlspecialchars(strip_tags($this->description));
        $this->specification=htmlspecialchars($this->specification);
        $this->brand_id=htmlspecialchars(strip_tags($this->brand_id));
        $this->category_id=htmlspecialchars(strip_tags($this->category_id));
        $this->type_id=htmlspecialchars(strip_tags($this->type_id));
        $this->series_id=(isset($this->series_id) && !empty($this->series_id))? htmlspecialchars(strip_tags($this->series_id)) : $this->series_id;
        $this->release_date=(isset($this->release_date) && !empty($this->release_date))? htmlspecialchars(strip_tags($this->release_date)) : $this->release_date;
        $this->created=htmlspecialchars(strip_tags($this->created));
        $this->user_id=htmlspecialchars(strip_tags($this->user_id));
        $this->action=htmlspecialchars(strip_tags($this->action));
    
        // bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":specification", $this->specification);
        $stmt->bindParam(":brand_id", $this->brand_id);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":type_id", $this->type_id);
        if(isset($this->series_id) && !empty($this->series_id))
            $stmt->bindParam(':series_id', $this->series_id);
        if(isset($this->release_date) && !empty($this->release_date))
            $stmt->bindParam(':release_date', $this->release_date);
        $stmt->bindParam(":created", $this->created);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":action", $this->action);
    
        // execute query
        if($stmt->execute()){
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function createVariant(){

        foreach($this->variant as $k => $x)
        {
            // query to insert record
            $query = "INSERT INTO options 
            SET 
                product_id=:product_id, 
                name=:name";                                          

            // prepare query
            $stmt = $this->conn->prepare($query);

            // sanitize
            $this->id=htmlspecialchars(strip_tags($this->id));
            $x['name']=htmlspecialchars(strip_tags($x['name']));

            // bind values
            $stmt->bindParam(":product_id", $this->id);
            $stmt->bindParam(":name", $x['name']);
                
            // execute query
            if($stmt->execute()){
                $this->variant[$k]['id'] = $this->conn->lastInsertId();
                $this->createVariantValue($this->variant[$k]);
            }
        }
    }
    
    public function createVariant2(){

        foreach($this->variant as $k => $x)
        {
            // query to insert record
            $query = "INSERT INTO options_temp 
            SET 
                product_id=:product_id, 
                name=:name";                                          

            // prepare query
            $stmt = $this->conn->prepare($query);

            // sanitize
            $this->id=htmlspecialchars(strip_tags($this->id));
            $x['name']=htmlspecialchars(strip_tags($x['name']));

            // bind values
            $stmt->bindParam(":product_id", $this->id);
            $stmt->bindParam(":name", $x['name']);
                
            // execute query
            if($stmt->execute()){
                $this->variant[$k]['id'] = $this->conn->lastInsertId();
                $this->createVariantValue2($this->variant[$k]);
            }
        }
    }

    private function createVariantValue($v){
        foreach($v['list'] as $k => $x)
        {
            $query = "INSERT INTO option_values 
            SET 
                product_id=:product_id,
                option_id=:option_id, 
                name=:name";                                          

            // prepare query
            $stmt = $this->conn->prepare($query);

            // sanitize
            $this->id=htmlspecialchars(strip_tags($this->id));
            $v['id']=htmlspecialchars(strip_tags($v['id']));
            $x['value']=htmlspecialchars(strip_tags($x['value']));

            // bind values
            $stmt->bindParam(":product_id", $this->id);
            $stmt->bindParam(":option_id", $v['id']);
            $stmt->bindParam(":name", $x['value']);
                
            // execute query
            if($stmt->execute()){
                $index = array_search($v['id'], array_column($this->variant,'id'));
                $this->variant[$index]['list'][$k]['v_id'] = $this->conn->lastInsertId();
            }
        }
    }
    
    private function createVariantValue2($v){
        foreach($v['list'] as $k => $x)
        {
            $query = "INSERT INTO option_values_temp 
            SET 
                product_id=:product_id,
                option_id=:option_id, 
                name=:name";                                          

            // prepare query
            $stmt = $this->conn->prepare($query);

            // sanitize
            $this->id=htmlspecialchars(strip_tags($this->id));
            $v['id']=htmlspecialchars(strip_tags($v['id']));
            $x['value']=htmlspecialchars(strip_tags($x['value']));

            // bind values
            $stmt->bindParam(":product_id", $this->id);
            $stmt->bindParam(":option_id", $v['id']);
            $stmt->bindParam(":name", $x['value']);
                
            // execute query
            if($stmt->execute()){
                $index = array_search($v['id'], array_column($this->variant,'id'));
                $this->variant[$index]['list'][$k]['v_id'] = $this->conn->lastInsertId();
            }
        }
    }
    
    public function createCombination(){
        foreach($this->combination as $k => $x)
        {
            // query to insert record
            $query = "INSERT INTO skus 
            SET 
                product_id=:product_id, 
                sku=:sku,
                price=:price,
                quantity=:quantity";                                          

            // prepare query
            $stmt = $this->conn->prepare($query);

            // sanitize
            $this->id=htmlspecialchars(strip_tags($this->id));
            $x['sku']=htmlspecialchars(strip_tags($x['sku']));
            $x['price']=htmlspecialchars(strip_tags($x['price']));
            $x['quantity']=htmlspecialchars(strip_tags($x['quantity']));

            // bind values
            $stmt->bindParam(":product_id", $this->id);
            $stmt->bindParam(":sku", $x['sku']);
            $stmt->bindParam(":price", $x['price']);
            $stmt->bindParam(":quantity", $x['quantity']);
                
            // execute query
            if($stmt->execute()){
                $this->combination[$k]['id'] = $this->conn->lastInsertId();
                $this->createCombinationValue($this->combination[$k]);
            }
        }
    }
    
    public function createCombination2(){
        foreach($this->combination as $k => $x)
        {
            // query to insert record
            $query = "INSERT INTO skus_temp 
            SET 
                product_id=:product_id, 
                sku=:sku,
                price=:price,
                quantity=:quantity";                                          

            // prepare query
            $stmt = $this->conn->prepare($query);

            // sanitize
            $this->id=htmlspecialchars(strip_tags($this->id));
            $x['sku']=htmlspecialchars(strip_tags($x['sku']));
            $x['price']=htmlspecialchars(strip_tags($x['price']));
            $x['quantity']=htmlspecialchars(strip_tags($x['quantity']));

            // bind values
            $stmt->bindParam(":product_id", $this->id);
            $stmt->bindParam(":sku", $x['sku']);
            $stmt->bindParam(":price", $x['price']);
            $stmt->bindParam(":quantity", $x['quantity']);
                
            // execute query
            if($stmt->execute()){
                $this->combination[$k]['id'] = $this->conn->lastInsertId();
                $this->createCombinationValue2($this->combination[$k]);
            }
        }
    }

    private function createCombinationValue($v){
        foreach($v['items'] as $k => $x)
        {
            $query = "INSERT INTO sku_values 
            SET 
                product_id=:product_id, 
                sku_id=:sku_id, 
                option_id=:option_id,
                option_value_id=:option_value_id";                                          

            // prepare query
            $stmt = $this->conn->prepare($query);

            // sanitize
            $this->id = htmlspecialchars(strip_tags($this->id));
            $v['id']=htmlspecialchars(strip_tags($v['id']));
            $x['o_id']=htmlspecialchars(strip_tags($x['o_id']));
            $x['v_id']=htmlspecialchars(strip_tags($x['v_id']));

            // bind values
            $stmt->bindParam(":product_id", $this->id);
            $stmt->bindParam(":sku_id", $v['id']);
            $stmt->bindParam(":option_id", $x['o_id']);
            $stmt->bindParam(":option_value_id", $x['v_id']);
                
            // execute query
            $stmt->execute();
        }
    }
    
    private function createCombinationValue2($v){
        foreach($v['items'] as $k => $x)
        {
            $query = "INSERT INTO sku_values_temp 
            SET 
                product_id=:product_id, 
                sku_id=:sku_id, 
                option_id=:option_id,
                option_value_id=:option_value_id";                                          

            // prepare query
            $stmt = $this->conn->prepare($query);

            // sanitize
            $this->id = htmlspecialchars(strip_tags($this->id));
            $v['id']=htmlspecialchars(strip_tags($v['id']));
            $x['o_id']=htmlspecialchars(strip_tags($x['o_id']));
            $x['v_id']=htmlspecialchars(strip_tags($x['v_id']));

            // bind values
            $stmt->bindParam(":product_id", $this->id);
            $stmt->bindParam(":sku_id", $v['id']);
            $stmt->bindParam(":option_id", $x['o_id']);
            $stmt->bindParam(":option_value_id", $x['v_id']);
                
            // execute query
            $stmt->execute();
        }
    }

    // used when filling up the update product form
    public function readOne(){
    
        // query to read single record
        $query = "SELECT      
                    p.id, 
                    p.name, 
                    p.description, 
                    p.specification, 
                    p.label, 
                    p.brand_id,
                    b.name as brand_name,
                    p.category_id,
                    c.name as category_name,
                    p.type_id,
                    t.name as type_name, 
                    i.pic_path,
                    p.series_id,
                    ps.name as series_name,
                    p.release_date,
                    i.type,
                    s.sku,
                    s.price as sku_price,
                    s.quantity as sku_quantity,
                    o.name as option_name,
                    ov.name as option_value,
                    p.created,
                    p.version,
                    p.status
                FROM ".$this->table_name." 
                p LEFT JOIN brands b
                ON p.brand_id = b.id
                LEFT JOIN series ps
                ON p.series_id = ps.id
                LEFT JOIN categories c 
                ON p.category_id = c.id 
                LEFT JOIN types t
                ON p.type_id = t.id
                LEFT JOIN sku_values sv 
                ON sv.product_id = p.id 
                LEFT JOIN options o 
                ON sv.option_id = o.id
                LEFT JOIN skus s 
                ON sv.sku_id = s.id
                LEFT JOIN option_values ov 
                ON sv.option_value_id = ov.id 
                LEFT JOIN pictures i
                ON i.target_id = p.id
                WHERE p.id = ? AND (i.type ='p' OR i.type='d') AND p.status=1";
        
        // prepare query statement
        $stmt = $this->conn->prepare( $query );
    
        // bind id of product to be updated
        $stmt->bindParam(1, $this->id);
        // execute query
        $stmt->execute();
    
        // get retrieved row
        $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // set values to object properties
        if(count($row) > 0)
        {
            $this->readOption();
            $this->readCombination();
            $this->name = $row[0]['name'];
            $this->description = $row[0]['description'];
            $this->specification = $row[0]['specification'];
            $this->label = $row[0]['label'];
            $this->brand_id = $row[0]['brand_id'];
            $this->brand_name = $row[0]['brand_name'];
            $this->category_id = $row[0]['category_id'];
            $this->category_name = $row[0]['category_name'];
            $this->type_id = $row[0]['type_id'];
            $this->type_name = $row[0]['type_name'];
            $this->series_id = $row[0]['series_id'];
            $this->series_name = $row[0]['series_name'];
            $this->release_date = $row[0]['release_date'];
            $this->version = $row[0]['version'];
            $this->status = $row[0]['status'];
            $this->pictures = Array();
            $this->feature_pictures = Array();
            foreach($row as $x)
            {
                if($x['type']=='p' && array_search($x['pic_path'], $this->pictures) === false)
                    $this->pictures[] = $x['pic_path'];
                else if($x['type']=='d' && array_search($x['pic_path'], $this->feature_pictures) === false)
                    $this->feature_pictures[] = $x['pic_path'];
            }
                
        }
    }

    // update the product
    public function update(){
        // update query
        $query = "UPDATE
                    " . $this->table_name . "
                SET "
                    .((isset($this->name) && !empty($this->name))? "name = :name,":"")
                    .((isset($this->description) && !empty($this->description))? "description = :description," : "")
                    .((isset($this->specification) && !empty($this->specification))? "specification = :specification," : "")
                    .((isset($this->label))? "label = :label," : "")
                    .((isset($this->brand_id) && !empty($this->brand_id))? "brand_id = :brand_id," : "")
                    .((isset($this->category_id) && !empty($this->category_id))? "category_id = :category_id," : "")
                    .((isset($this->type_id))? "type_id = :type_id," : "")
                    .((isset($this->series_id))? "series_id = :series_id," : "")
                    .((isset($this->release_date) && !empty($this->release_date))? "release_date = :release_date," : "")
                    .((isset($this->version) && !empty($this->version))? "version = :version," : "")
                    .((isset($this->status) && $this->status !== '')? "status = :status," : "")
                    ."modified = :modified 
                WHERE
                    id = :id";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id));
        $this->modified=htmlspecialchars(strip_tags($this->modified));
        $this->name=(isset($this->name) && !empty($this->name))? htmlspecialchars(strip_tags($this->name)) : $this->name;
        $this->description=(isset($this->description) && !empty($this->description))? htmlspecialchars(strip_tags($this->description)) : $this->description;
        $this->specification=(isset($this->specification) && !empty($this->specification))? htmlspecialchars($this->specification) : $this->specification;
        $this->label=(isset($this->label))? htmlspecialchars($this->label) : $this->label;
        $this->brand_id=(isset($this->brand_id) && !empty($this->brand_id))? htmlspecialchars(strip_tags($this->brand_id)) : $this->brand_id;
        $this->category_id=(isset($this->category_id) && !empty($this->category_id))? htmlspecialchars(strip_tags($this->category_id)) : $this->category_id;
        $this->type_id=(isset($this->type_id))? htmlspecialchars(strip_tags($this->type_id)) : $this->type_id;
        $this->series_id=(isset($this->series_id))? htmlspecialchars(strip_tags($this->series_id)) : $this->series_id;
        $this->version=(isset($this->version) && !empty($this->version))? htmlspecialchars(strip_tags($this->version)) : $this->version;
        $this->release_date=(isset($this->release_date) && !empty($this->release_date))? htmlspecialchars(strip_tags($this->release_date)) : $this->release_date;
        $this->status=(isset($this->status) && $this->status !== '')? htmlspecialchars(strip_tags($this->status)) : $this->status;       
    
        // bind new values
        $stmt->bindParam(':id', $this->id); 
        $stmt->bindParam(':modified', $this->modified);
        if(isset($this->name) && !empty($this->name))
            $stmt->bindParam(':name', $this->name);
        if(isset($this->description) && !empty($this->description))
            $stmt->bindParam(':description', $this->description);
        if(isset($this->specification) && !empty($this->specification))
            $stmt->bindParam(':specification', $this->specification);
        if(isset($this->label))
            $stmt->bindParam(':label', $this->label);
        if(isset($this->brand_id) && !empty($this->brand_id))
            $stmt->bindParam(':brand_id', $this->brand_id);
        if(isset($this->category_id) && !empty($this->category_id))
            $stmt->bindParam(':category_id', $this->category_id);
        if(isset($this->type_id))
            $stmt->bindParam(':type_id', $this->type_id);
        if(isset($this->series_id))
            $stmt->bindParam(':series_id', $this->series_id);
        if(isset($this->version) && !empty($this->version))
            $stmt->bindParam(':version', $this->version);
        if(isset($this->release_date) && !empty($this->release_date))
            $stmt->bindParam(':release_date', $this->release_date);
        if(isset($this->status) && $this->status !== '')
            $stmt->bindParam(':status', $this->status);
    
        // execute the query
        if($stmt->execute()){
            return true;
        }
    
        return false;
    }
    
    // update the product
    public function update2(){
        $this->action_mode = $this->checkExists();
        if($this->action_mode === 1){
            $mode = "INSERT INTO";
            $this->action = "edit";
        }
        else if($this->action_mode === 2){
            $mode = "UPDATE";
            $this->action = "create";
        }
        else{
            $mode = "UPDATE";
            $this->action = "edit";
        }
        
        // update query
        $query = "$mode
                " . $this->table_name_2 . "
            SET "
                .(($mode === "INSERT INTO") ? "product_id= :id," : "")
                .((isset($this->name) && !empty($this->name))? "name = :name,":"")
                .((isset($this->description) && !empty($this->description))? "description = :description," : "")
                .((isset($this->specification) && !empty($this->specification))? "specification = :specification," : "")
                .((isset($this->brand_id) && !empty($this->brand_id))? "brand_id = :brand_id," : "")
                .((isset($this->category_id) && !empty($this->category_id))? "category_id = :category_id," : "")
                .((isset($this->type_id))? "type_id = :type_id," : "")
                .((isset($this->series_id))? "series_id = :series_id," : "")
                .((isset($this->release_date) && !empty($this->release_date))? "release_date = :release_date," : "")
                .((isset($this->status) && $this->status !== '')? "status = :status," : "")
                .((isset($this->user_id) && $this->user_id !== '')? "user_id = :user_id," : "")
                .((isset($this->action) && $this->action !== '')? "action = :action," : "")
                ."created = :created,"
                ."modified = :modified"
                .(($mode === "UPDATE") ? " WHERE id = :id" : "");
        
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id));
        $this->created=htmlspecialchars(strip_tags($this->created));
        $this->modified=htmlspecialchars(strip_tags($this->modified));
        $this->name=(isset($this->name) && !empty($this->name))? htmlspecialchars(strip_tags($this->name)) : $this->name;
        $this->description=(isset($this->description) && !empty($this->description))? htmlspecialchars(strip_tags($this->description)) : $this->description;
        $this->specification=(isset($this->specification) && !empty($this->specification))? htmlspecialchars($this->specification) : $this->specification;
        $this->brand_id=(isset($this->brand_id) && !empty($this->brand_id))? htmlspecialchars(strip_tags($this->brand_id)) : $this->brand_id;
        $this->category_id=(isset($this->category_id) && !empty($this->category_id))? htmlspecialchars(strip_tags($this->category_id)) : $this->category_id;
        $this->type_id=(isset($this->type_id))? htmlspecialchars(strip_tags($this->type_id)) : $this->type_id;
        $this->series_id=(isset($this->series_id))? htmlspecialchars(strip_tags($this->series_id)) : $this->series_id;
        $this->release_date=(isset($this->release_date) && !empty($this->release_date))? htmlspecialchars(strip_tags($this->release_date)) : $this->release_date;
        $this->status=(isset($this->status) && $this->status !== '')? htmlspecialchars(strip_tags($this->status)) : $this->status;       
        $this->user_id=(isset($this->user_id) && $this->user_id !== '')? htmlspecialchars(strip_tags($this->user_id)) : $this->user_id;       
        $this->action=(isset($this->action) && $this->action !== '')? htmlspecialchars(strip_tags($this->action)) : $this->action;       
    
        // bind new values
        $stmt->bindParam(':id', $this->id); 
        $stmt->bindParam(':created', $this->created);
        $stmt->bindParam(':modified', $this->modified);
        if(isset($this->name) && !empty($this->name))
            $stmt->bindParam(':name', $this->name);
        if(isset($this->description) && !empty($this->description))
            $stmt->bindParam(':description', $this->description);
        if(isset($this->specification) && !empty($this->specification))
            $stmt->bindParam(':specification', $this->specification);
        if(isset($this->brand_id) && !empty($this->brand_id))
            $stmt->bindParam(':brand_id', $this->brand_id);
        if(isset($this->category_id) && !empty($this->category_id))
            $stmt->bindParam(':category_id', $this->category_id);
        if(isset($this->type_id))
            $stmt->bindParam(':type_id', $this->type_id);
        if(isset($this->series_id))
            $stmt->bindParam(':series_id', $this->series_id);
        if(isset($this->release_date) && !empty($this->release_date))
            $stmt->bindParam(':release_date', $this->release_date);
        if(isset($this->status) && $this->status !== '')
            $stmt->bindParam(':status', $this->status);
        if(isset($this->user_id) && $this->user_id !== '')
            $stmt->bindParam(':user_id', $this->user_id);
        if(isset($this->action) && $this->action !== '')
            $stmt->bindParam(':action', $this->action);
    
        // execute the query
        if($stmt->execute()){
            if($this->action_mode === 1)
            {
                $this->product_id = $this->id;
                $this->id = $this->conn->lastInsertId();
            }
            return true;
        }
    
        return false;
    }
    
    // delete the product
    public function delete(){
    
        // delete query
        $query = "DELETE FROM " . $this->table_name . 
                " WHERE id = ?";
    
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
    
    // delete the product
    public function delete2(){
    
        // delete query
        $query = "DELETE FROM " . $this->table_name_2 . 
                " WHERE id = ?";
    
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

    //delete variant and sku 
    public function deleteVaraint(){
        $tables = ["sku_values","skus","option_values","options"];
        $flags = [];
        foreach($tables as $x)
        {
            //delete query (sku_values)
            $query = "DELETE FROM ". $x.
            " WHERE product_id = ?";
            // prepare query
            $stmt = $this->conn->prepare($query);

            if($this->action == "edit" && $this->product_id){
                // sanitize
                $this->product_id=htmlspecialchars(strip_tags($this->product_id));

                // bind id of record to delete
                $stmt->bindParam(1, $this->product_id);
            }
            else{
                // sanitize
                $this->id=htmlspecialchars(strip_tags($this->id));

                // bind id of record to delete
                $stmt->bindParam(1, $this->id);
            }
            
            $flags[] = $stmt->execute();
        }
        return !in_array(false,$flags);
    }
    
    public function deleteVaraint2(){
        $tables = ["sku_values_temp","skus_temp","option_values_temp","options_temp"];
        $flags = [];
        foreach($tables as $x)
        {
            //delete query (sku_values)
            $query = "DELETE FROM ". $x.
            " WHERE product_id = ?";
            // prepare query
            $stmt = $this->conn->prepare($query);

            // sanitize
            $this->id=htmlspecialchars(strip_tags($this->id));

            // bind id of record to delete
            $stmt->bindParam(1, $this->id);
            $flags[] = $stmt->execute();
        }
        return !in_array(false,$flags);
    }

    public function checkExists(){
        //$query = "SELECT EXISTS(SELECT id FROM products_temp WHERE id=:id AND ISNULL(product_id)) count";
        $query = "SELECT product_id, action FROM products_temp WHERE id=:id LIMIT 1";

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
            if(is_null($row[0]['product_id'])){
                $this->action = $row[0]['action'];
                return 2; // product id is null => edit new product 
            }
            else {
                $this->product_id = $row[0]['product_id'];
                $this->action = $row[0]['action'];
                return 3; // product id is not null => edit the existings product
            }
        }
        else{
            return 1; // not found in database => new update product
        }
    }
    
    public function checkProductExists(){
        $query = "SELECT id FROM products WHERE id IN (SELECT product_id FROM products_temp WHERE id=:id)";

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
            return true;
        }
        else{
            return false; 
        }
    }
    
    public function getProductPicture(){
        $query = "SELECT target_id, type, pic_path FROM pictures WHERE target_id=?";

        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->product_id=htmlspecialchars(strip_tags($this->product_id));

        // bind id of record to delete
        $stmt->bindParam(1, $this->product_id);
        $stmt->execute();

        $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // set values to object properties
        if(count($row) > 0)
        {
            $this->pictures = Array();
            $this->feature_pictures = Array();
            foreach($row as $x)
            {
                if($x['type']==='p' && array_search($x['pic_path'], $this->pictures) === false)
                    $this->pictures[] = $x['pic_path'];
                else if($x['type']==='d' && array_search($x['pic_path'], $this->feature_pictures) === false)
                    $this->feature_pictures[] = $x['pic_path'];
            }
        }
    }
    
    public function getTempProductPicture(){
        $query = "SELECT target_id, type, pic_path FROM pictures_temp WHERE target_id=?";

        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id));

        // bind id of record to delete
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // set values to object properties
        if(count($row) > 0)
        {
            $this->pictures = Array();
            $this->feature_pictures = Array();
            foreach($row as $x)
            {
                if($x['type']==='p' && array_search($x['pic_path'], $this->pictures) === false)
                    $this->pictures[] = $x['pic_path'];
                else if($x['type']==='d' && array_search($x['pic_path'], $this->feature_pictures) === false)
                    $this->feature_pictures[] = $x['pic_path'];
            }
        }
    }

    public function copyProductInfo($action){
        $query = "INSERT INTO products_temp (
                    product_id, name, description, specification, brand_id, category_id, 
                    type_id, series_id, release_date, user_id, action, created)
                  SELECT id, name, description, specification, brand_id, category_id, 
                    type_id, series_id, release_date, $this->user_id, '$action', CURRENT_TIMESTAMP
                    FROM products WHERE id=?";
        
        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->product_id=htmlspecialchars(strip_tags($this->product_id));

        // bind id of record to delete
        $stmt->bindParam(1, $this->product_id);
        $stmt->execute();

        $this->id = $this->conn->lastInsertId();
    }
    
    public function cloneForUpdate(){
        $query = "UPDATE $this->table_name as p, 
                    (
                        SELECT id, product_id, name, description, specification, brand_id, category_id, type_id, series_id, release_date 
                        FROM $this->table_name_2
                        WHERE id=?
                    ) as t
                    SET p.name = t.name, 
                        p.description = t.description, 
                        p.specification = t.specification, 
                        p.brand_id = t.brand_id, 
                        p.category_id = t.category_id, 
                        p.type_id = t.type_id, 
                        p.series_id = t.series_id, 
                        p.release_date = t.release_date,
                        p.version = $this->version
                    WHERE p.id = t.product_id";
        
        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id));

        // bind id of record to delete
        $stmt->bindParam(1, $this->id);
        return $stmt->execute();
    }

    public function cloneForCreate(){
        $query = "INSERT INTO $this->table_name (
                    name, description, specification, brand_id, category_id, type_id, series_id,release_date, created)
                  SELECT name, description, specification, brand_id, category_id, type_id, series_id, release_date, CURRENT_TIMESTAMP
                    FROM $this->table_name_2 WHERE id=?";
        
        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id));

        // bind id of record to delete
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $this->product_id = $this->conn->lastInsertId();
    }

    private function migrateOption(){
        
        $table = $this->action_mode === 1 ? "options" : "options_temp";
        $table2 = $this->action_mode === 1 ? "options_temp" : "options";
        
        $table_v = $this->action_mode === 1 ? "option_values" : "option_values_temp";
        $table_v2 = $this->action_mode === 1 ? "option_values_temp" : "option_values";

        //read option with option option 
        $query = "SELECT 
                    o.name option_name,
                    ov.name option_value
                  FROM $table o
                  LEFT JOIN $table_v ov
                  ON ov.option_id = o.id
                  WHERE o.product_id = ?";

        $stmt = $this->conn->prepare($query);
        if($this->action_mode === 1)
        {
            $this->product_id=htmlspecialchars(strip_tags($this->product_id));
            $stmt->bindParam(1, $this->product_id);
        }
        else
        {
            $this->id=htmlspecialchars(strip_tags($this->id));
            $stmt->bindParam(1, $this->id);
        }
        $stmt->execute();

        $this->variant = array();
        if($stmt->rowCount() > 0)
        {
            // get retrieved row
            while($row = $stmt->fetch(\PDO::FETCH_ASSOC))
            {
                extract($row);         
                if(array_search($row['option_name'], array_column($this->variant,'name')) === false)
                    $this->variant[] = Array("name"=> $row['option_name'], "list"=> [Array("v_id" => null, "value"=>$row['option_value'])]);
                    
                $index = array_search($row['option_name'], array_column($this->variant,'name'));
                if(array_search($row['option_value'], array_column($this->variant[$index]['list'],'value')) === false)
                    $this->variant[$index]['list'][] = Array("v_id" => null, "value"=>$row['option_value']);
            }
        }
        
        //create variants
        foreach($this->variant as $k => $x)
        {
            // query to insert record
            $query = "INSERT INTO $table2 
            SET 
                product_id=:product_id, 
                name=:name";                                          

            // prepare query
            $stmt = $this->conn->prepare($query);

            // sanitize
            if($this->action_mode === 1)
                $this->id=htmlspecialchars(strip_tags($this->id));
            else
                $this->product_id=htmlspecialchars(strip_tags($this->product_id));
            $x['name']=htmlspecialchars(strip_tags($x['name']));

            // bind values
            if($this->action_mode === 1)
                $stmt->bindParam(":product_id", $this->id);
            else
                $stmt->bindParam(":product_id", $this->product_id);
            $stmt->bindParam(":name", $x['name']);
                
            // execute query
            if($stmt->execute()){
                $this->variant[$k]['id'] = $this->conn->lastInsertId();
                foreach($this->variant[$k]['list'] as $key => $y)
                {
                    $query = "INSERT INTO $table_v2 
                    SET 
                        product_id=:product_id,
                        option_id=:option_id, 
                        name=:name";                                          

                    // prepare query
                    $stmt = $this->conn->prepare($query);

                    // sanitize
                    if($this->action_mode === 1)
                        $this->id=htmlspecialchars(strip_tags($this->id));
                    else
                        $this->product_id=htmlspecialchars(strip_tags($this->product_id));
                    $this->variant[$k]['id']=htmlspecialchars(strip_tags($this->variant[$k]['id']));
                    $y['value']=htmlspecialchars(strip_tags($y['value']));

                    // bind values
                    if($this->action_mode === 1)
                        $stmt->bindParam(":product_id", $this->id);
                    else
                        $stmt->bindParam(":product_id", $this->product_id);
                    $stmt->bindParam(":option_id", $this->variant[$k]['id']);
                    $stmt->bindParam(":name", $y['value']);
                        
                    // execute query
                    if($stmt->execute()){
                        $index = array_search($this->variant[$k]['id'], array_column($this->variant,'id'));
                        $this->variant[$index]['list'][$key]['v_id'] = $this->conn->lastInsertId();
                    }
                }
            }
        }
    }
    
    private function migrateCombination(){
        $table = $this->action_mode === 1 ? "options" : "options_temp";
        $table2 = $this->action_mode === 1 ? "options_temp" : "options";
        
        $table_v = $this->action_mode === 1 ? "option_values" : "option_values_temp";
        $table_v2 = $this->action_mode === 1 ? "option_values_temp" : "option_values";

        $table_s = $this->action_mode === 1 ? "skus" : "skus_temp";
        $table_s2 = $this->action_mode === 1 ? "skus_temp" : "skus";
        
        $table_sv = $this->action_mode === 1 ? "sku_values" : "sku_values_temp";
        $table_sv2 = $this->action_mode === 1 ? "sku_values_temp" : "sku_values";
        
        //read option with option option 
        $query = "SELECT
                        s.id, 
                        s.sku,
                        s.price as sku_price,
                        s.quantity as sku_quantity,
                        o.name as option_name,
                        ov.name as option_value
                    FROM $table_sv sv
                    LEFT JOIN $table o 
                    ON sv.option_id = o.id
                    LEFT JOIN $table_s s 
                    ON sv.sku_id = s.id
                    LEFT JOIN $table_v ov 
                    ON sv.option_value_id = ov.id
                    WHERE sv.product_id = ?";

        $stmt = $this->conn->prepare($query);
        if($this->action_mode === 1)
        {
            $this->product_id=htmlspecialchars(strip_tags($this->product_id));
            $stmt->bindParam(1, $this->product_id);
        }
        else
        {
            $this->id=htmlspecialchars(strip_tags($this->id));
            $stmt->bindParam(1, $this->id);
        }
        $stmt->execute();

        $this->combination = array();

        if($stmt->rowCount() > 0)
        {
            // get retrieved row
            while($row = $stmt->fetch(\PDO::FETCH_ASSOC))
            {
                extract($row); 
                if(array_search($row['sku'], array_column($this->combination,'sku')) === false)
                {
                    $this->combination[] = Array(
                        "id"=>$row['id'],
                        "sku" => $row['sku'], 
                        "price" => $row['sku_price'], 
                        "quantity" => $row['sku_quantity'],
                        "items" => []
                    );
                }
                //add option value to a sku
                $index = array_search($row['sku'], array_column($this->combination,'sku'));
                if(array_search($row['option_value'], array_column($this->combination[$index]['items'],'value')) === false)
                    $this->combination[$index]['items'][] = Array("o_id" => null, "v_id"=>null,"v_name"=>$row['option_name'],"value"=>$row['option_value']);
            }
        }
        
        foreach($this->combination as $k => $x)
        {
            // query to insert record
            $query = "INSERT INTO $table_s2 
            SET 
                product_id=:product_id, 
                sku=:sku,
                price=:price,
                quantity=:quantity";                                          

            // prepare query
            $stmt = $this->conn->prepare($query);

            // sanitize
            if($this->action_mode === 1)
                $this->id=htmlspecialchars(strip_tags($this->id));
            else
                $this->product_id=htmlspecialchars(strip_tags($this->product_id));
            $x['sku']=htmlspecialchars(strip_tags($x['sku']));
            $x['price']=htmlspecialchars(strip_tags($x['price']));
            $x['quantity']=htmlspecialchars(strip_tags($x['quantity']));

            // bind values
            if($this->action_mode === 1)
                $stmt->bindParam(":product_id", $this->id);
            else
                $stmt->bindParam(":product_id", $this->product_id);
            $stmt->bindParam(":sku", $x['sku']);
            $stmt->bindParam(":price", $x['price']);
            $stmt->bindParam(":quantity", $x['quantity']);
                
            // execute query
            if($stmt->execute()){
                $x['id'] = $this->conn->lastInsertId();
                foreach($x['items'] as $sv)
                {
                    $vindex = array_search($sv['v_name'],array_column($this->variant, 'name'));
                    $vvindex = array_search($sv['value'],array_column($this->variant[$vindex]['list'], 'value'));
                    $x['o_id'] = $this->variant[$vindex]['id'];
                    $x['v_id'] = $this->variant[$vindex]['list'][$vvindex]['v_id'];

                    $query = "INSERT INTO $table_sv2 
                    SET 
                        product_id=:product_id, 
                        sku_id=:sku_id, 
                        option_id=:option_id,
                        option_value_id=:option_value_id";                                          

                    // prepare query
                    $stmt = $this->conn->prepare($query);

                    // sanitize
                    if($this->action_mode === 1)
                        $this->id = htmlspecialchars(strip_tags($this->id));
                    else
                        $this->product_id = htmlspecialchars(strip_tags($this->product_id));
                    $x['id']=htmlspecialchars(strip_tags($x['id']));
                    $x['o_id']=htmlspecialchars(strip_tags($x['o_id']));
                    $x['v_id']=htmlspecialchars(strip_tags($x['v_id']));

                    // bind values
                    if($this->action_mode === 1)
                        $stmt->bindParam(":product_id", $this->id);
                    else
                        $stmt->bindParam(":product_id", $this->product_id);
                    $stmt->bindParam(":sku_id", $x['id']);
                    $stmt->bindParam(":option_id", $x['o_id']);
                    $stmt->bindParam(":option_value_id", $x['v_id']);
                        
                    // execute query
                    $stmt->execute();
                }
            }
        }
        
        
    }

    public function cloneVariant(){
        if(isset($this->action_mode) && $this->action_mode === 1){
            $this->deleteVaraint2();
        }
        else{
            $this->deleteVaraint();
        }
        
        $this->migrateOption();

        $this->migrateCombination();
        
    }
}
?>