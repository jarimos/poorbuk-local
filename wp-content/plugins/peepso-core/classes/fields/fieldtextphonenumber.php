<?php

class PeepSoFieldTextPhoneNumber extends PeepSoFieldText
{
    public static $order = 800;
	public static $admin_label='Phone number';

	public function __construct($post, $user_id)
	{
		$this->field_meta_keys = array_merge($this->field_meta_keys, $this->field_meta_keys_extra);
		parent::__construct($post, $user_id);

		$this->default_desc = __('What\'s your phone number?', 'peepso-core');

        // Remove inherited text area / multiline and MarkDown rendering
        unset($this->render_form_methods['_render_form_textarea']);
        unset($this->render_methods['_render_md']);

		// Add an option to render as <a href>
		#$this->render_methods['_render_link'] = __('clickable link', 'peepso-core');

		// Remove inherited length validators
		#$this->validation_methods = array_diff($this->validation_methods, array('lengthmax','lengthmin'));
		$this->validation_methods[] = 'patternphonenumbercountrycode';
    }

    public function save($value, $validate_only = FALSE) {

	    // clean up non-numbers and preserve the leading + sign
        $plus='';
        if('+' == substr($value,0,1)) {
            $plus='+';
        }

        $value = $plus . preg_replace('/[^0-9]/', '', $value);

        return parent::save($value, $validate_only);
    }
}