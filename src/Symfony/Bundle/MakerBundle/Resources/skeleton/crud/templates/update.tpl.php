{% extends '@OroUI/actions/update.html.twig' %}
{% import '@OroUI/macros.html.twig' as UI %}

{% set formAction = form.vars.value.id
    ? path('<?= $route_prefix ?>_update', { 'id': form.vars.value.id })
    : path('<?= $route_prefix ?>_create')
%}

{% oro_title_set({params : {"%entity.name%": form.vars.value.name is defined ? form.vars.value.name : '', '%entityName%': '<?= $trans_prefix ?>.entity_label'|trans} }) %}

{% block navButtons %}
    {% if form.vars.value.id and is_granted('DELETE', form.vars.value) %}
        {{ UI.deleteButton({
            'dataUrl': path('<?= $route_prefix ?>_delete', {'id': form.vars.value.id}),
            'dataRedirect': path('<?= $route_prefix ?>_index'),
            'aCss': 'no-hash remove-button',
            'id': 'btn-remove-id',
            'dataId': form.vars.value.id,
            'entity_label': '<?= $trans_prefix ?>.entity_label'|trans,
        }) }}
        {{ UI.buttonSeparator() }}
    {% endif %}
    {{ UI.cancelButton(path('<?= $route_prefix ?>_index')) }}
    {% set html = UI.saveAndCloseButton({
        'route': '<?= $route_prefix ?>_view',
        'params': {'id': '$id'}
    }) %}
    {% if is_granted('<?= $route_prefix ?>_create') %}
        {% set html = html ~ UI.saveAndNewButton({
            'route': '<?= $route_prefix ?>_create'
        }) %}
    {% endif %}
    {% if form.vars.value.id or is_granted('<?= $route_prefix ?>_update') %}
        {% set html = html ~ UI.saveAndStayButton({
        'route': '<?= $route_prefix ?>_edit',
        'params': {'id': '$id'}
        }) %}
    {% endif %}
    {{ UI.dropdownSaveButton({'html': html}) }}
{% endblock %}

{% block pageHeader %}
    {% if form.vars.value.id %}
        {% set breadcrumbs = {
            'entity':      form.vars.value,
            'indexPath':   path('<?= $route_prefix ?>_index'),
            'indexLabel': '<?= $trans_prefix ?>.entity_plural_label'|trans,
            'entityTitle': form.vars.value.name is defined ? form.vars.value.name : ''
        } %}
        {{ parent() }}
    {% else %}
        {% set title = 'oro.ui.create_entity'|trans({'%entityName%': '<?= $trans_prefix ?>.entity_label'|trans}) %}
        {% include '@OroUI/page_title_block.html.twig' with { title: title } %}
    {% endif %}
{% endblock pageHeader %}

{% block content_data %}
    {% set id = '<?= $route_prefix ?>_update' %}

    {% set dataBlocks = [{
        'title': 'General'|trans,
        'class': 'active',
        'subblocks': [
            {
                'title': '<?= $trans_prefix ?>.entity_label'|trans,
                'data': [
                    form_widget(form),
                ]
            }
        ]
    }] %}

    {% set data = {
        'formErrors': form_errors(form) ? form_errors(form) : null,
        'dataBlocks': dataBlocks
    } %}
    {{ parent() }}
{% endblock %}
