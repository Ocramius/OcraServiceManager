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

namespace OcraServiceManagerTest\View\Helper;

use PHPUnit_Framework_TestCase;
use OcraServiceManager\View\Helper\YumlUrl;

/**
 * Functional test to verify that the module initializes services correctly
 *
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class YumlUrlTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var YumlUrl
     */
    protected $helper;

    /**
     * {@inheritDoc}
     *
     * @covers \OcraServiceManager\View\Helper\YumlUrl
     */
    public function setUp()
    {
        parent::setUp();
        $this->helper = new YumlUrl();
    }

    /**
     * @covers \OcraServiceManager\View\Helper\YumlUrl
     */
    public function testGetUrl()
    {
        $this->assertSame(
            '[key]->[value]',
            $this->helper->getUrl(array(
                'key' => array(
                    'dependencies' => array(
                        'value'
                    ),
                ),
            ))
        );

        $this->assertSame(
            '[key]->[value],[key]->[value1],[key2]->[value2]',
            $this->helper->getUrl(array(
                'key'  => array(
                    'dependencies' => array(
                        'value',
                        'value1',
                    ),
                ),
                'key2' => array(
                    'dependencies' => array(
                        'value2',
                    ),
                ),
            ))
        );
    }
}
