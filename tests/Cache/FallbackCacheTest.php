<?php
declare(strict_types=1);
namespace Zumba\Cache\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Zumba\Cache\FallbackCache;

class FallbackCacheTest extends TestCase
{
    private FallbackCache $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new FallbackCache(new ArrayAdapter());
    }

    public function testReturnsFromCallable(): void
    {
        $val = random_int(1, 100);
        $res = $this->cache->get('key', fn() => $val);
        self::assertEquals($val, $res, "Should return value from Callable");
    }

    public function testReturnsFromCallableEvenWhenCached(): void
    {
        $cached_val = 105;
        // this sets value in cache
        $this->cache->get('key', fn() => $cached_val);
        $val = random_int(1, 100);
        $res = $this->cache->get('key', fn() => $val);
        self::assertEquals($val, $res, "Should return value from Callable");
    }

    public function testReturnsFromCacheWhenCallableThrows(): void
    {
        $cached_val = random_int(1, 100);
        // this sets value in cache
        $this->cache->get('key', fn() => $cached_val);
        $res = $this->cache->get('key', fn() => throw new \RuntimeException());

        self::assertEquals($cached_val, $res, "Should return value from Cache");
    }

    public function testReThrowsWhenCacheHasNothing(): void
    {
        $exception = new \RuntimeException("nothing in cache");

        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage("nothing in cache");
        $this->cache->get('key', fn() => throw $exception);
    }

    public function testDeleteRemovesFallback(): void
    {
        $cached_val = random_int(1, 100);
        // this sets value in cache
        $this->cache->get('key', fn() => $cached_val);
        $this->cache->delete('key');
        $exception = new \RuntimeException("fallback removed");
        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage("fallback removed");
        $this->cache->get('key', fn() => throw $exception);
    }

    public function testUsesSecondaryCallableForFallback(): void
    {
        $exception = new \RuntimeException("nothing in cache");

        $res = $this->cache->getWithSecondary('key', fn() => throw $exception, fn() => 'foo');
        self::assertEquals('foo', $res);
    }

    public function testGetWithSecondaryReturnsFromCallable(): void
    {
        $val = random_int(1, 100);
        $res = $this->cache->getWithSecondary('key', fn() => $val, fn() => throw new \RuntimeException('nope'));
        self::assertEquals($val, $res, "Should return value from Callable");
    }

    public function testGetWithSecondaryReturnsFromCallableEvenWhenCached(): void
    {
        $cached_val = 105;
        // this sets value in cache
        $this->cache->get('key', fn() => $cached_val);
        $val = random_int(1, 100);
        $res = $this->cache->getWithSecondary('key', fn() => $val, fn() => throw new \RuntimeException('nope'));
        self::assertEquals($val, $res, "Should return value from Callable");
    }

    public function testGetWithSecondaryReturnsFromCacheWhenCallableThrows(): void
    {
        $cached_val = random_int(1, 100);
        // this sets value in cache
        $this->cache->get('key', fn() => $cached_val);
        $res = $this->cache->getWithSecondary('key', fn() => throw new \RuntimeException(), fn() => throw new \RuntimeException('nope'));

        self::assertEquals($cached_val, $res, "Should return value from Cache");
    }

    public function testGetWithSecondaryReThrowsWhenCacheHasNothing(): void
    {
        $exception = new \RuntimeException("nothing in cache");

        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage("secondary throw");
        $this->cache->getWithSecondary('key', fn() => throw $exception, fn () => throw new \RuntimeException('secondary throw'));
    }

}
