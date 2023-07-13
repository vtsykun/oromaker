<?php

namespace Symfony\Bundle\MakerBundle\Util;

use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Yaml\Yaml;

class GridHelper
{
    protected $frontendTypeMapping = [
        'string' => '',
        'integer' => '',
        'date' => 'date',
        'datetime' => 'datetime',
        'boolean' => 'boolean'
    ];

    protected $filterTypeMapping = [
        'string' => 'string',
        'integer' => 'number',
        'date' => 'date',
        'datetime' => 'datetime',
        'boolean' => ''
    ];

    public function __construct(
        protected DoctrineHelper $doctrineHelper,
    ) {
    }

    public function buildGrids(array $config): string
    {
        $metadata = $this->doctrineHelper->getMetadata($config['entity_class_full']);

        $select = $columns = $filters = [];
        foreach ($metadata->fieldMappings as $field) {
            $frontType = $this->frontendTypeMapping[$field['type']] ?? null;
            if ($frontType !== null) {
                $select[] = 'e.'.$field['fieldName'];
                $column = [
                    'label' => Str::getEntityLabel($metadata->getName(), $field['fieldName']),
                ];
                if ($frontType) {
                    $column['frontend_type'] = $frontType;
                }
                $columns[$field['fieldName']] = $column;

                if ($filterType = $this->filterTypeMapping[$field['type']] ?? null) {
                    $filter = [
                        'label' => Str::getEntityLabel($metadata->getName(), $field['fieldName']),
                        'data_name' => 'e.'.$field['fieldName'],
                        'type' => $filterType,
                    ];
                    $filters[$field['fieldName']] = $filter;
                }
            }
        }

        $columns = $this->addPadding(Yaml::dump($columns), 8);
        $filters = $this->addPadding(Yaml::dump($filters), 12);

        $select = str_repeat(' ', 16) . '- ' . implode("\n" . str_repeat(' ', 16) . '- ', $select);

        return $this->defaultConfig(['select' => $select, 'columns' => $columns, 'filters' => $filters] + $config);
    }

    private function addPadding(string $yml, int $padding): string
    {
        $yml = explode("\n", $yml);
        $yml = array_map(fn($line) => str_repeat(' ', $padding) . $line, $yml);
        return implode("\n", $yml);
    }

    private function defaultConfig(array $config): string
    {
        $yml = <<<YAML
{$config['grid_name']}:
    acl_resource: {$config['route_prefix']}_view
    source:
        type: orm
        query:
            select: 
{$config['select']}
            from:
                - { table: '{$config['entity_class_full']}', alias: e }
    columns:
{$config['columns']}
    sorters:
        columns:
            id:
                data_name: e.id
        default:
            id: DESC
    filters:
        columns:
{$config['filters']}
    properties:
        id: ~
        view_link:
            type:       url
            route:      {$config['route_prefix']}_view
            params:     [ id ]
        update_link:
            type:       url
            route:      {$config['route_prefix']}_update
            params:     [ id ]
        delete_link:
            type:       url
            route:      {$config['route_prefix']}_delete
            params:     [ id ]
    actions:
        view:
            type:         navigate
            label:        oro.grid.action.view
            link:         view_link
            icon:         eye
            acl_resource: {$config['route_prefix']}_view
            rowAction:    true
        edit:
            type:          navigate
            label:         oro.grid.action.update
            link:          update_link
            icon:          pencil-square-o
            acl_resource:  {$config['route_prefix']}_update
        delete:
            type: delete
            acl_resource: {$config['route_prefix']}_delete
            label: oro.grid.action.delete
            icon: trash
            link: delete_link
YAML;
        return $yml;
    }
}
