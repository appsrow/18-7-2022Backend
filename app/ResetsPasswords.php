<?php
namespace App;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Auth;
trait ResetsPasswords
{
    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postEmail(Request $request)
    {
        $body = file_get_contents("php://input");
        
        if(!empty($body)) {

            $requestData = json_decode($body, true);
    
            $email_duplicate = User::where('email', $requestData['email'])
            ->where('is_social_sign_in', "1")
            ->first();

            if(!empty($email_duplicate)){
                return $this->sendError("Not Allowed, Your account is associated with social login.",null,200);
            }

            return $this->sendResetLinkEmail($request);
        }
       
    }

    /**
     * Send a reset link to the given Brand.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function post_Brand_Email(Request $request)
    {
        //echo $request;
        return $this->sendResetLinkEmail_to_brand($request);
    }

    /**
     * Send a reset link to the given Brand.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendResetLinkEmail_to_brand(Request $request)
    {
        $body = file_get_contents("php://input");

        if(!empty($body)) {

            $requestData = json_decode($body, true);
            
            $validator = Validator::make($requestData, 

                ['email' => 'required|email']

            );

            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 200);
            }

            $broker = $this->getBroker();
            $response = Password::broker($broker)->sendResetLink_to_brand($requestData, function (Message $message) {
                $message->subject($this->getEmailSubject());
            });

            switch ($response) {
                case Password::RESET_LINK_SENT:
                    return $this->getSendResetLinkEmailSuccessResponse($response);
                case Password::INVALID_USER:
                default:
                    return $this->getSendResetLinkEmailFailureResponse($response);
            }
        }
    }

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendResetLinkEmail(Request $request)
    {
        $body = file_get_contents("php://input");

        if(!empty($body)) {

            $requestData = json_decode($body, true);
            
            $validator = Validator::make($requestData, 

                ['email' => 'required|email']

            );

            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 200);
            }

            $broker = $this->getBroker();
            $response = Password::broker($broker)->sendResetLink($requestData, function (Message $message) {
                $message->subject($this->getEmailSubject());
            });

            switch ($response) {
                case Password::RESET_LINK_SENT:
                    return $this->getSendResetLinkEmailSuccessResponse($response);
                case Password::INVALID_USER:
                default:
                    return $this->getSendResetLinkEmailFailureResponse($response);
            }
        }
    }

    /**
     * Get the e-mail subject line to be used for the reset link email.
     *
     * @return string
     */
    protected function getEmailSubject()
    {
        return property_exists($this, 'subject') ? $this->subject : 'Your Password Reset Link';
    }
    /**
     * Get the response for after the reset link has been successfully sent.
     *
     * @param  string  $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function getSendResetLinkEmailSuccessResponse($response)
    {
        return response()->json([
            'success' => true,
            'message' => 'Reset password link sent to the user'
        ]);
    }
    /**
     * Get the response for after the reset link could not be sent.
     *
     * @param  string  $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function getSendResetLinkEmailFailureResponse($response)
    {
        $message = "";
        if($response == "passwords.user"){
            $message = "User not found";
        }else{
            $message = "Unknown Error. ".$response;
        }
        return response()->json([
            'success' => false,
            'message' => $message
        ]);
    }
    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postReset(Request $request)
    {
        return $this->reset($request);
    }
    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function reset(Request $request)
    {
        $body = file_get_contents("php://input");
        
        if(!empty($body)) {

            $requestData = json_decode($body, true);
           
            $validator = Validator::make($requestData, $this->getResetValidationRules(), $this->getResetValidationMessages());

            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 200);
            }
            
            $credentials = $requestData;


            $is_encoded = preg_match('~%[0-9A-F]{2}~i', $credentials['token']);

            if($is_encoded){
                $credentials['token'] = urldecode($credentials['token']);
            }

            if(empty(explode('($',$credentials['token'])[1])){
                return $this->sendError("Reset link has expired or Invalid",null,200);
            }

            $email = explode('($',$credentials['token'])[1];
            
            try {
                $credentials['email'] = decrypt($email);
            } catch (DecryptException $e) {
                return $this->sendError("Reset link has expired or Invalid",null,200);
            }

            $credentials['token'] = explode('($',$credentials['token'])[0];
            $broker = $this->getBroker();
            $response = Password::broker($broker)->reset($credentials, function ($user, $password) {
                $this->resetPassword($user, $password);
            });
            switch ($response) {
                case Password::PASSWORD_RESET:
                    $remove_token = User::where('email', $credentials['email'])->update(['api_token' => '']);
                    //Auth::invalidate();
                    return $this->getResetSuccessResponse($response);
                default:
                    return $this->getResetFailureResponse($request, $response);
            }
        }
    }


    /**
     * Get the password reset validation rules.
     *
     * @return array
     */
    protected function getResetValidationRules()
    {
        return [
            'token' => 'required',
            // 'email' => 'required|email',
            'password' => 'required|confirmed|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',
        ];
    }

    /**
     * Get the password reset validation rules.
     *
     * @return array
     */
    protected function getResetValidationMessages()
    {
        return [
            'password.regex' => 'Password must have atleast one upper case, one lower case, one numeric and one special character',
        ];
    }


    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $password
     * @return void
     */
    protected function resetPassword($user, $password)
    {
        $hasher = app()->make('hash');
        $user->password = $hasher->make($password);
        $user->save();
        return response()->json(['success' => true]);
    }


    /**
     * Get the response for after a successful password reset.
     *
     * @param  string  $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function getResetSuccessResponse($response)
    {
        return response()->json(['success' => true, 'message' => 'Password has been successfully reset']);
    }


    /**
     * Get the response for after a failing password reset.
     *
     * @param  Request  $request
     * @param  string  $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function getResetFailureResponse(Request $request, $response)
    {
        $message = "Unknow reason.";
        if($response == "passwords.token"){
            $message = "Reset link has expired or Invalid";
        }
        return response()->json(['success' => false, 'message' => $message]);
    }


    /**
     * Get the broker to be used during password reset.
     *
     * @return string|null
     */
    public function getBroker()
    {
        return property_exists($this, 'broker') ? $this->broker : null;
    }
}