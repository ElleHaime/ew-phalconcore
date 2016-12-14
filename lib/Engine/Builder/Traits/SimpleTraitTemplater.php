<?php
/**
 * Created by Slava Basko.
 * Email: basko.slava@gmail.com
 * Date: 3/14/14
 * Time: 6:33 PM
 */

namespace Engine\Builder\Traits;

/**
 * Class SimpleServiceTemplater
 * @package Engine\Builder\Traits
 *
 */
trait SimpleTraitTemplater
{

    public $templateSimpleTraitFileCode = '<?php
%s

%s

%s
trait %s
{
%s
}
';

    public $templateSimpleUseTrait = [
        '%s as %s'
    ];

    public $templateSimpleTraitProperty = "
    /**
     * %s service
     * @var %s
     */
    private \$_%s;
";
    public $templateSimpleTraitSet = "
    /**
     * Set %s %s %s
     *
     * @param %s \$%s
     *
     * @return mixed
     */
    public function set%s(%s \$%s)
    {
        \$this->_%s = \$%s;
        return \$this;
    }
";

    public $templateSimpleTraitGet = "
    /**
     * Return %s %s %s object
     *
     * @return %s
     * @throws \Engine\Exception
     */
    public function get%s()
    {
        if (null === \$this->_%s) {
            if (\$this->_di) {
                \$this->_%s = \$this->_di->get('%s');
            }
        }
        if (!\$this->_%s instanceof %s) {
            throw new \Engine\Exception('Object not instance of %s');
        }
        return \$this->_%s;
    }
";

}
