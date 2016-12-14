<?php
/**
 * Created by Slava Basko.
 * Email: basko.slava@gmail.com
 * Date: 3/14/14
 * Time: 6:33 PM
 */

namespace Engine\Builder\Traits;


trait SimpleServiceTemplater {

    public $templateSimpleServiceFileCode = '<?php
%s

%s

%s
class %s implements %s
{
%s
}
';

    public $templateSimpleServiceExtends = 'Service';

    public $templateSimpleServiceImplements = 'InjectionAwareInterface';

    public $templateSimpleUseService = [
        'Phalcon\DI\InjectionAwareInterface',
        'Engine\Tools\Traits\DIaware'
    ];


} 