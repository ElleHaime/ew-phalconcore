<?php
/**
 * @namespace
 */
namespace Engine\Search\Elasticsearch;

use Engine\Exception,
    Engine\Tools\Traits\DIaware,
    Engine\Search\Elasticsearch\Query\Builder,
    Engine\Search\Elasticsearch\Type,
    Engine\Search\Elasticsearch\Query,
    Engine\Search\Elasticsearch\Filter\AbstractFilter,
    Engine\Crud\Grid,
    Engine\Crud\Grid\Filter,
    Engine\Crud\Grid\Filter\Field,
    Engine\Tools\Strings;

/**
 * Class Elasticsearch
 *
 * @package Search
 * @subpackage Elasticsearch
 */
class Indexer
{
    use DIaware;

    /**
     * Mvc model object
     * @var \Engine\Search\Elasticsearch\ModelAdapter
     */
    protected $_model;

    /**
     * Grid model
     * @var string
     */
    protected $_grid;

    /**
     * Search adapter
     * @var string
     */
    protected $_adapter;
    /**
     * Grid params
     * @var array
     */
    protected $_params = [];

    /**
     * Construct
     *
     * @param string $grid
     * @param string $adapter
     * @param array $params
     */
    public function __construct($grid, $adapter = 'elastic', array $params = [])
    {
        $this->_grid = $grid;
        $grid = ($this->_grid instanceof \Engine\Crud\Grid) ? $this->_grid : new $this->_grid($this->_params, $this->getDi());
        $this->_model = $grid->getContainer()->getModel();

        $this->_adapter = $adapter;
        $this->_params = $params;
    }

    /**
     * Create elasticsearch index
     *
     * @return array
     */
    public function createIndex()
    {
        $index = $this->getIndex();
        if ($index->exists()) {
            return false;
        }
        // Create new index
        $result = $index->create([
            'number_of_shards' => 1,
            'number_of_replicas' => 1,
            'analysis' => [
                'analyzer' => [
                    'myAnalyzer' => [
                        'tokenizer' => 'standard',
                        'filter' => ['standard', 'lowercase', 'mySnowball']
                    ]
                ],
                'filter' => [
                    'mySnowball' => [
                        'type' => 'snowball',
                        'language' => 'english'
                    ]
                ]
            ]
        ], true);

        return $result;
    }

    /**
     * Initialize and return search adapter by name
     *
     * @return \Engine\Search\Elasticsearch\Client
     */
    public function getClient()
    {
        return ($this->_adapter instanceof Client ? $this->_adapter : $this->getDi()->get($this->_adapter));
    }

    /**
     * Initialize and return search index
     *
     * @return \Engine\Search\Elasticsearch\Index
     */
    public function getIndex()
    {
        return $this->getClient()->getIndex($this->_model);
    }

    /**
     * Initialize and return search type
     *
     * @return Type
     */
    public function getType()
    {
        $type = new Type($this->_model);
        $type->setDi($this->getDi());
        $type->setAdapter($this->_adapter);

        return $type;
    }

    /**
     * Mapping index
     *
     * @return void
     */
    public function setMapping()
    {
        $mapping = new \Elastica\Type\Mapping();
        $mapping->setType($this->getType());
        //$mapping->setParam('analyzer', 'myAnalyzer');

        // Set mapping
        $properties = [];
        $grid = ($this->_grid instanceof \Engine\Crud\Grid) ? $this->_grid : new $this->_grid($this->_params, $this->getDi());
        $filterFields = $grid->getFilter()->getFields();
        $gridColums = $grid->getColumns();
        foreach ($filterFields as $key => $field) {
            $name = $field->getName();
            $sortable = false;
            $store = false;
            $joinType = false;
            $type = $field->getValueType();
            if ((isset($gridColums[$key]) && $column = $gridColums[$key]) || $column = $grid->getColumnByName($name)) {
                $sortable = $column->isSortable();
                $store = true;
                if ($column instanceof \Engine\Crud\Grid\Column\JoinOne) {
                    $joinType = true;
                    if (!$column->isUseJoin()) {
                        $sortable = true;
                    }
                }
            }
            $property = $this->getFieldMap($field, $sortable, $store, $joinType, $type);
            if (!$property) {
                continue;
            }
            if (isset($property['type'])) {
                $properties[$name] = $property;
            } else {
                $properties += $property;
            }
        }

        $mapping->setProperties($properties);

        // Send mapping to type
        $mapping->send();
    }

