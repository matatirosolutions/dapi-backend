<?php


namespace App\Service;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class FileMakerAPI
{

    /** @var SessionInterface */
    protected $session;

    /** @var array */
    protected $config;

    /** @var string */
    protected $baseURI;

    /** @var boolean */
    protected $retried = false;

    /**
     * FileMakerAPI constructor.
     * @param SessionInterface $session
     * @param array $fmConfig
     */
    public function __construct(SessionInterface $session, array $fmConfig)
    {
        $this->session = $session;
        $this->config = $fmConfig;

        // Set the Base URI based on the config values
        $this->setBaseURL($fmConfig['server'], $fmConfig['database']);
    }

    /**
     * @param $username
     * @param $password
     *
     * @throws Exception
     */
    public function logUserIn($username, $password)
    {
        // If someone's trying to login then we need to pass their username and
        // password through to the 'fetchToken' method which expects these to come in
        // 'username' and 'password' in the array of params passed in
        $params = [
            'username' => $username,
            'password' => $password
        ];

        $this->fetchToken($params);
    }

    /**
     * @param array $params
     *
     * @throws Exception
     */
    private function fetchToken(array $params)
    {
        $client = new Client();
        try {
            // Use a Guzzle request to access the FileMaker Data API setting the necessary
            // headers and credentials
            $response = $client->request('POST', $this->baseURI . 'sessions', [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'auth' => [
                    $params['username'], $params['password']
                ]
            ]);

            // We get JSON back from the FileMaker Data API. The Guzzle response object stores
            // that in 'contents' which can be accessed with $response->getBody()->getContents()
            // we then need to json_decode that so that we can access the object in PHP
            $content = json_decode($response->getBody()->getContents());

            // The token is in response.token. We need to save that for future use. You could write it
            // to a file, but because we want this to be user-specific one good place to store it is
            // in the user's session
            $this->saveToken($content->response->token);
        } catch (Exception $exception) {
            // We need to catch any exceptions which occur and try and provide more useful feedback
            // The Data API uses standard HTTP responses (see https://httpstatuses.com/)

            /** @var ClientException $exception */
            if (404 == $exception->getResponse()->getStatusCode()) {
                // 404 is 'not found' and that's what gets returned when the user credentials are wrong
                throw new Exception($exception->getResponse()->getReasonPhrase(), 404);
            }

            // FileMaker is also really good at giving other error information if things go wrong. That's also
            // in the body contents which we can get from the exception using
            // $exception->getResponse()->getBody()->getContents()
            $content = json_decode($exception->getResponse()->getBody()->getContents());

            // Extract the message and throw an exception which we can use in the calling method
            // to access information about what went wrong.
            throw new Exception($content->messages[0]->message, $content->messages[0]->code);
        } catch (GuzzleException $exception) {
            throw new Exception('Unknown error', -1);
        }
    }

    /**
     * @param string $method    HTTP method to use for the request (e.g. GET, POST, PATCH etc)
     * @param string $uri       The endpoint to call - everything after the name of the database
     * @param array $options    Any data to send to the API with this call
     *
     * @return array
     *
     * @throws Exception
     */
    public function performRequest(string $method, string $uri, array $options)
    {
        // Set the standard headers which are needed for every call. We need to set
        // the Content-Type and the Authorization header. These are merged into the
        // options passed in which allows them to be overridden by any requests which
        // require different headers (e.g. container uploads)
        $headers = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => sprintf('Bearer %s', $this->retrieveToken()),
            ]
        ];

        // Create a Guzzle client
        $client = new Client();
        try {

            // Actually make the call with Guzzle. We pass through the method, we add the baseURI to the
            // requested uri, then merge the default headers with the other cURL options have have been
            // passed in to the request
            $response = $client->request($method, $this->baseURI . $uri, array_merge($headers, $options));

            // The JSON response from the Data API is in the body of the response which we can access
            // using $response->getBody()->getContents()
            $content = json_decode($response->getBody()->getContents(), true);

            // Depending on exactly which endpoint we're calling we either want the data, or the whole response
            // if there's a data set, send that, otherwise the whole response array.
            return isset($content['response']['data']) ? $content['response']['data'] : $content['response'];
        } catch (Exception $e) {
            /** @var ClientException $e */
            $content = json_decode($e->getResponse()->getBody()->getContents());
            if (401 == $content->messages[0]->code) {
                // no records found
                return [];
            }

            // if the token has expired or is invalid then in theory 952 will come back
            // but sometimes you get 105 missing layout (go figure), so try a token refresh
            if (in_array($content->messages[0]->code, [105, 952]) && !$this->retried) {
                $this->retried = true;
                $this->clearToken();
                return $this->performRequest($method, $uri, $options);
            }
            throw new Exception($content->messages[0]->message, $content->messages[0]->code);
        } catch (GuzzleException $e) {
            throw new Exception('Unknown error', -1);
        }
    }

    /**
     * @param string $layout    The layout to upload content to
     * @param int $recId        The FileMaker internal recordId of the record to add the content to
     * @param string $field     The name of the container field
     * @param string $file      The path to the file to be inserted into the container
     * @param int $repetition   Which field repetition to use, defaults to 1
     *
     * @return array
     *
     * @throws Exception
     */
    public function performContainerInsert($layout, $recId, $field, $file, $repetition = 1)
    {
        // Generate the URI required for this request
        $uri = sprintf('layouts/%s/records/%s/containers/%s/%s', $layout, $recId, $field, $repetition);

        // Se the options specific to uploading a file. This means that we do NOT set the Content-Type
        // header, but we do still need the Authorization header.
        // We also have to set a form multipart, which has the name 'upload' as specified in the Data API
        // documentation, with a 'contents' node containing the actual file data which we read off disk
        $options = [
            'headers' => [
                'Authorization' => sprintf('Bearer %s', $this->retrieveToken()),
            ],
            'multipart' => [
                [
                    'name' => 'upload',
                    'contents' => fopen($file, 'r')
                ],
            ]
        ];

        return $this->performRequest('POST', $uri, $options);
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    private function retrieveToken()
    {
        $token = $this->session->get('fmToken');

        if ($token) {
            return $token;
        }

        $this->fetchToken($this->config);
        return $this->session->get('fmToken');
    }

    /**
     * @param string $token
     */
    private function saveToken($token)
    {
        $this->session->set('fmToken', $token);
    }

    /**
     *
     */
    private function clearToken()
    {
        $this->session->set('fmToken', false);
    }

    /**
     * @param string $host
     * @param string $database
     */
    private function setBaseURL(string $host, string $database)
    {
        // Do some sanity checking on the config variables to make sure that we're generating the right URL
        // - if it doesn't have the https at the start, add that on
        // - if it doesn't have a trailing slash, add the on
        // then compile all of that with the bits of the URL which are always that same. We do that here
        // to save having to replicate it all over the place, and because it means when v2 of the Data API
        // is released we only have to update one place!
        $this->baseURI =
            ('http' == substr($host, 4) ? $host : 'https://' . $host) .
            ('/' == substr($host, -1) ? '' : '/') .
            'fmi/data/v1/databases/' .
            $database . '/';
    }
}