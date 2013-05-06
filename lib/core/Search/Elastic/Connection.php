<?php
// (c) Copyright 2002-2013 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Elastic_Connection
{
	private $dsn;
	private $dirty = false;

	private $indices = array();

	function __construct($dsn)
	{
		$this->dsn = rtrim($dsn, '/');
	}

	function getStatus()
	{
		try {
			return $this->get('/');
		} catch (Exception $e) {
			return (object) array(
				'ok' => false,
				'status' => 0,
			);
		}
	}

	function deleteIndex($index)
	{
		try {
			unset($this->indices[$index]);
			return $this->delete("/$index");
		} catch (Search_Elastic_Exception $e) {
			if ($e->getCode() !== 404) {
				throw $e;
			}
		}
	}

	function search($index, array $query, $resultStart, $resultCount)
	{
		if ($this->dirty) {
			$this->refresh($index);
		}

		return $this->get("/$index/_search", json_encode($query));
	}

	function index($index, $type, $id, array $data)
	{
		$this->dirty = true;

		$type = $this->simplifyType($type);
		$id = rawurlencode($id);

		return $this->put("/$index/$type/$id", json_encode($data));
	}

	function unindex($index, $type, $id)
	{
		$this->dirty = true;

		$type = $this->simplifyType($type);
		$id = rawurlencode($id);

		return $this->delete("/$index/$type/$id");
	}

	function refresh($index)
	{
		$this->post("/$index/_refresh", '');
		$this->dirty = false;
	}

	function mapping($index, $type, array $mapping)
	{
		$type = $this->simplifyType($type);
		$data = array($type => array(
			"properties" => $mapping,
		));

		if (empty($this->indices[$index])) {
			$this->createIndex($index);
			$this->indices[$index] = true;
		}

		$result = $this->put("/$index/$type/_mapping", json_encode($data));

		return $result;
	}

	private function createIndex($index)
	{
		try {
			$this->put(
				"/$index", json_encode(
					array(
						'analysis' => array(
							'analyzer' => array(
								'default' => array(
									'tokenizer' => 'standard',
									'filter' => array('standard', 'lowercase', 'asciifolding', 'tiki_stop', 'porterStem'),
								),
							),
							'filter' => array(
								'tiki_stop' => array(
									'type' => 'stop',
									'stopwords' => array ("a", "an", "and", "are", "as", "at", "be", "but", "by", "for", "if", "in", "into", "is", "it", "no", "not", "of", "on", "or", "s", "such", "t", "that", "the", "their", "then", "there", "these", "they", "this", "to", "was", "will", "with"),
								),
							),
						),
					)
				)
			);
		} catch (Search_Elastic_Exception $e) {
			// Index already exists: ignore
		}
	}

	private function get($path, $data = null)
	{
		try {
			$client = $this->getClient($path);
			if ($data) {
				$client->setRawData($data);
			}
			$response = $client->request('GET');
			return $this->handleResponse($response);
		} catch (Zend_Http_Exception $e) {
			throw new Search_Elastic_TransportException($e->getMessage());
		}
	}

	private function put($path, $data)
	{
		try {
			$client = $this->getClient($path);
			$client->setRawData($data);
			$response = $client->request('PUT');

			return $this->handleResponse($response);
		} catch (Zend_Http_Exception $e) {
			throw new Search_Elastic_TransportException($e->getMessage());
		}
	}

	private function post($path, $data)
	{
		try {
			$client = $this->getClient($path);
			$client->setRawData($data);
			$response = $client->request('POST');

			return $this->handleResponse($response);
		} catch (Zend_Http_Exception $e) {
			throw new Search_Elastic_TransportException($e->getMessage());
		}
	}

	private function delete($path)
	{
		try {
			$client = $this->getClient($path);
			$response = $client->request('DELETE');

			return $this->handleResponse($response);
		} catch (Zend_Http_Exception $e) {
			throw new Search_Elastic_TransportException($e->getMessage());
		}
	}

	private function handleResponse($response)
	{
		$content = json_decode($response->getBody());

		if ($response->isSuccessful()) {
			return $content;
		} else {
			throw new Search_Elastic_Exception($content->error, $content->status);
		}
	}

	private function getClient($path)
	{
		$full = "{$this->dsn}$path";

		$tikilib = TikiLib::lib('tiki');
		return $tikilib->get_http_client($full);
	}

	private function simplifyType($type)
	{
		return preg_replace('/[^a-z]/', '', $type);
	}
}

