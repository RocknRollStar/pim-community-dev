<?php

namespace Pim\Component\Api\Hal;

/**
 * Basic implementation of a HAL resource.
 *
 * @author    Alexandre Hocquard <alexandre.hocquard@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class HalResource implements ResourceInterface
{
    /** @var array */
    protected $links = [];

    /** @var array */
    protected $embedded = [];

    /** @var array */
    protected $data = [];

    /**
     * @param string $url  url of the self link
     * @param array  $data additional data
     */
    public function __construct($url, array $data)
    {
        $link = $this->createSelfLink($url);

        $this->addLink($link);
        $this->setData($data);
    }

    /**
     * {@inheritdoc}
     */
    public function addEmbedded($key, ResourceInterface $resource)
    {
        $this->embedded[$key][] =  $resource;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setEmbedded($key, array $resources)
    {
        $this->embedded[$key] = $resources;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmbedded($key)
    {
        return isset($embedded[$key]) ? $embedded[$key] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function addLink(LinkInterface $link)
    {
        $this->links[] = $link;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setLinks(array $links)
    {
        foreach ($links as $link) {
            $this->addLink($link);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * {@inheritdoc}
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $data['_links'] = [];
        $links = $this->normalizeLinks($this->links);
        if (!empty($links)) {
            $data['_links'] = $links;
        }

        foreach ($this->data as $key => $value) {
            $data[$key] = $value;
        }

        foreach ($this->embedded as $rel => $embedded) {
            $data['_embedded'][$rel] = $this->normalizeEmbedded($embedded);
        }

        return $data;
    }

    /**
     * Normalize a list of embedded resources into an array.
     *
     * @param array $embedded list of embedded resource
     *
     * @return array
     */
    protected function normalizeEmbedded(array $embedded)
    {
        $data = [];
        foreach ($embedded as $embed) {
            $data[] = $embed->toArray();
        }

        return $data;
    }

    /**
     * Normalize the links into an array.
     *
     * @param array $links list of links
     *
     * @return array
     */
    protected function normalizeLinks(array $links)
    {
        $data = [];
        foreach ($links as $link) {
            $data = array_merge($data, $link->toArray());
        }

        return $data;
    }

    /**
     * Create a self link.
     *
     * @param string $url
     *
     * @return Link
     */
    protected function createSelfLink($url)
    {
        return new Link('self', $url);
    }
}
