<?php

class HTTPClient
{
	static function cGET ($url, $opts = NULL, &$response_headers = FALSE)
	{
		$cctxt = curl_init();
		curl_setopt($cctxt, CURLOPT_URL, $url);
		curl_setopt($cctxt, CURLOPT_RETURNTRANSFER, 1);
		if ($response_headers !== FALSE)
			curl_setopt($cctxt, CURLOPT_HEADER, 1);
		if ($opts !== NULL)
			curl_setopt_array($cctxt, $opts);
		$response = curl_exec($cctxt);
		if ($response_headers !== FALSE) {
			$header_size = curl_getinfo($cctxt, CURLINFO_HEADER_SIZE);
			$response_headers = substr($response, 0, $header_size);
			$response = substr($response, $header_size);
		}
		curl_close($cctxt);
		return $response;
	}

	static function cPOST ($url, $body, $opts = NULL, &$response_headers = FALSE)
	{
		$cctxt = curl_init();
		curl_setopt($cctxt, CURLOPT_URL, $url);
		curl_setopt($cctxt, CURLOPT_POST, 1);
		curl_setopt($cctxt, CURLOPT_POSTFIELDS, $body);
		curl_setopt($cctxt, CURLOPT_RETURNTRANSFER, 1);
		if ($response_headers !== FALSE)
			curl_setopt($cctxt, CURLOPT_HEADER, 1);
		if ($opts !== NULL)
			curl_setopt_array($cctxt, $opts);
		$response = curl_exec($cctxt);
		if ($response_headers !== FALSE) {
			$header_size = curl_getinfo($cctxt, CURLINFO_HEADER_SIZE);
			$response_headers = substr($response, 0, $header_size);
			$response = substr($response, $header_size);
		}
		curl_close($cctxt);
		return $response;
	}

	static function cPUT ($url, $body, $opts = NULL, &$response_headers = FALSE)
	{
		$cctxt = curl_init();
		curl_setopt($cctxt, CURLOPT_URL, $url);
		curl_setopt($cctxt, CURLOPT_RETURNTRANSFER, 1);
		if ($opts && array_key_exists(CURLOPT_HTTPHEADER, $opts)) {
			$userHeaders = $opts[CURLOPT_HTTPHEADER];
			$opts[CURLOPT_HTTPHEADER] = array_merge(array('X-HTTP-Method-Override: PUT'), $userHeaders);
		}
		else
			curl_setopt($cctxt, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: PUT'));
		curl_setopt($cctxt, CURLOPT_POSTFIELDS, $body);
		if ($response_headers !== FALSE)
			curl_setopt($cctxt, CURLOPT_HEADER, 1);
		if ($opts !== NULL)
			curl_setopt_array($cctxt, $opts);
		$response = curl_exec($cctxt);
		if ($response_headers !== FALSE) {
			$header_size = curl_getinfo($cctxt, CURLINFO_HEADER_SIZE);
			$response_headers = substr($response, 0, $header_size);
			$response = substr($response, $header_size);
		}
		curl_close($cctxt);
		return $response;
	}

	static function cDELETE ($url, $body, $opts = NULL, &$response_headers = FALSE)
	{
		$cctxt = curl_init();
		curl_setopt($cctxt, CURLOPT_URL, $url);
		curl_setopt($cctxt, CURLOPT_RETURNTRANSFER, 1);
		if ($opts && array_key_exists(CURLOPT_HTTPHEADER, $opts)) {
			$userHeaders = $opts[CURLOPT_HTTPHEADER];
			$opts[CURLOPT_HTTPHEADER] = array_merge(array('X-HTTP-Method-Override: DELETE'), $userHeaders);
		}
		else
			curl_setopt($cctxt, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: DELETE'));
		curl_setopt($cctxt, CURLOPT_POSTFIELDS, $body);
		if ($response_headers !== FALSE)
			curl_setopt($cctxt, CURLOPT_HEADER, 1);
		if ($opts !== NULL)
			curl_setopt_array($cctxt, $opts);
		$response = curl_exec($cctxt);
		if ($response_headers !== FALSE) {
			$header_size = curl_getinfo($cctxt, CURLINFO_HEADER_SIZE);
			$response_headers = substr($response, 0, $header_size);
			$response = substr($response, $header_size);
		}
		curl_close($cctxt);
		return $response;
	}
}