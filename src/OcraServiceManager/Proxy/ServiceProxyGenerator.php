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

use Doctrine\Common\Proxy\ProxyGenerator;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * Proxy generator responsible of creating proxy classes that delegate method calls to a wrapped service
 *
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class ServiceProxyGenerator extends ProxyGenerator
{
    const DEFAULT_SERVICE_PROXY_NS = 'Zend\\ServiceManager\\Proxy';

    /**
     * {@inheritDoc}
     *
     * @param string|null $proxyDir
     * @param string|null $proxyNs
     */
    public function __construct($proxyDir = null, $proxyNs = null)
    {
        $proxyDir = $proxyDir ?: sys_get_temp_dir();
        $proxyNs  = $proxyNs  ?: static::DEFAULT_SERVICE_PROXY_NS;

        parent::__construct($proxyDir, $proxyNs);

        $this->setPlaceholders(array(
            '<magicGet>'             => array($this, 'generateMagicGet'),
            '<magicSet>'             => array($this, 'generateMagicSet'),
            '<magicIsset>'           => array($this, 'generateMagicIsset'),
            '<sleepImpl>'            => array($this, 'generateSleepImpl'),
            '<wakeupImpl>'           => array($this, 'generateWakeupImpl'),
            '<cloneImpl>'            => array($this, 'generateCloneImpl'),
            '<methods>'              => array($this, 'generateMethods'),
            '<additionalProperties>' => <<<'EOT'

    /**
     * @var object wrapped object to which method calls will be forwarded"
     */
    public $__wrappedObject__;
EOT
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function generateMagicGet(ClassMetadata $class)
    {
        $inheritDoc = $class->getReflectionClass()->hasMethod('__get') ? "\n     * {@inheritDoc}\n     *" : '';

        return <<<EOD
    /**$inheritDoc
     *
     * @param string \$name
     */
    public function __get(\$name)
    {
        \$this->__initializer__ && \$this->__initializer__->__invoke(\$this, '__get', array(\$name));

        return \$this->__wrappedObject__->\$name;
    }
EOD;
    }

    /**
     * {@inheritDoc}
     */
    public function generateMagicSet(ClassMetadata $class)
    {
        $inheritDoc = $class->getReflectionClass()->hasMethod('__set') ? "\n     * {@inheritDoc}\n     *" : '';

        return <<<EOD
    /**$inheritDoc
     *
     * @param string \$name
     * @param mixed  \$value"
     */
    public function __set(\$name, \$value)
    {
        \$this->__initializer__ && \$this->__initializer__->__invoke(\$this, '__set', array(\$name, \$value));

        \$this->__wrappedObject__->\$name = \$value;
    }
EOD;
    }

    /**
     * {@inheritDoc}
     */
    public function generateMagicIsset(ClassMetadata $class)
    {
        $inheritDoc = $class->getReflectionClass()->hasMethod('__isset') ? "\n     * {@inheritDoc}\n     *" : '';

        return <<<EOD
    /**$inheritDoc
     *
     * @param string \$name
     */
    public function __isset(\$name)
    {
        \$this->__initializer__ && \$this->__initializer__->__invoke(\$this, '__isset', array(\$name));

        return isset(\$this->__wrappedObject__->\$name);
    }
EOD;
    }

    /**
     * {@inheritDoc}
     */
    public function generateSleepImpl(ClassMetadata $class)
    {
        $inheritDoc = $class->getReflectionClass()->hasMethod('__sleep') ? "\n     * {@inheritDoc}\n     *" : '';

        return <<<EOD
    /**$inheritDoc
     *
     */
    public function __sleep()
    {
        \$this->__initializer__ && \$this->__initializer__->__invoke(\$this, '__sleep', array());

        return array('__isInitialized__', '__wrappedObject__');
    }
EOD;
    }

    /**
     * {@inheritDoc}
     */
    public function generateWakeupImpl(ClassMetadata $class)
    {
        $unsetProperties = $class->getFieldNames();

        if (empty($unsetProperties)) {
            return '';
        }

        $unsetProperties = '$this->' . implode(', $this->', $unsetProperties);

        $inheritDoc = $class->getReflectionClass()->hasMethod('__wakeup') ? "\n     * {@inheritDoc}\n     *" : '';

        return <<<EOD
    /**$inheritDoc
     *
     */
    public function __wakeup()
    {
        unset($unsetProperties);
    }
EOD;
    }

    /**
     * {@inheritDoc}
     */
    public function generateCloneImpl(ClassMetadata $class)
    {
        $inheritDoc = $class->getReflectionClass()->hasMethod('__clone') ? "\n     * {@inheritDoc}\n     *" : '';

        return <<<EOD
    /**$inheritDoc
     *
     */
    public function __clone()
    {
        \$this->__initializer__ && \$this->__initializer__->__invoke(\$this, '__clone', array());

        \$this->__wrappedObject__ = clone \$this->__wrappedObject__;
    }
EOD;
    }

    /**
     * {@inheritDoc}
     */
    public function generateMethods(ClassMetadata $class)
    {
        $methods            = '';
        $methodNames        = array();
        $reflectionMethods  = $class->getReflectionClass()->getMethods(\ReflectionMethod::IS_PUBLIC);
        $excludedMethods    = array(
            '__get'    => true,
            '__set'    => true,
            '__isset'  => true,
            '__clone'  => true,
            '__sleep'  => true,
            '__wakeup' => true,
        );

        foreach ($reflectionMethods as $method) {
            $name = $method->getName();

            if (
                $method->isConstructor()
                || isset($methodNames[$name])
                || isset($excludedMethods[strtolower($name)])
                || $method->isFinal()
                || $method->isStatic()
                || ! $method->isPublic()
            ) {
                continue;
            }

            $methodNames[$name] = true;
            $methods .= "\n    /**\n"
                . "     * {@inheritDoc}\n"
                . "     */\n"
                . '    public function ';

            if ($method->returnsReference()) {
                $methods .= '&';
            }

            $methods .= $name . '(';
            $firstParam = true;
            $parameterString = $argumentString = '';
            $parameters = array();

            foreach ($method->getParameters() as $param) {
                if ($firstParam) {
                    $firstParam = false;
                } else {
                    $parameterString .= ', ';
                    $argumentString  .= ', ';
                }

                $paramClass = $param->getClass();

                // We need to pick the type hint class too
                if (null !== $paramClass) {
                    $parameterString .= '\\' . $paramClass->getName() . ' ';
                } elseif ($param->isArray()) {
                    $parameterString .= 'array ';
                }

                if ($param->isPassedByReference()) {
                    $parameterString .= '&';
                }

                $parameters[] = '$' . $param->getName();
                $parameterString .= '$' . $param->getName();
                $argumentString  .= '$' . $param->getName();

                if ($param->isDefaultValueAvailable()) {
                    $parameterString .= ' = ' . var_export($param->getDefaultValue(), true);
                }
            }

            $methods .= $parameterString . ')';
            $methods .= "\n" . '    {' . "\n";

            $methods .= "\n        \$this->__initializer__ "
                . "&& \$this->__initializer__->__invoke(\$this, " . var_export($name, true)
                . ", array(" . implode(', ', $parameters) . "));"
                . "\n\n        return \$this->__wrappedObject__->" . $name . '(' . $argumentString . ');'
                . "\n" . '    }' . "\n";
        }

        return $methods;
    }
}
