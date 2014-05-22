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

namespace OcraServiceManager\ServiceManager;

use OcraServiceManager\ServiceManager\Event\ServiceManagerEvent;
use ProxyManager\Proxy\ProxyInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceManagerAwareInterface;

/**
 * Logger capable of tracking events related to service instantiation
 *
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class Logger implements ListenerAggregateInterface
{
    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $handlers = array();

    /**
     * Traces of instantiated objects, indexed by object hash
     *
     * @var array[] created services traces
     */
    protected $tracedCalls = array();

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events)
    {
        $this->handlers[] = $events->attach(
            ServiceManagerEvent::EVENT_SERVICEMANAGER_GET,
            array($this, 'logServiceLocatorGet')
        );
        $this->handlers[] = $events->attach(
            ServiceManagerEvent::EVENT_SERVICEMANAGER_GET,
            array($this, 'logServiceManagerCreate')
        );
    }

    /**
     * {@inheritDoc}
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->handlers as $index => $listener) {
            if (false !== $events->detach($listener)) {
                unset($this->handlers[$index]);
            }
        }
    }

    /**
     * @param ServiceManagerEvent $event
     *
     * @return array|bool
     */
    public function logServiceLocatorGet(ServiceManagerEvent $event)
    {
        return $this->registerServiceCall(
            $event->getServiceLocator(),
            $event->getInstance(),
            $event->getCanonicalName(),
            $event->getRequestedName(),
            'get',
            $event->getTrace()
        );
    }

    /**
     * @param ServiceManagerEvent $event
     *
     * @return array|bool
     */
    public function logServiceManagerCreate(ServiceManagerEvent $event)
    {
        return $this->registerServiceCall(
            $event->getServiceLocator(),
            $event->getInstance(),
            $event->getCanonicalName(),
            $event->getRequestedName(),
            'create',
            $event->getTrace()
        );
    }

    /**
     * @private this method is public only for test purposes. Don't use it directly!
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param mixed                   $instance
     * @param string                  $canonicalName
     * @param string                  $requestedName
     * @param string                  $methodName
     * @param array                   $trace
     *
     * @return array|bool
     */
    public function registerServiceCall(
        ServiceLocatorInterface $serviceLocator,
        $instance,
        $canonicalName,
        $requestedName,
        $methodName,
        array $trace
    ) {
        if (!is_object($instance)) {
            return false;
        }

        return $this->tracedCalls[] = array(
            'instance'        => $instance,
            'service_locator' => $serviceLocator,
            'requested_name'  => $requestedName,
            'canonical_name'  => $canonicalName,
            'method'          => $methodName,
            'trace'           => $trace,
        );
    }

    /**
     * @param object $instance
     *
     * @return array[]
     */
    public function getDependingInstances($instance)
    {
        $relevantTracedCalls = array_filter(
            $this->tracedCalls,
            function ($tracedCallInfo) use ($instance) {
                return $tracedCallInfo['instance'] === $instance;
            }
        );

        $dependingServices = array();

        foreach ($relevantTracedCalls as $tracedCallInfo) {
            $parent = $this->getParentRequestingService($tracedCallInfo);

            if ($parent) {
                $dependingServices[] = $parent;
            }
        }

        return $dependingServices;
    }

    /**
     * @param object $instance
     *
     * @return array
     */
    public function getDependencyInstances($instance)
    {
        $calls = array();

        foreach ($this->tracedCalls as $call) {
            $oid = spl_object_hash($call['instance']);

            $calls[$oid] = $call;
        }

        $dependencies = array();

        foreach ($calls as $call) {
            $dependingServices = $this->getDependingInstances($call['instance']);

            foreach ($dependingServices as $depending) {
                if ($depending['instance'] === $instance) {
                    $dependencies[] = $call;
                }
            }
        }

        return $dependencies;
    }

    /**
     * Retrieves the canonical names of the services that need $service
     *
     * @param  string     $canonicalName
     * @return array|bool false if no discovery is possible
     */
    public function getDepending($canonicalName)
    {
        $relevantTracedCalls = array_filter(
            $this->tracedCalls,
            function ($tracedCallInfo) use ($canonicalName) {
                return $tracedCallInfo['canonical_name'] === $canonicalName;
            }
        );

        if (empty($relevantTracedCalls)) {
            return false;
        }

        $dependingServices = array();

        foreach ($relevantTracedCalls as $tracedCallInfo) {
            $parent = $this->getParentRequestingService($tracedCallInfo);

            if ($parent) {
                $dependingServices[] = $parent['canonical_name'];
            }
        }

        return $dependingServices;
    }

    /**
     * @param string $canonicalName
     *
     * @return array
     */
    public function getDependencies($canonicalName)
    {
        $services = array();

        foreach ($this->tracedCalls as $tracedCall) {
            $services[] = $tracedCall['canonical_name'];
        }

        $services = array_unique($services);

        $dependencies = array();

        foreach ($services as $serviceName) {
            $depending = $this->getDepending($serviceName);

            if ($depending && in_array($canonicalName, $depending)) {
                $dependencies[$serviceName] = true;
            }
        }

        return array_keys($dependencies);
    }

    /**
     * Analyzes a provided trace, looking for the first instantiation requesting $serviceName
     *
     * @param array $tracedCallInfo the element of {@see self::tracedCalls}
     *                              of which we're looking for a parent dependant service
     *
     * @return array|null null if a dependant service could not be found, the element
     *                    in {@see self::tracedCalls} otherwise
     */
    protected function getParentRequestingService($tracedCallInfo)
    {
        foreach ($tracedCallInfo['trace'] as $methodCall) {
            // not an invoking object: skip
            if (!isset($methodCall['object'])) {
                continue;
            }

            // service locator aware - maybe the dependency was pulled later on
            if ($methodCall['object'] instanceof ServiceLocatorAwareInterface
                || $methodCall['object'] instanceof ServiceManagerAwareInterface
            ) {
                foreach ($this->tracedCalls as $tracedCall) {
                    if ($tracedCall['instance'] === $methodCall['object']) {
                        return $tracedCall;
                    }
                }
            }

            if (!($methodCall['object'] instanceof ServiceLocatorInterface)
                || !in_array(strtolower($methodCall['function']), array('get', 'create'))
            ) {
                continue;
            }

            $name = $methodCall['args'][0];

            if (is_array($name)) {
                list($cName, $rName) = $name;
            } else {
                $rName = $name;
            }

            // if the name parameter is $serviceName, then we want to ignore this call, since it's the one
            // that instantiates $serviceName itself
            if ($rName === $tracedCallInfo['requested_name']) {
                continue;
            }

            foreach ($this->tracedCalls as $tracedCall) {
                if ($tracedCall['service_locator'] === $methodCall['object']
                    && $tracedCall['requested_name'] === $rName
                ) {
                    return $tracedCall;
                }
            }
        }

        return null;
    }

    /**
     * Retrieves an array with information about logged service instances,
     * with keys being the object hashes of instantiated services.
     * Each of the values contains following keys:
     *  * canonical_name: canonical name for the service
     *  * names: names used to retrieve the service
     *  * accesses: number of times the object was retrieved through a locator
     *  * hash: {@see spl_object_hash} of the object
     *  * service_instantiation_idx: index of this instance across all instances of the same service
     *  * dependencies: array of object hashes of dependencies
     *  * depending: array of object hashes of depending services
     *  * service_locator: {@see spl_object_hash} of the providing service locator
     *
     * @return array
     */
    public function getLoggedServices()
    {
        $loggedServices = array();
        $serviceNames   = array();

        foreach ($this->tracedCalls as $tracedCall) {
            $instance = $tracedCall['instance'];
            $oid      = spl_object_hash($instance);

            if (!isset($loggedServices[$oid])) {
                if (isset($serviceNames[$tracedCall['canonical_name']])) {
                    $serviceNames[$tracedCall['canonical_name']] += 1;
                } else {
                    $serviceNames[$tracedCall['canonical_name']] = 1;
                }

                $loggedServices[$oid] = array(
                    'canonical_name'            => $tracedCall['canonical_name'],
                    'names'                     => array(),
                    'accesses'                  => 0,
                    'hash'                      => $oid,
                    'service_instantiation_idx' => $serviceNames[$tracedCall['canonical_name']],
                    'service_locator'           => spl_object_hash($tracedCall['service_locator']),
                );

                $dependenciesCalls = $this->getDependencyInstances($instance);
                $dependingCalls    = $this->getDependingInstances($instance);

                $dependencies = array();

                foreach ($dependenciesCalls as $dependenciesCall) {
                    $dependencies[spl_object_hash($dependenciesCall['instance'])] = true;
                }

                $loggedServices[$oid]['dependencies'] = array_keys($dependencies);

                $depending = array();

                foreach ($dependingCalls as $dependingCall) {
                    $depending[spl_object_hash($dependingCall['instance'])] = true;
                }

                $loggedServices[$oid]['depending'] = array_keys($depending);
            }

            $loggedServices[$oid]['names'] = array_unique(
                array_merge(array($tracedCall['requested_name']), $loggedServices[$oid]['names'])
            );
            $loggedServices[$oid]['accesses'] += 1;
        }

        return $loggedServices;
    }

    /**
     * @return string[] service locator class names indexed by object hash
     */
    public function getLoggedServiceLocators()
    {
        $serviceLocators = array();

        foreach ($this->tracedCalls as $tracedCall) {
            $serviceLocator = $tracedCall['service_locator'];
            $serviceLocators[spl_object_hash($serviceLocator)] = $serviceLocator instanceof ProxyInterface
                ? get_parent_class($serviceLocator) : get_class($serviceLocator);
        }

        return $serviceLocators;
    }
}
