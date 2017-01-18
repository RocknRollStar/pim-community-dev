<?php

namespace Pim\Component\Api\Hal;

/**
 * Interface to manipulate a resource with the HAL format.
 *
 * @author    Alexandre Hocquard <alexandre.hocquard@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface ResourceInterface
{
    /**
     * Add a resource in the list of embedded resources for a given key.
     *
     * @param string            $key key of the list
     * @param ResourceInterface $resource resource to add
     *
     * @return ResourceInterface
     */
    public function addEmbedded($key, ResourceInterface $resource);

    /**
     * Set the list of embedded resources for a given key.
     *
     * @param string $key key of the list
     * @param array  $resources array of resources
     *
     * @return ResourceInterface
     */
    public function setEmbedded($key, array $resources);

    /**
     * Get the list of embedded resources for a given key.
     *
     * @param string $key key of the list to return
     *
     * @return array|null list of embedded resources, null if the list does not exist for the given key
     */
    public function getEmbedded($key);

    /**
     * Add a link in the resource.
     *
     * @param LinkInterface $link
     *
     * @return ResourceInterface
     */
    public function addLink(LinkInterface $link);

    /**
     * Set the links of the resource.
     *
     * @param array $links array of LinkInterface
     *
     * @return ResourceInterface
     */
    public function setLinks(array $links);

    /**
     * Get the links of the resource.
     *
     * @return array
     */
    public function getLinks();

    /**
     * Set the additional data bind to the resource.
     *
     * @param array $data additional data
     *
     * @return ResourceInterface
     */
    public function setData(array $data);

    /**
     * Get the data.
     *
     * @return array
     */
    public function getData();

    /**
     * Generate the resource into an array with the HAL format.
     *
     * [
     *     'data' => 'my_data',
     *     '_links'       => [
     *         'self'     => [
     *             'href' => 'http://akeneo.com/api/self/id',
     *         ],
     *     ],
     *     '_embedded' => [
     *         'items' => [
     *           [
     *               '_links' => [
     *                   'self' => [
     *                       'href' => 'http://akeneo.com/api/resource/id',
     *                   ],
     *               ],
     *               'data' => 'item_data',
     *           ],
     *         ],
     *     ],
     * ]
     *
     * @return array
     */
    public function toArray();
}
