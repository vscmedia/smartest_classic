<?php

class SmartestImageAssetDerivativeHelper{

    const HEIC_TYPE = 'SM_ASSETTYPE_HEIC_IMAGE';
    const JPEG_TYPE = 'SM_ASSETTYPE_JPEG_IMAGE';
    const PNG_TYPE = 'SM_ASSETTYPE_PNG_IMAGE';
    const WEBP_TYPE = 'SM_ASSETTYPE_WEBP_IMAGE';

    const VARIANT_HEIC_TO_JPEG = 'jpeg_from_heic';
    const VARIANT_DOWNSCALED = 'downscaled_image';
    const DEFAULT_LARGE_IMAGE_WIDTH_THRESHOLD = 3000;
    const DEFAULT_DOWNSCALE_WIDTH = 1200;

    protected static $large_image_width_threshold = null;
    protected static $default_downscale_width = null;

    public static function getLargeImageWidthThreshold(){

        if(is_numeric(self::$large_image_width_threshold)){
            return (int) self::$large_image_width_threshold;
        }

        $threshold = self::DEFAULT_LARGE_IMAGE_WIDTH_THRESHOLD;

        if(isset(SmartestInfo::$system_info_file) && is_file(SmartestInfo::$system_info_file)){
            $data = SmartestYamlHelper::fastLoad(SmartestInfo::$system_info_file);

            if(isset($data['system']['assets']['large_image_width_threshold']) && is_numeric($data['system']['assets']['large_image_width_threshold'])){
                $threshold = (int) $data['system']['assets']['large_image_width_threshold'];
            }
        }

        self::$large_image_width_threshold = max(3, $threshold);

        return self::$large_image_width_threshold;

    }

    public static function getDefaultDownscaleWidth(){

        if(is_numeric(self::$default_downscale_width)){
            return (int) self::$default_downscale_width;
        }

        $width = self::DEFAULT_DOWNSCALE_WIDTH;

        if(isset(SmartestInfo::$system_info_file) && is_file(SmartestInfo::$system_info_file)){
            $data = SmartestYamlHelper::fastLoad(SmartestInfo::$system_info_file);

            if(isset($data['system']['assets']['default_downscale_width']) && is_numeric($data['system']['assets']['default_downscale_width'])){
                $width = (int) $data['system']['assets']['default_downscale_width'];
            }
        }

        self::$default_downscale_width = max(1, $width);

        return self::$default_downscale_width;

    }

    public function heicConversionIsAvailable(){

        return self::heicConverterClassIsAvailable() && function_exists('exec');

    }

    public static function heicConverterClassIsAvailable(){

        if(!class_exists('Maestroerror\HeicToJpg') && defined('SM_ROOT_DIR') && is_file(SM_ROOT_DIR.'System/Library/vendor/autoload.php')){
            require_once SM_ROOT_DIR.'System/Library/vendor/autoload.php';
        }

        return class_exists('Maestroerror\HeicToJpg');

    }

    public function getHeicConversionAvailabilityMessage(){

        if(!self::heicConverterClassIsAvailable()){
            return 'The HEIC converter package is not installed.';
        }

        if(!function_exists('exec')){
            return 'The PHP exec() function is not available on this server.';
        }

        return '';

    }

    public function convertHeicAssetToJpeg(SmartestAsset $source_asset, $user=null){

        if($source_asset->getType() != self::HEIC_TYPE){
            throw new SmartestException('Only HEIC assets can be converted with SmartestImageAssetDerivativeHelper::convertHeicAssetToJpeg().');
        }

        if(!$this->heicConversionIsAvailable()){
            throw new SmartestException($this->getHeicConversionAvailabilityMessage());
        }

        $source_path = $source_asset->getFullPathOnDisk();

        if(!is_file($source_path)){
            throw new SmartestException('The original HEIC file could not be found on disk.');
        }

        if(!\Maestroerror\HeicToJpg::isHeic($source_path)){
            throw new SmartestException('The selected file does not appear to be a valid HEIC/HEIF image.');
        }

        $destination_dir = $source_asset->getStorageLocation(true);
        $base_name = SmartestStringHelper::removeDotSuffix(SmartestStringHelper::toSensibleFileName($source_asset->getUrl()));
        $destination_path = SmartestFileSystemHelper::getUniqueFileName($destination_dir.$base_name.'.jpg');

        if(!is_writable($destination_dir)){
            throw new SmartestException('Smartest cannot write the converted JPEG to '.$destination_dir.'.');
        }

        try{
            \Maestroerror\HeicToJpg::convert($source_path)->saveAs($destination_path);
        }catch(Throwable $e){
            SmartestLog::getInstance('system')->log('HEIC conversion failed for asset '.$source_asset->getId().': '.$e->getMessage(), SmartestLog::ERROR);
            throw new SmartestException('Smartest could not convert the HEIC file to JPEG: '.$e->getMessage());
        }

        if(!is_file($destination_path)){
            throw new SmartestException('The HEIC converter did not create the expected JPEG file.');
        }

        $dimensions = @getimagesize($destination_path);
        $label = $source_asset->getLabel().' (JPEG version)';

        return $this->createDerivedAssetRecord($source_asset, $destination_path, self::JPEG_TYPE, self::VARIANT_HEIC_TO_JPEG, 'JPEG converted from HEIC', $label, $user, array(
            'derivation_type' => self::VARIANT_HEIC_TO_JPEG,
            'source_asset_id' => $source_asset->getId(),
            'source_asset_type' => $source_asset->getType(),
            'converted_format' => 'jpeg',
            'converter' => 'maestroerror/php-heic-to-jpg',
            'derived_width' => is_array($dimensions) ? $dimensions[0] : '',
            'derived_height' => is_array($dimensions) ? $dimensions[1] : '',
        ));

    }

