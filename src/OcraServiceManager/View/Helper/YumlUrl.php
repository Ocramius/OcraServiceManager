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

namespace OcraServiceManager\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * Helper used to generate YUML {@see http://http://yuml.me/} diagram links
 * from a dependency tree
 *
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class YumlUrl extends AbstractHelper
{
    /**
     * Retrieves a YUML diagram link
     *
     * @param array $services services as produced
     *                        by {@see \OcraServiceManager\ServiceManager\LoggedServiceManager::getLoggedServices}
     *
     * @return string
     */
    public function getUrl(array $services)
    {
        $chunks = array();

        foreach ($services as $serviceName => $details) {
            foreach ($details['dependencies'] as $dependency) {
                $chunks[] = '[' . $serviceName . ']->[' . $dependency . ']';
            }
        }

        return implode(',', $chunks);
    }
}
