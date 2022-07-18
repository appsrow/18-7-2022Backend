<?php

namespace App\Helpers;

class GeneralHelper
{

    public static function RandomString()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randstring = '';
        for ($i = 0; $i < 10; $i++) {
            $randstring = $characters[rand(0, strlen($characters))];
        }
        return $randstring;
    }

    public static function RandomNumber($length)
    {
        return rand(pow(10, $length - 1), pow(10, $length) - 1);
    }

    public static function public_path($path = '')
    {
        return env('PUBLIC_PATH', base_path('public')) . ($path ? '/' . $path : $path);
    }

    public static function RequestCurl($method, $url, $options = array())
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request($method, $url, $options);
        $statusCode = $response->getStatusCode();
        $responseBody = json_decode($response->getBody(), true);
        $responseBody = !empty($responseBody) ? $responseBody : array();

        if ($statusCode == 200) {
            $return  = array("status" => true, "data" => $responseBody);
        } else {
            $return  = array("status" => false, "data" => $responseBody);
        }

        return $return;
    }
}
