<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

<?php

$gridName = str_replace('_', '-', $route_prefix) . '-grid';
?>

<?php
$aclClass = $bundle_name . ':' . $entity_class;
?>
/**
 * @Route("<?= $route_path ?>")
 */
class <?= $class_name; ?> extends AbstractController
{
    /**
     * @Route("/", name="<?= $route_prefix ?>_index")
     * @AclAncestor("<?= $route_prefix ?>_view")
     */
    public function indexAction(): Response
    {
        return $this->render('@<?= $template_path; ?>/index.html.twig', [
            'entityClass' => <?= $entity_class; ?>::class,
            'gridName' => '<?= $grid_name ?>'
        ]);
    }

    /**
     * @Route("/view/{id}", name="<?= $route_prefix ?>_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="<?= $route_prefix ?>_view",
     *      type="entity",
     *      class="<?= $aclClass ?>",
     *      permission="VIEW"
     * )
     */
    public function viewAction(<?= $entity_class ?> $entity): Response
    {
        return $this->render('@<?= $template_path; ?>/view.html.twig', [
            'entity' => $entity
        ]);
    }

    /**
     * @Acl(
     *      id="<?= $route_prefix ?>_create",
     *      type="entity",
     *      permission="CREATE",
     *      class="<?= $aclClass ?>"
     * )
     * @Route(name="<?= $route_prefix ?>_create", path="/create")
     */
    public function createAction(): Response
    {
        return $this->update(new <?= $entity_class ?>());
    }

    /**
     * @Acl(
     *      id="<?= $route_prefix ?>_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="<?= $aclClass ?>"
     * )
     * @Route(name="<?= $route_prefix ?>_update", path="/update/{id}", requirements={"id"="\d+"})
     * @inheritdoc
     */
    public function updateAction(<?= $entity_class ?> $entity): Response
    {
        return $this->update($entity);
    }

    /**
     * @Acl(
     *      id="<?= $route_prefix ?>_delete",
     *      type="entity",
     *      permission="DELETE",
     *      class="<?= $aclClass ?>"
     * )
     * @Route(name="<?= $route_prefix ?>_delete", path="/delete/{id}", requirements={"id"="\d+"})
     * @inheritdoc
     */
    public function deleteAction(<?= $entity_class ?> $entity): Response
    {
        $em = $this->container->get('doctrine')->getManager();
        $em->remove($entity);
        $em->flush();

        return new Response('', 204);
    }

    /**
     * @param <?= $entity_class ?> $entity
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function update(<?= $entity_class ?> $entity): Response
    {
        $form = $this->container->get('form.factory')->createNamed('<?= $entity_form_name ?>', <?= $form_class ?>::class);

        $response = $this->container->get('oro_form.update_handler')->update(
            $entity,
            $form,
            'Cambios guardados'
        );

        if (is_array($response)) {
            return $this->render('@<?= $template_path; ?>/update.html.twig', $response);
        } else {
            return $response;
        }
    }
}
