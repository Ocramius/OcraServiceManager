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

use OcraServiceManager\ServiceFactory\ServiceManagerFactory;
use Zend\ServiceManager\ServiceManager;

use PHPUnit_Framework_TestCase;

/**
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class ServiceManagerFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers \OcraServiceManager\ServiceFactory\ServiceManagerFactory::createService
     */
    public function testCreateService()
    {
        $factory        = new ServiceManagerFactory();
        $serviceManager = new ServiceManager();

        $serviceManager->setService(
            'Config',
            array(
                'ocra_service_manager' => array(
                    'logged_service_manager' => false,
                ),
                'service_manager' => array(
                    'lazy_services' => array(
                        'first-service',
                        'second-service',
                        'third-service' => 'with-factory',
                    ),
                ),
            )
        );

        /* @var $service \OcraServiceManager\ServiceManager */
        $service = $factory->createService($serviceManager);

        $this->assertInstanceOf('OcraServiceManager\\ServiceManager', $service);

        $proxyServices = $service->getProxyServices();

        $this->assertCount(3, $proxyServices);
        $this->assertSame('OcraServiceManager\Proxy\ServiceProxyAbstractFactory', $proxyServices['firstservice']);
        $this->assertSame('OcraServiceManager\Proxy\ServiceProxyAbstractFactory', $proxyServices['secondservice']);
        $this->assertSame('with-factory', $proxyServices['thirdservice']);
    }
    /**
     * @covers \OcraServiceManager\ServiceFactory\ServiceManagerFactory::createService
     */
    public function testCreateLoggedServiceManager()
    {
        $factory        = new ServiceManagerFactory();
        $serviceManager = new ServiceManager();

        $serviceManager->setService(
            'Config',
            array(
                'ocra_service_manager' => array(
                    'logged_service_manager' => true,
                ),
                'service_manager' => array(
                    'lazy_services' => array(),
                ),
            )
        );
        $serviceManager->setService(
            'OcraServiceManager\\ServiceManager\\EventManager',
            $this->getMock('Zend\\EventManager\\EventManagerInterface')
        );

        /* @var $service \OcraServiceManager\ServiceManager */
        $service = $factory->createService($serviceManager);

        $this->assertInstanceOf('OcraServiceManager\\ServiceManager\\LoggedServiceManager', $service);
    }
}
