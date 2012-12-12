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

use OcraServiceManager\ServiceManager\Logger;

class LoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Logger
     */
    protected $listener;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->listener = new Logger();
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\Logger::attach
     * @covers \OcraServiceManager\ServiceManager\Logger::detach
     */
    public function testAttachDetach()
    {
        $eventManager  = $this->getMock('Zend\\EventManager\\EventManagerInterface');
        $eventManager2 = $this->getMock('Zend\\EventManager\\EventManagerInterface');
        $callback      = $this->getMock('Zend\\Stdlib\\CallbackHandler', array(), array(), '', false);

        $eventManager
            ->expects($this->exactly(2))
            ->method('attach')
            ->with($this->callback(function ($eventName) {
                return $eventName === Logger::SERVICE_MANAGER_CREATE || $eventName === Logger::SERVICE_LOCATOR_GET;
            }))
            ->will($this->returnValue($callback));

        $this->listener->attach($eventManager);

        $eventManager
            ->expects($this->exactly(2))
            ->method('detach')
            ->with($callback)
            ->will($this->returnValue(true));

        $this->listener->detach($eventManager);

        $eventManager2->expects($this->never())->method('detach');

        $this->listener->detach($eventManager2);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\Logger::logServiceLocatorGet
     * @covers \OcraServiceManager\ServiceManager\Logger::registerServiceCall
     */
    public function testLogServiceLocatorGet()
    {
        $serviceLocator = $this->getMock('Zend\\ServiceManager\\ServiceLocatorInterface');
        $event          = $this->getMock('Zend\\EventManager\\EventInterface');
        $instance       = new \stdClass();

        $event->expects($this->any())->method('getTarget')->will($this->returnValue($serviceLocator));
        $event->expects($this->any())->method('getParam')->will($this->returnCallback(function ($name) use ($instance) {
            $data = array(
                'instance'       => $instance,
                'canonical_name' => 'canonical-name',
                'requested_name' => 'requested\\name',
                'trace'          => null,
            );

            return $data[$name];
        }));

        $result = $this->listener->logServiceLocatorGet($event);

        $this->assertSame($instance, $result['instance']);
        $this->assertSame($serviceLocator, $result['service_locator']);
        $this->assertSame('requested\\name', $result['requested_name']);
        $this->assertSame('canonical-name', $result['canonical_name']);
        $this->assertSame('get', $result['method']);
        $this->assertInternalType('array', $result['trace']);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\Logger::logServiceManagerCreate
     * @covers \OcraServiceManager\ServiceManager\Logger::registerServiceCall
     */
    public function testLogServiceManagerCreate()
    {
        $serviceLocator = $this->getMock('Zend\\ServiceManager\\ServiceLocatorInterface');
        $event          = $this->getMock('Zend\\EventManager\\EventInterface');
        $instance       = new \stdClass();

        $event->expects($this->any())->method('getTarget')->will($this->returnValue($serviceLocator));
        $event->expects($this->any())->method('getParam')->will($this->returnCallback(function ($name) use ($instance) {
            $data = array(
                'instance'       => $instance,
                'canonical_name' => 'canonical-name',
                'requested_name' => 'requested\\name',
                'trace'          => null,
            );

            return $data[$name];
        }));


        $result = $this->listener->logServiceManagerCreate($event);

        $this->assertSame($instance, $result['instance']);
        $this->assertSame($serviceLocator, $result['service_locator']);
        $this->assertSame('requested\\name', $result['requested_name']);
        $this->assertSame('canonical-name', $result['canonical_name']);
        $this->assertSame('create', $result['method']);
        $this->assertInternalType('array', $result['trace']);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\Logger::registerServiceCall
     */
    public function testRegisterServiceCallWithNonObjectInstance()
    {
        $this->assertFalse(
            $this->listener->registerServiceCall(
                $this->getMock('Zend\\ServiceManager\\ServiceLocatorInterface'),
                null,
                'canonical-name',
                'requested\\name',
                'methodName'
            )
        );
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\Logger::getDepending
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependencies
     */
    public function testGetDependingWithNonExistingService()
    {
        $this->assertFalse($this->listener->getDepending('non-existing-service'));
        $this->assertEmpty($this->listener->getDependencies('non-existing-service'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\Logger::getDepending
     * @covers \OcraServiceManager\ServiceManager\Logger::getParentRequestingService
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependencies
     */
    public function testGetDepending()
    {
        $locator     = $this->getMock('Zend\\ServiceManager\\ServiceLocatorInterface');

        $dependency2 = new \stdClass();
        $dependency1 = new \stdClass();
        $instance    = new \stdClass();

        $this->listener->registerServiceCall(
            $locator,
            $dependency2,
            'dependency2-canonical-name',
            'dependency2\\requested\\name',
            'create',
            array(
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('dependency2\\requested\\name'),
                ),
                array(
                    'object'   => $locator,
                    'function' => 'create',
                    'args'     => array(array('dependency1-canonical-name', 'dependency1\\requested\\name')),
                ),
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('instance\\requested\\name'),
                ),
            )
        );
        $this->listener->registerServiceCall(
            $locator,
            $dependency1,
            'dependency1-canonical-name',
            'dependency1\\requested\\name',
            'create',
            array(
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('dependency1\\requested\\name'),
                ),
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('instance\\requested\\name'),
                ),
            )
        );
        $this->listener->registerServiceCall(
            $locator,
            $instance,
            'instance-canonical-name',
            'instance\\requested\\name',
            'create',
            array(
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('instance\\requested\\name'),
                ),
            )
        );

        $this->assertSame(
            array('instance-canonical-name'),
            $this->listener->getDepending('dependency1-canonical-name')
        );
        $this->assertSame(
            array('dependency1-canonical-name'),
            $this->listener->getDepending('dependency2-canonical-name')
        );
        $this->assertEmpty($this->listener->getDepending('instance-canonical-name'));
        $this->assertSame(
            array('dependency1-canonical-name'),
            $this->listener->getDependencies('instance-canonical-name')
        );
        $this->assertSame(
            array('dependency2-canonical-name'),
            $this->listener->getDependencies('dependency1-canonical-name')
        );
        $this->assertEmpty($this->listener->getDependencies('dependency2-canonical-name'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\Logger::getDepending
     * @covers \OcraServiceManager\ServiceManager\Logger::getParentRequestingService
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependencies
     */
    public function testGetDependingWithServiceLocatorAwareObjects()
    {
        $locator                        = $this->getMock('Zend\\ServiceManager\\ServiceLocatorInterface');
        $serviceLocatorAwareInstance    = $this->getMock('Zend\\ServiceManager\\ServiceLocatorAwareInterface');
        $dependency                     = new \stdClass();

        $this->listener->registerServiceCall(
            $locator,
            $dependency,
            'dependency-canonical-name',
            'dependency\\requested\\name',
            'create',
            array(
                array(
                    'object'   => $serviceLocatorAwareInstance,
                    'function' => 'createDependency',
                    'args'     => array('dependency\\requested\\name'),
                ),
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('instance\\requested\\name'),
                ),
            )
        );
        $this->listener->registerServiceCall(
            $locator,
            $serviceLocatorAwareInstance,
            'instance-canonical-name',
            'instance\\requested\\name',
            'create',
            array(
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('instance\\requested\\name'),
                ),
            )
        );

        $this->assertSame(
            array('instance-canonical-name'),
            $this->listener->getDepending('dependency-canonical-name')
        );
        $this->assertEmpty($this->listener->getDepending('instance-canonical-name'));
        $this->assertSame(
            array('dependency-canonical-name'),
            $this->listener->getDependencies('instance-canonical-name')
        );
        $this->assertEmpty($this->listener->getDependencies('dependency-canonical-name'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\Logger::getDepending
     * @covers \OcraServiceManager\ServiceManager\Logger::getParentRequestingService
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependencies
     */
    public function testWillIgnoreUnrelatedCallsInStackTrace()
    {
        $locator      = $this->getMock('Zend\\ServiceManager\\ServiceLocatorInterface');
        $instance     = new \stdClass();
        $dependency   = new \stdClass();

        $this->listener->registerServiceCall(
            $locator,
            $dependency,
            'dependency-canonical-name',
            'dependency\\requested\\name',
            'create',
            array(
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('dependency\\requested\\name'),
                ),
                array(
                    'object'   => $locator,
                    'function' => 'otherLocatorMethod',
                ),
                array(
                    'function' => 'doStuff',
                ),
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('instance\\requested\\name'),
                ),
            )
        );
        $this->listener->registerServiceCall(
            $locator,
            $instance,
            'instance-canonical-name',
            'instance\\requested\\name',
            'create',
            array(
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('instance\\requested\\name'),
                ),
            )
        );

        $this->assertSame(
            array('instance-canonical-name'),
            $this->listener->getDepending('dependency-canonical-name')
        );
        $this->assertEmpty($this->listener->getDepending('instance-canonical-name'));
        $this->assertSame(
            array('dependency-canonical-name'),
            $this->listener->getDependencies('instance-canonical-name')
        );
        $this->assertEmpty($this->listener->getDependencies('dependency-canonical-name'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\Logger::getDepending
     * @covers \OcraServiceManager\ServiceManager\Logger::getParentRequestingService
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependencies
     */
    public function testGetDependingOnUnrelatedServices()
    {
        $locator   = $this->getMock('Zend\\ServiceManager\\ServiceLocatorInterface');
        $instance1 = new \stdClass();
        $instance2 = new \stdClass();

        $this->listener->registerServiceCall(
            $locator,
            $instance1,
            'instance1-canonical-name',
            'instance1\\requested\\name',
            'get',
            array(
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('instance1\\requested\\name'),
                )
            )
        );
        $this->listener->registerServiceCall(
            $locator,
            $instance2,
            'instance2-canonical-name',
            'instance2\\requested\\name',
            'get',
            array(
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('instance2\\requested\\name'),
                )
            )
        );

        $this->assertEmpty($this->listener->getDepending('instance1-canonical-name'));
        $this->assertEmpty($this->listener->getDepending('instance2-canonical-name'));
        $this->assertEmpty($this->listener->getDependencies('instance1-canonical-name'));
        $this->assertEmpty($this->listener->getDependencies('instance2-canonical-name'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\Logger::getDepending
     * @covers \OcraServiceManager\ServiceManager\Logger::getParentRequestingService
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependencies
     */
    public function testGetDependingOnMultipleAccesses()
    {
        $locator    = $this->getMock('Zend\\ServiceManager\\ServiceLocatorInterface');
        $dependency = new \stdClass();
        $instance   = new \stdClass();

        $this->listener->registerServiceCall(
            $locator,
            $dependency,
            'dependency-canonical-name',
            'dependency\\first\\requested\\name',
            'get',
            array(
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('dependency\\first\\requested\\name'),
                ),
            )
        );
        $this->listener->registerServiceCall(
            $locator,
            $dependency,
            'dependency-canonical-name',
            'dependency\\requested\\name',
            'get',
            array(
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('dependency\\requested\\name'),
                ),
                array(
                    'object'   => $locator,
                    'function' => 'otherLocatorMethod',
                ),
                array(
                    'function' => 'doStuff',
                ),
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('instance\\requested\\name'),
                ),
            )
        );
        $this->listener->registerServiceCall(
            $locator,
            $dependency,
            'dependency-canonical-name',
            'dependency\\second\\requested\\name',
            'get',
            array(
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('dependency\\second\\requested\\name'),
                ),
            )
        );
        $this->listener->registerServiceCall(
            $locator,
            $instance,
            'instance-canonical-name',
            'instance\\requested\\name',
            'get',
            array(
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('instance\\requested\\name'),
                ),
            )
        );

        $this->assertEmpty($this->listener->getDepending('instance-canonical-name'));
        $this->assertSame(array('instance-canonical-name'), $this->listener->getDepending('dependency-canonical-name'));

        $this->assertEmpty($this->listener->getDependencies('dependency-canonical-name'));
        $this->assertSame(
            array('dependency-canonical-name'),
            $this->listener->getDependencies('instance-canonical-name')
        );
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\Logger::getLoggedServices
     */
    public function testGetLoggedServicesWithNoLoggedServices()
    {
        $this->assertEmpty($this->listener->getLoggedServices());
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\Logger::getLoggedServices
     */
    public function testGetLoggedServices()
    {
        $locator     = $this->getMock('Zend\\ServiceManager\\ServiceLocatorInterface');
        $dependency2 = new \stdClass();
        $dependency1 = new \stdClass();
        $instance    = new \stdClass();
        $instance2   = new \stdClass();

        $this->listener->registerServiceCall(
            $locator,
            $dependency2,
            'dependency2-canonical-name',
            'dependency2\\requested\\name',
            'create',
            array(
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('dependency2\\requested\\name'),
                ),
                array(
                    'object'   => $locator,
                    'function' => 'create',
                    'args'     => array(array('dependency1-canonical-name', 'dependency1\\requested\\name')),
                ),
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('instance\\requested\\name'),
                ),
            )
        );
        $this->listener->registerServiceCall(
            $locator,
            $dependency1,
            'dependency1-canonical-name',
            'dependency1\\requested\\name',
            'create',
            array(
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('dependency1\\requested\\name'),
                ),
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('instance\\requested\\name'),
                ),
            )
        );
        $this->listener->registerServiceCall(
            $locator,
            $instance,
            'instance-canonical-name',
            'instance\\requested\\name',
            'create',
            array(
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('instance\\requested\\name'),
                ),
            )
        );
        $this->listener->registerServiceCall(
            $locator,
            $instance,
            'instance-canonical-name',
            'instance\\second\\requested\\name',
            'create',
            array(
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('instance\\second\\requested\\name'),
                ),
            )
        );
        $this->listener->registerServiceCall(
            $locator,
            $instance2,
            'instance-canonical-name',
            'instance\\third\\requested\\name',
            'create',
            array(
                array(
                    'object'   => $locator,
                    'function' => 'get',
                    'args'     => array('instance\\third\\requested\\name'),
                ),
            )
        );

        $loggedServices = $this->listener->getLoggedServices();

        $this->assertCount(3, $loggedServices);
        $this->assertSame(3, $loggedServices['instance-canonical-name']['accesses']);
        $this->assertCount(3, $loggedServices['instance-canonical-name']['names']);
        $this->assertSame(2, $loggedServices['instance-canonical-name']['instances']);

        $this->assertSame(1, $loggedServices['dependency1-canonical-name']['accesses']);
        $this->assertCount(1, $loggedServices['dependency1-canonical-name']['names']);
        $this->assertSame(1, $loggedServices['dependency1-canonical-name']['instances']);

        $this->assertSame(1, $loggedServices['dependency2-canonical-name']['accesses']);
        $this->assertCount(1, $loggedServices['dependency2-canonical-name']['names']);
        $this->assertSame(1, $loggedServices['dependency2-canonical-name']['instances']);

        $this->assertSame(
            $this->listener->getDepending('instance-canonical-name'),
            $loggedServices['instance-canonical-name']['depending']
        );
        $this->assertSame(
            $this->listener->getDepending('dependency1-canonical-name'),
            $loggedServices['dependency1-canonical-name']['depending']
        );
        $this->assertSame(
            $this->listener->getDepending('dependency2-canonical-name'),
            $loggedServices['dependency2-canonical-name']['depending']
        );

        $this->assertSame(
            $this->listener->getDependencies('instance-canonical-name'),
            $loggedServices['instance-canonical-name']['dependencies']
        );
        $this->assertSame(
            $this->listener->getDependencies('dependency1-canonical-name'),
            $loggedServices['dependency1-canonical-name']['dependencies']
        );
        $this->assertSame(
            $this->listener->getDependencies('dependency2-canonical-name'),
            $loggedServices['dependency2-canonical-name']['dependencies']
        );
    }
}
