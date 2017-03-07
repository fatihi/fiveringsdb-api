<?php

namespace Alsciende\DoctrineSerializerBundle;

/**
 * Description of Serializer
 *
 * @author Alsciende <alsciende@icloud.com>
 */
class Serializer
{

    /* @var \Doctrine\ORM\EntityManager */
    private $entityManager;

    /* @var Manager\SourceManager */
    private $sourceManager;

    /* @var \Symfony\Component\Validator\Validator\RecursiveValidator */
    private $validator;

    /* @var AssociationNormalizer */
    private $normalizer;

    /* @var \Doctrine\Common\Annotations\Reader */
    private $reader;

    public function __construct (\Doctrine\ORM\EntityManager $entityManager, Manager\SourceManager $sourceManager, \Symfony\Component\Validator\Validator\RecursiveValidator $validator, \Doctrine\Common\Annotations\Reader $reader)
    {
        $this->entityManager = $entityManager;
        $this->sourceManager = $sourceManager;
        $this->validator = $validator;
        $this->reader = $reader;
        $this->normalizer = new AssociationNormalizer($entityManager);
    }

    /**
     * 
     * @return Model\Fragment[]
     */
    public function import ()
    {

        /* @var $encoder JsonFileEncoder */
        $encoder = new JsonFileEncoder();

        $allMetadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        foreach($allMetadata as $metadata) {
            $className = $metadata->getName();
            /* @var $source \Alsciende\DoctrineSerializerBundle\Model\Source */
            $source = $this->reader->getClassAnnotation(new \ReflectionClass($className), 'Alsciende\DoctrineSerializerBundle\Model\Source');
            if ($source) {
                $source->classMetadata = $metadata;
                $source->entityManager = $this->entityManager;
                $source->className = $className;
                $this->sourceManager->addSource($source);
            }
        }
        $sources = $this->sourceManager->getSources();

        $result = [];

        foreach ($sources as $source) {

            $fragments = $encoder->decode($source);

            foreach ($fragments as $fragment) {
                $this->importFragment($fragment);
            }

            $source->entityManager->flush();

            $result = array_merge($result, $fragments);
        }

        return $result;
    }

    /**
     * 
     * @param \Alsciende\DoctrineSerializerBundle\Model\Fragment $fragment
     * @throws \Exception
     */
    public function importFragment (Model\Fragment $fragment)
    {

        // find the entity based on the incoming identifier
        $fragment->entity = $this->findEntity($fragment);

        // normalize the entity in its original state
        $fragment->original = $this->normalizer->normalize($fragment->entity, $fragment->source->group);

        // compute changes between the normalized data
        $fragment->changes = array_diff($fragment->incoming, $fragment->original);

        // denormalize the associations in the incoming data
        $renamedKeys = $this->denormalizeIncomingAssociations($fragment);

        // update the entity with the field updated in incoming
        foreach ($fragment->changes as $field => $value) {
            if (isset($renamedKeys[$field])) {
                $field = $renamedKeys[$field];
                $value = $fragment->incoming[$field];
            }
            $fragment->source->classMetadata->setFieldValue($fragment->entity, $field, $value);
        }

        $errors = $this->validator->validate($fragment->entity);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            throw new \Exception($errorsString);
        }

        $fragment->source->entityManager->merge($fragment->entity);
    }

    /**
     * Finds all the foreign keys in $incoming and replaces them with
     * a proper Doctrine association
     */
    protected function denormalizeIncomingAssociations (Model\Fragment $fragment)
    {
        $renamedKeys = [];
               
        $references = $this->normalizer->findReferences($fragment->incoming, $fragment->source->classMetadata);
        
        $findForeignKeyValues = $this->normalizer->findForeignKeyValues($references);

        foreach($findForeignKeyValues as $findForeignKeyValue) {
            foreach($findForeignKeyValue['joinColumns'] as $joinColumn) {
                unset($fragment->incoming[$joinColumn]);
                $renamedKeys[$joinColumn] = $findForeignKeyValue['foreignKey'];
            }
            $fragment->incoming[$findForeignKeyValue['foreignKey']] = $findForeignKeyValue['foreignValue'];
        }
        
        return $renamedKeys;
    }

    /**
     * Find the entity referenced by the identifiers in $fragment->incoming
     * 
     * @param \Alsciende\DoctrineSerializerBundle\Model\Fragment $fragment
     * @return \Alsciende\DoctrineSerializerBundle\classname
     */
    protected function findEntity (Model\Fragment $fragment)
    {
        $identifierPairs = $this->getIdentifierPairs($fragment);

        $entity = $fragment->source->entityManager->find($fragment->source->className, $identifierPairs);

        if (!isset($entity)) {
            $classname = $fragment->source->className;
            $entity = new $classname();
            foreach ($identifierPairs as $identifierField => $uniqueValue) {
                $fragment->source->classMetadata->setFieldValue($entity, $identifierField, $uniqueValue);
            }
        }

        return $entity;
    }

    /**
     * Returns the array of identifier keys/values that can be used with find()
     * to find the entity described by $incoming
     * 
     * If an identifier is a foreignIdentifier, find the foreign entity
     * 
     * @return array
     * @throws \InvalidArgumentException
     */
    function getIdentifierPairs (Model\Fragment $fragment)
    {
        $pairs = [];

        $identifierFieldNames = $fragment->source->classMetadata->getIdentifierFieldNames();
        $fieldNames = $fragment->source->classMetadata->getFieldNames();
        foreach ($identifierFieldNames as $identifierFieldName) {
            $pairs[$identifierFieldName] = $this->getIdentifierValue($fragment, $identifierFieldName, $fieldNames);
        }

        return $pairs;
    }

    function getIdentifierValue (Model\Fragment $fragment, $identifierFieldName, $fieldNames)
    {
        if (in_array($identifierFieldName, $fieldNames)) {
            if (!isset($fragment->incoming[$identifierFieldName])) {
                throw new \InvalidArgumentException("Missing identifier for entity " . $fragment->source->classMetadata->getName() . " in data " . json_encode($fragment->incoming));
            }
            return $fragment->incoming[$identifierFieldName];
        } else {
            $associationMapping = $fragment->source->classMetadata->getAssociationMapping($identifierFieldName);
            $referenceMetadata = $this->normalizer->findReferenceMetadata($fragment->incoming, $associationMapping);
            $entity = $this->normalizer->findReferencedEntity($identifierFieldName, $referenceMetadata);
            if (!$entity) {
                throw new \InvalidArgumentException("Cannot find entity referenced by $identifierFieldName in data " . json_encode($fragment->incoming));
            }
            return $entity;
        }
    }

}