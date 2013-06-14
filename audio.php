<?php
/**
 * visualCaptcha Audio file by emotionLoop - 2013.03.28
 *
 * This file will get the proper session audio file and play it, so that it's no possible to know through the front-end code the audio file name or even which audio file it is.
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

session_start();

include('inc/visualcaptcha.class.php');

if ( !is_callable ('__' ) ) {
	function __($text) {
		return $text;
	}
}

$visualCaptcha = new \visualCaptcha\visualcaptcha();

$file = $visualCaptcha->getAudioFilePath();

if ( ! isset($_GET['t']) ) {
	$_GET['t'] = 'mp3';
}


switch ($_GET['t']) {
	case 'ogg':
		$mimeType = 'audio/ogg';
		$extension = 'ogg';
		$file = str_replace( '.mp3', '.ogg', $file );
	break;
	case 'mp3':
	default:
		$mimeType = 'audio/mpeg';
		$extension = 'mp3';
	break;
}

header( 'Pragma: public' );
header( 'Expires: 0' );
header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
header( 'Cache-Control: private', false );
header( 'Content-Type: ' . $mimeType );
header( 'Content-Transfer-Encoding: binary' );
header( 'Content-Length: '.filesize($file) );
readfile( $file );
exit();

?>