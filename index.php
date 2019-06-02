<?php

include 'parameters.php';

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="favicon.ico">

    <title>Flights Monitoring Tool</title>

    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/starter-template.css" rel="stylesheet">
  </head>

  <body>

    <nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
      <a class="navbar-brand" href="#">Flights</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarsExampleDefault">
        <ul class="navbar-nav mr-auto">
          <li class="nav-item">
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
    	<table class="table table-striped">
    		<tr>
    			<th>Length</th>
    			<th>Departure</th>
    			<th>Destination</th>
    			<th>Carrier</th>
    			<th>Return</th>
    			<th>Carrier</th>
    			<th>Price</th>
    		</tr>
    	<?php
			foreach ($quotesSelected as $outboundDate) {
				foreach ($outboundDate as $duration=>$allQuotes) {
					$i = 0;
					// sort by price ascending
					uasort($allQuotes, function($a, $b){
						if ($a->MinPrice == $b->MinPrice) {
					        return 0;
					    }
					    return ($a->MinPrice < $b->MinPrice) ? -1 : 1;
					});
					foreach ($allQuotes as $quote) {
						// display all selected
						$class = ($i == 0) ? 'class="table-success"' : null;
						$i++;
						$DepartureDate = new DateTime($quote->OutboundLeg->DepartureDate);
						$ReturnDate = new DateTime($quote->InboundLeg->DepartureDate);
						$length = $DepartureDate->diff($ReturnDate);
						echo "<tr ".$class."><td>".$length->d."d</td><td>".$DepartureDate->format('D d M Y')."</td><td>".$quote->destinationName." (".$quote->destinationType.")</td><td>".$quote->outboundCarrier."</td>";
						echo "<td>".$ReturnDate->format('D d M Y')."</td><td>".$quote->inboundCarrier."</td><td>".$quote->MinPrice."</td></tr>";
					}
				}
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