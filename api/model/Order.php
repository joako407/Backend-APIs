<?php
namespace api\model;
//Check if CONSTANT called SITE_URL is defined.
if(!defined('SITE_URL')) {
    //Send 403 Forbidden response.
    header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
    //Kill the script.
    exit;
}

class Order{

    // database connection and table name
    private $conn;
    private $table_name = "orders";
  
    // object properties
    public $id;
    public $refno;
    public $user_id;
    public $order_date;
    public $amount;
    public $currency;
    public $payment_id;
    public $prodDesc;
    public $order_subtotal;
    public $shipping_fee;
    public $insurance_fee;
    public $shipping_address;
    public $shipping_postalcode;
    public $shipping_city;
    public $shipping_state;
    public $user_name;
    public $user_contact;
    public $user_email;
    public $created;
    public $modified;
    public $status;
    public $order_items;
    public $remark;
    public $payment_date;
    public $refund_date;
  
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
                    o.id AS order_id, 
                    o.refno,
                    o.user_id,
                    o.order_date,
                    o.amount,
                    o.currency,
                    o.payment_id,
                    o.prodDesc,
                    o.order_subtotal,
                    o.shipping_fee,
                    o.insurance_fee,
                    o.shipping_address,
                    o.shipping_postalcode,
                    o.shipping_city,
                    o.shipping_state,
                    o.user_name,
                    o.user_contact,
                    o.user_email,
                    o.status AS order_status,
                    o.refund,
                    o.payment_date,
                    o.refund_date,
                    d.id AS order_item_id,
                    d.product_id,
                    d.sku_id,
                    d.quantity,
                    p.name,
                    p.description,
                    p.status AS product_status,
                    i.pic_path,
                    sv.option_id,
                    sv.option_value_id,
                    op.id as optionId,
                    op.name as optionName,
                    ov.id as optionValueId,
                    ov.name as optionValue
                    FROM $this->table_name o
                    LEFT JOIN order_details d
                    ON o.id = d.order_id
                    LEFT JOIN products p
                    ON p.id = d.product_id
                    LEFT JOIN pictures i
                    ON i.target_id = d.product_id
                    LEFT JOIN skus s
                    ON s.id = d.sku_id
                    LEFT JOIN sku_values sv
                    ON sv.sku_id = s.id
                    LEFT JOIN options op
                    ON op.id = sv.option_id
                    LEFT JOIN option_values ov
                    ON ov.id = sv.option_value_id
                    WHERE i.type='p' AND o.user_id = ?";
                    
        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->user_id=htmlspecialchars(strip_tags($this->user_id));
    
        // bind values
        $stmt->bindParam(1, $this->user_id);
        
