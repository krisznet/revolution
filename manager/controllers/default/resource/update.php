<?php
/**
 * Loads the update resource page
 *
 * @package modx
 * @subpackage manager.resource
 */
if (!$modx->hasPermission('edit_document')) return $modx->error->failure($modx->lexicon('access_denied'));

if (empty($_REQUEST['id'])) return $modx->error->failure($modx->lexicon('resource_err_nf'));
$resource = $modx->getObject('modResource',$_REQUEST['id']);
if (empty($resource)) return $modx->error->failure($modx->lexicon('resource_err_nfs',array('id' => $_REQUEST['id'])));

if (!$resource->checkPolicy('save')) {
    return $modx->error->failure($modx->lexicon('access_denied'));
}

$lockedBy = $resource->addLock($modx->user->get('id'));
if (!empty($lockedBy) && $lockedBy !== true) {
    if ($user = $modx->getObject('modUser', $lockedBy)) {
        $lockedBy = $user->get('username');
    }
    return $modx->error->failure($modx->lexicon('resource_locked_by', array('user' => $lockedBy, 'id' => $resource->get('id'))));
}

$resourceClass= isset ($_REQUEST['class_key']) ? $_REQUEST['class_key'] : $resource->get('class_key');
$resourceDir= strtolower(substr($resourceClass, 3));

$delegateView= dirname(__FILE__) . '/' . $resourceDir . '/' . basename(__FILE__);
if (file_exists($delegateView)) {
    $overridden= include($delegateView);
    if ($overridden !== false) {
        return $overridden;
    }
}

if (isset($_REQUEST['template'])) $resource->set('template',$_REQUEST['template']);


/* invoke OnDocFormPrerender event */
$onDocFormPrerender = $modx->invokeEvent('OnDocFormPrerender',array(
    'id' => $resource->get('id'),
    'resource' => &$resource,
    'mode' => 'upd',
));
if (is_array($onDocFormPrerender)) {
    $onDocFormPrerender = implode('',$onDocFormPrerender);
}
$modx->smarty->assign('onDocFormPrerender',$onDocFormPrerender);

/* handle default parent */
$parentname = $modx->getOption('site_name');
if ($resource->get('parent') != 0) {
    $parent = $modx->getObject('modResource',$resource->get('parent'));
    if ($parent != null) {
        $parentname = $parent->get('pagetitle');
    }
}
$modx->smarty->assign('parent',$resource->get('parent'));
$modx->smarty->assign('parentname',$parentname);

/* invoke OnDocFormRender event */
$onDocFormRender = $modx->invokeEvent('OnDocFormRender',array(
    'id' => $resource->get('id'),
    'resource' => &$resource,
    'mode' => 'upd',
));
if (is_array($onDocFormRender)) $onDocFormRender = implode('',$onDocFormRender);
$onDocFormRender = str_replace(array('"',"\n","\r"),array('\"','',''),$onDocFormRender);
$modx->smarty->assign('onDocFormRender',$onDocFormRender);

/* get url for resource for preview window */
$url = $modx->makeUrl($resource->get('id'));

/* assign resource to smarty */
$modx->smarty->assign('resource',$resource);

/* check permissions */
$publish_document = $modx->hasPermission('publish_document');
$edit_doc_metatags = $modx->hasPermission('edit_doc_metatags');
$access_permissions = $modx->hasPermission('access_permissions');

/* register JS scripts */
$rte = isset($_REQUEST['which_editor']) ? $_REQUEST['which_editor'] : $modx->getOption('which_editor');
$modx->smarty->assign('which_editor',$rte);

$managerUrl = $modx->getOption('manager_url');
if ($modx->getOption('use_editor') && $resource->get('richtext') && (empty($rte) || $rte == 'MODxEditor')) {
    $modx->regClientStartupScript($managerUrl.'assets/modext/widgets/rte/modx.rte.js');
    $modx->regClientStartupScript($managerUrl.'assets/modext/widgets/rte/dom/modx.rte.selection.js');
    $modx->regClientStartupScript($managerUrl.'assets/modext/widgets/rte/modx.rte.plugins.js');
}
$modx->regClientStartupScript($managerUrl.'assets/modext/util/datetime.js');
$modx->regClientStartupScript($managerUrl.'assets/modext/widgets/element/modx.panel.tv.renders.js');
$modx->regClientStartupScript($managerUrl.'assets/modext/widgets/resource/modx.grid.resource.security.js');
$modx->regClientStartupScript($managerUrl.'assets/modext/widgets/resource/modx.panel.resource.tv.js');
$modx->regClientStartupScript($managerUrl.'assets/modext/widgets/resource/modx.panel.resource.js');
$modx->regClientStartupScript($managerUrl.'assets/modext/sections/resource/update.js');
$modx->regClientStartupHTMLBlock('
<script type="text/javascript">
// <![CDATA[
MODx.config.publish_document = "'.$publish_document.'";
MODx.onDocFormRender = "'.$onDocFormRender.'";
Ext.onReady(function() {
    MODx.load({
        xtype: "modx-page-resource-update"
        ,resource: "'.$resource->get('id').'"
        ,template: "'.$resource->get('template').'"
        ,content_type: "'.$resource->get('content_type').'"
        ,class_key: "'.$resource->get('class_key').'"
        ,context_key: "'.$resource->get('context_key').'"
        ,parent: "'.$resource->get('parent').'"
        ,deleted: "'.$resource->get('deleted').'"
        ,published: "'.$resource->get('published').'"
        ,richtext: "'.$resource->get('richtext').'"
        ,edit_doc_metatags: "'.$edit_doc_metatags.'"
        ,access_permissions: "'.$access_permissions.'"
        ,publish_document: "'.$publish_document.'"
        ,preview_url: "'.$url.'"
    });
});
// ]]>
</script>');

/*
 *  Initialize RichText Editor
 */
/* Set which RTE */
if ($modx->getOption('use_editor') && $rte != 'core' && !empty($rte)) {
    /* invoke OnRichTextEditorRegister event */
    $text_editors = $modx->invokeEvent('OnRichTextEditorRegister');
    $modx->smarty->assign('text_editors',$text_editors);

    $replace_richtexteditor = array('ta');
    $modx->smarty->assign('replace_richtexteditor',$replace_richtexteditor);

    /* invoke OnRichTextEditorInit event */
    $onRichTextEditorInit = $modx->invokeEvent('OnRichTextEditorInit',array(
        'editor' => $rte,
        'elements' => $replace_richtexteditor,
        'id' => $resource->get('id'),
        'resource' => &$resource,
        'mode' => 'upd',
    ));
    if (is_array($onRichTextEditorInit)) {
        $onRichTextEditorInit = implode('',$onRichTextEditorInit);
        $modx->smarty->assign('onRichTextEditorInit',$onRichTextEditorInit);
    }
}

return $modx->smarty->fetch('resource/update.tpl');
