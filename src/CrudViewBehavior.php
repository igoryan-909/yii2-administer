<?php

namespace ylab\administer;

use yii\base\Behavior;
use yii\base\ModelEvent;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii\validators\EmailValidator;
use yii\validators\FileValidator;
use yii\validators\ImageValidator;
use yii\validators\NumberValidator;
use ylab\administer\fields\EmailField;
use ylab\administer\fields\FileField;
use ylab\administer\fields\ImageField;
use ylab\administer\fields\NumberField;
use ylab\administer\fields\StringField;
use ylab\administer\helpers\BreadcrumbsHelper;
use ylab\administer\helpers\ButtonsHelper;
use ylab\administer\relations\AutocompleteService;
use ylab\administer\relations\RelationManager;
use ylab\administer\renderers\DetailRenderer;
use ylab\administer\renderers\FormRenderer;
use ylab\administer\renderers\ListRenderer;

/**
 * Behavior for add to ActiveRecord models possibility to use in Administer CRUD module.
 * Example usage:
 * ```
 * public function behaviors()
 * {
 *     return [
 *         'crudView' => [
 *             'class' => CrudViewBehavior::class,
 *             'formRenderer' => [
 *                 'attributesInputs' => [
 *                     'name',
 *                     'avatar' => [
 *                         'class' => \ylab\administer\fields\ImageField::class,
 *                     ],
 *                     'doc' => [
 *                         'class' => \ylab\administer\fields\FileField::class,
 *                     ],
 *                 ],
 *             ],
 *            'buttonsConfig' => [
 *                CrudButton::TYPE_CREATE => [
 *                    'text' => 'Add Post',
 *                    'options' => [
 *                        'class' => 'btn btn-danger',
 *                    ],
 *                ],
 *            ],
 *         ],
 *     ];
 * }
 * ```
 *
 * {@inheritdoc}
 * @property ActiveRecord $owner
 */
class CrudViewBehavior extends Behavior
{
    /**
     * @var FormRenderer
     */
    public $formRenderer;
    /**
     * @var ListRenderer
     */
    public $listRenderer;
    /**
     * @var DetailRenderer
     */
    public $detailRenderer;
    /**
     * @var array
     */
    public $buttonsConfig = [];
    /**
     * @var array definitions of relations
     */
    public $relations = [];

