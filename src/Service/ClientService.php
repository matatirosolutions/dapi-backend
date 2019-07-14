<?php


namespace App\Service;

use \Exception;

class ClientService
{
    /** @var FileMakerAPI */
    protected $fm;

    /**
     * ClientService constructor.
     * @param FileMakerAPI $fm
     */
    public function __construct(FileMakerAPI $fm)
    {
        $this->fm = $fm;
    }

    /**
     * @return array
     *
     * @throws Exception
     */
    public function fetchLastFiveClients()
    {
        // Start with the base layout
        $url = 'layouts/ContactsAPI/records';

        // add that we only want five records
        $url .= '?_limit=5';

        // Sort by creation order, this means passing in an array of arrays, which have the keys
        // 'fieldName' and 'sortOrder'
        $sort = [
            [
                'fieldName' => 'Created',
                'sortOrder' => 'descend',
            ]
        ];

        // and then url encoding the json encoding of that array - honestly, it's hinky! This method
        // works fine when combined with a _find because we would be POSTing this data, but when it's
        // being sent as a URL parameter it's just weird!
        $url .= '&_sort='. rawurlencode(json_encode($sort));

        // Make the call - we don't need any parameters for this because
        $clients = $this->fm->performRequest('GET', $url, []);

        return $this->extractClientData($clients);
    }

    private function extractClientData(array $clients)
    {
        $data = [];
        foreach($clients as $client) {
            $data[] = $client['fieldData'];
        }

        return $data;
    }
}