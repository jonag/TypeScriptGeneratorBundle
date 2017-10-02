<?php

namespace A5sys\TypeScriptGeneratorBundle\Generator;

use A5sys\TypeScriptGeneratorBundle\Util\ClassExtractor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type;

/**
 * Class TypeScriptGenerator
 */
class TypeScriptGenerator
{
    private const TYPE_TRANSLATION = [
        'DateTime' => 'Date',
        'int' => 'number',
        'float' => 'number',
        'bool' => 'boolean',
    ];

    private $twig;
    private $templateName;
    private $propertyInfo;

    /**
     * TypeScriptInterfaceGenerator constructor.
     * @param \Twig_Environment $twig
     * @param string            $templateName
     */
    public function __construct(\Twig_Environment $twig, string $templateName)
    {
        $this->twig = $twig;
        $this->templateName = $templateName;

        $phpDocExtractor = new PhpDocExtractor();
        $reflectionExtractor = new ReflectionExtractor();
        $doctrineExtractor = new DoctrineExtractor(new DisconnectedClassMetadataFactory());

        $this->propertyInfo = new PropertyInfoExtractor(
            [$reflectionExtractor, $doctrineExtractor],
            [$phpDocExtractor, $reflectionExtractor, $doctrineExtractor],
            [$phpDocExtractor],
            [$reflectionExtractor]
        );
    }

    /**
     * @param string $inputPath
     * @param string $outputPath
     */
    public function generate(string $inputPath, string $outputPath): void
    {
        $this->checkPath($inputPath);
        $this->checkPath($outputPath);

        $template = $this->twig->load($this->templateName);
        $fs = new Filesystem();

        $classes = ClassExtractor::createMap($inputPath);
        foreach ($classes as $class) {
            $fs->dumpFile($outputPath.'/'.$class['className'].'.ts', $template->render($this->getClassDefinition($class)));
        }
    }

    /**
     * @param string $path
     * @throws \RuntimeException
     */
    private function checkPath(string $path): void
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException(sprintf('The provided path %s is not a directory', $path));
        }
    }

    /**
     * @param array $class
     * @return array
     */
    private function getClassDefinition(array $class): array
    {
        $classDefinition = [
            'name' => $class['className'],
            'properties' => [],
            'classesToImport' => [],
        ];
        foreach ($this->propertyInfo->getProperties($class['fqcn']) as $propertyName) {
            $types = $this->propertyInfo->getTypes($class['fqcn'], $propertyName);

            [$type, $classToImport] = $this->getType($types ?? []);
            if ($classToImport) {
                $classDefinition['classesToImport'][] = $classToImport;
            }
            $classDefinition['properties'][] = [
                'name' => $propertyName,
                'type' => $type,
            ];
        }

        return $classDefinition;
    }

    /**
     * @param \Symfony\Component\PropertyInfo\Type[] $types
     * @return array
     */
    private function getType(array $types): array
    {
        foreach ($types as $type) {
            switch ($type->getBuiltinType()) {
                case Type::BUILTIN_TYPE_BOOL:
                case Type::BUILTIN_TYPE_FLOAT:
                case Type::BUILTIN_TYPE_INT:
                case Type::BUILTIN_TYPE_NULL:
                case Type::BUILTIN_TYPE_STRING:
                    return $this->computePritimiveType($type);

                case Type::BUILTIN_TYPE_ARRAY:
                    return $this->computeArrayType($type);

                case Type::BUILTIN_TYPE_OBJECT && ($type->getClassName() !== ArrayCollection::class):
                    return $this->computeObjectType($type);
            }
        }

        return ['any', null];
    }

    /**
     * @param \Symfony\Component\PropertyInfo\Type $type
     * @return array
     */
    private function computePritimiveType(Type $type): array
    {
        $returnType = $this->translateTypeName($type->getBuiltinType());
        if ($type->isNullable()) {
            $returnType .= ' | null';
        }

        return [$returnType, null];
    }

    /**
     * @param \Symfony\Component\PropertyInfo\Type $type
     * @return array
     */
    private function computeArrayType(Type $type): array
    {
        if ($type->getCollectionValueType() !== null) {
            list($inferredType, $typeToImport) = $this->getType([$type->getCollectionValueType()]);

            return [$inferredType.'[]', $typeToImport];
        }

        return ['[]', null];
    }

    /**
     * @param \Symfony\Component\PropertyInfo\Type $type
     * @return array
     */
    private function computeObjectType(Type $type): array
    {
        $className = (new \ReflectionClass($type->getClassName()))->getShortName();
        $returnType = $this->translateTypeName($className);
        if ($type->isNullable()) {
            $returnType .= ' | null';
        }

        $typeToImport = null;
        if ($type->getClassName() !== \DateTime::class) {
            $typeToImport = $className;
        }

        return [$returnType, $typeToImport];
    }

    /**
     * @param string $typeName
     * @return string
     */
    private function translateTypeName(string $typeName): string
    {
        if (isset(static::TYPE_TRANSLATION[$typeName])) {
            return static::TYPE_TRANSLATION[$typeName];
        }

        return $typeName;
    }
}
