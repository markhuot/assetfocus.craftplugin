<?php

namespace Craft;

class AssetFocus_FocusFieldType extends BaseFieldType
{
    public function getName()
    {
        return Craft::t('Asset Focus');
    }

    public function getInputHtml($name, $value)
    {
        return craft()->templates->render('assetfocus/input', array(
            'name'  => $name,
            'value' => $value,
            'url' => $this->element->getUrl(),
        ));
    }
}