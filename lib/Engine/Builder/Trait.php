<?php

namespace Engine\Builder;

use Engine\Builder\Traits\BasicTemplater as TBasicTemplater,
    Engine\Builder\Traits\SimpleTraitTemplater as TSimpleTraitTemplater,
    Engine\Tools\Inflector,
    Phalcon\Db\Column,
    Engine\Builder\Script\Color;

class Form extends Component
{
    use TBasicTemplater, TSimpleTraitTemplater;

    protected $type = self::TYPE_SIMPLE;

    /**
     * Constructor
     *
     * @param $options
     * @throws BuilderException
     */
    public function __construct($options)
    {
        if (!isset($options['table_name']) || empty($options['table_name'])) {
            throw new BuilderException("Please, specify the model name");
        }
        $this->_options = $options;
    }

    /**
     * Setup builder type
     *
     * @param int $type
     * @return $this
     */
    public function setType($type = self::TYPE_SIMPLE)
    {
        switch($type) {
            case self::TYPE_SIMPLE: $this->type = self::TYPE_SIMPLE;
                break;
            default: $this->type = self::TYPE_SIMPLE;
                break;
        }
        return $this;
    }

    public function build()
    {
        // Check name (table name)
        if (!$this->_options['table_name']) {
            throw new BuilderException("You must specify the table name");
        }


        // Get config
        $path = '';
        if (isset($this->_options['config_path'])) {
            $path = $this->_options['config_path'];
        } elseif (isset($this->_options['app_path']))  {
            $path = $this->_options['app_path'];
        } elseif (isset($this->_options['module_path']))  {
            $path = $this->_options['module_path'];
        }
        if (!$path) {
            throw new BuilderException('Config path was not set in builder options');
        }
        $config = $this->_getConfig($path);

        // build options
        $this->buildOptions($this->_options['table_name'], $config, Component::OPTION_TRAIT, $this->type);

        $service = $this->_options['table_name'];

        $belongsTo = [];

        // Model::initialize() code
        $initializeCode = "";
        if (count($belongsTo) > 0) {
            foreach ($belongsTo as $rel) {
                $initializeCode .= $rel."\n";
            }
        }

        $content = '';
        $content .= "\n";

        $code = sprintf(
            $this->templateSimpleServiceFileCode,
            $this->_builderOptions['namespace'],
            $this->_builderOptions['use'],
            $this->_builderOptions['head'],
            $this->_builderOptions['className'],
            $this->templateSimpleServiceImplements,
            $content
        );
        file_put_contents($this->_builderOptions['path'], $code);

        print Color::success(
                'Service "' . $this->_builderOptions['className'] .
                '" was successfully created.'
            ) . PHP_EOL;

    }

} 