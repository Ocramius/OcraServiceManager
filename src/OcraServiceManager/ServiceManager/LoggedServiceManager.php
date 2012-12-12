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

use OcraServiceManager\ServiceManager as BaseServiceManager;
use Zend\Stdlib\ArrayUtils;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;

/**
 * ServiceManager with additional logging capabilities.
 * Currently able to trace back dependencies by overloading `create` and `get`
 *
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 *
 * @todo support {@see \Zend\ServiceManager\AbstractPluginManager}
 * @todo support non-object services
 * @todo extend  {@see \OcraServiceManager\ServiceManager\ServiceManager} instead
 *
 * @todo support \Zend\Mvc\Controller\PluginManager
 * @todo support \Zend\Mvc\Controller\ControllerManager
 * @todo support \Zend\View\HelperPluginManager
 * @todo support all other objects implementing \Zend\ServiceManager\ServiceLocatorInterface
 */
class LoggedServiceManager extends BaseServiceManager
{
    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * Map of instance object hashes and their associated service name
     *
     * @var string[]
     *
     * @todo strip this? We can iterate on existing services. Un-performant
     *       but less chaotic...
     */
    protected $instanceHashes = array();

    /**
     * Array containing object hashes of the instantiated services.
     * Keys are the service names, values are arrays indexed by object ID,
     * containing the requested service name
     *
     * @var array[]
     */
    protected $serviceHashes = array();

    /**
     * Traces of instantiated objects, indexed by object hash
     *
     * @var array[] created services traces indexed by created object hash
     */
    protected $createTraces = array();

    /**
     * Traces of retrieved objects, indexed by object hash
     *
     * @var array[]
     */
    protected $getTraces = array();

    public function __construct(EventManagerInterface $eventManager, ServiceManager $serviceManager)
    {
        $this->eventManager = $eventManager;

        parent::__construct($serviceManager);
    }

    /**
     * {@inheritDoc}
     */
    public function create($name)
    {
        if (is_array($name)) {
            list($cName, $rName) = $name;
        } else {
            $cName = $this->canonicalizeName($name);
            $rName = $name;
        }

        $instance = parent::create($name);

        $this->eventManager->trigger(
            Logger::SERVICE_MANAGER_CREATE,
            $this,
            array(
                'instance'       => $instance,
                'canonical_name' => $cName,
                'requested_name' => $rName,
            )
        );

        if (is_object($instance)) {
            $oid = spl_object_hash($instance);

            if (!isset($this->createTraces[$oid])) {
                $this->createTraces[$oid] = array();
            }

            $this->createTraces[$oid][] = debug_backtrace();
            $this->registerServiceCall($instance, $cName, $rName);
        }

        return $instance;
    }

    /**
     * {@inheritDoc}
     */
    public function get($name, $usePeeringServiceManagers = true)
    {
        $cName   = $this->canonicalizeName($name);

        while ($this->hasAlias($cName)) {
            $cName = $this->aliases[$cName];
        }

        $instance = parent::get($name, $usePeeringServiceManagers);

        $this->eventManager->trigger(
            Logger::SERVICE_LOCATOR_GET,
            $this,
            array(
                'instance'       => $instance,
                'canonical_name' => $cName,
                'requested_name' => $name,
            )
        );

        if (is_object($instance)) {
            $oid = spl_object_hash($instance);

            if (!isset($this->getTraces[$oid])) {
                $this->createTraces[$oid] = array();
            }

            $this->getTraces[$oid][] = debug_backtrace();
            $this->registerServiceCall($instance, $cName, $name);
        }

        return $instance;
    }

    /**
     * Registers a retrieved service
     *
     * @param object $instance
     * @param string $serviceName
     * @param string $requestedName
     *
     * @return boolean true if a service was registered
     */
    protected function registerServiceCall($instance, $serviceName, $requestedName)
    {
        $registered    = false;

        if (!isset($this->serviceHashes[$serviceName])) {
            $this->serviceHashes[$serviceName] = array();
        }

        if (is_object($instance)) {
            $oid = spl_object_hash($instance);
            $registered = ! array_key_exists($oid, $this->serviceHashes[$serviceName]);
            $this->serviceHashes[$serviceName][$oid] = $requestedName;
            $this->instanceHashes[$oid] = $serviceName;
        }

        return $registered;
    }

