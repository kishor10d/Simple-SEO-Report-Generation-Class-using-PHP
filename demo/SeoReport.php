<?php 


/**
 * I generate a PDF report using TCPDF library, you have to include it and import the class into this class.
 * If you want to show the information on HTML page then no need to use TCPDF.
 * 
 * Required Resources : TCPDF PHP library, 
 * 
 */
// require 'tcpdf/tcpdf.php';
// require 'tcpdf/mypdf.php';

/**
 * Class SeoReport
 * @filesource SeoReport.php
 * @category SEO
 * @version v1.1
 * @author Kishor Mali
 * This class is used to get the Simple SEO report of the website
 */
class SeoReport{
	
	protected $url = "";
	protected $start = null;
	protected $end = null;
	
	function __construct($url = ""){
		$this->url = $url;
	}
	
	/**
	 * This method need to call from your source class file to generate SEO Report
	 */
	public function getSeoReport(){
		
		$htmlInfo = array();
		
		$htmlInfo["dnsReachable"] = $this->isDNSReachable($this->url);
		
// 		if($htmlInfo["dnsReachable"] !== false){
		
			$isAlive = $this->isAlive();
			/* $this->pre($isAlive);
			 die; */
			
			if($isAlive["STATUS"] == true){
				$this->start = microtime(true);
				$grabbedHTML = $this->grabHTML($this->url);
				$this->end = microtime(true);
				
				$htmlInfo = array_merge($htmlInfo, $this->getSiteMeta($grabbedHTML));
				$htmlInfo["isAlive"] = true;
				/* $this->pre($htmlInfo);
				die; */
			}else{
				$htmlInfo["isAlive"] = false;
			}
// 		}
		$htmlInfo["url"] = $this->url;
		$reqHTML = $this->getReadyHTML($htmlInfo);
		return $reqHTML;
		
		// $this->exportSEOReportPDF($htmlInfo, $this->url);
	}
	
	/**
	 * This function used to print any data 
	 * @param mixed $data
	 */
	function pre($data){
		echo "<pre>";
		print_r($data);
		echo "</pre>";
	}
	
	/**
	 * This function used to print any data
	 * @param mixed $data
	 */
	function dump($data){
		echo "<pre>";
		var_dump($data);
		echo "</pre>";
	}
	
