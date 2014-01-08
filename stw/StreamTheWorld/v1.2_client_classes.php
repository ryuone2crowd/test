<?php

//======================================
// Show source?
//======================================
if(isset($_GET['showsource']))
{
    $str = highlight_file(__FILE__, true) ;
    echo($str) ;
    exit() ;  
}


//================================
// Create a unique identifier, for testing purpose
//================================
if(!function_exists('uuid'))
{
    function uuid()
    {
        return md5(uniqid(mt_rand(), true));
    }
}


//======================================
// The main class to play with the webservices
//======================================
class StwConverterWebServices
{
    var $key = '' ;
    var $url = '' ;
    var $requests = array() ;
    var $client = null ;
   
    function __construct($wsURL, $wsKey)
    {
        $this->url = $wsURL ;
        $this->key = $wsKey ;
    }
   
    //======================================
    // add a request
    //======================================
    function addRequest($request)
    {
        $this->requests[] = $request ;
    }
    
    //======================================
    // get the formated requests
    //======================================
    function getFormatedRequests()
    {
        return $this->formatRequests() ;
    }
    
    //======================================
    // get client to talk to the webservices
    //======================================
    private function getClient()
    {
        if($this->client === null)
        {
            $clientTmp = new nusoap_client($this->url, true) ;
            if($err = $clientTmp->getError())
            {
                throw new Exception('unable to connect to ' . $this->url . ' : ' . $err) ;
            }
            
            $clientTmp = $clientTmp->getProxy();
            if($err = $clientTmp->getError())
            {
                throw new Exception('unable to get proxy : ' . $err) ;
            }
            
            $this->client = $clientTmp ;  
        }
        
        return $this->client ; 
    }
    
    //======================================
    // Launch the requests
    //======================================
    function launchRequests()
    {
        $returnIds = array() ;
        
        if(count($this->requests) == 0)
        {
            return $returnIds ;
        }
          
        try
        {
            $client = $this->getClient() ;
        }
        catch(Exception $ex)
        {
            throw $ex ;
        }
        
        //======================================
        // Formate the requests for the webservices
        //======================================
        $formatedRequests = $this->formatRequests() ;
        //======================================
        // call the ws_convert() function!
        //======================================
        $allResultInfo = $client->ws_convert($this->key, $formatedRequests) ;

        //======================================
        // check for potential global errors
        //======================================
        if($err = $client->getError())
        {
            throw new Exception($err) ;
        }
        
        foreach($allResultInfo as $onereturnInfo)
        {
            $oneIdPair = new IdPair() ;
            //======================================
            // customID will always be present, even if an error occured
            //======================================
            $oneIdPair->customID = $onereturnInfo['customID'] ;
            
            //======================================
            // check for error
            //======================================
            $error = $onereturnInfo['error'] ;
            if(!empty($error))
            {
                $oneIdPair->error = $error ;
            }
            else
            {
                $oneIdPair->stwID = $onereturnInfo['stwID'] ;
            }  
            
            $returnIds[] = $oneIdPair ;
        }

        return $returnIds ;
    }
    
    //======================================
    // Get all the IDs associated with the current Client
    //======================================
    function getAllIds()
    {
        try
        {
            $client = $this->getClient() ;
        }
        catch(Exception $ex)
        {
            throw $ex ;
        }
        
        //======================================
        // call the ws_getRequestIds() function
        //======================================
        $allResultInfo = $client->ws_getRequestIds($this->key) ;

        //======================================
        // check for potential global errors
        //======================================
        if($err = $client->getError())
        {
            throw new Exception($err) ;
        }
        
        $returnIds = array() ;
        foreach($allResultInfo as $onereturnInfo)
        {
            $oneIdPair = new IdPair() ;
            
            //======================================
            // customID
            //======================================
            $oneIdPair->customID = $onereturnInfo['customID'] ;
            
            //======================================
            // stwID
            //======================================
            $oneIdPair->stwID = $onereturnInfo['stwID'] ;
            
            $returnIds[] = $oneIdPair ;   
        }
        
        return $returnIds ;
    }
    
    
    //======================================
    // delete some requests
    //======================================
    function deleteRequests($stwIds)
    {
        try
        {
            $client = $this->getClient() ;
        }
        catch(Exception $ex)
        {
            throw $ex ;
        }
        
        //======================================
        // call the ws_deleteRequests() function
        //======================================
        $allResultInfo = $client->ws_deleteRequests($this->key, $stwIds) ;

        //======================================
        // check for potential global errors
        //======================================
        if($err = $client->getError())
        {
            throw new Exception($err) ;
        }
        
        $returnIds = array() ;
        foreach($allResultInfo as $onereturnInfo)
        {
            $oneDeletedRequest = new DeletedRequest() ;
            
            //======================================
            // stwID
            //======================================
            $oneDeletedRequest->stwID = $onereturnInfo['stwID'] ;
            
            //======================================
            // error?
            //======================================
            $oneDeletedRequest->error = $onereturnInfo['error'] ;
            
            $returnIds[] = $oneDeletedRequest ;   
        }
        
        return $returnIds ; 
    }
    
