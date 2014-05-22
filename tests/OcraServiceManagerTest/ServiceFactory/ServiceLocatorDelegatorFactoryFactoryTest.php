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

use OcraServiceManager\ServiceFactory\ServiceLocatorDelegatorFactoryFactory;

use PHPUnit_Framework_TestCase;

/**
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 *
 * @covers \OcraServiceManager\ServiceFactory\ServiceLocatorDelegatorFactoryFactory
 */
class ServiceLocatorDelegatorFactoryFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        /* @var $locator \Zend\ServiceManager\ServiceLocatorInterface */
        $locator = $this->getMock('Zend\\ServiceManager\\ServiceLocatorInterface');
        $factory = new ServiceLocatorDelegatorFactoryFactory();
        $scopeLocalizerFactory = $this->getMock('ProxyManager\\Factory\\AccessInterceptorScopeLocalizerFactory');

        $locator->expects($this->any())->method('get')->will($this->returnValueMap(array(
            array('OcraServiceManager\\ServiceManager\\AccessInterceptorProxyFactory', $scopeLocalizerFactory),
            array('OcraServiceManager\\ServiceManager\\AccessInterceptors', array()),
        )));

        $this->assertInstanceOf(
            'OcraServiceManager\DelegatorFactory\ServiceLocatorDelegatorFactory',
            $factory->createService($locator)
        );
    }
}
