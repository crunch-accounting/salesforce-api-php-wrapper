<?php

namespace Akeneo\SalesForce\Search;

/**
 * @author Emmanuel Ripoll <emmanuelripoll@gmail.com>
 */
class ParameterizedSearchBuilder
{
    /**
     * @var string
     */
    protected $query = '';

    /**
     * @param string $value
     *
     * @return ParameterizedSearchBuilder
     */
    public function search(string $value): ParameterizedSearchBuilder
    {
        $this->query = sprintf($value);

        return $this;
    }

    /**
     * @param string $table
     *
     * @return ParameterizedSearchBuilder
     */
    public function addTable(string $table): ParameterizedSearchBuilder
    {
        $this->query = sprintf('%s&sobject=%s', $this->query, $table);

        return $this;
    }

    /**
     * @param string $table
     * @param string $field
     *
     * @return ParameterizedSearchBuilder
     */
    public function select(string $table, string $field): ParameterizedSearchBuilder
    {
        $this->query = sprintf('%s&%s.fields=%s', $this->query, $table, $field);

        return $this;
    }

    /**
     * @param string $field
     *
     * @return ParameterizedSearchBuilder
     */
    public function addSelect(string $field): ParameterizedSearchBuilder
    {
        $this->query = sprintf('%s,%s', $this->query, $field);

        return $this;
    }

    /**
     * @param int $limit
     * @param string $table
     *
     * @return ParameterizedSearchBuilder
     */
    public function addLimit(int $limit, string $table): ParameterizedSearchBuilder
    {
        $this->query = sprintf('%s&%s.limit=%s', $this->query, $table, $limit);

        return $this;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

}