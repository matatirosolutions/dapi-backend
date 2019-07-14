<?php

namespace App\Controller;

use \Exception;
use App\Service\ChartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ChartController extends AbstractController
{
    /**
     * @Route("/chart/data", name="chart")
     * @param ChartService $chart
     *
     * @return JsonResponse
     */
    public function index(ChartService $chart)
    {
        try {
            // Use the ChartService to load the data, and get it into the structure which chart.js
            // expects to allow it to render the chart.
            $data = $chart->loadAndStructureChartData();

            // Return that as a JSON object
            return new JsonResponse($data);
        } catch(Exception $e) {
            // If something goes wrong, return an empty data structure so that the chart.js doesn't
            // fail and cause other things not to render properly
            return new JsonResponse( [
                'labels' => [],
                'data' => []
            ]);
        }
    }
}
