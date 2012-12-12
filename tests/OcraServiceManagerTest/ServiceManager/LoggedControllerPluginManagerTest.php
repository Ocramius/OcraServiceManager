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

use OcraServiceManager\ServiceManager\LoggedControllerPluginManager;
use OcraServiceManager\ServiceManager\Logger;
use Zend\Mvc\Controller\PluginManager;

class LoggedControllerPluginManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggedControllerPluginManager
     */
    protected $pluginManager;

    /**
     * @var \Zend\EventManager\EventManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventManager;

    /**
     * {@inheritDoc}
     *
     * @covers \OcraServiceManager\ServiceManager\LoggedControllerPluginManager::__construct
     */
    public function setup()
    {
        $this->eventManager  = $this->getMock('Zend\\EventManager\\EventManagerInterface');
        $this->pluginManager = new LoggedControllerPluginManager($this->eventManager, new PluginManager());
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedControllerPluginManager::create
     */
    public function testTriggersEventsOnCreate()
    {
        $helper = $this->getMock('Zend\\Mvc\\Controller\\Plugin\\PluginInterface');
        $this->pluginManager->setFactory('helper', function () use ($helper) {
            return $helper;
        });

        $this
            ->eventManager
            ->expects($this->once())
            ->method('trigger')
            ->with(
                Logger::SERVICE_MANAGER_CREATE,
                $this->pluginManager,
                array(
                    'instance'       => $helper,
                    'canonical_name' => 'helper',
                    'requested_name' => 'helper',
                )
            );
        $this->assertSame($helper, $this->pluginManager->create('helper'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedControllerPluginManager::get
     */
    public function testTriggersEventsOnGet()
    {
        $helper = $this->getMock('Zend\\Mvc\\Controller\\Plugin\\PluginInterface');
        $this->pluginManager->setService('helper', $helper);

        $this
            ->eventManager
            ->expects($this->once())
            ->method('trigger')
            ->with(
                Logger::SERVICE_LOCATOR_GET,
                $this->pluginManager,
                array(
                    'instance'       => $helper,
                    'canonical_name' => 'helper',
                    'requested_name' => 'helper',
                )
            );
        $this->assertSame($helper, $this->pluginManager->get('helper'));
    }
}