    //======================================
    // Return all the info about the specified requests of the clients
    //======================================
    function getRequestsInfo($stwIds)
    {
        $allRequestInfoObj = array() ;
        
        if(count($stwIds) == 0)
        {
            return $allRequestInfoObj ;
        }
        
        try
        {
            $client = $this->getClient() ;
        }
        catch(Exception $ex)
        {
            throw $ex ;
        }
        
        //======================================
        // call the ws_getRequestInfo() function
        // We use the stwIds that getIds() returned.
        //======================================
        $allReturnedInfo = $client->ws_getRequestInfo($this->key, $stwIds) ;
        
        //======================================
        // check for potential global errors
        //======================================
        if($err = $client->getError())
        {
            throw new Exception($err) ;
        }
        
        foreach($allReturnedInfo as $oneReturnedInfo)
        {
            $oneRequestInfoObj = new RequestInfo() ;
            
            $oneRequestInfoObj->customID                   = $oneReturnedInfo['customID'] ;
            $oneRequestInfoObj->stwID                      = $oneReturnedInfo['stwID'] ;
            $oneRequestInfoObj->requestDate                = $oneReturnedInfo['requestDate'] ;
            $oneRequestInfoObj->requestSource              = $oneReturnedInfo['source'] ;
            $oneRequestInfoObj->parametersUsed             = $oneReturnedInfo['requestInfo'] ;
            $oneRequestInfoObj->convertedMedias            = array() ;
            $oneRequestInfoObj->thumbnails                 = array() ;
            $oneRequestInfoObj->error                      = '' ;
            $oneRequestInfoObj->status                     = intval($oneReturnedInfo['status']) ;
            
            // error
            if($oneRequestInfoObj->status === 3)
            {
                $oneRequestInfoObj->error = $oneReturnedInfo['error'] ;
            }
            
            // more information to give?
            if($oneRequestInfoObj->status !== 0)
            {
                // converted medias?
                $convertedMedias = $oneReturnedInfo['convertedMedias'] ;
                if(count($convertedMedias) != 0)
                {
                    $allMediaObj = array() ;
                    foreach($convertedMedias as $oneMedia)
                    {
                        $oneMediaObj                = new ConvertedMediaInfo() ;
                        $oneMediaObj->customID      = $oneMedia['customID'] ;
                        $oneMediaObj->stwID         = $oneMedia['stwID'] ;
                        $oneMediaObj->destination   = $oneMedia['destination'] ;
                        $oneMediaObj->status        = intval($oneMedia['status']) ;
                        $oneMediaObj->error         = '' ;
                        $oneMediaObj->size          = '' ;
                        $oneMediaObj->sizeReadable  = '' ;
                        $oneMediaObj->duration      = '' ;
                        $oneMediaObj->timeTransfertSource           = '' ;
                        $oneMediaObj->timeTransfertConvertedFile    = '' ;
                        $oneMediaObj->timeConversion                = '' ;
                        
                        // error?
                        if($oneMediaObj->status === 3)
                        {
                            $oneMediaObj->error = $oneMedia['error'] ;
                        }
                        
                        // size and duration, if finished without error
                        if($oneMediaObj->status === 2)
                        {
                            $oneMediaObj->size                          = intval($oneMedia['size']) ;
                            $oneMediaObj->sizeReadable                  = $oneMedia['sizeReadable'] ;
                            $oneMediaObj->duration                      = intval($oneMedia['duration']) ;
                            $oneMediaObj->timeTransfertSource           = floatval($oneMedia['timeTransfertSource']) ;
                            $oneMediaObj->timeTransfertConvertedFile    = floatval($oneMedia['timeTransfertConvertedFile']) ;
                            $oneMediaObj->timeConversion                = floatval($oneMedia['timeConversion']) ;
                        }
                        
                        $allMediaObj[] = $oneMediaObj ; 
                    }
                    $oneRequestInfoObj->convertedMedias = $allMediaObj ;
                }

                
                // thumbnails?
                $thumbnails = $oneReturnedInfo['thumbnails'] ;
                if(count($thumbnails) != 0)
                {
                    $allThumbnailsObj = array() ;
                    foreach($thumbnails as $oneThumbnail)
                    {
                        $oneThumbnailObj                = new ThumbnailInfo() ;
                        $oneThumbnailObj->customID      = $oneThumbnail['customID'] ;
                        $oneThumbnailObj->stwID         = $oneThumbnail['stwID'] ;
                        $oneThumbnailObj->destination   = $oneThumbnail['destination'] ;
                        $oneThumbnailObj->status        = intval($oneThumbnail['status']) ;
                        $oneThumbnailObj->error         = '' ;
                        $oneThumbnailObj->size          = '' ;
                        $oneThumbnailObj->sizeReadable  = '' ;
                        
                        // error?
                        if($oneThumbnailObj->status === 3)
                        {
                            $oneThumbnailObj->error = $oneThumbnail['error'] ;
                        }
                        
                        // size, if finished without error
                        if($oneThumbnailObj->status === 2)
                        {
                            $oneThumbnailObj->size          = intval($oneThumbnail['size']) ;
                            $oneThumbnailObj->sizeReadable  = $oneThumbnail['sizeReadable'] ;
                        }
                        
                        $allThumbnailsObj[] = $oneThumbnailObj ; 
                    }
                    $oneRequestInfoObj->thumbnails = $allThumbnailsObj ;
                }  
            }

            $allRequestInfoObj[] = $oneRequestInfoObj ;
            
        }
    
        return $allRequestInfoObj ;
    }
    
