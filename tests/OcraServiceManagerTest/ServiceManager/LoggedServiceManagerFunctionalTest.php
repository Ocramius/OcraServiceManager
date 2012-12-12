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

use OcraServiceManager\ServiceManager\LoggedServiceManager;

use Zend\Di\Di;
use Zend\Mvc\Service\DiFactory;
use Zend\ServiceManager\Di\DiAbstractServiceFactory;
use Zend\ServiceManager\Exception;
use Zend\ServiceManager\ServiceManager as BaseServiceManager;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceLocatorInterface;

use OcraServiceManagerTest\ServiceManager\TestAsset\FooCounterAbstractFactory;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceManagerAwareInterface;

class LoggedServiceManagerFunctionalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggedServiceManager
     */
    protected $serviceManager = null;

    public function setup()
    {
        $this->serviceManager = new LoggedServiceManager(
            $this->getMock('Zend\\EventManager\\EventManagerInterface'),
            new BaseServiceManager()
        );
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::__construct
     */
    public function testConstructorConfig()
    {
        $config = new Config(array('services' => array('foo' => 'bar')));
        $serviceManager = new LoggedServiceManager(
            $this->getMock('Zend\\EventManager\\EventManagerInterface'),
            new BaseServiceManager($config)
        );
        $this->assertEquals('bar', $serviceManager->get('foo'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::setAllowOverride
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::getAllowOverride
     */
    public function testAllowOverride()
    {
        $this->assertFalse($this->serviceManager->getAllowOverride());
        $ret = $this->serviceManager->setAllowOverride(true);
        $this->assertSame($this->serviceManager, $ret);
        $this->assertTrue($this->serviceManager->getAllowOverride());
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::setThrowExceptionInCreate
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::getThrowExceptionInCreate
     */
    public function testThrowExceptionInCreate()
    {
        $this->assertTrue($this->serviceManager->getThrowExceptionInCreate());
        $ret = $this->serviceManager->setThrowExceptionInCreate(false);
        $this->assertSame($this->serviceManager, $ret);
        $this->assertFalse($this->serviceManager->getThrowExceptionInCreate());
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::setInvokableClass
     */
    public function testSetInvokableClass()
    {
        $ret = $this->serviceManager->setInvokableClass('foo', 'bar');
        $this->assertSame($this->serviceManager, $ret);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::setFactory
     */
    public function testSetFactory()
    {
        $ret = $this->serviceManager->setFactory('foo', 'bar');
        $this->assertSame($this->serviceManager, $ret);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::setFactory
     */
    public function testSetFactoryThrowsExceptionOnDuplicate()
    {
        $this->serviceManager->setFactory('foo', 'bar');
        $this->setExpectedException('Zend\ServiceManager\Exception\InvalidServiceNameException');
        $this->serviceManager->setFactory('foo', 'bar');
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::addAbstractFactory
     */
    public function testAddAbstractFactory()
    {
        $this->serviceManager->addAbstractFactory('ZendTest\ServiceManager\TestAsset\FooAbstractFactory');

        $ret = $this->serviceManager->addAbstractFactory(new TestAsset\FooAbstractFactory());
        $this->assertSame($this->serviceManager, $ret);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::addAbstractFactory
     */
    public function testAddAbstractFactoryThrowsExceptionOnInvalidFactory()
    {
        $this->setExpectedException('Zend\ServiceManager\Exception\InvalidArgumentException');
        $this->serviceManager->addAbstractFactory(10);
    }

    public function testServiceManagerIsPassedToInitializer()
    {
        $initializer = new TestAsset\FooInitializer();
        $this->serviceManager->addInitializer($initializer);
        $this->serviceManager->setFactory('foo', function () {
            return new \stdClass();
        });
        $obj = $this->serviceManager->get('foo');
        $this->assertSame($this->serviceManager, $initializer->sm);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::addInitializer
     */
    public function testAddInitializer()
    {
        $ret = $this->serviceManager->addInitializer(new TestAsset\FooInitializer());
        $this->assertSame($this->serviceManager, $ret);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::addInitializer
     */
    public function testAddInitializerThrowsExceptionOnInvalidInitializer()
    {
        $this->setExpectedException('Zend\ServiceManager\Exception\InvalidArgumentException');
        $this->serviceManager->addInitializer(5);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::setService
     */
    public function testSetService()
    {
        $ret = $this->serviceManager->setService('foo', 'bar');
        $this->assertSame($this->serviceManager, $ret);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::setShared
     */
    public function testSetShared()
    {
        $this->serviceManager->setInvokableClass('foo', 'bar');
        $ret = $this->serviceManager->setShared('foo', true);
        $this->assertSame($this->serviceManager, $ret);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::setShared
     */
    public function testSetSharedAbstractFactory()
    {
        $this->serviceManager->addAbstractFactory('ZendTest\ServiceManager\TestAsset\FooAbstractFactory');
        $ret = $this->serviceManager->setShared('foo', false);
        $this->assertSame($this->serviceManager, $ret);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::setShared
     */
    public function testSetSharedThrowsExceptionOnUnregisteredService()
    {
        $this->setExpectedException('Zend\ServiceManager\Exception\ServiceNotFoundException');
        $this->serviceManager->setShared('foo', true);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::get
     */
    public function testGet()
    {
        $this->serviceManager->setService('foo', 'bar');
        $this->assertEquals('bar', $this->serviceManager->get('foo'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::get
     */
    public function testGetDoesNotThrowExceptionOnEmptyArray()
    {
        $this->serviceManager->setService('foo', array());
        $this->serviceManager->get('foo');
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::get
     */
    public function testGetThrowsExceptionOnUnknownService()
    {
        $this->setExpectedException('Zend\ServiceManager\Exception\ServiceNotFoundException');
        $this->assertEquals('bar', $this->serviceManager->get('foo'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::get
     */
    public function testGetWithAlias()
    {
        $this->serviceManager->setService('foo', 'bar');
        $this->serviceManager->setAlias('baz', 'foo');
        $this->assertEquals('bar', $this->serviceManager->get('baz'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::get
     */
    public function testGetWithScopedContainer()
    {
        $this->serviceManager->setService('foo', 'bar');
        $scopedServiceManager = $this->serviceManager->createScopedServiceManager();
        $this->assertEquals('bar', $scopedServiceManager->get('foo'));
    }

    public function testCanRetrieveFromParentPeeringManager()
    {
        $parent = new LoggedServiceManager(
            $this->getMock('Zend\\EventManager\\EventManagerInterface'),
            new BaseServiceManager()
        );
        $parent->setService('foo', 'bar');
        $child  = new LoggedServiceManager(
            $this->getMock('Zend\\EventManager\\EventManagerInterface'),
            new BaseServiceManager()
        );
        $child->addPeeringServiceManager($parent, BaseServiceManager::SCOPE_PARENT);
        $this->assertEquals('bar', $child->get('foo'));
    }

    public function testCanRetrieveFromChildPeeringManager()
    {
        $parent = new LoggedServiceManager(
            $this->getMock('Zend\\EventManager\\EventManagerInterface'),
            new BaseServiceManager()
        );
        $child  = new LoggedServiceManager(
            $this->getMock('Zend\\EventManager\\EventManagerInterface'),
            new BaseServiceManager()
        );
        $child->addPeeringServiceManager($parent, BaseServiceManager::SCOPE_CHILD);
        $child->setService('foo', 'bar');
        $this->assertEquals('bar', $parent->get('foo'));
    }

    public function testAllowsRetrievingFromPeeringContainerFirst()
    {
        $parent = new LoggedServiceManager(
            $this->getMock('Zend\\EventManager\\EventManagerInterface'),
            new BaseServiceManager()
        );
        $parent->setFactory('foo', function() {
            return 'bar';
        });
        $child  = new LoggedServiceManager(
            $this->getMock('Zend\\EventManager\\EventManagerInterface'),
            new BaseServiceManager()
        );
        $child->setFactory('foo', function() {
            return 'baz';
        });
        $child->addPeeringServiceManager($parent, BaseServiceManager::SCOPE_PARENT);
        $child->setRetrieveFromPeeringManagerFirst(true);
        $this->assertEquals('bar', $child->get('foo'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::create
     */
    public function testCreateWithInvokableClass()
    {
        $this->serviceManager->setInvokableClass('foo', 'ZendTest\ServiceManager\TestAsset\Foo');
        $this->assertInstanceOf('ZendTest\ServiceManager\TestAsset\Foo', $this->serviceManager->get('foo'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::create
     */
    public function testCreateWithFactoryInstance()
    {
        $this->serviceManager->setFactory('foo', 'ZendTest\ServiceManager\TestAsset\FooFactory');
        $this->assertInstanceOf('ZendTest\ServiceManager\TestAsset\Foo', $this->serviceManager->get('foo'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::create
     */
    public function testCreateWithCallableFactory()
    {
        $this->serviceManager->setFactory('foo', function () { return new TestAsset\Foo; });
        $this->assertInstanceOf(
            'OcraServiceManagerTest\ServiceManager\TestAsset\Foo',
            $this->serviceManager->get('foo')
        );
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::create
     */
    public function testCreateWithAbstractFactory()
    {
        $this->serviceManager->addAbstractFactory('ZendTest\ServiceManager\TestAsset\FooAbstractFactory');
        $this->assertInstanceOf('ZendTest\ServiceManager\TestAsset\Foo', $this->serviceManager->get('foo'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::create
     */
    public function testCreateWithMultipleAbstractFactories()
    {
        $this->serviceManager->addAbstractFactory('ZendTest\ServiceManager\TestAsset\BarAbstractFactory');
        $this->serviceManager->addAbstractFactory('ZendTest\ServiceManager\TestAsset\FooAbstractFactory');

        $this->assertInstanceOf('ZendTest\ServiceManager\TestAsset\Bar', $this->serviceManager->get('bar'));
    }

    public function testCreateWithInitializerObject()
    {
        $this->serviceManager->addInitializer(new TestAsset\FooInitializer(array('foo' => 'bar')));
        $this->serviceManager->setFactory('foo', function () {
            return new \stdClass();
        });
        $obj = $this->serviceManager->get('foo');
        $this->assertEquals('bar', $obj->foo);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::has
     */
    public function testHas()
    {
        $this->assertFalse($this->serviceManager->has('foo'));
        $this->serviceManager->setInvokableClass('foo', 'bar');
        $this->assertTrue($this->serviceManager->has('foo'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::setAlias
     */
    public function testSetAlias()
    {
        $this->serviceManager->setInvokableClass('foo', 'bar');
        $ret = $this->serviceManager->setAlias('bar', 'foo');
        $this->assertSame($this->serviceManager, $ret);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::setAlias
     */
    public function testSetAliasThrowsExceptionOnInvalidAliasName()
    {
        $this->setExpectedException('Zend\ServiceManager\Exception\InvalidServiceNameException');
        $this->serviceManager->setAlias(5, 10);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::setAlias
     */
    public function testSetAliasThrowsExceptionOnEmptyAliasName()
    {
        $this->setExpectedException('Zend\ServiceManager\Exception\InvalidServiceNameException');
        $this->serviceManager->setAlias('', 'foo');
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::setAlias
     */
    public function testSetAliasThrowsExceptionOnDuplicateAlias()
    {
        $this->serviceManager->setService('foo', 'bar');
        $this->serviceManager->setAlias('baz', 'foo');

        $this->setExpectedException('Zend\ServiceManager\Exception\InvalidServiceNameException');
        $this->serviceManager->setAlias('baz', 'foo');
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::setAlias
     */
    public function testSetAliasDoesNotThrowExceptionOnServiceNotFound()
    {
        $this->serviceManager->setAlias('foo', 'bar');
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::get
     */
    public function testGetServiceThrowsExceptionOnAliasWithNoSetService()
    {
        $this->setExpectedException('Zend\ServiceManager\Exception\ServiceNotFoundException');
        $this->serviceManager->setAlias('foo', 'bar');
        $this->serviceManager->get('foo');
    }

    /**
     * @cover \OcraServiceManager\ServiceManager\LoggedServiceManager::get
     */
    public function testGetServiceThrowsExceptionOnMultipleAliasesWithNoSetService()
    {
        $this->setExpectedException('Zend\ServiceManager\Exception\ServiceNotFoundException');
        $this->serviceManager->setAlias('foo', 'bar');
        $this->serviceManager->setAlias('baz', 'foo');
        $this->serviceManager->get('foo');
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::hasAlias
     */
    public function testHasAlias()
    {
        $this->assertFalse($this->serviceManager->hasAlias('foo'));

        $this->serviceManager->setService('bar', 'baz');
        $this->serviceManager->setAlias('foo', 'bar');
        $this->assertTrue($this->serviceManager->hasAlias('foo'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::createScopedServiceManager
     */
    public function testCreateScopedServiceManager()
    {
        $this->serviceManager->setService('foo', 'bar');
        $scopedServiceManager = $this->serviceManager->createScopedServiceManager();
        $this->assertNotSame($this->serviceManager, $scopedServiceManager);
        $this->assertFalse($scopedServiceManager->has('foo', true, false));

        $this->assertContains($this->serviceManager, $this->readAttribute($scopedServiceManager, 'peeringServiceManagers'));

        // test child scoped
        $childScopedServiceManager = $this->serviceManager->createScopedServiceManager(BaseServiceManager::SCOPE_CHILD);
        $this->assertContains($childScopedServiceManager, $this->readAttribute($this->serviceManager, 'peeringServiceManagers'));
    }

    public function testConfigureWithInvokableClass()
    {
        $config = new Config(array(
            'invokables' => array(
                'foo' => 'ZendTest\ServiceManager\TestAsset\Foo',
            ),
        ));
        $serviceManager = new LoggedServiceManager(
            $this->getMock('Zend\\EventManager\\EventManagerInterface'),
            new BaseServiceManager($config)
        );
        $foo = $serviceManager->get('foo');
        $this->assertInstanceOf('ZendTest\ServiceManager\TestAsset\Foo', $foo);
    }

    public function testPeeringService()
    {
        $di = new Di();
        $di->instanceManager()->setParameters('ZendTest\ServiceManager\TestAsset\Bar', array('foo' => array('a')));
        $this->serviceManager->addAbstractFactory(new DiAbstractServiceFactory($di));
        $sm = $this->serviceManager->createScopedServiceManager(BaseServiceManager::SCOPE_PARENT);
        $sm->setFactory('di', new DiFactory());
        $bar = $sm->get('ZendTest\ServiceManager\TestAsset\Bar', true);
        $this->assertInstanceOf('ZendTest\ServiceManager\TestAsset\Bar', $bar);
    }

    public function testDiAbstractServiceFactory()
    {
        $di = $this->getMock('Zend\Di\Di');
        $factory = new DiAbstractServiceFactory($di);
        $factory->instanceManager()->setConfig('ZendTest\ServiceManager\TestAsset\Bar', array('parameters' => array('foo' => array('a'))));
        $this->serviceManager->addAbstractFactory($factory);

        $this->assertTrue($this->serviceManager->has('ZendTest\ServiceManager\TestAsset\Bar', true));

        $bar = $this->serviceManager->get('ZendTest\ServiceManager\TestAsset\Bar', true);
        $this->assertInstanceOf('ZendTest\ServiceManager\TestAsset\Bar', $bar);
    }

    public function testExceptionThrowingFactory()
    {
        $this->serviceManager->setFactory('foo', 'ZendTest\ServiceManager\TestAsset\ExceptionThrowingFactory');
        try {
            $this->serviceManager->get('foo');
            $this->fail("No exception thrown");
        } catch (Exception\ServiceNotCreatedException $e) {
            $this->assertInstanceOf('ZendTest\ServiceManager\TestAsset\FooException', $e->getPrevious());
        }
    }

    /**
     * @expectedException \Zend\ServiceManager\Exception\ServiceNotFoundException
     */
    public function testCannotUseUnknownServiceNameForAbstractFactory()
    {
        $config = new Config(array(
            'abstract_factories' => array(
                'ZendTest\ServiceManager\TestAsset\FooAbstractFactory',
            ),
        ));
        $serviceManager = new LoggedServiceManager(
            $this->getMock('Zend\\EventManager\\EventManagerInterface'),
            new BaseServiceManager($config)
        );
        $serviceManager->setFactory('foo', 'ZendTest\ServiceManager\TestAsset\FooFactory');
        $foo = $serviceManager->get('unknownObject');
    }

    /**
     * @expectedException \Zend\ServiceManager\Exception\ServiceNotCreatedException
     */
    public function testDoNotFallbackToAbstractFactory()
    {
        $factory = function ($sm) {
            return new TestAsset\Bar();
        };
        $serviceManager = new LoggedServiceManager(
            $this->getMock('Zend\\EventManager\\EventManagerInterface'),
            new BaseServiceManager()
        );
        $serviceManager->setFactory('ZendTest\ServiceManager\TestAsset\Bar', $factory);
        $di = new Di();
        $di->instanceManager()->setParameters('ZendTest\ServiceManager\TestAsset\Bar', array('foo' => array('a')));
        $serviceManager->addAbstractFactory(new DiAbstractServiceFactory($di));
        $bar = $serviceManager->get('ZendTest\ServiceManager\TestAsset\Bar');
    }

    /**
     * @expectedException \Zend\ServiceManager\Exception\InvalidServiceNameException
     */
    public function testAssignAliasWithExistingServiceName()
    {
        $this->serviceManager->setFactory('foo', 'ZendTest\ServiceManager\TestAsset\FooFactory');
        $this->serviceManager->setFactory('bar', function ($sm) {
            return new Bar(array('a'));
        });
        $this->serviceManager->setAllowOverride(false);
        // should throw an exception because 'foo' already exists in the service manager
        $this->serviceManager->setAlias('foo', 'bar');
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::createFromAbstractFactory
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::has
     */
    public function testWillNotCreateCircularReferences()
    {
        $abstractFactory = new TestAsset\CircularDependencyAbstractFactory();
        $sm = new LoggedServiceManager(
            $this->getMock('Zend\\EventManager\\EventManagerInterface'),
            new BaseServiceManager()
        );
        $sm->addAbstractFactory($abstractFactory);
        $foo = $sm->get('foo');
        $this->assertSame($abstractFactory->expectedInstance, $foo);
    }

    public function testShouldAllowAddingInitializersAsClassNames()
    {
        $result = $this->serviceManager->addInitializer('ZendTest\ServiceManager\TestAsset\FooInitializer');
        $this->assertSame($this->serviceManager, $result);
    }

    public function testShouldRaiseExceptionIfInitializerClassIsNotAnInitializerInterfaceImplementation()
    {
        $this->setExpectedException('Zend\ServiceManager\Exception\InvalidArgumentException');
        $result = $this->serviceManager->addInitializer(get_class($this));
    }

    public function duplicateService()
    {
        $self = $this;

        return array(
            array(
                'setFactory',
                function ($services) use ($self) {
                    return $self;
                },
                $self,
                'assertSame',
            ),
            array(
                'setInvokableClass',
                'stdClass',
                'stdClass',
                'assertInstanceOf',
            ),
            array(
                'setService',
                $self,
                $self,
                'assertSame',
            ),
        );
    }

    /**
     * @dataProvider duplicateService
     */
    public function testWithAllowOverrideOnRegisteringAServiceDuplicatingAnExistingAliasShouldInvalidateTheAlias($method, $service, $expected, $assertion = 'assertSame')
    {
        $this->serviceManager->setAllowOverride(true);
        $sm = $this->serviceManager;
        $this->serviceManager->setFactory('http.response', function () use ($sm) {
            return $sm;
        });
        $this->serviceManager->setAlias('response', 'http.response');
        $this->assertSame($sm, $this->serviceManager->get('response'));

        $this->serviceManager->{$method}('response', $service);
        $this->{$assertion}($expected, $this->serviceManager->get('response'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::canonicalizeName
     */
    public function testCanonicalizeName()
    {
        $this->serviceManager->setService('foo_bar', new \stdClass());
        $this->assertEquals(true, $this->serviceManager->has('foo_bar'));
        $this->assertEquals(true, $this->serviceManager->has('foobar'));
        $this->assertEquals(true, $this->serviceManager->has('foo-bar'));
        $this->assertEquals(true, $this->serviceManager->has('foo/bar'));
        $this->assertEquals(true, $this->serviceManager->has('foo bar'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::canCreateFromAbstractFactory
     */
    public function testCanCreateFromAbstractFactoryWillNotInstantiateAbstractFactoryOnce()
    {
        $count = FooCounterAbstractFactory::$instantiationCount;
        $this->serviceManager->addAbstractFactory(__NAMESPACE__ . '\TestAsset\FooCounterAbstractFactory');

        $this->serviceManager->canCreateFromAbstractFactory('foo', 'foo');
        $this->serviceManager->canCreateFromAbstractFactory('foo', 'foo');

        $this->assertSame($count + 1, FooCounterAbstractFactory::$instantiationCount);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::canCreateFromAbstractFactory
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::create
     */
    public function testAbstractFactoryNotUsedIfNotAbleToCreate()
    {
        $service = new \stdClass;

        $af1 = $this->getMock('Zend\ServiceManager\AbstractFactoryInterface');
        $af1->expects($this->any())->method('canCreateServiceWithName')->will($this->returnValue(true));
        $af1->expects($this->any())->method('createServiceWithName')->will($this->returnValue($service));

        $af2 = $this->getMock('Zend\ServiceManager\AbstractFactoryInterface');
        $af2->expects($this->any())->method('canCreateServiceWithName')->will($this->returnValue(false));
        $af2->expects($this->never())->method('createServiceWithName');

        $this->serviceManager->addAbstractFactory($af1);
        $this->serviceManager->addAbstractFactory($af2);

        $this->assertSame($service, $this->serviceManager->create('test'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::setAlias
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::get
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::retrieveFromPeeringManager
     */
    public function testCanGetAliasedServicesFromPeeringServiceManagers()
    {
        $service   = new \stdClass();
        $peeringSm = new LoggedServiceManager(
            $this->getMock('Zend\\EventManager\\EventManagerInterface'),
            new BaseServiceManager()
        );

        $peeringSm->setService('actual-service-name', $service);
        $this->serviceManager->addPeeringServiceManager($peeringSm);

        $this->serviceManager->setAlias('alias-name', 'actual-service-name');

        $this->assertSame($service, $this->serviceManager->get('alias-name'));
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::registerServiceCall
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::getDependencies
     */
    public function testGetDependencies()
    {
        $this->serviceManager->setFactory('root', function (ServiceLocatorInterface $sl) {
            $root = new \stdClass();
            $root->dep1 = $sl->get('dep1');
            $root->dep2 = $sl->get('dep2');

            return $root;
        });
        $this->serviceManager->setInvokableClass('dep1', 'stdClass');
        $this->serviceManager->setInvokableClass('dep2', 'stdClass');

        $dependencies = $this->serviceManager->getDependencies('root');
        $this->assertCount(2, $dependencies);
        $this->assertContains('dep1', $dependencies);
        $this->assertContains('dep2', $dependencies);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::registerServiceCall
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::getDepending
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::getParentRequestingService
     */
    public function testGetDepending()
    {
        $this->serviceManager->setFactory('depending1', function (ServiceLocatorInterface $sl) {
            $depending1 = new \stdClass();
            $depending1->dep1 = $sl->get('dep1');
            $depending1->dep2 = $sl->get('dep2');

            return $depending1;
        });
        $this->serviceManager->setFactory('depending2', function (ServiceLocatorInterface $sl) {
            $depending2 = new \stdClass();
            $depending2->dep1 = $sl->get('dep1');

            return $depending2;
        });
        $this->serviceManager->setInvokableClass('dep1', 'stdClass');
        $this->serviceManager->setInvokableClass('dep2', 'stdClass');

        $this->serviceManager->get('depending1');
        $this->serviceManager->get('depending2');

        $depending = $this->serviceManager->getDepending('dep1');
        $this->assertCount(2, $depending);
        $this->assertContains('depending1', $depending);
        $this->assertContains('depending2', $depending);

        $depending = $this->serviceManager->getDepending('dep2');
        $this->assertCount(1, $depending);
        $this->assertContains('depending1', $depending);
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::getLoggedServices
     */
    public function testGetLoggedServices()
    {
        $this->serviceManager->setFactory('depending1', function (ServiceLocatorInterface $sl) {
            $depending1 = new \stdClass();
            $depending1->dep1 = $sl->get('dep1');
            $depending1->dep2 = $sl->get('dep2');

            return $depending1;
        });
        $this->serviceManager->setFactory('depending2', function (ServiceLocatorInterface $sl) {
            $depending2 = new \stdClass();
            $depending2->dep1 = $sl->get('dep1');

            return $depending2;
        });
        $this->serviceManager->setInvokableClass('dep1', 'stdClass');
        $this->serviceManager->setInvokableClass('dep2', 'stdClass');

        $this->serviceManager->get('depending1');
        $this->serviceManager->get('depending2');

        $loggedServices = $this->serviceManager->getLoggedServices();
        $this->assertCount(4, $loggedServices);
        $this->assertArrayHasKey('dep1', $loggedServices);
        $this->assertArrayHasKey('dep2', $loggedServices);
        $this->assertArrayHasKey('depending1', $loggedServices);
        $this->assertArrayHasKey('depending2', $loggedServices);
    }

    /**
     * Verify that dependencies are discovered also through initialized objects
     * implementing {@see \Zend\ServiceManager\ServiceLocatorAwareInterface}
     *
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::registerServiceCall
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::getDepending
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::getDependencies
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::getParentRequestingService
     */
    public function testDiscoverDependenciesOnServiceLocatorAwareService()
    {
        $this->serviceManager->addInitializer(function ($instance, ServiceLocatorInterface $serviceLocator) {
            if ($instance instanceof ServiceLocatorAwareInterface) {
                $instance->setServiceLocator($serviceLocator);
            }
        });

        $this->serviceManager->setInvokableClass(
            'aware-foo',
            'OcraServiceManagerTest\ServiceManager\TestAsset\ServiceLocatorAwareFoo'
        );

        $this->serviceManager->setInvokableClass('foo', 'OcraServiceManagerTest\ServiceManager\TestAsset\Foo');

        /* @var $awareFoo \OcraServiceManagerTest\ServiceManager\TestAsset\ServiceLocatorAwareFoo */
        $awareFoo = $this->serviceManager->get('aware-foo');
        $this->assertInstanceOf('OcraServiceManagerTest\ServiceManager\TestAsset\ServiceLocatorAwareFoo', $awareFoo);
        $this->assertSame($this->serviceManager, $awareFoo->getServiceLocator());
        // triggering fetch of "foo" in the service
        $this->assertInstanceOf('OcraServiceManagerTest\ServiceManager\TestAsset\Foo', $awareFoo->getFoo());

        $this->assertSame(array('awarefoo'), $this->serviceManager->getDepending('foo'));
        $this->assertSame(array('foo'), $this->serviceManager->getDependencies('awarefoo'));
    }

    /**
     * Verify that dependencies are discovered also through initialized objects
     * implementing {@see \Zend\ServiceManager\ServiceManagerAwareInterface}
     *
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::registerServiceCall
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::getDepending
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::getDependencies
     * @covers \OcraServiceManager\ServiceManager\LoggedServiceManager::getParentRequestingService
     */
    public function testDiscoverDependenciesOnServiceManagerAwareService()
    {
        $this->serviceManager->addInitializer(function ($instance, ServiceLocatorInterface $serviceLocator) {
            if ($instance instanceof ServiceManagerAwareInterface) {
                $instance->setServiceManager($serviceLocator);
            }
        });

        $this->serviceManager->setInvokableClass(
            'aware-foo',
            'OcraServiceManagerTest\ServiceManager\TestAsset\ServiceManagerAwareFoo'
        );

        $this->serviceManager->setInvokableClass('foo', 'OcraServiceManagerTest\ServiceManager\TestAsset\Foo');

        /* @var $awareFoo \OcraServiceManagerTest\ServiceManager\TestAsset\ServiceManagerAwareFoo */
        $awareFoo = $this->serviceManager->get('aware-foo');
        $this->assertInstanceOf('OcraServiceManagerTest\ServiceManager\TestAsset\ServiceManagerAwareFoo', $awareFoo);
        // triggering fetch of "foo" in the service
        $this->assertInstanceOf('OcraServiceManagerTest\ServiceManager\TestAsset\Foo', $awareFoo->getFoo());

        $this->assertSame(array('awarefoo'), $this->serviceManager->getDepending('foo'));
        $this->assertSame(array('foo'), $this->serviceManager->getDependencies('awarefoo'));
    }
}
