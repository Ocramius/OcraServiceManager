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
     * @var Logger
     */
    protected $logger;

    /**
     * @var array of collected services with their dependencies
     */
    protected $collectedServices = array();

    /**
     * @var array of collected service locators with their dependencies
     */
    protected $collectedServiceLocators = array();

    /**
     * Collector priority
     */
    const PRIORITY = 15;

    /**
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
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
        $this->collectedServices        = $this->logger->getLoggedServices();
        $this->collectedServiceLocators = $this->logger->getLoggedServiceLocators();
    }

    /**
     * {@inheritDoc}
     */
    public function serialize()
    {
        return serialize(array(
            'collectedServices' => $this->collectedServices,
            'collectedServiceLocators' => $this->collectedServiceLocators,
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize($serialized)
    {
        $data                           = unserialize($serialized);
        $this->collectedServices        = $data['collectedServices'];
        $this->collectedServiceLocators = $data['collectedServiceLocators'];
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
     * Retrieves details of collected service locators as of {@see LoggedServiceManager::getLoggedServiceLocators}
     *
     * @return array
     */
    public function getServiceLocators()
    {
        return $this->collectedServiceLocators;
    }
}
