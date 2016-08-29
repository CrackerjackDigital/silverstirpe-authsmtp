<?php

/**
 *  AuthSMTP email queue for storing messages to send later
 *  This will fix screen hanging when sending to multiple recipients
 */
class AuthSMTPQueueModel extends DataObject {
	const StatusQueued = 'Queued';
	const StatusSending = 'Sending';
	const StatusSent = 'Sent';
	const StatusFailed = 'Failed';

	private static $db = [
		"Subject" => "Varchar(255)",
		"Recipient" => "Varchar(255)",
		"Body" => "HTMLText",
		"Template" => "Varchar",
		"TemplateData" => "Text",
		"CustomHeader" => "Text",
		'Attachments' => 'Text',
		"Status" => "Varchar(8)",
	    "Result" => "Varchar(255)"
	];

	public static $default_sort = "Created ASC";

	private static $summary_fields = [
		'LastEdited' => 'Date',
		'Subject' => "Subject",
		'Recipient' => "Recipient",
		'Status' => 'Status',
	    'Result' => 'Result'
	];

	public function getTitle() {
		return $this->Subject;
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
		$msg->Status = self::StatusQueued;

		return $msg->write();

	}

	/**
	 *
	 * send out emails
	 * should be called from task
	 *
	 */
	public static function processQueue() {
		$queue = static::get()->filter(
			[ "Status" => self::StatusQueued ]
		)->sort("Created", "ASC")->limit(AuthSMTPService::send_window_size());

		if ($queue->count() == 0) {
			echo "No Emails Available" . "\n";
			return false;
		}
		//get sender from config
		$from = AuthSMTPService::config()->get('from');

		foreach ($queue as $msg) {
			$msg->update([
				'Status' => self::StatusSending
			])->write();

			/** @var Email $notifier */
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
			try {
				//send and deleted from queue when successful
				if ($notifier->send()) {
					echo "Sent notification to " . $msg->Recipient . "\n";

					$msg->update([
						'Status' => self::StatusSent,
						'Result' => 'OK'
					])->write();

				}
			} catch (Exception $e) {
				echo "Failed to send notification to " . $msg->Recipient . ": \n" . $e->getMessage() . "\n";

				AuthSMTPService::error("Failed to '$msg->Recipient': " . $e->getMessage(), $msg);
			}
		}
		return true;
	}
}