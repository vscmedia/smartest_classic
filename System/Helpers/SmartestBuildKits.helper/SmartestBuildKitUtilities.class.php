<?php

class SmartestBuildKitUtilities{

    public static function getAvailableBuildKits(){

        $all = array_merge(
            self::getAvailableBuildKitsInDirectory(SM_ROOT_DIR.'System/Install/BuildKits/'),
            self::getAvailableBuildKitsInDirectory(SM_ROOT_DIR.'Library/BuildKits/')
        );

        $final = array();

        foreach($all as $buildkit){
            if($buildkit instanceof SmartestBuildKit && $buildkit->isValid()){
                $final[$buildkit->getShortName()] = $buildkit;
            }
        }

        ksort($final);
        return $final;
    }

    public static function getAvailableBuildKitsInDirectory($dir){

        if(!is_dir($dir)){
            return array();
        }

        $accepted = array();
        $raw_list = SmartestFileSystemHelper::getDirectoryContents($dir, false, SM_DIR_SCAN_DIRECTORIES);

        foreach($raw_list as $directory_name){
            if(strtolower(substr($directory_name, -9)) == '.buildkit' && is_file($dir.$directory_name.'/configure.yml')){
                $buildkit = new SmartestBuildKit($dir.$directory_name.'/');
                if($buildkit->isValid()){
                    $accepted[] = $buildkit;
                }
            }
        }

        return $accepted;
    }

    public static function buildKitIsInstalled($buildkit_name){
        $buildkits = self::getAvailableBuildKits();
        return isset($buildkits[SmartestStringHelper::toVarName($buildkit_name)]);
    }

    public static function getBuildKitIfInstalled($buildkit_name){

        $buildkits = self::getAvailableBuildKits();
        $key = SmartestStringHelper::toVarName($buildkit_name);

        return isset($buildkits[$key]) ? $buildkits[$key] : null;
    }
}
