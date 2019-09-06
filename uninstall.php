<?php

//if not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

delete_option('ccs_content_settings');

// TODO: Deprecate or use more specific key
delete_option('custom-gallery');