    public function canCreateDownscaledDerivative(SmartestAsset $asset, $threshold_width=null){

        foreach(array('imagecreatetruecolor', 'imagecopyresampled') as $function_name){
            if(!function_exists($function_name)){
                return false;
            }
        }

        if(!$asset->isBinaryImage()){
            return false;
        }

        if($asset->getType() == 'SM_ASSETTYPE_GIF_IMAGE'){
            return false;
        }

        $threshold_width = is_numeric($threshold_width) ? (int) $threshold_width : self::getLargeImageWidthThreshold();

        return is_numeric($asset->getWidth()) && (int) $asset->getWidth() > $threshold_width;

    }

    public function getMaximumDownscaleWidth(SmartestAsset $asset){

        $source_width = (int) $asset->getWidth();

        if($source_width < 2){
            return 0;
        }

        return min($source_width - 1, self::getLargeImageWidthThreshold() - 1);

    }

    public function getSuggestedDownscaleWidth(SmartestAsset $asset){

        $maximum = $this->getMaximumDownscaleWidth($asset);

        if($maximum < 1){
            return 0;
        }

        return min(self::getDefaultDownscaleWidth(), $maximum);

    }

    public function createTemporaryHeicPreview(SmartestAsset $source_asset, $max_width=600){

        if($source_asset->getType() != self::HEIC_TYPE){
            throw new SmartestException('Only HEIC assets can be previewed with SmartestImageAssetDerivativeHelper::createTemporaryHeicPreview().');
        }

        if(!$this->heicConversionIsAvailable()){
            throw new SmartestException($this->getHeicConversionAvailabilityMessage());
        }

        $source_path = $source_asset->getFullPathOnDisk();

        if(!is_file($source_path)){
            throw new SmartestException('The original HEIC file could not be found on disk.');
        }

        $cache_dir = SM_ROOT_DIR.'Public/Resources/System/Cache/Images/';

        if(!is_dir($cache_dir) && !@mkdir($cache_dir, 0775, true)){
            throw new SmartestException('Smartest cannot create the temporary image cache directory at '.$cache_dir.'.');
        }

        if(!is_writable($cache_dir)){
            throw new SmartestException('Smartest cannot write the temporary HEIC preview to '.$cache_dir.'.');
        }

        $cache_key = 'heic_preview_'.$source_asset->getId().'_'.substr(md5($source_path.'|'.@filemtime($source_path).'|'.@filesize($source_path)), 0, 12);
        $converted_path = $cache_dir.$cache_key.'.jpg';

        if(!is_file($converted_path)){
            try{
                \Maestroerror\HeicToJpg::convert($source_path)->saveAs($converted_path);
            }catch(Throwable $e){
                SmartestLog::getInstance('system')->log('Temporary HEIC preview failed for asset '.$source_asset->getId().': '.$e->getMessage(), SmartestLog::ERROR);
                throw new SmartestException('Smartest could not create a temporary HEIC preview: '.$e->getMessage());
            }
        }

        $max_width = is_numeric($max_width) ? (int) $max_width : 600;

        if($max_width > 0 && function_exists('imagecreatefromjpeg') && function_exists('imagecreatetruecolor') && function_exists('imagecopyresampled') && function_exists('imagejpeg')){
            $dimensions = @getimagesize($converted_path);

            if(is_array($dimensions) && isset($dimensions[0], $dimensions[1]) && (int) $dimensions[0] > $max_width && (int) $dimensions[1] > 0){
                $preview_path = $cache_dir.$cache_key.'_'.$max_width.'w.jpg';

                if(!is_file($preview_path)){
                    $source_resource = @imagecreatefromjpeg($converted_path);

                    if($source_resource){
                        $new_width = $max_width;
                        $new_height = (int) ceil($new_width / (int) $dimensions[0] * (int) $dimensions[1]);
                        $preview_resource = imagecreatetruecolor($new_width, $new_height);
                        imagecopyresampled($preview_resource, $source_resource, 0, 0, 0, 0, $new_width, $new_height, (int) $dimensions[0], (int) $dimensions[1]);
                        @imagejpeg($preview_resource, $preview_path, 75);
                        if(function_exists('imagedestroy')){
                            imagedestroy($source_resource);
                            imagedestroy($preview_resource);
                        }
                    }
                }

                if(is_file($preview_path)){
                    return 'Resources/System/Cache/Images/'.basename($preview_path);
                }
            }
        }

        return 'Resources/System/Cache/Images/'.basename($converted_path);

    }

