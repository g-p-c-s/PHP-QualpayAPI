# QualpayAPI
PHP wrapper for [Qualpay Payment Gateway](https://www.qualpay.com/)

Note: Still in development and is written with reference to [Qualpay Gateway Specification v1.3](https://www.qualpay.com/download/qppg/Payment_Gateway_Specification_V1.3.pdf)

# Requires
[Guzzle](http://guzzlephp.org/), PHP HTTP Client

# Examples
```php
<?php

require 'QualpayAPI.php';

// Instantiate
$qp = new QualpayAPI($merchant_id, $security_key, FALSE);

  $card = array(
          'card_number' => '4111xxxxxxxx1111',
          'exp_date' => '0120',   //MMYY
          'cardholder_name' => 'Mister Customer',
          'cvv2' => '113',
          'avs_zip' => '12345'
  );

  $trans = array(
          'tran_currency' => $qp->currencyCode('USD'),
          'amt_tran' => '1.00',
          'purchase_id' => uniqid() // Your Order Id, Invoice# etc.
  );

// Execute  
$response = $qp->authAndTokenize($card, $trans);

print_r( $response );
```
