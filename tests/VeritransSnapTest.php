<?php

class VeritransSnapTest extends PHPUnit_Framework_TestCase
{

    public function testGetSnapToken() {
      Veritrans_Config::$serverKey = 'My Very Secret Key';
      VT_Tests::$stubHttp = true;
      VT_Tests::$stubHttpResponse = '{ "token_id": "abcdefghijklmnopqrstuvwxyz" }';
      VT_Tests::$stubHttpStatus = array('http_code' => 200);

      $params = array(
        'transaction_details' => array(
          'order_id' => "Order-111",
          'gross_amount' => 10000,
        )
      );

      $tokenId = Veritrans_Snap::getSnapToken($params);

      $this->assertEquals($tokenId, "abcdefghijklmnopqrstuvwxyz");

      $this->assertEquals(
        VT_Tests::$lastHttpRequest["url"],
        "https://app.sandbox.veritrans.co.id/snap/v1/charge"
      );

      $this->assertEquals(
        VT_Tests::$lastHttpRequest["server_key"],
        'My Very Secret Key'
      );

      $fields = VT_Tests::lastReqOptions();

      $this->assertEquals($fields["POST"], 1);
      $this->assertEquals($fields["POSTFIELDS"],
        '{"credit_card":{"secure":false},' .
        '"transaction_details":{"order_id":"Order-111","gross_amount":10000}}'
      );
    }

    public function testGrossAmount() {
      $params = array(
        'transaction_details' => array(
          'order_id' => rand()
        ),
        'item_details' => array( array( 'price' => 10000, 'quantity' => 5 ) )
      );

      VT_Tests::$stubHttp = true;
      VT_Tests::$stubHttpResponse = '{ "token_id": "abcdefghijklmnopqrstuvwxyz" }';
      VT_Tests::$stubHttpStatus = array('http_code' => 200);

      $tokenId = Veritrans_Snap::getSnapToken($params);

      $this->assertEquals(
        VT_Tests::$lastHttpRequest['data_hash']['transaction_details']['gross_amount'],
        50000
      );
    }

    public function testOverrideParams() {
      $params = array(
        'echannel' => array(
          'bill_info1' => 'bill_value1'
        )
      );

      VT_Tests::$stubHttp = true;
      VT_Tests::$stubHttpResponse = '{ "token_id": "abcdefghijklmnopqrstuvwxyz" }';
      VT_Tests::$stubHttpStatus = array('http_code' => 200);

      $tokenId = Veritrans_Snap::getSnapToken($params);

      $this->assertEquals(
        VT_Tests::$lastHttpRequest['data_hash']['echannel'],
        array('bill_info1' => 'bill_value1')
      );
    }

    public function testRealConnect() {
      $params = array(
        'transaction_details' => array(
          'order_id' => rand(),
          'gross_amount' => 10000,
        )
      );

      try {
        $tokenId = Veritrans_Snap::getSnapToken($params);
      } catch (Exception $error) {
        $errorHappen = true;
        $this->assertEquals(
          $error->getMessage(),
          "Veritrans Error (401): Access denied due to unauthorized transaction, please check client or server key");
      }

      $this->assertTrue($errorHappen);
    }

    public function tearDown() {
      VT_Tests::reset();
    }

}
