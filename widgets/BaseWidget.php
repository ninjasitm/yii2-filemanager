<?php
/**
* @link http://www.yiiframework.com/
* @copyright Copyright (c) 2008 Yii Software LLC
* @license http://www.yiiframework.com/license/
*/

namespace nitm\filemanager\widgets;

use Yii;
use yii\base\InvalidConfigException;
use nitm\widgets\helpers\BaseWidget as Widget;
use yii\helpers\Html;
use kartik\icons\Icon;

class BaseWidget extends Widget
{
	public $model;
	public $withForm;

    /**
     * @var string the title for the alert. If set to empty or null, will not be
     * displayed.
     */
    public $title = '';

	public $buttonOptions = [];

    /**
     * @var array the HTML attributes for the title. The following options are additionally recognized:
     * - tag: the tag to display the title. Defaults to 'span'.
     */
    public $titleOptions = ['class' => 'kv-alert-title'];

	public $actions  = [];

	protected $inputType = 'image';

	public function init()
	{
		$this->registerAssets();
		$this->options['id'] .= $this->model->getId();
	}

    /**
     * Register client assets
     */
    public function registerAssets()
    {
        $view = $this->getView();
		foreach($this->getAssets() as $asset)
        	$asset::register($view);
    }

	public function getActions()
	{
		return array_merge($this->defaultActions(), $this->actions);
	}

	protected function defaultActions()
	{
		return [];
	}

    /**
     * Gets the title section
     *
     * @return string
     */
    protected function getTitle()
    {
        $icon = '';
        $title = '';
        $separator = '';
        if (!empty($this->icon) && $this->iconType == 'image') {
            $icon = Html::img($this->icon, $this->iconOptions);
        } elseif (!empty($this->icon)) {
            $this->iconOptions['class'] = $this->icon . ' ' . (empty($this->iconOptions['class']) ? 'kv-alert-title' : $this->iconOptions['class']);
            $icon = Html::tag('span', '', $this->iconOptions) . ' ';
        }
        if (!empty($this->title)) {
            $tag = ArrayHelper::remove($this->titleOptions, 'tag', 'span');
            $title = Html::tag($tag, $this->title, $this->titleOptions);
            if ($this->showSeparator) {
                $separator = '<hr class="kv-alert-separator">' . "\n";
            }
        }
        return $icon . $title . $separator;
    }

	protected function getInputId($model)
	{
		return $this->model->isWhat()."-".$this->inputType."-".(($model->getId() == 0) ? uniqid() : $model->getId());
	}

	protected function getAssets()
	{
		return [];
	}

	protected function defaultOptions()
	{
		return [];
	}
}
