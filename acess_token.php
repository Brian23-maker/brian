<?php
function generateAccessToken() {
    $consumerKey = " "; // Replace with your actual key
    $consumerSecret = " "; // Replace with your actual secret
    $url = "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";

    $credentials = base64_encode("$consumerKey:$consumerSecret");

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ["Authorization: Basic $credentials"]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curl);
    curl_close($curl);

    $json = json_decode($response);
    return $json->access_token;
}
?>