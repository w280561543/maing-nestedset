<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Maing\Nestedset;

use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\Relations\Constraint;

class DescendantsRelation extends BaseRelation
{
    /**
     * Set the base constraints on the relation query.
     */
    public function addConstraints()
    {
        if (! Constraint::isConstraint()) {
            return;
        }

        $this->query->whereDescendantOf($this->parent)
            ->applyNestedSetScope();
    }

    /**
     * @param QueryBuilder $query
     * @param Model $model
     */
    protected function addEagerConstraint($query, $model)
    {
        $query->orWhereDescendantOf($model);
    }

    /**
     * @param $related
     *
     * @return mixed
     */
    protected function matches(Model $model, $related)
    {
        return $related->isDescendantOf($model);
    }

    /**
     * @param $hash
     * @param $table
     * @param $lft
     * @param $rgt
     *
     * @return string
     */
    protected function relationExistenceCondition($hash, $table, $lft, $rgt)
    {
        return "{$hash}.{$lft} between {$table}.{$lft} + 1 and {$table}.{$rgt}";
    }
}
