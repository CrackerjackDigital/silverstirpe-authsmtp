<?php

class AuthSMTPTestQueueTask extends BuildTask {
	/**
	 * Tries to send an email with the authsmtp options set as per config and environment to self.Recipient email address.
	 * @param $request
	 */
	public function run($request) {
		AuthSMTPService::test_queued_send();
	}
}