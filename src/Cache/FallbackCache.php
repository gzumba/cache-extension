<?php
declare(strict_types=1);

namespace Zumba\Cache;

use Symfony\Contracts\Cache\CacheInterface;

class FallbackCache implements CacheInterface
{
    private CacheInterface $fallback;

    public function __construct(CacheInterface $fallback)
    {
        $this->fallback = $fallback;
    }

    public function get(string $key, callable $callback, float $beta = null, array &$metadata = null): mixed
    {
        try {
            return $this->callAndUpdateCache($callback, $key);
        } catch (\Throwable $e) {
            return $this->fallback->get($key, fn () => throw $e, 0, $metadata);
        }
    }

    public function delete(string $key): bool
    {
        return $this->fallback->delete($key);
    }

    public function getWithSecondary(string $key, callable $callback, callable $secondary_cb, array &$metadata = null): mixed
    {
        try {
            return $this->callAndUpdateCache($callback, $key);
        } catch (\Throwable $e) {
            return $this->fallback->get($key, $secondary_cb, 0, $metadata);
        }
    }

    /**
     * @param callable $callback
     * @param string $key
     * @return mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function callAndUpdateCache(callable $callback, string $key): mixed
    {
        $res = $callback();
        // update fallback with new value
        $this->fallback->delete($key);
        $this->fallback->get($key, fn() => $res);
        return $res;
    }
}
