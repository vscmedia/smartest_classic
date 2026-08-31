<?php

function buildkit_sm_blank_site_setup_content($buildkit, $site, $user, $options){

    $navstripe_palettes = array(
        array(
            'background' => 'linear-gradient(135deg, #6AA9FC 0%, #E344CD 100%)',
            'accent' => '#6AA9FC',
            'soft' => '#eef6ff'
        ),
        array(
            'background' => 'linear-gradient(135deg, #E344CD 0%, #F54E4E 100%)',
            'accent' => '#E344CD',
            'soft' => '#fff0fb'
        ),
        array(
            'background' => 'linear-gradient(135deg, #F7A423 0%, #F54E4E 100%)',
            'accent' => '#F7A423',
            'soft' => '#fff7e5'
        ),
        array(
            'background' => 'linear-gradient(135deg, #F54E4E 0%, #E344CD 100%)',
            'accent' => '#F54E4E',
            'soft' => '#fff0f0'
        ),
        array(
            'background' => 'linear-gradient(135deg, #6AA9FC 0%, #F7A423 100%)',
            'accent' => '#6AA9FC',
            'soft' => '#eef6ff'
        )
    );

    $palette = $navstripe_palettes[array_rand($navstripe_palettes)];
    $theme_css = "--sm-navstripe-background: ".$palette['background'].";\n".
        "  --sm-accent: ".$palette['accent'].";\n".
        "  --sm-accent-soft: ".$palette['soft'].";";

    register_value(date('r'), 'time');
    register_value($theme_css, 'colour');

    $css_filename = SmartestStringHelper::toVarName($site->getName()).'.css';
    $css = create_file('default.css', 'Main CSS file for '.$site->getName(), 'SM_ASSETTYPE_STYLESHEET', $css_filename);
    register_file($css, 'main_css_file');

}
