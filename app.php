<?php
@include( __DIR__ . '/vendor/autoload.php' );

// Initialize Session
session_cache_limiter( false );

if ( session_id() == '' ) {
    session_start();
}
    
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// Setup CORS
$app->response[ 'Access-Control-Allow-Origin' ] = '*';

// Inject Session object into app
if ( $namespace = $app->request->params( 'namespace' ) ) {
    $app->session = new \visualCaptcha\Session( 'visualcaptcha_' . $namespace );
} else {
    $app->session = new \visualCaptcha\Session();
}

// Populates captcha data into session object
// -----------------------------------------------------------------------------
// @param howmany is required, the number of images to generate
$app->get( '/start/:howmany', function( $howMany ) use( $app ) {
    $captcha = new \visualCaptcha\Captcha( $app->session );
    $captcha->generate( $howMany );

    $app->response[ 'Content-Type' ] = 'application/json';

    echo json_encode( $captcha->getFrontEndData() );
} );

// Streams captcha images from disk
// -----------------------------------------------------------------------------
// @param index is required, the index of the image you wish to get
$app->get( '/image/:index', function( $index ) use( $app ) {
    $captcha = new \visualCaptcha\Captcha( $app->session );

    if ( ! $captcha->streamImage(
            $app->response,
            $index,
            $app->request->params( 'retina' )
    ) ) {
        $app->pass();
    }
} );

// Streams captcha audio from disk
// -----------------------------------------------------------------------------
// @param type is optional and defaults to 'mp3', but can also be 'ogg'
$app->get( '/audio(/:type)', function( $type = 'mp3' ) use( $app ) {
    $captcha = new \visualCaptcha\Captcha( $app->session );

    if ( ! $captcha->streamAudio( $app->response, $type ) ) {
        $app->pass();
    }
} );

$app->run();
?>