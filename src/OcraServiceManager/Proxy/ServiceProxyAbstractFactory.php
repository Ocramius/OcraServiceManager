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

use OcraServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\AbstractFactoryInterface;

use Zend\Cache\Storage\StorageInterface;

use Doctrine\Common\Proxy\ProxyGenerator;
use Doctrine\Common\Proxy\Proxy;

/**
 * Abstract Service Factory responsible of generating lazy service instances that double
 * the functionality of the actually requested ones.
 *
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class ServiceProxyAbstractFactory implements AbstractFactoryInterface
{
    /**
     * @var ProxyGenerator
     */
    private $proxyGenerator;

    /**
     * @var StorageInterface used to store the proxy definitions
     */
    private $cache;

    public function __construct(StorageInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritDoc}
     *
     * @return Proxy|object
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $serviceName, $requestedName)
    {
        /* @var $serviceLocator ServiceManager */

        // FQCN is cached since we don't know anything about the requested service, and want to avoid instantiation
        if (( ! $fqcn = $this->cache->getItem($requestedName)) || ! class_exists($fqcn)) {
            $service        = $serviceLocator->createRealService(array($serviceName, $requestedName));
            $className      = get_class($service);
            $proxyGenerator = $this->getProxyGenerator();
            $fqcn           = $proxyGenerator->getProxyClassName($className);

            $proxyGenerator->generateProxyClass(new ServiceClassMetadata($className));
            require_once $proxyGenerator->getProxyFileName($className);

            $proxy = new $fqcn(null, null);
            $proxy->__wrappedObject__ = $service;
            $proxy->__setInitialized(true);
            $this->cache->setItem($requestedName, $fqcn);

            return $proxy;
        }

        return new $fqcn(
            function (Proxy $proxy) use ($serviceLocator, $serviceName, $requestedName) {
                $proxy->__setInitializer(null);
                $proxy->__setInitialized(true);
                $proxy->__wrappedObject__ = $serviceLocator->createRealService(array($serviceName, $requestedName));
            },
            null
        );
    }

    /**
     * {@inheritDoc}
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return $serviceLocator instanceof ServiceManager;
    }

    /**
     * @param ProxyGenerator $proxyGenerator
     */
    public function setProxyGenerator(ProxyGenerator $proxyGenerator)
    {
        $this->proxyGenerator = $proxyGenerator;
    }

    /**
     * @return ProxyGenerator
     */
    public function getProxyGenerator()
    {
        if (null === $this->proxyGenerator) {
            $this->proxyGenerator = new ServiceProxyGenerator();
        }

        return $this->proxyGenerator;
    }
}
