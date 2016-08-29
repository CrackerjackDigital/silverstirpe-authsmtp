<?php

/**
 * AuthSMTPService class for manual configuration if project doesn't use Injector and SmtpMailer configuration directly to setup AuthSMTPService
 */
class AuthSMTPService extends Object {
	const EmailGlobalDefine     = 'SS_SEND_ALL_EMAILS_FROM';
	const TestRecipient         = 'servers+authsmtp-test@under-development.co.nz';
	const LogRecipient          = 'servers+authsmtp-info@under-development.co.nz';
	const ErrorRecipient        = 'servers+authsmtp-errors@moveforward.co.nz';
	const DefaultSendWindowSize = 2;

	private static $host; // = 'mail.authsmtp.com';
	private static $port; // = 2525;
	private static $user;
	private static $password;
	private static $from;
	private static $tls = true;
	private static $charset = 'UTF-8';

	// if queue implemented, how many messages to process at a time
	private static $send_window_size;

	// only these options will be passed to the mailer from configuration
	private static $mailer_options = ['host', 'port', 'user', 'password', 'from', 'tls', 'charset'];

	/**
	 * Call this method to configure the SilverStripe Mail class to use SmtpMailer class as it's default Mailer using either provided
	 * options or AuthSMTPService.config values.
	 *
	 * @param array $overrideConfig options which override config values
	 * @return array $options used to configure SmtpMailer as figured out from provided and config.
	 */

	public static function configure(array $overrideConfig = []) {
		$options = static::options($overrideConfig);

		// setup email logging
		SS_Log::add_writer(new SS_LogEmailWriter(static::LogRecipient), static::log_level());

		if ($options['from']) {
			if (!defined(self::EmailGlobalDefine)) {
				define(self::EmailGlobalDefine, $options['from']);
			}
		}
		if (!defined(self::EmailGlobalDefine)) {
			user_error("No '" . self::EmailGlobalDefine . "' defined, can't continue configuring AuthSMTP.", E_USER_ERROR);
			return null;
		}
		$mailer = new AuthSMTPMailer(
			$options['host'] . ':' . $options['port'],
			$options['user'],
			$options['password'],
			$options['tls'],
			$options['charset']
		);
		Injector::inst()->registerService($mailer, 'Mailer');
		Email::set_mailer($mailer);

		return $options;
	}

	/**
	 * Updates model if provided to StatusFailed and send an error via a new SmtpMailer.
	 *
	 * If we are sending an error then die after sending using a default SMTP mailer to use so we don't end in a loop, and send to self.ErrorRecipient.
	 *
	 * This own't of course work if we can't send emails at all, but better than nothing.
	 *
	 * NB: dies after sending an error!
	 *
	 * @param                     $message
	 * @param \AuthSMTPQueueModel $emailModel
	 * @throws \ValidationException
	 * @throws null
	 */
	public static function error($message, AuthSMTPQueueModel $emailModel) {
		if ($emailModel instanceof DataObject) {
			$emailModel->update([
				'Status' => AuthSMTPQueueModel::StatusFailed,
				'Result' => $message
			])->write();
		}
		$options = static::options(['from' => 'servers@moveforward.co.nz']);
		Email::set_mailer(new SmtpMailer(
			$options['host'] . ':' . $options['port'],
			$options['user'],
			$options['password'],
			true,
			'UTF-8'
		));
		SS_Log::add_writer(new SS_LogEmailWriter(static::ErrorRecipient), SS_Log::ERR);
		Config::inst()->update('Email', 'send_all_emails_to', static::ErrorRecipient);
		SS_Log::log($message, SS_Log::ERR);
		throw new Exception($message);
	}

	/**
	 * Log an info messsage via normal paths
	 *
	 * @param $message
	 */
	public static function info_message($message) {
		SS_Log::log($message, SS_Log::INFO);
	}

	public static function log_level() {
		return Config::inst()->get(get_called_class(), 'log_level');
	}

	/**
	 * Return how many messages should be processed at a time, e.g. via queueing mechanism.
	 * Settable via config.send_window_size or returns self.DefaultSendWindowSize.
	 *
	 * @return int
	 */
	public static function send_window_size() {
		return static::config()->get('send_window_size') ?: static::DefaultSendWindowSize;
	}

	/**
	 * Merge configurable options from self.config in with passed options, passed options take precedence.
	 *
	 * @param array $overrideConfig options which override config values
	 * @return array
	 */
	public static function options(array $overrideConfig = []) {
		$config = static::config();
		$configurableOptions = static::config()->get('mailer_options');

		return array_merge(
			array_combine(
				$configurableOptions,
				array_map(
					function ($key) use ($config) {
						return $config->get($key);
					},
					$configurableOptions
				)
			),
			$overrideConfig
		);
	}

	/**
	 * Attempts to send an email to self.TestRecipient with a null 'from' sender. Tests port first then sends email. Expects AuthSMTPService::configure to have
	 * been run already e.g. in app/_config.php, though options for test calls can be overridden via passed array.
	 *
	 * @param array $overrideConfig options which override config values
	 * @return array
	 */
	public static function test_send(array $overrideConfig = []) {
		if (!Director::is_cli()) {
			ob_start('nl2br');
		}
		if ($options = static::test_port($overrideConfig)) {
			$to = self::TestRecipient;

			$server = Director::protocolAndHost();

			$body = "Options:\n";
			foreach ($options as $key => $value) {
				$body .= "$key:\t\t\t$value\n";
			}

			$email = new Email(
				null,
				$to,
				"Testing direct send authsmtp from '$server'",
				$body
			);
			$email->sendPlain();

			echo "Check for email sent to '$to' should contain:\n\n$body";
		}

		return $options;
	}

	/**
	 * Attempts to send an email to self.TestRecipient with a null 'from' sender. Tests port first then sends email. Expects AuthSMTPService::configure to have
	 * been run already e.g. in app/_config.php, though options for test calls can be overridden via passed array.
	 *
	 * @param array $overrideConfig options which override config values
	 * @return array
	 */
	public static function test_queued_send(array $overrideConfig = []) {
		if (!Director::is_cli()) {
			ob_start('nl2br');
		}
		if ($options = static::test_port($overrideConfig)) {
			$to = self::TestRecipient;

			$server = Director::protocolAndHost();

			$body = "Options:\n";
			foreach ($options as $key => $value) {
				$body .= "$key:\t\t\t$value\n";
			}

			AuthSMTPQueueModel::addMessage(
				$to,
				"Testing queued mail via authsmtp from '$server'",
				$body,
				'',
				serialize(['Name' => 'Fred'])
			);
			AuthSMTPQueueModel::processQueue();

			echo "Check for email sent to '$to' should contain:\n\n$body";
		}

		return $options;
	}

	/**
	 * Uses values from options to attempt to open a raw socket to the configured host:port. Does not call configure.
	 *
	 * @param array $overrideConfig use these options to override/test other settings for host/port
	 * @return array
	 */
	public static function test_port(array $overrideConfig = []) {
		$options = static::options($overrideConfig);

		if (!isset($options['host']) || !isset($options['port'])) {
			user_error("No host or port set, can't continue port test", E_USER_ERROR);
			return [];
		}
		$fp = @fsockopen($options['host'], $options['port'], $errno, $errstr, 5);
		if (!$fp) {
			user_error("Looks like port '" . $options['port'] . "' is not open to connect to host '" . $options['host'] . "'. $errstr", E_USER_ERROR);
			return [];
		} else {
			// port is open and available
			fclose($fp);
		}
		return $options;
	}

}