    /**
     *  Build and return field map property for mapping using grid filter object
     *
     * @param \Engine\Crud\Grid\Filter\Field $field
     * @param bool $sortable
     * @param bool $store
     * @param bool $joinType
     * @param bool|string $type
     * @return array
     */
    public function getFieldMap(Field $field, $sortable = false, $store = false, $joinType = false, $type = false)
    {
        $property = false;
        $key = $field->getKey();
        $name = $field->getName();
        $boost = $this->getBoostParam($field);
        if (
            $field instanceof Field\Match ||
            $field instanceof Field\Search ||
            $field instanceof Field\Submit
        ) {

        } elseif ($field instanceof Field\Primary) {
            if (!$type) {
                $type = AbstractFilter::VALUE_TYPE_INT;
            }
            $property = $this->getFieldProperty($name, $type, $sortable, 'analyzed', $store, true, $boost);
        } elseif ($field instanceof Field\Date) {
            if (!$type) {
                $type = AbstractFilter::VALUE_TYPE_DATE;
            }
            $property = $this->getFieldProperty($name, $type, $sortable, 'analyzed', $store, true, $boost, "YYYY-MM-dd HH:mm:ss");
        } elseif ($field instanceof Field\Between) {
            if (!$type || $type == Field\Standart::VALUE_TYPE_FLOAT) {
                $type = AbstractFilter::VALUE_TYPE_DOUBLE;
            }
            $property = $this->getFieldProperty($name, $type, $sortable, 'analyzed', $store, true, $boost);
        } else if ($field instanceof Field\Compound) {
            $property = [
                'type' => 'multi_field',
                'fields' => []
            ];
            $fields = $field->getFields();
            foreach ($fields as $field) {
                $property['fields'][] = $this->getFieldMap($field);
            }
        } else if ($field instanceof Field\Join) {
            $path = $field->getPath();
            if (count($path) > 1) {
                if ($field->category) {
                    $property = [];
                    $model = $field->category;
                    //$name = str_replace(["\\model", "\\"], ["", "_"], strtolower(trim($model, "\\")));
                    $name = $key;
                    $temp = explode("\\", $model);
                    $subKey = array_pop($temp);
                    $name .= "_".strtolower($subKey);
                    $model = new $model;
                    $filters = $model->find()->toArray();
                    $primary = $model->getPrimary();
                    foreach ($filters as $filter) {
                        $property[$name.'_'.$filter[$primary]] = [
                            //"index_name" => $filter['filter_key'],
                            'type' => 'integer',
                            'store' => false,
                            //'index' => 'analyzed',
                            //'index' => 'no',
                            'include_in_all' => FALSE
                        ];
                        $property[$name.'_'.$filter[$primary]] = $this->getFieldProperty($name.'_'.$filter[$primary], 'integer', false, false, false, false);
                    }
                } else {
                    $property[$key] = $this->getFieldProperty($key, $type, $sortable, 'analyzed', $store, true, $boost);
                    $property[$key."_id"] = $this->getFieldProperty($key."_id", 'integer', $sortable, 'analyzed', $store, true, $boost);
                }
            } else {
                $property = [];
                if ($joinType) {
                    if (!$type) {
                        $type = AbstractFilter::VALUE_TYPE_INT;
                    }
                    $property[$key] = $this->getFieldProperty($key, $type, $sortable, 'analyzed', $store, true, $boost);
                    $property[$key."_id"] = $this->getFieldProperty($key."_id", 'integer', $sortable, 'analyzed', $store, true, $boost);
                } else {
                    if (!$type) {
                        $type = AbstractFilter::VALUE_TYPE_INT;
                    }
                    $property[$key] = $this->getFieldProperty($key, $type, $sortable, 'analyzed', $store, true, $boost);
                }
            }
        } elseif (
            $field instanceof Field\Numeric ||
            $field instanceof Field\InArray ||
            $field instanceof Field\ArrayToSelect ||
            $field instanceof Field\Checkbox
        ) {
            if (!$type) {
                $type = AbstractFilter::VALUE_TYPE_INT;
            }
            $property = $this->getFieldProperty($name, $type, $sortable, 'analyzed', $store, true, $boost);
        } elseif (
            $field instanceof Field\Name ||
            $field instanceof Field\Standart
        ) {
            if (!$type) {
                $type = AbstractFilter::VALUE_TYPE_STRING;
            }
            $property = $this->getFieldProperty($name, $type, $sortable, 'analyzed', $store, true, $boost);
        }

        return $property;
    }

