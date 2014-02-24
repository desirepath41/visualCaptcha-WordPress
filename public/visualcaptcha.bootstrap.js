/*! visualCaptcha WordPress Bootstrapper
* http://visualcaptcha.net
* Copyright (c) 2014 emotionLoop; Licensed MIT */

( function( $ ) {
    $( function() {
        $( '[data-captcha]' ).visualCaptcha( {
            imgPath: captchaParams.imgPath,
            captcha: {
                url: captchaParams.url,
                numberOfImages: 6
            }
        } );
    } );
}( jQuery ) );