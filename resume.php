<?php

include 'parameters.php';

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
      <a class="navbar-brand" href="./">Flights</a>
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