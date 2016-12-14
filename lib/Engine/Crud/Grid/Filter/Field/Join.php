<?php
/**
 * @namespace
 */
namespace Engine\Crud\Grid\Filter\Field;

use Engine\Filter\SearchFilterInterface as Criteria,
    Engine\Db\Filter\Compound,
    Engine\Crud\Container\AbstractContainer as Container;

/**
 * Grid filter field
 *
 * @category   Engine
 * @package    Crud
 * @subpackage Grid
 */
class Join extends ArrayToSelect
{
    /**
     * Form element type
     * @var string
     */
    protected $_type = 'select';

    /**
     * Parent model
     * @var \Engine\Mvc\Model
     */
    protected $_model;

    /**
     * Filter join path
     * @var string|array
     */
    protected $_path;

    /**
     * Options row name
     * @var string
     */
    protected $_optionName = 'name';

    /**
     * Options category model
     * @var \Engine\Mvc\Model
     */
    public $category;

    /**
     * Optiosn category row name
     * @var string
     */
    public $categoryName = 'name';

    /**
     * Empty category value
     * @var string
     */
    public $emptyCategory;

    /**
     * Empty item value
     * @var string
     */
    public $emptyItem;

    /**
     * Addition select fields
     * @var array
     */
    public $fields = [];

    /**
     * Options select where condition
     * @var string|array
     */
    public $where;

    /**
     * @var string
     */
    protected $_glue = Compound::GLUE_OR;

    /**
     * Separate filter for simple queries.
     * @var bool
     */
    protected $_separatedQueries;

    /**
     * Add default value to filters
     * @var bool
     */
    protected $_enableDefaultValue = false;

    /**
     * Constructor
     *
     * @param string $label
     * @param string|\Vein\Core\Mvc\Model $model
     * @param string $name
     * @param string $optionName
     * @param string|array $path
     * @param string $desc
     * @param string $criteria
     * @param integer $width
     * @param bool $loadSelectOptions
     * @param bool $separatedQueries
     * @param string $default
     */
    public function __construct(
        $label = null,
        $model,
        $name = false,
        $optionName = null,
        $path = null,
        $desc = null,
        $criteria = Criteria::CRITERIA_EQ,
        $width = 280,
        $loadSelectOptions = true,
        $separatedQueries = false,
        $default = false
    ) {
        $this->_label = $label;
        $this->_name = $name;
        $this->_desc = $desc;
        $this->_criteria = $criteria;
        $this->_width = intval($width);

        $this->_model = $model;
        $this->_optionName = $optionName;
        $this->_path = $path;
        $this->_loadSelectOptions = $loadSelectOptions;
        $this->_separatedQueries = $separatedQueries;
    }

    /**
     * Initialize field (used by extending classes)
     *
     * @return void
     */
    protected function _init()
    {
        parent::_init();
        $this->_path = ($this->_path) ? $this->_path : $this->_model;
        if (!$this->_name) {
            //$this->_name = \Engine\Mvc\Model::NAME;
            //If counf of path more then 1 working incorect in Search Indexer on line 766
            $mainModel = $this->_gridFilter->getContainer()->getModel();
            $relations = $mainModel->getRelationPath($this->_path);
            if (!$relations) {
                throw new \Engine\Exception("Relations for model '".get_class($mainModel)."' by path '".implode(', ', $this->_path)."' not valid");
            }
            $relation = array_pop($relations);
            $this->_name = $relation->getFields();
        }
    }

    /**
     * Apply field filter value to dataSource
     *
     * @param mixed $dataSource
     * @param \Engine\Crud\Container\AbstractContainer $container
     *
     * @return \Engine\Crud\Grid\Filter\Field\Join
     */
    public function applyFilter($dataSource, Container $container)
    {
        if ($filters = $this->getFilter($container)) {
            if ($this->_separatedQueries === false) {
                $filterPath = $container->getFilter('path', $this->_path, $this, $filters, $this->category)->setFilterField($this);
                $filterPath->applyFilter($dataSource);
            } else {
                $filters = $this->_getSeparateFilters($filters, $dataSource->getModel(), $container);
                $filters->applyFilter($dataSource);
            }
        }

        return $this;
    }

