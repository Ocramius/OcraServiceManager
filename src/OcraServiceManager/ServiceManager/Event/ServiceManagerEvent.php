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

namespace OcraServiceManager\ServiceManager\Event;

use Zend\EventManager\Event;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Controller Plugin manager with additional logging capabilities.
 * Currently able to trace back dependencies by overloading `create` and `get`
 *
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 *
 * @todo support lazy services
 */
class ServiceManagerEvent extends Event
{
    const EVENT_SERVICEMANAGER_GET    = 'servicemanager.get';
    const EVENT_SERVICEMANAGER_CREATE = 'servicemanager.create';

    /**
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     */
    private $serviceLocator;

    /**
     * @var string
     */
    private $requestedName;

    /**
     * @var string
     */
    private $canonicalName;

    /**
     * @var array
     */
    private $trace;

    /**
     * @param string                  $eventName
     * @param ServiceLocatorInterface $serviceLocator
     * @param object                  $instance
     * @param string                  $requestedName
     * @param                         $canonicalName
     */
    public function __construct(
        $eventName,
        ServiceLocatorInterface $serviceLocator,
        $instance,
        $requestedName,
        $canonicalName
    ) {
        $this->serviceLocator = $serviceLocator;
        $this->requestedName  = (string) $requestedName;
        $this->canonicalName  = (string) $canonicalName;
        $this->trace          = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);

        parent::__construct(
            $eventName,
            $serviceLocator,
            array(
                'instance'       => $instance,
                'requested_name' => $requestedName,
                'canonical_name' => $canonicalName,
                'trace'          => $this->trace,
            )
        );
    }

    /**
     * @return string
     */
    public function getCanonicalName()
    {
        return $this->canonicalName;
    }

    /**
     * @return string
     */
    public function getRequestedName()
    {
        return $this->requestedName;
    }

    /**
     * @return \Zend\ServiceManager\ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * @return array
     */
    public function getTrace()
    {
        return $this->trace;
    }
}
