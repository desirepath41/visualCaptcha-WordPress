<?php
/**
 * visualCaptcha HTML class by emotionLoop - 2013.08.17
 *
 * This class handles the HTML for the main visualCaptcha class.
 *
 * This license applies to this file and others without reference to any other license.
 *
 * @author emotionLoop | http://emotionloop.com
 * @link http://visualcaptcha.net
 * @package visualCaptcha (WordPress)
 * @license GNU GPL v3
 * @version 4.2.0
 */
namespace visualCaptcha;

class HTML {
	
	public function __construct() {
	}
	
	public static function get( $type, $fieldName, $accessibilityFieldName, $formId, $captchaText, $options ) {
		$html = '';
		
		ob_start();
?>
<script>
window.vCVals = {
	'f': '<?php echo $formId; ?>',
	'n': '<?php echo $fieldName; ?>',
	'a': '<?php echo $accessibilityFieldName; ?>'
};
</script>
<div class="eL-captcha type-<?php echo $type; ?> clearfix">
	<p class="eL-explanation type-<?php echo $type; ?>"><span class="desktopText"><?php _e( 'Drag the', 'visualcaptcha' ); ?> <strong><?php _e( $captchaText, 'visualcaptcha' ); ?></strong> <?php _e( 'to the circle on the side', 'visualcaptcha' ); ?>.</span><span class="mobileText"><?php _e( 'Touch the', 'visualcaptcha' ); ?> <strong><?php _e( $captchaText, 'visualcaptcha' ); ?></strong> <?php _e( 'to move it to the circle on the side', 'visualcaptcha' ); ?>.</span></p>
	<div class="eL-possibilities type-<?php echo $type; ?> clearfix">
<?php
		$limit = count( $options );

		for ( $i = 0; $i < $limit; $i++ ) {
			$name = $options[ $i ];
?>
		<img src="<?php echo WP_PLUGIN_URL . '/visualcaptcha/' . Captcha::$imageFile; ?>?i=<?php echo ($i + 1); ?>&amp;r=<?php echo time(); ?>" class="vc-<?php echo ($i + 1); ?>" data-value="<?php echo $name; ?>" alt="" title="">
<?php
		}
?>
	</div>
	<input type="hidden" name="visualcaptcha" value="1" />
	<div class="eL-where2go type-<?php echo $type; ?> clearfix">
		<p><?php _e( 'Drop<br>Here', 'visualcaptcha' ); ?></p>
	</div>
	<p class="eL-accessibility type-<?php echo $type; ?>"><a href="#" title="<?php echo 'Accessibility option: listen to a question and answer it!'; ?>"><img src="<?php echo WP_PLUGIN_URL . '/visualcaptcha/' . Captcha::$imagesPath; ?>accessibility.png" alt="<?php _e( 'Accessibility option: listen to a question and answer it!', 'visualcaptcha' ); ?>"></a></p>
	<div class="eL-accessibility type-<?php echo $type; ?>">
		<p><?php _e( 'Type below the', 'visualcaptcha' ); ?> <strong><?php _e( 'answer', 'visualcaptcha' ); ?></strong> <?php _e( 'to what you hear. Numbers or words:', 'visualcaptcha' ); ?></p>
		<audio preload="preload">
			<source src="<?php echo WP_PLUGIN_URL . '/visualcaptcha/' . Captcha::$audioFile; ?>?t=ogg&amp;r=<?php echo time(); ?>" type="audio/ogg">
			<source src="<?php echo WP_PLUGIN_URL . '/visualcaptcha/' . Captcha::$audioFile; ?>?t=mp3&amp;r=<?php echo time(); ?>" type="audio/mpeg">
			<?php _e( 'Your browser does not support the audio element.', 'visualcaptcha' ); ?>
		</audio>
	</div>
</div>
<script>
(function( $ ) {
	var $formElement = $('.eL-captcha').closest( 'form' );
	$formElement.find('input[name="visualcaptcha"]').val( $formElement.attr('id') );
	window.vCVals.f = $formElement.attr( 'id' );
})( jQuery );
</script>
<?php
		$html = ob_get_clean();
		return $html;
	}
}
?>