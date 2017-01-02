<?php

namespace Pim\Component\Api\Pagination;

use Pim\Component\Api\Exception\PaginationParametersException;

/**
 * Validator for the pagination parameters.
 *
 * @author    Alexandre Hocquard <alexandre.hocquard@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ParameterValidator implements ParameterValidatorInterface
{
    /** @var int */
    protected $limitMax;

    /**
     * @param int $limitMax
     */
    public function __construct($limitMax)
    {
        $this->limitMax = $limitMax;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $parameters)
    {
        if (!isset($parameters['page'])) {
            throw new PaginationParametersException('Page number is missing.');
        }

        if (!isset($parameters['limit'])) {
            throw new PaginationParametersException('Limit number is missing.');
        }

        $this->validatePage($parameters['page']);

        $this->validateLimit($parameters['limit']);
    }

    /**
     * @param int $page
     *
     * @throws PaginationValidationException
     */
    protected function validatePage($page)
    {
        if (!is_int($page) || $page < 1) {
            throw new PaginationParametersException(sprintf('"%s" is not a valid page number.', $page));
        }
    }

    /**
     * @param int $limit
     *
     * @throws PaginationValidationException
     */
    protected function validateLimit($limit)
    {
        if (!is_int($limit) || $limit < 1) {
            throw new PaginationParametersException(sprintf('"%s" is not a valid limit number.', $limit));
        }

        if ($this->limitMax < $limit) {
            throw new PaginationParametersException(sprintf('You cannot request more than %s items.', $this->limitMax));
        }
    }
}
