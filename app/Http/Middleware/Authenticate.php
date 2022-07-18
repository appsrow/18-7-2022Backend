<?php

namespace App\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\User;
use Carbon\Carbon;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWTAuth;
use Exception;
use Illuminate\Support\Facades\Lang;


class Authenticate
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected $auth;
    public $selected_language;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $this->selected_language = $request->header('Selected-Language', 'es');

        $header = $request->header('Authorization');

        $header_token = explode(' ', $header);

        if (!empty($header_token[1])) {

            $token = $header_token[1];

            //return response()->json([ 'valid' => auth()->check() ]);
            //echo auth()->check();
            $response = auth()->check();
            //$responseCode = 200;
            if (!empty($response) and $response == "true") {

                //$user_id = Auth::id();

                try {
                    if (!app(\Tymon\JWTAuth\JWTAuth::class)->parseToken()->authenticate()) {
                        $response = 0;
                    }
                } catch (Exception $e) {
                    if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {

                        $res['success'] = false;
                        $res['message'] = Lang::get('auth.token_invalid', array(), $this->selected_language);

                        return response()->Json($res, 403);
                        //return response()->json(['status' => 'Token is Invalid']);
                    } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {

                        $res['success'] = false;
                        $res['message'] = Lang::get('auth.token_expired', array(), $this->selected_language);

                        return response()->Json($res, 403);
                        //return response()->json(['status' => 'Token is Expired']);
                    } else {

                        $res['success'] = false;
                        $res['message'] = Lang::get('auth.token_not_found', array(), $this->selected_language);

                        return response()->Json($res, 403);
                        //return response()->json(['status' => 'Authorization Token not found']);
                    }
                }
            } else {
                $res['success'] = false;
                $res['message'] = Lang::get('auth.token_expired', array(), $this->selected_language);

                return response()->Json($res, 403);
            }
        } else {
            $res['success'] = false;
            $res['message'] = Lang::get('auth.permission_denied', array(), $this->selected_language);

            return response()->Json($res, 403);
        }
        return $next($request);
    }
}
