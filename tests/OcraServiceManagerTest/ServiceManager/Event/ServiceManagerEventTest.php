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
use stdClass;

class ServiceManagerEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Logger
     */
    protected $listener;

    /**
     * {@inheritDoc}
     *
     * @covers \OcraServiceManager\ServiceManager\Event\ServiceManagerEvent
     */
    public function testBuildEvent()
    {
        $locator  = $this->getMock('Zend\\ServiceManager\\ServiceLocatorInterface');
        $instance = new stdClass();
        $event    = new ServiceManagerEvent('testEventName', $locator, $instance, 'serviceName', 'serviceName');

        $this->assertSame('testEventName', $event->getName());
        $this->assertSame($locator, $event->getServiceLocator());
        $this->assertSame('serviceName', $event->getRequestedName());
        $this->assertSame('serviceName', $event->getParam('requested_name'));
        $this->assertSame('serviceName', $event->getCanonicalName());
        $this->assertSame('serviceName', $event->getParam('canonical_name'));
        $this->assertSame($instance, $event->getInstance());
        $this->assertSame($instance, $event->getParam('instance'));

        $trace = $event->getTrace();

        $this->assertInternalType('array', $trace);
        $this->assertSame($trace, $event->getParam('trace'));

        $this->assertSame($this, $trace[1]['object']);
    }
}
