<?php
/*-------------------------------------------------------+
| PHPFusion Content Management System
| Copyright (C) PHP Fusion Inc
| https://phpfusion.com/
+--------------------------------------------------------+
| Filename: theme.php
| Author: Meangczac (Chan)
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/

/**
 * @param $part
 *
 * @return string
 */
function get_bootstrap( $part, $version = '3' ) {
    static $framework_paths = [];

    if (empty( $framework_paths )) {
        if ($version < 3) {
            $version = 3;
        } elseif ($version > 5) {
            $version = 5;
        }

        $version = 'v' . $version;

        require_once __DIR__ . '/' . $version . '/index.php';

        $framework_paths = [
            'showsublinks' => ['dir' => __DIR__ . '/' . $version . '/', 'file' => 'navbar.twig'],
            'form_inputs'   => ['dir' => __DIR__ . '/' . $version . '/', 'file' => 'dynamics.twig']
        ];
    }

    return $framework_paths[$part] ?? '';

}

/**
 * @param $component
 * @param $info
 *
 * @return string
 */
function fusion_get_template( $component, $info ) {

    if ($path = get_bootstrap( $component )) {

        if ($component == 'text_input' && $info['input_name'] == 'user_name') {
//            print_p($info, 1);
        }

        return fusion_render( $path['dir'], $path['file'], $info, TRUE );
    }

    return 'This template ' . $component . ' is not supported';
}


if (defined( 'BOOTSTRAP' )) {

    /**
     * Load bootstrap
     */
    get_bootstrap( 'load', BOOTSTRAP );

    /**
     * @uses bootstrap_header()
     */
    fusion_add_hook( 'fusion_header_include', 'bootstrap_header' );

    /**
     * @uses bootstrap_footer()
     */
    fusion_add_hook( 'fusion_footer_include', 'bootstrap_footer' );
}