    /**
     * Build field property for index mapping
     *
     * @param string $name
     * @param string $type
     * @param bool $sortable
     * @param string $index
     * @param bool $store
     * @param bool $include
     * @param float $boost
     * @param string $format
     * @return array
     */
    public function getFieldProperty(
        $name,
        $type,
        $sortable = false,
        $index = 'analyzed',
        $store = false,
        $include = true,
        $boost = 1.0,
        $format = false
    ) {
        $store = ($store) ? 'yes' : 'no';
        $field = [
            'type' => $type,
            'index' => $index,
            'store' => $store,
            'include_in_all' => $include,
            'boost' => $boost
        ];
        if ($format) {
            $field['format'] = $format;
        }
        if ($sortable) {
            $field = [
                'type' => 'multi_field',
                'fields' => [
                    $name => $field,
                    'sort' => [
                        'type' => $type,
                        'index' => 'not_analyzed',
                        'store' => 'no',
                        'include_in_all' => FALSE
                    ]
                ]
            ];
            if ($format) {
                $field['fields']['sort']['format'] = $format;
            }
        }

        return $field;
    }

    /**
     * Return field boost param value
     *
     * @param Field $field
     *
     * @return string
     */
    public function getBoostParam(Field $field)
    {
        $boost = '1.0';

        $boostParam = $field->getAttrib('boost');
        if ($boostParam) {
            $boost = $boostParam;
        }

        return $boost;
    }

    /**
     * Remove all documents from index type
     *
     * @return bool|\Elastica\Response
     */
    public function truncateIndexType()
    {
        $type = $this->getType();
        if (!$type->exists()) {
            return false;
        }
        return $type->deleteByQuery(new \Elastica\Query\MatchAll());
    }

    /**
     * Add data from grid to search index
     *
     * @param integer $page
     * @param integer $pages
     * @param integer $breakPage
     * @return array
     */
    public function setData($page = 0, $pages = false, $breakPage = 0)
    {
        $type = $this->getType();
        if (!$type->exists()) {
            $this->setMapping();
        }
        $grid = ($this->_grid instanceof \Engine\Crud\Grid) ? $this->_grid : new $this->_grid([], $this->getDi());

        $config = [];
        $config['model'] = $grid->getModel();
        $config['conditions'] = $grid->getConditions();
        $config['joins'] = $grid->getJoins();
        $modelAdapter = $grid->getModelAdapter();
        if ($modelAdapter) {
            $config['modelAdapter'] = $modelAdapter;
        }
        $container = new \Engine\Crud\Container\Grid\Mysql($grid, $config);
        $container->separateQuery(true);

        $columns = $grid->getColumns();
        foreach ($columns as $column) {
            $column->updateContainer($container);
        }
        $dataSource = $container->getDataSource();
        foreach ($columns as $column) {
            $column->updateDataSource($dataSource);
        }
        $filter = $grid->getFilter();
        $params = $grid->getParams();
        $filter->clearParams();
        $filter->setParams($params);
        $filter->applyFilters($dataSource);

        do {
            $grid->clearData();
            $grid->setPage($page);
            $data = $container->getData($dataSource);

            if (!$pages) {
                $pages = $data['pages'];
            }
            foreach ($data['data'] as $values) {
                $this->addItem($values, $grid);
            }
            if ($page == $breakPage) {
                break;
            }
            ++$page;
        } while ($page < $pages);

        return [$page, $pages];
    }

    /**
     * Add new item to index
     *
     * @param array $data
     * @param \Engine\Crud\Grid $grid
     *
     * @return \Elastica\Response
     * @throws \Engine\Exception
     */
    public function addItem(array $data, $grid = null)
    {
        if (!$grid) {
            $grid = new $this->_grid([], $this->getDi());
        }
        $itemDocument = $this->_processItemData($data, $grid);
        if (!$itemDocument) {
            return false;
        }

        return $this->getType()->addDocument($itemDocument);
    }

