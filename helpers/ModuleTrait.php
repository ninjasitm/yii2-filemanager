<?php

namespace nitm\filemanager\helpers;

/**
 * @property \nitm\filemanager\Module $module
 */
trait ModuleTrait
{
    /**
     * @var null|\nitm\filemanager\Module
     */
    private $_module;

    /**
     * @return null|\nitm\filemanager\Module
     */
    protected function getModule()
    {
        if ($this->_module == null) {
            $this->_module = \Yii::$app->getModule('filemanager');
        }

        return $this->_module;
    }
}