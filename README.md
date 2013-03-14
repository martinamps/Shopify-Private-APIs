# PHP Shopify Private API Wrapper

---
#### Note: This isn't officially supported by Shopify and thus functionality could be adversely affected at any time
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
	
	$params = [
		'reportcenter' => true,
		'start_date' => '2013-02-22',
		'end_date' => '2013-03-01',
		'timezone' => 'Pacific+Time+(US+%26+Canada)'
	];
	
	$referrals = $api->doRequest('GET', 'referrals.json', $params);
	print_r($referrals);
	
	$facts = $api->doRequest('GET', 'facts.json', $params);
	print_r($facts);
	
	$periodical_facts = $api->doRequest('GET', 'periodical_facts.json', $params);
	print_r($periodical_facts);
}
```

## Sample Output

### Create Token
```php

stdClass Object
(
    [discount] => stdClass Object
        (
            [applies_once] => 
            [applies_to_id] => 
            [code] => automated_token_example
            [ends_at] => 
            [id] => 16956508
            [minimum_order_amount] => 0.00
            [starts_at] => 2013-03-01T00:00:00-08:00
            [status] => enabled
            [usage_limit] => 1
            [value] => 5.0
            [discount_type] => percentage
            [applies_to_resource] => 
            [times_used] => 0
        )

)
```

### Get Token
```php
(
	stdClass Object
	(
	    [applies_once] => 
	    [applies_to_id] => 
	    [code] => automated_token_example
	    [ends_at] => 
	    [id] => 16956508
	    [minimum_order_amount] => 0.00
	    [starts_at] => 2013-03-01T00:00:00-08:00
	    [status] => enabled
	    [usage_limit] => 1
	    [value] => 5.0
	    [discount_type] => percentage
	    [applies_to_resource] => 
	    [times_used] => 0
	)
	
	...	
)

```

### Referrals
```php
stdClass Object
(
    [start_date] => 2013-02-22
    [end_date] => 2013-03-01
    [search_terms] => Array
        (
            [0] => stdClass Object
                (
                    [terms] => shopify.com
                    [count] => 1
                    [percentage] => 100
                )

        )

    [top_referrals] => Array
        (
            [0] => stdClass Object
                (
                    [referrer] => www.example.com
                    [count] => 530
                    [percentage] => 56.025369978858
                )
                
            ....
        )
)
```

### Facts
```php
stdClass Object
(
    [start_date] => 2013-02-22
    [end_date] => 2013-03-01
    [facts] => stdClass Object
        (
            [orders] => xxx4
            [visits] => xxx83
            [customers] => xxx0
            [unique_visits] => xxx8
            [revenue_per_visitor] => x2.600408834586
            [revenue_per_customer] => xx9.10291304348
            [revenue_order_average] => xxx9.34515748031
            [repeat_customer_percentage] => 29.4488188976378
            [revenue] => xxxxxxx8
        )

    [conversions] => stdClass Object
        (
            [total] => stdClass Object
                (
                    [count] => xxx3
                    [percentage] => 100
                )

            [cart] => stdClass Object
                (
                    [count] => xxx1
                    [percentage] => 40.663436451733
                )

```

## Notes

Use at your own risk, enjoy!