    /**
     * Check if item exist in search index
     *
     * @param array $data
     * @param \Engine\Crud\Grid $grid
     *
     * @return boolean
     * @throws \Engine\Exception
     */
    public function existItem(array $data, $grid = null)
    {
        if (!$grid) {
            $grid = new $this->_grid([], $this->getDi());
        }
        $itemDocument = $this->_processItemData($data, $grid);
        if (!$itemDocument) {
            return false;
        }

        try {
            $isDocumentExists = $this->getType()->getDocument($itemDocument->getId());
        } catch (\Exception $e) {
            $isDocumentExists = false;
        }

        return $isDocumentExists;
    }

    /**
     * Check if item exist in search index
     *
     * @param integer $id
     *
     * @return boolean
     * @throws \Engine\Exception
     */
    public function existItemById($id)
    {
        try {
            $isDocumentExists = $this->getType()->getDocument($id);
        } catch (\Exception $e) {
            $isDocumentExists = false;
        }

        return $isDocumentExists;
    }

    /**
     * Update document in index
     *
     * @param array $data
     * @param \Engine\Crud\Grid $grid
     *
     * @return \Elastica\Response
     * @throws \Engine\Exception
     */
    public function updateItem($data, $grid = null)
    {
        if (!$grid) {
            $grid = new $this->_grid([], $this->getDi());
        }
        $itemDocument = $this->_processItemData($data, $grid);

        return $this->getType()->updateDocument($itemDocument);
    }

    /**
     * Delete document from index
     *
     * @param array $data
     * @param \Engine\Crud\Grid $grid
     *
     * @return \Elastica\Response
     * @throws \Engine\Exception
     */
    public function deleteItem($data, $grid = null)
    {
        if (!$grid) {
            $grid = new $this->_grid([], $this->getDi());
        }
        $itemDocument = $this->_processItemData($data, $grid);

        return $this->getType()->deleteDocument($itemDocument);
    }

    /**
     * Delete document from index
     *
     * @param integer $id
     *
     * @return \Elastica\Response
     */
    public function deleteItemById($id)
    {
        return $this->getType()->deleteById($id);
    }

    /**
     * Build elastica document
     *
     * @param array $data
     * @param \Engine\Crud\Grid $grid
     * @return \Elastica\Document
     * @throws \Engine\Exception
     */
    protected function _processItemData(array $data, \Engine\Crud\Grid $grid)
    {
        $primaryKey = $grid->getPrimaryColumn()->getName();
        $filterFields = $grid->getFilter()->getFields();
        $gridColums = $grid->getColumns();

        $item = [];
        foreach ($filterFields as $key => $field) {
            if (
                $field instanceof Field\Search ||
                $field instanceof Field\Compound ||
                $field instanceof Field\Match ||
                $field instanceof Field\Submit
            ) {
                continue;
            }
            // check if filter field is a join field
            if ($field instanceof Field\Join) {
                $this->_processJoinFieldData($item, $key, $field, $data, $grid);
            } elseif ($field instanceof Field\Date) {
                $this->_processDateFieldData($item, $key, $field, $data, $grid);
            } else {
                $this->_processStandartFieldData($item, $key, $field, $data, $grid);
            }
        }
        $this->_normalizeItem($item);
        if (!(isset($item[$primaryKey]))) {
            $item[$primaryKey] = $data[$primaryKey];
        }
        $id = $item[$primaryKey];

        return $itemDocument = new \Elastica\Document($id, $item);
    }

    /**
     * Normalize item data before put it on elasticsearch
     *
     * @param array $item
     * @return void
     */
    protected function _normalizeItem(array &$item)
    {
        foreach ($item as &$value) {
            if (is_array($value)) {
                $this->_normalizeItem($value);
            } elseif (Strings::isFloat($value)) {
                $value = floatval($value);
            } elseif (Strings::isInt($value)) {
                $value = (int) $value;
            } elseif (is_string($value)) {
                $value = \Engine\Tools\Strings::replaceInvalidByteSequence5($value);
            }
        }
    }

