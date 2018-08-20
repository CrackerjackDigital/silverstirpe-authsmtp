<?php

use Pelago\Emogrifier;

/**
 *  Email queuing extension for Email
 */
class AuthSMTPEmail extends Email implements AuthSMTPEmailInterface {
	private static $inline_styles = true;

	private static $queue = true;

	protected $inlineStyles;

	protected $useQueue;

	public function __construct( $from = null, $to = null, $subject = null, $body = null, $bounceHandlerURL = null, $cc = null, $bcc = null ) {
		$this->inlineStyles = $this->config()->get('inline_styles');
		$this->useQueue = $this->config()->get('queue');

		parent::__construct( $from, $to, $subject, $body, $bounceHandlerURL, $cc, $bcc );
	}

	public function setInlineStyles($enable) {
		$this->inlineStyles = $enable;
		return $this;
	}

	public function setQueueing($enable) {
		$this->useQueue = $enable;
		return $this;
	}

	/**
	 * Override the standard send to use the queue if required, otherwise send and log
	 *
	 * @param null $messageID
	 *
	 * @return mixed|void
	 * @throws \ValidationException
	 */
	public function send( $messageID = null ) {
		if ($this->useQueue) {
			$result = AuthSMTPService::enqueue(
				$this->To(),
				$this->Subject(),
				$this->Body(),
				$this->getTemplate(),
				$this->templateData(),
				$this->attachments,
				$this->customHeaders,
				$this->From()
			);
		} else {
			$result = parent::send( $messageID );
			AuthSMTPService::log(
				$result,
				$this->To(),
				$this->Subject(),
				$this->Body(),
				$this->getTemplate(),
				$this->templateData(),
				$this->attachments,
				$this->customHeaders,
				$this->From()
			);
		}
		return $result;
	}

	/**
	 * Override sendPlain to use queue if required, otherwise send and log
	 *
	 * @param null $messageID
	 *
	 * @return \AuthSMTPQueueEntry|mixed
	 * @throws \ValidationException
	 */
	public function sendPlain( $messageID = null ) {
		if ( $this->useQueue ) {
			$result = AuthSMTPService::enqueue(
				$this->To(),
				$this->Subject(),
				$this->Body(),
				$this->getTemplate(),
				$this->templateData(),
				$this->attachments,
				$this->customHeaders,
				$this->From()
			);
		} else {
			$result = parent::sendPlain( $messageID );
			AuthSMTPService::log(
				$result,
				$this->To(),
				$this->Subject(),
				$this->Body(),
				$this->getTemplate(),
				$this->templateData(),
				$this->attachments,
				$this->customHeaders,
				$this->From()
			);
		}
		return $result;
	}

	public function parseVariables( $isPlain = false ) {
		parent::parseVariables( $isPlain );

		if ($this->inlineStyles) {
			// code taken from email-helpers wrapped into here
			// if it's an html email, filter it through emogrifier
			if ( ! $isPlain && preg_match( '/<style[^>]*>(?:<\!--)?(.*)(?:-->)?<\/style>/ims', $this->body, $match ) ) {
				$css  = $match[1];
				$html = str_replace(
					[
						"<p>\n<table>",
						"</table>\n</p>",
						'&copy ',
						$match[0],
					],
					[
						"<table>",
						"</table>",
						'',
						'',
					],
					$this->body
				);

				$emog       = new Emogrifier( $html, $css );
				$this->body = $emog->emogrify();
			}
		}

		return $this;
	}
}