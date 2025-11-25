--Create the table with the orders
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Auto generated unique key for every order',
    user_id INT NOT NULL COMMENT 'id from users table of who made the order',
    completed BOOL DEFAULT 0 COMMENT 'is the order paid for and completed',
    paypal_payment VARCHAR(100) DEFAULT NULL COMMENT 'The ID from paypal for the payment',
    page VARCHAR(100) COMMENT 'the page which this order is assoicated with.'
);

--Create the table with the items in the orders
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'unique id for every item per user per page - not really used',
    order_id INT COMMENT 'order id this item is associated with - must always be populated',
    item_id VARCHAR(100) COMMENT 'A (unique) string that meaninfully denominates the item to be ordered.',
    item_quantity INT COMMENT 'The number of these items that a user currently has in their card for this order.'
);

--DROP PROCEDURE IF EXISTS update_order_item;
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS update_order_item (
    IN in_order_id INT,
    IN in_item_id VARCHAR(100),
    IN in_item_quantity INT
)
BEGIN
    -- Check if the item is already in the order
    IF EXISTS (
        SELECT 1 
        FROM order_items 
        WHERE order_id = in_order_id AND item_id = in_item_id
    ) 
    THEN
        -- Item is already there - update the row's quantity only
        UPDATE order_items
        SET item_quantity = in_item_quantity
        WHERE order_id = in_order_id AND item_id = in_item_id;
    ELSE
        -- Item is not there yet - insert a row
        INSERT INTO order_items (order_id, item_id, item_quantity)
        VALUES (in_order_id, in_item_id, in_item_quantity);
    END IF;
END //
DELIMITER ;