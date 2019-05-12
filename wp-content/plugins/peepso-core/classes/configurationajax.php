<?php

class PeepSoConfigurationAjax extends PeepSoAjaxCallback
{
    public function set(PeepSoAjaxResponse $resp)
    {
        if(!PeepSo::is_admin()) {
            $resp->error('Access denied');
            return;
        }

        $key = $this->_input->val('key', '');
        $value = $this->_input->val('value', '');

        if(!strlen($key) || !strlen($value)) {
            $resp->error('Invalid Arguments');
            return;
        }

        $PeepSoConfigSettings = PeepSoConfigSettings::get_instance();
        $PeepSoConfigSettings->set_option($key, $value);

        $resp->success(TRUE);

    }

    public function remove(PeepSoAjaxResponse $resp)
    {
        if(!PeepSo::is_admin()) {
            $resp->error('Access denied');
            return;
        }

        $key = $this->_input->val('key', '');

        if(!strlen($key)) {
            $resp->error('Invalid Arguments');
            return;
        }

        $PeepSoConfigSettings = PeepSoConfigSettings::get_instance();
        $PeepSoConfigSettings->remove_option($key);

        $resp->success(TRUE);

    }
}
