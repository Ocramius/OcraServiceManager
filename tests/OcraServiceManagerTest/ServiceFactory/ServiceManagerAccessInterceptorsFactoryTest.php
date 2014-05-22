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

use OcraServiceManager\ServiceFactory\ServiceManagerAccessInterceptorsFactory;
use Zend\ServiceManager\ServiceManager;

use PHPUnit_Framework_TestCase;

/**
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 *
 * @covers \OcraServiceManager\ServiceFactory\ServiceManagerAccessInterceptorsFactory
 */
class ServiceManagerAccessInterceptorsFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        /* @var $locator \Zend\ServiceManager\ServiceLocatorInterface|\PHPUnit_Framework_MockObject_MockObject */
        $locator      = $this->getMock('Zend\\ServiceManager\\ServiceLocatorInterface');
        $eventManager = $this->getMock('Zend\\EventManager\\EventManagerInterface');
        $factory      = new ServiceManagerAccessInterceptorsFactory();

        $locator
            ->expects($this->any())
            ->method('get')
            ->with('OcraServiceManager\\ServiceManager\\EventManager')
            ->will($this->returnValue($eventManager));

        $interceptors = $factory->createService($locator);

        $this->assertArrayHasKey('get', $interceptors);
        $this->assertArrayHasKey('create', $interceptors);

        $this->assertInstanceOf('Closure', $interceptors['get']);
        $this->assertSame($interceptors['get'], $interceptors['create']);


    }
}
