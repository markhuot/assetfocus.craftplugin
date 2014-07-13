<?php

namespace Craft;

class AssetFocusPlugin extends BasePlugin
{
    function getName()
    {
         return Craft::t('Asset Focus');
    }

    function getVersion()
    {
        return '1.0.0';
    }

    function getDeveloper()
    {
        return 'Mark Huot';
    }

    function getDeveloperUrl()
    {
        return 'http://www.markhuot.com';
    }

    function init()
    {
        /**
         * @todo `$content->focus` is wrong here. I actually need to loop through
         * each of the model attributes and find all fields of type "Asset Focus" 
         * because who knows what content editors will name that field. I just
         * happened to name it "focus."
         */
        craft()->on('content.onSaveContent', function(Event $event) {
            $content = $event->params['content'];
            $fileModel = craft()->assets->getFileById($content->elementId);
            if ($fileModel) {
                $this->createThumb($fileModel, json_decode($content->assetFocus, true));
            }
        });
    }

    private function createThumb($fileModel, $transforms)
    {
        if (empty($transforms)) {
            return;
        }

        $sourceType = craft()->assetSources->getSourceTypeById($fileModel->sourceId);
        $imageSource = craft()->assetTransforms->getLocalImageSource($fileModel);

        foreach ($transforms as $transform => $size) {
            $transform = craft()->assetTransforms->normalizeTransform($transform);
            $quality = $transform->quality ? $transform->quality : craft()->config->get('defaultImageQuality');
            $transformLocation = $this->_getTransformFolderName($transform);

            $image = craft()->images->loadImage($imageSource);
            $image->setQuality($quality);

            $image->crop($size[0], $size[1], $size[2], $size[3]);

            $targetFile = AssetsHelper::getTempFilePath(IOHelper::getExtension($fileModel->filename));
            $image->saveAs($targetFile);

            clearstatcache(true, $targetFile);
            $sourceType->putImageTransform($fileModel, $transformLocation, $targetFile);
            IOHelper::deleteFile($targetFile);
        }
    }

    /**
     * Returns a trasnform's folder name.
     *
     * @param AssetTransformModel $transform
     * @return string
     */
    private function _getTransformFolderName(AssetTransformModel $transform)
    {
        if ($transform->isNamedTransform())
        {
            return $this->_getNamedTransformFolderName($transform);
        }
        else
        {
            return $this->_getUnnamedTransformFolderName($transform);
        }
    }

    /**
     * Returns a named transform's folder name.
     *
     * @param AssetTransformModel $transform
     * @return string
     */
    private function _getNamedTransformFolderName(AssetTransformModel $transform)
    {
        return '_'.$transform->handle;
    }

    /**
     * Returns an unnamed transform's folder name.
     *
     * @param AssetTransformModel $transform
     * @return string
     */
    private function _getUnnamedTransformFolderName(AssetTransformModel $transform)
    {
        return '_'.($transform->width ? $transform->width : 'AUTO').'x'.($transform->height ? $transform->height : 'AUTO') .
               '_'.($transform->mode) .
               '_'.($transform->position) .
               ($transform->quality ? '_'.$transform->quality : '');
    }
}