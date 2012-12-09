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

use OcraServiceManager\Proxy\ServiceProxyGenerator;

return array(
    'service_manager' => array(
        'lazy_services' => array(),
    ),
    'ocra_service_manager' => array(
        'logged_service_manager'    => false,
        'service_proxies_namespace' => ServiceProxyGenerator::DEFAULT_SERVICE_PROXY_NS,
        'service_proxies_dir'       => getcwd() . '/data/service-proxies',
        'service_proxies_cache'     => 'OcraServiceManager\\Cache\\ServiceProxyCache',
        'cache'                     => array(
            'adapter' => 'memory',
        ),
    ),
    'view_manager' => array(
        'template_map' => array(
            'zend-developer-tools/toolbar/ocra-service-manager' =>
            __DIR__ . '/../view/zend-developer-tools/toolbar/ocra-service-manager.phtml',
        ),
    ),
    'zenddevelopertools' => array(
        'profiler' => array(
            'collectors' => array(
                //'ocra_service_manager' => 'OcraServiceManager\\ServiceManager\\ZDTCollector',
                'ocra_service_manager' => 'OcraServiceManager\ServiceManager\ZDTCollector',
            ),
        ),
        'toolbar' => array(
            'entries' => array(
                'ocra_service_manager' => 'zend-developer-tools/toolbar/ocra-service-manager',
            ),
        ),
    ),
);
