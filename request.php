<?php
error_reporting(E_ALL);

include 'classes.php';
$apiKey = 'xxx';
$parameters = array(
				'country'=>'DE',
				'currency'=>'EUR',
				'locale'=>'en-GB',
				'originPlace'=>'MUC',
				'destinationPlace'=>'KEF',
				'apiKey'=>'xxx',
				);
$delay = new DateInterval("PT6H"); // 6 hours
$start = new DateTime('2018-09-01');
$end = new DateTime('2018-10-01');
$days = new DatePeriod($start,new DateInterval('P1D'),$end); // look for each day between start and end
$durations = array(7,8,9,10);

/*
 * Idea : for each actual day, request every `DELAY` hours the following data :
 *				- all flights from `originPlace` to `destinationPlace` 
 *				- with inbound and outbound legs dates between `START` and `END`
 *				- foreach of the possible durations
 *        save this data into a new folder for each request
 *        Three outputs:
 *				- one resume page with for each day the least expensive flight found
 *				- one tracking page with for each flight the price evolution over several days
 *				- one email sent as soon as a new lowest price is reached
 *
 * Results : after one month of running, the following amount of data is expected :
 *				- 30*4 (every 6 hours) folders each containing : 
 *					- 30*4 files (window of 30 days and 4 durations) with all individual flights;
 *			Each file weigths approximately 60kbs generating a total size of 864Mo
 */

$globalMetadataFile = './metadata2.txt';
$metadata = new Metadata($globalMetadataFile);

$currentTime = new DateTime();
$lastRequestTime = $metadata->getLastRequestTime();

if($lastRequestTime->add($delay) < $currentTime){
	$repertoire = $currentTime->getTimestamp();
	// create folder
	if(!mkdir('./'.$repertoire,0777)){
		echo 'Failed to create repertoire: '.$repertoire."\n";
	}
	// initiate loop
	foreach($days as $day){
		foreach ($durations as $length) {
			$interval = new DateInterval('P'.$length.'D');
			$outboundDate = $day->format('Y-m-d');
			$inboundDate = $day->add($interval)->format('Y-m-d');
			$parameters['inboundDate']=$inboundDate;
			$parameters['outboundDate']=$outboundDate;
			$search = new Search($parameters);
			$search->saveToFile($repertoire);
			usleep(100000);
		}
	}
	$metadata->setLastRequestTime();
	$metadata->save();
	echo "Finished\n";
}
else{
	// just sleep, we'll wake you up...
	echo "Nothing to do\n";
}
?>