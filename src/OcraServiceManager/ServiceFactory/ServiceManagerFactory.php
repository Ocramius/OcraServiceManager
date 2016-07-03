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

namespace OcraServiceManager\ServiceFactory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;

/**
 * Factory responsible of building an {@see OcraServiceManager\ServiceManager}
 *
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class ServiceManagerFactory
{
    /**
     * Create an overloaded service manager
     *
     * @param  ServiceLocatorInterface $container
     *
     * @return ServiceLocatorInterface
     */
    public function createService(ContainerInterface $container)
    {
        $config = $container->get('Config');

        if (! $config['ocra_service_manager']['logged_service_manager']) {
            return $container;
        }

        /* @var $proxyFactory \ProxyManager\Factory\AccessInterceptorScopeLocalizerFactory */
        $proxyFactory = $container->get('OcraServiceManager\\ServiceManager\\AccessInterceptorProxyFactory');
        /* @var $locatorInterceptors \Closure[] */
        $locatorInterceptors = $container->get('OcraServiceManager\\ServiceManager\\AccessInterceptors');

        // @todo maybe this should be a callback, and `locatorInterceptors` should not be used explicitly
        $proxyLocator = $proxyFactory->createProxy($container, array(), $locatorInterceptors);

        if (! $container instanceof ServiceManager) {
            return $proxyLocator;
        }

        // @todo this service hardcoding should be removed
        $allowOverrides = $container->getAllowOverride();

        $container->setAllowOverride(true);
        $container->setService('ServiceManager', $proxyLocator);
        $container->setAllowOverride($allowOverrides);

        return $proxyLocator;
    }
}
