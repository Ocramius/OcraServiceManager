<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace OcraServiceManagerTest\Proxy;

use OcraServiceManager\Proxy\ServiceProxyAbstractFactory;
use OcraServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManager as BaseServiceManager;
use Zend\Cache\Storage\Adapter\Memory;

use PHPUnit_Framework_TestCase;

use OcraServiceManagerTest\TestAsset\LazyService;
use OcraServiceManagerTest\TestAsset\PublicPropertiesLazyService;

/**
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class ServiceProxyAbstractFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceProxyAbstractFactory
     */
    protected $factory;

    /**
     * @var Memory
     */
    protected $cache;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->cache   = new Memory();
        $this->factory = new ServiceProxyAbstractFactory($this->cache);
    }

    /**
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::canCreateServiceWithName
     */
    public function testWillNotCreateProxiesFromGenericServiceLocators()
    {
        $sm = new ServiceManager(new BaseServiceManager());
        $this->assertTrue($this->factory->canCreateServiceWithName($sm, 'name', 'name'));

        $sl = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $this->assertFalse($this->factory->canCreateServiceWithName($sl, 'name', 'name'));
    }

    /**
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::createServiceWithName
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::generateProxyDefinitions
     * @covers \OcraServiceManager\Proxy\ServiceProxyGenerator
     */
    public function testCreateServiceWithNameFetchesServiceOnlyWhenProxyDefinitionIsUnknown()
    {
        $service = new \stdClass();
        $sm = $this->getMock('OcraServiceManager\ServiceManager', array(), array(), '', false);
        $sm
            ->expects($this->once())
            ->method('createRealService')
            ->with(array('std-class-service', 'std-class-service'))
            ->will($this->returnValue($service));

        // first code generation
        $proxy = $this->factory->createServiceWithName($sm, 'std-class-service', 'std-class-service');
        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $proxy);
        $this->assertInstanceOf('stdClass', $proxy);
        $proxy->__load(); // we know that it will be already loaded, but we won't make this assumption here
        $this->assertSame($service, $proxy->__wrappedObject__);

        // cached - doesn't trigger proxy generation anymore
        $uninitializedProxy = $this->factory->createServiceWithName($sm, 'std-class-service', 'std-class-service');
        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $uninitializedProxy);
        $this->assertInstanceOf('stdClass', $uninitializedProxy);
        $this->assertNotSame($proxy, $uninitializedProxy);
        $this->assertFalse($uninitializedProxy->__isInitialized());
        $this->assertNull($uninitializedProxy->__wrappedObject__);
    }

    /**
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::createServiceWithName
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::generateProxyDefinitions
     * @covers \OcraServiceManager\Proxy\ServiceProxyGenerator
     */
    public function testCanCreateServiceWithNameProducesLazyLoadingService()
    {
        $lazyService = new LazyService();
        $sm = $this->getMock('OcraServiceManager\ServiceManager', array(), array(), '', false);
        $sm
            ->expects($this->any())
            ->method('createRealService')
            ->with(array('lazy-service', 'lazy-service'))
            ->will($this->returnValue($lazyService));

        // first code generation - required to avoid fetching an initialized proxy
        $this->factory->createServiceWithName($sm, 'lazy-service', 'lazy-service');

        $uninitializedProxy = $this->factory->createServiceWithName($sm, 'lazy-service', 'lazy-service');
        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $uninitializedProxy);
        $this->assertInstanceOf('OcraServiceManagerTest\TestAsset\LazyService', $uninitializedProxy);
        $this->assertFalse($uninitializedProxy->__isInitialized());
        $this->assertNull($uninitializedProxy->__wrappedObject__);

        $uninitializedProxy->increment();
        $this->assertTrue($uninitializedProxy->__isInitialized(), 'Proxy was initialized at first method call');
        $lazyService->increment();
        $this->assertSame(2, $uninitializedProxy->count(), 'Proxy correctly calls wrapped object');
        $this->assertSame(2, $lazyService->count());
    }

    /**
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::createServiceWithName
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::generateProxyDefinitions
     * @covers \OcraServiceManager\Proxy\ServiceProxyGenerator
     */
    public function testProxyInitializationReferencesOriginalService()
    {
        $service = new \stdClass();
        $sm = $this->getMock('OcraServiceManager\ServiceManager', array(), array(), '', false);
        $sm
            ->expects($this->any())
            ->method('createRealService')
            ->with(array('std-class-service', 'std-class-service'))
            ->will($this->returnValue($service));

        // first code generation - required to avoid fetching an initialized proxy
        $this->factory->createServiceWithName($sm, 'std-class-service', 'std-class-service');

        $proxy = $this->factory->createServiceWithName($sm, 'std-class-service', 'std-class-service');
        $proxy->__load();
        $this->assertSame($proxy->__wrappedObject__, $service);
    }

    /**
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::createServiceWithName
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::generateProxyDefinitions
     * @covers \OcraServiceManager\Proxy\ServiceProxyGenerator
     */
    public function testProxyGenerationProducesInitializedProxyAtFirstRun()
    {
        $service = $this->getMock('stdClass');
        $sm = $this->getMock('OcraServiceManager\ServiceManager', array(), array(), '', false);
        $sm
            ->expects($this->any())
            ->method('createRealService')
            ->with(array('std-class-service', 'std-class-service'))
            ->will($this->returnValue($service));

        $proxy = $this->factory->createServiceWithName($sm, 'std-class-service', 'std-class-service');
        $this->assertTrue($proxy->__isInitialized());
        $this->assertSame($service, $proxy->__wrappedObject__);
    }

    /**
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::setProxyGenerator
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::getProxyGenerator
     */
    public function testSetGetProxyGenerator()
    {
        $generator = $this->factory->getProxyGenerator();
        $this->assertInstanceOf('OcraServiceManager\Proxy\ServiceProxyGenerator', $generator);

        $mockGenerator = $this->getMock('OcraServiceManager\Proxy\ServiceProxyGenerator');
        $this->factory->setProxyGenerator($mockGenerator);
        $this->assertSame($mockGenerator, $this->factory->getProxyGenerator());
    }

    /**
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::createServiceWithName
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::generateProxyDefinitions
     * @covers \OcraServiceManager\Proxy\ServiceProxyGenerator
     */
    public function testCloneInitializedService()
    {
        $lazyService = new LazyService();
        $lazyService->increment();
        $sm = $this->getMock('OcraServiceManager\ServiceManager', array(), array(), '', false);
        $sm
            ->expects($this->any())
            ->method('createRealService')
            ->with(array('lazy-service', 'lazy-service'))
            ->will($this->returnValue($lazyService));

        // first code generation - required to avoid fetching an initialized proxy
        $this->factory->createServiceWithName($sm, 'lazy-service', 'lazy-service');

        $proxy = $this->factory->createServiceWithName($sm, 'lazy-service', 'lazy-service');
        $proxy->__load();

        $proxy->increment();
        $this->assertSame($lazyService->count(), $proxy->count());

        $cloned = clone $proxy;
        $this->assertSame($proxy->count(), $cloned->count());
        $this->assertNotSame($proxy->__wrappedObject__, $cloned->__wrappedObject__);

        $proxy->increment();
        $this->assertSame($proxy->count() - 1, $cloned->count());

        $cloned->increment();
        $this->assertSame($proxy->count(), $cloned->count());
    }

    /**
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::createServiceWithName
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::generateProxyDefinitions
     * @covers \OcraServiceManager\Proxy\ServiceProxyGenerator
     */
    public function testCloneUninitializedService()
    {
        $lazyService = new LazyService();
        $lazyService->increment();
        $sm = $this->getMock('OcraServiceManager\ServiceManager', array(), array(), '', false);
        $sm
            ->expects($this->any())
            ->method('createRealService')
            ->with(array('lazy-service', 'lazy-service'))
            ->will($this->returnValue($lazyService));

        // first code generation - required to avoid fetching an initialized proxy
        $this->factory->createServiceWithName($sm, 'lazy-service', 'lazy-service');

        $proxy = $this->factory->createServiceWithName($sm, 'lazy-service', 'lazy-service');
        $this->assertFalse($proxy->__isInitialized());

        $cloned = clone $proxy;
        $this->assertSame($proxy->count(), $cloned->count());
        $this->assertNotSame($proxy->__wrappedObject__, $cloned->__wrappedObject__);

        $proxy->increment();
        $this->assertSame($proxy->count() - 1, $cloned->count());

        $cloned->increment();
        $this->assertSame($proxy->count(), $cloned->count());
    }

    /**
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::createServiceWithName
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::generateProxyDefinitions
     * @covers \OcraServiceManager\Proxy\ServiceProxyGenerator
     */
    public function testProxyMagicGetterSetterIssetter()
    {
        $lazyService = new PublicPropertiesLazyService();

        $sm = $this->getMock('OcraServiceManager\ServiceManager', array(), array(), '', false);
        $sm
            ->expects($this->any())
            ->method('createRealService')
            ->with(array('lazy-service', 'lazy-service'))
            ->will($this->returnValue($lazyService));

        // first code generation - required to avoid fetching an initialized proxy
        $this->factory->createServiceWithName($sm, 'lazy-service', 'lazy-service');

        $proxy = $this->factory->createServiceWithName($sm, 'lazy-service', 'lazy-service');
        $this->assertFalse($proxy->__isInitialized());

        // checking `__get`
        $this->assertSame('checkedPropertyValue', $proxy->checkedProperty);
        $lazyService->checkedProperty = 'newValue';
        $this->assertSame('newValue', $proxy->checkedProperty);

        // checking `__set`
        $proxy->checkedProperty = 'otherValue';
        $this->assertSame('otherValue', $lazyService->checkedProperty);

        // checking `__isset`
        $this->assertTrue(isset($proxy->checkedProperty));
        $lazyService->checkedProperty = null;
        $this->assertFalse(isset($proxy->checkedProperty));
    }

    /**
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::createServiceWithName
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::generateProxyDefinitions
     * @covers \OcraServiceManager\Proxy\ServiceProxyGenerator
     */
    public function testSerializeUninitializedProxy()
    {
        $lazyService = new PublicPropertiesLazyService();
        $lazyService->checkedProperty = 'serializedValue';

        $sm = $this->getMock('OcraServiceManager\ServiceManager', array(), array(), '', false);
        $sm
            ->expects($this->any())
            ->method('createRealService')
            ->with(array('lazy-service', 'lazy-service'))
            ->will($this->returnValue($lazyService));

        // first code generation - required to avoid fetching an initialized proxy
        $this->factory->createServiceWithName($sm, 'lazy-service', 'lazy-service');

        $proxy = $this->factory->createServiceWithName($sm, 'lazy-service', 'lazy-service');
        $this->assertFalse($proxy->__isInitialized());

        $unserialized = unserialize(serialize($proxy));

        $lazyService->checkedProperty = 'changedValue';
        $this->assertSame('serializedValue', $unserialized->checkedProperty);
        $this->assertNotSame($lazyService, $unserialized->__wrappedObject__);

        $proxy->checkedProperty = 'againChangedValue';
        $this->assertSame('serializedValue', $unserialized->checkedProperty);

        $unserialized->checkedProperty = 'serializedProxyChangedValue';
        $this->assertSame('againChangedValue', $lazyService->checkedProperty);
    }

    /**
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::createServiceWithName
     * @covers \OcraServiceManager\Proxy\ServiceProxyAbstractFactory::generateProxyDefinitions
     * @covers \OcraServiceManager\Proxy\ServiceProxyGenerator
     */
    public function testCachedServiceProxyName()
    {
        $lazyService = new PublicPropertiesLazyService();
        $lazyService->checkedProperty = 'serializedValue';

        $sm = $this->getMock('OcraServiceManager\ServiceManager', array(), array(), '', false);
        $sm
            ->expects($this->any())
            ->method('createRealService')
            ->with(array('lazy-service', 'lazy-service'))
            ->will($this->returnValue($lazyService));

        // first code generation - required to avoid fetching an initialized proxy
        $this->factory->createServiceWithName($sm, 'lazy-service', 'lazy-service');

        // cache is now filled, let's re-use it and verify that the proxy is not initialized

        $factory = new ServiceProxyAbstractFactory($this->cache);
        $proxy = $factory->createServiceWithName($sm, 'lazy-service', 'lazy-service');
        $this->assertFalse($proxy->__isInitialized());
    }
}
