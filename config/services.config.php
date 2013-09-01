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
    'invokables' => array(
        'OcraServiceManager\\ServiceManager\\Logger'            => 'OcraServiceManager\\ServiceManager\\Logger',
    ),
    'factories'  => array(
        'Application'
            => 'OcraServiceManager\\ServiceFactory\\ApplicationFactory',
        'ControllerPluginManager'
            => 'OcraServiceManager\\ServiceFactory\\ControllerPluginManagerFactory',
        'ControllerLoader'
            => 'OcraServiceManager\\ServiceFactory\\ControllerManagerFactory',
        'ViewHelperManager'
            => 'OcraServiceManager\\ServiceFactory\\ViewHelperPluginManagerFactory',
        'OcraServiceManager\\ServiceManager\\EventManager'
            => 'OcraServiceManager\\ServiceFactory\\EventManagerFactory',
        'OcraServiceManager\\View\\Helper\\YumlUrl'             => 'OcraServiceManager\\ServiceFactory\\YumlUrlFactory',
        'OcraServiceManager\\ServiceManager'
            => 'OcraServiceManager\\ServiceFactory\\ServiceManagerFactory',
        'OcraServiceManager\\Cache\\ServiceProxyCache'          => 'OcraServiceManager\\ServiceFactory\\CacheFactory',
        'OcraServiceManager\Proxy\ServiceProxyAbstractFactory'
            => 'OcraServiceManager\\ServiceFactory\\ServiceProxyAbstractFactoryFactory',
        'OcraServiceManager\\ServiceManager\\ZDTCollector'
            => 'OcraServiceManager\\ServiceFactory\\ZDTCollectorFactory',
        'OcraServiceManager\\ServiceManager\\AccessInterceptors'
            => 'OcraServiceManager\\ServiceFactory\\ServiceManagerAccessInterceptorsFactory',
        'OcraServiceManager\\ServiceManager\\AccessInterceptorProxyFactory'
            => 'OcraServiceManager\\ServiceFactory\\AccessInterceptorProxyFactoryFactory',
    ),
);
