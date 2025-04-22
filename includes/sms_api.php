<?php
require __DIR__ . '/../vendor/autoload.php';

use Twilio\Rest\Client;

function send_sms($to, $message) {
    $account_sid = 'AC1d2661f97a8b504707c9b481f9bb427c';
    $auth_token = '6f8ae565d1b047f5109b0a026dd65e9c';
    $twilio_number = '+16205228936';
    
    $to = preg_replace('/^0/', '+63', $to); // Add country code for the Philippines

    $client = new Client($account_sid, $auth_token);
    $client->messages->create(
        $to,
        array(
            'from' => $twilio_number,
            'body' => $message
        )
    );
}
?>