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

use OcraServiceManager\ServiceFactory\ApplicationFactory;
use Zend\ServiceManager\ServiceManager;

use PHPUnit_Framework_TestCase;

/**
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class ApplicationFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers \OcraServiceManager\ServiceFactory\ApplicationFactory::createService
     */
    public function testCreateService()
    {
        $factory                = new ApplicationFactory();
        $serviceManager         = new ServiceManager();
        $replacedServiceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $request                = $this->getMock('Zend\StdLib\RequestInterface');
        $response               = $this->getMock('Zend\StdLib\ResponseInterface');
        $evm                    = $this->getMock('Zend\EventManager\EventManagerInterface');

        $replacedServiceManager
            ->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($name) use ($request, $response, $evm) {
                $services = array(
                    'Request'      => $request,
                    'Response'     => $response,
                    'EventManager' => $evm,
                );

                return $services[$name];
            }));

        $serviceManager->setService('Config', array());
        $serviceManager->setService('OcraServiceManager\\ServiceManager', $replacedServiceManager);

        $application = $factory->createService($serviceManager);

        $appServiceManager  = $application->getServiceManager();
        $this->assertSame($replacedServiceManager, $appServiceManager);
    }
}
