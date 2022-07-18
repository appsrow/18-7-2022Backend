<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    /**
     * ------------------------------------------------------
     * The DropForCoin API is RESTful and it:
     * ------------------------------------------------------
     * 
     * Uses predictable, resource-oriented URLs.
     * Uses built-in HTTP capabilities for passing parameters and authentication.
     * Responds with standard HTTP response codes to indicate errors.
     * Returns JSON.
     * To give you an idea of how to use the API, we have annotated our documentation with code samples.
     * 
     */
    public $selected_language;

    function __construct(Request $request)
    {
        //get language and set application local language
        $this->selected_language = $request->header('Selected-Language', 'es');
        app()->setLocale($this->selected_language);
    }

    public function sendResponse($result, $message, $code = 200)
    {
        $response = [
            'success'    => true,
            'message'   => $message,
            'api_version' => config('app.api_latest'),
            'datetime' => time()
        ];

        if (!is_null($result)) {
            $response['data'] = $result;
        }

        return response($response, $code);
    }

    public function sendError($message, $data = null, $code = 404)
    {
        $res = [
            'success'    => false,
            'message'   => $message,
            'api_version' => config('app.api_latest'),
            'datetime' => time()
        ];
        if (!empty($data)) {
            $res['data'] = $data;
        } else {
            $res['data'] = json_decode("{}");
        }
        return response($res, $code);
    }
}
