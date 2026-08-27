<?php

class DesktopAjax extends SmartestSystemApplication{

    public function buildKitOptions(){

        $buildkits = SmartestBuildKitUtilities::getAvailableBuildKits();
        $buildkit_id = $this->getRequestParameter('buildkit_id');

        if(isset($buildkits[$buildkit_id])){

            $buildkit = $buildkits[$buildkit_id];

            $content_options = $buildkit->getContentConfigurationOptions();
            $template_options = $buildkit->getTemplatesConfigurationOptions();
            $data_structure_options = $buildkit->getDataStructureConfigurationOptions();
            $page_structure_options = $buildkit->getPageStructureConfigurationOptions();
            $general_options = $buildkit->getMainConfigurationOptions();
            $unwritable_locations = $buildkit->getUnwritableRequiredWriteLocations();

            $this->send($general_options, 'general_configuration_options');
            $this->send((bool) count($general_options), 'has_general_configuration_options');

            $this->send($buildkit->getContentIsEnabled(), 'buildkit_content_available');
            $this->send($buildkit->getContentIsRequired(), 'buildkit_content_required');
            $this->send((bool) count($content_options), 'buildkit_content_configurable');
            $this->send($content_options, 'buildkit_content_options');

            $this->send($buildkit->getTemplatesAreEnabled(), 'buildkit_templates_available');
            $this->send($buildkit->getTemplatesAreRequired(), 'buildkit_templates_required');
            $this->send((bool) count($template_options), 'buildkit_templates_configurable');
            $this->send($template_options, 'buildkit_templates_options');

            $this->send($buildkit->getDataStructureIsEnabled(), 'buildkit_datastructure_available');
            $this->send($buildkit->getDataStructureIsRequired(), 'buildkit_datastructure_required');
            $this->send((bool) count($data_structure_options), 'buildkit_datastructure_configurable');
            $this->send($data_structure_options, 'buildkit_datastructure_options');

            $this->send($buildkit->getPageStructureIsEnabled(), 'buildkit_pagestructure_available');
            $this->send($buildkit->getPageStructureIsRequired(), 'buildkit_pagestructure_required');
            $this->send((bool) count($page_structure_options), 'buildkit_pagestructure_configurable');
            $this->send($page_structure_options, 'buildkit_pagestructure_options');

            $this->send($unwritable_locations, 'unwritable_locations');
            $this->send((bool) count($unwritable_locations), 'has_unwritable_locations');

        }else{

            $this->send(array(), 'general_configuration_options');
            $this->send(false, 'has_general_configuration_options');
            $this->send(false, 'buildkit_content_available');
            $this->send(false, 'buildkit_content_required');
            $this->send(false, 'buildkit_content_configurable');
            $this->send(array(), 'buildkit_content_options');
            $this->send(false, 'buildkit_templates_available');
            $this->send(false, 'buildkit_templates_required');
            $this->send(false, 'buildkit_templates_configurable');
            $this->send(array(), 'buildkit_templates_options');
            $this->send(false, 'buildkit_datastructure_available');
            $this->send(false, 'buildkit_datastructure_required');
            $this->send(false, 'buildkit_datastructure_configurable');
            $this->send(array(), 'buildkit_datastructure_options');
            $this->send(false, 'buildkit_pagestructure_available');
            $this->send(false, 'buildkit_pagestructure_required');
            $this->send(false, 'buildkit_pagestructure_configurable');
            $this->send(array(), 'buildkit_pagestructure_options');
            $this->send(array(), 'unwritable_locations');
            $this->send(false, 'has_unwritable_locations');
        }
    }

}
