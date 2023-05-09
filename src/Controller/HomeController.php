<?php

namespace App\Controller;

use Google_Client;
use Google\Service\YouTube as Google_Service_YouTube;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(Request $request)
    {
        $session = $request->getSession();
        $access_token = $session->get('access_token');
        
        if (!$access_token) {
            return $this->redirectToRoute('login');
        }
        echo $access_token["access_token"];
        $client = new Google_Client();
        $client->setAccessToken($access_token);

        if ($client->isAccessTokenExpired()) {
            // Get the refresh token from the session
            $refresh_token = $session->get('refresh_token');

            // Set the refresh token and fetch a new access token
            $client->fetchAccessTokenWithRefreshToken($refresh_token);
            $access_token = $client->getAccessToken();
    
            // Update the session with the new access token
            $session->set('access_token', $access_token);
        }

        $youtube = new Google_Service_YouTube($client);

        $channelsResponse = $youtube->channels->listChannels('id,contentDetails', [
            'mine' => true,
        ]);
        $channelId = $channelsResponse[0]['id'];

        $searchResponse = $youtube->search->listSearch('id,snippet', [
            'order' => 'date',
            'channelId' => $channelId,
            'maxResults' => 500,
        ]);

        $videoIds = array();
        foreach ($searchResponse['items'] as $searchResult) {
            $videoIds[] = $searchResult['id']['videoId'];
        }

        if (empty($videoIds)) {
            return $this->render('home/novideo.html.twig', [
                'videos' => [],
            ]);
        }

        $videosResponse = $youtube->videos->listVideos('snippet,contentDetails', [
            'id' => implode(',', $videoIds),
        ]);

        $videos = array();
        foreach ($videosResponse as $video) {
            $durationString = $video->contentDetails->duration;
            $duration = new \DateInterval($durationString);
            $durationSeconds = $duration->h * 3600 + $duration->i * 60 + $duration->s;


            $durationReadable = gmdate('H:i:s', $durationSeconds);
            $videos[] = array(
                'title' => $video->snippet->title,
                'thumbnail' => $video->snippet->thumbnails->default->url,
                'duration' => $durationReadable,
                'videoId' => $video->id,
                'publishedAt' => $video->snippet->publishedAt,
            );
        }

        $keyword = $request->query->get('keyword');
        $limit = $request->query->get('limit', 3);

        if ($keyword) {
            $videos = array_filter($videos, function ($video) use ($keyword) {
                $title = $video['title'];
                return (strtolower($title[0]) == strtolower($keyword[0])) &&
                    str_contains(strtolower($title), strtolower($keyword));
            });
        }
        return $this->render('home/index.html.twig', [
            'videos' => $videos,
            'keyword' => $keyword,
            'noResults' => empty($videos),
            'limit' => $limit,
        ]);
    }
    
    public function signout(Request $request)
    {
        $session = $request->getSession();
        $session->clear();
    
        return $this->redirectToRoute('login');
    }
}
