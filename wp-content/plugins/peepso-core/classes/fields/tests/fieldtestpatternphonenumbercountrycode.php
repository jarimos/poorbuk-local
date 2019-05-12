<?php

class PeepSoFieldTestPatternPhoneNumberCountryCode extends PeepSoFieldTestAbstract
{

	public function __construct($value)
	{
		parent::__construct($value);

		$this->admin_label 	= __('Must start with a country code', 'peepso-core');
		$this->admin_type 	= 'checkbox';

		$this->message 		= __('Please include the country code, for example +48', 'peepso-core');
	}

	public function test()
	{

		if ( strlen($this->value) && '+' != substr($this->value,0,1)) {

			$this->error = $this->message;

			return FALSE;
		}

		return TRUE;
	}

}