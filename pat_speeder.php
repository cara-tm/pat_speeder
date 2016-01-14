<?php
/**
 * @name	  pat_speeder
 * @description	  Display page source on one line of code
 * @link 	  http://pat-speeder.cara-tm.com
 * @author	  Patrick LEFEVRE
 * @author_email  <patrick[dot]lefevre[at]gmail[dot]com>
 * @type:         Public
 * @prefs:        no prefs
 * @order:        5
 * @version:      0.7.3
 * @license:      GPLv2
 */

/**
 * This plugin tag registry
 *
 */
if (class_exists('\Textpattern\Tag\Registry')) {
	Txp::get('\Textpattern\Tag\Registry')
		->register('pat_speeder');
}


/**
 * This plugin tag with attributes
 *
 * @param  array    Tag attributes
 * @return boolean  Call for main function
 */
function pat_speeder($atts)
{
	extract(lAtts(array(
		'enable' => '1',
		'gzip'   => '1',
		'code'   => 'script,pre,code',
	),$atts));

	if ($enable)
		ob_start('_pat_speeder_go');

}

/**
 * Main function
 * @param string $buffer
 * @return string HTML compressed content
 */

function _pat_speeder_go($buffer)
{
	$codes = explode(',', $code);

	// remove uncessary elements from the source document
	$buffer = preg_replace('#(?ix)(?>[^\S ]\s*|\s{2,})(?=(?:(?:[^<]++|<(?!/?(?:textarea|'.$codes.')\b))*+)(?:<(?>textarea|'.$codes.')\b|\z))#', ' ', $buffer);
	// remove all comments
	$buffer = preg_replace('/<!--(?!<!)[^\[>].*?--> /', '', $buffer);

	// server side compression if available
	if( $gzip && isset($_SERVER['HTTP_ACCEPT_ENCODING']) ) {
		$encoding = $_SERVER['HTTP_ACCEPT_ENCODING'];
		if( function_exists('gzencode') && preg_match('/gzip/i', $encoding) ) {
			header ('Content-Encoding: gzip');
			$buffer = gzencode($buffer);
		} elseif( function_exists('gzdeflate') && preg_match('/deflate/i', $encoding) ) {
			header ('Content-Encoding: deflate');
			$buffer = gzdeflate($buffer);
		}
	}

	return $buffer;
}
