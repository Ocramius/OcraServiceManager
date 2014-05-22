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

use OcraServiceManager\ServiceManager\Event\ServiceManagerEvent;
use Zend\EventManager\EventManagerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory responsible of instantiating an array of pre- and post-
 * method call interceptors to be introduced in an access
 * interceptor service manager proxy.
 *
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class ServiceManagerAccessInterceptorsFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return \Closure[]
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /* @var $eventManager \Zend\EventManager\EventManagerInterface */
        $eventManager     = $serviceLocator->get('OcraServiceManager\\ServiceManager\\EventManager');
        $creationListener = $this->createInstantiationListener($eventManager);

        return array(
            'create' => $creationListener,
            'get'    => $creationListener,
        );
    }

    /**
     * Bulds a listener closure responsible for triggering events on service instantiation
     *
     * @param EventManagerInterface $eventManager
     *
     * @return \Closure
     */
    private function createInstantiationListener(EventManagerInterface $eventManager)
    {
        return function ($proxy, $instance, $method, $params, $returnValue) use ($eventManager) {
            $eventName = ('create' === $method)
                ? ServiceManagerEvent::EVENT_SERVICEMANAGER_CREATE
                : ServiceManagerEvent::EVENT_SERVICEMANAGER_GET;

            $serviceName = $params['name'];

            if (is_array($serviceName)) {
                list($canonicalName, $serviceName) = $serviceName;
            } else {
                $canonicalName = strtolower(
                    strtr($serviceName, array('-' => '', '_' => '', ' ' => '', '\\' => '', '/' => ''))
                );
            }

            $eventManager->trigger(new ServiceManagerEvent(
                $eventName,
                $proxy,
                $returnValue,
                $serviceName,
                $canonicalName
            ));
        };
    }
}
