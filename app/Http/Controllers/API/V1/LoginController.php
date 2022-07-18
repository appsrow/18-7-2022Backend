<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use App\Helpers\GeneralHelper;
use App\User;
use App\Company;
use App\PasswordReset;
use App\UserCoins;
use App\UserCoinsBalances;
use Carbon\Carbon;
use App\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Lang;
use App\ReferralData;
use App\ReferralStreamersName;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application.
    */

    /**
     * @index - The Login API is used authenticate a user.
     * 
     * NOTE: The Authorization bearer token must be used in all subsequent API calls after authentication. 
     *       Tokens expire periodically and will result in a 401 response. 
     *       Tokens must be refreshed at this point via a new “Login”.
     * 
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            if (!empty($requestData['email']) && !empty($requestData['password'])) {
                $email = $requestData['email'];
                $password = $requestData['password'];
            } else {
                return $this->sendError(Lang::get('auth.require_email_password', array(), $this->selected_language), json_decode("{}"), 400);
            }
            $hasher = app()->make('hash');
            $login = User::where('email', $email)->where('user_type', '2')->withTrashed()->first();
            if (!$login) {
                //email not registered
                return $this->sendError(Lang::get('auth.email_not_registered', array(), $this->selected_language), json_decode("{}"), 201);
            } else {
                //check if register with social account
                if ($login->is_social_sign_in == 1) {
                    return $this->sendError(Lang::get('auth.signin_account_is_social_account', array(), $this->selected_language), json_decode("{}"), 201);
                }

                if ($login) {
                    if ($login->active == 0 or !empty($login->deleted_at)) {
                        return $this->sendError(Lang::get('auth.account_deactivated', array(), $this->selected_language), json_decode("{}"), 201);
                    }
                    if ($login->confirmed == 0) {
                        return $this->sendError(Lang::get('auth.account_not_confirmed', array(), $this->selected_language), json_decode("{}"), 201);
                    }
                }
                if ($hasher->check($password, $login->password)) {
                    if (!$create_token = Auth::attempt($requestData)) {
                        return $this->sendError(Lang::get('common.unauthorized', array(), $this->selected_language), json_decode("{}"), 401);
                        // return response()->json(['message' => Lang::get('common.unauthorized', array(), $this->selected_language)], 401);
                    }
                    // Update user device token
                    User::where('id', $login->id)->update(['api_token' => $create_token]);
                    if ($create_token) {
                        $User = $login;
                        if (!empty($User->user_photo) && !is_null($User->user_photo)) {
                            $Original = URL::to('/') . '/' . 'uploads/user_files/' . $User->user_photo;
                        }
                        $user_photo['original'] = isset($Original) ? $Original : '';
                        $User->photo = $user_photo;
                        unset($User->user_photo);
                        $tokens = $this->respondWithToken($create_token);
                        $data['api_token'] = $tokens['api_token'];
                        $data['token_expires_in'] = $tokens['token_expires_in'];
                        $data['user_info'] = $User;

                        try {
                            $Log = new Log;
                            $Log->user_id = $login->id;
                            $Log->action = "login";
                            $Log->save();
                        } catch (Exception $e) {
                            echo "Pending to create logs. WARN: Action logging has failed.";
                        }
                        return $this->sendResponse($data, Lang::get('common.success', array(), $this->selected_language), 200);
                    }
                } else {
                    return $this->sendError(Lang::get('auth.invalid_login', array(), $this->selected_language), json_decode("{}"), 201);
                }
            }
        } else {
            return $this->sendError(Lang::get('common.request_empty', array(), $this->selected_language), json_decode("{}"), 201);
        }
    }

    /**
     *  @User_Google_Social_login - This API is used for Social Login of Google.
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function User_Google_Social_login(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $validator =  Validator::make($requestData, [
                'is_social_sign_in' => 'required',
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required|email',
                'authToken' => 'required',
                'referral_streamer_name' => 'nullable'
            ]);

            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }

            if ($requestData['is_social_sign_in'] == 1) {
                $email = $requestData['email'];
                $is_social_sign_in = $requestData['is_social_sign_in'];
                $user_type = '2';
                $email_duplicate = User::where('email', $email)->where('is_social_sign_in', $is_social_sign_in)->first();
                if (!empty($email_duplicate)) {
                    $user_old = User::where('email', '=', $email)->where('active', 1)->first();
                    if (!empty($user_old)) {
                        $token = JWTAuth::fromUser($user_old);
                        // Update device token
                        User::where('id', $user_old->id)->update(['api_token' => $token]);
                        $data['api_token'] = $token;
                        $data['user_info'] = $user_old;
                        if (!empty($data)) {

                            try {
                                $Log = new Log;
                                $Log->user_id = $user_old->id;
                                $Log->action = "login-google";
                                $Log->save();
                            } catch (Exception $e) {
                                echo "Pending to create logs. WARN: Action logging has failed.";
                            }


                            return $this->sendResponse($data, Lang::get('common.success', array(), $this->selected_language));
                        }
                    } else {
                        return $this->sendError(Lang::get('auth.account_deactivated', array(), $this->selected_language), null, 201);
                    }
                } else {
                    $email_duplicates = User::where('email', $email)->withTrashed()->first();
                    if (!empty($email_duplicates)) {
                        return $this->sendError(Lang::get('auth.social_email_exist', array(), $this->selected_language), null, 201);
                    }
                    $register = new User;
                    $register->first_name = $requestData['first_name'];
                    $register->last_name = $requestData['last_name'];
                    $register->email = $requestData['email'];
                    $register->user_type = $user_type;
                    $register->is_social_sign_in = $requestData['is_social_sign_in'];
                    $register->confirmed = "1";
                    $register->save();
                    if ($register) {
                        $UserCoins = new UserCoins;
                        $UserCoins->user_id = $register->id;
                        $UserCoinsBalances = new UserCoinsBalances;
                        $UserCoinsBalances->user_id = $register->id;
                        $UserCoinsBalances->save();
                        $UserCoins->save();
                        // Referral streamer name
                        $referral_streamer_name = '';
                        if (!empty($requestData['referral_streamer_name'])) {
                            $referral_streamer_name = $requestData['referral_streamer_name'];
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
                        $user_new = User::where('email', '=', $register->email)->first();
                        if ($user_new) {
                            $token = JWTAuth::fromUser($user_new);
                            // Update device token
                            User::where('id', $user_new->id)->update(['api_token' => $token]);
                            $data['api_token'] = $token;
                            $data['user_info'] = $user_new;

                            try {
                                $Log = new Log;
                                $Log->user_id = $user_new->id;
                                $Log->action = "sign_up_google";
                                $Log->save();
                            } catch (Exception $e) {
                                echo "Pending to create logs. WARN: Action logging has failed.";
                            }

                            try {
                                $Log = new Log;
                                $Log->user_id = $user_new->id;
                                $Log->action = "login-google";
                                $Log->save();
                            } catch (Exception $e) {
                                echo "Pending to create logs. WARN: Action logging has failed.";
                            }
                        }
                        return $this->sendResponse($data, Lang::get('common.success', array(), $this->selected_language), 200);
                    }
                }
            } else {
                return $this->sendError(Lang::get('common.request_invalid', array(), $this->selected_language), json_decode("{}"), 400);
            }
        }
    }

    /**
     * @login_brand - The Login API is used authenticate a brand.
     * 
     * NOTE: The Authorization bearer token must be used in all subsequent API calls after authentication. 
     *       Tokens expire periodically and will result in a 401 response. 
     *       Tokens must be refreshed at this point via a new “Login”.
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login_brand(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            if (!empty($requestData['email']) && !empty($requestData['password'])) {
                $email = $requestData['email'];
                $password = $requestData['password'];
            } else {
                return $this->sendError(Lang::get('auth.require_email_password', array(), $this->selected_language), json_decode("{}"), 400);
            }
            $hasher = app()->make('hash');
            $login = User::where('email', $email)->where('user_type', '1')->withTrashed()->first();



            if (!$login) {
                //email not registered
                return $this->sendError(Lang::get('auth.email_not_registered', array(), $this->selected_language), json_decode("{}"), 201);
            } else {
                if ($login) {
                    if ($login->active == 0 or !empty($login->deleted_at)) {
                        return $this->sendError(Lang::get('auth.account_deactivated', array(), $this->selected_language), json_decode("{}"), 201);
                    }
                    if ($login->confirmed == 0) {
                        return $this->sendError(Lang::get('auth.account_not_confirmed', array(), $this->selected_language), json_decode("{}"), 201);
                    }
                }
                if ($hasher->check($password, $login->password)) {
                    if (!$create_token = Auth::attempt($requestData)) {
                        return $this->sendError(Lang::get('common.unauthorized', array(), $this->selected_language), json_decode("{}"), 401);
                    }
                    // Update device token
                    User::where('id', $login->id)->update(['api_token' => $create_token]);

                    if ($create_token) {
                        $User = $login;
                        $Original = "";
                        if (!empty($User->user_photo) && !is_null($User->user_photo)) {
                            $Original = URL::to('/') . '/' . 'uploads/user_files/' . $User->user_photo;
                        }
                        $user_photo['original'] = $Original;
                        $User->photo = $user_photo;
                        unset($User->user_photo);
                        $Company = Company::where('user_id', $login->id)->first();
                        $tokens = $this->respondWithToken($create_token);
                        $data['api_token'] = $tokens['api_token'];
                        $data['token_expires_in'] = $tokens['token_expires_in'];
                        $data['user_info'] = $User;
                        $data['company_info'] = $Company;

                        try {
                            $Log = new Log;
                            $Log->user_id = $login->id;
                            $Log->action = "login_brand";
                            $Log->save();
                        } catch (Exception $e) {
                            echo "Pending to create logs. WARN: Action logging has failed.";
                        }


                        return $this->sendResponse($data, Lang::get('common.success', array(), $this->selected_language));
                    }
                } else {
                    return $this->sendError(Lang::get('auth.invalid_login', array(), $this->selected_language), json_decode("{}"), 201);
                }
            }
        }
    }

    /**
     * @logout - The API is used for logout of user.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "logout";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        if (!empty($user_id)) {
            $remove_token = User::where('id', $user_id)->update(['api_token' => '']);
            Auth::logout();
            Auth::invalidate();
            if ($remove_token) {
                return $this->sendResponse(json_decode("{}"), Lang::get('common.success', array(), $this->selected_language));
            } else {
                return $this->sendError(Lang::get('common.failed', array(), $this->selected_language), null, 201);
            }
        } else {
            return $this->sendError(Lang::get('auth.unauthorized_user', array(), $this->selected_language), null, 401);
        }
    }

    /**
     * @refresh - refresh access token
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        $data = $this->respondWithToken(Auth::refresh());
        return $this->sendResponse($data, Lang::get('common.success', array(), $this->selected_language));
    }

    /**
     * @email_confirm - This API used for confirm email and manage redirections.
     * 
     * @param  {String} $confirm_email 
     * @return \Illuminate\Http\JsonResponse
     */
    public function email_confirm($confirm_email)
    {
        try {
            $Log = new Log;
            $Log->user_id = -2;
            $Log->action = "email_confirm";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }
        $User = User::where('confirmation_code', $confirm_email)->where('confirmed', 0)->first();
        if ($User) {
            if ($User->user_type == "1") {
                if ($User->confirmation_code_expired >= date("Y-m-d H:i:s")) {
                    $user_update = User::find($User->id);
                    $user_update->confirmed = 1;
                    $user_update->save();
                    if (!empty($user_update)) {
                        $redirect =  env("BRAND_EMAIL_CONFIRMATION_REDIRECTION_URL", null);
                        header('Location: ' . $redirect);
                        die();
                    }
                } else {
                    $redirect =  env("BRAND_EMAIL_CONFIRMATION_REDIRECTION_URL", null);
                    header('Location: ' . $redirect . '?expired=true');
                    die();
                }
            } else if ($User->user_type == "2") {
                if ($User->confirmation_code_expired >= date("Y-m-d H:i:s")) {
                    $user_update = User::find($User->id);
                    $user_update->confirmed = 1;
                    $user_update->save();
                    if (!empty($user_update)) {
                        $redirect =  env("USER_EMAIL_CONFIRMATION_REDIRECTION_URL", null);
                        header('Location: ' . $redirect);
                        die();
                    }
                } else {
                    $redirect =  env("USER_EMAIL_CONFIRMATION_REDIRECTION_URL", null);
                    header('Location: ' . $redirect . '?expired=true');
                    die();
                }
            }
        } else {
            $redirect = env("EMAIL_EXPIRE_URL", null);
            header('Location: ' . $redirect);
            die();
        }
    }

    /**
     * @resend_email_confirm - This API used for resend confirmation email.
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resend_email_confirm(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $validator = Validator::make($requestData, [
                'email' => 'required|email'
            ]);

            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }
            $email = $requestData['email'];
            $check_email = User::where('email', $email)->first();
            if ($check_email) {
                $first_name = $last_name = $company_name = "";
                $user_update = User::where('email', $email)->where('confirmed', 0)->first();
                if (!empty($check_email->first_name)) {
                    $first_name = $check_email->first_name;
                    $last_name = $check_email->last_name;
                } else {
                    $company = Company::where('user_id', $check_email->id)->first();
                    $company_name = $company->company_name;
                }
                if (!empty($user_update)) {
                    $confirmation_code = GeneralHelper::RandomNumber(12);
                    $confirmation_code_expired = date("Y/m/d H:i:s", strtotime("+1 day")); //1 day expired from now
                    $user_update->confirmation_code = $confirmation_code;
                    $user_update->confirmation_code_expired = $confirmation_code_expired;

                    $data = [
                        'confirmation_code' => $confirmation_code,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'company_name' => $company_name
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
                    $user_update->save();
                    if ($user_update) {
                        return $this->sendResponse(json_decode("{}"), Lang::get('auth.confirmation_link_sent', array(), $this->selected_language), 200);
                    }
                } else {
                    return $this->sendResponse(json_decode("{}"), Lang::get('auth.account_already_confirmed', array(), $this->selected_language), 200);
                }
            } else {
                return $this->sendError(Lang::get('auth.email_not_found', array(), $this->selected_language), null, 201);
            }
        }
    }

    /**
     * @AdminLogin - The Login API is used authenticate a admin user.
     * 
     * NOTE: The Authorization bearer token must be used in all subsequent API calls after authentication. 
     *       Tokens expire periodically and will result in a 401 response. 
     *       Tokens must be refreshed at this point via a new “Login”.      
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function AdminLogin(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            if (!empty($requestData['email']) && !empty($requestData['password'])) {
                $email = $requestData['email'];
                $password = $requestData['password'];
            } else {
                return $this->sendError(Lang::get("auth.require_email_password", array(), $this->selected_language), json_decode("{}"), 201);
            }
            $hasher = app()->make('hash');
            $login = User::where('email', $email)->where('user_type', '3')->withTrashed()->first();
            if (!$login) {
                return $this->sendError(Lang::get("auth.invalid_login", array(), $this->selected_language), json_decode("{}"), 201);
            } else {
                if ($login) {
                    if (!empty($login->deleted_at)) {
                        return $this->sendError(Lang::get("auth.account_deactivated", array(), $this->selected_language), json_decode("{}"), 201);
                    }
                }
                if ($hasher->check($password, $login->password)) {
                    if (!$create_token = Auth::attempt($requestData)) {
                        return $this->sendError(Lang::get("common.unauthorized", array(), $this->selected_language), json_decode("{}"), 401);
                    }
                    // Update device token
                    User::where('id', $login->id)->update(['api_token' => $create_token]);
                    if ($create_token) {
                        $User = $login;
                        $tokens = $this->respondWithToken($create_token);
                        $data['api_token'] = $tokens['api_token'];
                        $data['token_expires_in'] = $tokens['token_expires_in'];
                        $data['user_info'] = $User;

                        try {
                            $Log = new Log;
                            $Log->user_id = $login->id;
                            $Log->action = "login_admin";
                            $Log->save();
                        } catch (Exception $e) {
                            echo "Pending to create logs. WARN: Action logging has failed.";
                        }

                        return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language));
                    }
                } else {
                    return $this->sendError(Lang::get("auth.invalid_login", array(), $this->selected_language), json_decode("{}"), 201);
                }
            }
        } else {
            return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), json_decode("{}"), 400);
        }
    }

    /**
     * @respondWithToken - Get the token array structure.
     *
     * @param  string $token
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return [
            'api_token' => $token,
            'token_type' => 'bearer',
            'token_expires_in' => Auth::factory()->getTTL() * 60
        ];
    }

    /**
     * @postEmail - This API is used for send email of forgot password.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postEmail(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $validator = Validator::make($requestData, [
                'email' => 'required|email|exists:users'
            ], [
                'email.exists' => Lang::get("auth.email_not_registered", array(), $this->selected_language)
            ]);
            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }
            $email_duplicate = User::where('email', $requestData['email'])->where('is_social_sign_in', "1")->first();
            if (!empty($email_duplicate)) {
                return $this->sendError(Lang::get("auth.not_allow_login_social_account", array(), $this->selected_language), null, 201);
            }
            $token = Str::random(64);
            //if email exist update otherwise insert
            $dup_email = PasswordReset::where('email', $requestData['email'])->first();
            if (!empty($dup_email)) {
                $updated = PasswordReset::where('email', $requestData['email'])->update(['token' => $token]);
            } else {
                $PasswordReset = new PasswordReset;
                $PasswordReset->email = $requestData['email'];
                $PasswordReset->token = $token;
                $PasswordReset->created_at = Carbon::now();
                $PasswordReset->save();
            }
            if (!empty($PasswordReset) or !empty($updated)) {
                $user = User::where('email', $requestData['email'])->first();
                $data[] = "";

                $static_url = URL::to('/') . '/api/v1/users/confirm_reset_token';
                if (!empty($user) and $user->user_type == "2") {
                    $static_url .= "/users/" . $token;
                    $first_name = $user->first_name;
                    $last_name = $user->last_name;
                    $data = ['reset_confirm_url' => $static_url, 'first_name' => $first_name, 'last_name' => $last_name];
                } else if (!empty($user) and $user->user_type == "1") {
                    $static_url .= "/brands/" . $token;
                    $company = Company::where('user_id', $user->id)->first();
                    $company_name = $company->company_name;
                    $data = ['reset_confirm_url' => $static_url, 'company_name' => $company_name];
                }
                $to = $requestData['email'];
                $emails =  env("MAIL_FROM_ADDRESS", null);
                $from_name =  env("MAIL_FROM_NAME", null);
                $from = $emails;
                // $subject = ($templates_lang === "es") ? "Dropforcoin-restablecimiento de contraseña de cuenta" : "Dropforcoin account password reset";
                $subject = Lang::get("user.password_reset_subject", array(), $this->selected_language);
                Mail::send($this->selected_language . '.auth.emails.passwordreset', $data, function ($msg) use ($to, $from, $from_name, $subject) {
                    $msg->to($to)->from($from, $from_name)->subject($subject);
                });
                return $this->sendResponse(null, Lang::get("auth.reset_password_link", array(), $this->selected_language), 200);
            } else {
                return $this->sendError(Lang::get("common.failed", array(), $this->selected_language), null, 201);
            }
        } else {
            return $this->sendError(Lang::get("common.request_empty", array(), $this->selected_language), null, 400);
        }
    }

    /**
     * @postReset - This API is used for store user's new password on reset password after forgot password.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postReset(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $validator = Validator::make($requestData, [
                'password' => 'required|string|min:6|required_with:password_confirmation|same:password_confirmation',
                'password_confirmation' => 'required',
                'token' => 'required'
            ]);
            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }
            $updatePassword = DB::table('password_resets')->where('token', $requestData['token'])->first();
            if (!$updatePassword) {
                return $this->sendError(Lang::get("auth.reset_password_link_expired", array(), $this->selected_language), null, 201);
            } else {
                User::where('email', $updatePassword->email)->update(['password' => Hash::make($requestData['password'])]);
                DB::table('password_resets')->where(['email' => $updatePassword->email])->delete();
                return $this->sendResponse(null, Lang::get("auth.reset_new_password_changed", array(), $this->selected_language), 200);
            }
        } else {
            return $this->sendError(Lang::get("common.request_empty", array(), $this->selected_language), null, 400);
        }
    }

    /**
     * @postReset - This API is used for check reset password link of forgot password.
     *
     * @param  {String} $type 
     * @param  {String} $token 
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkResetLink($type, $token)
    {
        try {
            //code...
            $app_url =  env("APP_URL", null);
            $brand_reset_url = env("RESETPASSWORD_BRAND_PATH", null);
            $user_reset_url = env("RESETPASSWORD_USER_PATH", null);

            /* check required parameters */
            if (empty($token) || empty($type)) {
                header('Location: ' . $app_url);
                die();
            }

            /* redirect link expire page of frontend based on the user type */
            $TokenRecord = DB::table('password_resets')->where('token', $token)->first();
            if (empty($TokenRecord)) {
                if ($type === "brands") {
                    $redirect = $brand_reset_url;
                } elseif ($type === "users") {
                    $redirect = $user_reset_url;
                } else {
                    $redirect = $app_url;
                }
                header('Location: ' . $redirect);
                die();
            }

            /* let redirect to reset password page as per the user type */
            if ($type === "brands") {
                $redirect = $brand_reset_url;
            } elseif ($type === "users") {
                $redirect = $user_reset_url;
            }

            header('Location: ' . $redirect . '?token=' . $token);
            die();
        } catch (Exception $e) {
            header('Location: ' . $redirect . '?token=' . $token);
            die();
        }
    }
}
