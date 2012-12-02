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

namespace OcraServiceManager;

use Zend\ServiceManager\ServiceManager as BaseServiceManager;
use Zend\ServiceManager\AbstractFactoryInterface;

/**
 * ServiceManager with additional capabilities, such as generating proxy
 * "lazy" services
 *
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class ServiceManager extends BaseServiceManager
{
    /**
     * Services to be proxied through a proxy factory, with keys being
     * the service names, and values being the service name of the proxy
     * factory responsible of instantiating them (false if default
     * proxy factory has to be used)
     *
     * @var array
     */
    protected $proxyServices = array();

    /**
     * Name of the abstract service factory responsible of instantiating
     * service proxies
     *
     * @var string
     */
    protected $defaultProxyFactory = 'OcraServiceManager\Proxy\ServiceProxyAbstractFactory';

    /**
     * @param BaseServiceManager $baseServiceManager
     */
    public function __construct(BaseServiceManager $baseServiceManager)
    {
        // localizing scope since we're proxying the base service manager
        $this->canonicalNames                   = & $baseServiceManager->canonicalNames;
        $this->allowOverride                    = & $baseServiceManager->allowOverride;
        $this->invokableClasses                 = & $baseServiceManager->invokableClasses;
        $this->factories                        = & $baseServiceManager->factories;
        $this->abstractFactories                = & $baseServiceManager->abstractFactories;
        $this->pendingAbstractFactoryRequests   = & $baseServiceManager->pendingAbstractFactoryRequests;
        $this->shared                           = & $baseServiceManager->shared;
        $this->instances                        = & $baseServiceManager->instances;
        $this->aliases                          = & $baseServiceManager->aliases;
        $this->initializers                     = & $baseServiceManager->initializers;
        $this->peeringServiceManagers           = & $baseServiceManager->peeringServiceManagers;
        $this->shareByDefault                   = & $baseServiceManager->shareByDefault;
        $this->retrieveFromPeeringManagerFirst  = & $baseServiceManager->retrieveFromPeeringManagerFirst;
        $this->throwExceptionInCreate           = & $baseServiceManager->throwExceptionInCreate;
        $this->canonicalNamesReplacements       = & $baseServiceManager->canonicalNamesReplacements;

        // setting 'ServiceManager' within the original service manager
        $allowOverride = $baseServiceManager->getAllowOverride();

        $baseServiceManager->setAllowOverride(true);
        $baseServiceManager->setService('ServiceManager', $this);
        $baseServiceManager->setAllowOverride($allowOverride);
    }

    /**
     * Mark the provided service as "proxy" and optionally assign an abstract factory
     * to be used to instantiate it
     *
     * @param string      $serviceName
     * @param string|null $proxyFactory
     */
    public function setProxyService($serviceName, $proxyFactory = null)
    {
        $this->proxyServices[$this->canonicalizeName($serviceName)] = $proxyFactory ? $proxyFactory : false;
    }

    /**
     * Retrieves services marked as "proxy"
     *
     * @return array
     */
    public function getProxyServices()
    {
        return $this->proxyServices;
    }

    /**
     * {@inheritDoc}
     */
    public function create($name)
    {
        if (is_array($name)) {
            list($cName, $rName) = $name;
        } else {
            $rName = $name;
            $cName = $this->canonicalizeName($rName);
        }

        if (isset($this->proxyServices[$cName])) {
            $factory      = $this->proxyServices[$cName] ? $this->proxyServices[$cName] : $this->defaultProxyFactory;
            /* @var $proxyFactory \Zend\ServiceManager\AbstractFactoryInterface */
            $proxyFactory = $this->get($factory);

            return $proxyFactory->createServiceWithName($this, $cName, $rName);
        }

        return parent::create($name);
    }

    /**
     * Creates a service ignoring proxy factories
     *
     * @see    \Zend\ServiceManager\ServiceManager::create
     *
     * @param  string|array   $name
     * @return object|boolean
     */
    public function createRealService($name)
    {
        return parent::create($name);
    }

    /**
     * Set the name of the default proxy factory to be used when instantiating proxy services
     *
     * @param string $defaultProxyFactory
     */
    public function setDefaultProxyFactory($defaultProxyFactory)
    {
        $this->defaultProxyFactory = $defaultProxyFactory;
    }
}
