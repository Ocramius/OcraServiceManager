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

use OcraServiceManager\ServiceManager\Event\ServiceManagerEvent;
use OcraServiceManager\ServiceManager\Logger;

class LoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Logger
     */
    protected $listener;

    /**
     * {@inheritDoc}
     *
     * @covers \OcraServiceManager\ServiceManager\Logger
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
            ->with(
                $this->logicalOr(
                    ServiceManagerEvent::EVENT_SERVICEMANAGER_GET,
                    ServiceManagerEvent::EVENT_SERVICEMANAGER_CREATE
                )
            )
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
        $instance       = new \stdClass();
        $event          = $this
            ->getMockBuilder('OcraServiceManager\\ServiceManager\\Event\\ServiceManagerEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->any())->method('getServiceLocator')->will($this->returnValue($serviceLocator));
        $event->expects($this->any())->method('getInstance')->will($this->returnValue($instance));
        $event->expects($this->any())->method('getCanonicalName')->will($this->returnValue('canonical-name'));
        $event->expects($this->any())->method('getRequestedName')->will($this->returnValue('requested\\name'));
        $event->expects($this->any())->method('getTrace')->will($this->returnValue(array()));

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
        $instance       = new \stdClass();
        $event          = $this
            ->getMockBuilder('OcraServiceManager\\ServiceManager\\Event\\ServiceManagerEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->any())->method('getServiceLocator')->will($this->returnValue($serviceLocator));
        $event->expects($this->any())->method('getInstance')->will($this->returnValue($instance));
        $event->expects($this->any())->method('getCanonicalName')->will($this->returnValue('canonical-name'));
        $event->expects($this->any())->method('getRequestedName')->will($this->returnValue('requested\\name'));
        $event->expects($this->any())->method('getTrace')->will($this->returnValue(array()));

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
                'methodName',
                array()
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
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependingInstances
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependencyInstances
     */
    public function testGetDependingInstancesWithNonExistingService()
    {
        $this->assertEmpty($this->listener->getDependingInstances(new \stdClass()));
        $this->assertEmpty($this->listener->getDependencyInstances(new \stdClass()));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependingInstances
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependencyInstances
     * @covers \OcraServiceManager\ServiceManager\Logger::getParentRequestingService
     * @covers \OcraServiceManager\ServiceManager\Logger::getLoggedServiceLocators
     */
    public function testGetDependingInstances()
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

        $depending1 = $this->listener->getDependingInstances($dependency1);
        $this->assertCount(1, $depending1);
        $this->assertSame($depending1[0]['instance'], $instance);

        $depending2 = $this->listener->getDependingInstances($dependency2);
        $this->assertCount(1, $depending2);
        $this->assertSame($depending2[0]['instance'], $dependency1);

        $this->assertEmpty($this->listener->getDependingInstances($instance));

        $dependencies1 = $this->listener->getDependencyInstances($instance);
        $this->assertCount(1, $dependencies1);
        $this->assertSame($dependencies1[0]['instance'], $dependency1);

        $dependencies2 = $this->listener->getDependencyInstances($dependency1);
        $this->assertCount(1, $dependencies2);
        $this->assertSame($dependencies2[0]['instance'], $dependency2);

        $this->assertEmpty($this->listener->getDependencyInstances($dependency2));
        $serviceLocators = $this->listener->getLoggedServiceLocators();
        $this->assertCount(1, $serviceLocators);
        $this->assertArrayHasKey(spl_object_hash($locator), $serviceLocators);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependingInstances
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependencyInstances
     * @covers \OcraServiceManager\ServiceManager\Logger::getParentRequestingService
     */
    public function testGetDependingInstancesOnMultipleLocators()
    {
        $locator1 = $this->getMock('Zend\\ServiceManager\\ServiceLocatorInterface');
        $locator2 = $this->getMock('Zend\\ServiceManager\\ServiceLocatorInterface');

        $dependency1 = new \stdClass();
        $instance1   = new \stdClass();
        $dependency2 = new \stdClass();
        $instance2   = new \stdClass();

        $this->listener->registerServiceCall(
            $locator1,
            $dependency1,
            'dependency-canonical-name',
            'dependency\\requested\\name',
            'create',
            array(
                 array(
                     'object'   => $locator1,
                     'function' => 'get',
                     'args'     => array('dependency\\requested\\name'),
                 ),
                 array(
                     'object'   => $locator1,
                     'function' => 'get',
                     'args'     => array('instance\\requested\\name'),
                 ),
            )
        );
        $this->listener->registerServiceCall(
            $locator1,
            $instance1,
            'instance-canonical-name',
            'instance\\requested\\name',
            'create',
            array(
                 array(
                     'object'   => $locator1,
                     'function' => 'get',
                     'args'     => array('instance\\requested\\name'),
                 ),
            )
        );
        $this->listener->registerServiceCall(
            $locator2,
            $dependency2,
            'dependency-canonical-name',
            'dependency\\requested\\name',
            'create',
            array(
                 array(
                     'object'   => $locator2,
                     'function' => 'get',
                     'args'     => array('dependency\\requested\\name'),
                 ),
                 array(
                     'object'   => $locator2,
                     'function' => 'get',
                     'args'     => array('instance\\requested\\name'),
                 ),
            )
        );
        $this->listener->registerServiceCall(
            $locator2,
            $instance2,
            'instance-canonical-name',
            'instance\\requested\\name',
            'create',
            array(
                 array(
                     'object'   => $locator1,
                     'function' => 'get',
                     'args'     => array('instance\\requested\\name'),
                 ),
            )
        );

        $depending1 = $this->listener->getDependingInstances($dependency1);
        $this->assertCount(1, $depending1);
        $this->assertSame($instance1, $depending1[0]['instance']);
        $this->assertEmpty($this->listener->getDependingInstances($instance1));

        $dependencies1 = $this->listener->getDependencyInstances($instance1);
        $this->assertCount(1, $dependencies1);
        $this->assertSame($dependency1, $dependencies1[0]['instance']);
        $this->assertEmpty($this->listener->getDependencyInstances($dependency1));

        $depending2 = $this->listener->getDependingInstances($dependency2);
        $this->assertCount(1, $depending2);
        $this->assertSame($instance2, $depending2[0]['instance']);
        $this->assertEmpty($this->listener->getDependingInstances($instance2));

        $dependencies2 = $this->listener->getDependencyInstances($instance2);
        $this->assertCount(1, $dependencies2);
        $this->assertSame($dependency2, $dependencies2[0]['instance']);
        $this->assertEmpty($this->listener->getDependencyInstances($dependency2));

        $locators = $this->listener->getLoggedServiceLocators();
        $this->assertCount(2, $locators);
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
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependingInstances
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependencyInstances
     * @covers \OcraServiceManager\ServiceManager\Logger::getParentRequestingService
     */
    public function testGetDependingInstancesWithServiceLocatorAwareObjects()
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

        $dependencyDepending = $this->listener->getDependingInstances($dependency);
        $this->assertCount(1, $dependencyDepending);
        $this->assertSame($dependencyDepending[0]['instance'], $serviceLocatorAwareInstance);

        $this->assertEmpty($this->listener->getDependingInstances($serviceLocatorAwareInstance));

        $instanceDependencies = $this->listener->getDependencyInstances($serviceLocatorAwareInstance);
        $this->assertCount(1, $instanceDependencies);
        $this->assertSame($instanceDependencies[0]['instance'], $dependency);

        $this->assertEmpty($this->listener->getDependencyInstances($dependency));
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
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependingInstances
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependencyInstances
     * @covers \OcraServiceManager\ServiceManager\Logger::getParentRequestingService
     */
    public function testGetDependingInstancesWillIgnoreUnrelatedCallsInStackTrace()
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

        $dependencyDepending = $this->listener->getDependingInstances($dependency);
        $this->assertCount(1, $dependencyDepending);
        $this->assertSame($dependencyDepending[0]['instance'], $instance);

        $this->assertEmpty($this->listener->getDependingInstances($instance));

        $instanceDependencies = $this->listener->getDependencyInstances($instance);
        $this->assertCount(1, $instanceDependencies);
        $this->assertSame($instanceDependencies[0]['instance'], $dependency);

        $this->assertEmpty($this->listener->getDependencyInstances($dependency));
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
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependingInstances
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependencyInstances
     * @covers \OcraServiceManager\ServiceManager\Logger::getParentRequestingService
     */
    public function testGetDependingInstancesOnUnrelatedServices()
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

        $this->assertEmpty($this->listener->getDependingInstances($instance1));
        $this->assertEmpty($this->listener->getDependingInstances($instance2));
        $this->assertEmpty($this->listener->getDependencyInstances($instance1));
        $this->assertEmpty($this->listener->getDependencyInstances($instance2));
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
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependingInstances
     * @covers \OcraServiceManager\ServiceManager\Logger::getDependencyInstances
     * @covers \OcraServiceManager\ServiceManager\Logger::getParentRequestingService
     */
    public function testGetDependingInstancesOnMultipleAccesses()
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

        $this->assertEmpty($this->listener->getDependingInstances($instance));

        $dependencyDepending = $this->listener->getDependingInstances($dependency);
        $this->assertCount(1, $dependencyDepending);
        $this->assertSame($dependencyDepending[0]['instance'], $instance);

        $this->assertEmpty($this->listener->getDependencyInstances($dependency));
        $instanceDependencies = $this->listener->getDependencyInstances($instance);
        $this->assertCount(1, $instanceDependencies);
        $this->assertSame($instanceDependencies[0]['instance'], $dependency);
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

        $instanceHash  = spl_object_hash($instance);
        $instance2Hash = spl_object_hash($instance2);
        $dependency1Hash = spl_object_hash($dependency1);
        $dependency2Hash = spl_object_hash($dependency2);

        $this->assertCount(4, $loggedServices);

        $this->assertArrayHasKey($instanceHash, $loggedServices);
        $this->assertArrayHasKey($instance2Hash, $loggedServices);
        $this->assertArrayHasKey($dependency1Hash, $loggedServices);
        $this->assertArrayHasKey($dependency2Hash, $loggedServices);

        $this->assertSame(2, $loggedServices[$instanceHash]['accesses']);
        $this->assertSame('instance-canonical-name', $loggedServices[$instanceHash]['canonical_name']);
        $this->assertCount(2, $loggedServices[$instanceHash]['names']);
        $this->assertSame(1, $loggedServices[$instanceHash]['service_instantiation_idx']);
        $this->assertEmpty($loggedServices[$instanceHash]['depending']);
        $this->assertCount(1, $loggedServices[$instanceHash]['dependencies']);
        $this->assertContains($dependency1Hash, $loggedServices[$instanceHash]['dependencies']);

        $this->assertSame(1, $loggedServices[$instance2Hash]['accesses']);
        $this->assertSame('instance-canonical-name', $loggedServices[$instance2Hash]['canonical_name']);
        $this->assertCount(1, $loggedServices[$instance2Hash]['names']);
        $this->assertSame(2, $loggedServices[$instance2Hash]['service_instantiation_idx']);
        $this->assertEmpty($loggedServices[$instance2Hash]['depending']);
        $this->assertCount(0, $loggedServices[$instance2Hash]['dependencies']);

        $this->assertSame(1, $loggedServices[$dependency1Hash]['accesses']);
        $this->assertSame('dependency1-canonical-name', $loggedServices[$dependency1Hash]['canonical_name']);
        $this->assertCount(1, $loggedServices[$dependency1Hash]['names']);
        $this->assertSame(1, $loggedServices[$dependency1Hash]['service_instantiation_idx']);
        $this->assertCount(1, $loggedServices[$dependency1Hash]['depending']);
        $this->assertContains($instanceHash, $loggedServices[$dependency1Hash]['depending']);
        $this->assertCount(1, $loggedServices[$dependency1Hash]['dependencies']);
        $this->assertContains($dependency2Hash, $loggedServices[$dependency1Hash]['dependencies']);

        $this->assertSame(1, $loggedServices[$dependency2Hash]['accesses']);
        $this->assertSame('dependency2-canonical-name', $loggedServices[$dependency2Hash]['canonical_name']);
        $this->assertCount(1, $loggedServices[$dependency2Hash]['names']);
        $this->assertSame(1, $loggedServices[$dependency2Hash]['service_instantiation_idx']);
        $this->assertCount(1, $loggedServices[$dependency2Hash]['depending']);
        $this->assertContains($dependency1Hash, $loggedServices[$dependency2Hash]['depending']);
        $this->assertEmpty($loggedServices[$dependency2Hash]['dependencies']);
    }
}
