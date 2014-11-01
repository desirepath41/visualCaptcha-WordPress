/*! visualCaptcha WordPress Bootstrapper
* http://visualcaptcha.net
* Copyright (c) 2014 emotionLoop; Licensed MIT */

(function( $ ) {
    $( document ).ready( function() {
        $( '[data-captcha]' ).each( function() {
            var $this = $( this );

            var captchaData = $this.data( 'captcha' );

            var numberOfImages = captchaData.numberOfImages || 6;

            var namespace = captchaData.namespace || '';

            $this.visualCaptcha( {
                imgPath: captchaParams.imgPath,
                captcha: {
                    url: captchaParams.url,
                    numberOfImages: numberOfImages,
                    namespace: namespace
                }
            });
        });
    });
})( jQuery );