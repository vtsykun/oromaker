<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Renderer;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @internal
 */
final class FormTypeRenderer
{
    private array $typeMapping = [
        'string' => TextType::class,
        'integer' => NumberType::class,
        'text' => TextareaType::class,
        'bool' => CheckboxType::class,
    ];

    public function __construct(
        private Generator $generator,
    ) {
    }

    public function render(ClassNameDetails $formClassDetails, array $formFields, ClassNameDetails $boundClassDetails = null, array $constraintClasses = [], array $extraUseClasses = [], ClassMetadata $metadata = null): void
    {
        $fieldTypeUseStatements = [];
        $fields = [];
        foreach ($formFields as $name => $fieldTypeOptions) {
            $fieldTypeOptions ??= ['type' => null, 'options_code' => null];
            $options = [];
            if ($metadata !== null) {
                $options = ['label' => ConfigHelper::getTranslationKey('entity', 'label', $metadata->getName(), $name)];
                if ($info = $metadata->fieldMappings[$name] ?? null) {
                    $fieldTypeOptions['type'] = $fieldTypeOptions['type'] ?? ($this->typeMapping[(string)$info['type']] ?? null);
                    $options['required'] = !($info['nullable'] ?? null);
                }
            }

            if ($options) {
                $fieldTypeOptions['options_code'] ??= $this->getOptionCode($options);
            }

            if (isset($fieldTypeOptions['type'])) {
                $fieldTypeUseStatements[] = $fieldTypeOptions['type'];
                $fieldTypeOptions['type'] = Str::getShortClassName($fieldTypeOptions['type']);
            }

            $fields[$name] = $fieldTypeOptions;
        }

        $useStatements = new UseStatementGenerator(array_unique(array_merge(
            $fieldTypeUseStatements,
            $extraUseClasses,
            $constraintClasses
        )));

        $useStatements->addUseStatement([
            AbstractType::class,
            FormBuilderInterface::class,
            OptionsResolver::class,
        ]);

        if ($boundClassDetails) {
            $useStatements->addUseStatement($boundClassDetails->getFullName());
        }

        $this->generator->generateClass(
            $formClassDetails->getFullName(),
            'form/Type.tpl.php',
            [
                'use_statements' => $useStatements,
                'bounded_class_name' => $boundClassDetails?->getShortName(),
                'form_fields' => $fields,
            ]
        );
    }

    private function getOptionCode(array $options): string
    {
        $string = '';
        foreach ($options as $name => $value) {
            if (is_string($value)) {
                $value = '"' . $value . '"';
            } else if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            $string .= str_repeat(' ', 16) . "'$name' => $value,\n";
        }

        return rtrim($string);
    }
}
