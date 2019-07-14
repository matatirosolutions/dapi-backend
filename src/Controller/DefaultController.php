<?php

namespace App\Controller;

use \Exception;
use App\Service\ClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="default")
     *
     * @param SessionInterface $session
     * @param ClientService $clientService
     *
     * @return Response
     */
    public function index(SessionInterface $session, ClientService $clientService)
    {
        $clients = [];
        if($session->get('loggedIn')) {
            // If they're logged in try and load the last five clients
            try {
                $clients = $clientService->fetchLastFiveClients();
            } catch(Exception $e) { }
        }

        // Render the view and return it to the browser
        return $this->render('default/index.html.twig', [
            'loggedIn' => $session->get('loggedIn'),
            'clients' => $clients,
        ]);
    }
}
