<?php
namespace Notification\NotificationBundle\Services;

class HtmlToTextException extends \Exception {
	public $more_info;

	public function __construct($message = "", $more_info = "") {
		parent::__construct($message);
		$this->more_info = $more_info;
	}
}