    /**
     * Retrieve the canonical names of the dependencies of $service
     *
     * @param  string        $service
     * @return string[]|bool false if dependencies cannot be resolved
     */
    public function getDependencies($service)
    {
        $cName = $this->canonicalizeName($service);

        // if no service was discovered, try fetching it to trigger logging
        if (!isset($this->serviceHashes[$cName])) {
            $this->get($service);
        }

        // if no hash is available, then the service probably came from a peering service manager
        if (!isset($this->serviceHashes[$cName])) {
            return false;
        }

        $dependencies = array();

        foreach ($this->serviceHashes as $serviceName => $instantiations) {
            $depending = $this->getDepending($serviceName);

            if (in_array($cName, $depending)) {
                $dependencies[$serviceName] = true;
            }
        }

        return array_keys($dependencies);
    }

    /**
     * Retrieves the canonical names of the services that need $service
     *
     * @param  string     $service
     * @return array|bool false if no discovery is possible
     */
    public function getDepending($service)
    {
        $cName = $this->canonicalizeName($service);

        // if no service was discovered, try fetching it to trigger logging
        if (!isset($this->serviceHashes[$cName])) {
            $this->get($service);
        }

        // if no hash is available, then the service probably came from a peering service manager
        if (!isset($this->serviceHashes[$cName])) {
            return false;
        }

        $dependingServices = array();

        foreach ($this->serviceHashes[$cName] as $oid => $rName) {
            foreach (ArrayUtils::merge($this->getTraces[$oid], $this->createTraces[$oid]) as $trace) {
                if ($parent = $this->getParentRequestingService($trace, $cName)) {
                    $dependingServices[] = $parent;
                }
            }
        }

        return $dependingServices;
    }

    /**
     * Analyzes a provided trace, looking for the first instantiation requesting $serviceName
     *
     * @param array  $trace       a debug_backtrace produced call definition
     * @param string $serviceName name of the service for which we're looking for the parent requester
     */
    protected function getParentRequestingService($trace, $serviceName)
    {
        foreach ($trace as $methodCall) {
            // not an invoking object: skip
            if (!isset($methodCall['object'])) {
                continue;
            }

            // service locator aware - maybe the dependency was pulled later on
            if (
                $methodCall['object'] instanceof ServiceLocatorAwareInterface
                || $methodCall['object'] instanceof ServiceManagerAwareInterface
            ) {
                $oid = spl_object_hash($methodCall['object']);

                if (isset($this->instanceHashes[$oid])) {
                    return $this->instanceHashes[$oid];
                }
            }

            if (
                !($methodCall['object'] === $this)
                || !(strtolower($methodCall['function']) === 'get' || strtolower($methodCall['function']) === 'create')
            ) {
                continue;
            }

            $name = $methodCall['args'][0];

            if (is_array($name)) {
                list($cName, $unused) = $name;
            } else {
                $cName = $this->canonicalizeName($name);
            }

            // if the name parameter is $serviceName, then we want to ignore this call, since it's the one
            // that instantiates $serviceName itself
            if ($cName === $serviceName) {
                continue;
            }

            return $cName;
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

        foreach (array_keys($this->serviceHashes) as $cName) {
            $oids = array_keys($this->serviceHashes[$cName]);
            $accesses = 0;

            foreach ($oids as $oid) {
                if (isset($this->getTraces[$oid])) {
                    $accesses += count($this->getTraces[$oid]);
                }
            }

            $loggedServices[$cName] = array(
                'names'        => array_unique($this->serviceHashes[$cName]),
                'dependencies' => $this->getDependencies($cName),
                'depending'    => $this->getDepending($cName),
                'accesses'     => $accesses,
                'instances'    => count($oids),
            );
        }

        return $loggedServices;
    }
}
