<?php

class ApiRequest
{
	private $apiKey;
	private $baseUrl;
	private $endUrl;
	private $fileContents;

	function __construct($parameters)
	{
		$this->baseUrl = "http://partners.api.skyscanner.net/apiservices/browsequotes/v1.0/".$parameters->country."/".$parameters->currency."/".$parameters->locale."/".$parameters->originPlace."/".$parameters->destinationPlace;

		$this->endUrl = "?apiKey=".$parameters->apiKey;
	}

	public function request($variables){
		$outboundDate = $variables->outboundDate;
		$inboundDate = $variables->inboundDate;
		$targetUrl = $this->baseUrl."/".$outboundDate."/".$inboundDate.$this->endUrl;
		$file = file_get_contents($targetUrl);
		$this->fileContents = $file;
		return $this->fileContents;
	}
}

class GlobalParametersSearch{
	private $forecastLength;
	private $lengthPossibilites;
	private $directRequired;
	private $budgets;
	private $minTotals;
	private $updateLatency;

	public function __construct($arrayInputs = array()){
		$this->forecastLength = (isset($arrayInputs['forecastLength'])) ? $arrayInputs['forecastLength'] : 12;
		$this->lengthPossibilites = (isset($arrayInputs['lengthPossibilites'])) ? $arrayInputs['lengthPossibilites'] : array(2, 3, 9);
		$this->directRequired = (isset($arrayInputs['directRequired'])) ? $arrayInputs['directRequired'] : TRUE;
		$this->budgets = (isset($arrayInputs['budgets'])) ? $arrayInputs['budgets'] : array(2=>150, 3=>150, 9=>200, 16=>250);
		$this->minTotals = (isset($arrayInputs['minTotals'])) ? $arrayInputs['minTotals'] : 999999999999;
	}

	public function getParameters(){
		return get_object_vars($this);
	}
}

class Parameters{
	private $country;
	private $currency;
	private $locale;
	private $originPlace;
	private $destinationPlace;
	private $apiKey;

	public function __construct($array){
		foreach ($array as $key => $value) {
			$this->$key = $value;
		}
	}

	public function __set($key, $value){
		$this->$key = $value;
	}

	public function __get($key){
		return $this->$key;
	}
}

class Metadata{
	private $urlFile;
	private $unstructuredData;

	function __construct($url){
		$this->urlFile = $url;
		$this->retrieveMetadata();
	}

	public function __set($key, $value){
		$this->$key = $value;
	}

	public function __get($key){
		return $this->$key;
	}

	function retrieveMetadata(){
		$json = file_get_contents($this->urlFile);
		$this->unstructuredData = json_decode($json,true);
	}

	public function getLastRequestTime(){
		$this->lastRequestTime = DateTime::createFromFormat(DateTime::W3C,$this->unstructuredData['lastRequestTime']);
		return $this->lastRequestTime;
	}

	public function setLastRequestTime(){
		$this->lastRequestTime = new DateTime();
	}

	public function save(){
		$fp = fopen($this->urlFile, 'w+');
		fwrite($fp, json_encode(array('lastRequestTime'=>$this->lastRequestTime->format(DateTime::W3C))));
		fclose($fp);
	}
}

class Search{
	private $parameters;
	private $ApiRequest;
	private $fileName;
	private $results;

	function __construct($parameters){
		$this->parameters = new Parameters($parameters);
		$this->launchApiRequest();
	}

	public function launchApiRequest(){
		$this->ApiRequest = new ApiRequest($this->parameters);
		$this->results = $this->ApiRequest->request($this->parameters);
	}

	public function saveToFile($repertoire){
		$outboundDate = $this->parameters->outboundDate;
		$inboundDate = $this->parameters->inboundDate;
		$saveUrl = './'.$repertoire.'/'.$outboundDate."_".$inboundDate.'.txt';
		file_put_contents($saveUrl, $this->results);
	}

}

class Flight{

}