<?php
# Response Extension
class Response
{
	private $_contentType;
	static function factory ($contentType) {
		return new Response($contentType);
	}
	public function __construct ($contentType = 'application/json') {
		$this->_contentType = $contentType;
	}
	public function __invoke ($state) {
		switch ($state) {
			case Route::$PreRequest:
				header("Content-Type: $this->_contentType");
				break;
		}
	}
}
