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
use Zend\Escaper\Escaper;

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
     * @var Escaper
     */
    protected $escaper;

    /**
     * @param Escaper $escaper
     */
    public function __construct(Escaper $escaper)
    {
        $this->escaper = $escaper;
    }

    /**
     * Retrieves a YUML diagram link
     *
     * @param array $services services as produced by
     *              {@see \OcraServiceManager\ServiceManager\LoggedServiceManager::getLoggedServices}
     */
    public function getUrl(array $services)
    {
        $url = 'http://yuml.me/diagram/scruffy/class/';

        $chunks = array();

        foreach ($services as $serviceName => $details) {
            foreach ($details['dependencies'] as $dependency) {
                $chunks[] = $this->escaper->escapeUrl('[' . $serviceName . ']->[' . $dependency . ']');
            }
        }

        return $url . implode(',', $chunks);
    }
}
