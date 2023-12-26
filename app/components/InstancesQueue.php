<?php

namespace app\components;

use app\modules\catalog\models\Category;
use app\modules\catalog\models\Product;
use app\modules\orders\models\Orders;
use yii\base\BaseObject;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Yii;

class InstancesQueue extends BaseObject
{
	const TYPE_CREATED = 'created';
	const TYPE_UPDATED = 'updated';
	const TYPE_SEND_MAIL = 'send-mail';
	const MAIL_RESTORE_PASSWORD = 'restorePassword';
	const MAIL_WELCOME_EMAIL = 'welcomeEmail';

	public $host;
	public $port;
	public $user;
	public $password;
	public $exchange = 'boundless_ex';
	public $routingKey = 'msg';

	protected AMQPStreamConnection $connection;
	protected AMQPChannel $channel;

	public function publishMsg(int $instanceId, string $type, $data)
	{
		try {
			$connection = $this->getActiveConnection();

			$messageBody = json_encode([
				'instanceId' => $instanceId,
				'type' => $type,
				'data' => $data
			]);

			$message = new AMQPMessage($messageBody, ['content_type' => 'application/json']);

			$connection->channel()
				->basic_publish($message, $this->exchange, $this->routingKey)
			;
		} catch (\Exception $e) {
			Yii::error('Cannot publish message to instances:' . $e->getMessage());
		}
	}

	public function getActiveConnection(): AMQPStreamConnection
	{
		if (!isset($this->connection)) {
			$this->connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password, '/');
		}

		return $this->connection;
	}

	public function modelCreated(string $model, array $pkList, array|null $diff = null, bool|null $notifyAdmin = null, bool|null $notifyClient = null)
	{
		$instanceId = $this->extractInstanceId();
//		$instanceId = Yii::$app->user->getIdentity()->getInstance()->instance_id;

		$data = [
			'pkList' => $pkList,
			'model' => $this->getNodeModelByClass($model)
		];

		if ($diff) {
			$data['diff'] = $diff;
		}

		if ($notifyAdmin || $notifyClient) {
			$data['notify'] = [
				'admin' => boolval($notifyAdmin),
				'client' => boolval($notifyClient)
			];
		}

		return $this->publishMsg($instanceId, self::TYPE_CREATED, $data);
	}

	public function modelUpdated(string $model, array $pkList, array|null $diff = null, bool|null $notifyAdmin = null, bool|null $notifyClient = null)
	{
		$instanceId = $this->extractInstanceId();

		$data = [
			'pkList' => $pkList,
			'model' => $this->getNodeModelByClass($model)
		];

		if ($diff) {
			$data['diff'] = $diff;
		}

		if ($notifyAdmin || $notifyClient) {
			$data['notify'] = [
				'admin' => boolval($notifyAdmin),
				'client' => boolval($notifyClient)
			];
		}

		return $this->publishMsg($instanceId, self::TYPE_UPDATED, $data);
	}

	public function sendMail(string $mail, array|null $options = null)
	{
		$instanceId = $this->extractInstanceId();

		$data = [
			'mail' => $mail,
		];

		if (!empty($options)) {
			$data['options'] = $options;
		}

		return $this->publishMsg($instanceId, self::TYPE_SEND_MAIL, $data);
	}

	public function extractInstanceId()
	{
//		it doesn't work in dev mode (if INSTANCE_DB_DSN_DEV is used).
//		return Yii::$app->user->getIdentity()->getInstance()->instance_id;

		$db = Yii::$app->get('instanceDb');
		if (preg_match('/dbname=i([\d]+)/i', $db->dsn, $matches)) {
			return $matches[1];
		}

		throw new \RuntimeException('Cannot extract instanceId from DSN');
	}

	protected function getNodeModelByClass($model)
	{
		switch ($model) {
			case Category::class:
				return 'category';
			case Orders::class:
				return 'orders';
			case Product::class:
				return 'product';

			default:
				throw new \RuntimeException('Unsupported class notification: "' . $model . '"');
		}
	}
}
