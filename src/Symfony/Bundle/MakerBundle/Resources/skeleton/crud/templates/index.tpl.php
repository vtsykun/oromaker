{% extends '@OroUI/actions/index.html.twig' %}
{% import '@OroUI/macros.html.twig' as UI %}
{% set pageTitle = '<?= $trans_prefix ?>.entity_plural_label'|trans %}

{% block navButtons %}
    {% if is_granted('<?= $route_prefix ?>_create') %}
        <div class="btn-group">
            {{ UI.addButton({
                'path' : path('<?= $route_prefix ?>_create'),
                'entity_label': '<?= $trans_prefix ?>.entity_label'|trans,
            }) }}
        </div>
    {% endif %}
{% endblock %}
