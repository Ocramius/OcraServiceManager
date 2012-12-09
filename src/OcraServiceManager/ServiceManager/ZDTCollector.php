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

use ZendDeveloperTools\Collector\CollectorInterface;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;
use Serializable;

/**
 * Zend Developer Tools collector based on a LoggedServiceManager
 *
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class ZDTCollector implements CollectorInterface, Serializable
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * @var array of collected services with their dependencies
     */
    protected $collectedServices = array();

    /**
     * Defines if the collector was able to collect data
     *
     * @var bool
     */
    protected $couldCollect = false;

    /**
     * Collector priority
     */
    const PRIORITY = 15;

    /**
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'ocra_service_manager';
    }

    /**
     * {@inheritDoc}
     */
    public function getPriority()
    {
        return static::PRIORITY;
    }

    /**
     * {@inheritDoc}
     *
     * @todo complete with dependencies
     */
    public function collect(MvcEvent $mvcEvent)
    {
        if (!$this->serviceLocator instanceof LoggedServiceManager) {
            return;
        }

        /* @var $locator LoggedServiceManager */
        $locator                 = $this->serviceLocator;
        $this->collectedServices = $locator->getLoggedServices();
        $this->couldCollect      = true;
    }

    /**
     * {@inheritDoc}
     */
    public function serialize()
    {
        return serialize(array(
            'collectedServices' => $this->collectedServices,
            'couldCollect'      => $this->couldCollect,
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize($serialized)
    {
        $data                    = unserialize($serialized);
        $this->collectedServices = $data['collectedServices'];
        $this->couldCollect      = $data['couldCollect'];
    }

    /**
     * Retrieves details of collected services as of {@see LoggedServiceManager::getLoggedServices}
     *
     * @return array
     */
    public function getServices()
    {
        return $this->collectedServices;
    }

    /**
     * Retrieves whether the collector was able to retrieve information from the provided service locator
     *
     * @return bool
     */
    public function couldCollect()
    {
        return $this->couldCollect;
    }
}
