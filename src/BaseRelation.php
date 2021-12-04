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

use Hyperf\Database\Model\Builder as HyperfBuilder;
use Hyperf\Database\Model\Collection as HyperfCollection;
use Hyperf\Database\Model\Model as HyperfModel;
use Hyperf\Database\Model\Relations\Relation;
use Hyperf\Database\Query\Builder;
use InvalidArgumentException;

abstract class BaseRelation extends Relation
{
    /**
     * @var QueryBuilder
     */
    protected $query;

    /**
     * @var Model|NodeTrait
     */
    protected $parent;

    /**
     * The count of self joins.
     *
     * @var int
     */
    protected static $selfJoinCount = 0;

    /**
     * AncestorsRelation constructor.
     *
     * @param Model $model
     */
    public function __construct(QueryBuilder $builder, HyperfModel $model)
    {
        if (! NestedSet::isNode($model)) {
            throw new InvalidArgumentException('Model must be node.');
        }

        parent::__construct($builder, $model);
    }

    /**
     * @param array $columns
     *
     * @return mixed
     */
    public function getRelationExistenceQuery(
        HyperfBuilder $query,
        HyperfBuilder $parent,
        $columns = ['*']
    ) {
        $query = $this->getParent()->replicate()->newScopedQuery()->select($columns);

        $table = $query->getModel()->getTable();

        $query->from($table . ' as ' . $hash = $this->getRelationCountHash());

        $query->getModel()->setTable($hash);

        $grammar = $query->getQuery()->getGrammar();

        $condition = $this->relationExistenceCondition(
            $grammar->wrapTable($hash),
            $grammar->wrapTable($table),
            $grammar->wrap($this->parent->getLftName()),
            $grammar->wrap($this->parent->getRgtName())
        );

        return $query->whereRaw($condition);
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param string $relation
     *
     * @return array
     */
    public function initRelation(array $models, $relation)
    {
        return $models;
    }

    /**
     * @param array $columns
     *
     * @return mixed
     */
    public function getRelationQuery(
        HyperfBuilder $query,
        HyperfBuilder $parent,
        $columns = ['*']
    ) {
        return $this->getRelationExistenceQuery($query, $parent, $columns);
    }

    /**
     * Get a relationship join table hash.
     *
     * @param bool $incrementJoinCount
     * @return string
     */
    public function getRelationCountHash($incrementJoinCount = true)
    {
        return 'nested_set_' . ($incrementJoinCount ? static::$selfJoinCount++ : static::$selfJoinCount);
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        return $this->query->get();
    }

    /**
     * Set the constraints for an eager load of the relation.
     */
    public function addEagerConstraints(array $models)
    {
        // The first model in the array is always the parent, so add the scope constraints based on that model.
        // @link https://github.com/laravel/framework/pull/25240
        // @link https://github.com/lazychaser/laravel-nestedset/issues/351
        optional($models[0])->applyNestedSetScope($this->query);

        $this->query->whereNested(function (Builder $inner) use ($models) {
            // We will use this query in order to apply constraints to the
            // base query builder
            $outer = $this->parent->newQuery()->setQuery($inner);

            foreach ($models as $model) {
                $this->addEagerConstraint($outer, $model);
            }
        });
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param string $relation
     *
     * @return array
     */
    public function match(array $models, HyperfCollection $results, $relation)
    {
        foreach ($models as $model) {
            $related = $this->matchForModel($model, $results);

            $model->setRelation($relation, $related);
        }

        return $models;
    }

    /**
     * @param $related
     *
     * @return bool
     */
    abstract protected function matches(HyperfModel $model, $related);

    /**
     * @param QueryBuilder $query
     * @param Model $model
     */
    abstract protected function addEagerConstraint($query, $model);

    /**
     * @param $hash
     * @param $table
     * @param $lft
     * @param $rgt
     *
     * @return string
     */
    abstract protected function relationExistenceCondition($hash, $table, $lft, $rgt);

    /**
     * @param Model $model
     *
     * @return Collection
     */
    protected function matchForModel(HyperfModel $model, HyperfCollection $results)
    {
        $result = $this->related->newCollection();

        foreach ($results as $related) {
            if ($this->matches($model, $related)) {
                $result->push($related);
            }
        }

        return $result;
    }
}
