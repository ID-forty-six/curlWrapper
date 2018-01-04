<?php

namespace idfortysix\curlwrapper;
 
/*
 * Robot bazine klase su konfiguracijom...
 */
 
/**
 * Description of librobotBase
 *
 * @author tautvydas
 */
abstract class RobotBase {

	const DEF_SLEEP = 5;

	/**
	 * CURL options
	 * @var type
	 */
	protected $options = [];

	protected $curl;

	/**
	 * issaugotas cookis
	 * @var array
	 */
	protected $cookie = [];

	/**
	 * turime esama URL, kuri galima naudoti kaip refereri
	 * @var type
	 */
	protected $current_url;

	/**
	 * issaugojame locationa
	 * @var type
	 */
	public $location;

	/**
	 * Grazintas HTML content'as is HTTP CURL uzklausos
	 * @var type
	 */
	public $page;

	protected $charset = "UTF-8";

	/**
	 * pirma karta setinam atsitiktiniu budu, veliau dirbame su tuo paciu - svarbu kai yra sesija
	 * @var type
	 */
	protected $user_agent;

	/**
	 * laikinas failas Wget f-jai (failo vardas)
	 * @var type
	 */
	protected $tmp_filename;

	protected $last_err;

	/**
	 * ar naudoti TOR proxy
	 */
	protected $use_proxy;

	protected $force_check_encoding = false;

	protected $debug = false;

