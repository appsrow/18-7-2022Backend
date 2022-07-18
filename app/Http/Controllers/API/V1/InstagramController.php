<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\InstagramFollowers;
use App\InstagramFollows;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class InstagramController extends Controller
{
    public function GetInstagramAccessToken(Request $request)
    {
        $requestData = $request->json()->all();
        $code = $requestData['code'];

        $validator =  Validator::make($requestData, [
            "code" => 'required',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return $this->sendError($error, null, 400);
        }
        $instagram_client_id =  env("INSTAGRAM_CLIENT_ID");
        //  $instagram_client_id =  '301605308829401';
        $instagram_client_secret = env("INSTAGRAM_CLIENT_SECRET");
        // $instagram_client_secret = '3fd9a43d38524e1af701d06e91f2b33d';
        // $redirect_url = 'https://coinbase.hcshub.in/';
        $redirect_url = env("INSTAGRAM_REDIRECT_URL");
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.instagram.com/oauth/access_token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('client_id' => $instagram_client_id, 'client_secret' => $instagram_client_secret, 'grant_type' => 'authorization_code', 'redirect_uri' => $redirect_url, 'code' => $code),
            CURLOPT_HTTPHEADER => array(
                'Cookie: csrftoken=T4TwVOIUipWVStL1Cghmi9l2GzdCqHhO; ig_did=877E2228-6DB6-4C82-9A31-32F5F630D7CF; ig_nrcb=1; mid=YnzIrwAEAAFS5WST3FFM1lpGGKs9'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $resp = json_decode($response);

        if (!empty($resp->access_token)) {
            return $this->sendResponse(json_decode($response), Lang::get("common.success", array(), $this->selected_language), 200);
        }

        if ($resp->code) {
            return $this->sendError('', json_decode($response), 201);
        }
    }

    public function getInstaUserName(Request $request)
    {
        $requestData = $request->json()->all();
        $access_token = $requestData['access_token'];

        $validator =  Validator::make($requestData, [
            "access_token" => 'required',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return $this->sendError($error, null, 400);
        }
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://graph.instagram.com/me?fields=id,username&access_token=' . $access_token . '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: csrftoken=T4TwVOIUipWVStL1Cghmi9l2GzdCqHhO; ig_did=877E2228-6DB6-4C82-9A31-32F5F630D7CF; ig_nrcb=1; mid=YnzIrwAEAAFS5WST3FFM1lpGGKs9'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        // echo $response;

        return $this->sendResponse(json_decode($response), Lang::get("common.success", array(), $this->selected_language), 200);
    }

    #Allows Python script ask for pending brands follows verification
    public function getInstagramFollows(Request $request)
    {
        $requestData = $request->json()->all();

        $pending_follows = InstagramFollows::where('is_follower', '!=', '1')->get();

        return $this->sendResponse($pending_follows, Lang::get("common.success", array(), $this->selected_language), 200);
    }

    #Allows post new InstagramFollows requests
    public function postInstagramFollows(Request $request)
    {
        $requestData = $request->json()->all();

        if (count($requestData) > 0) {
            $validator =  Validator::make($requestData, [
                'user_id' => 'required',
                'campaign_id' => 'required',
                'brand_instagram_account' => 'required',
                'user_instagram_account' => 'required'
            ]);
            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }

            $camp_ids = $requestData['campaign_id'];
            $campaign_id = $this->encrypt_decrypt($camp_ids, 'decrypt');

            // check if the same instagram task is there for the specific user
            $check_user_follow = InstagramFollows::where('campaign_id', $campaign_id)->where('user_id', $requestData['user_id'])->first();

            if (!$check_user_follow) {
                $instagramFollow = new InstagramFollows;
                $instagramFollow->campaign_id = $campaign_id;
                $instagramFollow->user_id = $requestData['user_id'];
                $instagramFollow->brand_instagram_account = $requestData['brand_instagram_account'];
                $instagramFollow->user_instagram_account = $requestData['user_instagram_account'];
                $instagramFollow->is_follower = -2;
                $instagramFollow->save();

                if ($instagramFollow) {
                    return $this->sendResponse($instagramFollow, "Success");
                } else {
                    return $this->sendError("Failed", json_decode("{}"), 500);
                }
            }
            // else {
            //     // already perfomed task
            //     return $this->sendError(Lang::get("campaign.campaign_task_already_completed", array(), $this->selected_language), null, 201);
            // }
        } else {
            return $this->sendResponse(json_decode("{}"), "Request body not found", 200);
        }
    }
    #Allows update InstagramFollows requests
    public function updateInstagramFollows(Request $request)
    {
        $requestData = $request->json()->all();

        if (count($requestData) > 0) {
            $validator =  Validator::make($requestData, [
                'id' => 'required',
                'is_follower' => 'required'
            ]);
            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }
            $instagramFollow = InstagramFollows::where('id', $requestData['id'])->first();
            if (!empty($instagramFollow)) {
                $instagramFollow->is_follower = $requestData['is_follower'];
                $instagramFollow = $instagramFollow->save();
                if (!empty($instagramFollow)) {
                    return $this->sendResponse($instagramFollow, "Success");
                } else {
                    return $this->sendError("Failed", json_decode("{}"), 500);
                }
            } else {
                return $this->sendError("Data not found.", json_decode("{}"));
            }
        } else {
            return $this->sendResponse(json_decode("{}"), "Request body not found", 200);
        }
    }

    public function checkInstagramFollow(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $validator =  Validator::make($requestData, [
                'campaign_id' => 'required',
                'user_id' => 'required'
            ]);
            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }

            $camp_ids = $requestData['campaign_id'];
            $campaign_id = $this->encrypt_decrypt($camp_ids, 'decrypt');
            $user_id = $requestData['user_id'];
            $action = InstagramFollows::where('campaign_id', $campaign_id)
                ->where('user_id', $user_id)->first();
            if (!empty($action)) {
                if ($action['is_follower'] == '-2') {
                    return $this->sendError(Lang::get("common.follow_wait_msg", array(), $this->selected_language), json_decode("{}"), 201);
                }
                if ($action['is_follower'] == '-1') {
                    return $this->sendError(Lang::get("common.follow_wait_msg", array(), $this->selected_language), json_decode("{}"), 201);
                }
                if ($action['is_follower'] == '0') {
                    return $this->sendError(Lang::get("common.follow_not_done", array(), $this->selected_language), json_decode("{}"), 201);
                }
                if ($action['is_follower'] == '1') {
                    return $this->sendResponse('', Lang::get("common.success", array(), $this->selected_language), 200);
                }
            } else {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendResponse(json_decode("{}"), "Request body not found", 400);
        }
    }

    public function encrypt_decrypt($string, $action = 'encrypt')
    {
        $encrypt_method = "AES-256-CBC";
        $secret_key = 'AA74CDCC2BBRT935136HH7B63C27'; // user define private key
        $secret_iv = '5fgf5HJ5g27'; // user define secret key
        $key = hash('sha256', $secret_key);
        $iv = substr(hash('sha256', $secret_iv), 0, 16); // sha256 is hash_hmac_algo
        if ($action == 'encrypt') {
            $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
            $output = base64_encode($output);
            $output .= "#" . Str::random(64);
        } else if ($action == 'decrypt') {
            $string = explode('#', $string);
            $output = openssl_decrypt(base64_decode($string[0]), $encrypt_method, $key, 0, $iv);
        }
        return $output;
    }
}
