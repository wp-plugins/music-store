<?php
	echo 'Start IPN';
	
	$item_name = $_POST['item_name'];
	$item_number = $_POST['item_number'];
	$payment_status = $_POST['payment_status'];
	$payment_amount = $_POST['mc_gross'];
	$payment_currency = $_POST['mc_currency'];
	$txn_id = $_POST['txn_id'];
	$receiver_email = $_POST['receiver_email'];
	$payer_email = $_POST['payer_email'];
	$payment_type = $_POST['payment_type'];

	if ($payment_status != 'Completed' && $payment_type != 'echeck') exit;
	if ($payment_type == 'echeck' && $payment_status == 'Completed') exit;
	
	$price = -1;
	
	if(!isset($_GET['id']) || !isset($_GET['purchase_id'])) exit;
	
	$id = $_GET['id'];
	$_post = get_post($id);
	if(is_null($_post)) exit;
	switch ($_post->post_type){
		case "ms_song":
			$obj = new MSSong($id);
		break;
		case "ms_collection":
			$obj = new MSCollection($id);
		break;
		default:
			exit;
		break;
	}
	
	if (!isset($obj->price) || abs($payment_amount - $obj->price) > 0.2) exit;
	
	$str = "";
	foreach ($_POST as $item => $value) $str .= $item."=".$value."\r\n";
	
	// Insert purchase in database
	if($wpdb->insert(
						$wpdb->prefix.MSDB_PURCHASE,
						array(
							'product_id'  => $_GET['id'],
							'purchase_id' => $_GET['purchase_id'],
							'date'		  => date( 'Y-m-d H:i:s'),
							'email'		  => $payer_email,
							'amount'	  => $payment_amount,
							'paypal_data' => $str
						),
						array('%d', '%s', '%s', '%s', '%f', '%s')
					))
	{
		// Increase sales in elements
		$obj->purchases++;
	}
	
	
	
	$ms_notification_from_email 		= get_option('ms_notification_from_email', MS_NOTIFICATION_FROM_EMAIL);
	$ms_notification_to_email   		= get_option('ms_notification_to_email', MS_NOTIFICATION_TO_EMAIL);
	
	$ms_notification_to_payer_subject   = get_option('ms_notification_to_payer_subject', MS_NOTIFICATION_TO_PAYER_SUBJECT);
	$ms_notification_to_payer_message   = get_option('ms_notification_to_payer_message', MS_NOTIFICATION_TO_PAYER_MESSAGE);
	
	$ms_notification_to_seller_subject  = get_option('ms_notification_to_seller_subject', MS_NOTIFICATION_TO_SELLER_SUBJECT);
	$ms_notification_to_seller_message  = get_option('ms_notification_to_seller_message', MS_NOTIFICATION_TO_SELLER_MESSAGE);
	
	$information_payer = "Product: {$item_name}\n".
						 "Amount: {$payment_amount} {$payment_currency}\n".
						 "Download Link: ".MS_H_URL."?ms-action=download&purchase_id={$_GET['purchase_id']}\n";
						 
	$information_seller = "Product: {$item_name}\n".
						  "Amount: {$payment_amount} {$payment_currency}\n".
						  "Buyer Email: {$payer_email}\n".
						  "Download Link: ".MS_H_URL."?ms-action=download&purchase_id={$_GET['purchase_id']}\n";
						 
	$ms_notification_to_payer_message  = str_replace("%INFORMATION%", $information_payer, $ms_notification_to_payer_message);
	$ms_notification_to_seller_message = str_replace("%INFORMATION%", $information_seller, $ms_notification_to_seller_message);
	
	// Send email to payer
	wp_mail($payer_email, $ms_notification_to_payer_subject, $ms_notification_to_payer_message,
            "From: \"$ms_notification_from_email\" <$ms_notification_from_email>\r\n".
            "Content-Type: text/plain; charset=utf-8\n".
            "X-Mailer: PHP/" . phpversion());

    // Send email to seller
	wp_mail($ms_notification_to_email , $ms_notification_to_seller_subject, $ms_notification_to_seller_message,
			"From: \"$ms_notification_from_email\" <$ms_notification_from_email>\r\n".
			"Content-Type: text/plain; charset=utf-8\n".
			"X-Mailer: PHP/" . phpversion());

   echo 'OK';
   exit();
?>