	/**
	 * Headeriai kuriuos siunciam kaskarta kad apsimesti kazkokia narsykle
	 * @var type
	 */
	private $user_agents = array(
		// IE 10
		"Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/4.0; InfoPath.2; SV1; .NET CLR 2.0.50727; WOW64)",
		"Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)",
		"Mozilla/5.0 (compatible; MSIE 10.0; Macintosh; Intel Mac OS X 10_7_3; Trident/6.0)",
		// IE 9
		"Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; Media Center PC 6.0; InfoPath.3; MS-RTC LM 8; Zune 4.7)",
		"Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; Zune 4.0; InfoPath.3; MS-RTC LM 8; .NET4.0C; .NET4.0E)",
		"Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 2.0.50727; Media Center PC 6.0)",
		"Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0",
		// Firefox
		"Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:23.0) Gecko/20131011 Firefox/23.0",
		"Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:22.0) Gecko/20130328 Firefox/22.0",
		"Mozilla/5.0 (Windows NT 6.2; rv:22.0) Gecko/20130405 Firefox/22.0",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:25.0) Gecko/20100101 Firefox/25.0",
		// Safari
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.13+ (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_3) AppleWebKit/534.55.3 (KHTML, like Gecko) Version/5.1.3 Safari/534.53.10",
		"Mozilla/5.0 (iPad; CPU OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko ) Version/5.1 Mobile/9B176 Safari/7534.48.3",
		"Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_8; de-at) AppleWebKit/533.21.1 (KHTML, like Gecko) Version/5.0.5 Safari/533.21.1",
		"Mozilla/5.0 (Windows; U; Windows NT 6.1; ko-KR) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27",
		// Chrome
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1664.3 Safari/537.36",
		"Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.16 Safari/537.36",
		"Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1623.0 Safari/537.36",
		"Mozilla/5.0 (Windows NT 6.2; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1667.0 Safari/537.36",
		// Opera
		"Opera/9.80 (Windows NT 6.1; WOW64; U; pt) Presto/2.10.229 Version/11.62",
		// Googlebots
		"Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)",
		"Googlebot/2.1 (+http://www.googlebot.com/bot.html)",
		"Googlebot/2.1 (+http://www.google.com/bot.html)"
	);







	/**
	 * HTTP user agent headeris
	 * @return type
	 */
	protected function getUserAgent()
	{
		if (!$this->user_agent)
		{
			$this->user_agent = $this->user_agents[ array_rand($this->user_agents) ];
		}
		return $this->user_agent;
	}

	/**
	 * Pradines CURL options - ivykdom su Construct
	 * @return \RobotBase
	 */
	protected function initCurlOpts()
	{
		$this->options += [
			CURLOPT_RETURNTRANSFER  => true,                // return web page
			CURLINFO_HEADER_OUT     => false,               // debug - trackina koks request headeris...
			CURLOPT_HEADER          => true,                // return headers
			CURLOPT_FAILONERROR     => true,                // TRUE to fail verbosely if the HTTP code returned is greater than or equal to 400
			CURLOPT_ENCODING        => "",                  // handle all encodings
			CURLOPT_USERAGENT       => $this->getUserAgent(),       // who am i
			CURLOPT_AUTOREFERER     => true,                // set referer on redirect
			CURLOPT_CONNECTTIMEOUT  => 10,                  // timeout on connect
			CURLOPT_TIMEOUT         => 500,                 // timeout on response
			CURLOPT_MAXREDIRS       => 2,                   // stop after x redirects
			CURLOPT_SSL_VERIFYHOST  => 2,                   // SSL / https
			CURLOPT_SSL_VERIFYPEER  => false,               // SSL / https
		];
		if ($this->use_proxy) {
				$this->useProxy();
		}
		return $this;
	}

	/**
	 * inicializuojam CURL option'us
	 */
	protected function initCurl()
	{
		$this->initCurlOpts();

		$this->last_err = null;
		$this->curl = curl_init();

		return $this;
	}

	/**
	 * uzdarom CURL, resetinam CURL optionus
	 */
	protected function closeCurl()
	{
		curl_close($this->curl);
		$this->options = [];
		return $this;
	}


	/**
	 * sukuriam laikina failo pavadinima Wget f-jai
	 */
	protected function tmpFileName()
	{
		$this->tmp_filename = __DIR__."/../temp/".uniqid().".html";
		return $this->tmp_filename;
	}

	/**
	 * encodingo konvertavimas - pagal tai kas nurodyta konfige, visada i unicoda
	 */
	protected function convertEncoding()
	{
		if ($this->charset != "UTF-8")
		{
			$this->page = iconv($this->charset, "UTF-8", $this->page);
		}
		if ($this->force_check_encoding)
		{
			// sutvarkome encodinga
			$page_encoding = mb_detect_encoding($this->page);
			if (!$page_encoding)
			{
					$page_encoding = 'windows-1257';
			}
			$this->page = iconv($page_encoding, "UTF-8//IGNORE", $this->page);
		}
		return $this;
	}

	/**
	 * Jeigu naudojame CURL Proxy, ijungiame sita metoda
	 */
	private function useProxy()
	{
		$this->options[CURLOPT_HTTPPROXYTUNNEL]         = 1;

		$this->options[CURLOPT_PROXYTYPE]               = 7;    // 7 = CURLPROXY_SOCKS5_HOSTNAME
		$this->options[CURLOPT_PROXY]                   = 'localhost:80';     // 127.0.0.1

		return $this;
	}

	/**
	 * Callback'as kai nuskaitome header'i
	 */
	protected function headerCallback($curl, $header)
	{
		// saugom cookie
		if (preg_match('/Set-Cookie: (\w+?)\=(.+?);/iu', $header, $results)) {
				$this->cookie[ $results[1] ] = $results[2];
		}
		// saugom location
		if (preg_match('/Location: (.+?)$/iu', $header, $results)) {
				$this->location = $results[1];
		}
		return strlen($header);
	}

	protected function curlDebugOutput()
	{
		echo "\nCookie:-------------------------\n";
		print_r($this->cookie);
		echo "Location: ".$this->location ."\n";
		echo "curl_getinfo:---------------------\n";
		print_r (curl_getinfo($this->curl));
		return $this;
	}

	/**
	 * nauja TOR tapatybe (IP addr)
	 * TOR_PORT ir TOR_PASSW turi buti confige
	 * @return boolean
	 */
	protected function newTorIdentity()
	{
		echo "Changing TOR Identity...\n";
		$fp = fsockopen('127.0.0.1', 9051, $errno, $errstr, 30);
		if (!$fp) {
				echo "failed fsockopen\n";
				return false;
		}
		fputs($fp, "AUTHENTICATE \"labadiena\"\r\n");
		$response = fread($fp, 1024);
		list($code, $text) = explode(' ', $response, 2);
		if ($code != '250') {
				echo "failed AUTHENTICATE $code $text\n";
				return false;
		}
		fputs($fp, "SIGNAL NEWNYM\r\n");
		$response = fread($fp, 1024);
		list($code, $text) = explode(' ', $response, 2);
		if ($code != '250') {
				echo "failed signal $code $text\n";
				return false;
		}
		fclose($fp);
		return true;
	}


}
