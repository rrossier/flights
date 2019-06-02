<?php

include 'parameters.php';

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
  /*
  {outboundPartialDate}/
  {inboundPartialDate}?
  apiKey={apiKey}";
  */
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
		$quotesSelected[$outboundDate->format('Y-m-d')] = $tempQuotes;
		$tempArray = array('total'=>count($potentialQuotes), 'nbSelected'=>count($tempQuotes), 'minQuote'=>$minQuote, 'minSelected'=>$minSelectedQuote);
		$resume[$outboundDate->format('Y-m-d')][$length]['quotesSelected'] = $tempArray;
	}
}
// var_dump($resume);
// var_dump($quotesSelected);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="favicon.ico">

    <title>Flights Monitoring Tool - Overview</title>

    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/starter-template.css" rel="stylesheet">
  </head>

  <body>

    <nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
      <a class="navbar-brand" href="./">Navbar</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarsExampleDefault">
        <ul class="navbar-nav mr-auto">
          <li class="nav-item active">
            <a class="nav-link" href="resume.php">Overview</a>
          </li>
          <li class="nav-item">
            <a class="nav-link disabled" href="#">Disabled</a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="http://example.com" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Dropdown</a>
            <div class="dropdown-menu" aria-labelledby="dropdown01">
              <a class="dropdown-item" href="#">Action</a>
              <a class="dropdown-item" href="#">Another action</a>
              <a class="dropdown-item" href="#">Something else here</a>
            </div>
          </li>
        </ul>
        <form class="form-inline my-2 my-lg-0">
          <input class="form-control mr-sm-2" type="text" placeholder="Search" aria-label="Search">
          <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
        </form>
      </div>
    </nav>

    <div class="container">
    	<table class="table">
    	<?php
    	// var_dump($resume);
    		foreach($resume as $outboundDate=>$array){
    			$dateFormatted = new DateTime($outboundDate);
    			$rowspan = (count($LENGTH_POSSIBILITIES)*4 + 1);
    			echo "<tr><th rowspan='".$rowspan."'>".$dateFormatted->format('d-M-Y')."</th></tr>";
    			foreach ($array as $duration=>$allQuotes) {
    				echo "<tr class='table-active'><th>Duration</th><td colspan='3'>".$duration." days</td>";
    				$quotes = $allQuotes['quotesSelected'];
    				$minQuote = $quotes['minQuote'];
    				$minSelected = $quotes['minSelected'];
    				echo "<tr><td>Selected</td><td>".$quotes['nbSelected']."</td><td>Total</td><td>".$quotes['total']."</td></tr>";
    				echo "<tr><td>Min Selected</td><td colspan='3'>";
    				$DepartureDate = new DateTime($minQuote->OutboundLeg->DepartureDate);
					$ReturnDate = new DateTime($minQuote->InboundLeg->DepartureDate);
    				echo $minSelected->destinationName." - ".$DepartureDate->format('D d M Y')." / ".$ReturnDate->format('D d M Y')." via ".ucfirst($minQuote->outboundCarrier)." <span class='badge badge-primary'>".$minQuote->MinPrice."€</span>";
    				echo "</td></tr>";
    				echo "<tr><td>Min Available</td><td colspan='3'>";
    				$DepartureDate = new DateTime($minSelected->OutboundLeg->DepartureDate);
					$ReturnDate = new DateTime($minSelected->InboundLeg->DepartureDate);
    				echo $minSelected->destinationName." - ".$DepartureDate->format('D d M Y')." / ".$ReturnDate->format('D d M Y')." via ".ucfirst($minSelected->outboundCarrier)." <span class='badge badge-secondary'>".$minSelected->MinPrice."€</span>";
    				echo "</td></tr>";
    			}
    			echo "</tr>";
    		}

    	?>
    	</table>

    </div><!-- /.container -->


    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script>window.jQuery || document.write('<script src="../../../../assets/js/vendor/jquery.min.js"><\/script>')</script>
    <script src="js/bootstrap.min.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="s/ie10-viewport-bug-workaround.js"></script>
  </body>
</html>