	/**
	 * check if a url is online/alive
	 * @param string $url : URL of the website
	 * @return array $result : This containt HTTP_CODE and STATUS
	 */
	function isAlive() {
		set_time_limit(0);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 7200);
		curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false );
		curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 2 );
		curl_exec ($ch);
		$int_return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close ($ch);
		
		$validCodes = array(200, 301, 302, 304);
		
		if (in_array($int_return_code, $validCodes)){
			return array("HTTP_CODE"=>$int_return_code, "STATUS"=>true);
		}
		else return array("HTTP_CODE"=>$int_return_code, "STATUS"=>false);
	}
	
	/**
	 * This function is used to check the reachable DNS
	 * @param {String} $url : URL of website
	 * @return {Boolean} $status : TRUE/FALSE
	 */
	function isDNSReachable($url){
		$dnsReachable = checkdnsrr($this->addScheme($url));
		return $dnsReachable == false ? false : true;
	}
	
	/**
	 * This function is used to check for file existance on server
	 * @param {String} $filename : filename to be check for existance on server
	 * @return {Boolean} $status : TRUE/FALSE
	 */
	function checkForFiles($filename){
		$handle = curl_init("http://www.".$this->url."/".$filename);
		curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($handle);
		$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		curl_close($handle);
		if($httpCode == 200) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * This function is used to check broken link checking
	 * @param {String} $link : Link to be test as broken or not
	 * @return {Boolean} $status : TRUE/FALSE
	 */
	function brokenLinkTester($link){
		set_time_limit(0);
		$handle = curl_init($link);
		curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($handle);
		$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		curl_close($handle);
		if($httpCode == 200) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * This function is used to check broken link checking for all anchors from page
	 * @param {Array} $anchors : Anchor tags from page
	 * @return {Number} $count : Count of broken link
	 */
	function getBrokenLinkCount($anchors){
		$count = 0;
		$blinks = array();
		foreach ($anchors as $a){
			array_push($blinks, $a->getAttribute("href"));
		}
		if(!empty($blinks)){
			foreach ($blinks as $ln){
				$res = $this->brokenLinkTester($ln);
				if($res){
					$count++;
				}
			}
		}
		
		return $count;
	}
	
	/**
	 * This function is used to check the alt tags for available images from page
	 * @param {Array} $imgs : Images from pages
	 * @return {Array} $result : Array of results
	 */
	function imageAltText($imgs){
		$totImgs = 0;
		$totAlts = 0;
		$diff = 0;
		foreach($imgs as $im){
			$totImgs++;
			if(!empty($im->getAttribute("alt"))){
				$totAlts++;
			}
		}
		return array("totImgs"=>$totImgs, "totAlts"=>$totAlts, "diff"=>($totImgs - $totAlts));
	}
	
	/**
	 * HTTP GET request with curl.
	 * @param string $url : String, containing the URL to curl.
	 * @return string : Returns string, containing the curl result.
	 */
	function grabHTML($url){
		set_time_limit(0);
		$ch  = curl_init($url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch,CURLOPT_MAXREDIRS,2);
		if(strtolower(parse_url($this->url, PHP_URL_SCHEME)) == 'https') {
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,1);
			curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);
		}
		$str = curl_exec($ch);
		curl_close($ch);
	
		return ($str)?$str:FALSE;
	}
	
	/**
	 * This function used to check that google analytics is included in page or not
	 * @param {Object} $grabbedHtml : Page HTML object
	 * @return {Boolean} $result : TRUE/FALSE
	 */
	function findGoogleAnalytics($grabbedHtml){
		$pos = strrpos($grabbedHtml, "GoogleAnalyticsObject");
		return ($pos > 0)?TRUE:FALSE;
	}
	
	/**
	 * This function used to add http protocol to the url if not available
	 * @param {Strin} $url : This is website url
	 * @param {String} $scheme : Protocol Scheme, default http
	 */
	function addScheme($url, $scheme = 'http://'){
		return parse_url($url, PHP_URL_SCHEME) === null ? $scheme . $url : $url;
	}
	
	/**
	 * This function used to get meta and language information from HTML
	 * @param string $grabbedHTML : This is HTML string
	 * @return array $htmlInfo : This is information grabbed from HTML
	 */
	function getSiteMeta($grabbedHTML){
		
		$html = new DOMDocument();
		libxml_use_internal_errors(true);
		$html->loadHTML($grabbedHTML);
		libxml_use_internal_errors(false);
		$xpath = new DOMXPath( $html );
		$htmlInfo = array();
		$langs = $xpath->query( '//html' );
		foreach ($langs as $lang) {
			$htmlInfo['language'] = $lang->getAttribute('lang');
		}
		$metas = $xpath->query( '//meta' );		
		foreach ($metas as $meta) {
			if ($meta->getAttribute('name')){
				$htmlInfo[$meta->getAttribute('name')] = $meta->getAttribute('content');
			}
		}
		
		$favicon = $xpath->query("//link[@rel='shortcut icon']");
		if(!empty($favicon)){
			foreach($favicon as $fav){
				$htmlInfo[$fav->getAttribute("rel")] = $fav->getAttribute("href");
			}
		}
		
		$title = $xpath->query("//title");
		foreach ($title as $tit){
			$htmlInfo["titleText"] = $tit->textContent;
		}
		
		$htmlInfo = array_change_key_case($htmlInfo, CASE_LOWER);
		
		$onlyText = $this->stripHtmlTags($grabbedHTML);
		
		if(!empty($onlyText)){
			$onlyText = array(trim($onlyText));
			
			$count = $this->getWordCounts($onlyText);
			
			$grammar = array("a"=>"", "an"=>"", "the"=>"", "shall"=>"", "should"=>"", "can"=>"", "could"=>"",
					"will"=>"", "would"=>"", "am"=>"", "is"=>"", "are"=>"", "we"=>"", "us"=>"", "has"=>"",
					"have"=>"", "had"=>"", "not"=>"", "yes"=>"", "no"=>"", "true"=>"", "false"=>"", "with"=>"",
					"to"=>"", "your"=>"", "more"=>"", "and"=>"", "in"=>"", "out"=>"", "login"=>"", "logout"=>"",
					"sign"=>"", "up"=>"", "coming"=>"", "going"=>"", "now"=>"", "then"=>"", "about"=>"",
					"contact"=>"", "my"=>"", "you"=>"", "go"=>"", "close"=>"", ""=>"", "of"=>"", "our"=>"");
			
			$count = array_diff_key($count, $grammar);
			
			arsort($count, SORT_DESC | SORT_NUMERIC);
			
			$htmlInfo["wordCount"] = $count;
			$htmlInfo["wordCountMax"] = array_slice($count, 0, 5, true);
		}
		
		if(!empty($htmlInfo["wordCount"]) && !empty($htmlInfo["keywords"])){
			$htmlInfo["compareMetaKeywords"] = $this->compareMetaWithContent(array_keys($htmlInfo["wordCount"]), $htmlInfo["keywords"]);
		}
		
		$h1headings = $xpath->query("//h1");
		$index = 0;
		foreach ($h1headings as $h1h){
			$htmlInfo["h1"][$index] = trim(strip_tags($h1h->textContent));
			$index++;
		}
		
		$h2headings = $xpath->query("//h2");
		$index = 0;
		foreach ($h2headings as $h2h){
			$htmlInfo["h2"][$index] = trim(strip_tags($h2h->textContent));
			$index++;
		}
		
		$htmlInfo["robots"] = $this->checkForFiles("robots.txt");
		$htmlInfo["sitemap"] = $this->checkForFiles("sitemap.xml");
		
		$htmlInfo["brokenLinkCount"] = 0;
		$anchors = $xpath->query("//a");
		if(!empty($anchors)){
// 			$htmlInfo["brokenLinkCount"] = $this->getBrokenLinkCount($anchors);
		}
		
		$htmlInfo["images"] = array();
		$imgs = $xpath->query("//img");
		if(!empty($imgs)){
			$htmlInfo["images"] = $this->imageAltText($imgs);
		}
		
		$htmlInfo["googleAnalytics"] = $this->findGoogleAnalytics($grabbedHTML);
		
		$htmlInfo["pageLoadTime"] = $this->getPageLoadTime();
		
		$htmlInfo["flashTest"] = FALSE;
		$flashExists = $xpath->query("//embed[@type='application/x-shockwave-flash']");
		if($flashExists->length !== 0){
			$htmlInfo["flashTest"] = TRUE;
		}
		
		$htmlInfo["frameTest"] = FALSE;
		$frameExists = $xpath->query("//frameset");
		if($frameExists->length !== 0){
			$htmlInfo["frameTest"] = TRUE;
		}
		
		$htmlInfo["css"] = array();
		$cssExists = $xpath->query("//link[@rel='stylesheet']");
		$htmlInfo["css"] = array_merge ($htmlInfo["css"], $this->cssFinder($cssExists));
		
		$htmlInfo["js"] = array();
		$jsExists = $xpath->query("//script[contains(@src, '.js')]");
		$htmlInfo["js"] = array_merge ($htmlInfo["js"], $this->jsFinder($jsExists));
		
		return $htmlInfo;
	}
	
	/**
	 * This function used to find all JS files
	 * @param {Array} $jsExists : JS exist count
	 * @return {Array} $push : JS result with js counts
	 */
	function jsFinder($jsExists){
		$push["jsCount"] = 0;
		$push["jsMinCount"] = 0;
		$push["jsNotMinFiles"] = array();
		
		if(!empty($jsExists)){
			foreach($jsExists as $ce){
				$push["jsCount"]++;
				if($this->formatCheckLinks($ce->getAttribute("src"))){
					$push["jsMinCount"]++;
				} else {
					array_push($push["jsNotMinFiles"], $ce->getAttribute("src"));
				}
			}
		}
		return $push;
	}
	
	/**
	 * This function used to find all CSS files
	 * @param {Array} $cssExists : CSS exist count
	 * @return {Array} $push : CSS result with css counts
	 */
	function cssFinder($cssExists){
		$push["cssCount"] = 0;
		$push["cssMinCount"] = 0;
		$push["cssNotMinFiles"] = array();
		
		if(!empty($cssExists)){
			foreach($cssExists as $ce){
				$push["cssCount"]++;				
				if($this->formatCheckLinks($ce->getAttribute("href"))){
					$push["cssMinCount"]++;
					
				} else {
					array_push($push["cssNotMinFiles"], $ce->getAttribute("href"));
				}
			}
		}
		
		return $push;
	}
	
	/**
	 * This function used to check format checking for JS and CSS
	 * @param {String} $link : JS or CSS file link
	 * @return {Boolean} $result : TRUE/FALSE
	 */
	function formatCheckLinks($link){
		$cssFile = "";
		if(strpos($cssFile, '?') !== false){
			$cssFile = substr($link, strrpos($link, "/"), strrpos($link, "?") - strrpos($link, "/"));
		} else {
			$cssFile = substr($link, strrpos($link, "/"));
		}
		if (strpos($cssFile, '.min.') !== false) {
			return true;
		}else {
			return false;
		}
	}
	
	/**
	 * This function used to strip HTML tags from grabbed string
	 * @param {String} $str : HTML string to be stripped
	 * @return {String} $str : Stripped string
	 */
	function stripHtmlTags($str){
		$str = preg_replace('/(<|>)\1{2}/is', '', $str);
		$str = preg_replace(
				array(
						'@<head[^>]*?>.*?</head>@siu',
						'@<style[^>]*?>.*?</style>@siu',
						'@<script[^>]*?.*?</script>@siu',
						'@<noscript[^>]*?.*?</noscript>@siu',
				),
				"",
				$str );
		
		$str = $this->replaceWhitespace($str);
		$str = html_entity_decode($str);
		$str = strip_tags($str);
		return $str;
	}
	
	/**
	 * This function used to remove whitespace from string, recursively
	 * @param {String} $str : This is input string
	 * @return {String} $str : Output string, or recursive call
	 */
	function replaceWhitespace($str) {
		$result = $str;
		foreach (array(
				"  ","   ", " \t",  " \r",  " \n",
				"\t\t", "\t ", "\t\r", "\t\n",
				"\r\r", "\r ", "\r\t", "\r\n",
				"\n\n", "\n ", "\n\t", "\n\r",
		) as $replacement) {
			$result = str_replace($replacement, $replacement[0], $result);
		}
		return $str !== $result ? $this->replaceWhitespace($result) : $result;
	}
	
	/**
	 * This function use to get word count throughout the webpage
	 * @param array $phrases : This is array of strings
	 * @return array $count : Array of words with count - number of occurences
	 */
	function getWordCounts($phrases) {
		
		$counts = array();
		foreach ($phrases as $phrase) {
			$words = explode(' ', strtolower($phrase));
			
			$grammar = array("a", "an", "the", "shall", "should", "can", "could", "will", "would", "am", "is", "are",
					"we", "us", "has", "have", "had", "not", "yes", "no", "true", "false", "with", "to", "your", "more",
					"and", "in", "out", "login", "logout", "sign", "up", "coming", "going", "now", "then", "about",
					"contact", "my", "you", "of", "our");
			
			$words = array_diff($words, $grammar);
			
			foreach ($words as $word) {
				if(!empty(trim($word))){
					$word = preg_replace("#[^a-zA-Z\-]#", "", $word);
					if(isset($counts[$word])){
						$counts[$word] += 1;
					}else{
						$counts[$word] = 1;
					}
				}
			}
		}
		return $counts;
	}
	
	/**
	 * gets the inbounds links from a site
	 * @param string $url
	 * @param integer
	 */
	function googleSearchResult($url)
	{
		$url  = 'https://www.google.com/#q='.$url;
        $str  = $this->grabHTML($url);
        $data = json_decode($str);
	
        return (!isset($data->responseData->cursor->estimatedResultCount))
                ? '0'
                : intval($data->responseData->cursor->estimatedResultCount);
	}
	
	/**
	 * This function used to compare keywords with meta
	 * @param array $contentArray : This is content array
	 * @param string $kewordsString : This is meta keyword string
	 * @return array $keywordMatch : Match found
	 */
	function compareMetaWithContent($contentArray, $kewordsString){
		$kewordsString = strtolower(str_replace(',', ' ', $kewordsString));
		$keywordsArray = explode(" ", $kewordsString);
		$keywordMatch = array();
		foreach ($contentArray as $ca) {
			if(!empty(trim($ca)) && in_array($ca, $keywordsArray)){
				array_push($keywordMatch, $ca);
			}
		}
		
		/* $this->pre($contentArray);
		$this->pre($kewordsString); */
		
		return $keywordMatch;
	}
	
	/**
	 * This function is used to export requirements as PDF
	 * @param {String} $htmlInfo : This is HTML string which is to be print in PDF
	 * @param {String} $for : This website link for which we are generating report
	 */
	function exportSEOReportPDF($htmlInfo, $for) {
		set_time_limit ( 0 );
		ob_start();
		
		// $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf = new MYPDF ( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );
	
		$fileName = $for;
		$htmlInfo["url"] = $for;
	
		if (! empty ( $htmlInfo )) {
			// set document information
			$pdf->SetCreator ( PDF_CREATOR );
			$pdf->SetAuthor ( 'CodeInsect' );
			$pdf->SetTitle ( "SEO Report" );
			$pdf->SetSubject ( 'SEO Report For ' );

			$logo = 'logo.png';

			// set default header data
			// $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 006', PDF_HEADER_STRING);
			$pdf->SetHeaderData ( $logo, 10, $for, "by CodeInsect" );

			// set header and footer fonts
			$pdf->setHeaderFont ( Array (
					PDF_FONT_NAME_MAIN,
					'',
					PDF_FONT_SIZE_MAIN
					) );
			$pdf->setFooterFont ( Array (
					PDF_FONT_NAME_DATA,
					'',
					PDF_FONT_SIZE_DATA
					) );

			// set default monospaced font
			$pdf->SetDefaultMonospacedFont ( PDF_FONT_MONOSPACED );

			// set margins
			$pdf->SetMargins ( PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT );
			$pdf->SetHeaderMargin ( PDF_MARGIN_HEADER );
			$pdf->SetFooterMargin ( PDF_MARGIN_FOOTER );

			// set auto page breaks
			$pdf->SetAutoPageBreak ( TRUE, PDF_MARGIN_BOTTOM );

			// set image scale factor
			$pdf->setImageScale ( PDF_IMAGE_SCALE_RATIO );

			$pdf->AddPage ();
			
			$reqHTML = $this->getReadyHTML($htmlInfo);
			
			/* $this->pre($reqHTML);
			die; */
			
			// set font for utf-8 type of data
			$pdf->SetFont('freeserif', '', 12);
			
			$pdf->writeHTML ( $reqHTML, true, false, false, false, '' );
			$pdf->lastPage ();
		}
	
		$pdf->Output ( $fileName . '.pdf', 'D' );
	}
	
	/**
	 * This function is used to calculate simple load time of HTML page
	 */
	function getPageLoadTime(){
		if(!is_null($this->start) && !is_null($this->end)){
			return $this->end - $this->start;
		}else{
			return 0;
		}
	}
	
	/**
	 * This function used to clean the string with some set of rules
	 * @param {String} $string : String to be clean
	 * @return {String} $string : clean string
	 */
	function clean($string) {
		$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
		$string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
	
		$string  = preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
		return str_replace('-', ' ', $string);
	}
	
	/**
	 * Create HTML to print on PDF (or on page if you want to print on HTML page)
	 * Make Sure that HTML is correct, otherwise it will not print on PDF
	 * @param {Array} $htmlInfo : Array having total seo analysis
	 * @return {String} $html : Real html which is to be print 
	 */
	function getReadyHTML($htmlInfo){
		
		$html = '<div>';
		$html .= '<table border="1" cellpadding="2" cellspacing="2" nobr="true">';
		$html .= '<thead>';
		$html .= '<tr>';
		$html .= '<th colspan="2" align="center">COMMON SEO ISSUES</th>';
		$html .= '</tr>';
		$html .= '</thead>';
		$html .= '<tbody>';
		
		
		/* if($htmlInfo["dnsReachable"] !== false){ */
		
			if( $htmlInfo["isAlive"]  == true){		
				$html .= '<tr nobr="true">';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;"><strong>Site Status</strong></span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;">Congratulations! Your site is alive.</span></div>';			
				$html .= '</td>';
				$html .= '</tr>';
				
				$html .= '<tr nobr="true">';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;"><strong>Title Tag</strong></span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				if(isset($htmlInfo["titletext"])){
					$html .= '<div style="width: 100%;"><span style="font-size:11px;">The meta title of your page has a length of '.strlen($htmlInfo["titletext"]).' characters. Most search engines will truncate meta titles to 70 characters. <br> -> <strong>'.$htmlInfo["titletext"].'</strong> </span></div>';
				}else{
					$html .= '<div style="width: 100%;"><span style="font-size:11px;">Your page doesn\'t have title. </span></div>';
				}
				$html .= '</td>';
				$html .= '</tr>';
				
				$html .= '<tr style="width: 100%; padding-left:10px;" nobr="true">';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;"><strong>Meta Description</strong></span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				if(isset($htmlInfo["description"])){
					$html .= '<div style="width: 100%;"><span style="font-size:11px;">The meta description of your page has a length of '.strlen($htmlInfo["description"]).' characters. Most search engines will truncate meta descriptions to 160 characters. <br> -> <strong>'.$htmlInfo["description"].'</strong> </span></div>';
				}else{
					$html .= '<div style="width: 100%;"><span style="font-size:11px;">Your page doesn\'t have meta description </span></div>';
				}
				$html .= '</td>';
				$html .= '</tr>';
				
				$html .= '<tr style="width: 100%; padding-left:10px;" nobr="true">';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;"><strong>Google Search Results Preview</strong></span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				$html .= '<div style="width: 100%;">';
				if(isset($htmlInfo["titletext"])){
					$html .= '<span style="color:#609;font-size:13px;"><u>'.$htmlInfo["titletext"].'</u></span><br>';
				}
				$html .= '<span style="color:#006621;font-size:11px;">'.$this->addScheme($htmlInfo["url"], "http://").'</span><br>';
				if(isset($htmlInfo["description"])){
					$html .= '<span style="color:#6A6A6A;font-size:11px;">'.$htmlInfo["description"].'</span>';
				}
				$html .= '</div>';
				$html .= '</td>';
				$html .= '</tr>';
				
				$html .= '<tr style="width: 100%; padding-left:10px;" nobr="true">';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;"><strong>Most Common Keywords Test</strong></span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				$html .= '<div style="width: 100%;">';
				if(!empty($htmlInfo["wordCountMax"])){
					$html .= '<span style="font-size:11px;">There is likely no optimal keyword density (search engine algorithms have evolved beyond
						keyword density metrics as a significant ranking factor). It can be useful, however, to note which
						keywords appear most often on your page and if they reflect the intended topic of your page. More
						importantly, the keywords on your page should appear within natural sounding and grammatically
						correct copy.</span>';
					foreach($htmlInfo["wordCountMax"] as $wordMaxKey => $wordMaxValue){
						$html .= '<br>-> <span style="font-weight:bold;font-size:11px;color:#000000;">'.$wordMaxKey.' - '.$wordMaxValue.'</span>';
					}
				}else{
					$html .= '<span style="font-size:11px;">Your page doens\'t have any repeated keywords.</span><br>';
				}
				$html .= '</div>';
				$html .= '</td>';
				$html .= '</tr>';
				
				
				$html .= '<tr style="width: 100%; padding-left:10px;" nobr="true">';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;"><strong>Keyword Usage</strong></span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				$html .= '<div style="width: 100%;">';
				if(!empty($htmlInfo["compareMetaKeywords"])){
					$html .= '<span style="font-size:11px;">Your page have common keywords from meta tags.</span>';
					foreach($htmlInfo["compareMetaKeywords"] as $metaKey => $metaValue){
						$html .= '<br>-> <span style="font-size:11px;color:#000000;">'.$metaValue.'</span>';
					}
				}else{
					$html .= '<span style="font-size:11px;">Your most common keywords are not appearing in one or more of the meta-tags above. Your
							primary keywords should appear in your meta-tags to help identify the topic of your webpage to
							search engines.</span>';
				}
				$html .= '</div>';
				$html .= '</td>';
				$html .= '</tr>';
				
				$html .= '<tr style="width: 100%; padding-left:10px;" nobr="true">';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;"><strong>h1 Headings Status</strong></span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				$html .= '<div style="width: 100%;">';
				if(isset($htmlInfo["h1"])){
					$html .= '<span style="font-size:10px;">Your pages having these H1 headigs.</span>';
					foreach($htmlInfo["h1"] as $h1){
						$html .= '<br>-> <span style="font-weight:bold;font-size:10px;color:#000000;">'.$h1.'</span> ';
					}
				}else{
					$html .= '<span style="font-size:10px;">Your page doesn\'t have H1 tags.</span>';
				}
				$html .= '</div>';
				$html .= '</td>';
				$html .= '</tr>';
				
				$html .= '<tr style="width: 100%; padding-left:10px;" nobr="true">';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;"><strong>h2 Headings Status</strong></span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				$html .= '<div style="width: 100%;">';
				if(isset($htmlInfo["h2"])){
					$html .= '<span style="font-size:10px;">Your pages having these H2 headigs.</span>';
					foreach($htmlInfo["h2"] as $h2){
						$html .= '<br>-> <span style="font-weight:bold;font-size:10px;color:#000000;">'.$h2.'</span> ';
					}
				}else{
					$html .= '<span style="font-size:10px;">Your page doesn\'t have H2 tags.</span>';
				}
				$html .= '</div>';
				$html .= '</td>';
				$html .= '</tr>';
				
				$html .= '<tr style="width: 100%; padding-left:10px;" nobr="true">';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;"><strong>Robots.txt Test</strong></span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				$html .= '<div style="width: 100%;">';
				if($htmlInfo["robots"] == 200){
					$html .= '<span style="font-size:10px;">Congratulations! Your site uses a "robots.txt" file: <span style="color:blue">http://'.$htmlInfo["url"].'/robots.txt</span></span>';
				}else{
					$html .= '<span style="font-size:10px;">Your page doesn\'t have "robots.txt" file </span>';
				}
				$html .= '</div>';
				$html .= '</td>';
				$html .= '</tr>';
				
				$html .= '<tr style="width: 100%; padding-left:10px;" nobr="true">';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;"><strong>Sitemap Test</strong></span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				$html .= '<div style="width: 100%;">';
				if($htmlInfo["robots"] == 200){
					$html .= '<span style="font-size:10px;">Congratulations! We\'ve found sitemap file for your website: <span style="color:blue">http://'.$htmlInfo["url"].'/sitemap.xml</span></span>';
				}else{
					$html .= '<span style="font-size:10px;">Your page doesn\'t have "sitemap.xml" file. </span>';
				}
				$html .= '</div>';
				$html .= '</td>';
				$html .= '</tr>';
				
				$html .= '<tr style="width: 100%; padding-left:10px;" nobr="true">';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;"><strong>Broken Links Test</strong></span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				$html .= '<div style="width: 100%;">';
				if(!empty($htmlInfo["brokenLinkCount"]) && $htmlInfo["brokenLinkCount"] != 0){
					$html .= '<span style="font-size:10px;">Your page has some broken links, count : '.$htmlInfo["brokenLinkCount"].'</span>';
				}else{
					$html .= '<span style="font-size:10px;">Congratulations! Your page doesn\'t have any broken links. </span>';
				}
				$html .= '</div>';
				$html .= '</td>';
				$html .= '</tr>';
				
				$html .= '<tr style="width: 100%; padding-left:10px;" nobr="true">';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;"><strong>Image Alt Test</strong></span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				$html .= '<div style="width: 100%;">';
				if(!empty($htmlInfo["images"])){
					if(isset($htmlInfo["images"]["totImgs"]) && $htmlInfo["images"]["totImgs"] != 0){
						if($htmlInfo["images"]["diff"] <= 0){
							$html .= '<span style="font-size:10px;">Congratulations! '.$htmlInfo["images"]["totImgs"].' images found in your page, and all have "ALT" text. </span>';
						}else{
							$html .= '<span style="font-size:10px;">'.$htmlInfo["images"]["totImgs"].' images found in your page and '.$htmlInfo["images"]["diff"].' images are without "ALT" text.</span>';
						}
					}else{
						$html .= '<span style="font-size:10px;">Your pages does not have any images</span>';
					}
				}else{
					$html .= '<span style="font-size:10px;">Your pages does not have any images</span>';
				}
				$html .= '</div>';
				$html .= '</td>';
				$html .= '</tr>';
				
				$html .= '<tr style="width: 100%; padding-left:10px;" nobr="true">';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;"><strong>Google Analytics</strong></span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				$html .= '<div style="width: 100%;">';
				if($htmlInfo["googleAnalytics"] == true){
					$html .= '<span style="font-size:10px;">Congratulations! Your page is already submitted to Google Analytics.</span>';
				}else{
					$html .= '<span style="font-size:10px;">Your page not submitted to Google Analytics</span>';
				}
				$html .= '</div>';
				$html .= '</td>';
				$html .= '</tr>';
				
				
				$html .= '<tr nobr="true">';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;"><strong>Favicon Test</strong></span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				if(isset($htmlInfo["shortcut icon"]) || isset($htmlInfo["icon"])){
					$html .= '<div style="width: 100%;font-size:10px;">Congratulations! Your website appears to have a favicon.</div>';
				}else{
					$html .= '<div style="width: 100%;font-size:10px;">Your site doesn\'t have favicon.</div>';
				}
				$html .= '</td>';
				$html .= '</tr>';
				
				$html .= '<tr nobr="true">';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;"><strong>Site Loading Speed Test</strong></span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				if($htmlInfo["pageLoadTime"] !== 0){
					$html .= '<div style="width: 100%;font-size:10px;">Your site loading time is around <strong>'.$htmlInfo["pageLoadTime"].' seconds</strong> and the average loading speed of any website which is <strong>5 seconds</strong> required. </div>';
				}else{
					$html .= '<div style="width: 100%;font-size:10px;">Unable to get load time of your site.</div>';
				}
				$html .= '</td>';
				$html .= '</tr>';
				
				
				$html .= '<tr nobr="true">';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;"><strong>Flash Test</strong></span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				if($htmlInfo["flashTest"] == true){
					$html .= '<div style="width: 100%;font-size:10px;">Your website include flash objects (an outdated technology that was sometimes used to deliver rich multimedia content). Flash content does not work well on mobile devices, and is difficult for crawlers to interpret.</div>';
				}else{
					$html .= '<div style="width: 100%;font-size:10px;">Congratulations! Your website does not include flash objects (an outdated technology that was sometimes used to deliver rich multimedia content). Flash content does not work well on mobile devices, and is difficult for crawlers to interpret.</div>';
				}
				$html .= '</td>';
				$html .= '</tr>';
				
				$html .= '<tr nobr="true">';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;"><strong>Frame Test</strong></span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				if($htmlInfo["frameTest"] == true){
					$html .= '<div style="width: 100%;font-size:10px;">Your webpage use frames.</div>';
				}else{
					$html .= '<div style="width: 100%;font-size:10px;">Congratulations! Your webpage does not use frames.</div>';
				}
				$html .= '</td>';
				$html .= '</tr>';
				
				
				$html .= '<tr nobr="true">';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;"><strong>CSS Minification</strong></span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				$html .= '<div style="width: 100%;font-size:10px;">';
				if(!empty($htmlInfo["css"])){
					if($htmlInfo["css"]["cssCount"] > 0){
						$html .= '<span style="width: 100%;font-size:10px;">Your page having '.$htmlInfo["css"]["cssCount"].' external css files </span>';
						if($htmlInfo["css"]["cssMinCount"] > 0){
							$html .= '<span style="width: 100%;font-size:10px;">and out of them '.$htmlInfo["css"]["cssMinCount"].' css files are minified.</span>';
						} else{
							$html .= '<span style="width: 100%;font-size:10px;">and no file is minified.</span>';
						}
							
						if(!empty($htmlInfo["css"]["cssNotMinFiles"])){
							$html .= '<br><span style="width: 100%;font-size:10px;">Following files are not minified : </span>';
							foreach($htmlInfo["css"]["cssNotMinFiles"] as $cNMF){
								$html .= '<br><span style="width: 100%;font-size:10px;color:blue;">'.$cNMF.'</span>';
							}
						}
					}
					else{
						$html .= '<span style="width: 100%;font-size:10px;">No external css found.</span>';
					}
				}else{
					$html .= '<span style="width: 100%;font-size:10px;">No external css found.</span>';
				}
				$html .= '</div>';
				$html .= '</td>';
				$html .= '</tr>';
				
				$html .= '<tr nobr="true">';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;"><strong>JS Minification</strong></span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				$html .= '<div style="width: 100%;font-size:10px;">';
				if(!empty($htmlInfo["js"])){
					if($htmlInfo["js"]["jsCount"] > 0){
						$html .= '<span style="width: 100%;font-size:10px;">Your page having '.$htmlInfo["js"]["jsCount"].' external js files </span>';
						if($htmlInfo["js"]["jsMinCount"] > 0){
							$html .= '<span style="width: 100%;font-size:10px;">and out of them '.$htmlInfo["js"]["jsMinCount"].' js files are minified.</span>';
						} else{
							$html .= '<span style="width: 100%;font-size:10px;">and no file is minified.</span>';
						}
					
						if(!empty($htmlInfo["js"]["jsNotMinFiles"])){
							$html .= '<br><span style="width: 100%;font-size:10px;">Following files are not minified : </span>';
							foreach($htmlInfo["js"]["jsNotMinFiles"] as $jNMF){
								$html .= '<br><span style="width: 100%;font-size:10px;color:blue;">'.$jNMF.'</span>';
							}
						}
					}
					else{
						$html .= '<span style="width: 100%;font-size:10px;">No external js found.</span>';
					}
				}else{
					$html .= '<span style="width: 100%;font-size:10px;">No external js found.</span>';
				}
				$html .= '</div>';
				$html .= '</td>';
				$html .= '</tr>';
				
			} else {
				$html .= '<tr>';
				$html .= '<td style="width: 30%;">';
				$html .= '<div style="width: 100%;"><span style="font-size:11px;">Site Status</span></div>';
				$html .= '</td>';
				$html .= '<td style="width: 70%;">';
				$html .= '<div style="width: 100%;">You didn\'t uploaded anything on site yet.</div>';
				$html .= '</td>';
				$html .= '</tr>';
			}
			
		/* }
		else {
			
			$html .= '<tr>';
			$html .= '<td style="width: 30%;">';
			$html .= '<div style="width: 100%;"><span style="font-size:11px;">Site Status</span></div>';
			$html .= '</td>';
			$html .= '<td style="width: 70%;">';
			$html .= '<div style="width: 100%;">No DNS Found</div>';
			$html .= '</td>';
			$html .= '</tr>';
		} */
		
		
		$html .= '</tbody>';
		$html .= '</table>';
		$html .= '</div>';
		
		
		return $html;
	}
	
}