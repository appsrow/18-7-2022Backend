<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        if ($request->is('api/*')) {
            $jsonResponse = parent::render($request, $exception);
            return $this->processApiException($jsonResponse);
        }
        return parent::render($request, $exception);
    }

    protected function processApiException($originalResponse)
    {
        if ($originalResponse instanceof JsonResponse) {
            $data = $originalResponse->getData(true);
            $res = [
                'success'    => false,
                'message'   => $data['message'],
                'api_version' => config('app.api_latest')
            ];
            if (!empty($data)) {
                $res['data'] = $data;
            }
            $originalResponse->setData($res);
        }
        return $originalResponse;
    }
}
