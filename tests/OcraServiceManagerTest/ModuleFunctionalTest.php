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

namespace OcraServiceManagerTest;

use PHPUnit_Framework_TestCase;
use Zend\ServiceManager\ServiceManager as BaseServiceManager;
use Zend\Mvc\Service\ServiceManagerConfig;

/**
 * Functional test to verify that the module initializes services correctly
 *
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 *
 * @coversNothing
 */
class ModuleFunctionalTest extends PHPUnit_Framework_TestCase
{
    /**
     * {@inheritDoc}
     */
    public function testDefinedServices()
    {
        $serviceManager = new BaseServiceManager(new ServiceManagerConfig());
        $serviceManager->setService(
            'ApplicationConfig',
            array(
                'modules'                 => array('OcraServiceManager'),
                'module_listener_options' => array(
                    'config_glob_paths' => array(),
                ),
            )
        );

        /* @var $moduleManager \Zend\ModuleManager\ModuleManager */
        $moduleManager = $serviceManager->get('ModuleManager');
        $moduleManager->loadModules();

        /* @var $application \Zend\Mvc\Application */
        $application        = $serviceManager->get('Application');
        /* @var $ocraServiceManager \Zend\ServiceManager\ServiceManager */
        $ocraServiceManager = $serviceManager->get('ServiceManager');

        $this->assertInstanceOf(
            'Zend\\ServiceManager\\ServiceManager',
            $ocraServiceManager,
            'ServiceManager is a valid service manager'
        );
        $this->assertInstanceOf(
            'ProxyManager\\Proxy\\AccessInterceptorInterface',
            $ocraServiceManager,
            'ServiceManager is an access interceptor proxy'
        );

        $this->assertSame(
            $application->getServiceManager(),
            $ocraServiceManager,
            'The application service manager was replaced'
        );
    }
}
