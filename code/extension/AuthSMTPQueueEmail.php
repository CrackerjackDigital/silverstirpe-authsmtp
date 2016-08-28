<?php

/**
 *  Email queuing extension for Email
 */
class AuthSMTPQueueEmail extends Email {

	public function queueOrSend() {
		$queue = AuthSMTPConfig::config()->get('queue');
		if (!$queue) {
			$this->send();
		}

		AuthSMTPQueueModel::addMessage(
			$this->To(),
			$this->Subject(),
			$this->Body(),
			$this->getTemplate(),
			$this->template_data,
			$this->attachments,
			$this->customHeaders
		);

		return true;
	}
}