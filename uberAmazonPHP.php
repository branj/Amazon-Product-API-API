<?php
 
require 'aws_signed_request.php';

 
class AmazonProductAPI
{


    /*USER INFO*/
    private $public_key     = "Your Amazon Public Key";
    private $private_key    = "Your Private Key";
    public $associateTag = "Your assoicated tag";


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
                echo $response->Items->Request->Errors->Error->Message;
                $response = False;
                return($response);
            }
        }
    }





    
    private function queryAmazon($parameters)
    /**************************************************************************
      Builds the REST query to Amazon by orgainzing the parameters, adding 
      the user information and generating the signature
    **************************************************************************/
    {
        return aws_signed_request("com",
                                  $parameters,
                                  $this->public_key,
                                  $this->private_key
                                  );
    }




    
    public function searchProducts($search,$category,$searchType, $itemPage)
    /***************************************************************************
     Most used function,, searches through products and returns xml to do with what you want
    ***************************************************************************/
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
 
   



    public function searchForItemsByNode($params)
    /***************************************************************************
    This will search for items in nodes by with a keyword and minimum and 
    maximum prices. 
    
    Node is the catagory it will search in, for more info: 
     http://docs.amazonwebservices.com/AWSECommerceService/2010-11-01/DG/
    
    ResponseGroup is the type of response you want (ie. Medium, Large, Images, etc.
    if you have no idea of what a response group is go to:
      http://docs.amazonwebservices.com/AWSECommerceService/2010-11-01/DG/
    
    The other values should be obvious :)   
    
    Since you have to use a search index just pass the name of a browse node result.
    ***************************************************************************/        
     {
       
        $parameters = array( "Operation"      => "ItemSearch",
                             "ResponseGroup" => "Medium, Variations",
                             "MerchantId" => "All",
                             "Condition" => "All",
                             "Availability" => "Available"

                             );
           //adding passed params
        foreach($params as $name=>$value)
        {
            if($value != 'undefined')
                $parameters["$name"] = $value;
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






    public function getItemsByAsin($asin_code)
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






/*******************************************************************************
 *************** NODE RETRIVAL AND INFORMATION FUNCTIONS ***********************                              *
 *******************************************************************************/

   
    function browseNode($nodeId)
     /*****************************************************************
      Get all the children nodes to build an exhaustive browsing
      list of products. Feel free to use this library how you want, but
      it was written with navigating the application through node's, not 
      through search indexes,as nodes provide more precision when it comes 
      time to do ItemSearch's. Once you've called browseNode you can get all its
      info: children, ancestors, nodeId, name. 
       
      Although you can sift through the xml response after calling this, be aware
      there are functions that take the xml repsonse and return what you want to make
      life easier. Afterall, isn't that what were all after?
      
      
      To look up Node ID's and how Amazon is organized go to browsenodes.com
     *****************************************************************/
    {
        $parameters = array("Operation"     => "BrowseNodeLookup",
                            "BrowseNodeId"  => $nodeId,
                            "ResponseGroup" => "BrowseNodeInfo");

        $xml_response = $this->queryAmazon($parameters);

        if(isset($xml_repsonse->BrowseNodes->Errors->Error->Message))
        {
            return false;
        }

        else
        {
            return $xml_response;
        }
    }





    function getNodeName($xml)
    /********************************************************
       Gets a Node's name from an XML repsonse
     *******************************************************/
    {
        
        $name = $xml->BrowseNodes->BrowseNode->Name;

        return $name;
    }





    function getNodeId($xml)
    /********************************************************
       Gets a Node's ID from an XML repsonse
     *******************************************************/
    {
        $nodeId = $xml->BrowseNodes->BrowseNode->BrowseNodeId;

        return $nodeId;
    }




    
    function getNodeAncestors($xml)
    /*************************************************************************
     Takes an xml repsonse from BrowseNode request and returns the node's
     ancestor's name and ID as an array: ['nodesName'=> 'nodesId']
    ************************************************************************/
    {
        $ancestors = array();
        $node = $xml->BrowseNodes->BrowseNode->Ancestors->BrowseNode;
        while( $node )
        {
            $ancestors[] = $node->BrowseNodeId;
            $node = $node->Ancestors->BrowseNode;
        }

            return $ancestors;
         
    }

    



    function getNodeChildren($xml)
    /*************************************************************************
      Takes an xml repsonse from a BrowseNode request and returns the node's
      children's name and ID: ['nodesName'=> 'nodesId']
    ************************************************************************/
    {
        $children = array();

        foreach($xml->BrowseNodes->BrowseNode->Children->BrowseNode as $node)
        {
            
                $children[ "$node->Name" ] = $node->BrowseNodeId;
            
        }

            return $children;
    }





     function getSearchIndexByRootNodeId($ancestors)
    /* **************************************************************************  
      Amazon REQUIRES a SearchIndex to accompany any ItemSearch requests, yet there is no 
      ability to get a the corresponding SearchIndex for a given NodeID , hence
      this function. If none of this makes sense, keep working with the Amazon API
      I'm sure you'll eventually find this function useful. To clarify....
     
      This function takes the root of any given node and finds it's corresponding
      SearchIndex, so make sure the $nodeId you send is a root node (ie. call
      getAncestors on whichever node your one when you want to call SearchIndex on that
      node) 
      
      The array was generated by visiting BrowseNodes.com and seeing the root node 
      of the most commonly used Nodes, but it is by no means exhaustive. If you 
      want to add a new Node to map to a SearchIndex, go to browsenodes.com, 
      see what node you want and add it to this array with the SearchIndex. 
      All being said, as long as you're staying within general Node types, ie. 
      The useful ones, you should trust in what's here. 
      
      Also, some of these maps happen earlier than the root node so it's worth
      trying to see if you can map the Node you're at. (ie. Video Games root node 
      is Electronics, yet a SearchIndex exists for Video Games, so it is possible 
      to get a more refined search for Video Games by mapping the VideoGame node
      to a SearchIndex.  
    *****************************************************************************/
    {
         


        $BrowseNodeIdToSearchIndex = array(
                                   1036682 => "Apparel" ,
                                   15690151 => "Automotive",
                                   11055981 => "Beauty",
                                   1000 => "Books",
                                   301668 => "Classical",
                                   493964 => "Electronics",
                                   3580501 => "GourmetFood",
                                   16310101 => "Grocery",
                                   3760931 => "HealthPersonalCare",
                                   285080 => "HomeGarden",
                                   228239 => "Industrial",
                                   3880591 => "Jewelry",
                                   284507 => "Kitchen",
                                   599872 => "Magazines",
                                   10304191 => "Miscellaneous",
                                   195211011 => "MP3Downloads",
                                   301668 => "Music",
                                   11965861 => "MusicalInstruments",
                                   1084128 => "OfficeProducts",
                                   286168 => "OutdoorLiving",
                                   541966 => "PCHardware",
                                   1063498 => "PetSupplies",
                                   502394 => "Photo",
                                   672124011=> "Shoes" ,
                                   409488 => "Software",
                                   3375251 => "SportingGoods",
                                   468240 => "Tools",
                                   165793011 => "Toys",
                                   130 => "Video ",
                                   471304 => "VideoGames",
                                   471280 => "VideoGames",
                                   1079730 => "Watches",
                                   508494 => "Wireless",
                                   13900851 => "WirelessAccessories");

        foreach($ancestors as $nodeId)
        {
            
            if($BrowseNodeIdToSearchIndex["$nodeId"]) {

                    return $BrowseNodeIdToSearchIndex["$nodeId"];
                    
                }
        }

                    //To ensure it doesn't fail, and will still allow for a search
                    return "All";

    }





    function getTopSellersByNode($nodeId)
   /****************************************************************************   
     Given a nodeID, this will return all the top sellers for that category
     This will likely be used for standard display (ie. Before the user searches
     with their parameters 
    ****************************************************************************/
    {
        $parameters = array("Operation"     => "BrowseNodeLookup",
                            "BrowseNodeId"  => $nodeId,
                            "ResponseGroup" => "TopSellers");

        $xml_response = $this->queryAmazon($parameters);

        return $xml_response;

        /* Accessing this response:
         
          $node = $xml_response->BrowseNodes->BrowseNode->TopSellers->TopSeller
          $node->ASIN
          $node->Title
         
         */
   }

   



    function getNewestReleasesByNode($nodeId)
   /***************************************************************************
     Given a nodeID, this will return all the Newest Release for that category
     You can then use the ASIN number to do ItemLookups to get more info.
    ****************************************************************************/
   {
        $parameters = array("Operation"     => "BrowseNodeLookup",
                            "BrowseNodeId"  => $nodeId,
                            "ResponseGroup" => "NewReleases");

        $xml_response = $this->queryAmazon($parameters);

        return $xml_response;

        /* Accessing this response:
         
          $node = $xml_response->BrowseNodes->BrowseNode->NewReleases->NewRelease
          $node->ASIN
          $node->Title
         
         */
   }





   function getMostWishedForByNode($nodeId)
   /***************************************************************************
     Given a nodeID, this will return all the MostWishedFor that category.
     You can then use the ASIN number to do ItemLookups to get more info.
    ****************************************************************************/
   {
        $parameters = array("Operation"     => "BrowseNodeLookup",
                            "BrowseNodeId"  => $nodeId,
                            "ResponseGroup" => "MostWishedFor");

        $xml_response = $this->queryAmazon($parameters);

        return $xml_response;

        /* Accessing this response:
         
          $node = $xml_response->BrowseNodes->BrowseNode->TopItemSet->TopItem
          $node->ASIN
          $node->Title
          $node->DetailPageURL
         
         */
   }





   function getMostGiftedByNode($nodeId)
   {
    /***************************************************************************
      Given a nodeID, this will return all the MostGifted that category.
      You can then use the ASIN number to do ItemLookups to get more info.
    ****************************************************************************/
        $parameters = array("Operation"     => "BrowseNodeLookup",
                            "BrowseNodeId"  => $nodeId,
                            "ResponseGroup" => "MostGifted");

        $xml_response = $this->queryAmazon($parameters);

        return $xml_response;

        /* Accessing this response:
         
         $node = $xml_response->BrowseNodes->BrowseNode->TopItemSet->TopItem
         $node->ASIN
         $node->Title
         $node->DetailPageURL
        
        */
   }





 /*******************************************************************************
 *************** SHOPPING CART FUNCTIONS *************** ***********************
 *******************************************************************************/


 function cartCreate($itemID)
 /***************************************************************************
    Creates a new cart shopping cart, feel free to use this, but for simplicity
    purposes, just call cartAdd from your application, and it will set a cookies
    and call cartCreate if the Cookie wasn't found on their computer.
  ****************************************************************************/
  {
     $parameters = array("Operation"    => "CartCreate",
                         "AssociateTag" => $this->associateTag,
                         "Item.1.ASIN" => $itemID,
                         "Item.1.Quantity" => 1);

     $xml = $this->queryAmazon($parameters);

     //Create cookies for this user's new cartid and HMAC
     $expire=time()+60*60*24;
     setcookie('cartID', $xml->Cart->CartId , $expire);
     setcookie('HMAC', $xml->Cart->HMAC , $expire);

     return $xml;
     

  }

  function cartAdd($itemID)
  /*************************************************************************
    Adds items to an existing cart. Don't worry about creating a new one,
    will check for a cookie and set one 
   *************************************************************************/
  {
      //Checking to see if the cart cookies have been set
      if(isset($_COOKIE['cartID'])){
          
         $parameters = array("Operation"    => "CartAdd",
                         "AssociateTag" => $this->associateTag,
                         "CartId" => $_COOKIE['cartID'],
                         "HMAC" => $_COOKIE["HMAC"],
                         "Item.1.ASIN" => $itemID,
                         "Item.1.Quantity" => 1,
                          );

         $xml = $this->queryAmazon($parameters);
     
      }

      //not set...create a new cart and cookies
      else {

         $xml = $this->cartCreate($itemID);

      }
      
      
      return $xml;

      
      
  }




  function cartGet()
  /********************************************************************
   * Retrieves the contents of the cart, assuming it's been created, I've formated
   * it to output json, so you can do with it what you may client side
   * the contents of the cart
   **************************************************************************/
  {
         $parameters = array("Operation"    => "CartGet",
                            "AssociateTag" => $this->associateTag,
                            "CartId" => $_COOKIE['cartID'],
                            "HMAC" => $_COOKIE["HMAC"]
                          );

        $xml = $this->queryAmazon($parameters);
        
        return $xml;
         
  }





  function cartModify($cartItemId, $quantity)
  /*********************************************************************
   * Modify the quantity of an an item in the cart by a cartItemID and a $quantity
   *********************************************************************/
  {

        $parameters = array("Operation"    => "CartModify",
                            "AssociateTag" => $this->associateTag,
                            "CartId" => $_COOKIE['cartID'],
                            "HMAC" => $_COOKIE["HMAC"],
                            "Item.1.CartItemId" => $cartItemId,
                            "Item.1.Quantity" => $quantity);


        $xml = $this->queryAmazon($parameters);

        return $xml;

  }
  




  function cartResponseToJSON($xml)
  /***************************************************
   * Parses cart responses into a json object
   *
   ***************************************************/
  {
      $purchaseURL = $xml->Cart->PurchaseURL;
      $cart['purchaseURL'] = $purchaseURL;
      $cart['subtotal'] = $xml->Cart->SubTotal->FormattedPrice;

      /*
      foreach($xml->Cart->CartItems->CartItem as $item){
        
          $cart[] = array("PurschaseURL" => $xml->Cart->PurchaseURL,
                        "Subtotal" => $xml->Cart->SubTotal->FormattedPrice,
                        "CartItemId" => $item->CartItemId,
                        "Title" => $item->Title,
                        "Price" => $item->Price->FormattedPrice,
                        "Quantity" => $item->Quantity,
                        "ItemTotal" => $item->ItemTotal->FormattedPrice,
                        "Link" => "http://www.amazon.com/dp/$item->ASIN/?tag=$this->associateTag");
          
      }
      */
      
      echo json_encode(new SimpleXMLElement($xml->asXML(), LIBXML_NOCDATA));
  }
   
    

 
  
 
 
}
 
?>
