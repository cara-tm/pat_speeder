<?php
/**
 * @name          pat_speeder
 * @description	  Display page source on one line of code
 * @link          https://github.com/cara-tm/pat_speeder
 * @author        Patrick LEFEVRE
 * @author_email  <patrick[dot]lefevre[at]gmail[dot]com>
 * @type:         Admin + Public
 * @prefs:        prefs
 * @order:        5
 * @version:      2.1
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
 * This plugin public handler
 *
 */
if (txpinterface == 'public') {
	register_callback('pat_process($buffer, $gzip, $code, $compact)', 'pretext');
	if (get_pref('pat_speeder_pref_debug') == 1) {
		register_callback('pat_compression_end', 'textpattern_end');
	}
}

/**
 * This plugin admin events
 *
 */
if (txpinterface == 'admin')
{
    // Install / remove prefs
    register_callback('pat_speeder_lifecycle', 'plugin_lifecycle.pat_speeder');

    // Prefs pane
    add_privs("prefs.pat_speeder", "1,2,3,4");
    add_privs("plugin_prefs.pat_speeder", "1,2,3,4");
    register_callback('pat_speeder_options_prefs_redirect', 'plugin_prefs.pat_speeder');
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
		'enable'  => false,
		'gzip'    => get_pref('pat_speeder_pref_gzip'),
		'code'    => get_pref('pat_speeder_pref_tags'),
		'compact' => get_pref('pat_speeder_pref_compact'),
	),$atts));

	if (
		(get_pref('pat_speeder_pref_enable_live_only') and get_pref('production_status') === 'live')
			or
		(get_pref('pat_speeder_pref_enable_live_only') == '0' and
			(get_pref('pat_speeder_pref_enable') or ($enable and get_pref('pat_speeder_pref_enable')))
		)
	) {
		ob_start(function($buffer) use ($gzip, $code, $compact) {
			return pat_process($buffer, $gzip, $code, $compact);
		});
	}

}

/**
 * Main function
 * @param string $buffer
 * @return string HTML compressed content
 */

function pat_process($buffer, $gzip, $code, $compact)
{
	// Add a tag to process if list is empty
	$code .= 'template'; 
	// Sanitize the list: no spaces
	$codes = preg_replace('/\s*/m', '', $code);
	// ... and no final comma. Convert into a pipes separated list
	$codes = str_replace(',', '|', rtrim($codes, ','));
	// Set the replacement mode
	$compact = ($compact ? '' : ' ');

	// Remove uncessary elements from the source document (especially: from 2 and more spaces between tags). But keep safe excluded tags
	$buffer = preg_replace('/(?imx)(?>[^\S ]\s*|\s{2,})(?=(?:(?:[^<]++|<(?!\/?(?:textarea|'.$codes.')\b))*+)(?:<(?>textarea|'.$codes.')\b| \z))/u', $compact, $buffer);
	if (get_pref('pat_speeder_pref_old_comments') == 1 ) {
		// Remove all comments except google ones and IE conditional comments
		$buffer = preg_replace('/<!--([^<|\[|>|go{2}gleo]).*?-->/s', '', $buffer);
	}

	// Server side compression if available
	if (function_exists('brotli_compress')) {
		// Brotli compression
		$compressed_page = brotli_compress($page, 11);
		header('Content-Encoding: br');
	} elseif (get_pref('pat_speeder_gzip') and $gzip) {
		// Check server config
		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && false == ini_get('zlib.output_compression')) {
			$encoding = $_SERVER['HTTP_ACCEPT_ENCODING'];
				if(function_exists('gzencode') && preg_match('/gzip/i', $encoding)) {
					header('Content-Encoding: gzip');
					$buffer = gzencode($buffer);
			} elseif (function_exists('gzdeflate') && preg_match('/deflate/i', $encoding)) {
				header('Content-Encoding: deflate');
				$buffer = gzdeflate($buffer);
			}
		}
	}

	// Return the result
	return $buffer;
	// Send the buffer
	ob_end_flush();
	// Empty the buffer
	ob_end_clean();
}

function pat_compression_end() {
	if (ob_get_length()) {
		ob_end_flush();
	}
}

/**
 * Plugin prefs.
 *
 * @param
 * @return Insert this plugin prefs into 'txp_prefs' table.
 */
function pat_speeder_lifecycle($event, $step) {

    $msg = '';
    $name = 'pat_speeder';

    switch ($step) {
        case "enabled":
            if (!pref_exists("pat_speeder_pref_enable")) {
                set_pref("pat_speeder_pref_enable", 0, 'pat_speeder', PREF_PLUGIN, 'yesnoradio', 0);
                set_pref("pat_speeder_pref_enable_live_only", "0", 'pat_speeder', PREF_PLUGIN, 'yesnoradio', 0);
                set_pref("pat_speeder_pref_compact", "0", 'pat_speeder', PREF_PLUGIN, 'yesnoradio', 0);
                set_pref("pat_speeder_pref_gzip", "0", 'pat_speeder', PREF_PLUGIN, 'yesnoradio', 0);
                set_pref("pat_speeder_pref_tags", "script,svg,pre,code", 'pat_speeder', PREF_PLUGIN, 'input', 0);
                set_pref("pat_speeder_pref_old_comments", "0", 'pat_speeder', PREF_PLUGIN, 'yesnoradio', 0);
                set_pref("pat_speeder_pref_debug", "0", 'pat_speeder', PREF_PLUGIN, 'yesnoradio', 0);
                $msg = 'pat_speeder enabled';
            }
            safe_repair('txp_prefs');
            safe_repair('txp_plugin');
            break;
        case "disabled":
            break;
        case "installed":
            $msg = gTxt('plugin_installed', array('{name}' => $name));
            break;
        case "deleted":
            remove_pref(null, "pat_speeder");
            _pat_speeder_cleanup();
            safe_repair('txp_prefs');
            safe_repair('txp_plugin');
            $msg = gTxt('plugin_deleted', array('{name}' => $name));
            break;
    }

    return $msg;
}

/**
 * Re-route 'Options' link on Plugins panel to Admin › Preferences panel
 *
 */
function pat_speeder_options_prefs_redirect()
{
    header("Location: index.php?event=prefs#prefs_group_pat_speeder");
}


/**
 * Delete plugin prefs & language strings.
 *
 * @param
 * @return Delete this plugin prefs.
 */
function _pat_speeder_cleanup()
{

	$tables = array('pat_speeder', 'pat_speeder_pref_enable', 'pat_speeder_pref_gzip', 'pat_speeder_pref_tags', 'pat_speeder_pref_enable_live_only', 'pat_speeder_pref_compact');
	foreach ($tables as $val) {
		safe_delete('txp_prefs', "name='".$val."'");
	}
	safe_delete('txp_lang', "owner='pat_speeder'");

	safe_repair('txp_prefs');
	safe_repair('txp_plugin');

}
