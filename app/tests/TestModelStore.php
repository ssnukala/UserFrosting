<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Tests;

use League\FactoryMuffin\Exceptions\DeleteMethodNotFoundException;
use League\FactoryMuffin\Exceptions\SaveMethodNotFoundException;
use League\FactoryMuffin\Stores\ModelStore;

/**
 * This is the model store class.
 *
 * @author Alex Weissman (https://alexanderweissman.com)
 */
class TestModelStore extends ModelStore
{
    /**
     * {@inheritDoc}
     */
    protected function save($model)
    {
        $method = $this->methods['save'];

        if (!method_exists($model, $method) || !is_callable([$model, $method])) {
            throw new SaveMethodNotFoundException(get_class($model), $method);
        }

        return $model->setConnection('test_integration')->$method();
    }

    /**
     * {@inheritDoc}
     */
    protected function delete($model)
    {
        $method = $this->methods['delete'];

        if (!method_exists($model, $method) || !is_callable([$model, $method])) {
            throw new DeleteMethodNotFoundException(get_class($model), $method);
        }

        return $model->setConnection('test_integration')->$method();
    }
}
