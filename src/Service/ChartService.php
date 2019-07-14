<?php


namespace App\Service;

use \Exception;

class ChartService
{

    /** @var FileMakerAPI */
    protected $fm;

    /**
     * ChartService constructor.
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
    public function loadAndStructureChartData()
    {
        // Call the FileMaker Data API to get the data we need
        $data = $this->loadData();

        // Transform it into the structure the chart.js script expects
        return $this->structureData($data);
    }

    /**
     * @return array
     *
     * @throws Exception
     */
    private function loadData()
    {
        // As we did in the Postman example, we need to perform a query for any rows in the VirtualList
        // where Column2 is >= 0, i.e. it has a data value in there
        // To populate it, we need to call the SetStates script 'prerequest' so that it's been run before
        // the above query (otherwise the VirtualList table will be empty)
        $body = [
            "query" => [
                ["Column2" => ">=0"],
            ],
            "script.prerequest" => "SetStates",
        ];

        // JSON encode the body into the 'body' key for the request
        $params = ['body' => json_encode($body)];

        // Make the call, when we search it's a POST to the _find pseudo endpoint (this is where the FileMaker
        // Data API stops being restful because this is essentially and Remote Procedure Call (RPC) query
        return $this->fm->performRequest('POST', 'layouts/VirtualList/_find', $params);
    }

    /**
     * @param array $data
     * @return array
     */
    private function structureData($data)
    {
        // The chart.js script on the page expects to get two arrays back, one which holds the
        // labels to appear on the x axis, the other for the corresponding value
        $labels = [];
        $values = [];

        // loop through each of the rows in the returned virtual list
        foreach($data as $state)
        {
            // The label is in Column1
            $labels[] = $state['fieldData']['Column1'];

            // The data in Column2
            $values[] = $state['fieldData']['Column2'];
        }

        // Put them into an array and send it back
        return [
            'labels' => $labels,
            'values' => $values
        ];
    }
}