<?php

namespace App\Controller;

use App\Service\FileMakerAPI;
use \Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    /**
     * We can use the same URL for different methods - this is the controller for handling a
     * GET request to /login and simply renders the view. Look further below for what happens
     * when we POST that form back
     *
     * @Route("/login", name="login", methods={"GET"})
     */
    public function index()
    {
        return $this->render('login/index.html.twig', [
            'controller_name' => 'LoginController',
        ]);
    }


    /**
     * @Route("/logout", name="logout", methods={"GET"})
     *
     * @param SessionInterface $session
     *
     * @return RedirectResponse
     */
    public function logout(SessionInterface $session)
    {
        // Put a message in the UI
        $this->addFlash('success', 'You have been logged out.');

        // Explicitly set that they are not logged in
        $session->set('loggedIn', false);

        // Remove their token from the session
        $session->remove('fmToken');

        // Redirect them to the homepage
        return new RedirectResponse('/');
    }

    /**
     * @Route("/login", name="login_post", methods={"POST"})
     *
     * @param SessionInterface $session
     * @param FileMakerAPI $fm
     * @param Request $request
     * @return RedirectResponse
     */
    public function doLogin(SessionInterface $session, FileMakerAPI $fm, Request $request)
    {
        try {
            // Try to log the user in
            $fm->logUserIn(
                $request->request->get('username'),
                $request->request->get('password')
            );

            // If we get to here it worked, so display a message in the UI to tell them that
            $this->addFlash('success', 'You have been logged in.');

            // And set a session flag so that the view knows that they're logged in
            $session->set('loggedIn', true);

            // Then send them back to the homepage
            return new RedirectResponse('/');
        } catch(Exception $e) {
            // If anything goes wrong trying to login then an exception will be thrown and they'll end up here.
            // Set a message in the UI
            $this->addFlash('error', 'Unable to login. Please check your credentials.');

            // Explicitly set that they are not logged in
            $session->set('loggedIn', false);

            // Redirect them back to the login screen to try again
            return new RedirectResponse('/login');
        }
    }
}
