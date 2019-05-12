<?php

class PeepSoConfigSectionPostbox extends PeepSoConfigSectionAbstract
{
	// Builds the groups array
	public function register_config_groups()
	{
		$this->context='left';
        $this->general();
        $this->location();
        $this->moods();
        $this->tags();

        $this->context='right';
        $this->hashtags();
	}

	function general() {
        // # Default privacy
        $privacy = PeepSoPrivacy::get_instance();
        $privacy_settings = $privacy->get_access_settings();

        $options = array();

        foreach($privacy_settings as $key => $value) {
            $options[$key] = $value['label'];
        }

        $this->args('options', $options);
        $this->args('descript',__('Defines the default starting privacy level for new posts. Users can change it, and the postbox will always remember their last choice.','peepso-core'));

        $this->set_field(
            'activity_privacy_default',
            __('Default post privacy', 'peepso-core'),
            'select'
        );

        // # Maximum size of Post
        $this->args('validation', array('required', 'numeric','minval:500'));
        $this->args('int', TRUE);

        $this->set_field(
            'site_status_limit',
            __('Maximum size of Post', 'peepso-core'),
            'text'
        );

        $this->args('default', 0);
        $this->set_field(
            'scheduled_posts_enable',
            __('Allow non-admins to schedule posts', 'peepso-core'),
            'yesno_switch'
        );

        // # Separator Profile
        $this->set_field(
            'separator_profile',
            __('Profile Posts', 'peepso-core'),
            'separator'
        );


        // # Who can post on "my profile" page
        $privacy = PeepSoPrivacy::get_instance();
        $privacy_settings = $privacy->get_access_settings();

        $options = array();

        foreach($privacy_settings as $key => $value) {
            $options[$key] = $value['label'];
        }

        // Remove site guests & rename "only me"
        unset($options[PeepSo::ACCESS_PUBLIC]);
        $options[PeepSo::ACCESS_PRIVATE] .= __(' (profile owner)', 'peepso-core');

        $this->args('options', $options);

        $this->set_field(
            'site_profile_posts',
            __('Who can post on "my profile" page', 'peepso-core'),
            'select'
        );

        $this->args('default', 1);
        $this->set_field(
            'site_profile_posts_override',
            __('Let users override this setting', 'peepso-core'),
            'yesno_switch'
        );
        // Build Group
        $this->set_group(
            'general',
            __('General', 'peepso-core')
        );
    }
    function location()
    {

        // Enable Location
        $this->set_field(
            'location_enable',
            __('Enabled', 'peepso-core'),
            'yesno_switch'
        );


        ob_start();
        echo __('A Google maps API key is required for the Location suggestions to work properly','peepso-core') . '<br/>' . __('You can get the API key', 'peepso-core'); ?>
        <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">
            <?php _e('here', 'peepso-core');?>
        </a>.

        <?php
        $this->args('descript', ob_get_clean());
        $this->set_field(
            'location_gmap_api_key',
            __('Google Maps API Key (v3)', 'peepso-core'),
            'text'
        );

        $this->set_group(
            'location',
            __('Location', 'peepso-core')
        );
    }

    function moods()
    {

        // Enable Moods
        $this->set_field(
            'moods_enable',
            __('Enabled', 'peepso-core'),
            'yesno_switch'
        );

        $this->set_group(
            'moods',
            __('Moods', 'peepso-core')
        );
    }

    function tags()
    {

        // Enable Tags
        $this->set_field(
            'tags_enable',
            __('Enabled', 'peepso-core'),
            'yesno_switch'
        );


        $this->set_group(
            'moods',
            __('Tags', 'peepso-core')
        );
    }

