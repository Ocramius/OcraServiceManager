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

namespace OcraServiceManagerTest\ServiceFactory;

use OcraServiceManager\ServiceFactory\ServiceProxyAbstractFactoryFactory;
use Zend\ServiceManager\ServiceManager;

use PHPUnit_Framework_TestCase;

/**
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class ServiceProxyAbstractFactoryFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers \OcraServiceManager\ServiceFactory\ServiceProxyAbstractFactoryFactory::createService
     */
    public function testCreateService()
    {
        $factory = new ServiceProxyAbstractFactoryFactory();
        $sm = new ServiceManager();
        $sm->setService('Config', array(
            'service_proxies_dir' => __DIR__,
            'service_proxies_ns'  => 'TestNs',
        ));
        $proxyFactory = $factory->createService($sm);


        $this->assertInstanceOf('OcraServiceManager\Proxy\ServiceProxyAbstractFactory', $proxyFactory);
        // verifying generator
        $proxyClass = $proxyFactory->getProxyGenerator()->getProxyClassName('TestClass');
        $this->assertSame('TestNs\__CG__\TestClass', $proxyClass);
        $proxyFile = $proxyFactory->getProxyGenerator()->getProxyFileName('TestClass');
        $this->assertSame(__DIR__ . '/__CG__TestClass.php', $proxyFile);

        // cleaning up
        $autoloaders = spl_autoload_functions();
        spl_autoload_unregister(end($autoloaders));
    }
}
