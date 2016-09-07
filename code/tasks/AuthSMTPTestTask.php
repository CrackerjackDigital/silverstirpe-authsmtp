<?php

class AuthSMTPTestTask extends BuildTask {
	/**
	 * Tries to send an email with the authsmtp options set as per config and environment to self.Recipient email address.
	 * If you're logged in then override config can be passed on QueryString
	 *
	 * @param SS_HTTPRequest $request
	 */
	public function run($request) {
		if (Member::currentUserID()) {
			AuthSMTPService::test_send($request->getVars());
		} else {
			AuthSMTPService::test_send();
		}
	}
}