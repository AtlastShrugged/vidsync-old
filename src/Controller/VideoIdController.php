<?php

namespace App\Controller;

use Google_Client;
use Google\Service\YouTube as Google_Service_YouTube;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class VideoIdController extends AbstractController
{
    #[Route('/code/{videoId}', name: 'video_code')]
    public function videoCode(Request $request, string $videoId)
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

        $width = $request->get('textbox1');
        $height = $request->get('textbox2');

        $autoplay = $request->get('autoplay', 0);
        $loop = $request->get('loop', 0);
        $disablekb = $request->get('disablekb', 0);

        $iframe = '<iframe width="' . $width . '" height="' . $height . '" 
        src="https://www.youtube.com/embed/' . $video['videoId'] . '?autoplay=' . $autoplay . '&disablekb=' . $disablekb . '&loop=' . $loop . '&playlist: ' . $video['videoId'] . ' " frameborder="0" allowfullscreen></iframe>';
        $embedCode = '
            <div id="player"></div>
        
            <script>
              
              var tag = document.createElement("script");
        
              tag.src = "https://www.youtube.com/iframe_api";
              var firstScriptTag = document.getElementsByTagName("script")[0];
              firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        
              var player;
              function onYouTubeIframeAPIReady() {
                player = new YT.Player("player", {
                  height: "' . $height . '",
                  width: "' . $width . '",
                  videoId: "' . $video['videoId'] . '",
                  
                  playerVars: {
                    disablekb: ' . $disablekb . ',
                    autoplay: ' . $autoplay . ',
                    loop: ' . $loop . ',
                    playlist: "' . $video['videoId'] . '",
                  },
                });
              }
            </script>';

        return $this->render('home/nextCode.html.twig', [
            'video' => $video,
            'iframe' => $iframe,
            'embedcode' => $embedCode

        ]);
    }
}
