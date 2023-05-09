<?php

namespace App\Controller;

use Google_Client;
use Google\Service\YouTube as Google_Service_YouTube;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class VideoController extends AbstractController
{
    #[Route('/videos/{videoId}', name: 'video_detail')]
    public function videoDetail(Request $request, string $videoId)
    {
        $session = $request->getSession();
        $access_token = $session->get('access_token');
        
        if (!$access_token) {
            return $this->redirectToRoute('login');
        }

        $client = new Google_Client();
        $client->setAccessToken($access_token);

        $youtube = new Google_Service_YouTube($client);

        $videosResponse = $youtube->videos->listVideos('snippet', [
            'id' => $videoId,
        ]);

        $video = [
            'title' => $videosResponse[0]->snippet->title,
            'thumbnail' => $videosResponse[0]->snippet->thumbnails->high->url,
            'videoId' => $videosResponse[0]->id,
        ];

        return $this->render('home/next.html.twig', [
            'video' => $video,
        ]);
    }
}
