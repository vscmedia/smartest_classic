<?php

// include 'XML/Serializer.php';

class SmartestRssOutputHelper{
    
    protected $_limit = 15;
    protected $_items = array();
    protected $_domObject;
    protected $_domRootTagElement;
    protected $_title;
    protected $_author;
    protected $_description;
    protected $_data_array = array();
    protected $_request;
    protected $_site;
    protected $_channel_image;
    
    public function __construct($data=null){
        
        if(is_array($data)){
            $this->_items = array_slice($data, 0, 20);
            $this->_request = SmartestPersistentObject::get('controller')->getCurrentRequest();
        }else{
            // do nothing
        }
    }
    
    public function getXml(){
        
        if(class_exists('DOMDocument')){
			
			$this->_domObject = new DOMDocument('1.0');
	        $this->_domObject->formatOutput = true;
			$this->_domObject->loadXML('<?xml version="1.0" encoding="UTF-8" ?'.'><!-- generator="Smartest v'.SmartestInfo::$version.'" --><rss version="2.0" />');
	    
	        $this->_domRootTagElement = $this->_domObject->getElementsByTagName('rss')->item(0);
    	    $channel = $this->_domObject->createElement("channel");
    	    $this->_domRootTagElement->appendChild($channel);
	    
    	    $title = $this->_domObject->createElement("title");
    	    $title_text = $this->_domObject->createTextNode($this->getTitle());
    	    $title->appendChild($title_text);
            
            $description = $this->_domObject->createElement("description");
    	    $description_text = $this->_domObject->createCDATASection($this->getDescription());
    	    $description->appendChild($description_text);
            
    	    $link = $this->_domObject->createElement("link");
    	    $link_text = $this->_domObject->createTextNode($this->_site->getHomepageFullUrl());
    	    $link->appendChild($link_text);
            
    	    $image = $this->_domObject->createElement("image");
    	    $image_url = $this->_domObject->createElement("url");
            $image_title = $this->_domObject->createElement("title");
            $image_link = $this->_domObject->createElement("link");
            
            if($this->_channel_image instanceof SmartestImage){
                
                $image_url_text = $this->_domObject->createTextNode($this->_site->getTopLevelUrl().substr($this->_channel_image->getWebPath(), 1));
                $image_url->appendChild($image_url_text);
                $image->appendChild($image_url);
                
                $image_title_text = $this->_domObject->createTextNode($this->getTitle());
                $image_title->appendChild($image_title_text);
                $image->appendChild($image_title);
                
                $image_link_text = $this->_domObject->createTextNode($this->_site->getHomepageFullUrl());
                $image_link->appendChild($image_link_text);
                $image->appendChild($image_link);
                
            }
            
            $generator = $this->_domObject->createElement("generator");
    	    $generator_text = $this->_domObject->createTextNode('Smartest v'.SmartestInfo::$version);
    	    $generator->appendChild($generator_text);
            
            $ttl = $this->_domObject->createElement("ttl");
            $ttl_value = $this->_domObject->createTextNode('15');
            $ttl->appendChild($ttl_value);
	    
    	    $channel->appendChild($link);
    	    $channel->appendChild($title);
            $channel->appendChild($description);
            $channel->appendChild($ttl);
    	    $channel->appendChild($generator);
            if($this->_channel_image instanceof SmartestImage){
                $channel->appendChild($image);
            }
	    
    	    $this->addItems();
	    
    	    return $this->_domObject->saveXml();
	    
        }
        
    }
    
