<?php

namespace app\modules\orders\components\manipulatorForReserve;

class NotEnoughStockException extends \Exception
{
	public int $itemId;
	public int $requestedQty;

	public function __construct($message = "", $code = 0, Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}

	public function setItem(int $itemId, int $requestedQty)
	{
		$this->itemId = $itemId;
		$this->requestedQty = $requestedQty;
		$this->message = 'Not enough stock, itemId:' . $itemId . ', requestedQty:' . $this->requestedQty;
	}
}
