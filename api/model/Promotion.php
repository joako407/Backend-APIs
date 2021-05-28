<?php
namespace api\model;

//Check if CONSTANT called SITE_URL is defined.
if(!defined('SITE_URL')) {
    //Send 403 Forbidden response.
    header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
    //Kill the script.
    exit;
}

class Promotion{

    // database connection and table name
    private $conn;
    private $table_name = "promotions";
  
    // object properties
    public $id;
    public $name;
    public $description;
    public $discount;
    public $discount_type;
    public $date_begin;
    public $date_end;
    public $items;
    public $created;
    public $modified;
    public $status;
    public $promotionList;
  
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

    public function __destruct()
    {
        $this->conn = null;
    }

    //read product
    public function read(){
        // select all query        

        $query = "SELECT
                p.id, 
                p.name, 
                p.description, 
                p.discount,
                p.discount_type,
                p.date_begin,
                p.date_end,
                p.created,
                p.modified,
                i.target_id,
                p.status
            FROM ".$this->table_name." p 
            LEFT JOIN promotion_items i
            ON p.id = i.promotion_id";
        
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        
        // execute query
        $stmt->execute();        
        return $stmt;
    }
    
    public function readActivePromotion($product){
        $query = "SELECT
                p.id, 
                p.name, 
                p.description, 
                p.discount,
                p.discount_type,
                p.date_begin,
                p.date_end,
                p.created,
                p.modified,
                i.target_id,
                p.status
            FROM ".$this->table_name." p 
            LEFT JOIN promotion_items i
            ON p.id = i.promotion_id
            WHERE status=1 AND CURRENT_TIMESTAMP between date_begin AND date_end";

        // prepare query statement
        $stmt = $this->conn->prepare($query);
        
        // execute query
        if($stmt->execute())
        {
            $promotions_arr=array();
            $promotions_arr["records"]=array();
            // retrieve our table contents
            // fetch() is faster than fetchAll()
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
                // extract row
                // this will make $row['name'] to
                // just $name only
                extract($row);
                if($key = array_search($id,array_column($promotions_arr['records'],'id')) === false)
                {
                    $promotion_arr = array(
                        "id" =>  $id,
                        "name" => $name,
                        "description" => $description,
                        "discount" => $discount,
                        "discount_type" => $discount_type,
                        "date_begin" => $date_begin,
                        "date_end" => $date_end,
                        "item" => [$target_id],
                        "status" => $status
                    );  
                    array_push($promotions_arr["records"], $promotion_arr);  
                }
                else{
                    $promotions_arr["records"][$key]['item'][] = $target_id;
                }
            }
            $this->promotionList = $this->targetSplitter($promotions_arr, $product);
        }        
    }

    private function targetSplitter($promotions_arr, $product){
        $promotionProductArray = Array();
    
        foreach($promotions_arr['records'] as $promotion)
        {
            $categories = Array();
            $brands = Array();
            $types = Array();
            $skus = Array();
    
            foreach($promotion['item'] as $item)
            {
                $arr =  Array();
                $arr[] = $item[0];
                $arr[] = substr($item,1);
    
                switch($arr[0])
                {
                    case 'B':
                        $brands[] = $arr[1];
                        break;
                    case 'C':
                        $categories[] = $arr[1];
                        break;
                    case 'T':
                        $types[] = $arr[1];
                        break;
                    default:
                        $skus[] = $arr[1];
                }
            }
    
            $promotionProduct = Array(
                "promotionID" => $promotion['id'],
                "promotionName" => $promotion['name'],
                "product_skus" => []
            );
    
            $product->prom_brand_id = isset($brands) ? $brands : null;
            $product->prom_category_id = isset($categories) ? $categories : null;
            $product->prom_type_id = isset($types) ? $types : null;
            $product->prom_sku_id = isset($skus) ? $skus : null;
    
            $stmt = $product->readProductForPromotion();
            $num = $stmt->rowCount();
            if($num > 0)
            {
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
                    extract($row);
                    $promotionProduct['product_skus'][] = $sku_id;
                }
            }  
            array_push($promotionProductArray,$promotionProduct); 
        }
        //echo json_encode($promotionProductArray);
        return $promotionProductArray;
    }

    public function getPromotionsToProduct($product){

        $promotions = Array();
        if($product['combinations'] != null)
        {
            foreach($product['combinations'] as $comb)
            {
                foreach($this->promotionList as $pro)
                {
                    if(array_search($comb['id'], $pro['product_skus']))
                    {
                        if(array_search($pro['promotionID'], array_column($promotions,'promotionID')) === false)
                        {
                            $promotions[] = Array(
                                "promotionID" => $pro['promotionID'],
                                "promotionName" => $pro['promotionName'],
                                "target_skus" => [$comb['id']]
                            );
                        }
                        else{
                            $index = array_search($pro['promotionID'], array_column($promotions,'promotionID'));
                            $promotions[$index]['target_skus'][] = $comb['id'];
                        }
                        
                    }
                }
            }
        }
        
        return $promotions;
    }


    // create product
    public function create(){
        // query to insert record
        $query = "INSERT INTO ".$this->table_name." 
                SET 
                    name=:name,
                    description=:description, 
                    discount=:discount,
                    discount_type=:discount_type, 
                    date_begin=:date_begin,
                    date_end=:date_end,
                    created=:created,
                    status=:status";
    
        // prepare query
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->name=htmlspecialchars(strip_tags($this->name));
        $this->description=htmlspecialchars(strip_tags($this->description));
        $this->discount=htmlspecialchars(strip_tags($this->discount));
        $this->discount_type=htmlspecialchars(strip_tags($this->discount_type));
        $this->date_begin=htmlspecialchars(strip_tags($this->date_begin));
        $this->date_end=htmlspecialchars(strip_tags($this->date_end));
        $this->created=htmlspecialchars(strip_tags($this->created));
        $this->status=htmlspecialchars(strip_tags($this->status));
    
        // bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":discount", $this->discount);
        $stmt->bindParam(":discount_type", $this->discount_type);
        $stmt->bindParam(":date_begin", $this->date_begin);
        $stmt->bindParam(":date_end", $this->date_end);
        $stmt->bindParam(":created", $this->created);
        $stmt->bindParam(":status", $this->status);
    
        // execute query
        if($stmt->execute()){
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // update the product
    public function update(){
    
        // update query
        $query = "UPDATE
                    " . $this->table_name . "
                SET "
                    .((isset($this->name) && !empty($this->name))? "name=:name,":"")
                    .((isset($this->description) && !empty($this->description))? "description=:description," : "")
                    .((isset($this->discount) && !empty($this->discount))? "discount=:discount," : "")
                    .((isset($this->discount_type) && !empty($this->discount_type))? "discount_type=:discount_type," : "")
                    .((isset($this->date_begin) && !empty($this->date_begin))? "date_begin=:date_begin," : "")
                    .((isset($this->date_end) && !empty($this->date_end))? "date_end=:date_end," : "")
                    .((isset($this->status) && $this->status !== '')? "status=:status," : "")
                    ."modified = :modified 
                WHERE
                    id=:id";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id));
        $this->modified=htmlspecialchars(strip_tags($this->modified));
        $this->name=(isset($this->name) && !empty($this->name))? htmlspecialchars(strip_tags($this->name)) : $this->name;
        $this->description=(isset($this->description) && !empty($this->description))? htmlspecialchars(strip_tags($this->description)) : $this->description;
        $this->discount=(isset($this->discount) && !empty($this->discount))? htmlspecialchars(strip_tags($this->discount)) : $this->discount;
        $this->discount_type=(isset($this->discount_type) && !empty($this->discount_type))? htmlspecialchars(strip_tags($this->discount_type)) : $this->discount_type;
        $this->date_begin=(isset($this->date_begin) && !empty($this->date_begin))? htmlspecialchars(strip_tags($this->date_begin)) : $this->date_begin;
        $this->date_end=(isset($this->date_end) && !empty($this->date_end))? htmlspecialchars(strip_tags($this->date_end)) : $this->date_end;
        $this->status=(isset($this->status) && $this->status !== '')? htmlspecialchars(strip_tags($this->status)) : $this->status;       
    
        // bind new values
        $stmt->bindParam(':id', $this->id); 
        $stmt->bindParam(':modified', $this->modified);
        if(isset($this->name) && !empty($this->name))
            $stmt->bindParam(':name', $this->name);
        if(isset($this->description) && !empty($this->description))
            $stmt->bindParam(':description', $this->description);
        if(isset($this->discount) && !empty($this->discount))
            $stmt->bindParam(':discount', $this->discount);
        if(isset($this->discount_type) && !empty($this->discount_type))
            $stmt->bindParam(':discount_type', $this->discount_type);
        if(isset($this->date_begin) && !empty($this->date_begin))
            $stmt->bindParam(':date_begin', $this->date_begin);
        if(isset($this->date_end) && !empty($this->date_end))
            $stmt->bindParam(':date_end', $this->date_end);
        if(isset($this->status) && $this->status !== '')
            $stmt->bindParam(':status', $this->status);
    
        // execute the query
        if($stmt->execute()){
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

    public function setItem(){
        $flags = [];
        foreach($this->items as $x)
        {
            // query to insert record
            $query = "INSERT INTO promotion_items 
            SET 
                promotion_id=:promotion_id,
                target_id=:target_id";

            // prepare query
            $stmt = $this->conn->prepare($query);

            // sanitize
            $this->id=htmlspecialchars(strip_tags($this->id));
            $x=htmlspecialchars(strip_tags($x));

            // bind values
            $stmt->bindParam(":promotion_id", $this->id);
            $stmt->bindParam(":target_id", $x);

            // execute query
            $flags[] =$stmt->execute();
        }
        return !in_array(false,$flags);    
    }

    public function removeItem(){
        
            // query to insert record
            $query = "DELETE FROM promotion_items 
                    WHERE promotion_id = ?";

            // prepare query
            $stmt = $this->conn->prepare($query);

            // sanitize
            $this->id=htmlspecialchars(strip_tags($this->id));

            // bind values
            $stmt->bindParam(1, $this->id);

            // execute query
            return $stmt->execute();
        
    }
}
?>