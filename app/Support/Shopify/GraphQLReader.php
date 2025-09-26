<?php declare(strict_types=1);

namespace App\Support\Shopify;

class GraphQLReader
{
    public static function read(string $queryName): string
    {
        $graphQuery = resource_path(sprintf('graphql/shopify/%s.graphql', $queryName));
        if (!file_exists($graphQuery)) {
            throw new \Exception(sprintf('Graph query \'%s\' does not found', $queryName));
        }

        return file_get_contents($graphQuery);
    }
}
