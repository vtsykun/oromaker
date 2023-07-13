<?php

namespace Symfony\Bundle\MakerBundle\Maker;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Renderer\FormTypeRenderer;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\GridHelper;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class MakeOroCrud extends AbstractMaker
{
    private Inflector $inflector;

    public function __construct(
        protected DoctrineHelper $doctrineHelper,
        protected FormTypeRenderer $formTypeRenderer,
        protected GridHelper $gridHelper,
    ) {
        $this->inflector = InflectorFactory::create()->build();
    }

    public static function getCommandName(): string
    {
        return 'make:oro-crud';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates ORO CRUD for Doctrine entity class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('entity-class', InputArgument::OPTIONAL, sprintf('The class name of the entity to create CRUD (e.g. <fg=yellow>%s</>)', Str::asClassName(Str::getRandomTerm())))
        ;
        $command->addOption('flags', null, InputOption::VALUE_OPTIONAL, '1111 - 1 controller, 2 - twigs, 3 - form type, 4 - grids');

        $inputConfig->setArgumentAsNonInteractive('entity-class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (null === $input->getArgument('entity-class')) {
            $argument = $command->getDefinition()->getArgument('entity-class');

            $entities = $this->doctrineHelper->getEntitiesForAutocomplete();

            $question = new Question($argument->getDescription());
            $question->setAutocompleterValues($entities);

            $value = $io->askQuestion($question);

            $input->setArgument('entity-class', $value);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $entityClassDetails = $generator->createClassNameDetails(
            Validator::entityExists($input->getArgument('entity-class'), $this->doctrineHelper->getEntitiesForAutocomplete()),
        );

        $entityDoctrineDetails = $this->doctrineHelper->createDoctrineDetails($entityClassDetails->getFullName());

        $repositoryVars = [];
        $repositoryClassName = EntityManagerInterface::class;
        $controllerClassName = $entityClassDetails->getNamespacePrefix() . '\\Controller\\' . $entityClassDetails->getShortName() . 'Controller';

        $controllerClassDetails = $generator->createClassNameDetails(
            $controllerClassName,
            'Controller\\',
            'Controller'
        );

        $iter = 0;
        do {
            $className = $entityClassDetails->buildRelativeName('Form\\Type', ($iter ?: ''). 'Type');
            $formClassDetails = $generator->createClassNameDetails($className);
            ++$iter;
        } while (class_exists($formClassDetails->getFullName()));

        $entityVarPlural = lcfirst($this->inflector->pluralize($entityClassDetails->getShortName()));
        $entityVarSingular = lcfirst($this->inflector->singularize($entityClassDetails->getShortName()));

        $bundleName = $entityClassDetails->getBundleName();

        $entityName = Str::asRouteName($entityVarSingular, $entityClassDetails->getBundleName());
        $routePath = Str::asRoutePath($entityVarSingular);
        $templatesPath = ($bundleName ? str_ireplace('bundle', '', $bundleName) . '/' : '') . ucfirst($entityVarSingular);
        $gridName = str_replace('_', '-', $entityName) . '-grid';

        $useStatements = new UseStatementGenerator([
            $entityClassDetails->getFullName(),
            $formClassDetails->getFullName(),
            $repositoryClassName,
            AbstractController::class,
            Request::class,
            Response::class,
            Route::class,
            AclAncestor::class,
            'Oro\Bundle\SecurityBundle\Annotation\Acl',
            'Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException'
        ]);

        $transPrefix = Str::getEntityLabel($entityClassDetails->getFullName() , 'unset123');
        $transPrefix = str_replace('.unset123.label', '', $transPrefix);

        $vars = [
            'use_statements' => $useStatements,
            'entity_class' => $entityClassDetails->getShortName(),
            'entity_class_full' => $entityClassDetails->getFullName(),
            'form_class' => $formClassDetails->getShortName(),
            'route_path' => $routePath,
            'route_prefix' => $entityName,
            'grid_name' => $gridName,
            'template_path' => $templatesPath,
            'bundle_name' => $entityClassDetails->getBundleName(),
            'entity_form_name' => Str::asTwigVariable($entityClassDetails->getShortName()),
            'trans_prefix' => $transPrefix,
        ];

        $bundleDir = '';
        if ($entityClassDetails->getBundleName()) {
            $bundleClass = $entityClassDetails->getNamespacePrefix() . '\\' . $entityClassDetails->getBundleName();
            if ($bundleDir = $generator->getRelativePathForFutureClass($bundleClass)) {
                $bundleDir = explode('/', $bundleDir);
                array_pop($bundleDir);
                $bundleDir = implode('/', $bundleDir);
            }

            $bundleDir = $bundleDir . '/';
        }

        $flags = $input->getOption('flags') ?? '1111';
        if ($flags[0] ?? null) {
            $generator->generateController(
                $controllerClassDetails->getFullName(),
                'main/Controller2.tpl.php',
                $vars
            );
        }

        if ($flags[3] ?? null) {
            $gridPath = $bundleDir . 'Resources/config/oro/datagrids.yml';
            $gridData = $this->gridHelper->buildGrids($vars);
            $generator->addContentToFile($gridPath, $gridData);
        }

        if ($flags[2] ?? null) {
            $this->formTypeRenderer->render(
                $formClassDetails,
                $entityDoctrineDetails->getFormFields(),
                $entityClassDetails,
                metadata: $entityDoctrineDetails->getMetadata()
            );
        }

        if ($flags[1] ?? null) {
            $templates = [
                'index' => [],
                'update' => [],
                'view' => ['fields' => $entityDoctrineDetails->getMetadata()->fieldMappings],
            ];

            $templatesPath = $bundleDir . 'Resources/views/' . ucfirst($entityVarSingular);
            foreach ($templates as $template => $variables) {
                $variables = array_merge($vars, $variables);

                $generator->generateFile(
                    $templatesPath.'/'.$template.'.html.twig',
                    'crud/templates/'.$template.'.tpl.php',
                    $variables
                );
            }
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text(sprintf('Next: Check your new CRUD by going to <fg=yellow>%s/</>', Str::asRoutePath($controllerClassDetails->getRelativeNameWithoutSuffix())));
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }
}
