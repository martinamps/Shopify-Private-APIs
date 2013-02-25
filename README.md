# PHP Shopify Private API Wrapper

---

Faced with the need of automatically generating coupon codes I turned to the Shopify public API. Unfortunately, no such functionality existed. I figured the ones used by their admin panel were just undocumented, however the typical API credentials weren't accepted. This wrapper enables this use of such API's.

## Requirements

* PHP 5.4+
* PHP curl 
* Shopify store admin account

## Usage

```php

<?php

require 'shopify.php';

$api = new \Shopify\PrivateAPI('username', 'password', 'https://mystore.myshopify.com/admin');

if (!$api->isLoggedIn() && !$api->login()) {
	echo 'invalid credentials';
} else {
	
	# Create a 5% discount coupon
	$new_discount = ['discount' => [
		'applies_to_id' => '',
		'code' => 'automatic_coupon'
		'discount_type' => 'percentage',
		'value' => 5,
		'usage_limit' => 1,
		'starts_at' => date('Y-m-d\TH:i:sO', mktime(0, 0, 0)),
		'ends_at' => null,
		'applies_once' => false
	]];
	
	# Set the CSRF token for the POST request
	try { $api->setToken('https://mystore.myshopify.com/admin/discounts/new'); } 
	catch (\Exception $ex) { }
	
	$do_discount = $api->doRequest('POST', 'discounts.json', $new_discount);

	print_r($do_discount);
	

	# List coupons
	$params = [
		'limit' => 50, 
		'order' => 'id+DESC', 
		'direction' => 'next'
	];
		
	$discounts = $api->doRequest('GET', 'discounts.json', $params);
	if (isset($discounts->discounts)) {
		$coupons = $discounts->discounts;
		foreach ($coupons as $coupon) {
			print_r($coupon);
		}
	}
}
```

## Notes

Use at your own risk, enjoy!