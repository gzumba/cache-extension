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

    public function get(string $key, callable $callback, float $beta = null, array &$metadata = null)
    {
        try {
            $res = $callback();
            // update fallback with new value
            $this->fallback->delete($key);
            $this->fallback->get($key, fn () => $res);

            return $res;
        } catch (\Throwable $e) {
            return $this->fallback->get($key, fn () => throw $e);
        }
    }

    public function delete(string $key): bool
    {
        return $this->fallback->delete($key);
    }
}