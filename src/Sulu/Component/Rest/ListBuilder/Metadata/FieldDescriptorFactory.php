<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata;

use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineConcatenationFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineGroupConcatFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\FieldMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\PropertyMetadata as DoctrinePropertyMetadata;
<<<<<<< 55aeef5d19a8ac01fbef4160ea0f89b41b86c6ea
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\ConcatenationTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\GroupConcatTypeMetadata;use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\SingleTypeMetadata;
=======
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\ConcatenationType;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\GroupConcatType;
>>>>>>> added group-concat to field-descriptor factory
use Sulu\Component\Rest\ListBuilder\Metadata\General\PropertyMetadata as GeneralPropertyMetadata;
use Symfony\Component\Config\ConfigCache;

/**
 * Creates legacy field-descriptors for metadata.
 */
class FieldDescriptorFactory implements FieldDescriptorFactoryInterface
{
    /**
     * @var ProviderInterface
     */
    private $metadataProvider;

    /**
     * @var ConfigCache
     */
    private $cache;

    public function __construct(ProviderInterface $metadataProvider, ConfigCache $cache)
    {
        $this->metadataProvider = $metadataProvider;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDescriptorForClass($className)
    {
        if ($this->cache->isFresh()) {
            return require $this->cache;
        }

        $metadata = $this->metadataProvider->getMetadataForClass($className);

        $fieldDescriptors = [];
        /** @var PropertyMetadata $propertyMetadata */
        foreach ($metadata->propertyMetadata as $propertyMetadata) {
            if (!$propertyMetadata->has(DoctrinePropertyMetadata::class)
                || !$propertyMetadata->has(GeneralPropertyMetadata::class)
            ) {
                continue;
            }

            /** @var DoctrinePropertyMetadata $doctrineMetadata */
            $doctrineMetadata = $propertyMetadata->get(DoctrinePropertyMetadata::class);
            /** @var GeneralPropertyMetadata $generalMetadata */
            $generalMetadata = $propertyMetadata->get(GeneralPropertyMetadata::class);

            $fieldDescriptor = null;
            if ($doctrineMetadata->getType() instanceof ConcatenationTypeMetadata) {
                $fieldDescriptor = $this->getConcatenationFieldDescriptor(
                    $generalMetadata,
                    $doctrineMetadata->getType()
                );
            } elseif ($doctrineMetadata->getType() instanceof GroupConcatTypeMetadata) {
                $fieldDescriptor = $this->getGroupConcatenationFieldDescriptor(
                    $generalMetadata,
                    $doctrineMetadata->getType()->getField()
                );
            } elseif ($doctrineMetadata->getType() instanceof SingleTypeMetadata) {
                $fieldDescriptor = $this->getFieldDescriptor(
                    $generalMetadata,
                    $doctrineMetadata->getType()->getField()
                );
            }

            if (null !== $fieldDescriptor) {
                $fieldDescriptor->setMetadata($propertyMetadata);
                $fieldDescriptors[$generalMetadata->getName()] = $fieldDescriptor;
            }
        }

        $this->cache->write('<?php return unserialize(' . var_export(serialize($fieldDescriptors), true) . ');');

        return $fieldDescriptors;
    }

    /**
     * Returns field-descriptor for given general metadata.
     *
     * @param GeneralPropertyMetadata $generalMetadata
     * @param FieldMetadata $fieldMetadata
     *
     * @return DoctrineFieldDescriptor
     */
    protected function getFieldDescriptor(GeneralPropertyMetadata $generalMetadata, FieldMetadata $fieldMetadata)
    {
        $joins = [];
        foreach ($fieldMetadata->getJoins() as $joinMetadata) {
            $joins[$joinMetadata->getEntityName()] = new DoctrineJoinDescriptor(
                $joinMetadata->getEntityName(),
                $joinMetadata->getEntityField(),
                $joinMetadata->getCondition(),
                $joinMetadata->getMethod(),
                $joinMetadata->getConditionMethod()
            );
        }

        return new DoctrineFieldDescriptor(
            $fieldMetadata->getName(),
            $generalMetadata->getName(),
            $fieldMetadata->getEntityName(),
            $generalMetadata->getTranslation(),
            $joins,
            $generalMetadata->isDisabled(),
            $generalMetadata->isDefault(),
            $generalMetadata->getType(),
            $generalMetadata->getWidth(),
            $generalMetadata->getMinWidth(),
            $generalMetadata->isSortable(),
            $generalMetadata->isEditable(),
            $generalMetadata->getCssClass()
        );
    }

    /**
     * Returns concatenation field-descriptor for given general metadata.
     *
     * @param GeneralPropertyMetadata $generalMetadata
     * @param ConcatenationTypeMetadata $type
     *
     * @return DoctrineFieldDescriptor
     */
    protected function getConcatenationFieldDescriptor(
        GeneralPropertyMetadata $generalMetadata,
        ConcatenationTypeMetadata $type
    ) {
        return new DoctrineConcatenationFieldDescriptor(
            array_map(
                function (FieldMetadata $fieldMetadata) use ($generalMetadata) {
                    return $this->getFieldDescriptor($generalMetadata, $fieldMetadata);
                },
                $type->getFields()
            ),
            $generalMetadata->getName(),
            $generalMetadata->getTranslation(),
            $type->getGlue(),
            $generalMetadata->isDisabled(),
            $generalMetadata->isDefault(),
            $generalMetadata->getType(),
            $generalMetadata->getWidth(),
            $generalMetadata->getMinWidth(),
            $generalMetadata->isSortable(),
            $generalMetadata->isEditable(),
            $generalMetadata->getCssClass()
        );
    }

    /**
     * Returns concatenation field-descriptor for given general metadata.
     *
     * @param GeneralPropertyMetadata $generalMetadata
     * @param GroupConcatType $type
     *
     * @return DoctrineFieldDescriptor
     */
    protected function getGroupConcatenationFieldDescriptor(
        GeneralPropertyMetadata $generalMetadata,
        GroupConcatType $type
    ) {
        return new DoctrineGroupConcatFieldDescriptor(
            $this->getFieldDescriptor($generalMetadata, $type->getField()),
            $generalMetadata->getName(),
            $generalMetadata->getTranslation(),
            $type->getGlue(),
            $generalMetadata->isDisabled(),
            $generalMetadata->isDefault(),
            $generalMetadata->getType(),
            $generalMetadata->getWidth(),
            $generalMetadata->getMinWidth(),
            $generalMetadata->isSortable(),
            $generalMetadata->isEditable(),
            $generalMetadata->getCssClass()
        );
    }
}
