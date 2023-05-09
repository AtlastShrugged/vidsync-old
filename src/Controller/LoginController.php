<?php

namespace App\Controller;

use Google_Client;
use App\Entity\AccessToken;
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
         $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
         $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
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
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
         $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $client->setRedirectUri($this->generateUrl('login_callback', [], UrlGeneratorInterface::ABSOLUTE_URL));
        $client->addScope(Google_Service_YouTube::YOUTUBE_READONLY);

        $session = $request->getSession();

        if ($session->has('access_token')) {
            $client->setAccessToken($session->get('access_token'));
        } else {
            $code = $request->query->get('code');
            $accessToken = $client->fetchAccessTokenWithAuthCode($code);
            
            $client->setAccessToken($accessToken);

            // Store access token in session
            $session->set('access_token', $accessToken);
        }
    
        // Check if access token has expired
        if ($client->isAccessTokenExpired()) {
            // Refresh the access token
            $refreshToken = $client->getRefreshToken();
            $client->fetchAccessTokenWithRefreshToken($refreshToken);
    
            // Update the access token in session
            $session->set('access_token', $client->getAccessToken());

            // Set expiration time to 1 minute
            $accessToken = $client->getAccessToken();
            $accessToken['expires_in'] = 60;
            $client->setAccessToken($accessToken);
            $session->set('access_token', $accessToken);
        }

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
        return $this->redirectToRoute('home');
    }
}