    private function hashtags()
    {
        // Enable Tags
        $this->args('default', 1);
        $this->set_field(
            'hashtags_enable',
            __('Enabled', 'peepso-core'),
            'yesno_switch'
        );


        $this->set_field(
            'hashtags_performance_separator',
            __('Maintenance cron','peepso-core'),
            'separator'
        );

        // Hashtag  post count refresh rate
        $options = array();
        for($i=5;$i<=60;$i+=5) {
            $options[$i]= "$i " . __('minutes', 'peepso-core');
            if(60==$i) { $options[$i].= ' ' . __('(default)','peepso-core'); }
        }

        $this->args('options', $options);
        $this->args('default', 60);
        $this->args('descript', __('Deleted and edited posts are checked periodically to update post counts for each hashtag.', 'peepsohashtag').'<br/>'.__('Smaller delay means more database load.', 'peepso-core'));

        $this->set_field(
            'hashtags_post_count_interval',
            __('Update post count in tags every', 'peepso-core'),
            'select'
        );


        // Hashtag  post count refresh rate
        $options = array();

        for($i=5;$i<=100;$i+=5) {
            $options[$i]= "$i " . __('entries', 'peepso-core');
            if(5==$i) { $options[$i].= ' ' . __('(default)','peepso-core'); }
        }

        $this->args('options', $options);
        $this->args('default', 5);
        $this->args('descript', __('How many posts and hashtags to process when the maintenance scripts are ran.', 'peepsohashtag').'<br/>'.__('Bigger batches mean faster updates, but generate higher load.', 'peepso-core'));

        $this->set_field(
            'hashtags_post_count_batch_size',
            __('Process', 'peepso-core'),
            'select'
        );

        // Delete empty hashtags
        $this->args('default', 1);
        $this->args('descript', __('When enabled, hashtags with zero posts will be deleted and not shown in the widget or suggestions. ', 'peepso-core').'<br/>'.__('Hashtags with zero posts can occur, when posts are deleted or edited.','peepso-core'));

        $this->set_field(
            'hashtags_delete_empty',
            __('Delete empty hashtags', 'peepso-core'),
            'yesno_switch'
        );

        $this->set_field(
            'hashtags_advanced_separator',
            __('Advanced','peepso-core'),
            'separator'
        );

        $this->args('default', 0);
        $this->args('descript',
            __('This feature is currently in BETA and should be used with caution.','peepso-core')
            . '<br>'
            . __('Enables hashtags of any length, with any content, including non-alphanumeric characters (Arabic, Japanese, Korean, Cyrillic, Emoji, etc). Hashtags MUST end with a space, line break or another hashtag.','peepso-core')
        );

        $this->set_field(
            'hashtags_everything',
            __('Allow non-alphanumeric hashtags', 'peepso-core'),
            'yesno_switch'
        );


        $options = array();
        for($i=1;$i<=5;$i++) {
            $options[$i]= "$i " . _n('character','characters', $i,'peepso-core');
            if(3==$i) { $options[$i].= ' ' . __('(default)','peepso-core'); }
        }

        $this->args('options', $options);
        $this->args('default', 3);
        $this->args('descript', __('Shorter hashtags will be ignored', 'peepsohashtag'));

        $this->set_field(
            'hashtags_min_length',
            __('Minimum hashtag length', 'peepso-core'),
            'select'
        );


        // Hashtag  post count refresh rate
        $options = array();

        for($i=5;$i<=64;$i++) {
            $options[$i]= "$i " . __('characters','peepso-core');
            if(16==$i) { $options[$i] .= ' ' . __('(default)','peepso-core'); }
        }

        $this->args('options', $options);
        $this->args('default', 16);
        $this->args('descript', __('Longer hashtags will be ignored', 'peepso-core'));

        $this->set_field(
            'hashtags_max_length',
            __('Maximum hashtag length', 'peepso-core'),
            'select'
        );

        // Start with letter
        $this->args('default', 0);
        $this->args('descript', __('Enabled: hashtags beginning with a number will be ignored','peepso-core'));

        $this->set_field(
            'hashtags_must_start_with_letter',
            __('Hashtags must start with a letter', 'peepso-core'),
            'yesno_switch'
        );

        // Rebuild
        $this->args('default', 0);
        $this->args('descript', __('Enable and click "save" to force a hashtag cache rebuild.','peepso-core').'<br/>'.__('It will also happen automatically after changing any of the settings above.','peepso-core'));

        $this->set_field(
            'hashtags_rebuild',
            __('Reset and rebuild the hashtags cache', 'peepso-core'),
            'yesno_switch'
        );


        $this->set_group(
            'peepso_hashtags_performance',
            __('Hashtags', 'peepso-core')
        );
    }


}
?>