        // execute query
        $stmt->execute();        
        return $stmt;
    }

    public function readAll(){
        // select all query
   
        $query = "SELECT
                    o.id AS order_id, 
                    o.refno,
                    o.user_id,
                    o.order_date,
                    o.payment_id,
                    o.prodDesc,
                    o.order_subtotal,
                    o.order_total,
                    o.order_total,
                    o.shipping_fee,
                    o.insurance_fee,
                    o.status AS order_status,
                    o.refund,
                    o.payment_date,
                    o.refund_date,
                    o.remark,
                    ad.name AS user_name,
                    ad.contact,
                    ad.address,
                    ad.postalcode,
                    ad.city,
                    ad.state,
                    ph.transId,
                    ph.amount AS payment_amount,
                    ph.authDetail,
                    ph.status AS payment_status,
                    d.id AS order_item_id,
                    d.product_id,
                    d.sku_id,
                    d.quantity,
                    p.name,
                    p.description,
                    p.status AS product_status,
                    i.pic_path,
                    s.sku,
                    s.price,
                    sv.option_id,
                    sv.option_value_id,
                    op.id as optionId,
                    op.name as optionName,
                    ov.id as optionValueId,
                    ov.name as optionValue
                    FROM $this->table_name o
                    LEFT JOIN addresses ad
                    ON ad.id = o.address_id
                    LEFT JOIN payment_history ph
                    ON ph.refno = o.refno
                    LEFT JOIN order_details d
                    ON o.id = d.order_id
                    LEFT JOIN customer_products p
                    ON p.id = d.product_id
                    LEFT JOIN customer_pictures i
                    ON i.target_id = d.product_id
                    LEFT JOIN customer_skus s
                    ON s.id = d.sku_id
                    LEFT JOIN customer_sku_values sv
                    ON sv.sku_id = s.id
                    LEFT JOIN customer_options op
                    ON op.id = sv.option_id
                    LEFT JOIN customer_option_values ov
                    ON ov.id = sv.option_value_id
                    WHERE i.type='p' AND o.status <> -1 AND o.refund <> 1 ORDER BY o.order_date DESC, o.status ASC";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // execute query
        $stmt->execute();        
        return $stmt;
    }

    public function readAllRefund(){
        // select all query
   
        $query = "SELECT
                    o.id AS order_id, 
                    o.refno,
                    o.user_id,
                    o.order_date,
                    o.payment_id,
                    o.prodDesc,
                    o.order_subtotal,
                    o.order_total,
                    o.order_total,
                    o.shipping_fee,
                    o.insurance_fee,
                    o.status AS order_status,
                    o.refund,
                    o.payment_date,
                    o.refund_date,
                    o.remark,
                    ad.name AS user_name,
                    ad.contact,
                    ad.address,
                    ad.postalcode,
                    ad.city,
                    ad.state,
                    ph.transId,
                    ph.amount AS payment_amount,
                    ph.authDetail,
                    ph.status AS payment_status,
                    d.id AS order_item_id,
                    d.product_id,
                    d.sku_id,
                    d.quantity,
                    p.name,
                    p.description,
                    p.status AS product_status,
                    i.pic_path,
                    s.sku,
                    s.price,
                    sv.option_id,
                    sv.option_value_id,
                    op.id as optionId,
                    op.name as optionName,
                    ov.id as optionValueId,
                    ov.name as optionValue
                    FROM $this->table_name o
                    LEFT JOIN addresses ad
                    ON ad.id = o.address_id
                    LEFT JOIN payment_history ph
                    ON ph.refno = o.refno
                    LEFT JOIN order_details d
                    ON o.id = d.order_id
                    LEFT JOIN customer_products p
                    ON p.id = d.product_id
                    LEFT JOIN customer_pictures i
                    ON i.target_id = d.product_id
                    LEFT JOIN customer_skus s
                    ON s.id = d.sku_id
                    LEFT JOIN customer_sku_values sv
                    ON sv.sku_id = s.id
                    LEFT JOIN customer_options op
                    ON op.id = sv.option_id
                    LEFT JOIN customer_option_values ov
                    ON ov.id = sv.option_value_id
                    WHERE i.type='p' AND o.refund = 1 ORDER BY o.status ASC";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        // execute query
        $stmt->execute();        
        return $stmt;
    }

    // create category
    public function create(){
        // query to insert record
        $query = "INSERT INTO ".$this->table_name." 
            SET refno=:refno,
                user_id=:user_id,
                order_date=:order_date,
                amount=:amount,
                currency=:currency,
                prodDesc=:prodDesc,
                payment_id=:payment_id,
                order_subtotal=:order_subtotal,
                insurance_fee=:insurance_fee,
                shipping_fee=:shipping_fee,
                shipping_address=:shipping_address,
                shipping_postalcode=:shipping_postalcode,
                shipping_city=:shipping_city,
                shipping_state=:shipping_state,
                user_name=:user_name,
                user_contact=:user_contact,
                user_email=:user_email,
                status=:status,
                created=:created";
    
        // prepare query
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->refno=htmlspecialchars(strip_tags($this->refno));
        $this->user_id=htmlspecialchars(strip_tags($this->user_id));
        $this->order_date=htmlspecialchars(strip_tags($this->order_date));
        $this->amount=htmlspecialchars(strip_tags($this->amount));
        $this->currency=htmlspecialchars(strip_tags($this->currency));
        $this->prodDesc=htmlspecialchars(strip_tags($this->prodDesc));
        $this->payment_id=htmlspecialchars(strip_tags($this->payment_id));
        $this->order_subtotal=htmlspecialchars(strip_tags($this->order_subtotal));
        $this->insurance_fee=htmlspecialchars(strip_tags($this->insurance_fee));
        $this->shipping_fee=htmlspecialchars(strip_tags($this->shipping_fee));
        $this->shipping_address=htmlspecialchars(strip_tags($this->shipping_address));
        $this->shipping_postalcode=htmlspecialchars(strip_tags($this->shipping_postalcode));
        $this->shipping_city=htmlspecialchars(strip_tags($this->shipping_city));
        $this->shipping_state=htmlspecialchars(strip_tags($this->shipping_state));
        $this->user_name=htmlspecialchars(strip_tags($this->user_name));
        $this->user_contact=htmlspecialchars(strip_tags($this->user_contact));
        $this->user_email=htmlspecialchars(strip_tags($this->user_email));
        $this->status=htmlspecialchars(strip_tags($this->status));
        $this->created=htmlspecialchars(strip_tags($this->created));
            
        // bind values
        $stmt->bindParam(":refno", $this->refno);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":order_date", $this->order_date);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":currency", $this->currency);
        $stmt->bindParam(":prodDesc", $this->prodDesc);
        $stmt->bindParam(":payment_id", $this->payment_id);
        $stmt->bindParam(":order_subtotal", $this->order_subtotal);
        $stmt->bindParam(":insurance_fee", $this->insurance_fee);
        $stmt->bindParam(":shipping_fee", $this->shipping_fee);
        $stmt->bindParam(":shipping_address", $this->shipping_address);
        $stmt->bindParam(":shipping_postalcode", $this->shipping_postalcode);
        $stmt->bindParam(":shipping_city", $this->shipping_city);
        $stmt->bindParam(":shipping_state", $this->shipping_state);
        $stmt->bindParam(":user_name", $this->user_name);
        $stmt->bindParam(":user_contact", $this->user_contact);
        $stmt->bindParam(":user_email", $this->user_email);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":created", $this->created);
    
        // execute query
        if($stmt->execute()){
            $this->id = $this->conn->lastInsertId();
            $flag = $this->createOrderItem();
            return $flag;
        }
        return false;
    }

    function createOrderItem(){
        $flag = [];
        foreach($this->order_items as $x)
        {
            // query to insert record
            $query = "INSERT INTO order_details 
            SET order_id=:order_id,
                product_id=:product_id,
                sku_id=:sku_id,
                quantity=:quantity,
                created=:created";
            
            // prepare query
            $stmt = $this->conn->prepare($query);
        
            // sanitize
            $this->id=htmlspecialchars(strip_tags($this->id)); //order_id
            $x->product_id=htmlspecialchars(strip_tags($x->product_id)); // product id
            $x->sku_id=htmlspecialchars(strip_tags($x->sku_id)); // sku_id
            $x->quantity=htmlspecialchars(strip_tags($x->quantity));
            $this->created=htmlspecialchars(strip_tags($this->created));
            
            // bind values
            $stmt->bindParam(":order_id", $this->id);
            $stmt->bindParam(":product_id", $x->product_id);
            $stmt->bindParam(":sku_id", $x->sku_id);
            $stmt->bindParam(":quantity", $x->quantity);
            $stmt->bindParam(":created", $this->created);

            // execute query
            if($stmt->execute()){
                array_push($flag, true);
            }     
            else{
                array_push($flag, false);
            }
        }
        if (count(array_unique($flag)) === 1 && end($flag) === 'true') {
            return true;
        }
        else{
            return false;
        }
    }
    
    public function checkExists(){
        if($this->id != null){
            $this->update();
        }
        else{
            $this->create();
        }
    }

    // update the category
    function update(){
        // update query
        $query = "UPDATE
                    " . $this->table_name . "
                SET "
                    .((isset($this->payment_id) && !empty($this->payment_id))? "payment_id = :payment_id,":"")
                    .((isset($this->insurance_fee) && !empty($this->insurance_fee))? "insurance_fee = :insurance_fee,":"")
                    .((isset($this->shipping_fee) && !empty($this->shipping_fee))? "shipping_fee = :shipping_fee,":"")
                    .((isset($this->address_id) && !empty($this->address_id))? "address_id = :address_id,":"")
                    .((isset($this->user_name) && !empty($this->user_name))? "user_name = :user_name,":"")
                    .((isset($this->user_contact) && !empty($this->user_contact))? "user_contact = :user_contact,":"")
                    .((isset($this->user_email) && !empty($this->user_email))? "user_email = :user_email,":"")
                    .((isset($this->status) && !empty($this->status))? "status = :status,":"")
                    .((isset($this->remark) && !empty($this->remark))? "remark = :remark,":"")
                    .((isset($this->refund) && !empty($this->refund))? "refund = :refund,":"")
                    .((isset($this->refund_date) && !empty($this->refund_date))? "refund_date = :refund_date,":"")
                    ."modified = :modified
                WHERE
                    id = :id";
    
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->payment_id=(isset($this->payment_id) && !empty($this->payment_id))? htmlspecialchars(strip_tags($this->payment_id)) : $this->payment_id;
        $this->insurance_fee=(isset($this->insurance_fee) && !empty($this->insurance_fee))? htmlspecialchars(strip_tags($this->insurance_fee)) : $this->insurance_fee;
        $this->shipping_fee=(isset($this->shipping_fee) && !empty($this->shipping_fee))? htmlspecialchars(strip_tags($this->shipping_fee)) : $this->shipping_fee;
        $this->address_id=(isset($this->address_id) && !empty($this->address_id))? htmlspecialchars(strip_tags($this->address_id)) : $this->address_id;
        $this->user_name=(isset($this->user_name) && !empty($this->user_name))? htmlspecialchars(strip_tags($this->user_name)) : $this->user_name;
        $this->user_contact=(isset($this->user_contact) && !empty($this->user_contact))? htmlspecialchars(strip_tags($this->user_contact)) : $this->user_contact;
        $this->user_email=(isset($this->user_email) && !empty($this->user_email))? htmlspecialchars(strip_tags($this->user_email)) : $this->user_email;
        $this->status=(isset($this->status) && !empty($this->status))? htmlspecialchars(strip_tags($this->status)) : $this->status;
        $this->remark=(isset($this->remark) && !empty($this->remark))? htmlspecialchars(strip_tags($this->remark)) : $this->remark;
        $this->refund=(isset($this->refund) && !empty($this->refund))? htmlspecialchars(strip_tags($this->refund)) : $this->refund;
        $this->refund_date=(isset($this->refund_date) && !empty($this->refund_date))? htmlspecialchars(strip_tags($this->refund_date)) : $this->refund_date;
        $this->modified=htmlspecialchars(strip_tags($this->modified));
        $this->id=htmlspecialchars(strip_tags($this->id)); 
    
        // bind new values
        if(isset($this->payment_id) && !empty($this->payment_id))
            $stmt->bindParam(':payment_id', $this->payment_id);
        if(isset($this->shipping_fee) && !empty($this->shipping_fee))
            $stmt->bindParam(':shipping_fee', $this->shipping_fee);
        if(isset($this->insurance_fee) && !empty($this->insurance_fee))
            $stmt->bindParam(':insurance_fee', $this->insurance_fee);
        if(isset($this->address_id) && !empty($this->address_id))
            $stmt->bindParam(':address_id', $this->address_id);
        if(isset($this->user_name) && !empty($this->user_name))
            $stmt->bindParam(':user_name', $this->user_name);
        if(isset($this->user_contact) && !empty($this->user_contact))
            $stmt->bindParam(':user_contact', $this->user_contact);
        if(isset($this->user_email) && !empty($this->user_email))
            $stmt->bindParam(':user_email', $this->user_email);
        if(isset($this->status) && !empty($this->status))
            $stmt->bindParam(':status', $this->status);
        if(isset($this->remark) && !empty($this->remark))
            $stmt->bindParam(':remark', $this->remark);
        if(isset($this->refund) && !empty($this->refund))
            $stmt->bindParam(':refund', $this->refund);
        if(isset($this->refund_date) && !empty($this->refund_date))
            $stmt->bindParam(':refund_date', $this->refund_date);
        $stmt->bindParam(':modified', $this->modified);
        $stmt->bindParam(':id', $this->id);    
    
        // execute the query
        if($stmt->execute()){
            return true;
        }
    
        return false;
    }
    
    function updatePaymentStatus(){
        // update query
        $query = "UPDATE
                    " . $this->table_name . "
                SET "
                    ."status = :status"
                    ."payment_date = :payment_date"
                    ."modified = :modified
                WHERE
                    id = :id";
        
        // prepare query statement
        $stmt = $this->conn->prepare($query);
    
        // sanitize
        $this->status=htmlspecialchars(strip_tags($this->status));
        $this->payment_date=htmlspecialchars(strip_tags($this->payment_date));
        $this->modified=htmlspecialchars(strip_tags($this->modified));
        $this->id=htmlspecialchars(strip_tags($this->id)); 
        
        //bind new values
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':payment_date', $this->payment_date);
        $stmt->bindParam(':modified', $this->modified);
        $stmt->bindParam(':id', $this->id); 
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