    public function getITunesXml(){
        
        if(class_exists('DOMDocument')){
			
			$this->_domObject = new DOMDocument('1.0');
	        $this->_domObject->formatOutput = true;
			$this->_domObject->loadXML('<?xml version="1.0" encoding="UTF-8" ?'.'><!-- generator="Smartest v'.SmartestInfo::$version.'" --><rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:content="http://purl.org/rss/1.0/modules/content/" />');
	    
	        $this->_domRootTagElement = $this->_domObject->getElementsByTagName('rss')->item(0);
    	    $channel = $this->_domObject->createElement("channel");
    	    $this->_domRootTagElement->appendChild($channel);
            
            // Podcast data:
            $TITLE       = "PODCAST TITLE";
            $AUTHOR      = "AUTHOR NAME";
            $TYPE        = "EPISODIC/SERIAL";
            $LANGUAGE    = "LANG";
            $OWNER_NAME  = "OWNER NAME";
            $OWNER_EMAIL = "OWNER EMAIL";
            $IMAGE_URL   = "IMAGE URL";
            $MAIN_CAT    = "MAIN CAT";
            $SUB_CAT     = "SUB CAT";
            $DESCRIPTION = "DESCRIPTION";
	    
    	    $author = $this->_domObject->createElement("author");
    	    $author_text = $this->_domObject->createTextNode($AUTHOR);
    	    $author->appendChild($author_text);
            
            $itunes_author = $this->_domObject->createElement("itunes:author");
	        $itunes_author_text = $this->_domObject->createTextNode($AUTHOR);
	        $itunes_author->appendChild($itunes_author_text);
            
            $itunes_type = $this->_domObject->createElement("itunes:type");
	        $itunes_type_text = $this->_domObject->createTextNode($TYPE);
	        $itunes_type->appendChild($itunes_type_text);
            
            $language = $this->_domObject->createElement("language");
	        $language_text = $this->_domObject->createTextNode($LANGUAGE);
	        $language->appendChild($language_text);
            
            $itunes_owner = $this->_domObject->createElement("itunes:owner");
            
            $itunes_owner_name = $this->_domObject->createElement("itunes:name");
            $itunes_owner_name_text = $this->_domObject->createTextNode($OWNER_NAME);
            $itunes_owner_name->appendChild($itunes_owner_name_text);
            
            $itunes_owner_email = $this->_domObject->createElement("itunes:email");
            $itunes_owner_email_text = $this->_domObject->createTextNode($OWNER_EMAIL);
            $itunes_owner_email->appendChild($itunes_owner_email_text);
            
	        $itunes_owner->appendChild($itunes_owner_name);
            $itunes_owner->appendChild($itunes_owner_email);
            
            $itunes_image = $this->_domObject->createElement("itunes:image");
            $itunes_image_url = $this->_domObject->createTextNode($IMAGE_URL);
            $itunes_image->appendChild($itunes_image_url);
            
            $itunes_main_cat = $this->_domObject->createElement("itunes:category");
            $itunes_main_cat_text = $this->_domObject->createAttribute("text");
            $itunes_main_cat_text->value = $MAIN_CAT;
            
            $itunes_sub_cat = $this->_domObject->createElement("itunes:category");
            $itunes_sub_cat_text = $this->_domObject->createAttribute("text");
            $itunes_sub_cat_text->value = $SUB_CAT;
            $itunes_sub_cat->appendChild($itunes_sub_cat_text);
            
            $itunes_main_cat->appendChild($itunes_main_cat_text);
            $itunes_main_cat->appendChild($itunes_sub_cat);
            
            $description = $this->_domObject->createElement("description");
    	    $description_text = $this->_domObject->createTextNode($DESCRIPTION);
    	    $description->appendChild($description_text);
	    
    	    $title = $this->_domObject->createElement("title");
    	    $title_text = $this->_domObject->createTextNode($TITLE);
    	    $title->appendChild($title_text);
	    
    	    $generator = $this->_domObject->createElement("generator");
    	    $generator_text = $this->_domObject->createTextNode('Smartest v'.SmartestInfo::$version);
    	    $generator->appendChild($generator_text);
            
            $ttl = $this->_domObject->createElement("ttl");
            $ttl_value = $this->_domObject->createTextNode('15');
            $ttl->appendChild($ttl_value);
	    
    	    $channel->appendChild($title);
            $channel->appendChild($itunes_type);
    	    $channel->appendChild($description);
            $channel->appendChild($language);
            $channel->appendChild($author);
            $channel->appendChild($itunes_author);
            $channel->appendChild($itunes_owner);
            $channel->appendChild($itunes_image);
            $channel->appendChild($itunes_main_cat);
    	    $channel->appendChild($ttl);
            $channel->appendChild($generator);
	    
    	    $this->addITunesItems();
	    
    	    return $this->_domObject->saveXml();
	    
        }
        
    }
    
