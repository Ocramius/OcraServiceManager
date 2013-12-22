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
use UnexpectedValueException;
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
    public function testCreateLoggedServiceManager()
    {
        $factory        = new ServiceManagerFactory();
        $serviceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $proxyLocator   = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $proxyFactory   = $this
            ->getMockBuilder('ProxyManager\Factory\AccessInterceptorScopeLocalizerFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $proxyFactory
            ->expects($this->any())
            ->method('createProxy')
            ->with($serviceLocator, array(), array('foo', 'bar'))
            ->will($this->returnValue($proxyLocator));

        $serviceLocator
            ->expects($this->any())
            ->method('get')
            ->will(
                $this->returnCallback(
                    function ($service) use ($proxyFactory) {
                        if ('OcraServiceManager\\ServiceManager\\AccessInterceptorProxyFactory' === $service) {
                            return $proxyFactory;
                        }

                        if ('OcraServiceManager\\ServiceManager\\AccessInterceptors' === $service) {
                            return array('foo', 'bar');
                        }

                        if ('Config' === $service) {
                            return array(
                                'ocra_service_manager' => array(
                                    'logged_service_manager' => true,
                                ),
                                'service_manager' => array(
                                    'lazy_services' => array(),
                                ),
                            );
                        }

                        throw new UnexpectedValueException(sprintf('Unknown service "%s"', $service));
                    }
                )
            );

        $this->assertSame($proxyLocator, $factory->createService($serviceLocator));
    }

    /**
     * @covers \OcraServiceManager\ServiceFactory\ServiceManagerFactory::createService
     */
    public function testOverridesServiceManagerService()
    {
        $factory        = new ServiceManagerFactory();
        $serviceLocator = $this->getMock('Zend\ServiceManager\ServiceManager');
        $proxyLocator   = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $proxyFactory   = $this
            ->getMockBuilder('ProxyManager\Factory\AccessInterceptorScopeLocalizerFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $proxyFactory
            ->expects($this->any())
            ->method('createProxy')
            ->with($serviceLocator, array(), array('foo', 'bar'))
            ->will($this->returnValue($proxyLocator));

        $serviceLocator->expects($this->once())->method('setService')->with('ServiceManager', $proxyLocator);

        $serviceLocator
            ->expects($this->any())
            ->method('get')
            ->will(
                $this->returnCallback(
                    function ($service) use ($proxyFactory) {
                        if ('OcraServiceManager\\ServiceManager\\AccessInterceptorProxyFactory' === $service) {
                            return $proxyFactory;
                        }

                        if ('OcraServiceManager\\ServiceManager\\AccessInterceptors' === $service) {
                            return array('foo', 'bar');
                        }

                        if ('Config' === $service) {
                            return array(
                                'ocra_service_manager' => array(
                                    'logged_service_manager' => true,
                                ),
                                'service_manager' => array(
                                    'lazy_services' => array(),
                                ),
                            );
                        }

                        throw new UnexpectedValueException(sprintf('Unknown service "%s"', $service));
                    }
                )
            );

        $this->assertSame($proxyLocator, $factory->createService($serviceLocator, 'OcraServiceManager\ServiceManager'));
    }

    /**
     * @covers \OcraServiceManager\ServiceFactory\ServiceManagerFactory::createService
     */
    public function testSkipsIfLoggedServiceManagerIsDisabled()
    {
        $factory        = new ServiceManagerFactory();
        $serviceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');

        $serviceLocator
            ->expects($this->any())
            ->method('get')
            ->with('Config')
            ->will($this->returnValue(array(
                'ocra_service_manager' => array(
                    'logged_service_manager' => false,
                ),
            )));

        $this->assertSame($serviceLocator, $factory->createService($serviceLocator));
    }
}
