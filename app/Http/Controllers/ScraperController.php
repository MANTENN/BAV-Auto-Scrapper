<?php
//@TODO: Order when order totals hit 250; free shipping
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
    private $excludedLinks;
    private $products = array();


    public function run() {
        $this->client = new Client();
        $StartTime = microtime(true);

        $crawler = $this->client->request('GET', 'https://www.bavauto.com/');
        //array of objects--collection of links
        // $sectionsLinks = $crawler->filter('.category-box a')->links();
        $this->excludedLinks = array("/diy_videos");
        $data = $crawler->filter('.category-box a')->each(function($node) {
            return $this->clickOnLinks($node);
        });
        $data = array_filter($data, function($element) {
            return $element != NULL;
        });
        // $this->clickOnLinks($sectionsLinks);
        $EndTime = microtime(true);
        
        $transactionTime = ($EndTime - $StartTime);
        echo 'time: ' .$transactionTime . 's - <br>';
        var_dump($data);
        // var_dump($sectionsLinks); exit;

        // echo $this->lastPage;
    }
    private function printLog($data) {
        foreach($data as $label => $value) {
            if (gettype($value) != "string") {
                continue;
            }
            echo "<strong>" . $label . "</strong>: " . $value . "<br>";
        }
        echo "<hr>";
    }
    //Does click on links
    /*private function clickOnLinks($pageLinks)
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
                echo 'time: ' .$transactionTime . 's - ' . 'clicked on: ' . $link->getUri() . '<br>';
                //List of Products on a specific page
                if($crawler){
                    $titleObj = $this->returnNode($crawler, 'div.product-name .h1');
                    var_dump($titleObj); exit;
                    $partNumObj = $this->returnNode($crawler, '.product-sku');
                    if($titleObj && $partNumObj) {
                        $product = array(
                            "title" => $titleObj->text(),
                            "part_num" => explode(":", $partNumObj->text())[1]
                        );
                        var_dump($product); exit;
                        array_push($this->products, $product);
                    }
                }
                $pageLinks1Deep = $crawler->filter('h2.product-name a')->links();
                if($pageLinks1Deep) {
                    $this->clickOnLinks($pageLinks1Deep);
                }
                $nextPage = $crawler->filter('a.next.i-next')->links();
                //click on next page
                if($nextPage) {
                    $this->clickOnLinks($nextPage);
                }
            }
        }
    }*/
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
    private function clickOnLinks($section) {
        $text = $section->text();
        $link = $section->link();
        
        $this->printLog(array("link" => $link, "text" => $text));
        // $this->clickOnLinks($section);
        if(!$this->isLinkDuplicate($section->attr('href'))) {

            $sectionPage = $this->client->click($link);
            $products = $sectionPage->filter('h2.product-name a')->each(function($product, $i) {
                $this->printLog(array("link" => $product->attr('href'), "text" => $product->text()));
                return array("name" => $product->text(), "link" => $product->attr('href'));
            });
            $productsNextPage = $sectionPage->filter('a.next.i-next');
            $nextPage = $this->clickOnLinks($productsNextPage);
            if(!isset($nextPage)) {
                $nextPage = array();
            }
            ///messy; should not always click and return the title link and products for every page 
            return array(
                "title" => trim($text),
                "link" => $link,
                "products" => array_merge($products,  $nextPage)
            );
        }
    }
    private function getProducts($linkObj) {
        $text = $section->text();
        $link = $section->link();

        if(!$this->isLinkDuplicate($link->attr('href'))) {
            $productsPage = $this->client->click($linkObj);
            $products = $productsPage->filter('h2.product-name a')->each(function($product, $i) {
                // $this->printLog(array("link" => $product->attr('href'), "text" => $product->text()));
                return array("name" => $product->text(), "link" => $product->attr('href'));
            });
            $productsNextPage = $productsPage->filter('a.next.i-next');
            $this->getProducts($productsNextPage);
            return $products;
        }
        return;
        
    }
}