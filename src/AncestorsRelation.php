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

class AncestorsRelation extends BaseRelation
{
    /**
     * Set the base constraints on the relation query.
     */
    public function addConstraints()
    {
        if (! Constraint::isConstraint()) {
            return;
        }

        $this->query->whereAncestorOf($this->parent)
            ->applyNestedSetScope();
    }

    /**
     * @param $related
     *
     * @return bool
     */
    protected function matches(Model $model, $related)
    {
        return $related->isAncestorOf($model);
    }

    /**
     * @param QueryBuilder $query
     * @param Model $model
     */
    protected function addEagerConstraint($query, $model)
    {
        $query->orWhereAncestorOf($model);
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
        $key = $this->getBaseQuery()->getGrammar()->wrap($this->parent->getKeyName());

        return "{$table}.{$rgt} between {$hash}.{$lft} and {$hash}.{$rgt} and {$table}.{$key} <> {$hash}.{$key}";
    }
}
