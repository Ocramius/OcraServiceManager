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

use Zend\View\HelperPluginManager;
use Zend\EventManager\EventManagerInterface;

/**
 * Controller Plugin manager with additional logging capabilities.
 * Currently able to trace back dependencies by overloading `create` and `get`
 *
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 *
 * @todo support lazy services
 */
class LoggedViewHelperPluginManager extends HelperPluginManager
{
    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * {@inheritDoc}
     *
     * @param EventManagerInterface $eventManager
     * @param HelperPluginManager   $pluginManager
     */
    public function __construct(EventManagerInterface $eventManager, HelperPluginManager $pluginManager)
    {
        $this->eventManager = $eventManager;

        // localizing scope since we're proxying the base service manager
        $this->canonicalNames                   = & $pluginManager->canonicalNames;
        $this->allowOverride                    = & $pluginManager->allowOverride;
        $this->invokableClasses                 = & $pluginManager->invokableClasses;
        $this->factories                        = & $pluginManager->factories;
        $this->abstractFactories                = & $pluginManager->abstractFactories;
        $this->pendingAbstractFactoryRequests   = & $pluginManager->pendingAbstractFactoryRequests;
        $this->shared                           = & $pluginManager->shared;
        $this->instances                        = & $pluginManager->instances;
        $this->aliases                          = & $pluginManager->aliases;
        $this->initializers                     = & $pluginManager->initializers;
        $this->peeringServiceManagers           = & $pluginManager->peeringServiceManagers;
        $this->shareByDefault                   = & $pluginManager->shareByDefault;
        $this->retrieveFromPeeringManagerFirst  = & $pluginManager->retrieveFromPeeringManagerFirst;
        $this->throwExceptionInCreate           = & $pluginManager->throwExceptionInCreate;
        $this->canonicalNamesReplacements       = & $pluginManager->canonicalNamesReplacements;
        $this->autoAddInvokableClass            = & $pluginManager->autoAddInvokableClass;
        $this->creationOptions                  = & $pluginManager->creationOptions;
        $this->serviceLocator                   = & $pluginManager->serviceLocator;
        $this->renderer                         = & $pluginManager->renderer;
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
