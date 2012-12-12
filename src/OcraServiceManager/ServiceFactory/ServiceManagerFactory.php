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

use OcraServiceManager\ServiceManager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use OcraServiceManager\ServiceManager\LoggedServiceManager;

/**
 * Factory responsible of building an {@see OcraServiceManager\ServiceManager}
 *
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class ServiceManagerFactory implements FactoryInterface
{
    /**
     * Create an overloaded service manager
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return ServiceManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {

        $config = $serviceLocator->get('Config');

        if ($config['ocra_service_manager']['logged_service_manager']) {
            /* @var $eventManager \Zend\EventManager\EventManagerInterface */
            $eventManager = $serviceLocator->get('OcraServiceManager\\ServiceManager\\EventManager');
            /* @var $serviceLocator \Zend\ServiceManager\ServiceManager*/
            $serviceManager = new LoggedServiceManager($eventManager, $serviceLocator);
        } else {
            /* @var $serviceLocator \Zend\ServiceManager\ServiceManager*/
            $serviceManager = new ServiceManager($serviceLocator);
        }

        foreach ($config['service_manager']['lazy_services'] as $lazyService => $factory) {
            if (is_int($lazyService)) {
                $serviceManager->setProxyService($factory);
            } else {
                $serviceManager->setProxyService($lazyService, $factory);
            }
        }

        return $serviceManager;
    }
}
