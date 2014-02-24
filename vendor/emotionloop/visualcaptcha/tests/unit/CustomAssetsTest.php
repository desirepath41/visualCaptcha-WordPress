<?php

class CustomAssetsTest extends visualCaptcha_TestCase {
    public function testCustomAssets() {
        $assetsPath = __DIR__ . '/../fixture';

        $captcha = new \visualCaptcha\Captcha( $this->session, $assetsPath );

        $imageOptions = $captcha->getAllImageOptions();

        $this->assertCount( 2, $imageOptions );
        $this->assertEquals( 'Cat', $imageOptions[ 0 ][ 'name' ] );

        $audioOptions = $captcha->getAllAudioOptions();

        $this->assertCount( 2, $audioOptions );
        $this->assertEquals( '4plus1.mp3', $audioOptions[ 0 ][ 'path' ] );
    }
}

?>