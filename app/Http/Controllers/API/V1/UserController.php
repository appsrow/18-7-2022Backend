<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\User;
use App\Company;
use App\State;
use App\UserCoins;
use App\Country;
use App\UserCoinsBalances;
use App\Campaign;
use Illuminate\Support\Facades\Auth;
use App\Helpers\GeneralHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\BrandWallet;
use App\BrandWalletBalance;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Lang;
use App\Log;
use App\ReferralStreamersName;
use App\ReferralData;
use App\AuthCode;
use DateTime;
use Exception;

class UserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | User Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles all user related functionalites
    */
    /**
     * @Add - This API is used to create a new User Account & send confirmation email.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function Add(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $validator =  Validator::make($requestData, [
                'first_name' => 'required',
                'last_name' => 'required',
                'gender' => 'required',
                'dob' => 'required',
                'phone' => 'nullable',
                'is_phone_confirmed' => 'required',
                'country_dialing_code' => 'required_if:phone,!=,null',
                'city' => 'required',
                'state' => 'nullable',
                'country' => 'required',
                'email' => 'required|email',
                'password' => 'min:6|required_with:confirm_password|same:confirm_password',
                'confirm_password' => 'min:6',
                'referral_streamer_name' => 'nullable'
            ]);
            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }
            $hasher = app()->make('hash');
            $dob = $phone = "";

            $password = $hasher->make($requestData['password']);
            $first_name = $requestData['first_name'];
            $last_name = $requestData['last_name'];
            if (!empty($requestData['gender'])) {
                $gender = $requestData['gender'];
            }
            $city = $requestData['city'];
            if (!empty($requestData['state'])) {
                $state = $requestData['state'];
            }
            $country = $requestData['country'];
            if (!empty($requestData['dob'])) {
                $dob = $requestData['dob'];
            }
            $is_phone_confirmed = $requestData['is_phone_confirmed'];
            if (!empty($requestData['phone'])) {
                $phone = $requestData['phone'];
            }
            if (!empty($requestData['country_dialing_code'])) {
                $country_dialing_code = $requestData['country_dialing_code'];
            }
            $referral_streamer_name = '';
            if (!empty($requestData['referral_streamer_name'])) {
                $referral_streamer_name = $requestData['referral_streamer_name'];
            }
            $email = $requestData['email'];
            $user_type = '2'; // Normal user role
            $confirmation_code = GeneralHelper::RandomNumber(12);
            $confirmation_code_expired = date("Y/m/d H:i:s", strtotime("+1 day")); // 1 day expired from now
            $email_duplicate = User::where('email', $email)->withTrashed()->first();
            if (!empty($phone)) {
                $phone_duplicate = User::where('phone', $phone)->where('country_dialing_code', $country_dialing_code)->withTrashed()->first();
                if (!empty($phone_duplicate)) {
                    return $this->sendError(Lang::get("user.phone_exist_already", array(), $this->selected_language), null, 201);
                }
            }
            if (!empty($email_duplicate)) {
                return $this->sendError(Lang::get("user.email_exist_already", array(), $this->selected_language), null, 201);
            }
            $register = new User;
            $register->password = $password;
            $register->first_name = $first_name;
            $register->last_name = $last_name;
            if (!empty($gender)) {
                $register->gender = $gender;
            }
            $register->city = $city;
            if (!empty($state)) {
                $register->state = $state;
            }
            $register->country = $country;
            if (!empty($dob)) {
                $register->dob = date("Y-m-d", strtotime($dob));
            }
            if (!empty($phone)) {
                $register->phone = $phone;
            }
            if (!empty($country_dialing_code)) {
                $register->country_dialing_code = $country_dialing_code;
            }
            $register->is_phone_confirmed = $is_phone_confirmed;
            $register->email = $email;
            $register->user_type = $user_type;
            $register->confirmation_code = $confirmation_code;
            $register->confirmation_code_expired = $confirmation_code_expired;

            $data = [
                'confirmation_code' => $confirmation_code,
                'first_name' => $first_name,
                'last_name' => $last_name
            ];
            $to = $email;
            $emails =  env("MAIL_FROM_ADDRESS", null);
            $from_name =  env("MAIL_FROM_NAME", null);
            $from = $emails;

            // $subject = ($templates_lang === "es") ? "Dropforcoin-Enlace de activación de usuario" : "Dropforcoin-User Activation link";
            $subject = Lang::get("user.user_activation_link", array(), $this->selected_language);
            Mail::send($this->selected_language . '.auth.emails.confirmationcode', $data, function ($msg) use ($to, $from, $from_name, $subject) {
                $msg->to($to)->from($from, $from_name)->subject($subject);
            });
            $register->save();
            if ($register) {
                $UserCoins = new UserCoins;
                $UserCoins->user_id = $register->id;
                $UserCoinsBalances = new UserCoinsBalances;
                $UserCoinsBalances->user_id = $register->id;
                $UserCoinsBalances->save();
                $UserCoins->save();
                // If referral_streamer_name found, save the referral data into referral table
                if (!empty($referral_streamer_name)) {
                    // check if the referral name is from a valid link
                    $check_referral_streamer = ReferralStreamersName::where('streamer_name', $referral_streamer_name)->first();
                    if (!empty($check_referral_streamer)) {
                        $referral_detail = new ReferralData;
                        $referral_detail->user_id = $register->id;
                        $referral_detail->user_type = 2;
                        $referral_detail->referral_id = $referral_streamer_name;
                        $referral_detail->save();
                    }
                }
                $User = User::find($register->id);
                if (!empty($User->user_photo)) {
                    $Original = URL::to('/') . '/' . 'uploads/user_files/' . $User->user_photo;
                    $user_photo['original'] = $Original;
                    $User->photo = $user_photo;
                }
                try {
                    $Log = new Log;
                    $Log->user_id = $register->id;
                    $Log->action = "userSignup";
                    $Log->save();
                } catch (Exception $e) {
                    echo "Pending to create logs. WARN: Action logging has failed.";
                }

                return $this->sendResponse($User, Lang::get("common.success", array(), $this->selected_language), 200);
            } else {
                return $this->sendError(Lang::get("common.failed", array(), $this->selected_language), json_decode("{}"), 201);
            }
        }
    }

    /**
     * @add_brand - This API is used to create a new Brand(Company) Account & send confirmation email.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add_brand(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $validator =  Validator::make($requestData, [
                'company_name' => 'required',
                'phone' => 'required',
                'email' => 'required|email',
                'password' => 'min:6|required_with:confirm_password|same:confirm_password',
                'confirm_password' => 'min:6'
            ]);

            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }
            $hasher = app()->make('hash');
            $password = $hasher->make($requestData['password']);
            $company_name = $requestData['company_name'];
            $company_phone = $requestData['phone'];
            $company_email = $requestData['email'];
            $user_type = '1'; // Brand user role

            $confirmation_code = GeneralHelper::RandomNumber(12);
            $confirmation_code_expired = date("Y/m/d H:i:s", strtotime("+1 day")); // 30 day expired from now
            $email_duplicate = User::where('email', $company_email)->withTrashed()->first();
            if (!empty($email_duplicate)) {
                return $this->sendError(Lang::get("user.email_exist_already", array(), $this->selected_language), null, 201);
            }
            $register = new User;
            $register->password = $password;
            $register->email = $company_email;
            $register->user_type = $user_type;
            $register->confirmation_code = $confirmation_code;
            $register->confirmation_code_expired = $confirmation_code_expired;
            $data = ['confirmation_code' => $confirmation_code, 'company_name' => $company_name];
            $to = $company_email;
            $emails =  env("MAIL_FROM_ADDRESS", null);
            $from_name =  env("MAIL_FROM_NAME", null);
            $from = $emails;

            $subject = Lang::get("user.user_activation_link", array(), $this->selected_language);
            Mail::send($this->selected_language . '.auth.emails.confirmationcode', $data, function ($msg) use ($to, $from, $from_name, $subject) {
                $msg->to($to)->from($from, $from_name)->subject($subject);
            });
            $register->save();
            if (!empty($register)) {
                $Company = new Company;
                $Company->user_id = $register->id;
                $Company->company_name = $company_name;
                $Company->phone = $company_phone;
                $Company->save();
                if (!empty($Company)) {
                    $BrandWallet = new BrandWallet;
                    $BrandWallet->user_id = $register->id;
                    $BrandWalletBalance = new BrandWalletBalance;
                    $BrandWalletBalance->user_id = $register->id;
                    $BrandWalletBalance->save();
                    $BrandWallet->save();
                    $User = User::find($register->id);
                    if (!empty($User->user_photo)) {
                        $Original = URL::to('/') . '/' . 'uploads/user_files/' . $User->user_photo;
                        $user_photo['original'] = $Original;
                        $User->photo = $user_photo;
                    }
                    $User['company_info'] = $Company;
                    try {
                        $Log = new Log;
                        $Log->user_id = $register->id;
                        $Log->action = "brandSignup";
                        $Log->save();
                    } catch (Exception $e) {
                        echo "Pending to create logs. WARN: Action logging has failed.";
                    }

                    return $this->sendResponse($User, Lang::get("common.success", array(), $this->selected_language), 200);
                } else {
                    return $this->sendError(Lang::get("user.brand_register_failed", array(), $this->selected_language), json_decode("{}"), 201);
                }
            } else {
                return $this->sendError(Lang::get("common.failed", array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), null, 400);
        }
    }

    /**
     * @getByLoggedUser - This API is used for retrieve data of logged in user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByLoggedUser()
    {
        $id = Auth::id();
        $ids = User::where('user_type', 2)->where('id', $id)->first();
        if (!empty($ids)) {
            $User = User::select('id', 'first_name', 'last_name', 'user_photo', 'dob', 'email', 'gender', 'country', 'city', 'state', 'country', 'phone', 'country_dialing_code', 'is_phone_confirmed')->where('id', $id)->first();
            if ($User) {
                if (!empty($User->country)) {
                    $country = Country::where('id', $User->country)->first();
                    $country_name = $country->country_name;
                    $data['country_name'] = $country_name;
                }
                if (!empty($User->state)) {
                    $state = State::where('id', $User->state)->first();
                    $state_name = $state->state_name;
                    $data['state_name'] = $state_name;
                }
                if (!empty($User->user_photo)) {
                    $Original = URL::to('/') . '/' . 'uploads/user_files/' . $User->user_photo;
                    $data['user_photo'] = $Original;
                }
                $data['user_info'] = $User;
                return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language));
            } else {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 403);
        }
    }

    /**
     * @getByLoggedBrand - This API is used for retrieve data of logged in brand(company).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByLoggedBrand()
    {
        $id = Auth::id();
        $ids = User::where('user_type', 1)->where('id', $id)->first();
        if (!empty($ids)) {
            $User = User::select('id', 'user_photo', 'email')->where('id', $id)->first();
            if ($User) {
                $Company = Company::select('id', 'company_name', 'phone')->where('user_id', $User->id)->first();
                if (!empty($User->user_photo)) {
                    $Original = URL::to('/') . '/' . 'uploads/user_files/' . $User->user_photo;
                    $data['user_photo'] = $Original;
                }
                $data['company_info'] = $Company;
                $data['user_info'] = $User;
                return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
            } else {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 403);
        }
    }

    /**
     * @ChangePassword - This API is used for change password of logged in user.
     *
     * @return \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ChangePassword(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "ChangePassword";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }
        $id = Auth::id();
        $ids = User::where('user_type', '!=', 3)->where('id', $id)->first();
        if (!empty($ids)) {
            $user = User::find($id);
            $requestData = $request->json()->all();
            if (count($requestData) > 0) {
                $validator = Validator::make($requestData, [
                    'old_password' => 'required',
                    'new_password' => 'required',
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                if (!empty($user)) {
                    $social_email = User::whereId($id)->where('email', $user->email)->where('is_social_sign_in', "1")->first();
                    if (!empty($social_email)) {
                        return $this->sendError(Lang::get("auth.not_allow_login_social_account", array(), $this->selected_language), null, 201);
                    }
                    $old_password = $requestData['old_password'];
                    $new_password = $requestData['new_password'];
                    if ($user) {
                        $hasher = app()->make('hash');
                        $password = $hasher->make($new_password);
                        if ($hasher->check($old_password, $user->password)) {
                            $user->password = $password;
                            $user->save();
                            return $this->sendResponse(null, Lang::get("user.password_changed", array(), $this->selected_language), 200);
                        } else {
                            return $this->sendError(Lang::get("user.old_password_incorrect", array(), $this->selected_language), null, 201);
                        }
                    } else {
                        return $this->sendError(Lang::get("auth.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
                    }
                } else {
                    return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 403);
                }
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 401);
        }
    }

    /**
     * @UserWalletBalalnce - This API is used for get user wallet balance.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function UserWalletBalalnce()
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "UserWalletBalalnce";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }
        $user_id = Auth::id();
        $ids = User::where('user_type', 2)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $wallet_balance['wallet_balance'] = UserCoinsBalances::getUserBalance($user_id);
            return $this->sendResponse($wallet_balance, Lang::get("common.success", array(), $this->selected_language), 200);
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 403);
        }
    }

    /**
     * @BrandCampaignSpend - This API is used for get list of campaigns of the logged in brand(company)
     *                       with the current balance of the brand.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function BrandCampaignSpend()
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "BrandCampaignSpend";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }
        $user_id = Auth::id();
        $ids = User::where('user_type', 1)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $user = User::find($user_id);
            if (!empty($user)) {
                $company = Company::where('user_id', $user->id)->first();
                $campaigns = Campaign::select('id', 'company_id', 'campaign_name', 'campaign_type', 'campaign_type_name', 'sub_total', 'campaign_image')->where('end_date', '<', date('Y-m-d'))->where('company_id', $company->id)->get();
                $campaign_count = count($campaigns);
                if ($campaign_count) {
                    $data = [];
                    $wallet_balance = 0;
                    $percentage = 0;
                    foreach ($campaigns as $campaign) {
                        if (!empty($campaign->campaign_image)) {
                            $Original = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->campaign_image;
                            $campaign->campaign_image = $Original;
                        }
                        $campaign_debit_count = BrandWallet::where('campaign_id', $campaign->id)->sum('debit');
                        $new = [];

                        // calculation starts
                        $new['spend_euro'] = $campaign_debit_count;
                        $left_euro = $campaign->sub_total - $campaign_debit_count;

                        $wallet_balance += $left_euro;
                        if (!empty($campaign_debit_count) and !empty($campaign->sub_total)) {
                            $percentage = ($campaign_debit_count * 100) / $campaign->sub_total;
                            $percentage = round($percentage, 2);
                            if ($percentage >= 100) {
                                $percentage = 100;
                            }
                        }
                        // calculation Ends
                        $new['percentage'] = $percentage;
                        $new['left_euro'] = $left_euro;
                        $campaign->campaign_details = $new;
                    }
                    $data['campaigns'] = $campaigns;
                    $data['my_balance'] = $wallet_balance;
                    return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
                } else {
                    return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), null, 201);
                }
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 403);
        }
    }

    public function oldSendVerificationCode(Request $request)
    {
        $return = array();

        try {
            $requestData = $request->json()->all();

            $validator =  Validator::make($requestData, [
                'country_dialing_code' => 'required',
                'phone_number' => 'required',
            ]);
            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            } else {
                $phone = $requestData['phone_number'];
                $country_dialing_code =  explode(" ", $requestData['country_dialing_code']);
                $country_dialing_code = $country_dialing_code[0];
                if (!empty($phone)) {
                    // check if auth id is there
                    $user_id = Auth::id();
                    if (!empty($user_id)) {
                        $phone_duplicate = User::where('phone', $phone)->where('country_dialing_code', $country_dialing_code)->where('id', "!=", $user_id)->withTrashed()->first();
                        if (!empty($phone_duplicate)) {
                            return $this->sendError(Lang::get("user.phone_exist_already", array(), $this->selected_language), null, 201);
                        }
                    } else {
                        $phone_duplicate = User::where('phone', $phone)->where('country_dialing_code', $country_dialing_code)->withTrashed()->first();
                        if (!empty($phone_duplicate)) {
                            return $this->sendError(Lang::get("user.phone_exist_already", array(), $this->selected_language), null, 201);
                        }
                    }
                }
                // check the twilio verification
                $twilio_api_url = env("TWILIO_API_URL");
                $twilio_account_id = env("TWILIO_ACCOUNT_SID", null);
                $twilio_auth_token = env("TWILIO_AUTH_TOKEN", null);
                $twitch_service_id = env("TWITCH_SERVICE_ID", null);


                if (empty($twilio_account_id) || empty($twilio_auth_token) || empty($twilio_api_url)) {
                    return $return;
                }

                // verify url
                $twilio_api_url .= $twitch_service_id . "/Verifications";
                $basicAuthToken = base64_encode($twilio_account_id . ":" . $twilio_auth_token);

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $twilio_api_url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => http_build_query(['To' => $country_dialing_code . $requestData['phone_number'], 'Channel' => 'sms']),
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Basic ' . $basicAuthToken,
                        'Content-Type: application/x-www-form-urlencoded'
                    )
                ));

                $response = curl_exec($curl);
                curl_close($curl);
                $decoded = json_decode($response);
                $status = $decoded->status;

                if ($status === 'pending') {
                    return $this->sendResponse(json_decode($response), Lang::get("common.success", array(), $this->selected_language), 200);
                }
                if ($status === 429) {
                    return $this->sendError(Lang::get("common.too_many_requests", array(), $this->selected_language), null, 201);
                }
                if ($status === 400) {
                    return $this->sendError(Lang::get("common.check_phone_number_or_country_code", array(), $this->selected_language), null, 201);
                } elseif ($status) {
                    return $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 201);
                }
            }
        } catch (Exception $e) {
            $return = array();
        }
        return $return;
    }

    public function sendVerificationCode(Request $request)
    {
        $return = array();

        try {
            $requestData = $request->json()->all();

            $validator =  Validator::make($requestData, [
                'country_dialing_code' => 'required',
                'phone_number' => 'required',
            ]);
            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            } else {
                $phone = $requestData['phone_number'];
                $country_dialing_code =  explode(" ", $requestData['country_dialing_code']);
                $country_dialing_code = str_replace('+', '', $country_dialing_code[0]);

                if (!empty($phone) && !empty($country_dialing_code)) {
                    // check if auth id is there
                    $user_id = Auth::id();
                    if (!empty($user_id)) {
                        $phone_duplicate = User::where('phone', $phone)->where('country_dialing_code', $country_dialing_code)->where('id', "!=", $user_id)->withTrashed()->first();
                        if (!empty($phone_duplicate)) {
                            return $this->sendError(Lang::get("user.phone_exist_already", array(), $this->selected_language), null, 201);
                        }
                    } else {
                        $phone_duplicate = User::where('phone', $phone)->where('country_dialing_code', $country_dialing_code)->withTrashed()->first();
                        if (!empty($phone_duplicate)) {
                            return $this->sendError(Lang::get("user.phone_exist_already", array(), $this->selected_language), null, 201);
                        }
                    }

                    $sms_token = env("SMS_360NRS_TOKEN");
                    if (empty($sms_token)) {
                        return $this->sendError(Lang::get("common.unauthorized", array(), $this->selected_language), null, 201);;
                    }

                    $auth_code_number = rand(100000, 999999);
                    $auth_code = new AuthCode;
                    $auth_code->user_id = $user_id;
                    $auth_code->country_dialing_code = $country_dialing_code;
                    $auth_code->phone_number = $phone;
                    $auth_code->auth_code = $auth_code_number;
                    $auth_code->save();

                    if ($auth_code) {
                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => 'https://dashboard.360nrs.com/api/rest/sms',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS => '{
                        "to": [' . $country_dialing_code . $requestData['phone_number'] . '],
                        "from": "Dropforcoin",
                        "message": "[Dropforcoin] Verification code: ' . $auth_code_number . '"
                        }',
                            CURLOPT_HTTPHEADER => array(
                                'Content-Type: application/json',
                                'Authorization: Basic ' . $sms_token
                            ),
                        ));

                        $response = curl_exec($curl);
                        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                        curl_close($curl);
                        error_log($response);
                        $decoded = json_decode($response);

                        if ($status === 202) {
                            return $this->sendResponse(json_decode($response), Lang::get("common.success", array(), $this->selected_language), 200);
                        }
                        if ($status === 402) {
                            return $this->sendError(Lang::get("common.not_enough_credits", array(), $this->selected_language), null, 201);
                        }
                        if ($status === 400) {
                            return $this->sendError(Lang::get("common.check_phone_number_or_country_code", array(), $this->selected_language), null, 201);
                        } elseif ($status) {
                            return $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 201);
                        }
                    } else {
                        return $this->sendError(Lang::get("common.failed", array(), $this->selected_language), null, 201);
                    }
                } else {
                    return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), null, 201);
                }
            }
        } catch (Exception $e) {
            $return = $e;
        }
        return $return;
    }

    public function oldVerifySmsCode(Request $request)
    {
        $return = array();

        try {
            $requestData = $request->json()->all();

            $validator =  Validator::make($requestData, [
                'phone_number' => 'required',
                'code' => 'required',
                'country_dialing_code' => 'required'
            ]);
            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            } else {
                // check the twilio verification
                $twilio_api_url = env("TWILIO_API_URL");
                $twilio_account_id = env("TWILIO_ACCOUNT_SID", null);
                $twilio_auth_token = env("TWILIO_AUTH_TOKEN", null);
                $twitch_service_id = env("TWITCH_SERVICE_ID", null);
                $country_dialing_code =  explode(" ", $requestData['country_dialing_code']);
                $country_dialing_code = $country_dialing_code[0];

                if (empty($twilio_account_id) || empty($twilio_auth_token) || empty($twilio_api_url)) {
                    return $return;
                }

                // verify url
                $twilio_api_url .= $twitch_service_id . "/VerificationCheck";
                $basicAuthToken = base64_encode($twilio_account_id . ":" . $twilio_auth_token);

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $twilio_api_url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => http_build_query(['To' => $country_dialing_code . $requestData['phone_number'], 'Code' => $requestData['code']]),
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Basic ' . $basicAuthToken,
                        'Content-Type: application/x-www-form-urlencoded'
                    )
                ));

                $response = curl_exec($curl);
                curl_close($curl);

                $decoded = json_decode($response);
                $status = $decoded->status;

                $user_id = Auth::id();
                if ($status === 'approved') {
                    if ($user_id) {
                        User::where('id', $user_id)->update(['country_dialing_code' => $country_dialing_code, 'phone' =>  $requestData['phone_number'], 'is_phone_confirmed' => 1]);
                    }
                    return $this->sendResponse(json_decode($response), Lang::get("common.success", array(), $this->selected_language), 200);
                }
                if ($status === 404) {
                    return $this->sendError(Lang::get("common.verification_check_not_found", array(), $this->selected_language), null, 201);
                }
                if ($status === 'pending') {
                    return $this->sendError(Lang::get("common.verification_code_not_valid", array(), $this->selected_language), null, 201);
                } elseif ($status) {
                    return $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 201);
                }
            }
        } catch (Exception $e) {
            $return = array();
        }
        return $return;
    }
    public function verifySmsCode(Request $request)
    {
        $return = array();

        try {
            $requestData = $request->json()->all();

            $validator =  Validator::make($requestData, [
                'phone_number' => 'required',
                'code' => 'required',
                'country_dialing_code' => 'required'
            ]);
            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            } else {
                $country_dialing_code =  explode(" ", $requestData['country_dialing_code']);
                $country_dialing_code = $country_dialing_code[0];

                $user_id = Auth::id();
                if (!empty($user_id)) {
                    $date = new DateTime;
                    $date->modify('-10 minutes');
                    $formatted_date = $date->format('Y-m-d H:i:s');
                    $auth_code = AuthCode::where('user_id', $user_id)->where('created_at', '<=', $formatted_date)->orderBy('created_at', 'DESC')->first();
                    $code = $requestData['code'];

                    if (!empty($auth_code)) {
                        if ($auth_code->auth_code == $code) {
                            User::where('id', $user_id)->update(['country_dialing_code' => $country_dialing_code, 'phone' =>  $requestData['phone_number'], 'is_phone_confirmed' => 1]);
                            return $this->sendResponse(json_decode("{}"), Lang::get("common.success", array(), $this->selected_language), 200);
                        }
                        return $this->sendError(Lang::get("common.verification_code_not_valid", array(), $this->selected_language), null, 201);
                    }
                    return $this->sendError(Lang::get("common.verification_check_not_found", array(), $this->selected_language), null, 201);
                }
                return $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 201);
            }
        } catch (Exception $e) {
            $return = array();
        }
        return $return;
    }
}
