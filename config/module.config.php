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

return array(
    'ocra_service_manager' => array(
        'logged_service_manager' => true,
    ),

    'service_manager' => require __DIR__ . '/services.config.php',

    // zenddevelopertools specific settings
    'view_manager' => array(
        'template_map' => array(
            'zend-developer-tools/toolbar/ocra-service-manager' =>
            __DIR__ . '/../view/zend-developer-tools/toolbar/ocra-service-manager.phtml',
        ),
    ),

    'controllers' => array(
        'invokables' => array(
            'OcraServiceManager\\Controller\\YumlController' => 'OcraServiceManager\\Controller\\YumlController',
        ),
    ),

    'view_helpers' => array(
        'invokables' => array(
            'x11LikeColor' => 'OcraServiceManager\\View\\Helper\\X11LikeColor',
        ),
        'factories'  => array(
            'yumlUrl'      => 'OcraServiceManager\\ServiceFactory\\YumlUrlFactory',
        ),
    ),

    'router' => array(
        'routes' => array(
            'ocra_service_manager_yuml' => array(
                'type' => 'Zend\\Mvc\\Router\\Http\\Literal',
                'options' => array(
                    'route'    => '/ocra_service_manager_yuml',
                    'defaults' => array(
                        'controller' => 'OcraServiceManager\\Controller\\YumlController',
                        'action'     => 'index',
                    ),
                ),
            ),
        ),
    ),

    'zenddevelopertools' => array(
        'profiler' => array(
            'collectors' => array(
                'ocra_service_manager' => 'OcraServiceManager\\ServiceManager\\ZDTCollector',
            ),
        ),
        'toolbar' => array(
            'entries' => array(
                'ocra_service_manager' => 'zend-developer-tools/toolbar/ocra-service-manager',
            ),
        ),
    ),
);
