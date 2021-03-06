<?php
/**
 * This file is part of the Pandawa package.
 *
 * (c) 2018 Pandawa <https://github.com/bl4ckbon3/pandawa>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pandawa\Component\Ddd\Relationship;

use Illuminate\Database\Eloquent\Relations\BelongsToMany as LaravelBelongsToMany;
use Pandawa\Component\Ddd\AbstractModel;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class BelongsToMany extends LaravelBelongsToMany
{
    /**
     * @var AbstractModel
     */
    protected $parent;

    /**
     * {@inheritdoc}
     */
    public function attach($id, array $attributes = [], $touch = true)
    {
        $this->parent->addAfterAction(
            function () use ($id, $attributes, $touch) {
                parent::attach($id, $attributes, $touch);
            }
        );
    }

    public function sync($ids, $detaching = true)
    {
        $this->parent->addAfterAction(
            function () use ($ids, $detaching) {
                parent::sync($ids, $detaching);
            }
        );
    }

    public function detach($ids = null, $touch = true)
    {
        $this->parent->addAfterAction(
            function () use ($ids, $touch) {
                parent::detach($ids, $touch);
            }
        );
    }

    public function updateExistingPivot($id, array $attributes, $touch = true)
    {
        $this->parent->addAfterAction(
            function () use ($id, $attributes) {
                parent::updateExistingPivot($id, $attributes, $touch);
            }
        );
    }

    protected function migratePivotAttributes($model)
    {
        $values = [];

        foreach ($model->getAttributes() as $key => $value) {
            if (strpos($key, 'pivot_') === 0) {
                $values[substr($key, 6)] = $value;

                unset($model->$key);
            }
        }

        return $values;
    }
}
