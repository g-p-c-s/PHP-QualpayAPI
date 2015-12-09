<?php

use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\RequestExeption;

/**
 *
 */
class QualpayAPI
{
    private $client;
    private $api_host;
    private $merchant_id;
    private $security_key;
    private $tokenize_if_allowed = false;
    private $other_common_fields = [
        'moto_ecomm_ind' => 7, // Ecommerce
    ];

    /**
     * List of supported currencies and their Qualpay codes.
     *
     * @var Array
     */
    private $supported_currencies = [
        'USD' => 841,
        'GBP' => 826,
        'EUR' => 978,
    ];

    public function __construct($merchant_id, $security_key, $is_live = true, $tokenize_if_allowed = true)
    {
        if ($is_live == true) {
            $this->api_host = 'https://api.qualpay.com';
        } else {
            $this->api_host = 'https://api-test.qualpay.com';
            // $this->api_host = 'http://localhost:5255';
        }

        // Set the merchant_id and key
        $this->merchant_id = $merchant_id;
        $this->security_key = $security_key;

        $this->tokenize_if_allowed = $tokenize_if_allowed;

        // Instantiate Http Client
        $this->client = new Client([
                'base_uri' => $this->api_host,
            ]);
    }

    /**
     * Capture given amount from a Tokenized Card.
     *
     * @param Var    $card_id  Tokenied Card Id
     * @param String $exp_date Expiration Date in MMYY format
     * @param Array  $trans    Transaction details
     *
     * @return [type] [description]
     */
    public function captureFromToken($card_id, $exp_date, $trans)
    {
        $command_uri = '/pg/capture';

        $inputData = [
            'card_id' => $card_id,
            'exp_date' => $exp_date,
        ];

        $inputData = $this->clubArrays($inputData, $trans);

        // Execute
        return $this->submitCommand($command_uri, $inputData);
    }

    /**
     * Run a Sale on the given card
     * and Tokenize.
     *
     * @param Array $card  Card Data
     * @param Array $trans Transaction details e.g Amount, Order Id
     *
     * @return Array [description]
     */
    public function saleAndTokenize($card, $trans)
    {
        $command_uri = '/pg/sale';

        if ($this->tokenize_if_allowed) {
            $auth['tokenize'] = 'true';
        }

        //print_r( array_merge($card, $trans, $auth) );
        $inputData = array();
        $inputData = $this->clubArrays($inputData, $card);
        $inputData = $this->clubArrays($inputData, $trans);
        $inputData = $this->clubArrays($inputData, $auth);

        // Execute
        return $this->submitCommand($command_uri, $inputData);
    }

    /**
     * Run a Authorization on the given card
     * and Tokenize.
     *
     * @param Array $card  Card Data
     * @param Array $trans Transaction details e.g Amount, Order Id
     *
     * @return Array [description]
     */
    public function authAndTokenize($card, $trans)
    {
        $command_uri = '/pg/auth';

        if ($this->tokenize_if_allowed) {
            $auth['tokenize'] = 'true';
        }

        //print_r( array_merge($card, $trans, $auth) );
        $inputData = array();
        $inputData = $this->clubArrays($inputData, $card);
        $inputData = $this->clubArrays($inputData, $trans);
        $inputData = $this->clubArrays($inputData, $auth);

        // Execute
        return $this->submitCommand($command_uri, $inputData);
    }

    /**
     * Verify the given card
     * and Tokenize.
     *
     * @param Array $card Card Data
     *
     * @return Array [description]
     */
    public function verifyAndTokenize($card)
    {
        $command_uri = '/pg/verify';

        if ($this->tokenize_if_allowed) {
            $auth['tokenize'] = 'true';
        }

        //print_r( array_merge($card, $trans, $auth) );
        $inputData = array();
        $inputData = $this->clubArrays($inputData, $card);

        // Execute
        return $this->submitCommand($command_uri, $inputData);
    }

    /**
     * Verify the given card
     * and Tokenize.
     *
     * @param Array $card Card Data
     *
     * @return Array [description]
     */
    public function refundTransaction($pg_id, $trans)
    {
        $command_uri = '/pg/refund/'.$pg_id;

        //print_r( array_merge($card, $trans, $auth) );
        $inputData = array();
        //$inputData = $this->clubArrays($inputData, $card);
        $inputData = $this->clubArrays($inputData, $trans);
        //$inputData = $this->clubArrays($inputData, $auth);


        // Execute
        return $this->submitCommand($command_uri, $inputData);
    }

