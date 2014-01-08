<?php

require_once('StreamTheWorld/nusoap.php');
require_once('StreamTheWorld/v1.2_client_classes.php');

// Webservice key
defined("WEBSERVICE_URL")
    || define("WEBSERVICE_URL", "http://webservices.streamtheworld.com/webservices/converter/v1.2/v1.2.php?wsdl");

// Webservice key
defined("WEBSERVICE_KEY")
    || define("WEBSERVICE_KEY", "one2crowd1003231411");

// Stream the world url
defined("STW_URL")
    || define("STW_URL", "http://one2crowd.media.streamtheworld.com");

// Stream the world ftp url
defined("STW_FTP_URL")
    || define("STW_FTP_URL", "ftp.one2crowd.media.streamtheworld.com");

// Stream the world ftp username
defined("STW_FTP_USERNAME")
    || define("STW_FTP_USERNAME", "one2crowd");

// Stream the world ftp password
defined("STW_FTP_PASSWORD")
    || define("STW_FTP_PASSWORD", "v1L6jzS");

// Stream the world ftp root directory
defined("STW_FTP_ROOT_DIR")
    || define("STW_FTP_ROOT_DIR", "public_html");

// Stream the world ftp source directory
defined("STW_SRC_DIR")
    || define("STW_SRC_DIR", "uploads");

// Stream the world ftp destination directory
defined("STW_DES_DIR")
    || define("STW_DES_DIR", "destinations");

echo("<pre>");

$webservices = new StwConverterWebServices(WEBSERVICE_URL, WEBSERVICE_KEY);

/***************** DELETE *****************/
echo("<b>Before Delete : </b><br />");
try {
    $yourIDs = $webservices->getAllIds();
}
catch(Exception $ex) {
    echo "An error occured while getting all your IDs: " . $ex->getMessage();
    exit();
}
foreach($yourIDs as $oneIDPair) {
    $stwIds[] = $oneIDPair->stwID;
    echo 'customID: '    . $oneIDPair->customID . '<br />';
    echo 'stwID: '       . $oneIDPair->stwID    . '<br />';
	echo '---------------------------<br />';
}
$webservices->deleteRequests(array('7222591'));
echo '********************************<br />';
echo("<b>After Delete : </b><br />");
try {
    $yourIDs = $webservices->getAllIds();
}
catch(Exception $ex) {
    echo "An error occured while getting all your IDs: " . $ex->getMessage();
    exit();
}
foreach($yourIDs as $oneIDPair) {
    $stwIds[] = $oneIDPair->stwID;
    echo 'customID: '    . $oneIDPair->customID . '<br />';
    echo 'stwID: '       . $oneIDPair->stwID    . '<br />';
	echo '---------------------------<br />';
}
exit();
/***************** DELETE *****************/

$customId = time();
$customConvertId = "convert-".$customId;

$request = new Request(
    $customId,
    'Video',
	STW_URL."/".STW_SRC_DIR."/2221.flv",
    //STW_URL."/".STW_SRC_DIR."/112.flv",
    array(),
    array(),
    'existing'
);

$conversionConfig = new ConversionConfiguration(
    $customConvertId,
    'Video',
    'ftp://'.STW_FTP_USERNAME.':'.STW_FTP_PASSWORD.'@'.STW_FTP_URL.'/'.STW_FTP_ROOT_DIR.'/'.STW_DES_DIR.'/source/a.flv',
    '',
    'bottom right',
    ''
);

$transcodingParameters = array(
    'video_resolution'  => '240X180',
    'video_bitrate'     => 400,
    'video_framerate'   => 24,
    'audio_bitrate'     => 128,
    'audio_samplerate'  => 44100,
    'audio_stereo'      => 2,
    'output_type'       => "h263"
);

//$conversionConfig->setTranscodingParameters($transcodingParameters);

$request->addConversionConfiguration($conversionConfig);

$thumbnailPosition1 = new ThumbnailPosition("10%");

$thumbnailConfiguration1 = new ThumbnailConfiguration(
    $customId,
    'ftp://'.STW_FTP_USERNAME.':'.STW_FTP_PASSWORD.'@'.STW_FTP_URL.'/'.STW_FTP_ROOT_DIR.'/'.STW_DES_DIR.'/img/a.gif',
    200,
    200,
    true,
    'gif',
    75,
    '',
    'bottom right'
);

$thumbnailPosition1->addThumbnailConfiguration($thumbnailConfiguration1);

$request->addThumbnailPositions($thumbnailPosition1);

$webservices->addRequest($request);

print_r($webservices);//exit();

try {
    $resultingIDs = $webservices->launchRequests();
    $stwIds = array();
    foreach ($resultingIDs as $result) {
        $stwIds[] = $result->stwID;
    }

    $requestInfos = $webservices->getRequestsInfo($stwIds);
} catch (Exception $ex) {
	exit("Error");
}

$requestInfos = $webservices->getRequestsInfo($stwIds);
foreach ($requestInfos as $oneRequestInfo) {

	print_r($oneRequestInfo);continue;
	
    /*
     * Status
     * 0 = Not start yet
     * 1 = Processing
     * 2 = Finish
     * 3 = Error
     */

    $thumbs = array();
    $thumbnails = $oneRequestInfo->thumbnails;
    foreach ($thumbnails as $oneThumbnail) {
        $thumbnailStatus = $oneThumbnail->status;
        if ($thumbnailStatus === 2) {
            $destination = $oneThumbnail->destination;
            $thumbs[] = substr($destination, strpos($destination, STW_DIR)+strlen(STW_DES_DIR."/img")+1);
        }
    }
    $thumbnails = implode(":@:", $thumbs);

    if ($oneRequestInfo->status === 2) {
        $status = "Finish";
    } else if ($oneRequestInfo->status === 3) {
        $status = "Error";
    }

    $convertedMedias = $oneRequestInfo->convertedMedias;
    if (count($convertedMedias) != 0) {
        foreach($convertedMedias as $oneConvertedMedia) {
            $destination = $oneConvertedMedia->destination;
            $destination = substr($destination, strpos($destination, STW_DIR)+strlen(STW_DES_DIR."/source")+1);
            break;
        }
    }
}

echo("</pre>");

?>