    /**
     * Process data in standart filter type field value for search document
     *
     * @param array $item
     * @param string $key
     * @param \Engine\Crud\Grid\Filter\Field $field
     * @param array $data
     * @param \Engine\Crud\Grid $grid
     * @throws \Exception
     * @return void
     */
    protected function _processStandartFieldData(array &$item, $key, Field $field, array $data, Grid $grid)
    {
        $name = $field->getName();
        if (!$column = $grid->getColumnByName($name)) {
            throw new \Engine\Exception('Column with name \''.$name.'\' was not found in the grid \''.get_class($grid).'\'');
        }
        $dataKey = $grid->getColumnByName($name)->getKey();
        if (!array_key_exists($dataKey, $data)) {
            throw new \Engine\Exception("Value by filter key '".$dataKey."' not found in data from grid '".get_class($grid)."'");
        }
        if (null !== $data[$dataKey]) {
            $item[$key] = $data[$dataKey];
        }
    }

    /**
     * Process data in date filter type field value for search document
     *
     * @param array $item
     * @param string $key
     * @param \Engine\Crud\Grid\Filter\Field $field
     * @param array $data
     * @param \Engine\Crud\Grid $grid
     * @throws \Exception
     * @return void
     */
    protected function _processDateFieldData(array &$item, $key, Field\Date $field, array $data, Grid $grid)
    {
        $name = $field->getName();
        $dataKey = $grid->getColumnByName($name)->getKey();
        if (null !== $data[$dataKey]) {
            $item[$key] = $data[$dataKey];
        }
    }

