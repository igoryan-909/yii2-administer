<?php

namespace ylab\administer\relations;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Class represent relation with data.
 */
class RelatedData
{
    /**
     * @var string Attribute name.
     */
    protected $attribute;
    /**
     * @var array|string Loaded data.
     */
    protected $data;
    /**
     * @var ActiveQuery Query of relation.
     */
    protected $activeQuery;
    /**
     * @var array
     */
    protected $newModels = [];
    /**
     * @var array
     */
    protected $oldModels = [];
    /**
     * @var array
     */
    protected $newRows = [];
    /**
     * @var array
     */
    protected $oldRows = [];
    /**
     * @var string Column name in junction table.
     */
    protected $junctionColumn;
    /**
     * @var string Junction table name.
     */
    protected $junctionTable;

    /**
     * @param $attribute
     */
    public function __construct($attribute)
    {
        $this->attribute = $attribute;
    }

    /**
     * @return string
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @return string|array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string|array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return ActiveQuery
     */
    public function getActiveQuery()
    {
        return $this->activeQuery;
    }

    /**
     * @param ActiveQuery $activeQuery
     */
    public function setActiveQuery(ActiveQuery $activeQuery)
    {
        $this->activeQuery = $activeQuery;
        if (empty($activeQuery->via)) {
            $this->setOldModels($this->activeQuery->all());
        }
    }

    /**
     * @return array
     */
    public function getOldModels()
    {
        return $this->oldModels;
    }

    /**
     * @param array $oldModels
     */
    public function setOldModels(array $oldModels)
    {
        $this->oldModels = $oldModels;
    }

    /**
     * @return array
     */
    public function getNewModels()
    {
        return $this->newModels;
    }

    /**
     * @param array $newModels
     */
    public function setNewModels($newModels)
    {
        $this->newModels = $newModels;
    }

    /**
     * Append into new models list.
     *
     * @param $model
     */
    public function pushNewModel($model)
    {
        $this->newModels[] = $model;
    }

    /**
     * Returns count of existing models.
     *
     * @return int
     */
    public function getCountModels()
    {
        $modelClass = $this->activeQuery->modelClass;
        return $modelClass::find()->where([$modelClass::primaryKey()[0] => $this->data])->count();
    }

    public function replaceExistingModels()
    {
        foreach ($this->newModels as $i => $model) {
            $this->replaceNewModelByExisting($i, $model);
        }
    }

    /**
     * Return existing model if it found in old models
     *
     * @param int $index
     * @param ActiveRecord $model
     */
    protected function replaceNewModelByExisting($index, ActiveRecord $model)
    {
        $modelAttributes = $model->attributes;
        unset($modelAttributes[$model->primaryKey()[0]]);

        foreach ($this->oldModels as $oldModel) {
            /** @var ActiveRecord $oldModel */
            $oldModelAttributes = $oldModel->attributes;
            unset($oldModelAttributes[$oldModel->primaryKey()[0]]);

            if ($oldModelAttributes == $modelAttributes) {
                $this->newModels[$index] = $oldModel;
                return;
            }
        }

        $this->newModels[$index] = $model;
    }

    /**
     * @return string
     */
    public function getJunctionColumn()
    {
        return $this->junctionColumn;
    }

    /**
     * @param string $junctionColumn
     */
    public function setJunctionColumn($junctionColumn)
    {
        $this->junctionColumn = $junctionColumn;
    }

    /**
     * @return array
     */
    public function getOldRows()
    {
        return $this->oldRows;
    }

    /**
     * @return array
     */
    public function getNewRows()
    {
        return $this->newRows;
    }

    /**
     * @return string
     */
    public function getJunctionTable()
    {
        return $this->junctionTable;
    }

    /**
     * @param string $junctionTable
     */
    public function setJunctionTable($junctionTable)
    {
        $this->junctionTable = $junctionTable;
    }
}