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
		'Attachments' => 'Text',
		"Sent" => "Boolean",
	];

	public static $default_sort = "Created DESC";

	private static $summary_fields = [
		'Subject' => "Subject",
		'Recipient' => "Recipient",
		'IsSent' => "Sent",
	];

	public function getTitle() {
		return $this->Subject;
	}

	public function IsSent() {
		return ($this->Sent) ? "Yes" : "No";
	}

	public function canView($member = null) {
		return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
	}
	public function canEdit($member = null) {
		return false;
	}
	public function canDelete($member = null) {
		return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
	}
	public function canCreate($member = null) {
		return false;
	}

	/**
	 *
	 * add email message to queue
	 *
	 */
	public static function addMessage($Recipient, $Subject, $Body, $Template, $TemplateData = null, $Attachments = null, $CustomHeader = null) {
		$msg = AuthSMTPQueueModel::create();
		$msg->Subject = $Subject;
		$msg->Recipient = $Recipient;
		$msg->Body = $Body;
		$msg->Template = $Template;
		$msg->TemplateData = base64_encode($TemplateData);
		$msg->CustomHeader = json_encode($CustomHeader);
		$msg->Attachments = json_encode($Attachments);

		return $msg->write();

	}

	/**
	 *
	 * send out emails
	 * should be called from task
	 *
	 */
	public static function processQueue() {
		$queue = static::get()->filter(["Sent" => 0])->sort("Created", "DESC")->limit(2);
		if ($queue->count() == 0) {
			echo "No Emails Available" . "\n";
			return false;
		}
		//get sender from config
		$from = AuthSMTPConfig::config()->get('from');

		foreach ($queue as $msg) {
			$notifier = Email::create($from, $msg->Recipient, $msg->Subject, $msg->Body);
			$notifier->setTemplate($msg->Template);

			//attachments
			$files = json_decode($msg->Attachments, true);
			if (!empty($files)) {
				foreach ($files as $file) {
					$f = File::get()->byID($file['ID']);
					if ($f->exists()) {
						$path = $f->getFullPath();
						$notifier->attachFile($path);
					}
				}
			}

			//customer header
			$headers = json_decode($msg->CustomHeader, true);
			if (!empty($headers)) {
				foreach ($headers as $HeaderName => $HeaderValue) {
					$notifier->addCustomHeader($HeaderName, $HeaderValue);
				}
			}

			//template data
			$TemplateData = unserialize(base64_decode($msg->TemplateData));
			if (!empty($TemplateData)) {
				$notifier->populateTemplate($TemplateData);
			}
			//send and deleted from queue when successful
			if ($notifier->send()) {
				echo "Sent notification to " . $msg->Recipient . "\n";
				$msg->Sent = 1;
				$msg->write();
			}
		}
		return true;
	}
}