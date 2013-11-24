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

namespace OcraServiceManager\DelegatorFactory;

use ProxyManager\Factory\AccessInterceptorScopeLocalizerFactory;
use Zend\ServiceManager\DelegatorFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory responsible of building
 * an {@see \ProxyManager\Factory\AccessInterceptorScopeLocalizerFactory}
 *
 * Note: the double "factory" in the class name is actually because we're
 * producing another factory here.
 *
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class ServiceLocatorDelegatorFactory implements DelegatorFactoryInterface
{
    /**
     * @var \ProxyManager\Factory\AccessInterceptorScopeLocalizerFactory
     */
    private $proxyFactory;

    /**
     * @var \Closure[]
     */
    private $locatorInterceptors;

    /**
     * @param AccessInterceptorScopeLocalizerFactory $proxyFactory
     * @param \Closure[]                             $locatorInterceptors
     */
    public function __construct(AccessInterceptorScopeLocalizerFactory $proxyFactory, array $locatorInterceptors)
    {
        $this->proxyFactory        = $proxyFactory;
        $this->locatorInterceptors = $locatorInterceptors;
    }

    /**
     * {@inheritDoc}
     */
    public function createDelegatorWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName, $callback)
    {
        $service = call_user_func($callback);

        if (! $service instanceof ServiceLocatorInterface) {
            return $service;
        }

        // @todo maybe this should be a callback, and `locatorInterceptors` should not be used explicitly
        return $this->proxyFactory->createProxy($service, array(), $this->locatorInterceptors);
    }
}
