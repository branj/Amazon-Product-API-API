<?php
 

require 'aws_signed_request.php';

 
class AmazonProductAPI
{

    /*USER INFO*/
    private $public_key     = "AKIAJ7LPEK463BV7XHEQ";
    private $private_key    = "gAOXnY7cyGBUWD4TIA5TA564l/idD6gd9kZxouWB";
    private $assoiciate_name = "game08e-20";

   /*THIS ARE ALL THE SEARCH INDEXES*/
    const ALL = "All";
    const AUTOMOTIVE = "Automotive";
    const BLENDED = "Blended";
    const APPARREL = "Apparrel";
    const BABY = "Baby";
    const BEAUTY = "Beauty";
    const BOOKS =  "Books";
    const CLASSICAL = "Classical";
    const DVD = "DVD";
    const ELECTRONICS = "Electronics";
    const GROCERY =  "Grocery";
    const HEALTH = "HealthPersonalCare";
    const HOME = "HomeGarden";
    const JEWELRY = "Jewelry";
    const KITCHEN =  "Kitchen";
    const LIGHTING = "Lighting";
    const MP3DOWNLOADS = "MP3Downloads";
    const MUSIC = "Music";
    const INSTRUMENTS = "MusicalInstruments";
    const MUSTICTRACKS = "MusicTracks";
    const OFFICE = "OfficeProducts";
    const OUTDOORLIVING = "OutdoorLiving";
    const OUTLES = "Outlet";
    const SHOES = "Shoes";
    const SOFTWARE =  "Software";
    const SOFTWAREVIDEOGAMES = "SoftwareVideoGames";
    const TOYS = "Toys";
    const VHS = "VHS";
    const VIDEO = "Video";
    const GAMES = "VideoGames";
    const WATCHES =  "Watches";

    /*Ensure the Amazon Request worked*/
    private function verifyXmlResponse($response)
    {
        if ($response === False)
        {
            throw new Exception("Could not connect to Amazon");
        }
        else
        {
            if (isset($response->Items->Item->ItemAttributes->Title))
            {
                return ($response);
            }
            else
            {
                throw new Exception("Invalid xml response.");
            }
        }
    }

    /*Builds the REST query to Amazon adds; basically adds the user information and generates the signature*/
    private function queryAmazon($parameters)
    {
        return aws_signed_request("com",
                                  $parameters,
                                  $this->public_key,
                                  $this->private_key,
                                   $this->assoiciate_name);
    }

    /*Most used function,, searches through products and returns xml to do with what you want*/
    public function searchProducts($search,$category,$searchType, $itemPage)
    {
   
        switch($searchType) 
        {
            case "UPC" :
                $parameters = array("Operation"     => "ItemLookup",
                                    "ItemId"        => $search,
                                    "SearchIndex"   => $category,
                                    "IdType"        => "UPC",
                                    "ResponseGroup" => "Medium");
                            break;
 
            case "TITLE" :
                $parameters = array("Operation"     => "ItemSearch",
                                    "Title"         => $search,
                                    "SearchIndex"   => $category,
                                    "ResponseGroup" => "Medium",
                                    "ItemPage" => $itemPage );
                            break;
 
        }
 
        $xml_response = $this->queryAmazon($parameters);


 
        return $this->verifyXmlResponse($xml_response);
 
    }
 
    public function getItemByUpc($upc_code, $product_type)
    {
        $parameters = array("Operation"     => "ItemLookup",
                            "ItemId"        => $upc_code,
                            "SearchIndex"   => $product_type,
                            "IdType"        => "UPC",
                            "ResponseGroup" => "Medium");
 
        $xml_response = $this->queryAmazon($parameters);
 
        return $this->verifyXmlResponse($xml_response);
 
    }
 
    public function getItemByAsin($asin_code)
    {
        $parameters = array("Operation"     => "ItemLookup",
                            "ItemId"        => $asin_code,
                            "ResponseGroup" => "Medium");
 
        $xml_response = $this->queryAmazon($parameters);
 
        return $this->verifyXmlResponse($xml_response);
    }
 
    public function getItemByKeyword($keyword, $product_type, $pagenum)
    {
        $parameters = array("Operation"   => "ItemSearch",
                            "Keywords"    => $keyword,
                            "SearchIndex" => $product_type,
                            "ItemPage" => $pagenum,
                            "ResponseGroup" => "Medium");
 
        $xml_response = $this->queryAmazon($parameters);
 
        return $this->verifyXmlResponse($xml_response);
    }

    public function searchForProductsAndDisplayImages($keyword, $searchindex)
    {
        try
        {
        /* Returns a SimpleXML object */
         $result = $this->getItemByKeyword($keyword,$searchindex,1);
        }
    catch(Exception $e)
    {
        echo $e->getMessage();
    }

$currentpage = 1;
foreach ($result->Items->Item as $product)
 {
    echo "<img src='".$product->SmallImage->URL."'/>";
 }
 echo "<br/>Page:";

while($currentpage <= $result->Items->TotalPages)
{
echo <<< HTML
    <div class="pagelink">$currentpage</div>
HTML;

    if($currentpage == 25)
    {
        echo "...";
        $currentpage = $result->Items->TotalPages - 1;
    }

 $currentpage++;
}
    }
 
}
 
?>