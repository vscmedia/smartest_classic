<?php

class SmartestAssetUpdateHelper{

    public function updateAssetFromParameters(SmartestAsset $asset, array $params, $user, array $options=array()){

        $preserve_absent_metadata = isset($options['preserve_absent_metadata']) ? (bool) $options['preserve_absent_metadata'] : false;

        if(!$this->userCanModifyAsset($asset, $user)){
            return array(
                'success' => false,
                'message' => "You don't have permission to edit assets created by other users.",
                'message_type' => SmartestUserMessage::WARNING
            );
        }

        $filter = $asset->isEditable() && (!isset($params['filter_markup']) || (bool) $params['filter_markup']);

        if(!$preserve_absent_metadata || isset($params['params'])){
            $param_values = isset($params['params']) ? $params['params'] : '';
            $asset->setParameterDefaults(serialize($param_values));
        }

        if($asset->isEditable()){
            $content = isset($params['asset_content']) ? $params['asset_content'] : '';
            $content = mb_convert_encoding($content, 'UTF-8');
        }else{
            $content = null;
        }

        if($filter){
            $content = SmartestStringHelper::unProtectSmartestTags($content);
            $content = SmartestTextFragmentCleaner::convertDoubleLineBreaks($content);
            $update_success = $asset->setContentFromEditor($content);
        }else{
            if($asset->isEditable()){
                $asset->setContent($content);
            }
            $update_success = true;
        }

        if(!$preserve_absent_metadata || isset($params['asset_language'])){
            $asset->setLanguage(strtolower(substr(isset($params['asset_language']) ? $params['asset_language'] : '', 0, 3)));
        }

        $asset->setModified(time());
        $asset->save();

        if($update_success){
            return array(
                'success' => true,
                'message' => 'The file has been successfully updated.',
                'message_type' => SmartestUserMessage::SUCCESS
            );
        }

        if($asset->usesTextFragment()){
            return array(
                'success' => false,
                'message' => 'The file "'.$asset->getLabel().'" could not be updated because of illegal characters in the submitted text that prevented validation parsing.',
                'message_type' => SmartestUserMessage::WARNING
            );
        }

        return array(
            'success' => false,
            'message' => 'The file could not be updated because of file permissions while writing to disk.',
            'message_type' => SmartestUserMessage::WARNING
        );

    }

    protected function userCanModifyAsset(SmartestAsset $asset, $user){

        if(!is_object($user)){
            return false;
        }

        return (method_exists($user, 'hasToken') && $user->hasToken('modify_assets'))
            || (method_exists($user, 'getId') && $asset->getUserId() == $user->getId());

    }

}