    /**
     * [submitCommand description].
     *
     * @param String $command_uri [description]
     * @param Array  $data        [description]
     *
     * @return Array [description]
     */
    private function submitCommand($command_uri, $inputData)
    {
        $commandResponse = [
            'status' => 0,  // Unsuccessful
            'errorCode' => 0,
            'errorMessage' => 'Unknown',
            'statusCode' => 0,    // HTTP Response Code
            'reasonPhrase' => 'Unknown',
            'body' => [],
        ];

        // Auth fields
        $inputData['merchant_id'] = $this->merchant_id;
        $inputData['security_key'] = $this->security_key;

        // Attach any other common fields if defined
        if (count($this->other_common_fields) > 0) {
            foreach ($this->other_common_fields as $key => $value) {
                $inputData[$key] = $value;
            }
        }

        $clientHandler = $this->client->getConfig('handler');
        $tapMiddleware = Middleware::tap(function ($request) {
            echo 'Printing Headers: '.PHP_EOL;
            // foreach($request->getHeaders() as $h=>$v){
            //     echo $h . ': ' . var_dump($v) . PHP_EOL;
            // }
            echo 'Content-Type: '.var_dump($request->getHeader('Content-Type')).PHP_EOL;
            echo 'Body: '.$request->getBody().PHP_EOL;
            // {"foo":"bar"}
        });

        echo 'INPUT: '.print_r($inputData, true).PHP_EOL;

        try {
            $response = $this->client->post(
                '/pg/auth',
                [
                    'timeout' => 30,
                    'json' => $inputData,
                    'debug' => true,
                    'handler' => $tapMiddleware($clientHandler)
                ]
            );

            $commandResponse['errorCode'] = 0;
            $commandResponse['errorMessage'] = '';

            $commandResponse['statusCode'] = $response->getStatusCode();
            $commandResponse['reasonPhrase'] = $response->getReasonPhrase();
            $commandResponse['body'] = json_decode($response->getBody()->getContents());
        } catch (ServerException $e) {
            // 500 errors
            $commandResponse['errorCode'] = $e->getCode();
            $commandResponse['errorMessage'] = $e->getMessage();
            $commandResponse['line'] = $e->getLine();
            $commandResponse['file'] = $e->getFile();

            // echo $e->getRequest();
            if ($e->hasResponse()) {
                $response = $e->getResponse();

                $commandResponse['statusCode'] = $response->getStatusCode();
                $commandResponse['reasonPhrase'] = $response->getReasonPhrase();
                $commandResponse['body'] = json_decode($response->getBody()->getContents());
            }
        } catch (ClientException $e) {
            // 400 errors
            $commandResponse['errorCode'] = $e->getCode();
            $commandResponse['errorMessage'] = $e->getMessage();
            $commandResponse['line'] = $e->getLine();
            $commandResponse['file'] = $e->getFile();

            // echo $e->getRequest();
            if ($e->hasResponse()) {
                $response = $e->getResponse();

                $commandResponse['statusCode'] = $response->getStatusCode();
                $commandResponse['reasonPhrase'] = $response->getReasonPhrase();
                $commandResponse['body'] = json_decode($response->getBody()->getContents());
            }
        } catch (RequestExeption $e) {
            // 500 errors
            $commandResponse['errorCode'] = $e->getCode();
            $commandResponse['errorMessage'] = $e->getMessage();
            $commandResponse['line'] = $e->getLine();
            $commandResponse['file'] = $e->getFile();

            // echo $e->getRequest();
            if ($e->hasResponse()) {
                $response = $e->getResponse();

                $commandResponse['statusCode'] = $response->getStatusCode();
                $commandResponse['reasonPhrase'] = $response->getReasonPhrase();
                $commandResponse['body'] = json_decode($response->getBody()->getContents());
            }
        } catch (Exception $e) {
            // Any other
            $commandResponse['errorCode'] = $e->getCode();
            $commandResponse['errorMessage'] = $e->getMessage();
            $commandResponse['line'] = $e->getLine();
            $commandResponse['file'] = $e->getFile();
        }

        return $commandResponse;
    }

    /**
     * Find gateway's currency code for a given 3-letter
     * ISO currency code.
     *
     * @param String $currency_string 3-letter currency code
     *
     * @return Mixed 3-letter currency if supported, FALSE otherwise
     */
    public function currencyCode($currency_string)
    {
        if (in_array($currency_string, array_keys($this->supported_currencies))) {
            return $this->supported_currencies[$currency_string];
        } else {
            return false;
        }
    }

    /**
     * [clubArrays description].
     *
     * @param Array $arr1 [description]
     * @param Array $arr2 [description]
     *
     * @return Array $arr1 with all elements of $arr2 added
     */
    private function clubArrays($arr1, $arr2)
    {
        foreach ($arr2 as $k => $v) {
            $arr1[$k] = $v;
        }

        return $arr1;
    }
}