    //======================================
    // format the request for the webservice
    //======================================
    function formatRequests()
    {
        $formatedRequests = array() ;
        foreach($this->requests as $oneRequest)
        {
            $oneFormatedRequest                     = array() ;
            $oneFormatedRequest['customID']         = $oneRequest->customId ;
            $oneFormatedRequest['inputMediaType']   = $oneRequest->inputMediaType ;
            $oneFormatedRequest['source']           = $oneRequest->inputMediaSource ;
            $oneFormatedRequest['insertionType']    = $oneRequest->insertionType ;
            
            
            // all conversion configurations
            $allConversionConfigurations = array() ;
            foreach($oneRequest->conversionConfiguration as $oneConversionConfigurationObj)
            {
                $oneConversionConfiguration = array() ;
                
                $oneConversionConfiguration['customID']                     = $oneConversionConfigurationObj->customId ;
                $oneConversionConfiguration['outputMediaType']              = $oneConversionConfigurationObj->outputMediaType ;
                $oneConversionConfiguration['destination']                  = $oneConversionConfigurationObj->destination ;
                $oneConversionConfiguration['watermarkImageSource']         = $oneConversionConfigurationObj->watermarkImageSource ;
                $oneConversionConfiguration['watermarkImagePosition']       = $oneConversionConfigurationObj->watermarkImagePosition ;
                $oneConversionConfiguration['audioBackgroundImageSource']   = $oneConversionConfigurationObj->audioBackgroundImageSource ;
                
                $allTranscodingParameters = array() ;
                foreach($oneConversionConfigurationObj->transcodingParameters as $key => $value)
                {
                    $oneTranscodingParameter            = array() ;
                    $oneTranscodingParameter['key']     = $key ;
                    $oneTranscodingParameter['value']   = $value ;
                    $allTranscodingParameters[]         = $oneTranscodingParameter ;   
                }
                $oneConversionConfiguration['transcodingParameters'] = $allTranscodingParameters ;

                $allConversionConfigurations[] = $oneConversionConfiguration ;  
            }
            $oneFormatedRequest['configs'] = $allConversionConfigurations ;
            
            // all thumbnail positions
            $allThumbnailPositions       = array() ;
            foreach($oneRequest->thumbnailPositions as $oneThumbnailPositionObj)
            {
                $oneThumbnailPosition = array() ;
                
                $oneThumbnailPosition['position'] = $oneThumbnailPositionObj->position ;
                
                $allThumbnailConfigurations = array() ;
                foreach($oneThumbnailPositionObj->thumbnailConfigurations as $oneThumbnailConfigurationObj)
                {
                    $oneThumbnailConfiguration = array() ;
                    
                    $oneThumbnailConfiguration['customID']      = $oneThumbnailConfigurationObj->customID ;
                    $oneThumbnailConfiguration['destination']   = $oneThumbnailConfigurationObj->destination ;
                    $oneThumbnailConfiguration['width']         = $oneThumbnailConfigurationObj->width ;
                    $oneThumbnailConfiguration['height']        = $oneThumbnailConfigurationObj->height ;
                    $oneThumbnailConfiguration['crop']          = $oneThumbnailConfigurationObj->crop ;
                    $oneThumbnailConfiguration['format']        = $oneThumbnailConfigurationObj->format ;
                    $oneThumbnailConfiguration['quality']       = $oneThumbnailConfigurationObj->quality ;
                    $oneThumbnailConfiguration['watermarkImageSource']      = $oneThumbnailConfigurationObj->watermarkImageSource ;
                    $oneThumbnailConfiguration['watermarkImagePosition']    = $oneThumbnailConfigurationObj->watermarkPosition ;
                    
                    $allThumbnailConfigurations[] = $oneThumbnailConfiguration ;
                }
                $oneThumbnailPosition['configs'] = $allThumbnailConfigurations ;
                
                $allThumbnailPositions[] = $oneThumbnailPosition ;
                
            }
            $oneFormatedRequest['thumbnails'] = $allThumbnailPositions ;
            
            // extra request parameters
            if(count($oneRequest->extra))
            {
                $oneFormatedRequest['extra'] = array() ;
                foreach($oneRequest->extra as $key => $value)
                {
                    $onePair = array() ;
                    $onePair['key']     = $key ;
                    $onePair['value']   = $value ;
                    $oneFormatedRequest['extra'][] = $onePair ;
                }
            }
            
            $formatedRequests[] = $oneFormatedRequest ;
        }
        
        return $formatedRequests ;
    }
}

