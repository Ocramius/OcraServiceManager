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

namespace OcraServiceManager\Proxy;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use ReflectionClass;

use Zend\ServiceManager\Exception\RuntimeException;

/**
 * Class metadata for a generic service object
 *
 * @author   Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class ServiceClassMetadata implements ClassMetadata
{
    /**
     * @var ReflectionClass
     */
    protected $reflectionClass;

    /**
     * @param object|string $service
     */
    public function __construct($service)
    {
        $this->reflectionClass = new ReflectionClass($service);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->reflectionClass->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function getReflectionClass()
    {
        return $this->reflectionClass;
    }

    /**
     * {@inheritDoc}
     */
    public function hasField($fieldName)
    {
        return $this->reflectionClass->hasProperty($fieldName);
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldNames()
    {
        $properties = $this->reflectionClass->getProperties();
        $fields     = array();

        foreach ($properties as $property) {
            $fields[] = $property->getName();
        }

        return $fields;
    }

    // @codeCoverageIgnoreStart

    /**
     * {@inheritDoc}
     */
    public function hasAssociation($fieldName)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier()
    {
        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function isIdentifier($fieldName)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isSingleValuedAssociation($fieldName)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function isCollectionValuedAssociation($fieldName)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifierFieldNames()
    {
        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociationNames()
    {
        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeOfField($fieldName)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociationTargetClass($assocName)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function isAssociationInverseSide($assocName)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociationMappedByTargetField($assocName)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifierValues($object)
    {
        throw new RuntimeException('Not implemented');
    }

    // @codeCoverageIgnoreEnd
}
