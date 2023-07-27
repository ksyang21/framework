<?php

namespace controllers;

use utils\Helper;

abstract class BaseController
{
	private static int $SUCCESS = 1;
	private static int $FAILED = 0;
	protected array $response = [];

	protected array $errors = [];

	public function respond(): void
	{
		$response = [];
		if (!empty($this->errors)) {
			$response['status'] = self::$FAILED;
			$response['data'] = $this->errors;
			$this->errors       = [];
		} else {
			$response['status'] = self::$SUCCESS;
			$response['data'] = $this->response;
			$this->response = [];
		}
		echo Helper::JSON($response);
	}
}