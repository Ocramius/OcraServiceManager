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
use OcraServiceManager\View\Helper\X11LikeColor;

/**
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class X11LikeColorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var X11LikeColor
     */
    protected $helper;

    /**
     * {@inheritDoc}
     *
     * @covers \OcraServiceManager\View\Helper\X11LikeColor
     */
    public function setUp()
    {
        parent::setUp();
        $this->helper = new X11LikeColor();
    }

    /**
     * @covers \OcraServiceManager\View\Helper\X11LikeColor::getRandomColor
     */
    public function testGetRandomColorGetsSameColorsWithSameSeed()
    {
        $color1 = $this->helper->getRandomColor(1);
        $color2 = $this->helper->getRandomColor(1);
        $color3 = $this->helper->getRandomColor(10);
        $color4 = $this->helper->getRandomColor(10);

        $this->assertInternalType('string', $color1);
        $this->assertInternalType('string', $color2);
        $this->assertInternalType('string', $color3);
        $this->assertInternalType('string', $color4);

        $this->assertNotSame($color1, $color3);
        $this->assertSame($color1, $color2);
        $this->assertSame($color3, $color4);
    }
}
