<?php

namespace idfortysix\curlwrapper;
 
/*
 * Roboto pagrindine klase, pagrinde visi metodai atspindi tai kas buvo librobot'e
 */
 
/**
 * Description of robot
 *
 * @author tautvydas
 */
class Robot extends RobotBase
{
       
	public function __construct($use_proxy=false)
	{
		$this->use_proxy = $use_proxy;
	}

	/**
	 * Idedame issaugota $this->cookie i HTTP requesta
	 * @return \Robot
	 */
	public function putSavedCookie()
	{
		$this->options[CURLOPT_HTTPHEADER] = array("Accept: */*", "Connection: Keep-Alive");
		if ($this->cookie)
		{
			$this->options[CURLOPT_COOKIE]                  = http_build_query($this->cookie, '', '; ');
		}
		return $this;
	}

	/**
	 * POST requestas su kintamaisiais (masyvu)
	 * @param array $post
	 * @return \Robot
	 */
	public function addPost(array $post)
	{
		$this->options[CURLOPT_POST]                    = true;
		$this->options[CURLOPT_POSTFIELDS]              = http_build_query($post);
		return $this;
	}

	/**
	 * follow redirects (location header)
	 * @return \Robot
	 */
	public function followLocation()
	{
		$this->options[CURLOPT_FOLLOWLOCATION] = true;
		return $this;
	}


	/**
	 * idedame refereri i requesta
	 * @return \Robot
	 */
	public function addReferrer($referrer=null)
	{
		$this->options[CURLOPT_REFERER] = $referrer ? $referrer : $this->current_url;
		return $this;
	}

	/**
	 * rezultatas skaitmeniniu formatu
	 */
	public function getBinary()
	{
		$this->options[CURLOPT_BINARYTRANSFER] = true;
		return $this;
	}

	/**
	 * pagrindine CURL funkcija - vykdo kreipimasi i puslapi
	 * @param type $url
	 * @return type
	 */
	public function curlPage($url)
	{
		$this->initCurl();

		// removing hashtag (#)
		$this->options[CURLOPT_URL] = $this->current_url = strtok($url, "#");
		
		if ($this->debug)
		{
			$this->options[CURLINFO_HEADER_OUT] = true;
		}

		if (isset($this->options[CURLOPT_POST]))
		{
			echo "post ";
		}
		echo date("m-d H:i:s")." ".$this->current_url."\n";

		curl_setopt_array($this->curl, $this->options);

		$this->page             = curl_exec($this->curl);

		$err                    = curl_errno($this->curl);
		$errmsg                 = curl_error($this->curl);

		if ($err)
		{
			$this->last_err = $errmsg;
			echo("CURL ERR: $err | $errmsg\n");
		}

		if ($this->debug)
		{
			$this->curlDebugOutput();
		}

		$this->closeCurl();

		$this->convertEncoding();

		return $this->page;
	}

	/**
	 * Galima gauti ar erroras buvo pagal tai kokia ieskoma eilute
	 * @param type $err_fragment
	 * @return boolean
	 */
	public function ifError($err_fragment=null)
	{
		if (!$this->last_err)
		{
			return false;
		}
		if ($err_fragment)
		{
			return stripos($this->last_err, $err_fragment) !== false ? true : false;
		}
		else
		{
			return true;
		}
	}

	/*
	 * Debug outputas
	 */
	public function setDebug($do_debug=true)
	{
		$this->debug = $do_debug;
		return $this;
	}

	/**
	 * isvalome Cookie (pvz. kai reikia is naujo gauti sesijos kuki)
	 *
	 * @return \RobotBase
	 */
	public function resetCookie()
	{
		$this->cookie = [];
		return $this;
	}


	/**
	 * Naujas identitetas, su nauju headeriu ir IP adresu
	 */
	public function newIdentity()
	{
		if ($this->use_proxy)
		{
			$this->newTorIdentity();
			$this->user_agent = null;
		}
		sleep(self::DEF_SLEEP);
	}


	/**
	 * parsisiunciam puslapi pagal URL
	 */
	public function wgetPage($url)
	{
		// parsiunciamas source
		exec("wget -O --user-agent=\"".$this->getUserAgent()."\" ".$this->tmpFileName()." $url");

		// puslapio paemimas, perkonvertavimas ir paruosimas apdorojimui
		$this->page = file_get_contents($this->tmp_filename);

		$this->convertEncoding();

		// istrinamas tmp failas
		exec("rm ".$this->tmp_filename);

		return $this->page;
	}

	/**
	 * irasom page i laikina HTML faila
	 */
	public function writeTemp()
	{
		$fp = fopen($this->tmpFileName(), 'w');
		fwrite($fp, $this->page);
		fclose($fp);
	}
       
}