    /**
     * Process data field value for search document
     *
     * @param array $item
     * @param string $key
     * @param \Engine\Crud\Grid\Filter\Field $field
     * @param array $data
     * @param \Engine\Crud\Grid $grid
     * @throws \Exception
     * @return void
     */
    protected function _processJoinFieldData(array &$item, $key, Field\Join $field, array $data, Grid $grid)
    {
        $name = $field->getName();
        $path = $field->getPath();
        $gridColums = $grid->getColumns();
        $primaryKey = $grid->getPrimaryColumn()->getKey();
        // if count of path models more than one, means that is many to many relations
        if (count($path) > 2) {
            $mainModel = $grid->getContainer()->getModel();
            $adapter = $mainModel->getReadConnectionService();

            $workingModelClass = array_shift($path);
            /* @var $workingModel \Engine\Mvc\Model */
            $workingModel = new $workingModelClass;
            $workingModel->setReadConnectionService($adapter);
            $queryBuilder = $workingModel->queryBuilder();

            $columns = [];
            $columns[] = [$name, 'useCorrelationName' => true];
            $relationsMainModel = $workingModel->getRelationPath($mainModel);
            $keyParent = array_shift($relationsMainModel)->getFields();
            $columns[] = [$keyParent, 'useCorrelationName' => true];
            $queryBuilder->andWhere($keyParent." = :".$keyParent.":");

            $queryBuilder->columnsJoinOne($path);
            while ($path) {
                $refModelClass = array_shift($path);
                $refModel = new $refModelClass;
                $relationsRefModel = $workingModel->getRelationPath($refModel);
                $refKey = array_shift($relationsRefModel)->getFields();
                $columns[] = [$refKey, 'useCorrelationName' => true];
                $workingModel = $refModel;
            }
            $queryBuilder->columns($columns);
            $savedData =  $queryBuilder->getQuery()->execute([$keyParent => $data[$primaryKey]])->toArray();
            //$savedData = (($result = $queryBuilder->getQuery()->execute()) === null) ? [] : $result->toArray();
            if ($savedData) {
                $item[$key] = \Engine\Tools\Arrays::assocToLinearArray($savedData, $name);
                $item[$key . "_id"] = \Engine\Tools\Arrays::assocToLinearArray($savedData, $refKey);
                //$item[$key] = \Engine\Tools\Arrays::resultArrayToJsonType($savedData);
            }
        } elseif (count($path) == 2) {
            $mainModel = $grid->getContainer()->getModel();
            $adapter = $mainModel->getReadConnectionService();

            $workingModelClass = array_shift($path);
            /* @var $workingModel \Engine\Mvc\Model */
            $workingModel = new $workingModelClass;
            $refModelClass = array_shift($path);
            /* @var $refModel \Engine\Mvc\Model */
            $refModel = new $refModelClass;
            $relationsRefModel = $workingModel->getRelationPath($refModel);
            $workingModel->setReadConnectionService($adapter);

            $relationsMainModel = $workingModel->getRelationPath($mainModel);
            if (!$relationsMainModel) {
                throw new \Engine\Exception("Did not find relations between '".get_class($workingModel)."' and '".get_class($mainModel)."' for filter field '".$key."'");
            }
            $refKey = array_shift($relationsRefModel)->getFields();
            $keyParent = array_shift($relationsMainModel)->getFields();
            $queryBuilder = $workingModel->queryBuilder();
            $queryBuilder->columns([$keyParent, $refKey]);
            // if field have category model, we add each type of category like separate item values
            if ($field->category) {
                $category = $field->category;

                $temp = explode("\\", $category);
                $subKey = array_pop($temp);
                $name .= "_".strtolower($subKey);

                $model = new $category;
                $primary = $model->getPrimary();
                $relationsCategoryModel = $refModel->getRelationPath($category);
                $categoryKey = array_shift($relationsCategoryModel)->getFields();

                $queryBuilder->columnsJoinOne($refModelClass, [$categoryKey => $categoryKey]);
                $queryBuilder->orderBy($categoryKey.', name');
                $queryBuilder->andWhere($keyParent." = '".$data[$primaryKey]."'");
                $sql = $queryBuilder->getPhql();
                $sql = str_replace(
                    [trim($workingModelClass, "\\"), trim($refModelClass, "\\"), "[", "]"],
                    [$workingModel->getSource(), $refModel->getSource(), "", ""],
                    $sql
                );
                $db = $workingModel->getReadConnection();
                $filterData = $db->fetchAll($sql);
                //$filterData = (($result = $queryBuilder->getQuery()->execute()) === null) ? [] : $result->toArray();

                foreach ($filterData as $filter) {
                    $newName = $name."_".$filter[$categoryKey];
                    if (!isset($item[$newName])) {
                        $item[$newName] = [];
                    }
                    $item[$newName][] = $filter[$refKey];
                }
            } else {
                $queryBuilder->andWhere($keyParent." = :".$keyParent.":");
                if (
                    $name == \Engine\Mvc\Model::ID ||
                    $name == $refKey
                ) {
                    $name = \Engine\Mvc\Model::NAME;
                }
                $queryBuilder->columnsJoinOne($refModel, ['name' => $name, 'id' => \Engine\Mvc\Model::ID]);
                $queryBuilder->orderBy('name');
                $savedData = $queryBuilder->getQuery()->execute([$keyParent => $data[$primaryKey]])->toArray();
                //$savedData = (($result = $queryBuilder->getQuery()->execute()) === null) ? [] : $result->toArray();
                if ($savedData) {
                    $item[$key] = \Engine\Tools\Arrays::assocToLinearArray($savedData, 'name');
                    $item[$key . "_id"] = \Engine\Tools\Arrays::assocToLinearArray($savedData, 'id');
                    //$item[$key] = \Engine\Tools\Arrays::resultArrayToJsonType($savedData);
                }
            }
        } else {
            if (
                (
                    (isset($gridColums[$key]) && $column = $gridColums[$key]) ||
                    $column = $grid->getColumnByName($name)
                ) &&
                ($column instanceof \Engine\Crud\Grid\Column\JoinOne)
            ) {
                if ($column->isUseJoin()) {
                    if (null !== $data[$key]) {
                        $item[$key] = [];
                        $item[$key] = $data[$key];
                        $item[$key . "_id"] = $data[$key . "_" . \Engine\Mvc\Model::JOIN_PRIMARY_KEY_PREFIX];
                    }
                } else {
                    $item[$key] = $column->getValue((object) $data);
                }
            } else {
                if (null !== $data[$key]) {
                    $item[$key] = $data[$key];
                }
            }
        }
    }

    /**
     * Delete index
     *
     * @return \Elastica\Response
     */
    public function deleteIndex()
    {
        $index = $this->getIndex();
        if (!$index->exists()) {
            return false;
        }
        return $index->delete();
    }
}