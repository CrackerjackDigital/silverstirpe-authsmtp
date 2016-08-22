<?php

/**
 *  AuthSMTP email queue for storing messages to send later
 *  This will fix screen hanging when sending to multiple recipients
 */
class AuthSMTPQueueModel extends DataObject {
	private static $db = [
		"Subject" => "Varchar(255)",
		"Recipient" => "Varchar(255)",
		"Body" => "HTMLText",
		"Template" => "Varchar",
		"TemplateData" => "Text",
		"CustomHeader" => "Text",
	];

	/**
	 *
	 * add email message to queue
	 *
	 */
	public static function addMessage($Recipient, $Subject, $Body, $Template, array $TemplateData, $CustomHeader = null) {
		$msg = AuthSMTPQueueModel::create();
		$msg->Subject = $Subject;
		$msg->Recipient = $Recipient;
		$msg->Body = $Body;
		$msg->Template = $Template;
		$msg->TemplateData = json_encode($TemplateData);
		$msg->CustomHeader = $CustomHeader;
		return $msg->write();
	}

	/**
	 *
	 * send out emails
	 * should be called from task
	 *
	 */
	public static function processQueue() {
		$queue = static::get()->sort("Created", "DESC")->limit(2);
		if ($queue->count() == 0) {
			echo "No Emails Available" . "\n";
			return false;
		}
		//get sender from config
		$from = AuthSMTPConfig::config()->get('from');

		foreach ($queue as $msg) {
			$notifier = Email::create($from, $msg->Recipient, $msg->Subject, $msg->Body);
			$notifier->setTemplate($msg->Template);

			if ($msg->CustomHeader) {
				$headers = json_decode($msg->CustomHeader, true);
				foreach ($headers as $key => $value) {
					$notifier->addCustomHeader($key, $value);
				}
			}

			if ($msg->TemplateData) {
				$notifier->populateTemplate(json_decode($msg->TemplateData, true));
				$dataObj = json_decode($msg->TemplateData);
			}

			//send and deleted from queue when successful
			if ($notifier->send()) {
				echo "Sent notification to " . $msg->Recipient . "\n";
				$msg->delete();
			}

			return true;
		}
	}
}