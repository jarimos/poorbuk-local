<?php

$submissions = FALSE;

if(PeepSo::get_option('blogposts_submissions_enable', class_exists( 'CMUserSubmittedPosts' ))) { $submissions = TRUE; }
if(PeepSo::get_option('blogposts_submissions_enable_usp', defined('USP_VERSION')))                  { $submissions = TRUE; }


if($submissions) {

    $PeepSoUser = PeepSoUser::get_instance();
    $pro = PeepSoProfileShortcode::get_instance();

    if (PeepSoUrlSegments::get_view_id($pro->get_view_user_id()) == get_current_user_id()) {
        ?>
        <div class="ps-tabs__wrapper">
            <div class="ps-tabs ps-tabs--arrows">
                <div class="ps-tabs__item <?php if (!$create_tab) echo "current"; ?>"><a
                            href="<?php echo $PeepSoUser->get_profileurl() . 'blogposts/'; ?>"><?php _e('View', 'peepso-core'); ?></a>
                </div>
                <div class="ps-tabs__item <?php if ($create_tab) echo "current"; ?>"><a
                            href="<?php echo $PeepSoUser->get_profileurl() . 'blogposts/create/'; ?>"><?php _e('Create', 'peepso-core'); ?></a>
                </div>
            </div>
        </div>

        <?php
    }
}