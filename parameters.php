<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);

define('FORECAST_LENGTH', 12); // in weeks
$LENGTH_POSSIBILITIES = array(2, 3, 9); // in days
define('DIRECT_REQUIRED', TRUE); // only direct flights, no connection
$BUDGETS = array(2=>150, 3=>150, 9=>200, 16=>250); // max amount in euros for each duration
define('MIN_TOTALS', 999999999999); // for stats

function retrieveMetadata($filename){
	$line = '';

	$f = fopen($filename, 'a+');
	$cursor = -1;

	fseek($f, $cursor, SEEK_END);
	$char = fgetc($f);

	/**
	 * Trim trailing newline chars of the file
	 */
	while ($char === "\n" || $char === "\r") {
	    fseek($f, $cursor--, SEEK_END);
	    $char = fgetc($f);
	}

	/**
	 * Read until the start of file or first newline char
	 */
	while ($char !== false && $char !== "\n" && $char !== "\r") {
	    /**
	     * Prepend the new char
	     */
	    $line = $char . $line;
	    fseek($f, $cursor--, SEEK_END);
	    $char = fgetc($f);
	}
	return(explode(';', $line));
}
$parameters = array(
				'country'=>'DE',
				'currency'=>'EUR',
				'locale'=>'en-GB',
				'originPlace'=>'MUC',
				'destinationPlace'=>'Everywhere',
				'apiKey'=>'xxx',
				);
$baseUrl = "http://partners.api.skyscanner.net/apiservices/browsequotes/v1.0/".$parameters['country']."/".$parameters['currency']."/".$parameters['locale']."/".$parameters['originPlace']."/".$parameters['destinationPlace'];


$endUrl = "?apiKey=".$parameters['apiKey'];
$forecastLength = FORECAST_LENGTH; // in weeks
$potentialOutboundDates = array();
$nextFriday = new DateTime();
$nextFriday->modify('next friday');
$lengthPossibilites = $LENGTH_POSSIBILITIES; // either just a week-end, a full week or two weeks.
$directRequired = DIRECT_REQUIRED;
$potentialOutboundDates[0] = $nextFriday;
// generate all dates that will be then used as inbound and outbound dates
for($i = 0;$i < $forecastLength-1; $i++){
	$outboundDate = clone $potentialOutboundDates[$i];
	$interval = new DateInterval('P1W'); // every week
	$outboundDate->add($interval);
	$potentialOutboundDates[$i+1] = $outboundDate;
}
// var_dump($potentialOutboundDates);
$quotesSelected = array();
// max price for each duration
$budgets = $BUDGETS; // everything below this
$resume = array();
// for each date and duration we look into all available trips
foreach ($potentialOutboundDates as $outboundDate) {
	// loop on each duration
	foreach ($lengthPossibilites as $length) {
		$minTotals = $minSelected = MIN_TOTALS;
		$minQuote = $minSelectedQuote = null;
		$inboundDate = clone $outboundDate;
		$str = 'P'.$length.'D';
		$interval = new DateInterval($str);
		$inboundDate->add($interval);

		$filename = "./metadata.txt";
		$metadata = retrieveMetadata($filename);
		$lastRetrieved = $metadata[1];
		$now = time();
		if($now - $lastRetrieved < 24*3600){
			$targetUrl = "./".$outboundDate->format('Y-m-d')."-".$inboundDate->format('Y-m-d').'.txt';
			if(file_exists($targetUrl)){
				$handle = fopen($targetUrl, 'c+');
				$file = fread($handle, filesize($targetUrl));
				fclose($handle);
			}
			else{
				$targetUrl = $baseUrl."/".$outboundDate->format('Y-m-d')."/".$inboundDate->format('Y-m-d').$endUrl;
				$saveUrl = './'.$outboundDate->format('Y-m-d')."-".$inboundDate->format('Y-m-d').'.txt';
				$file = file_get_contents($targetUrl);
				file_put_contents($saveUrl, $file);
				$fp = fopen('metadata.txt', 'w+');
				fwrite($fp, $targetUrl.';'.$now."\n");
				fclose($fp);
			}
		}
		else{
			$targetUrl = $baseUrl."/".$outboundDate->format('Y-m-d')."/".$inboundDate->format('Y-m-d').$endUrl;
			$saveUrl = './'.$outboundDate->format('Y-m-d')."-".$inboundDate->format('Y-m-d').'.txt';
			$file = file_get_contents($targetUrl);
			file_put_contents($saveUrl, $file);
			$fp = fopen('metadata.txt', 'w+');
			fwrite($fp, $targetUrl.';'.$now."\n");
			fclose($fp);
		}
		
		$map = json_decode($file);
		// var_dump($map);
		$potentialQuotes = $map->Quotes;
		$mapPlaces = $map->Places;
		$mapCarriers = $map->Carriers;
		$mapCurrencies = $map->Currencies;
		$resume[$outboundDate->format('Y-m-d')][$length]['potentialQuotes'] = count($potentialQuotes);
		$tempQuotes = array();
		foreach ($potentialQuotes as $potentialQuote) {
			// select minQuote
			if($potentialQuote->MinPrice <= $minTotals){
				$minQuote = $potentialQuote;
				$minTotals = $potentialQuote->MinPrice;
			}
			if( ($directRequired && $potentialQuote->Direct) || (!$directRequired) ){
				if($potentialQuote->MinPrice <= $budgets[$length]){
					// select minQuote
					if($potentialQuote->MinPrice <= $minSelected){
						$minSelectedQuote = $potentialQuote;
						$minSelected = $potentialQuote->MinPrice;
					}
					// search for destinationName
					foreach ($mapPlaces as $place) {
						if($place->PlaceId == $potentialQuote->OutboundLeg->DestinationId){
							$potentialQuote->destinationName = $place->Name;
							$potentialQuote->destinationType = $place->Type;
						}
					}
					// search for carriers
					foreach ($mapCarriers as $carrier) {
						if($carrier->CarrierId == $potentialQuote->OutboundLeg->CarrierIds[0]){
							$potentialQuote->outboundCarrier = $carrier->Name;
						}
						if($carrier->CarrierId == $potentialQuote->InboundLeg->CarrierIds[0]){
							$potentialQuote->inboundCarrier = $carrier->Name;
						}
					}
					$tempQuotes[] = $potentialQuote;
				}
			}
		}
		$quotesSelected[$outboundDate->format('Y-m-d')][$length] = $tempQuotes;
		$tempArray = array('total'=>count($potentialQuotes), 'nbSelected'=>count($tempQuotes), 'minQuote'=>$minQuote, 'minSelected'=>$minSelectedQuote);
		$resume[$outboundDate->format('Y-m-d')][$length]['quotesSelected'] = $tempArray;
	}
}