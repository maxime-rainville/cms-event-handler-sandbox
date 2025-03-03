<?php

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPApplication;
use SilverStripe\Control\HTTPRequestBuilder;
use SilverStripe\Core\CoreKernel;

// Find autoload.php
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo "autoload.php not found";
    exit(1);
}

// Build request and detect flush
$request = HTTPRequestBuilder::createFromEnvironment();

// Default application
try {
    $kernel = new CoreKernel(BASE_PATH);
    $app = new HTTPApplication($kernel);
    $response = $app->handle($request);
    $response->output();
} finally {
    // This call will complete the request without closing the PHP worker. A nice side effect of this is that your
    // event listeners won't block your request from being sent to the client. So you can use them to run slow
    // operations like sending emails or doing API calls without delaying the response.
    session_write_close();
    fastcgi_finish_request();


    // Many methods in Silverstripe CMS rely on having a current controller with a request.
    $controller = new Controller();
    $controller->setRequest($request);
    $controller->pushCurrent();

    // Now we can process the events in the event loop
    \Revolt\EventLoop::run();
}
