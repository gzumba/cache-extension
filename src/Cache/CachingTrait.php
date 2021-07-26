<?php
declare(strict_types=1);

namespace Zumba\Cache;

use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

trait CachingTrait
{
    private ?CacheInterface $cache = null;

    /** @var int|\DateInterval|null $default_timeout */
    private $default_timeout;

    /**
     * @param CacheInterface $cache
     * @param int|\DateInterval|null $default_timeout
     */
    public function setCache(CacheInterface $cache, $default_timeout = null): void
    {
        $this->cache = $cache;
        $this->default_timeout = $default_timeout;
    }

    protected function cacheGet(string $key, callable $callback)
    {
        if (!$this->cache) {
            $this->cache = new NullAdapter();
        }

        return $this->cacheGetWithTimeout($key, $this->default_timeout, $callback);
    }

    protected function cacheDelete(string $key): bool
    {
        if (!$this->cache) {
            $this->cache = new NullAdapter();
        }

        return $this->cache->delete($key);
    }

    /**
     * @param string $key
     * @param int|null $timeout in secods
     * @param callable $callback
     * @return mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function cacheGetWithTimeout(string $key, ?int $timeout, callable $callback)
    {
        if (!$this->cache) {
            $this->cache = new NullAdapter();
        }

        return $this->cache->get(
            $key,
            function (ItemInterface $item) use ($callback, $timeout) {
                $item->expiresAfter($timeout);

                return $callback($item);
            }
        );
    }

    protected function buildKeyForStringArray(array $strings): string
    {
        sort($strings);

        return sha1(serialize(array_values($strings)));
    }

    protected function buildKeyForArray(array $array): string
    {
        ksort($array);
        return sha1(serialize($array));
    }

    protected function cacheSet(string $key, $value): void
    {
        $this->cacheDelete($key);

        $this->cacheGet($key, function () use ($value) {return $value;});
    }

    /**
     * Calculate or something the value, but when callable throws, use
     * cached value instead. If the item is not available, rethrow
     *
     * @param string $key
     * @param callable $callback
     * @throws \Throwable
     */
    protected function cacheGetUnless(string $key, callable $callback)
    {
        throw new \LogicException("This is not yet implemented, but heavily needed");
    }
}
