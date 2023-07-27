<?php

namespace utils;

class Cache
{
	private static string $cache_dir = ROOT_PATH.'cache/';

	public static function set(string $key, $data, int $expiry = 3600): bool
	{
		$cache_file = self::getCacheFile($key);
		$cache_data = [
			'data'   => $data,
			'expiry' => time() + $expiry,
		];

		$cacheContent = serialize($cache_data);

		if (file_put_contents($cache_file, $cacheContent) !== FALSE) {
			return TRUE;
		}

		return FALSE;
	}

	private static function getCacheFile(string $key): string
	{
		return self::$cache_dir . md5($key) . '.cache';
	}

	public static function get(string $key, $default = null)
	{
		$cacheFile = self::getCacheFile($key);

		if (file_exists($cacheFile)) {
			$cacheContent = file_get_contents($cacheFile);
			$cacheData    = unserialize($cacheContent);

			if ($cacheData && $cacheData['expiry'] > time()) {
				return $cacheData['data'];
			}

			// Cache has expired, so delete the cache file
			unlink($cacheFile);
		}

		return $default;
	}

	public static function clear(string $key): bool
	{
		$cacheFile = self::getCacheFile($key);

		if (file_exists($cacheFile)) {
			return unlink($cacheFile);
		}

		return FALSE;
	}
}
