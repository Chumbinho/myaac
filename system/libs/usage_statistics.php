<?php
/**
 * Usage Statistics
 *
 * @package   MyAAC
 * @author    Slawkens <slawkens@gmail.com>
 * @copyright 2017 MyAAC
 * @version   0.6.1
 * @link      http://my-aac.org
 */
defined('MYAAC') or die('Direct access not allowed!');

class Usage_Statistics {
	public static function report() {
		$url = 'http://my-acc.org/report_usage.php';
		//$url = BASE_URL . 'report_usage.php';
		
		$data = json_encode(self::getStats());
		$options = array(
			'http' => array(
				'header'  => 'Content-type: application/json',
				'method'  => 'POST',
				'content' => $data
			)
		);
		
		$context  = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
		if ($result === false) {
			return false;
		}
		
		return true;
		//var_dump($result);
	}
	
	public static function getStats() {
		global $config, $db;
		
		$ret = array();
		
		$ret['unique_id'] = hash('sha1', $config['server_path']);
		$ret['server_os'] = php_uname('s') . ' ' . php_uname('r');
		
		$ret['myaac_version'] = MYAAC_VERSION;
		$ret['myaac_db_version'] = DATABASE_VERSION;
		
		$query = $db->query('SELECT `value` FROM `server_config` WHERE `config` = ' . $db->quote('database_version'));
		if($query->rowCount() == 1) {
			$query = $query->fetch();
			$ret['otserv_db_version'] = $query['value'];
		}
		
		$ret['client_version'] = $config['client'];
		
		$ret['php_version'] = phpversion();
		
		$query = $db->query('SELECT VERSION() as `version`;');
		if($query->rowCount() == 1) {
			$query = $query->fetch();
			$ret['mysql_version'] = $query['version'];
		}
		
		$query = $db->query('SELECT SUM(ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 ), 0)) AS "size"
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = "forgottenserver";');
		
		if($query->rowCount() == 1) {
			$query = $query->fetch();
			$ret['database_size'] = $query['size'];
		}
		
		$ret['views_counter'] = getDatabaseConfig('views_counter');
		
		$query = $db->query('SELECT COUNT(`id`) as `size` FROM `accounts`;');
		if($query->rowCount() == 1) {
			$query = $query->fetch();
			$ret['accounts_size'] = $query['size'];
		}
		
		$query = $db->query('SELECT COUNT(`id`) as `size` FROM `players`;');
		if($query->rowCount() == 1) {
			$query = $query->fetch();
			$ret['players_size'] = $query['size'];
		}
		
		$query = $db->query('SELECT COUNT(`id`) as `size` FROM `' . TABLE_PREFIX . 'monsters`;');
		if($query->rowCount() == 1) {
			$query = $query->fetch();
			$ret['monsters_size'] = $query['size'];
		}
		
		$query = $db->query('SELECT COUNT(`id`) as `size` FROM `' . TABLE_PREFIX . 'spells`;');
		if($query->rowCount() == 1) {
			$query = $query->fetch();
			$ret['spells_size'] = $query['size'];
		}
		
		$ret['locales'] = get_locales();
		$ret['plugins'] = get_plugins();
		$ret['templates'] = get_templates();
		
		$ret['date_timezone'] = $config['date_timezone'];
		$ret['backward_support'] = $config['backward_support'];
		
		$cache_engine = strtolower($config['cache_engine']);
		if($cache_engine == 'auto') {
			$cache_engine = Cache::detect();
		}
		
		$ret['cache_engine'] = $cache_engine;
		return $ret;
	}
}