    public function getKml(){
        
        if(class_exists('DOMDocument')){
            
			$this->_domObject = new DOMDocument('1.0');
	        $this->_domObject->formatOutput = true;
			$this->_domObject->loadXML('<?xml version="1.0" encoding="UTF-8" ?'.'><!-- generator="Smartest v'.SmartestInfo::$version.'" --><kml xmlns="http://www.opengis.net/kml/2.2" />');
	    
	        $this->_domRootTagElement = $this->_domObject->getElementsByTagName('kml')->item(0);
            
    	    $document = $this->_domObject->createElement("Document");
    	    $this->_domRootTagElement->appendChild($document);
            
        }
        
    }
    
    public function send(){
        header("Cache-Control: public, must-revalidate\r\n");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT\r\n");
        header('Last-Modified: '.gmdate( 'D, d M Y H:i:s' ). ' GMT'."\r\n");
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: application/rss+xml; charset=utf-8');
        // header('Content-Type: text/xml; charset=utf-8');
        // header('Content-Type: text/plain; charset=utf-8');
        // $this->getXml();
        echo $this->getXml();
        exit;
    }
    
    public function sendAtom(){
        header("Cache-Control: public, must-revalidate\r\n");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT\r\n");
        header('Last-Modified: '.gmdate( 'D, d M Y H:i:s' ). ' GMT'."\r\n");
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: text/plain; charset=utf-8');
        // $this->getXml();
        echo $this->getXml();
        exit;
    }
    
    public function sendITunes(){
        header("Cache-Control: public, must-revalidate\r\n");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT\r\n");
        header('Last-Modified: '.gmdate( 'D, d M Y H:i:s' ). ' GMT'."\r\n");
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: text/xml; charset=utf-8');
        // $this->getXml();
        echo $this->getITunesXml();
        exit;
    }
    
    public function sendKml(){
        header("Cache-Control: public, must-revalidate\r\n");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT\r\n");
        header('Last-Modified: '.gmdate( 'D, d M Y H:i:s' ). ' GMT'."\r\n");
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: text/xml; charset=utf-8');
        echo $this->getKml();
        exit;
    }
    
    public function setLimit($limit){
        if(is_numeric($limit)){
            $this->_limit = ceil($limit);
        }
    }
    
    public function getSite(){
        return $this->_site;
    }
    
    public function setSite(SmartestSite $s){
        $this->_site = $s;
    }
    
    public function getLink(){
        return $this->_link;
    }
    
    public function setLink(SmartestExternalUrl $link){
        $this->_link = $link;
    }
    
    public function getTitle(){
        return $this->_title;
    }
    
    public function setTitle($t){
        $this->_title = $t;
    }
    
    public function getAuthor(){
        return $this->_author;
    }
    
    public function setAuthor($t){
        $this->_author = $t;
    }
    
    public function getDescription(){
        return $this->_description;
    }
    
    public function setDescription($t){
        $this->_description = $t;
    }
    
    public function getImage(){
        return $this->_channel_image;
    }
    
    public function setImage($image){
        if($image instanceof SmartestImage){
            $this->_channel_image = $image;
        }elseif($image instanceof SmartestAsset && $image->isBinaryImage()){
            $this->_channel_image = $image->getImage();
        }else{
            // Supplied value for $image was not compatible
        }
    }
    
    public function addItems(){
        
        // var_dump($this->_items);
        
        $channel = $this->_domObject->getElementsByTagName('channel')->item(0);
        
        foreach($this->_items as $object){
            
            $item = $this->_domObject->createElement("item");
	        
	        $title = $this->_domObject->createElement("title");
	        $title_text = $this->_domObject->createTextNode($object->getTitle());
	        $title->appendChild($title_text);
	        
	        $description = $this->_domObject->createElement("description");
	        $description_text = $this->_domObject->createCDATASection($object->getDescription());
	        $description->appendChild($description_text);
            
            $pubDate = $this->_domObject->createElement("pubDate");
            $date = $object->getDate();
            if(!$date instanceof SmartestDateTime){
                if(is_numeric($date)){
                    $date = new SmartestDateTime($date);
                }else{
                    $date = new SmartestDateTime(0);
                }
            }
            $pubDate_text = $this->_domObject->createTextNode(date(DATE_RSS, $date->getUnixFormat()));
            $pubDate->appendChild($pubDate_text);
	        
	        $link = $this->_domObject->createElement("link");
            $guid = $this->_domObject->createElement("guid");
            
            $url = $this->_request->getUrlProtocol().$_SERVER['HTTP_HOST'].$object->getUrl();
            
	        $link_text = $this->_domObject->createTextNode($url);
            $guid_text = $this->_domObject->createTextNode($url);
            
	        $link->appendChild($link_text);
            $guid->appendChild($guid_text);
	        
	        $item->appendChild($title);
    	    $item->appendChild($description);
    	    $item->appendChild($link);
            $item->appendChild($guid);
    	    $item->appendChild($pubDate);
	        
	        $channel->appendChild($item);
	    
        }
        
    }
    
