<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use Goutte\Client;

class ScraperController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    private $crawledLinks = array();
    private $client = null;
    private $ignoredLinks = array(
        '/diy_videos/',
    );
    private $startingAddress = array();
    private $currentDomain = null;
    private $lastPage = 0;
    public function run() {
        $this->client = new Client();

        $crawler = $this->client->request('GET', 'https://www.bavauto.com/');
        $sectionsLinks = $crawler->filter('.category-box a')->links();
        $this->clickOnLinks($sectionsLinks);
        echo $this->lastPage;
    }
    //Does click on links
    private function clickOnLinks($pageLinks)
    {
        foreach ($pageLinks as $link) {
            if (!$this->isLinkDuplicate($link->getUri())) {

                //start timer
                $StartTime = microtime(true);
                //gets DOM
                $crawler = $this->client->request('GET', $link->getUri());

                //end timer
                $EndTime = microtime(true);

                $transactionTime = ($EndTime - $StartTime);
                echo 'time: ' . $transactionTime . 's - ' . 'clicked on: ' . $link->getUri() . '<br>';

                $pageLinks1Deep = $crawler->filter('h2.product-name a')->links();
                $lastPage = 0;
                $pageNum = 0;
                
                $i = 0;
                $nextUrl = $crawler->filter('h2.product-name a')->link()->getUri();
                $lastNum = (int) explode("=", $nextUrl);
                while ($i < $lastNum) {
                    echo "I: ";
                    var_dump($i);
                    echo "</br>";
                    echo "lastNum: ";
                    var_dump($lastNum);
                    echo "</br>";
                    
                    var_dump($i < $lastNum);
                    echo "</br>";
                    $crawler = $this->client->request('GET', $link->getUri());
                    $nextUrl = $crawler->filter('h2.product-name a')->link()->getUri();
                    $lastNum = (int) explode("=", $nextUrl);
                    $i++;
                    var_dump($lastNum);
                    echo "<br>";
                }
                exit;
                $this->clickOnLinks($pageLinks1Deep);
            }
        }
    }
    private function isLinkDuplicate($url)
    {

        //check to make sure its only on the current crawling domain
        $regexDomain = str_replace('/', '\/', $this->currentDomain);
        preg_match('/' . $regexDomain . '/', $url, $onWebsite);
        if (empty($onWebsite)) {
            return true;
        }

        //check if hashtag is present in url, ignore
        preg_match('/(#)/', $url, $hasHash);
        if (!empty($hasHash)) {
            return true;
        }

        //check if ignored match link to check
        foreach ($this->ignoredLinks as $ignoredLink) {
            preg_match($ignoredLink, $url, $matches);

            if (!empty($matches)) {
                return true;
            }
        }

        //check if link is in crawled links
        if (in_array($url, $this->crawledLinks)) {
            return true;
        } else {
            array_push($this->crawledLinks, $url);
            return false;
        }
    }
    private function clickOnProducts() {

    }
}