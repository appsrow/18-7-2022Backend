<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*$app->withFacades();

$app->withEloquent();

$app->configure('services');

$app->configure('mail');

$app->configure('jwt');*/

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);



/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);

// $app->register(\Illuminate\Auth\Passwords\PasswordResetServiceProvider::class);
$app->register(\Illuminate\Mail\MailServiceProvider::class);
$app->register(\Illuminate\Notifications\NotificationServiceProvider::class);
//$app->register(Intervention\Image\ImageServiceProvider::class);
//$app->register(Davibennun\LaravelPushNotification\LaravelPushNotificationServiceProvider::class);
//$app->register(LaravelFCM\FCMServiceProvider::class);
$app->register(App\Providers\PasswordResetServiceProvider::class);
// $app->register(Bugsnag\BugsnagLaravel\BugsnagServiceProvider::class);
$app->register(Tymon\JWTAuth\Providers\LaravelServiceProvider::class);

//$app->register(\Illuminate\Contracts\Mail\Mailer::class);
$app->alias('mailer', \Illuminate\Contracts\Mail\Mailer::class);


$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/api.php';
});


return $app;
