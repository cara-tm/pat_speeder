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
 * @version:      0.7.5
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
 * Callbacks for admin side
 * 
 */
if (txpinterface == 'admin')
{

	global $pat_speeder_gTxt;

	register_callback('pat_speeder_prefs', 'prefs', '', 1);
	register_callback('pat_speeder_cleanup', 'plugin_lifecycle.pat_speeder', 'deleted');

	// Default plugin Textpack.
	$pat_speeder_gTxt = array(
		'pat_speeder_enable' => 'Activate pat_speeder?',
		'pat_speeder_gzip' => 'Active Gzip compression for pat_speeder?',
		'pat_speeder_tags' => 'Tags protection from pat_speeder',
	);

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

	if ( $enable || ($prefs['pat_speeder_enable'] && $enable) )
		ob_start(function($buffer) use ($gzip, $code) {
			return _pat_speeder_go($buffer, $gzip, $code);
		});

}

/**
 * Main function
 * @param string $buffer
 * @return string HTML compressed content
 */

function _pat_speeder_go($buffer, $gzip, $code)
{
	// List of tags to keep as this
	$codes = str_replace(',', '|', $code);

	// remove uncessary elements from the source document
	$buffer = preg_replace('/(?imx)(?>[^\S ]\s*|\s{2,})(?=(?:(?:[^<]++|<(?!\/?(?:textarea|'.$codes.')\b))*+)(?:<(?>textarea|'.$codes.')\b| \z))/u, ' ', $buffer);
	// remove all comments but keep Googlebot and IE conditional ones
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
 * i18n from adi_plugins. Tks ;)
 * @param   $phrase   $atts
 */
function pat_speeder_gTxt($phrase, $atts = array()) {
// will check installed language strings before embedded English strings - to pick up Textpack
// - for TXP standard strings gTxt() & pat_speeder_gTxt() are functionally equivalent
	global $pat_speeder_gTxt;

	if (strpos(gTxt($phrase, $atts), $phrase) !== FALSE) { // no TXP translation found
		if (array_key_exists($phrase, $pat_speeder_gTxt)) // translation found
			return strtr($pat_speeder_gTxt[$phrase], $atts);
		else // last resort
			return $phrase;
		}
	else // TXP translation
		return gTxt($phrase, $atts);
}


/**
 * Plugin prefs.
 *
 * @param  
 * @return Insert this plugin prefs into 'txp_prefs' table.
 */
function pat_speeder_prefs()
{

	global $textarray, $pat_speeder_gTxt;

	$textarray['pat_speeder_enable'] = gTxt('pat_speeder_enable');
	$textarray['pat_speeder_gzip'] = gTxt('pat_speeder_gzip');
	$textarray['pat_speeder_tags'] = gTxt('pat_speeder_tags');

	if (!safe_field ('name', 'txp_prefs', "name='pat_speeder_enable'"))
		safe_insert('txp_prefs', "prefs_id=1, name='pat_speeder_enable', val='0', type=1, event='admin', html='yesnoradio', position=24");

	if (!safe_field ('name', 'txp_prefs', "name='pat_speeder_gzip'"))
		safe_insert('txp_prefs', "prefs_id=1, name='pat_speeder_gzip', val='0', type=1, event='admin', html='yesnoradio', position=25");

	if (!safe_field ('name', 'txp_prefs', "name='pat_speeder_tags'"))
		safe_insert('txp_prefs', "prefs_id=1, name='pat_speeder_tags', val='script,svg,pre,code', type=1, event='admin', html='text_input', position=26");

	safe_repair('txp_prefs');

}


/**
 * Delete plugin prefs & language strings.
 *
 * @param
 * @return Delete this plugin prefs.
 */
function pat_speeder_cleanup()
{

	// Array of tables & rows to be removed
	$els = array('txp_prefs' => 'pat_speeder', 'txp_lang' => 'pat_speeder');

	// Process actions
	foreach ($els as $table => $row) {
		safe_delete($table, "name LIKE '".str_replace('_', '\_', $row)."\_%'");
		safe_repair($table);
	}

}