    public function createDownscaledImageAsset(SmartestAsset $source_asset, $max_width=null, $user=null){

        $max_width = is_numeric($max_width) ? (int) $max_width : $this->getSuggestedDownscaleWidth($source_asset);

        if(!$this->canCreateDownscaledDerivative($source_asset)){
            throw new SmartestException('This image is not wider than Smartest\'s large-image threshold.');
        }

        $maximum_width = $this->getMaximumDownscaleWidth($source_asset);

        if($maximum_width < 1){
            throw new SmartestException('Smartest could not determine a valid smaller width for this image.');
        }

        if($max_width < 1){
            $max_width = $this->getSuggestedDownscaleWidth($source_asset);
        }

        if($max_width > $maximum_width){
            $max_width = $maximum_width;
        }

        $source_image = $source_asset->getImage();

        if(!$source_image instanceof SmartestImage){
            throw new SmartestException('The original image could not be loaded.');
        }

        $source_resource = $source_image->getResource();

        if(!$source_resource){
            throw new SmartestException('Smartest could not open the original image for resizing.');
        }

        $source_width = (int) $source_image->getWidth();
        $source_height = (int) $source_image->getHeight();
        $new_width = $max_width;
        $new_height = (int) ceil($new_width / $source_width * $source_height);
        $destination_dir = $source_asset->getStorageLocation(true);
        $suffix = strtolower(SmartestStringHelper::getDotSuffix($source_asset->getUrl()));
        $base_name = SmartestStringHelper::removeDotSuffix(SmartestStringHelper::toSensibleFileName($source_asset->getUrl()));
        $destination_path = SmartestFileSystemHelper::getUniqueFileName($destination_dir.$base_name.'-'.$new_width.'w.'.$suffix);

        if(!is_writable($destination_dir)){
            throw new SmartestException('Smartest cannot write the downsized image to '.$destination_dir.'.');
        }

        $new_resource = ImageCreateTrueColor($new_width, $new_height);

        if(in_array($source_image->getImageType(), array(SmartestImage::PNG, SmartestImage::WEBP))){
            imagealphablending($new_resource, false);
            imagesavealpha($new_resource, true);
        }

        imagecopyresampled($new_resource, $source_resource, 0, 0, 0, 0, $new_width, $new_height, $source_width, $source_height);

        if(function_exists('imagedestroy')){
            imagedestroy($source_resource);
        }

        if(!$source_image->saveToFile($new_resource, $destination_path)){
            throw new SmartestException('Smartest could not save the downsized image.');
        }

        $scale_percentage = round($new_width / $source_width * 100, 1);
        $label = $source_asset->getLabel().' ('.$new_width.'px wide)';

        return $this->createDerivedAssetRecord($source_asset, $destination_path, $source_asset->getType(), self::VARIANT_DOWNSCALED, $new_width.'px-wide version', $label, $user, array(
            'derivation_type' => self::VARIANT_DOWNSCALED,
            'source_asset_id' => $source_asset->getId(),
            'source_asset_type' => $source_asset->getType(),
            'source_width' => $source_width,
            'source_height' => $source_height,
            'derived_width' => $new_width,
            'derived_height' => $new_height,
            'scale_percentage' => $scale_percentage,
        ));

    }

    protected function createDerivedAssetRecord(SmartestAsset $source_asset, $destination_path, $asset_type, $variant_name, $variant_label, $label, $user=null, $info=array()){

        $asset = new SmartestAsset;
        $asset->setWebId(SmartestStringHelper::random(32, SM_RANDOM_ALPHANUMERIC));
        $asset->setCreated(time());
        $asset->setModified(time());
        $asset->setStringId(SmartestStringHelper::toVarName($label, true), $source_asset->getSiteId());
        $asset->setLabel($label);
        $asset->setType($asset_type);
        $asset->setUrl(basename($destination_path));
        $asset->setSiteId($source_asset->getSiteId());
        $asset->setShared($source_asset->getShared());
        $asset->setParentId($source_asset->getId());
        $asset->setVariantName($variant_name);
        $asset->setVariantLabel($variant_label);

        if(is_object($user) && method_exists($user, 'getId')){
            $asset->setUserId($user->getId());
        }else{
            $asset->setUserId($source_asset->getUserId());
        }

        $info['created_at'] = time();
        $info['created_by_user_id'] = $asset->getUserId();
        $info['source_url'] = $source_asset->getUrl();

        $storable_info = array();

        foreach($info as $key=>$value){
            if(is_scalar($value) || is_null($value)){
                $storable_info[SmartestStringHelper::toVarName($key)] = (string) $value;
            }
        }

        $asset->setInfo(serialize($storable_info));
        $asset->save();

        SmartestLog::getInstance('site')->log('Created derived image asset '.$asset->getId().' from asset '.$source_asset->getId().' using '.$variant_name.'.');

        return $asset;

    }

}
