<?php
/**
 * Initialize some defaults
 *
 * @package   MyAAC
 * @author    Slawkens <slawkens@gmail.com>
 * @copyright 2017 MyAAC
 * @version   0.0.1
 * @link      http://my-aac.org
 */
defined('MYAAC') or die('Direct access not allowed!');
// load configuration
require_once(BASE . 'config.php');
if(file_exists(BASE . 'config.local.php')) // user customizations
	require(BASE . 'config.local.php');

// take care of trailing slash at the end
if($config['server_path'][strlen($config['server_path']) - 1] != '/')
	$config['server_path'] .= '/';

// enable gzip compression if supported by the browser
if($config['gzip_output'] && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) && function_exists('ob_gzhandler'))
	ob_start('ob_gzhandler');

// cache
require_once(SYSTEM . 'libs/cache.php');
$cache = Cache::getInstance($config['cache_engine'], $config['cache_prefix']);

// trim values we receive
if(isset($_POST))
{
	foreach($_POST as $var => $value) {
		if(is_string($value)) {
			$_POST[$var] = trim($value);
		}
	}
}
if(isset($_GET))
{
	foreach($_GET as $var => $value) {
		if(is_string($value))
			$_GET[$var] = trim($value);
	}
}
if(isset($_REQUEST))
{
	foreach($_REQUEST as $var => $value) {
		if(is_string($value))
			$_REQUEST[$var] = trim($value);
	}
}

// load otserv config file
$tmp = '';
if($cache->enabled() && $cache->fetch('config_lua', $tmp)) {
	$config['lua'] = unserialize($tmp);
	/*if(isset($config['lua']['myaac'][0])) {
		foreach($config['lua']['myaac'] as $key => $value)
			$config[$key] = $value;
	}*/
}
else
{
	$config['lua'] = load_config_lua($config['server_path'] . 'config.lua');

	// cache config
	if($cache->enabled())
		$cache->set('config_lua', serialize($config['lua']), 120);
}
unset($tmp);

if(isset($config['lua']['servername']))
	$config['lua']['serverName'] = $config['lua']['servername'];

// localize data/ directory
if(isset($config['lua']['dataDirectory'][0]))
{
	$tmp = $config['lua']['dataDirectory'];
	if($tmp[0] != '/')
		$tmp = $config['server_path'] . $tmp;

	if($tmp[strlen($tmp) - 1] != '/') // do not forget about trailing slash
		$tmp .= '/';
}
else if(isset($config['lua']['data_directory'][0]))
{
	$tmp = $config['lua']['data_directory'];
	if($tmp[0] != '/')
		$tmp = $config['server_path'] . $tmp;

	if($tmp[strlen($tmp) - 1] != '/') // do not forget about trailing slash
		$tmp .= '/';
}
else
	$tmp = $config['server_path'] . 'data/';

$config['data_path'] = $tmp;
unset($tmp);

// POT
require_once(SYSTEM . 'libs/pot/OTS.php');
$ots = POT::getInstance();
require_once(SYSTEM . 'database.php');

// load vocation names
$tmp = '';
if($cache->enabled() && $cache->fetch('vocations', $tmp)) {
	$config['vocations'] = unserialize($tmp);
}
else {
	$vocations = new DOMDocument();
	$path_extra = 'XML/';
	if($config['otserv_version'] >= OTSERV_FIRST && $config['otserv_version'] <= OTSERV_LAST)
		$path_extra = '';

	$vocations->load($config['data_path'] . $path_extra . 'vocations.xml');

	if(!$vocations)
		die('ERROR: Cannot load <i>vocations.xml</i> file.');

	$config['vocations'] = array();
	foreach($vocations->getElementsByTagName('vocation') as $vocation) {
		$id = $vocation->getAttribute('id');
		//if($id == $vocation->getAttribute('fromvoc'))
			$config['vocations'][$id] = $vocation->getAttribute('name');
		//else
		//	$config['vocations'][$id] = $vocation->getAttribute('name');
	}

	if($cache->enabled()) {
		$cache->set('vocations', serialize($config['vocations']), 120);
	}
}
unset($tmp, $id, $vocation);

// load towns
/* TODO: doesnt work
ini_set('memory_limit', '-1'); 
$tmp = '';

if($cache->enabled() && $cache->fetch('towns', $tmp)) {
	$config['towns'] = unserialize($tmp);
}
else {
	$towns = new OTS_OTBMFile();
	$towns->loadFile('D:/Projekty/opentibia/wodzislawski/data/world/wodzislawski.otbm');

	$config['towns'] = $towns->getTownsList();
	if($cache->enabled()) {
		$cache->set('towns', serialize($config['towns']), 120);
	}
}
*/
?>