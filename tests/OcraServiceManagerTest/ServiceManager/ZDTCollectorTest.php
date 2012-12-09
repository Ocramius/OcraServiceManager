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

use OcraServiceManager\ServiceManager\ZDTCollector;

class ZDTCollectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \OcraServiceManager\ServiceManager\ZDTCollector::__construct
     * @covers \OcraServiceManager\ServiceManager\ZDTCollector::collect
     * @covers \OcraServiceManager\ServiceManager\ZDTCollector::getServices
     * @covers \OcraServiceManager\ServiceManager\ZDTCollector::couldCollect
     */
    public function testGetServices()
    {
        $locator = $this->getMock('OcraServiceManager\ServiceManager\LoggedServiceManager', array(), array(), '', false);
        $collector = new ZDTCollector($locator);
        $locator->expects($this->any())->method('getLoggedServices')->will($this->returnValue(array('a' => array())));
        $collector->collect($this->getMock('Zend\Mvc\MvcEvent'));
        $this->assertTrue($collector->couldCollect());
        $this->assertSame(array('a' => array()), $collector->getServices());
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\ZDTCollector::__construct
     * @covers \OcraServiceManager\ServiceManager\ZDTCollector::collect
     * @covers \OcraServiceManager\ServiceManager\ZDTCollector::getServices
     * @covers \OcraServiceManager\ServiceManager\ZDTCollector::couldCollect
     */
    public function testWillNotCollectWithoutLoggedServiceManager()
    {
        $collector = new ZDTCollector($this->getMock('Zend\ServiceManager\ServiceLocatorInterface'));
        $collector->collect($this->getMock('Zend\Mvc\MvcEvent'));
        $this->assertFalse($collector->couldCollect());
        $this->assertSame(array(), $collector->getServices());
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\ZDTCollector::getPriority
     */
    public function testGetPriority()
    {
        $collector = new ZDTCollector($this->getMock('Zend\ServiceManager\ServiceLocatorInterface'));
        $this->assertInternalType('int', $collector->getPriority());
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\ZDTCollector::getName
     */
    public function testGetName()
    {
        $collector = new ZDTCollector($this->getMock('Zend\ServiceManager\ServiceLocatorInterface'));
        $this->assertInternalType('string', $collector->getName());
    }

    /**
     * @covers \OcraServiceManager\ServiceManager\ZDTCollector::serialize
     * @covers \OcraServiceManager\ServiceManager\ZDTCollector::unserialize
     */
    public function testSerializeUnserialize()
    {
        $locator = $this->getMock('OcraServiceManager\ServiceManager\LoggedServiceManager', array(), array(), '', false);
        $collector = new ZDTCollector($locator);
        $locator->expects($this->any())->method('getLoggedServices')->will($this->returnValue(array('a' => array())));
        $collector->collect($this->getMock('Zend\Mvc\MvcEvent'));
        $collector = unserialize(serialize($collector));
        $this->assertTrue($collector->couldCollect());
        $this->assertSame(array('a' => array()), $collector->getServices());
    }
}
