<?php

class AuthSMTPQueueTask extends BuildTask {

	protected $title = 'AuthSMTP Send email in queue';
	protected $description = 'Send available Email in queue';

	protected $enabled = true;
	/**
	 * Process all queued emails
	 * @param $request
	 */
	public function run($request) {
		set_time_limit(0);
		AuthSMTPQueueEntryModel::process_queue();
	}
}