//======================================
// info about a converted media
//======================================
class ConvertedMediaInfo
{
    var $customID       = '' ;
    var $stwID          = '' ;
    var $destination    = '' ;
    var $status         = '' ;
    var $error          = '' ;
    var $size           = '' ;
    var $sizeReadable   = '' ;
    var $duration       = '' ;
}

//======================================
// info about a generated thumbnail
//======================================
class ThumbnailInfo
{
    var $customID       = '' ;
    var $stwID          = '' ;
    var $destination    = '' ;
    var $status         = '' ;
    var $error          = '' ;
    var $size           = '' ;
    var $sizeReadable   = '' ;
}

//======================================
// info about a Request, returned by the webservices
//======================================
class RequestInfo
{
    var $customID           = '' ;
    var $stwID              = '' ;
    var $error              = '' ;
    var $requestDate        = '' ;
    var $requestSource      = '' ;
    var $status             = '' ;
    var $parametersUsed     = '' ;
    var $thumbnails         = array() ;
    var $convertedMedias    = array() ;
}

//======================================
// to store a pair of customID/stwID
//======================================
class IdPair
{
    var $customID   = '' ;
    var $stwID      = '' ;
    var $error      = '' ;
}

//======================================
// info about a deleted Request
//======================================
class DeletedRequest
{
    var $stwID              = '' ;
    var $error              = '' ;
}




