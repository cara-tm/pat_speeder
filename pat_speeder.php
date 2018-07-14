<?php
/**
 * @name	  pat_speeder
 * @description	  Display page source on one line of code
 * @link 	  http://pat-speeder.cara-tm.com
 * @author	  Patrick LEFEVRE
 * @author_email  <patrick[dot]lefevre[at]gmail[dot]com>
 * @type:         Admin + Public
 * @prefs:        no prefs
 * @order:        5
 * @version:      1.0
 * @license:      GPLv2
 */

/**
 * This plugin tag registry
 *
 */
if (class_exists('\Textpattern\Tag_Registry')) {
	Txp::get('\Textpattern\Tag\Registry')
		->register('pat_speeder');
}


/**
 * This plugin lifecycle
 * 
 */
if (txpinterface == 'admin')
{
	register_callback('pat_speeder_prefs', 'plugin_lifecycle.pat_speeder', 'installed');
	register_callback('pat_speeder_cleanup', 'plugin_lifecycle.pat_speeder', 'deleted');
}


/**
 * This plugin tag with attributes
 *
 * @param  array    Tag attributes
 * @return boolean  Call for main function
 */
function pat_speeder($atts)
{

	global $prefs;

	extract(lAtts(array(
		'enable' => true,
		'gzip'   => $prefs['pat_speeder_gzip'],
		'code'   => $prefs['pat_speeder_tags'],
	),$atts));

	if ( $enable || ($prefs['pat_speeder_enable'] && $enable) ) {
		ob_start(function($buffer) use ($gzip, $code) {
			return _pat_speeder_go($buffer, $gzip, $code);
		});
	}

}

/**
 * Main function
 * @param string $buffer
 * @return string HTML compressed content
 */

function _pat_speeder_go($buffer, $gzip, $code)
{

	$codes = str_replace(',', '|', $code);

	// remove uncessary elements from the source document
	$buffer = preg_replace('/(?imx)(?>[^\S ]\s*|\s{2,})(?=(?:(?:[^<]++|<(?!\/?(?:textarea|'.$codes.')\b))*+)(?:<(?>textarea|'.$codes.')\b| \z))/u', ' ', $buffer);
	// remove all comments except google ones
	$buffer = preg_replace('/<!--([^<|\[|>|go{2}gleo]).*?-->/s', '', $buffer);

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


/**
 * Plugin prefs.
 *
 * @param  
 * @return Insert this plugin prefs into 'txp_prefs' table.
 */
function pat_speeder_prefs()
{

	if (!safe_field ('name', 'txp_prefs', "name='pat_speeder_enable'"))
		safe_insert('txp_prefs', "name='pat_speeder_enable', val='0', type=1, event='admin', html='yesnoradio', position=24");

	if (!safe_field ('name', 'txp_prefs', "name='pat_speeder_gzip'"))
		safe_insert('txp_prefs', "name='pat_speeder_gzip', val='0', type=1, event='admin', html='yesnoradio', position=25");

	if (!safe_field ('name', 'txp_prefs', "name='pat_speeder_tags'"))
		safe_insert('txp_prefs', "name='pat_speeder_tags', val='script,svg,pre,code', type=1, event='admin', html='text_input', position=26");

	safe_repair('txp_prefs');
	safe_repair('txp_plugin');

}


/**
 * Delete plugin prefs & language strings.
 *
 * @param
 * @return Delete this plugin prefs.
 */
function pat_speeder_cleanup()
{

	safe_delete('txp_prefs', "name='pat_speeder_enable'");
	safe_delete('txp_prefs', "name='pat_speeder_gzip'");
	safe_delete('txp_prefs', "name='pat_speeder_tags'");
	safe_delete('txp_lang', "owner='pat_speeder'");
	safe_repair('txp_plugin');

}

