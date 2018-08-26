<?php

/**
 *  Email queuing extension for Email
 */
class AuthSMTPEmail extends StyledHtmlEmail {
	/**
	 * We need to expose the data for queuing to work
	 * @return $this|array
	 */
	public function getTemplateData() {
		return $this->templateData();
	}

	/**
	 * @param array|\ViewableData $data
	 *
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function setTemplateData($data) {
		if ($this->template_data) {
			$this->template_data->customise( $data);
		} else {
			if (is_array( $data)) {
				$this->template_data = new ArrayObject($data);
			} elseif ($data instanceof ViewableData) {
				$this->template_data = $data;
			}
		}

		return $this;
	}

	/**
	 * Expose this
	 * @return array
	 */
	public function getAttachments() {
		return $this->attachments;
	}

	/**
	 * Expose this
	 *
	 * @return array
	 */
	public function getCustomHeaders() {
		return $this->customHeaders;
	}
}