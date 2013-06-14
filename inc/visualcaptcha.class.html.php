<?php
/**
 * visualCaptchaHTML class by emotionLoop - 2013.03.28
 *
 * This class handles the HTML for the main visualCaptcha class.
 *
 * This license applies to this file and others without reference to any other license.
 *
 * @author emotionLoop | http://emotionloop.com
 * @link http://visualcaptcha.net
 * @package visualCaptcha Wordpress
 * @license GNU GPL v3 | http://www.gnu.org/licenses/gpl.html
 * @version 4.0.3 
 */
namespace visualCaptcha;

class visualcaptcha_html {
	
	public function __construct() {
	}
	
	public static function get( $type, $fieldName, $accessibilityFieldName, $formId, $captchaText, $options, $optionsProperties, $jsFile, $cssFile, $audioOption ) {

		$html = '';
		
		$limit = count($options);
		
		ob_start();
?>
<link rel="stylesheet" href="<?php echo $cssFile; ?>">
<div class="eL-captcha type-<?php echo $type; ?> clearfix">
	<p class="eL-explanation type-<?php echo $type; ?>"><?php _e( 'Drag the', 'visualcaptcha' ); ?> <strong><?php echo $captchaText; ?></strong> <?php _e( 'to the circle on the side', 'visualcaptcha') ; ?>.</p>
	<div class="eL-possibilities type-<?php echo $type; ?> clearfix">
<?php
		for ($i=0;$i<$limit;$i++) {
			$name = $options[$i];
			$image = $optionsProperties[$name][0];
			$text = $optionsProperties[$name][1];
?>
		<img src="<?php echo $image; ?>" class="vc-<?php echo $name; ?>" data-value="<?php echo $name; ?>" alt="" title="">
<?php
		}
?>
	</div>
    <input type="hidden" name="visualcaptcha" value="1" />
	<div class="eL-where2go type-<?php echo $type; ?> clearfix">
	</div>
<?php 
	if ( !empty( $audioOption ) ) {
?>
	<p class="eL-accessibility type-<?php echo $type; ?>"><a href="javascript:void(0);" title="<?php _e('Accessibility option: listen to a question and answer it!', 'visualcaptcha' ); ?>"><img src="<?php echo \visualCaptcha\visualcaptcha::$imagesPath; ?>accessibility.png" alt="<?php _e('Accessibility option: listen to a question and answer it!', 'visualcaptcha' ); ?>"></a></p>
	<div class="eL-accessibility type-<?php echo $type; ?>">
		<p><?php _e( 'Type below the', 'visualcaptcha' ); ?> <strong><?php echo 'answer'; ?></strong> <?php _e( 'to what you hear. Numbers or words, lowercase:', 'visualcaptcha' ); ?></p>
		<audio preload="preload">
			<source src="<?php echo \visualCaptcha\visualcaptcha::$audioFile; ?>?t=ogg&amp;r=<?php echo time(); ?>" type="audio/ogg">
			<source src="<?php echo \visualCaptcha\visualcaptcha::$audioFile; ?>?t=mp3&amp;r=<?php echo time(); ?>" type="audio/mpeg">
			<?php _e( 'Your browser does not support the audio element.', 'visualcaptcha' ); ?>
		</audio>
	</div>
<?php 
	}
?>
</div>
<script>
ft = jQuery('.eL-captcha').parent('form')
jQuery('input[name="visualcaptcha"]').val(ft.attr('id'))

window.vCVals = {
	'f': ft.attr('id'),
	'n': '<?php echo $fieldName; ?>',
	'a': '<?php echo $accessibilityFieldName; ?>'
};
</script>
<style>
/*forcing the correct path to the drop here image*/
div.eL-captcha > div.eL-where2go {
	background: transparent url('<?php echo WP_CONTENT_URL ?>/plugins/visualcaptcha/images/visualcaptcha/dropzone.png') center center no-repeat !important;
}
</style>
<script src="<?php echo $jsFile; ?>" ></script>
<?php
		$html = ob_get_clean();
		return $html;
	}
}
?>