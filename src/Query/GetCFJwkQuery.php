<?php

declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Query;

use Lmc\Cqrs\Http\Query\AbstractHttpGetQuery;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriInterface;

class GetCFJwkQuery extends AbstractHttpGetQuery
{
    public function __construct(RequestFactoryInterface $requestFactory, private readonly string $iss)
    {
        parent::__construct($requestFactory);
    }

    public function getUri(): UriInterface|string
    {
        return $this->iss . '/cdn-cgi/access/certs';
    }
}
