<?php

namespace spec\Pim\Component\Api\Hal;

use PhpSpec\ObjectBehavior;
use Pim\Component\Api\Hal\HalResource;
use Pim\Component\Api\Hal\Link;

class HalResourceSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('http://akeneo.com/self', []);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Pim\Component\Api\Hal\HalResource');
    }

    function it_is_a_resource()
    {
        $this->shouldImplement('Pim\Component\Api\Hal\ResourceInterface');
    }

    function it_generates_an_hal_array_with_links_and_data_and_embedded_resources(HalResource $resource, Link $link)
    {
        $link->toArray()->willReturn(
            [
                'next' => [
                    'href' => 'http://akeneo.com/next',
                ],
            ]
        );

        $resource->toArray()->willReturn(
            [
                '_links' => [
                    'self' => [
                        'href' => 'http://akeneo.com/api/resource/id',
                    ],
                ],
                'data'   => 'item_data',
            ]
        );

        $this->addLink($link);
        $this->addEmbedded('items', $resource);
        $this->setData(['total_items' => 1]);

        $this->toArray()->shouldReturn(
            [
                '_links'      => [
                    'self' => [
                        'href' => 'http://akeneo.com/self',
                    ],
                    'next' => [
                        'href' => 'http://akeneo.com/next',
                    ],
                ],
                'total_items' => 1,
                '_embedded'   => [
                    'items' => [
                        [
                            '_links' => [
                                'self' => [
                                    'href' => 'http://akeneo.com/api/resource/id',
                                ],
                            ],
                            'data'   => 'item_data',
                        ],
                    ],
                ],
            ]
        );
    }

    function it_generates_an_hal_array_without_any_embedded_resources(Link $link)
    {
        $link->toArray()->willReturn(
            [
                'next' => [
                    'href' => 'http://akeneo.com/next',
                ],
            ]
        );

        $this->addLink($link);
        $this->setData(['total_items' => 1]);

        $this->toArray()->shouldReturn(
            [
                '_links'      => [
                    'self' => [
                        'href' => 'http://akeneo.com/self',
                    ],
                    'next' => [
                        'href' => 'http://akeneo.com/next',
                    ],
                ],
                'total_items' => 1,
            ]
        );
    }

    function it_generates_an_array_with_selflink_by_default()
    {
        $this->toArray()->shouldReturn(
            [
                '_links'      => [
                    'self' => [
                        'href' => 'http://akeneo.com/self',
                    ],
                ],
            ]
        );
    }

    function it_generates_an_hal_array_with_an_empty_list_of_embedded_resources()
    {
        $this->setEmbedded('items', []);

        $this->toArray()->shouldReturn(
            [
                '_links'      => [
                    'self' => [
                        'href' => 'http://akeneo.com/self',
                    ],
                ],
                '_embedded'   => [
                    'items' => [],
                ],
            ]
        );
    }

    function it_get_null_if_key_not_found_for_an_embedded_list()
    {
        $this->getEmbedded('unknown')->shouldReturn(null);
    }
}
