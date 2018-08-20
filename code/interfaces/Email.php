<?php

interface AuthSMTPEmailInterface {

	/**
	 * Write this message to the AuthSMTPQueue for sending later if enqueue is true, otherwise send and log a message
	 *
	 * @param null $messageID
	 *
	 * @return mixed
	 */
	public function send($messageID = null);

	/**
	 * Write this message to the AuthSMTPQueue for sending later if enqueue is true, otherwise send and log a message
	 *
	 * @param null $messageID
	 *
	 * @return mixed
	 */
	public function sendPlain( $messageID = null);
	/**
	 * Configure to use queue or not when it is sent.
	 *
	 * @param bool $enable
	 *
	 * @return $this
	 */
	public function setQueueing($enable);
	/**
	 * Attach the specified file to this email message.
	 *
	 * @param string      $filename         Relative or full path to file you wish to attach to this email message.
	 * @param string|null $attachedFilename Name of the file that should appear once it's sent as a separate attachment.
	 * @param string|null $mimeType         MIME type to use when attaching file. If not provided, will attempt to infer via HTTP::get_mime_type().
	 *
	 * @return $this
	 */
	public function attachFile( $filename, $attachedFilename = null, $mimeType = null );

	/**
	 * @return string|null
	 */
	public function Subject();

	/**
	 * @return string|null
	 */
	public function Body();

	/**
	 * @return string|null
	 */
	public function To();

	/**
	 * @return string|null
	 */
	public function From();

	/**
	 * @return string|null
	 */
	public function Cc();

	/**
	 * @return string|null
	 */
	public function Bcc();

	/**
	 * @param string $val
	 *
	 * @return $this
	 */
	public function setSubject( $val );

	/**
	 * @param string $val
	 *
	 * @return $this
	 */
	public function setBody( $val );

	/**
	 * @param string $val
	 *
	 * @return $this
	 */
	public function setTo( $val );

	/**
	 * @param string $val
	 *
	 * @return $this
	 */
	public function setFrom( $val );

	/**
	 * @param string $val
	 *
	 * @return $this
	 */
	public function setCc( $val );

	/**
	 * @param string $val
	 *
	 * @return $this
	 */
	public function setBcc( $val );

	/**
	 * Set the "Reply-To" header with an email address.
	 *
	 * @param string $val The email address of the "Reply-To" header
	 *
	 * @return $this
	 */
	public function setReplyTo( $val );

	/**
	 * @param string $email
	 *
	 * @return $this
	 * @deprecated 4.0 Use the "setReplyTo" method instead
	 */
	public function replyTo( $email );

	/**
	 * Add a custom header to this email message. Useful for implementing all those cool features that we didn't think of.
	 *
	 * IMPORTANT: If the specified header already exists, the provided value will be appended!
	 *
	 * @todo Should there be an option to replace instead of append? Or maybe a new method ->setCustomHeader()?
	 *
	 * @param string $headerName
	 * @param string $headerValue
	 *
	 * @return $this
	 */
	public function addCustomHeader( $headerName, $headerValue );

	/**
	 * Set template name (without *.ss extension).
	 *
	 * @param string $template
	 *
	 * @return $this
	 */
	public function setTemplate( $template );

	/**
	 * @return string
	 */
	public function getTemplate();


}