<?php

/**
 * AuthSMTPConfig class for manual configuration if project doesn't use Injector and SmtpMailer configuration directly to setup AuthSMTPService
 */
class AuthSMTPConfig extends Object {
	const EmailGlobalDefine = 'SS_SEND_ALL_EMAILS_FROM';
	const TestRecipient = 'servers+authsmtp-test@under-development.co.nz';

	private static $host; // = 'mail.authsmtp.com';
	private static $port; // = 2525;
	private static $user;
	private static $password;
	private static $from;
	private static $tls = true;
	private static $charset = 'UTF-8';
	private static $queue = false;

	private static $configurable_options = ['host', 'port', 'user', 'password', 'from', 'tls', 'charset', 'queue'];

	/**
	 * Call this method to configure the SilverStripe Mail class to use SmtpMailer class as it's default Mailer using either provided
	 * options or AuthSMTPConfig.config values.
	 *
	 * @param array $overrideConfig options which override config values
	 * @return array $options used to configure SmtpMailer as figured out from provided and config.
	 */

	public static function configure(array $overrideConfig = []) {
		$options = static::options($overrideConfig);

		if ($options['from']) {
			if (!defined(self::EmailGlobalDefine)) {
				define(self::EmailGlobalDefine, $options['from']);
			}
		}
		if (!defined(self::EmailGlobalDefine)) {
			user_error("No '" . self::EmailGlobalDefine . "' defined, can't continue configuring AuthSMTP.", E_USER_ERROR);
			return null;
		}
		$mailer = new SmtpMailer(
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
	 * Merge configurable options from self.config in with passed options, passed options take precedence.
	 *
	 * @param array $overrideConfig options which override config values
	 * @return array
	 */
	public static function options(array $overrideConfig = []) {
		$config = static::config();
		$configurableOptions = static::config()->get('configurable_options');

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
	 * Attempts to send an email to self.TestRecipient with a null 'from' sender. Tests port first then sends email. Expects AuthSMTPConfig::configure to have
	 * been run already e.g. in app/_config.php, though options for explicit calls can be overridden via passed array.
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
				"Testing authsmtp from '$server'",
				$body
			);
			$email->sendPlain();

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
			user_error("Not host or port set, can't continue port test", E_USER_ERROR);
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
