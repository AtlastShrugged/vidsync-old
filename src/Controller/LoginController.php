<?php

namespace App\Controller;

use Google_Client;
use Google\Service\YouTube as Google_Service_YouTube;
use Google_Service_Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LoginController extends AbstractController
{
    /**
     * @Route("/login", name="login")
     */

    public function login(Request $request)
    {
        $session = $request->getSession();
        $access_token = $session->get('access_token');

        if ($access_token) {
            return $this->redirectToRoute('home');
        }

        return $this->render('first_login.html.twig');
    }

    /**
     * @Route("/login_with_google", name="login_with_google")
     */

    public function loginWithGoogle(Request $request)
    {
        $client = new Google_Client();
        $client->setClientId('328000882849-mb5o55rongrsa65c6m0vvcupqp5atbl8.apps.googleusercontent.com');
        $client->setClientSecret('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');
        $client->setRedirectUri($this->generateUrl('login_callback', [], UrlGeneratorInterface::ABSOLUTE_URL));
        $client->addScope(Google_Service_YouTube::YOUTUBE_READONLY);


        $authUrl = $client->createAuthUrl();
        return $this->redirect($authUrl);
    }

    /**
     * @Route("/login_callback", name="login_callback")
     */
    public function loginCallback(Request $request)
    {
        $client = new Google_Client();
        $client->setClientId('328000882849-mb5o55rongrsa65c6m0vvcupqp5atbl8.apps.googleusercontent.com');
        $client->setClientSecret('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');
        $client->setRedirectUri($this->generateUrl('login_callback', [], UrlGeneratorInterface::ABSOLUTE_URL));
        $client->addScope(Google_Service_YouTube::YOUTUBE_READONLY);
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');

        $code = $request->query->get('code');
        $accessToken = $client->fetchAccessTokenWithAuthCode($code);

        

        try {
            $youtube = new Google_Service_YouTube($client);
            $channels = $youtube->channels->listChannels('snippet', ['mine' => true]);
            if (count($channels) === 0) {
                return $this->render('error.html.twig', [
                    'message' => 'No channel for this account, try with another one'
                ]);
            }
        } catch (Google_Service_Exception $e) {
            return $this->render('error.html.twig', [
                'message' => $e->getMessage()
            ]);
        }

        $session = $request->getSession();
        $session->set('access_token', $accessToken);

        return $this->redirectToRoute('home');
    }
}
