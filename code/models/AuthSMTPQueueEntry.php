<?php

/**
 *  AuthSMTP email queue for storing messages to send later
 *  This will fix screen hanging when sending to multiple recipients
 *
 * @property string  Subject
 * @property string  Recipient
 * @property string  Body
 * @property string  Template
 * @property string  TemplateData
 * @property string  CustomHeader
 * @property string  Attachments
 * @property string  Status
 * @property string  Result
 * @property string  From
 * @property boolean Immediate
 * @property boolean SendPlain
 */
class AuthSMTPQueueEntry extends DataObject {
	const StatusQueued  = 'Queued';
	const StatusSending = 'Sending';
	const StatusSent    = 'Sent';
	const StatusFailed  = 'Failed';

	private static $db = [
		"Subject"      => "Varchar(255)",
		"Recipient"    => "Text",
		"Body"         => "HTMLText",
		"Template"     => "Varchar",
		"TemplateData" => "Text",
		"CustomHeader" => "Text",
		'Attachments'  => 'Text',
		"Status"       => "Varchar(8)",
		"Result"       => "Varchar(255)",
		"From"         => "Varchar(255)",
		'Immediate'    => 'Boolean',                    // was this queued or sent immediately?
		'SendPlain'    => 'Boolean',
	];

	public static $default_sort = "LastEdited DESC";

	private static $summary_fields = [
		'LastEdited' => 'Date',
		'Subject'    => "Subject",
		'Recipient'  => "Recipient",
		'From'       => "From",
		'Status'     => 'Status',
		'Immediate'  => 'Immediate',
		'Result'     => 'Result',
	];

	public function getTitle() {
		return $this->Subject;
	}

	public function canView( $member = null ) {
		return Permission::check( 'CMS_ACCESS_CMSMain', 'any', $member );
	}

	public function canEdit( $member = null ) {
		return false;
	}

	public function canDelete( $member = null ) {
		return Permission::check( 'CMS_ACCESS_CMSMain', 'any', $member );
	}

	public function canCreate( $member = null ) {
		return false;
	}

	/**
	 * Return a configured message ready to send, Status and Immediate are not initialised
	 *
	 * @param string|\Email $recipient
	 * @param string        $subject
	 * @param string        $body
	 * @param string        $templateName
	 * @param string        $templateData
	 * @param string        $attachments
	 * @param string        $customHeader
	 * @param string        $from
	 *
	 * @return \AuthSMTPQueueEntry
	 * @factory
	 */
	public static function factory( $recipient, $subject, $body, $templateName, $templateData = null, $attachments = null, $customHeader = null, $from = null ) {
		$queueEntry               = static::create();
		$queueEntry->Subject      = $subject;
		$queueEntry->Recipient    = static::encode_data( $recipient );
		$queueEntry->Body         = $body;
		$queueEntry->Template     = $templateName;
		$queueEntry->TemplateData = static::encode_data( $templateData );
		$queueEntry->CustomHeader = static::encode_data( $customHeader );
		$queueEntry->Attachments  = static::encode_data( $attachments );
		$queueEntry->From         = static::authorised_from( $from );

		return $queueEntry;
	}

	/**
	 * Given an email address see if it is authorised, otherwise use config.from
	 *
	 * @param string $from
	 *
	 * @return string
	 */
	public static function authorised_from( $from ) {
		//get default sender from config
		$authorised = AuthSMTPService::config()->get( 'from' );

		//if there is a message sender and it is from an authorised domain, use that instead
		if ( ( $allowed_domains = AuthSMTPService::config()->get( 'allowed_from' ) ) && $from ) {
			foreach ( $allowed_domains as $allowed ) {
				if ( preg_match( '/@' . $allowed . '/', $from ) ) {
					$authorised = $from;
					break;
				}
			}
		}

		return $authorised;

	}

	/**
	 * @param $data
	 *
	 * @return string
	 */
	public static function encode_data( $data ) {
		return json_encode( $data );
	}

	/**
	 * @param $data
	 *
	 * @return array
	 */
	public static function decode_data( $data ) {
		return json_decode( $data, true );
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
		)->sort( "Created", "ASC" )->limit( AuthSMTPService::send_window_size() );

		if ( $queue->count() == 0 ) {
			echo "No Emails Available" . "\n";

			return false;
		}

		foreach ( $queue as $queueEntry ) {

			$queueEntry->update( [
				'Status' => self::StatusSending,
			] )->write();

			/** @var Email $email */
			$email = AuthSMTPEmail::create(
				$queueEntry->From,
				static::decode_data( $queueEntry->Recipient ),
				$queueEntry->Subject,
				$queueEntry->Body
			);
			$email->setTemplate( $queueEntry->Template );

			//attachments
			$files = static::decode_data( $queueEntry->Attachments );
			if ( ! empty( $files ) ) {
				foreach ( $files as $file ) {
					/** @var \File $f */
					$f = File::get()->byID( $file['ID'] );
					if ( $f->exists() ) {
						$path = $f->getFullPath();
						$email->attachFile( $path );
					}
				}
			}

			//customer header
			$headers = static::decode_data( $queueEntry->CustomHeader );
			if ( ! empty( $headers ) ) {
				foreach ( $headers as $HeaderName => $HeaderValue ) {
					$email->addCustomHeader( $HeaderName, $HeaderValue );
				}
			}

			//template data
			$TemplateData = static::decode_data( $queueEntry->TemplateData );
			if ( ! empty( $TemplateData ) ) {

				$email->populateTemplate( $TemplateData );
			}
			try {
				if ( $queueEntry->SendPlain ) {
					$result = $email->sendPlain();
				} else {
					$result = $email->send();
				}
				//send and update message in queue when successful
				if ( $result ) {
					echo "Sent notification to " . $queueEntry->Recipient . "\n";

					$queueEntry->update( [
						'Status' => self::StatusSent,
						'Result' => 'OK',
					] )->write();

				} else {

					throw new Exception( "Failed to send notification '$queueEntry->Subject' to '$queueEntry->Recipient' from '$$queueEntry->From'\n" );

				}
			} catch ( Exception $e ) {
				// we'll probably never get here as the sendPlain and sendHTML methods should have done this already
				// but implementation may change...
				echo $e->getMessage();

				$queueEntry->update( [
					'Status' => self::StatusFailed,
					'Result' => $e->getMessage(),
				] )->write();

				AuthSMTPService::error( $e->getMessage() );
			}
		}

		return true;
	}
}
