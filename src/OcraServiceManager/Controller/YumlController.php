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

namespace OcraServiceManager\Controller;

use BadMethodCallException;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Http\Client;
use Zend\Http\Request;

/**
 * OcraServiceManager module
 *
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class YumlController extends AbstractActionController
{
    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * Redirects the user to a YUML graph drawn with the provided `dsl_text`
     *
     * @return \Zend\Http\Response
     * @throws \UnexpectedValueException if the YUML service answered incorrectly
     * @throws BadMethodCallException    if the request has been aborted for security reasons
     */
    public function indexAction()
    {
        $config = $this->getServiceLocator()->get('Config');

        if (! $config['ocra_service_manager']['logged_service_manager']
            || ! isset($config['zenddevelopertools']['toolbar']['enabled'])
            || ! $config['zenddevelopertools']['toolbar']['enabled']
        ) {
            throw new BadMethodCallException('Toolbar not enabled, aborting');
        }

        /* @var $request \Zend\Http\Request */
        $request = $this->getRequest();
        $client  = $this->getHttpClient();

        $client->setMethod(Request::METHOD_POST);
        $client->setParameterPost(array(
            'dsl_text' => $request->getPost('dsl_text'),
        ));
        $response = $client->send();

        if (!$response->isSuccess()) {
            throw new \UnexpectedValueException('HTTP Request failed');
        }

        /* @var $redirect \Zend\Mvc\Controller\Plugin\Redirect */
        $redirect = $this->plugin('redirect');

        return $redirect->toUrl('http://yuml.me/' . $response->getBody());
    }

    /**
     * @param Client $client
     */
    public function setHttpClient(Client $client)
    {
        $this->httpClient = $client;
    }

    /**
     * @return Client
     */
    public function getHttpClient()
    {
        if (!$this->httpClient) {
            $this->httpClient = new Client('http://yuml.me/diagram/plain/class/', array('timeout' => 30));
        }

        return $this->httpClient;
    }
}