    public function addITunesItems(){
        
        $channel = $this->_domObject->getElementsByTagName('channel')->item(0);
        
        foreach($this->_items as $object){
            
            // <itunes:episodeType>full</itunes:episodeType>
            $episode_type = $this->_domObject->createElement("itunes:episodeType");
            $episode_type_text = $this->_domObject->createTextNode("EPISODE TYPE");
            $episode_type->appendChild($episode_type_text);
            
            // <itunes:episode>2</itunes:episode>
            $episode = $this->_domObject->createElement("itunes:episode");
            $episode_num = $this->_domObject->createTextNode("EPISODE NUM");
            $episode->appendChild($episode_num);
            
            // <itunes:season>2</itunes:season>
            $season = $this->_domObject->createElement("itunes:season");
            $season_num = $this->_domObject->createTextNode("SEASON NUM");
            $season->appendChild($season_num);
            
            $item = $this->_domObject->createElement("item");
	        
	        $title = $this->_domObject->createElement("title");
            // $media_title = $this->_domObject->createElement("media:title");
	        $title_text = $this->_domObject->createTextNode($object->getTitle());
            // $mtitle_text = $this->_domObject->createTextNode($object->getTitle());
	        $title->appendChild($title_text);
            // $media_title->appendChild($mtitle_text);
	        
	        $description = $this->_domObject->createElement("description");
	        $description_text = $this->_domObject->createTextNode($object->getDescription());
	        $description->appendChild($description_text);
            
            $image = $this->_domObject->createElement("itunes:image");
	        $image_url = $this->_domObject->createTextNode('EPISODE IMG');
	        $image->appendChild($image_url);
            
            $pubDate = $this->_domObject->createElement("pubDate");
            $date = $object->getDate();
            if(!$date instanceof SmartestDateTime){
                if(is_numeric($date)){
                    $date = new SmartestDateTime($date);
                }else{
                    $date = new SmartestDateTime(0);
                }
            }
            
            $pubDate_text = $this->_domObject->createTextNode(date(DATE_RSS, $date->getUnixFormat()));
            $pubDate->appendChild($pubDate_text);
	        
	        $guid = $this->_domObject->createElement("guid");
            
            $url = $this->_request->getUrlProtocol().$_SERVER['HTTP_HOST'].$object->getUrl();
            
	        $guid_text = $this->_domObject->createTextNode(md5($object->getWebId()));
            
	        $guid->appendChild($guid_text);
            
            // Add media
            $enclosure = $this->_domObject->createElement("enclosure");
            $enclosure_length = $this->_domObject->createAttribute("length");
            $enclosure_length->value = "0";
            $enclosure_type   = $this->_domObject->createAttribute("type");
            $enclosure_type->value = "MIME TYPE";
            $enclosure_url   = $this->_domObject->createAttribute("url");
            $enclosure_url->value = "MEDIA URL";
            $enclosure->appendChild($enclosure_length);
            $enclosure->appendChild($enclosure_type);
            $enclosure->appendChild($enclosure_url);
            
	        // <itunes:duration>2434</itunes:duration>
            // <itunes:explicit>false</itunes:explicit> // 
            
            $item->appendChild($title);
            // $item->appendChild($media_title);
    	    $item->appendChild($description);
            $item->appendChild($episode_type);
            $item->appendChild($episode);
            $item->appendChild($season);
    	    $item->appendChild($guid);
    	    $item->appendChild($pubDate);
            $item->appendChild($image);
            $item->appendChild($enclosure);
	        
            $channel->appendChild($item);
	    
        }
        
    }
    
    public function addMapData(){
        
        $document = $this->_domObject->getElementsByTagName('Document')->item(0);
        
        foreach($this->_items as $object){
            
            $placemark = $this->_domObject->createElement("Placemark");
            
            
            
            $document->appendChild($placemark);
            
        }
        
    }
}