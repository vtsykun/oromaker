<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Util;

use Symfony\Bundle\MakerBundle\Str;

final class ClassNameDetails
{
    public function __construct(
        private string $fullClassName,
        private string $namespacePrefix,
        private ?string $suffix = null,
        private ?string $bundleName = null,
    ) {
        $this->namespacePrefix = trim($namespacePrefix, '\\');
    }

    public function getBundleName(): ?string
    {
        return $this->bundleName;
    }

    public function getFullName(): string
    {
        return $this->fullClassName;
    }

    public function getShortName(): string
    {
        return Str::getShortClassName($this->fullClassName);
    }

    /**
     * Returns the original class name the user entered (after
     * being cleaned up).
     *
     * For example, assuming the namespace is App\Entity:
     *      App\Entity\Admin\User => Admin\User
     */
    public function getRelativeName(): string
    {
        return str_replace($this->namespacePrefix.'\\', '', $this->fullClassName);
    }

    public function getNamespacePrefix(): string
    {
        return $this->namespacePrefix;
    }

    public function buildRelativeName(string $namespacePrefix, string $suffix = ''): string
    {
        $fullNamespacePrefix = rtrim($this->namespacePrefix, '\\') . '\\'. trim($namespacePrefix, '\\');

        return $fullNamespacePrefix . '\\' . $this->getShortName() . $suffix;
    }

    public function getRelativeNameWithoutSuffix(): string
    {
        return Str::removeSuffix($this->getRelativeName(), $this->suffix);
    }
}
