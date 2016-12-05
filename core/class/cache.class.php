<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../core/php/core.inc.php';

class cache {
	/*     * *************************Attributs****************************** */

	private static $cache = null;

	private $key;
	private $value = null;
	private $lifetime = 0;
	private $datetime;
	private $options = null;

	/*     * ***********************Methode static*************************** */

	public static function set($_key, $_value, $_lifetime = 0, $_options = null) {
		if ($_lifetime < 0) {
			$_lifetime = 0;
		}
		$cache = new self();
		$cache->setKey($_key);
		$cache->setValue($_value);
		$cache->setLifetime($_lifetime);
		if ($_options != null) {
			$cache->options = json_encode($_options, JSON_UNESCAPED_UNICODE);
		}
		return $cache->save();
	}

	public static function getCache() {
		if (self::$cache !== null) {
			return self::$cache;
		}
		$engine = \config::byKey('cache::engine');
		if ($engine == 'MemcachedCache' && !class_exists('memcached')) {
			$engine = 'FilesystemCache';
			config::save('cache::engine', 'FilesystemCache');
		}
		if ($engine == 'RedisCache' && !class_exists('redis')) {
			$engine = 'FilesystemCache';
			config::save('cache::engine', 'FilesystemCache');
		}
		switch ($engine) {
			case 'FilesystemCache':
				self::$cache = new \Doctrine\Common\Cache\FilesystemCache("/tmp/jeedom-cache");
				break;
			case 'PhpFileCache':
				self::$cache = new \Doctrine\Common\Cache\PhpFileCache("/tmp/jeedom-cache-php");
				break;
			case 'MemcachedCache':
				$memcached = new Memcached();
				$memcached->addServer(config::byKey('cache::memcacheaddr'), config::byKey('cache::memcacheport'));
				self::$cache = new \Doctrine\Common\Cache\MemcachedCache();
				self::$cache->setMemcached($memcached);
				break;
			case 'RedisCache':
				$redis = new Redis();
				$redis->connect(config::byKey('cache::redisaddr'), config::byKey('cache::redisport'));
				self::$cache = new \Doctrine\Common\Cache\RedisCache();
				self::$cache->setRedis($redis);
				break;
			default:
				$cache = new \Doctrine\Common\Cache\FilesystemCache("/tmp/jeedom-cache");
				break;
		}
		return self::$cache;
	}

	public static function byKey($_key, $_noRemove = false) {
		$cache = self::getCache()->fetch($_key);
		if (!is_object($cache)) {
			$cache = new self();
			$cache->setKey($_key);
			$cache->setDatetime(date('Y-m-d H:i:s'));
		}
		return $cache;
	}

	public static function flush() {
		self::getCache()->deleteAll();
		shell_exec('sudo rm -rf /tmp/jeedom-cache 2>&1 > /dev/null');
		shell_exec('rm -rf /tmp/jeedom-cache 2>&1 > /dev/null');
	}

	public static function search() {
		return array();
	}

	public static function persist() {
		switch (config::byKey('cache::engine')) {
			case 'FilesystemCache':
				$cache_dir = '/tmp/jeedom-cache';
				break;
			case 'PhpFileCache':
				$cache_dir = '/tmp/jeedom-cache-php';
				break;
			default:
				return;
		}
		try {
			com_shell::execute('sudo rm -rf ' . dirname(__FILE__) . '/../../cache.tar.gz;cd ' . $cache_dir . ';sudo tar cfz ' . dirname(__FILE__) . '/../../cache.tar.gz * 2>&1 > /dev/null;sudo chmod 775 ' . dirname(__FILE__) . '/../../cache.tar.gz;sudo chown www-data:www-data ' . dirname(__FILE__) . '/../../cache.tar.gz;sudo chmod 777 -R ' . $cache_dir);
		} catch (Exception $e) {
			log::add('cache', 'debug', $e->getMessage());
		}

	}

	public static function isPersistOk() {
		if (config::byKey('cache::engine') != 'FilesystemCache' && config::byKey('cache::engine') != 'PhpFileCache') {
			return true;
		}
		$filename = dirname(__FILE__) . '/../../cache.tar.gz';
		if (!file_exists($filename)) {
			return false;
		}
		if (filemtime($filename) < strtotime('-35min')) {
			return false;
		}
		return true;
	}

	public static function restore() {
		switch (config::byKey('cache::engine')) {
			case 'FilesystemCache':
				$cache_dir = '/tmp/jeedom-cache';
				break;
			case 'PhpFileCache':
				$cache_dir = '/tmp/jeedom-cache-php';
				break;
			default:
				return;
		}
		if (!file_exists(dirname(__FILE__) . '/../../cache.tar.gz')) {
			$cmd = 'sudo mkdir ' . $cache_dir . ';';
			$cmd .= 'sudo chmod -R 777 ' . $cache_dir . ';';
			com_shell::execute($cmd);
			return;
		}
		$cmd = 'sudo rm -rf ' . $cache_dir . ';';
		$cmd .= 'sudo mkdir ' . $cache_dir . ';';
		$cmd .= 'cd ' . $cache_dir . ';';
		$cmd .= 'sudo tar xfz ' . dirname(__FILE__) . '/../../cache.tar.gz;';
		$cmd .= 'sudo chmod -R 777 ' . $cache_dir . ';';
		com_shell::execute($cmd);
	}

	/*     * *********************Methode d'instance************************* */

	public function save() {
		$this->setDatetime(date('Y-m-d H:i:s'));
		if ($this->getLifetime() == 0) {
			return self::getCache()->save($this->getKey(), $this);
		} else {
			return self::getCache()->save($this->getKey(), $this, $this->getLifetime());
		}
	}

	public function remove() {
		try {
			self::getCache()->delete($this->getKey());
		} catch (Exception $e) {

		}
	}

	public function hasExpired() {
		return true;
	}

	/*     * **********************Getteur Setteur*************************** */

	public function getKey() {
		return $this->key;
	}

	public function setKey($key) {
		$this->key = $key;
		return $this;
	}

	public function getValue($_default = '') {
		return ($this->value === null || (is_string($this->value) && trim($this->value) === '')) ? $_default : $this->value;
	}

	public function setValue($value) {
		$this->value = $value;
		return $this;
	}

	public function getLifetime() {
		return $this->lifetime;
	}

	public function setLifetime($lifetime) {
		$this->lifetime = $lifetime;
		return $this;
	}

	public function getDatetime() {
		return $this->datetime;
	}

	public function setDatetime($datetime) {
		$this->datetime = $datetime;
		return $this;
	}

	public function getOptions($_key = '', $_default = '') {
		return utils::getJsonAttr($this->options, $_key, $_default);
	}

	public function setOptions($_key, $_value) {
		$this->options = utils::setJsonAttr($this->options, $_key, $_value);
	}

}

?>
