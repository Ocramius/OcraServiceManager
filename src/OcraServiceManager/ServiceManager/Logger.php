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

use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventInterface;
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
    const SERVICE_LOCATOR_GET    = 'get';
    const SERVICE_MANAGER_CREATE = 'create';

    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $handlers = array();

    /**
     * Traces of instantiated objects, indexed by object hash
     *
     * @var array[] created services traces indexed by created object hash
     */
    protected $tracedCalls = array();

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events)
    {
        $this->handlers[] = $events->attach(
            static::SERVICE_LOCATOR_GET,
            array($this, 'logServiceLocatorGet')
        );
        $this->handlers[] = $events->attach(
            static::SERVICE_MANAGER_CREATE,
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

    public function logServiceLocatorGet(EventInterface $event)
    {
        return $this->registerServiceCall(
            $event->getTarget(),
            $event->getParam('instance'),
            $event->getParam('canonical_name'),
            $event->getParam('requested_name'),
            'get',
            $event->getParam('trace')
        );
    }

    public function logServiceManagerCreate(EventInterface $event)
    {
        return $this->registerServiceCall(
            $event->getTarget(),
            $event->getParam('instance'),
            $event->getParam('canonical_name'),
            $event->getParam('requested_name'),
            'create',
            $event->getParam('trace')
        );
    }

    public function registerServiceCall(
        ServiceLocatorInterface $serviceLocator,
        $instance,
        $canonicalName,
        $requestedName,
        $methodName,
        array $trace = null
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
            'trace'           => $trace ?: debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT),
        );
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
     * @param array  $trace       a debug_backtrace produced call definition
     * @param string $serviceName name of the service for which we're looking for the parent requester
     */
    protected function getParentRequestingService($tracedCallInfo)
    {
        foreach ($tracedCallInfo['trace'] as $methodCall) {
            // not an invoking object: skip
            if (!isset($methodCall['object'])) {
                continue;
            }

            // service locator aware - maybe the dependency was pulled later on
            if (
                $methodCall['object'] instanceof ServiceLocatorAwareInterface
                || $methodCall['object'] instanceof ServiceManagerAwareInterface
            ) {
                foreach ($this->tracedCalls as $tracedCall) {
                    if ($tracedCall['instance'] === $methodCall['object']) {
                        return $tracedCall;
                    }
                }
            }

            if (
                !($methodCall['object'] instanceof ServiceLocatorInterface)
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
                if (
                    $tracedCall['service_locator'] === $methodCall['object']
                    && $tracedCall['requested_name'] === $rName
                ) {
                    return $tracedCall;
                }
            }
        }

        return false;
    }

    /**
     * Retrieves an array with information about logged service instances,
     * with keys being the canonical names of the services.
     *
     * @return array
     */
    public function getLoggedServices()
    {
        $loggedServices = array();

        foreach ($this->tracedCalls as $tracedCall) {
            $cName = $tracedCall['canonical_name'];

            if (!isset($loggedServices[$cName])) {
                $loggedServices[$cName] = array(
                    'names'     => array(),
                    'accesses'  => 0,
                    'instances' => array(),
                );
            }

            $loggedServices[$cName]['names'] = array_unique(
                array_merge(array($tracedCall['requested_name']), $loggedServices[$cName]['names'])
            );
            $loggedServices[$cName]['instances'][spl_object_hash($tracedCall['instance'])] = $tracedCall['instance'];
            $loggedServices[$cName]['accesses'] += 1;
        }

        foreach ($loggedServices as $cName => $details) {
            $loggedServices[$cName]['dependencies'] = $this->getDependencies($cName);
            $loggedServices[$cName]['depending']    = $this->getDepending($cName);
            $loggedServices[$cName]['instances']    = count($loggedServices[$cName]['instances']);
        }

        return $loggedServices;
    }
}
