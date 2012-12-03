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
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\Cache\Storage\Adapter\Memory;

use PHPUnit_Framework_TestCase;

use OcraServiceManagerTest\TestAsset\HelloWorld;
use OcraServiceManagerTest\TestAsset\ServiceLocatorAwareHelloWorld;

/**
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class ProxyPerformanceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        // @codeCoverageIgnoreStart
        $proxyFactory   = new ServiceProxyAbstractFactory(new Memory());
        $this->serviceManager = $serviceManager = new ServiceManager(new BaseServiceManager());
        $serviceManager->setService('OcraServiceManager\Proxy\ServiceProxyAbstractFactory', $proxyFactory);
        $serviceManager->setFactory(
            'HelloWorld',
            function () { return new HelloWorld(); },
            false
        );
        $serviceManager->setFactory(
            'ServiceLocatorAwareHelloWorld',
            function () { return new ServiceLocatorAwareHelloWorld(); },
            false
        );
        $serviceManager->addInitializer(function ($instance) use ($serviceManager) {
            if ($instance instanceof ServiceLocatorAwareInterface) {
                $instance->setServiceLocator($serviceManager);
            }
        });
        $serviceManager->setFactory(
            'ProxyHelloWorld',
            function () { return new HelloWorld(); },
            false
        );
        $serviceManager->setProxyService('ProxyHelloWorld');

        // first instantiation to avoid measuring first overhead
        $helloWorld = $serviceManager->create('HelloWorld');
        $this->assertInstanceOf('OcraServiceManagerTest\\TestAsset\\HelloWorld', $helloWorld);
        $this->assertSame('Hello World!', $helloWorld->sayHello());

        $helloWorld = $serviceManager->create('ServiceLocatorAwareHelloWorld');
        $this->assertInstanceOf('OcraServiceManagerTest\\TestAsset\\ServiceLocatorAwareHelloWorld', $helloWorld);
        $this->assertSame('Hello World!', $helloWorld->sayHello());

        $helloWorld = $serviceManager->create('ProxyHelloWorld');
        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $helloWorld);
        $this->assertSame('Hello World!', $helloWorld->sayHello());
        // @codeCoverageIgnoreEnd
    }

    public function testProxyInstantiationPerformance()
    {
        // @codeCoverageIgnoreStart
        $iterations                = 1000;
        $baseLineInstantiationTime = 0;
        $slAwareInstantiationTime  = 0;
        $proxyInstantiationTime    = 0;

        for ($i = 0; $i < $iterations; $i += 1) {
            $start = microtime(true);
            $this->serviceManager->create('HelloWorld');
            $baseLineInstantiationTime += microtime(true) - $start;

            $start = microtime(true);
            $this->serviceManager->create('ServiceLocatorAwareHelloWorld');
            $slAwareInstantiationTime += microtime(true) - $start;

            $start = microtime(true);
            $this->serviceManager->create('ProxyHelloWorld');
            $proxyInstantiationTime += microtime(true) - $start;
        }

        var_dump('Instantiating ' . $iterations . ' base objects:', $baseLineInstantiationTime);
        var_dump('Instantiating ' . $iterations . ' service locator aware objects:', $slAwareInstantiationTime);
        var_dump('Instantiating ' . $iterations . ' proxy objects:', $proxyInstantiationTime);
        // @codeCoverageIgnoreEnd
    }

    public function testProxyInitializationPerformance()
    {
        // @codeCoverageIgnoreStart
        $iterations                = 1000;
        $baseLineCallTime          = 0;
        $slAwareInitializationTime = 0;
        $proxyInitializationTime   = 0;

        for ($i = 0; $i < $iterations; $i += 1) {
            $helloWorld = $this->serviceManager->create('HelloWorld');
            $start = microtime(true);
            $helloWorld->sayHello();
            $baseLineCallTime += microtime(true) - $start;

            $helloWorld = $this->serviceManager->create('ServiceLocatorAwareHelloWorld');
            $start = microtime(true);
            $helloWorld->sayHello();
            $slAwareInitializationTime += microtime(true) - $start;

            $helloWorld = $this->serviceManager->create('ProxyHelloWorld');
            $start = microtime(true);
            $helloWorld->sayHello();
            $proxyInitializationTime += microtime(true) - $start;
        }

        var_dump('Initializing ' . $iterations . ' base objects:', $baseLineCallTime);
        var_dump('Initializing ' . $iterations . ' service locator aware objects:', $slAwareInitializationTime);
        var_dump('Initializing ' . $iterations . ' proxy objects:', $proxyInitializationTime);
        // @codeCoverageIgnoreEnd
    }

    public function testInitializedProxyCallTime()
    {
        // @codeCoverageIgnoreStart
        $iterations                = 1000;
        $baseLineCallTime          = 0;
        $slAwareInitializationTime = 0;
        $proxyInitializationTime   = 0;

        for ($i = 0; $i < $iterations; $i += 1) {
            $helloWorld = $this->serviceManager->create('HelloWorld');
            $start = microtime(true);
            $helloWorld->sayHello();
            $baseLineCallTime += microtime(true) - $start;

            $helloWorld = $this->serviceManager->create('ServiceLocatorAwareHelloWorld');
            $helloWorld->sayHello();
            $start = microtime(true);
            $helloWorld->sayHello();
            $slAwareInitializationTime += microtime(true) - $start;

            $helloWorld = $this->serviceManager->create('ProxyHelloWorld');
            $helloWorld->sayHello();
            $start = microtime(true);
            $helloWorld->sayHello();
            $proxyInitializationTime += microtime(true) - $start;
        }

        var_dump('Calling on ' . $iterations . ' base objects:', $baseLineCallTime);
        var_dump(
            'Calling on ' . $iterations . ' initialized service locator aware objects:',
            $slAwareInitializationTime
        );
        var_dump('Calling on ' . $iterations . ' initialized proxy objects:', $proxyInitializationTime);
        // @codeCoverageIgnoreEnd
    }
}