    /**
     * @var ButtonsHelper
     */
    protected $buttonsHelper;
    /**
     * @var BreadcrumbsHelper
     */
    protected $breadcrumbsHelper;
    /**
     * @var RelationManager
     */
    protected $relationManager;
    /**
     * @var AutocompleteServiceInterface
     */
    protected $relationAutocompleteService;

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        parent::attach($owner);
        $this->buttonsHelper = \Yii::createObject(ButtonsHelper::class);
        $this->breadcrumbsHelper = \Yii::createObject(BreadcrumbsHelper::class);
        $this->initRenderer('formRenderer', FormRenderer::class);
        $this->initRenderer('listRenderer', ListRenderer::class);
        $this->initRenderer('detailRenderer', DetailRenderer::class);
        $this->relationManager = \Yii::createObject(
            RelationManager::class,
            [$owner, $this->relations]
        );
        $this->relationAutocompleteService = \Yii::createObject(AutocompleteServiceInterface::class, [$owner]);
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return ArrayHelper::merge(
            parent::events(),
            [
                BaseActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
                BaseActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
                BaseActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
                BaseActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
                BaseActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
                BaseActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
            ]
        );
    }

    /**
     * Handler of loading relational data.
     *
     * @param ModelEvent $event
     */
    public function beforeSave(ModelEvent $event)
    {
        $this->relationManager->beforeSave($event);
    }

    /**
     * Handler of saving relational data.
     */
    public function afterSave()
    {
        $this->relationManager->afterSave();
    }

    public function beforeDelete()
    {
        $this->relationManager->beforeDelete();
    }

    /**
     * Handler for deletion relational data.
     */
    public function afterDelete()
    {
        $this->relationManager->afterDelete();
    }

    /**
     * Permission for this behavior to set relational attributes.
     *
     * @inheritdoc
     */
    public function canSetProperty($name, $checkVars = true)
    {
        return $this->relationManager->canSetProperty($name) || parent::canSetProperty($name, $checkVars);
    }

    /**
     * Setter for relational attributes. Called only if attribute is exist in POST array.
     *
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if (!$this->relationManager->setRelationValue($name, $value)) {
            parent::__set($name, $value);
        }
    }

    /**
     * Render form and return it as a string.
     *
     * @param string $modelUrl
     * @return string
     */
    public function renderForm($modelUrl)
    {
        return $this->formRenderer->renderForm($this->owner, $modelUrl, $this->getFieldsConfig());
    }

    /**
     * Render GridView widget and return it as a string.
     *
     * @param array $params
     * @param string $url
     * @return string
     * @throws \Exception
     */
    public function renderGrid(array $params, $url)
    {
        return $this->listRenderer->renderGrid($this->owner, $params, $url);
    }

    /**
     * Render DetailView widget and return it as a string.
     *
     * @return string
     * @throws \Exception
     */
    public function renderDetailView()
    {
        return $this->detailRenderer->renderDetailView($this->owner);
    }

    /**
     * Get actions buttons.
     *
     * @param string $action
     * @param string $modelClass
     * @param null|int $id
     * @return array
     */
    public function getButtons($action, $modelClass, $id = null)
    {
        return $this->buttonsHelper->getButtons($action, $modelClass, $id, $this->buttonsConfig);
    }

    /**
     * Get breadcrumbs items config.
     *
     * @param string $action
     * @param null|string $url
     * @param null|string $name
     * @param null|int $id
     * @return array
     */
    public function getBreadcrumbs($action, $url = null, $name = null, $id = null)
    {
        return $this->breadcrumbsHelper->getBreadcrumbs($action, $url, $name, $id);
    }

    /**
     * Returns data by relation.
     *
     * Define fields of related model in `fields()` method:
     * - id: for attribute which uses as key
     * - text: for attribute which uses as label
     *
     * @param string $relation Name of relation in model
     * @param string $keyAttribute Attribute in related model uses for key
     * @param string $labelAttribute Attribute in related model uses for label
     * @param string $q Query from field
     * @param int $limit
     * @return array
     */
    public function getRelatedAutocompleteHintsData($relation, $keyAttribute, $labelAttribute, $q, $limit = 10)
    {
        return $this->relationAutocompleteService->getHints($relation, $keyAttribute, $labelAttribute, $q, $limit);
    }

    /**
     * Get fields config based on `rules()` model method.
     *
     * @return array
     */
    protected function getFieldsConfig()
    {
        $config = [];
        foreach ($this->owner->getActiveValidators() as $validator) {
            $class = get_class($validator);
            switch ($class) {
                case EmailValidator::class:
                    $class = EmailField::class;
                    break;
                case ImageValidator::class:
                    $class = ImageField::class;
                    break;
                case FileValidator::class:
                    $class = FileField::class;
                    break;
                case NumberValidator::class:
                    $class = NumberField::class;
                    break;
                default:
                    continue 2;
            }
            $config = ArrayHelper::merge($config, $this->addInConfig($class, $validator->getAttributeNames()));
        }

        foreach ($this->owner->attributes() as $attribute) {
            if (!isset($config[$attribute])) {
                $config[$attribute] = ['class' => StringField::class];
            }
        }

        return $config;
    }

    /**
     * Create config for scope of attributes.
     *
     * @param string $type
     * @param array $attributes
     * @return array
     */
    private function addInConfig($type, array $attributes)
    {
        $config = [];
        foreach ($attributes as $attribute) {
            $config[$attribute] = ['type' => $type];
        }
        return $config;
    }

    /**
     * Init renderer based on user config and default config.
     *
     * @param string $property
     * @param string $class
     * @throws \yii\base\InvalidConfigException
     */
    private function initRenderer($property, $class)
    {
        if ($this->{$property} === null) {
            $renderer = $class;
        } else {
            if (is_array($this->{$property}) && !isset($this->{$property}['class'])) {
                $this->{$property}['class'] = $class;
            }
            $renderer = $this->{$property};
        }
        $this->{$property} = \Yii::createObject($renderer);
    }
}