//======================================
// to built a Request that will be passed to the webservices
//======================================
class Request
{
    var $customId                   = '' ;
    var $inputMediaType             = '' ;
    var $inputMediaSource           = '' ;
    var $conversionConfiguration    = array() ; 
    var $thumbnailPositions         = array() ; 
    var $insertionType              = '' ; 
    var $extra                      = array() ;
    
    
    function __construct($customId, $inputMediaType, $inputMediaSource, $conversionConfiguration = array(), $thumbnailPositions = array(), $insertionType = 'error')
    {
        $this->customId                     = $customId ;
        $this->inputMediaType               = $inputMediaType ;
        $this->inputMediaSource             = $inputMediaSource ;
        $this->conversionConfiguration      = $conversionConfiguration ;
        $this->thumbnailPositions           = $thumbnailPositions ;
        $this->insertionType                = $insertionType ; 
    }
   
    function addConversionConfiguration($conversionConfiguration)
    {
        $this->conversionConfiguration[] = $conversionConfiguration ;
    }
    
    function addThumbnailPositions($thumbnailPosition)
    {
        $this->thumbnailPositions[] = $thumbnailPosition ;
    }
    
    function addExtra($key, $value)
    {
        $this->extra[$key] = $value ;
    }
    
}

//======================================
// to build a comversion configuration that will be added to a Request
//======================================
class ConversionConfiguration
{
    var $customId                       = '' ;
    var $outputMediaType                = '' ;
    var $destination                    = '' ;
    var $watermarkImageSource           = '' ;
    var $watermarkImagePosition         = 'bottom right' ;
    var $audioBackgroundImageSource     = '' ;
    var $transcodingParameters          = array() ;
   
    function __construct($customId, 
                         $outputMediaType, 
                         $destination, 
                         $watermarkImageSource = '', 
                         $watermarkImagePosition = '', 
                         $audioBackgroundImageSource = '',
                         $transcodingParameters = array()
                         )
    {
        $this->customId                      = $customId ;
        $this->outputMediaType               = $outputMediaType ;
        $this->destination                   = $destination ;
        $this->watermarkImageSource          = $watermarkImageSource ;
        $this->watermarkImagePosition        = $watermarkImagePosition ;
        $this->audioBackgroundImageSource    = $audioBackgroundImageSource ;  
        $this->transcodingParameters         = $transcodingParameters ;  
    }
   
    function setTranscodingParameters(array $transcodingParameters)
    {
        $this->transcodingParameters = $transcodingParameters ;
    }
}

//======================================
// to build a thumbnail position that will be added to a Request
//======================================
class ThumbnailPosition
{
    var $position               = '' ;
    var $thumbnailConfigurations = array() ;

    function __construct($position = '', $thumbnailConfigurations = array())
    {
        $this->position                 = $position ;
        $this->thumbnailConfigurations   = $thumbnailConfigurations ;
    }
    
    function addThumbnailConfiguration($thumbnailConfiguration)
    {
        $this->thumbnailConfigurations[] = $thumbnailConfiguration ;  
    }
    
    
}

//======================================
// a thumbnail configuration that will be associated to a thumbnail position
//======================================
class ThumbnailConfiguration
{
    var $customID                   = '' ;
    var $destination                = '' ;
    var $width                      = '' ;
    var $height                     = '' ;
    var $crop                       = '' ;
    var $format                     = '' ;
    var $quality                    = '' ;
    var $watermarkImageSource       = '' ;
    var $watermarkPosition          = '' ;

    function __construct($customID, $destination, $width, $height, $crop, $format, $quality, $watermarkImageSource = '', $watermarkPosition = '')
    {
        $this->customID                 = $customID ;
        $this->destination              = $destination ;
        $this->width                    = $width ;
        $this->height                   = $height ;
        $this->crop                     = $crop ;
        $this->format                   = $format ;
        $this->quality                  = $quality ;
        $this->watermarkImageSource     = $watermarkImageSource ;
        $this->watermarkPosition        = $watermarkPosition ;
        
    }
}

?>