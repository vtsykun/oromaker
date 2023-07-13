{% extends '@OroUI/actions/view.html.twig' %}
{% import '@OroUI/macros.html.twig' as UI %}
{% import '@OroDataGrid/macros.html.twig' as dataGrid %}

{% oro_title_set({params : {"%entity.name%": entity.name} }) %}

{% block navButtons %}
    {% if is_granted('EDIT', entity) %}
        {{ UI.editButton({
            'path' : path('<?= $route_prefix ?>_update', { 'id': entity.id }),
            'entity_label': '<?= $trans_prefix ?>.entity_label'|trans
        }) }}
    {% endif %}
    {% if is_granted('DELETE', entity) %}
        {{ UI.deleteButton({
            'dataUrl': path('<?= $route_prefix ?>_delete', {'id': entity.id}) ,
            'dataRedirect': path('<?= $route_prefix ?>_index'),
            'aCss': 'no-hash remove-button',
            'id': 'btn-remove-tdt-case-area',
            'dataId': entity.id,
            'entity_label': '<?= $trans_prefix ?>.entity_label'|trans,
        }) }}
    {% endif %}
{% endblock navButtons %}

{% block pageHeader %}
    {% set breadcrumbs = {
        'entity': entity,
        'indexPath': path('<?= $route_prefix ?>_index'),
        'indexLabel': '<?= $trans_prefix ?>.entity_label'|trans,
        'entityTitle': entity.name
    } %}
    {{ parent() }}
{% endblock pageHeader %}

{% block content_data %}
    {% set generalInfo %}
        <div class="widget-content">
            <div class="row-fluid form-horizontal contact-info">
                <div class="responsive-block">
<?php foreach ($fields as $field): ?>
                    {{ UI.renderProperty('<?= \Symfony\Bundle\MakerBundle\Str::getEntityLabel($entity_class_full, $field['fieldName']) ?>'|trans, entity.<?= $field['fieldName'] ?>) }}
<?php endforeach; ?>
                </div>
            </div>
        </div>
    {% endset %}

    {% set dataBlocks = [
        {
            'title': 'General Information'|trans,
            'class': 'active',
            'subblocks': [
                {'data' : [generalInfo] }
            ]
        }
    ] %}

    {% set id = '<?= $route_prefix ?>_view' %}
    {% set data = {'dataBlocks': dataBlocks} %}
    {{ parent() }}
{% endblock content_data %}
