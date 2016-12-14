<?php

namespace Engine\Builder;

use Phalcon\Db\Column,
    Engine\Builder\Component,
    Engine\Builder\BuilderException,
    Engine\Builder\Script\Color,
    Phalcon\Text as Utils,
    Engine\Builder\Traits\BasicTemplater as TBasicTemplater,
    Engine\Builder\Traits\SimpleServiceTemplater as TServiceTemplater,
    Engine\Tools\Inflector;

/**
 * ModelBuilderComponent
 *
 * Builder to generate models
 */
class Service extends Component
{
    use TBasicTemplater,
        TServiceTemplater;

    protected $type = self::TYPE_SIMPLE;

    public function __construct($options)
    {
        if (!isset($options['table_name']) || empty($options['table_name'])) {
            throw new BuilderException("Please, specify the service name");
        }
        parent::__construct($options);
    }

    public function build()
    {
        // Check name (table name)
        if (!$this->_options['table_name']) {
            throw new BuilderException("You must specify the service name");
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
        $this->buildOptions($this->_options['table_name'], $config, Component::OPTION_SERVICE, $this->type);

        $service = $this->_options['table_name'];

        $belongsTo = [];

        // Model::initialize() code
        $initializeCode = "";
        if (count($belongsTo) > 0) {
            foreach ($belongsTo as $rel) {
                $initializeCode .= $rel."\n";
            }
        }

        $content = '    use DIaware;';
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
