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
use Zend\ServiceManager\ServiceManager;
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
     * {@inheritDoc}
     *
     * @param EventManagerInterface $eventManager
     * @param ServiceManager        $serviceManager
     */
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

        return $instance;
    }
}
