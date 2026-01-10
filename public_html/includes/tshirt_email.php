<?php
/**
 * T-Shirt Order Email Functions (Backward Compatibility)
 *
 * This file provides backward compatibility for code that references
 * the old T-shirt specific email functions. It simply includes the
 * new generic store_email.php which contains all the same functions.
 *
 * The functions send_tshirt_order_confirmation() and send_tshirt_order_notification()
 * are defined in store_email.php as wrappers around the generic functions.
 */

require_once(__DIR__ . '/store_email.php');
?>
