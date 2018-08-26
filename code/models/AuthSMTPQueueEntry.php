<?php

/**
 *  AuthSMTP email queue for storing messages to send later
 *  This will fix screen hanging when sending to multiple recipients
 *
 * @property string Subject
 * @property string Recipient
 * @property string Body
 * @property string Template
 * @property string TemplateData
 * @property string CustomHeader
 * @property string Attachments
 * @property string Status
 * @property string Result
 * @property string From
 * @property bool   Plain
 * @property string Queued (date)
 * @property string Sent   (date)
 */
class AuthSMTPQueueEntryModel extends DataObject {
	const StatusQueued  = 'Queued';
	const StatusSending = 'Sending';
	const StatusSent    = 'Sent';
	const StatusFailed  = 'Failed';
	const StatusRetry   = 'Retry';

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
		"Plain"        => "Boolean",
		"Queued"       => "SS_DateTime",
		"Sent"         => "SS_DateTime",
		"RetryCount"   => "Int",
	];

	public static $default_sort = "LastEdited DESC";

	private static $summary_fields = [
		'Queued'    => 'Queued',
		'Sent'      => 'Sent',
		'Subject'   => "Subject",
		'Recipient' => "To",
		'From'      => "From",
		'Status'    => 'Status',
		'Result'    => 'Result',
		'Plain'     => 'Plain Text',
	];

	private static $singular_name = 'Queued Email';

	public function getTitle() {
		return $this->Subject;
	}

	public function canView( $member = null ) {
		return Permission::check( 'CMS_ACCESS_CMSMain', 'any', $member );
	}

	public function canEdit( $member = null ) {
		return Permission::check( 'admin', 'any', $member );
	}

	public function canDelete( $member = null ) {
		return Permission::check( 'admin', 'any', $member );
	}

	public function canCreate( $member = null ) {
		return false;
	}

	/**
	 *
	 * add email message to queue
	 *
	 * @param            $Recipient
	 * @param            $Subject
	 * @param            $Body
	 * @param            $Template
	 * @param bool       $Plain
	 * @param null       $TemplateData
	 * @param array      $Attachments
	 * @param array      $CustomHeader
	 * @param null       $From
	 *
	 * @return int|\AuthSMTPQueueEntryModel
	 * @throws \ValidationException
	 */
	public static function add_message( $Recipient, $Subject, $Body, $Template, $TemplateData = null, $Attachments = [], $CustomHeader = [], $From = null, $Plain = false ) {
		$msg               = AuthSMTPQueueEntryModel::create();
		$msg->Subject      = $Subject;
		$msg->Recipient    = $Recipient;
		$msg->Body         = $Body;
		$msg->Template     = $Template;
		$msg->TemplateData = static::encode_template_data( $TemplateData );
		$msg->CustomHeader = static::encode_data( $CustomHeader );
		$msg->Attachments  = static::encode_data( $Attachments );
		$msg->Status       = self::StatusQueued;
		$msg->Queued       = date( 'Y-m-d H:i:s' );
		$msg->Plain        = $Plain;

		//get default sender from config
		$authorised_from = AuthSMTPService::config()->get( 'from' );

		//if there is a message sender and it is from an authorised domain, use that instead
		if ( ( $allowed_domains = AuthSMTPService::config()->get( 'allowed_from' ) ) && $From ) {
			foreach ( $allowed_domains as $allowed ) {
				if ( preg_match( '/@' . $allowed . '/', $From ) ) {
					$authorised_from = $From;
					break;
				}
			}
		}
		$msg->From = $authorised_from;

		if ( $msg->write() ) {
			return $msg;
		}

		return null;
	}

	public static function add_email( AuthSMTPEmail $email, $sendPlain = false ) {
		return static::add_message(
			$email->To(),
			$email->Subject(),
			$email->Body(),
			$email->getTemplate(),
			$email->getTemplateData(),
			$email->getAttachments(),
			$email->getCustomHeaders(),
			$email->From(),
			$sendPlain
		);
	}

	/**
	 * @param mixed $data
	 *
	 * @return string
	 */
	public static function encode_data( $data ) {
		return json_encode( $data );
	}

	/**
	 * @param string $data
	 *
	 * @return array
	 */
	public static function decode_data( $data ) {
		return $data ? json_decode( $data, true ) : [];
	}

	/**
	 * @param mixed $data
	 *
	 * @return string
	 */
	public static function encode_template_data( $data ) {
		return base64_encode( serialize( $data ) );
	}

	/**
	 * @param string $data
	 *
	 * @return mixed
	 */
	public static function decode_template_data( $data ) {
		return $data ? unserialize( base64_decode( $data ) ) : '';
	}

	/**
	 *
	 * send out emails
	 * should be called from task
	 *
	 */
	public static function process_queue() {
		$queue = static::get()->filter( [
			"Status" => [ self::StatusQueued, self::StatusRetry ],
		] )->sort( "Created", "ASC" )->limit( AuthSMTPService::send_window_size() );

		if ( $queue->count() == 0 ) {
			echo "No Emails Available" . "\n";

			return false;
		}

		foreach ( $queue as $msg ) {

			$msg->update( [
				'Status' => self::StatusSending,
				'Sent'   => date( 'Y-m-d H:i:s' ),
			] )->write();

			/** @var Email|\AuthSMTPEmail $email */
			$email = Email::create( $msg->From, $msg->Recipient, $msg->Subject, $msg->Body );
			$email->setTemplate( $msg->Template );

			//template data
			$TemplateData = static::decode_template_data( $msg->TemplateData );
			if ( ! empty( $TemplateData ) ) {
				$email->setTemplateData( $TemplateData );
				$email->populateTemplate( $TemplateData );
			}

			//attachments
			$files = static::decode_data( $msg->Attachments);
			if ( ! empty( $files ) ) {
				foreach ( $files as $file ) {
					$f = File::get()->byID( $file['ID'] );
					if ( $f->exists() ) {
						$path = $f->getFullPath();
						$email->attachFile( $path );
					}
				}
			}

			//customer header
			$headers = static::decode_data( $msg->CustomHeader );
			if ( ! empty( $headers ) ) {
				foreach ( $headers as $HeaderName => $HeaderValue ) {
					$email->addCustomHeader( $HeaderName, $HeaderValue );
				}
			}

			try {
				//send and update message in queue when successful
				if ( $msg->Plain ) {
					$result = $email->sendPlain();
				} else {
					$result = $email->send();
				}
				if ( $result ) {
					echo "Sent notification to " . $msg->Recipient . "\n";

					$msg->update( [
						'Status' => self::StatusSent,
						'Result' => 'OK',
						'Sent'   => date( 'Y-m-d H:i:s' ),
					] )->write();

				} else {
					throw new Exception( "Failed to send notification '$msg->Subject' to '$msg->Recipient' from '$msg->From'\n" );
				}
			} catch ( Exception $e ) {
				// we'll probably never get here as the sendPlain and sendHTML methods should have done this already
				// but implementation may change...
				echo $e->getMessage();

				$msg->update( [
					'Status' => self::StatusFailed,
					'Result' => $e->getMessage(),
					'Sent'   => date( 'Y-m-d H:i:s' ),
				] )->write();

				// this will die
				AuthSMTPService::error( $e->getMessage() );
			}
		}

		return true;
	}
}
