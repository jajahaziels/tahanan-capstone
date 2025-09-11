CREATE TABLE ecommerce_orders (

 
order_id            BIGINT NOT NULL AUTO_INCREMENT,
order_date          DATETIME NOT NULL,

   
customer_id         BIGINT NOT NULL,
customer_name       VARCHAR(150) NOT NULL,  
customer_email      VARCHAR(150),   
customer_phone      VARCHAR(50),   
customer_address    TEXT,

product_id          BIGINT NOT NULL, 
product_name        VARCHAR(200) NOT NULL,  
product_sku         VARCHAR(50),   
product_category    VARCHAR(100),  
brand_name          VARCHAR(100),
unit_price          DECIMAL(10,2),  
quantity            INT, 
line_total          DECIMAL(12,2),

payment_method  VARCHAR(50),   

payment_status     VARCHAR(50),   
  
transaction_id      VARCHAR(100),



shipping_method     VARCHAR(100),

shipping_status    
VARCHAR(50),   

Shipped, Delivered

tracking_number     VARCHAR(100),
   
shipping_cost       DECIMAL(10,2),


   
order_subtotal      DECIMAL(12,2),
   
order_discount      DECIMAL(12,2),

order_tax           DECIMAL(12,2),

order_grand_total   DECIMAL(12,2),

PRIMARY KEY (order_id, product_id) -- composite key (1 order, many products)

);
SELECT * FROM ecommerce_orders;