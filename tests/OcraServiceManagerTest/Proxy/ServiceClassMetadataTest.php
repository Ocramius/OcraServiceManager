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

namespace OcraServiceManagerTest\Proxy;

use OcraServiceManager\Proxy\ServiceClassMetadata;
use PHPUnit_Framework_TestCase;

use OcraServiceManagerTest\TestAsset\PublicPropertiesLazyService;

/**
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class ServiceClassMetadataTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers \OcraServiceManager\Proxy\ServiceClassMetadata::__construct
     * @covers \OcraServiceManager\Proxy\ServiceClassMetadata::getReflectionClass
     * @covers \OcraServiceManager\Proxy\ServiceClassMetadata::getName
     */
    public function testClassMetadataFromString()
    {
        $classMetadata = new ServiceClassMetadata('stdClass');
        $this->assertSame('stdClass', $classMetadata->getName());
        $this->assertInstanceOf('ReflectionClass', $classMetadata->getReflectionClass());
    }

    /**
     * @covers \OcraServiceManager\Proxy\ServiceClassMetadata::__construct
     * @covers \OcraServiceManager\Proxy\ServiceClassMetadata::getReflectionClass
     * @covers \OcraServiceManager\Proxy\ServiceClassMetadata::getName
     */
    public function testClassMetadataFromObject()
    {
        $classMetadata = new ServiceClassMetadata(new \stdClass());
        $this->assertSame('stdClass', $classMetadata->getName());
        $this->assertInstanceOf('ReflectionClass', $classMetadata->getReflectionClass());
    }

    /**
     * @covers \OcraServiceManager\Proxy\ServiceClassMetadata::getReflectionClass
     * @covers \OcraServiceManager\Proxy\ServiceClassMetadata::getName
     * @covers \OcraServiceManager\Proxy\ServiceClassMetadata::getFieldNames
     * @covers \OcraServiceManager\Proxy\ServiceClassMetadata::hasField
     */
    public function testGetFields()
    {
        $classMetadata = new ServiceClassMetadata(new PublicPropertiesLazyService());
        $this->assertSame('OcraServiceManagerTest\TestAsset\PublicPropertiesLazyService', $classMetadata->getName());
        $this->assertInstanceOf('ReflectionClass', $classMetadata->getReflectionClass());
        $this->assertSame(array('checkedProperty'), $classMetadata->getFieldNames());
        $this->assertTrue($classMetadata->hasField('checkedProperty'));
        $this->assertFalse($classMetadata->hasField('non_existing'));
    }
}
