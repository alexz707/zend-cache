<?php
/**
 * @see       https://github.com/zendframework/zend-cache for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-cache/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Cache\Psr\CacheItemPool;

use Cache\IntegrationTests\CachePoolTest;
use Zend\Cache\Psr\CacheItemPool\CacheItemPoolDecorator;
use Zend\Cache\Storage\Adapter\Redis;
use Zend\Cache\Storage\Plugin\Serializer;
use Zend\Cache\StorageFactory;
use Zend\Cache\Exception;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;

class RedisIntegrationTest extends CachePoolTest
{
    /**
     * Backup default timezone
     * @var string
     */
    private $tz;

    /**
     * @var Redis
     */
    private $storage;

    protected function setUp()
    {
        if (! getenv('TESTS_ZEND_CACHE_REDIS_ENABLED')) {
            $this->markTestSkipped('Enable TESTS_ZEND_CACHE_REDIS_ENABLED to run this test');
        }

        // set non-UTC timezone
        $this->tz = date_default_timezone_get();
        date_default_timezone_set('America/Vancouver');

        parent::setUp();
    }

    protected function tearDown()
    {
        date_default_timezone_set($this->tz);

        if ($this->storage) {
            $this->storage->flush();
        }

        parent::tearDown();
    }

    public function createCachePool()
    {
        $options = ['resource_id' => __CLASS__];

        if (getenv('TESTS_ZEND_CACHE_REDIS_HOST') && getenv('TESTS_ZEND_CACHE_REDIS_PORT')) {
            $options['server'] = [getenv('TESTS_ZEND_CACHE_REDIS_HOST'), getenv('TESTS_ZEND_CACHE_REDIS_PORT')];
        } elseif (getenv('TESTS_ZEND_CACHE_REDIS_HOST')) {
            $options['server'] = [getenv('TESTS_ZEND_CACHE_REDIS_HOST')];
        }

        if (getenv('TESTS_ZEND_CACHE_REDIS_DATABASE')) {
            $options['database'] = getenv('TESTS_ZEND_CACHE_REDIS_DATABASE');
        }

        if (getenv('TESTS_ZEND_CACHE_REDIS_PASSWORD')) {
            $options['password'] = getenv('TESTS_ZEND_CACHE_REDIS_PASSWORD');
        }

        try {
            $storage = StorageFactory::adapterFactory('redis', $options);
            $storage->addPlugin(new Serializer());

            $deferredSkippedMessage = sprintf(
                '%s storage doesn\'t support driver deferred',
                \get_class($storage)
            );
            $this->skippedTests['testHasItemReturnsFalseWhenDeferredItemIsExpired'] = $deferredSkippedMessage;

            return new CacheItemPoolDecorator($storage);
        } catch (Exception\ExtensionNotLoadedException $e) {
            $this->markTestSkipped($e->getMessage());
        } catch (ServiceNotCreatedException $e) {
            if ($e->getPrevious() instanceof Exception\ExtensionNotLoadedException) {
                $this->markTestSkipped($e->getMessage());
            }
            throw $e;
        }
    }
}
