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

namespace OcraServiceManagerTest\DelegatorFactory;

use OcraServiceManager\DelegatorFactory\ServiceLocatorDelegatorFactory;
use stdClass;

use PHPUnit_Framework_TestCase;

/**
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class ServiceLocatorDelegatorFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers \OcraServiceManager\DelegatorFactory\ServiceLocatorDelegatorFactory
     */
    public function testProducesLocatorProxy()
    {
        $proxyFactory = $this->getMock('ProxyManager\\Factory\\AccessInterceptorScopeLocalizerFactory');
        $instance     = $this->getMock('Zend\\ServiceManager\\ServiceLocatorInterface');
        $proxyLocator = $this->getMock('Zend\\ServiceManager\\ServiceLocatorInterface');

        $proxyFactory
            ->expects($this->any())
            ->method('createProxy')
            ->with($instance, array(), array('foo'))
            ->will($this->returnValue($proxyLocator));

        $factory = new ServiceLocatorDelegatorFactory($proxyFactory, array('foo'));

        $this->assertSame(
            $proxyLocator,
            $factory->createDelegatorWithName(
                $this->getMock('Zend\\ServiceManager\\ServiceLocatorInterface'),
                'fooBar',
                'fooBaz',
                function () use ($instance) {
                    return $instance;
                }
            )
        );
    }

    /**
     * @covers \OcraServiceManager\DelegatorFactory\ServiceLocatorDelegatorFactory
     */
    public function testSkipsNonLocatorInstances()
    {
        $proxyFactory = $this->getMock('ProxyManager\\Factory\\AccessInterceptorScopeLocalizerFactory');
        $instance     = new stdClass();

        $factory = new ServiceLocatorDelegatorFactory($proxyFactory, array('foo'));

        $this->assertSame(
            $instance,
            $factory->createDelegatorWithName(
                $this->getMock('Zend\\ServiceManager\\ServiceLocatorInterface'),
                'fooBar',
                'fooBaz',
                function () use ($instance) {
                    return $instance;
                }
            )
        );
    }
}
