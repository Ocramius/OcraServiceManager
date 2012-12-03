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

namespace OcraServiceManagerTest;

use OcraServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManager as BaseServiceManager;
use PHPUnit_Framework_TestCase;
use Zend\Cache\Storage\Adapter\Memory;
use OcraServiceManager\Proxy\ServiceProxyAbstractFactory;

/**
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class ServiceManagerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var BaseServiceManager
     */
    protected $baseServiceManager;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * {@inheritDoc}
     *
     * @covers \OcraServiceManager\ServiceManager::__construct
     */
    public function setup()
    {
        $this->baseServiceManager = new BaseServiceManager();
        $this->serviceManager = new ServiceManager($this->baseServiceManager);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager::setProxyService
     * @covers \OcraServiceManager\ServiceManager::getProxyServices
     */
    public function testSetProxyService()
    {
        $this->serviceManager->setProxyService('first-service');
        $this->serviceManager->setProxyService('second-service', 'assigned-factory');

        $proxied = $this->serviceManager->getProxyServices();

        $this->assertCount(2, $proxied);
        $this->assertSame('OcraServiceManager\Proxy\ServiceProxyAbstractFactory', $proxied['firstservice']);
        $this->assertSame('assigned-factory', $proxied['secondservice']);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager::setProxyService
     * @covers \OcraServiceManager\ServiceManager::create
     * @covers \OcraServiceManager\ServiceManager::createRealService
     */
    public function testProxiedLazyService()
    {
        $this->serviceManager->setService(
            'OcraServiceManager\Proxy\ServiceProxyAbstractFactory',
            new ServiceProxyAbstractFactory(new Memory())
        );
        $service = $this->getMock('stdClass', array('proxiedMethod'));
        $service->expects($this->once())->method('proxiedMethod')->will($this->returnValue('proxiedValue'));

        $this->serviceManager->setService('Config', array());
        $this->serviceManager->setService('OcraServiceManager\\Cache\\ServiceProxiesCache', new Memory());
        $this->serviceManager->setFactory('LazyService', function () use ($service) { return $service; });
        $this->serviceManager->setProxyService('LazyService');
        $lazyService = $this->serviceManager->create('LazyService');

        $this->assertInstanceOf(get_class($service), $lazyService);
        $this->assertNotSame($service, $lazyService);
        $this->assertSame('proxiedValue', $lazyService->proxiedMethod());
    }

    /**
     * @covers \OcraServiceManager\ServiceManager::setDefaultProxyFactory
     * @covers \OcraServiceManager\ServiceManager::setProxyService
     * @covers \OcraServiceManager\ServiceManager::create
     */
    public function testSetProxyServiceWithCustomFactory()
    {
        $this->serviceManager->setDefaultProxyFactory('default-proxy-factory');
        $proxyFactory = $this->getMock('Zend\\ServiceManager\\AbstractFactoryInterface');

        $this->serviceManager->setService('default-proxy-factory', $proxyFactory);

        $service = $this->getMock('stdClass');

        $proxyFactory
            ->expects($this->once())
            ->method('createServiceWithName')
            ->with($this->serviceManager, 'lazyservice', 'LazyService')
            ->will($this->returnValue($service));

        $this->serviceManager->setProxyService('LazyService');
        $lazyService = $this->serviceManager->create('LazyService');

        $this->assertSame($service, $lazyService);
    }

    public function testProxyingParentServiceManager()
    {
        $service1 = new \stdClass();

        $this->serviceManager->setService('service1', $service1);
        $this->assertSame($service1, $this->baseServiceManager->get('service1'));

        $this->assertSame($this->serviceManager, $this->baseServiceManager->get('ServiceManager'));

        $service2 = new \stdClass();
        $this->baseServiceManager->setService('service2', $service2);
        $this->assertSame($service2, $this->serviceManager->get('service2'));
    }
}
