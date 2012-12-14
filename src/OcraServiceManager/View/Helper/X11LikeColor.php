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
 * Helper class used to get a random X11-compatible color for Yuml
 *
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class X11LikeColor extends AbstractHelper
{
    /**
     * @var array[]
     */
    protected $colors = array(
        // custom colors
        'white'                => array(255, 255, 255),
        'gold'                 => array(255, 215, 0),
        'red'                  => array(255, 0, 0),
        'blue'                 => array(0, 0, 255),
        'green'                => array(0, 255, 0),
        'pink'                 => array(255, 192, 203),
        'grey'                 => array(190, 190, 190),
        'cyan'                 => array(0, 255, 255),
        'yellow'               => array(255, 255, 0),
        'purple'               => array(160, 32, 240),
        'brown'                => array(165, 42, 42),
        'orange'               => array(255, 165, 0),
        'coral'                => array(255, 127, 80),
        'plum'                 => array(221, 160, 221),
        'snow'                 => array(255, 250, 250),
        'magenta'              => array(255, 0, 255),
        'violet'               => array(238, 130, 238),
    );

    /**
     * @param int|null $seed
     *
     * @return string
     */
    public function getRandomColor($seed = null)
    {
        $seed = (int) $seed;
        $keys = array_keys($this->colors);

        return $keys[$seed];
    }
}
