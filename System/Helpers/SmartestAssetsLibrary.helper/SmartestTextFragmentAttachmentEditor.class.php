<?php

class SmartestTextFragmentAttachmentEditor{

    public function getAttachmentData($asset_id, $attachment_name, $user){

        if($attachment_context = $this->getAttachmentContext($asset_id, $attachment_name, $user)){
            return $this->buildAttachmentData(
                $attachment_context['asset'],
                $attachment_context['textfragment'],
                $attachment_context['attachment_name'],
                $attachment_context['attachment']
            );
        }

        return false;

    }

    public function getAttachmentContext($asset_id, $attachment_name, $user){

        $attachment_name = SmartestStringHelper::toVarName($attachment_name);

        if(!$asset_id || !strlen($attachment_name)){
            return false;
        }

        $asset = new SmartestAsset;

        if(!$asset->find($asset_id)){
            return false;
        }

        if(!$this->userCanModifyAsset($asset, $user)){
            return false;
        }

        $textfragment = $asset->getTextFragment();

        if(!is_object($textfragment)){
            return false;
        }

        return array(
            'asset' => $asset,
            'textfragment' => $textfragment,
            'attachment_name' => $attachment_name,
            'attachment' => $textfragment->getAttachmentCurrentDefinition($attachment_name)
        );

    }

    public function buildAttachmentData(SmartestAsset $asset, SmartestTextFragment $textfragment, $attachment_name, SmartestTextFragmentAttachment $attachment){

        $attached_asset = $attachment->getAsset();
        $has_attached_asset = is_object($attached_asset) && $attached_asset->getId();
        $attached_asset_is_image = $has_attached_asset && $attached_asset->isBinaryImage();
        $thumbnail_url = '';
        $modal_thumbnail_url = '';
        $width = null;
        $height = null;
        $image_is_resized = false;
        $resize_percentage = null;
        $resized_width = null;
        $resized_height = null;
        $resize_label = '';
        $preview_kind = 'empty';
        $preview_label = '';
        $preview_badge = '';

        if($has_attached_asset){
            $width = $attached_asset->getWidth();
            $height = $attached_asset->getHeight();
            $type_info = $attached_asset->getTypeInfo();
            $preview_label = is_array($type_info) && isset($type_info['label']) ? $type_info['label'] : 'Attached file';

            if($attached_asset_is_image){
                $preview_kind = 'image';
                $preview_badge = 'IMAGE';
                $image = $attached_asset->getImage();
                if(is_object($image)){
                    $thumbnail = $image->getConstrainedVersionWithin(180, 120);
                    if(is_object($thumbnail)){
                        $thumbnail_url = $thumbnail->getWebPath();
                    }
                    $modal_thumbnail = $image->getSquareVersion(96);
                    if(is_object($modal_thumbnail)){
                        $modal_thumbnail_url = $modal_thumbnail->getWebPath();
                    }
                }
                if(!strlen($modal_thumbnail_url)){
                    $modal_thumbnail_url = $thumbnail_url;
                }

                $image_is_resized = SmartestStringHelper::toRealBool($attachment->getResizeImageResizeFlag());
                if($image_is_resized && is_numeric($width) && is_numeric($height) && $width > 0 && $height > 0){
                    $resize_percentage = (int) $attachment->getThumbnailRelativeSize();
                    if($resize_percentage < 1){
                        $resize_percentage = 10;
                    }
                    $resized_width = ceil($resize_percentage/100*$width);
                    $resized_height = ceil($resize_percentage/100*$height);
                    $resize_label = $resize_percentage.'% ('.$resized_width.'x'.$resized_height.'px)';
                }else{
                    $image_is_resized = false;
                }
            }elseif($attached_asset->getType() == 'SM_ASSETTYPE_OEMBED_URL'){
                $preview_kind = 'oembed';
                $preview_badge = 'OEMBED';
                if($service = $attached_asset->getOEmbedService()){
                    $preview_label = $service->getParameter('label');
                }
            }elseif($attached_asset->getType() == 'SM_ASSETTYPE_HTML_FRAGMENT'){
                $preview_kind = 'embed';
                $preview_badge = 'EMBED';
            }else{
                $preview_kind = 'file';
                $preview_badge = 'FILE';
            }
        }

        $alignment = $attachment->getAlignment();
        if(!in_array($alignment, array('left', 'center', 'right'))){
            $alignment = 'left';
        }

        $caption_alignment = $attachment->getCaptionAlignment();
        if(!in_array($caption_alignment, array('left', 'center', 'right'))){
            $caption_alignment = 'left';
        }

        return array(
            'asset_id' => (int) $asset->getId(),
            'textfragment_id' => (int) $textfragment->getId(),
            'attachment_name' => $attachment_name,
            'has_definition' => (bool) $attachment->getTextFragmentId(),
            'has_asset' => (bool) $has_attached_asset,
            'attached_asset_id' => $has_attached_asset ? (int) $attached_asset->getId() : null,
            'attached_asset_label' => $has_attached_asset ? (string) $attached_asset->getLabel() : '',
            'attached_asset_url' => $has_attached_asset ? (string) $attached_asset->getUrl() : '',
            'attached_asset_type' => $has_attached_asset ? (string) $attached_asset->getType() : '',
            'attached_asset_is_image' => (bool) $attached_asset_is_image,
            'attached_asset_width' => $width,
            'attached_asset_height' => $height,
            'thumbnail_url' => $thumbnail_url,
            'modal_thumbnail_url' => $modal_thumbnail_url,
            'image_is_resized' => (bool) $image_is_resized,
            'resize_percentage' => $resize_percentage,
            'resized_width' => $resized_width,
            'resized_height' => $resized_height,
            'resize_label' => $resize_label,
            'preview_kind' => $preview_kind,
            'preview_label' => $preview_label,
            'preview_badge' => $preview_badge,
            'alignment' => $alignment,
            'float' => SmartestStringHelper::toRealBool($attachment->getFloat()),
            'caption' => html_entity_decode((string) $attachment->getCaption(), ENT_COMPAT, 'UTF-8'),
            'caption_alignment' => $caption_alignment,
            'border' => SmartestStringHelper::toRealBool($attachment->getBorder()),
            'full_editor_url' => 'assets/defineAttachment?attachment='.rawurlencode($attachment_name).'&asset_id='.$asset->getId()
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