    /**
     * Return datasource filters
     *
     * @param \Engine\Crud\Container\AbstractContainer $container
     *
     * @return \Engine\Filter\SearchFilterInterface
     */
    public function getFilter(Container $container)
    {
        $values = $this->normalizeValues($this->getValue());

        if ($values === false) {
            if ($this->_enableDefaultValue) {
                return $container->getFilter('standart', $this->_name, $this->_default, $this->_criteria)->setFilterField($this);
            }
            return false;
        }

        if (is_array($values) && count($values) == 1) {
            $values = $values[0];
        }

        if (!$this->checkHashValue($values)) {
            return false;
        }

        return (is_array($values) ?
            $container->getFilter('in', $this->_name, $values, $this->_criteria)->setFilterField($this) :
            $container->getFilter('standart', $this->_name, $values, $this->_criteria)->setFilterField($this));
    }

    /**
     * Normalize array of values
     *
     * @param array|string $values
     * @return array|bool
     */
    public function normalizeValues($values)
    {
        if ($values === null ||
            $values === false ||
            (is_string($values) && trim($values) == '') ||
            (is_array($values) && count($values) == 0)
        ) {
            return false;
        }
        $filters = [];
        if (!is_array($values)) {
            $values = [$values];
        }
        $normalizeValues = [];
        foreach ($values as $val) {
            if (trim($val) === '' || $val == -1 || $val === false  || array_search($val, $this->_exceptionValues, empty($val) && $val !== '0')) {
                continue;
            }
            if ($val == '{{empty}}') {
                $val = '';
            }
            if ((int) $val == $val) {
                $val = (int) $val;
            } elseif (is_float($val)) {
                $val = floatval($val);
            }
            $normalizeValues[] = $val;
        }

        return $normalizeValues;
    }

    /**
     *
     *
     * @param \Engine\Filter\SearchFilterInterface $filter
     * @param \Engine\Mvc\Model $model
     * @param \Engine\Crud\Container\AbstractContainer $container
     * 
     * @return \Engine\Filter\SearchFilterInterface
     */
    protected function _getSeparateFilters($filter, \Engine\Mvc\Model $model, Container $container)
    {
        $values = $this->getValue();

        if ($values === false) {
            if ($this->_enableDefaultValue) {
                return $container->getFilter('standart', $this->_name, $this->_default, $this->_criteria)->setFilterField($this);
            }
            return false;
        }

        $path = ($this->_path) ? $this->_path : $this->_model;
        if ((array) $path !== $path) {
            $path = [$path];
        }
        $rule = array_shift($path);
        $relations = $model->getRelationPath($rule);
        $relation = array_shift($relations);
        $fields = $relation->getFields();
        $refModel = $relation->getReferencedModel();
        $refModel = new $refModel;
        $adapter = $model->getReadConnectionService();
        $refModel->setConnectionService($adapter);
        $refFields = $relation->getReferencedFields();
        $options = $relation->getOptions();

        $queryBuilder = $refModel->queryBuilder();
        $queryBuilder->columns($refFields);

        if (count($path) > 0) {
            //$queryBuilder->columnsJoinOne($path, null);
            $filterPath = $container->getFilter('path', $path, $this, $filter)->setFilterField($this);
            $filterPath->applyFilter($queryBuilder);
        } else {
            $filter->applyFilter($queryBuilder);
        }
        $queryBuilder->groupBy($refFields);
        $rows = $queryBuilder->getQuery()->execute()->toArray();
        $values = [];
        foreach ($rows as $val) {
            $values[] = $val[$refFields];
        }

        return $container->getFilter('in', $fields, $values, Criteria::CRITERIA_IN)->setFilterField($this);
    }

    /**
     * Return options array
     *
     * @return array
     */
    public function getOptions()
    {
        if (empty($this->_options)) {
            $this->_setOptions();
        }
        return $this->_options;
    }

    /**
     * Set options
     *
     * @return void
     */
    protected function _setOptions()
    {
        if (is_string($this->_model)) {
            $this->_model = new $this->_model;
            $modelAdapter = $this->_gridFilter->getGrid()->getModelAdapter();
            if ($modelAdapter) {
                $this->_model->setConnectionService($modelAdapter);
            }
        }
        $queryBuilder = $this->_model->queryBuilder();

        $this->_options = \Engine\Crud\Tools\Multiselect::prepareOptions(
            $queryBuilder,
            $this->_optionName,
            $this->category,
            $this->categoryName,
            $this->where,
            $this->emptyCategory,
            $this->emptyItem,
            $this->fields
        );
    }

    /**
     * Return field join path
     *
     * @return array|null|string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Set flag for exclude joins from query builder
     *
     * @param bool $flag
     * @return \Engine\Crud\Grid\Filter\Field\Join
     */
    public function setSeparatedQueriesFlag($flag = true)
    {
        $this->_separatedQueries = $flag;
        return $this;
    }
}