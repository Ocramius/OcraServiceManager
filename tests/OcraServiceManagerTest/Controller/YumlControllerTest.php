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

namespace OcraServiceManagerTest\ServiceManager;

use OcraServiceManager\Controller\YumlController;

class YumlControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var YumlController
     */
    protected $controller;

    /**
     * @var \Zend\ServiceManager\ServiceLocatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $serviceLocator;

    /**
     * @var \Zend\Mvc\Controller\PluginManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $pluginManager;

    /**
     * {@inheritDoc}
     *
     * @covers \OcraServiceManager\Controller\YumlController
     */
    public function setUp()
    {
        $this->serviceLocator = $this->getMock('Zend\\ServiceManager\\ServiceLocatorInterface');
        $this->controller     = new YumlController();
        $this->pluginManager  = $this->getMock('Zend\\Mvc\\Controller\\PluginManager');
        $this->controller->setServiceLocator($this->serviceLocator);
        $this->controller->setPluginManager($this->pluginManager);
    }

    /**
     * @covers \OcraServiceManager\Controller\YumlController::setHttpClient
     * @covers \OcraServiceManager\Controller\YumlController::getHttpClient
     */
    public function testSetGetClient()
    {
        $this->assertInstanceOf('Zend\\Http\\Client', $this->controller->getHttpClient());
        $client = $this->getMock('Zend\\Http\\Client');
        $this->controller->setHttpClient($client);
        $this->assertSame($client, $this->controller->getHttpClient());
    }

    /**
     * @covers \OcraServiceManager\Controller\YumlController::indexAction
     */
    public function testIndexActionWillNotDispatchOnDisabledToolbar()
    {
        $this->serviceLocator->expects($this->any())->method('get')->with('Config')->will($this->returnValue(array(
            'ocra_service_manager' => array(
                'logged_service_manager' => true,
            ),
        )));

        $this->setExpectedException('BadMethodCallException');
        $this->controller->indexAction();
    }

    /**
     * @covers \OcraServiceManager\Controller\YumlController::indexAction
     */
    public function testIndexActionWillRedirectToYuml()
    {
        $this->serviceLocator->expects($this->any())->method('get')->with('Config')->will($this->returnValue(array(
            'ocra_service_manager' => array(
                'logged_service_manager' => true,
            ),
            'zenddevelopertools' => array(
                'toolbar' => array(
                    'enabled' => true,
                ),
            ),
        )));
        $client = $this->getMock('Zend\\Http\\Client');
        $response = $this->getMock('Zend\\Http\\Response');
        $controllerResponse = $this->getMock('Zend\\Http\\Response');
        $redirect = $this->getMock('Zend\\Mvc\\Controller\\Plugin\\Redirect');
        $client->expects($this->any())->method('send')->will($this->returnValue($response));
        $response->expects($this->any())->method('isSuccess')->will($this->returnValue(true));
        $response->expects($this->any())->method('getBody')->will($this->returnValue('short-url'));
        $this
            ->pluginManager
            ->expects($this->any())
            ->method('get')->with('redirect')
            ->will($this->returnValue($redirect));
        $redirect
            ->expects($this->any())
            ->method('toUrl')
            ->with('http://yuml.me/short-url')
            ->will($this->returnValue($controllerResponse));
        $this->controller->setHttpClient($client);

        $this->assertSame($controllerResponse, $this->controller->indexAction());
    }

    /**
     * @covers \OcraServiceManager\Controller\YumlController::indexAction
     */
    public function testIndexActionWillFailOnMalformedResponse()
    {
        $this->serviceLocator->expects($this->any())->method('get')->with('Config')->will($this->returnValue(array(
            'ocra_service_manager' => array(
                'logged_service_manager' => true,
            ),
            'zenddevelopertools' => array(
                'toolbar' => array(
                    'enabled' => true,
                ),
            ),
        )));
        $client = $this->getMock('Zend\\Http\\Client');
        $response = $this->getMock('Zend\\Http\\Response');
        $client->expects($this->any())->method('send')->will($this->returnValue($response));
        $response->expects($this->any())->method('isSuccess')->will($this->returnValue(false));
        $this->controller->setHttpClient($client);

        $this->setExpectedException('UnexpectedValueException');
        $this->controller->indexAction();
    }
}
