<?php
    namespace api\config;

    //Check if CONSTANT called SITE_URL is defined.
    if(!defined('SITE_URL')) {
        //Send 403 Forbidden response.
        header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
        //Kill the script.
        exit;
    }

    use utility\Encryptor\PasswordEncryptor;
    require_once "dbconfig.php";
    class Database{
        private $host;
        private $db_name;
        private $username;
        private $password;
        private $redis;
        public $conn;

        public function __construct(){
            $app_config = [
                "host" => "localhost",
                "dbname" => "cheannyo_chean",
                "dbusername" => "cheannyo_jeffindonchanty",
                "dbpassword" => "S]!gjl!S@M_h",
            ];
            $this->host=$app_config['host'];
            $this->db_name=$app_config['dbname'];
            $this->username=$app_config['dbusername'];
            $this->pass=$app_config['dbpassword'];
            $this->conn = null;
            //echo $this->db_name;
        }
        public function __destruct()
        {
            $this->conn = null;
        }

        public function getConnection(){
            
            try{
                $this->conn = new \PDO("mysql:host=$this->host;dbname=$this->db_name;charset=utf8", $this->username, $this->pass);
                $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $sqls = [
                    "CREATE TABLE IF NOT EXISTS `users` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `username` varchar(100) NOT NULL UNIQUE,
                        `firstname` varchar(100) NOT NULL,
                        `lastname` varchar(100) NOT NULL,
                        `gender` varchar(1) NOT NULL,
                        `dob` date NOT NULL,
                        `type` varchar(10) NOT NULL,
                        `email` varchar(255) NOT NULL UNIQUE,
                        `password` varchar(255) NOT NULL,
                        `created` datetime NOT NULL,
                        `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `lastlogin` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `status` int(2) NOT NULL,
                        PRIMARY KEY (`id`)
                    )",
                    "CREATE TABLE IF NOT EXISTS `users_temp` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `user_id` int(11),
                        `username` varchar(100) NOT NULL,
                        `firstname` varchar(100) NOT NULL,
                        `lastname` varchar(100) NOT NULL,
                        `gender` varchar(1) NOT NULL,
                        `dob` date NOT NULL,
                        `type` varchar(10) NOT NULL,
                        `email` varchar(255) NOT NULL,
                        `password` varchar(255) NOT NULL,
                        `action` varchar(20) NOT NULL,
                        `executor_id` int(11) NOT NULL,
                        `created` datetime NOT NULL,
                        `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `status` int(2) NOT NULL,
                        PRIMARY KEY (`id`)
                    )",
                    /*"CREATE TABLE IF NOT EXISTS `verify` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `user_id` int(11) NOT NULL UNIQUE,
                        `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                        PRIMARY KEY (`id`) 
                    )",*/
                    "CREATE TABLE IF NOT EXISTS `reset_password_request` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `user_id` int(11) NOT NULL UNIQUE,
                        `token` text NOT NULL,
                        `decrypt_key` text NOT NULL,
                        `date_requested` datetime NOT NULL,
                        `status` int(2) DEFAULT 1,
                        PRIMARY KEY (`id`),
                        FOREIGN KEY (`user_id`) REFERENCES users(`id`)
                    )",
                    "CREATE TABLE IF NOT EXISTS `brands` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `name` varchar(256) NOT NULL,
                        `created` datetime NOT NULL,
                        `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `status` int(2) NOT NULL,
                        `priority` int(2),
                        PRIMARY KEY (`id`)
                    )",
                    "CREATE TABLE IF NOT EXISTS `series` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `brand_id` int(11) NOT NULL,
                        `name` varchar(256) NOT NULL,
                        `created` datetime NOT NULL,
                        `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `status` int(2) NOT NULL,
                        PRIMARY KEY (`id`),
                        FOREIGN KEY (`brand_id`) REFERENCES brands(`id`)
                    )",
                    "CREATE TABLE IF NOT EXISTS `categories` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `name` varchar(256) NOT NULL,
                        `description` text NOT NULL,
                        `created` datetime NOT NULL,
                        `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `status` int(2) NOT NULL,
                        PRIMARY KEY (`id`)
                      ) ENGINE=InnoDB  DEFAULT CHARSET=utf8",
                    "CREATE TABLE IF NOT EXISTS `types` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `category_id` INT(11) NOT NULL,
                        `name` varchar(256) NOT NULL,
                        `created` datetime NOT NULL,
                        `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `status` int(2) NOT NULL,
                        PRIMARY KEY (`id`),
                        FOREIGN KEY (`category_id`) REFERENCES categories(`id`)
                      )",
                    "CREATE TABLE IF NOT EXISTS `products` (
                        `id` int(11) NOT NULL AUTO_INCREMENT, 
                        `name` varchar(256) NOT NULL,
                        `description` text NOT NULL,
                        `specification` text NOT NULL,
                        `label` varchar(30) NOT NULL,
                        `brand_id` int(11) NOT NULL,
                        `category_id` int(11) NOT NULL,
                        `type_id` int(11) DEFAULT NULL,
                        `series_id` int(11) DEFAULT NULL,
                        `release_date` date DEFAULT NULL,
                        `created` datetime NOT NULL,
                        `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `status` int(2) NOT NULL,
                        PRIMARY KEY (`id`),
                        FOREIGN KEY (`brand_id`) REFERENCES brands(`id`),
                        FOREIGN KEY (`category_id`) REFERENCES categories(`id`)
                    )",//temp
                    "CREATE TABLE IF NOT EXISTS `products_temp` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `product_id` int(11),
                        `name` varchar(256) NOT NULL,
                        `description` text NOT NULL,
                        `specification` text NOT NULL,
                        `label` varchar(30) NOT NULL,
                        `brand_id` int(11) NOT NULL,
                        `category_id` int(11) NOT NULL,
                        `type_id` int(11) DEFAULT NULL,
                        `series_id` int(11) DEFAULT NULL,
                        `release_date` date DEFAULT NULL,
                        `user_id` int(11) NOT NULL,
                        `action` varchar(20) NOT NULL,
                        `created` datetime NOT NULL,
                        `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `status` int(2) NOT NULL,
                        PRIMARY KEY (`id`),
                        FOREIGN KEY (`brand_id`) REFERENCES brands(`id`),
                        FOREIGN KEY (`category_id`) REFERENCES categories(`id`),
                        FOREIGN KEY (`user_id`) REFERENCES users(`id`)
                    )",
                    "CREATE TABLE IF NOT EXISTS `options` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `product_id` INT(11) NOT NULL,
                        `name` varchar(256) NOT NULL,
                        PRIMARY KEY (`id`),
                        FOREIGN KEY (`product_id`) REFERENCES products(`id`)
                    )",//temp
                    "CREATE TABLE IF NOT EXISTS `options_temp` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `product_id` INT(11) NOT NULL,
                        `name` varchar(256) NOT NULL,
                        PRIMARY KEY (`id`),
                        FOREIGN KEY (`product_id`) REFERENCES products_temp(`id`)
                    )",
                    "CREATE TABLE IF NOT EXISTS `option_values` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `product_id` INT(11) NOT NULL,
                        `option_id` INT(11) NOT NULL,
                        `name` varchar(256) NOT NULL,
                        PRIMARY KEY (`id`),
                        FOREIGN KEY (`product_id`) REFERENCES products(`id`),
                        FOREIGN KEY (`option_id`) REFERENCES options(`id`)
                    )",//temp
                    "CREATE TABLE IF NOT EXISTS `option_values_temp` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `product_id` INT(11) NOT NULL,
                        `option_id` INT(11) NOT NULL,
                        `name` varchar(256) NOT NULL,
                        PRIMARY KEY (`id`),
                        FOREIGN KEY (`product_id`) REFERENCES products_temp(`id`),
                        FOREIGN KEY (`option_id`) REFERENCES options_temp(`id`)
                    )",
                    "CREATE TABLE IF NOT EXISTS `skus` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `product_id` INT(11) NOT NULL,
                        `sku` varchar(256) NOT NULL,
                        `price` decimal(10,2) NOT NULL,
                        `quantity` int(11) NOT NULL,
                        PRIMARY KEY (`id`),
                        FOREIGN KEY (`product_id`) REFERENCES products(`id`)
                    )",//temp
                    "CREATE TABLE IF NOT EXISTS `skus_temp` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `product_id` INT(11) NOT NULL,
                        `sku` varchar(256) NOT NULL,
                        `price` decimal(10,2) NOT NULL,
                        `quantity` int(11) NOT NULL,
                        PRIMARY KEY (`id`),
                        FOREIGN KEY (`product_id`) REFERENCES products_temp(`id`)
                    )",
                    "CREATE TABLE IF NOT EXISTS `sku_values` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `product_id` INT(11) NOT NULL,
                        `sku_id` INT(11) NOT NULL,
                        `option_id` INT(11) NOT NULL,
                        `option_value_id` INT(11) NOT NULL,
                        PRIMARY KEY (`id`),
                        FOREIGN KEY (`product_id`) REFERENCES products(`id`),
                        FOREIGN KEY (`sku_id`) REFERENCES skus(`id`),
                        FOREIGN KEY (`option_id`) REFERENCES options(`id`),
                        FOREIGN KEY (`option_value_id`) REFERENCES option_values(`id`)
                    )",//temp
                    "CREATE TABLE IF NOT EXISTS `sku_values_temp` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `product_id` INT(11) NOT NULL,
                        `sku_id` INT(11) NOT NULL,
                        `option_id` INT(11) NOT NULL,
                        `option_value_id` INT(11) NOT NULL,
                        PRIMARY KEY (`id`),
                        FOREIGN KEY (`product_id`) REFERENCES products_temp(`id`),
                        FOREIGN KEY (`sku_id`) REFERENCES skus_temp(`id`),
                        FOREIGN KEY (`option_id`) REFERENCES options_temp(`id`),
                        FOREIGN KEY (`option_value_id`) REFERENCES option_values_temp(`id`)
                    )",
                    "CREATE TABLE IF NOT EXISTS `pictures` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `target_id` int(11) NOT NULL,
                        `type` varchar(2) NOT NULL,
                        `pic_path` text NOT NULL,
                        PRIMARY KEY (`id`)
                    )",//temp
                    "CREATE TABLE IF NOT EXISTS `pictures_temp` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `target_id` int(11) NOT NULL,
                        `type` varchar(2) NOT NULL,
                        `pic_path` text NOT NULL,
                        PRIMARY KEY (`id`)
                    )",
                    "CREATE TABLE IF NOT EXISTS `carts` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `product_id` INT(11) NOT NULL,
                        `sku_id` INT(11) NOT NULL,
                        `quantity` INT(11) NOT NULL,
                        `user_id` INT(11) NOT NULL,
                        `version` int(11) DEFAULT 1,
                        `created` datetime NOT NULL,
                        `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        FOREIGN KEY (`product_id`) REFERENCES products(`id`),
                        FOREIGN KEY (`user_id`) REFERENCES users(`id`)
                    )",
                    "CREATE TABLE IF NOT EXISTS `promotions` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `name` VARCHAR(256) NOT NULL,
                        `description` text NOT NULL,
                        `discount` decimal(10,2) NOT NULL,
                        `discount_type` varchar(15) NOT NULL,
                        `date_begin` datetime NOT NULL,
                        `date_end` datetime NOT NULL,
                        `created` datetime NOT NULL,
                        `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `status` INT(2) NOT NULL,
                        PRIMARY KEY (`id`)
                    )",
                    "CREATE TABLE IF NOT EXISTS `promotion_items` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `promotion_id` INT(11) NOT NULL,
                        `target_id` VARCHAR(256) NOT NULL,
                        PRIMARY KEY(`id`),
                        FOREIGN KEY (`promotion_id`) REFERENCES promotions(`id`)
                    )",
                    "CREATE TABLE IF NOT EXISTS `orders` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `refno` VARCHAR(25) NOT NULL,
                        `user_id` INT(11) NOT NULL,
                        `order_date` datetime NOT NULL,
                        `amount` decimal(10,2) NOT NULL,
                        `currency` VARCHAR(10) NOT NULL,
                        `payment_id` VARCHAR(4) NOT NULL,
                        `prodDesc` TEXT NOT NULL,
                        `order_subtotal` decimal(10,2) NOT NULL,
                        `shipping_fee` decimal(10,2) DEFAULT 0,
                        `insurance_fee` decimal(10,2) DEFAULT 0,
                        `shipping_address` text NOT NULL,
                        `shipping_postalcode` VARCHAR(6) NOT NULL,
                        `shipping_city` VARCHAR(255) NOT NULL,
                        `shipping_state` VARCHAR(255) NOT NULL,
                        `user_name` text NOT NULL,
                        `user_contact` VARCHAR(12) NOT NULL,
                        `user_email` VARCHAR(255) NOT NULL,
                        `status` INT(2) NOT NULL DEFAULT 0,
                        `refund` INT(2) NOT NULL DEFAULT 0,
                        `remark` TEXT DEFAULT NULL,
                        `created` datetime NOT NULL,
                        `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `payment_date` datetime,
                        `refund_date` datetime,
                        PRIMARY KEY (`id`),
                        FOREIGN KEY (`user_id`) REFERENCES users(`id`)
                    )",
                    "CREATE TABLE IF NOT EXISTS `order_details` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `order_id` INT(11) NOT NULL,
                        `product_id` INT(11) NOT NULL,
                        `sku_id` INT(11) NOT NULL,
                        `quantity` INT(11) NOT NULL,
                        `created` datetime NOT NULL,
                        `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        FOREIGN KEY (`order_id`) REFERENCES orders(`id`),
                        FOREIGN KEY (`sku_id`) REFERENCES skus(`id`)
                    )",
                    "CREATE TABLE IF NOT EXISTS `consignment` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `order_id` INT(11) NOT NULL,
                        `company_name` VARCHAR(255), 
                        `tracking_code` VARCHAR(50), 
                        `remark` text,
                        `status` INT(2) NOT NULL,
                        `created` datetime NOT NULL,
                        `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        FOREIGN KEY (`order_id`) REFERENCES orders(`id`)
                    )"
                    ];
                foreach($sqls as $sql)
                {
                    $this->conn->exec($sql);
                }
                //echo "Table MyGuests created successfully";
                $this->initalize();
            }catch(PDOException $exception){
                echo "Connection error: " . $exception->getMessage();
            }
            return $this->conn;
        }

        private function initalize()
        {
            $sql = "SELECT * FROM users LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            if($stmt->rowCount() == 0)
            { 
                $USERNAME = "ADMIN";
                $FIRSTNAME = "USER";
                $LASTNAME= "ADMIN";
                $GENDER="M";
                $DOB="1900-01-01";
                $TYPE="SUPERUSER";
                $EMAIL="admin@meowmeow.com";
                $CREATED = date('Y-m-d H:i:s');
                $MODIFIED = date('Y-m-d H:i:s');
                $LASTLOGIN = date('Y-m-d H:i:s');
                $PASSWORD=PasswordEncryptor::encrypt('admin123');
                $STATUS= 1;

                $sql = "INSERT INTO users 
                        SET 
                            username= :username,
                            firstname= :firstname,
                            lastname= :lastname,
                            gender= :gender,
                            dob= :dob,
                            type= :type,
                            email= :email,
                            password= :password,  
                            created= :created,
                            modified= :modified,
                            lastlogin= :lastlogin,                  
                            status= :status";
                // prepare query
                $stmt = $this->conn->prepare($sql); 
                $stmt->bindParam(":username", $USERNAME);
                $stmt->bindParam(":firstname", $FIRSTNAME);
                $stmt->bindParam(":lastname", $LASTNAME);
                $stmt->bindParam(":gender", $GENDER);
                $stmt->bindParam(":dob", $DOB);
                $stmt->bindParam(":type", $TYPE);
                $stmt->bindParam(":email", $EMAIL);
                $stmt->bindParam(":password", $PASSWORD);
                $stmt->bindParam(":created", $CREATED);
                $stmt->bindParam(":modified", $MODIFIED);
                $stmt->bindParam(":lastlogin", $LASTLOGIN);
                $stmt->bindParam(":status", $STATUS);
                $stmt->execute();
            }
        }
    }

?>