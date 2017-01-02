<?php

namespace Pim\Component\Api\Pagination;

use Pim\Component\Api\Hal\HalResource;
use Pim\Component\Api\Hal\Link;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * HAL format paginator.
 *
 * @author    Alexandre Hocquard <alexandre.hocquard@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class HalPaginator implements PaginatorInterface
{
    /** @var RouterInterface */
    protected $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(array $items, array $options, $count, $routeListName, $routeItemName, $itemIdentifier)
    {
        $data = [
            'current_page' => $options['page'],
            'pages_count'  => $this->getLastPage($options['limit'], $count),
            'items_count'  => $count,
        ];

        $collection = $this->generateResource($routeListName, $options, $data);
        $collection->setEmbedded('items', []);

        foreach ($items as $item) {
            $resourceItem = $this->generateResource($routeItemName, ['code' => $item[$itemIdentifier]], $item);
            $collection->addEmbedded('items', $resourceItem);
        }

        $collection
            ->addLink($this->generateFirstLink($routeListName, $options))
            ->addLink($this->generateLastLink($routeListName, $options, $count));

        if (null !== $previousLink = $this->generatePreviousLink($routeListName, $options, $count)) {
            $collection->addLink($previousLink);
        }

        if (null !== $nextLink = $this->generateNextLink($routeListName, $options, $count)) {
            $collection->addLink($nextLink);
        }

        return $collection->toArray();
    }

    /**
     * Generates an absolute URL for a specific route based on the given parameters.
     *
     * @param string $routeName
     * @param array  $options
     *
     * @return string
     */
    protected function generateRoute($routeName, array $options)
    {
        return $this->router->generate($routeName, $options, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Generates a resource from a route name.
     *
     * @param string $routeName
     * @param array  $options
     * @param array  $data
     *
     * @return HalResource
     */
    protected function generateResource($routeName, array $options, array $data)
    {
        $url = $this->generateRoute($routeName, $options);

        return new HalResource($url, $data);
    }

    /**
     * Generates a link from a route name.
     *
     * @param string $routeName
     * @param array  $options
     * @param string $linkName
     *
     * @return Link
     */
    protected function generateLink($routeName, array $options, $linkName)
    {
        $url = $this->generateRoute($routeName, $options);

        return new Link($linkName, $url);
    }

    /**
     * Generates the link to the first page.
     *
     * @param string $routeName
     * @param array  $options
     *
     * @return Link
     */
    protected function generateFirstLink($routeName, array $options)
    {
        $options['page'] = 1;

        return $this->generateLink($routeName, $options, 'first');
    }

    /**
     * Generates the link to the last page.
     *
     * @param string $routeName
     * @param array  $options
     * @param int    $count
     *
     * @return Link
     */
    protected function generateLastLink($routeName, array $options, $count)
    {
        $options['page'] = $this->getLastPage($options['limit'], $count);

        return $this->generateLink($routeName, $options, 'last');
    }

    /**
     * Generates the link to the next page if it exists.
     *
     * @param string $routeName
     * @param array  $options
     * @param int    $count
     *
     * @return Link|null return either a link to the next page or null if there is not a next page
     */
    protected function generateNextLink($routeName, array $options, $count)
    {
        $lastPage = $this->getLastPage($options['limit'], $count);
        $nextPage = ++$options['page'];

        if ($nextPage > $lastPage) {
            return null;
        }

        return $this->generateLink($routeName, $options, 'next');
    }

    /**
     * Generates the link to the previous page if it exists.
     *
     * @param string $routeName
     * @param array  $options
     * @param int    $count
     *
     * @return Link|null return either a link to the previous page or null if there is not a previous page
     */
    protected function generatePreviousLink($routeName, array $options, $count)
    {
        $lastPage    = $this->getLastPage($options['limit'], $count);
        $currentPage = $options['page'];

        if ($currentPage < 2 || $currentPage > $lastPage) {
            return null;
        }

        $options['page']--;

        return $this->generateLink($routeName, $options, 'previous');
    }

    /**
     * Calculate the last page depending on the number of total items and the limit.
     *
     * @param int $limit
     * @param int $count
     *
     * @return int
     */
    protected function getLastPage($limit, $count)
    {
        return 0 === $count ? 1 : (int) ceil($count / $limit);
    }
}
