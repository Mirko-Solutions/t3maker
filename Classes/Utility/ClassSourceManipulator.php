<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mirko\T3maker\Utility;

use Doctrine\DBAL\Types\Types;
use Mirko\T3maker\Doctrine\BaseCollectionRelation;
use Mirko\T3maker\Doctrine\BaseRelation;
use Mirko\T3maker\Doctrine\DoctrineHelper;
use Mirko\T3maker\Doctrine\RelationManyToMany;
use Mirko\T3maker\Doctrine\RelationManyToOne;
use Mirko\T3maker\Doctrine\RelationOneToMany;
use Mirko\T3maker\Doctrine\RelationOneToOne;
use PhpParser\Builder;
use PhpParser\BuilderHelpers;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * @internal
 */
final class ClassSourceManipulator
{
    private const CONTEXT_OUTSIDE_CLASS = 'outside_class';
    private const CONTEXT_CLASS = 'class';
    private const CONTEXT_CLASS_METHOD = 'class_method';
    private const DEFAULT_VALUE_NONE = '__default_value_none';

    private Parser\Php7 $parser;
    private Lexer\Emulative $lexer;
    private PrettyPrinter $printer;
    private ?SymfonyStyle $io = null;

    private ?array $oldStmts = null;
    private array $oldTokens = [];
    private array $newStmts = [];

    private array $pendingComments = [];

    public function __construct(
        private string $sourceCode,
        private bool $overwrite = false,
        private bool $useAttributesForDoctrineMapping = true,
    ) {
        $this->lexer = new Lexer\Emulative(
            [
                'usedAttributes' => [
                    'comments',
                    'startLine', 'endLine',
                    'startTokenPos', 'endTokenPos',
                ],
            ]
        );
        $this->parser = new Parser\Php7($this->lexer);
        $this->printer = new PrettyPrinter();

        $this->setSourceCode($sourceCode);
    }

    public function setIo(SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    public function getSourceCode(): string
    {
        return $this->sourceCode;
    }

    public function addEntityField(string $propertyName, array $columnOptions, array $comments = []): void
    {
        $typeHint = DoctrineHelper::getPropertyTypeForColumn($columnOptions['type']);
        if ($typeHint && DoctrineHelper::canColumnTypeBeInferredByPropertyType($columnOptions['type'], $typeHint)) {
            unset($columnOptions['type']);
        }

        if (isset($columnOptions['type'])) {
            $typeConstant = DoctrineHelper::getTypeConstant($columnOptions['type']);
            if ($typeConstant) {
                $this->addUseStatementIfNecessary(Types::class);
                $columnOptions['type'] = $typeConstant;
            }
        }

        // 2) USE property type on property below, nullable
        // 3) If default value, then NOT nullable

        $nullable = $columnOptions['nullable'] ?? false;
        $isId = (bool)($columnOptions['id'] ?? false);
        $attributes = [];
        //TODO replace with T3 style
        //        $attributes[] = $this->buildAttributeNode(Column::class, $columnOptions, 'ORM');

        $defaultValue = null;
        if ($typeHint === 'array') {
            $defaultValue = new Node\Expr\Array_([], ['kind' => Node\Expr\Array_::KIND_SHORT]);
        } elseif ($typeHint && $typeHint[0] === '\\' && strpos($typeHint, '\\', 1) !== false) {
            $typeHint = $this->addUseStatementIfNecessary(substr($typeHint, 1));
        }

        $propertyType = $typeHint;
        if ($propertyType && !$defaultValue) {
            // all property types
            $propertyType = '?' . $propertyType;
        }

        $this->addProperty(
            name: $propertyName,
            defaultValue: $defaultValue,
            attributes: $attributes,
            comments: $comments,
            propertyType: $propertyType
        );

        $this->addGetter(
            $propertyName,
            $typeHint,
            // getter methods always have nullable return values
            // because even though these are required in the db, they may not be set yet
            // unless there is a default value
            $defaultValue === null
        );

        // don't generate setters for id fields
        if (!$isId) {
            $this->addSetter($propertyName, $typeHint, $nullable);
        }
    }

    public function addManyToOneRelation(RelationManyToOne $manyToOne): void
    {
        $this->addSingularRelation($manyToOne);
    }

    public function addOneToOneRelation(RelationOneToOne $oneToOne): void
    {
        $this->addSingularRelation($oneToOne);
    }

    public function addOneToManyRelation(RelationOneToMany $oneToMany): void
    {
        $this->addCollectionRelation($oneToMany);
    }

    public function addManyToManyRelation(RelationManyToMany $manyToMany): void
    {
        $this->addCollectionRelation($manyToMany);
    }

    public function addGetter(
        string $propertyName,
        $returnType,
        bool $isReturnTypeNullable,
        array $commentLines = []
    ): void {
        $methodName = ($returnType === 'bool' ? 'is' : 'get') . StringUtility::asCamelCase($propertyName);
        $this->addCustomGetter($propertyName, $methodName, $returnType, $isReturnTypeNullable, $commentLines);
    }

    public function addSetter(string $propertyName, ?string $type, bool $isNullable, array $commentLines = []): void
    {
        $builder = $this->createSetterNodeBuilder($propertyName, $type, $isNullable, $commentLines);
        $builder->addStmt(
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $propertyName),
                    new Node\Expr\Variable($propertyName)
                )
            )
        );
        $this->makeMethodVoid($builder);
        $this->addMethod($builder->getNode());
    }

    /**
     * @param array<Node\Attribute|Node\AttributeGroup> $attributes
     */
    public function addProperty(
        string $name,
        $defaultValue = self::DEFAULT_VALUE_NONE,
        array $attributes = [],
        array $comments = [],
        string $propertyType = null
    ): void {
        if ($this->propertyExists($name)) {
            // we never overwrite properties
            return;
        }

        $newPropertyBuilder = (new Builder\Property($name))->makePrivate();

        if ($propertyType !== null) {
            $newPropertyBuilder->setType($propertyType);
        }

        if ($this->useAttributesForDoctrineMapping) {
            foreach ($attributes as $attribute) {
                $newPropertyBuilder->addAttribute($attribute);
            }
        }

        if ($comments) {
            $newPropertyBuilder->setDocComment($this->createDocBlock($comments));
        }

        if ($defaultValue !== self::DEFAULT_VALUE_NONE) {
            $newPropertyBuilder->setDefault($defaultValue);
        }
        $newPropertyNode = $newPropertyBuilder->getNode();

        $this->addNodeAfterProperties($newPropertyNode);
    }

    private function addCustomGetter(
        string $propertyName,
        string $methodName,
        $returnType,
        bool $isReturnTypeNullable,
        array $commentLines = [],
        $typeCast = null
    ): void {
        $propertyFetch = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $propertyName);

        if ($typeCast !== null) {
            switch ($typeCast) {
                case 'string':
                    $propertyFetch = new Node\Expr\Cast\String_($propertyFetch);
                    break;
                default:
                    // implement other cases if/when the library needs them
                    throw new \Exception('Not implemented');
            }
        }

        $getterNodeBuilder = (new Builder\Method($methodName))
            ->makePublic()
            ->addStmt(
                new Node\Stmt\Return_($propertyFetch)
            );

        if ($returnType !== null) {
            $getterNodeBuilder->setReturnType($isReturnTypeNullable ? new Node\NullableType($returnType) : $returnType);
        }

        if ($commentLines) {
            $getterNodeBuilder->setDocComment($this->createDocBlock($commentLines));
        }

        $this->addMethod($getterNodeBuilder->getNode());
    }

    private function createSetterNodeBuilder(
        string $propertyName,
        $type,
        bool $isNullable,
        array $commentLines = []
    ): Builder\Method {
        $methodName = 'set' . StringUtility::asCamelCase($propertyName);
        $setterNodeBuilder = (new Builder\Method($methodName))->makePublic();

        if ($commentLines) {
            $setterNodeBuilder->setDocComment($this->createDocBlock($commentLines));
        }

        $paramBuilder = new Builder\Param($propertyName);
        if ($type !== null) {
            $paramBuilder->setType($isNullable ? new Node\NullableType($type) : $type);
        }
        $setterNodeBuilder->addParam($paramBuilder->getNode());

        return $setterNodeBuilder;
    }

    private function addSingularRelation(BaseRelation $relation): void
    {
        $typeHint = $this->addUseStatementIfNecessary($relation->getTargetClassName());
        if ($relation->getTargetClassName() === $this->getThisFullClassName()) {
            $typeHint = 'self';
        }

        $this->addProperty(
            name: $relation->getPropertyName(),
            defaultValue: null,
            propertyType: '?' . $typeHint,
        );

        $this->addGetter(
            $relation->getPropertyName(),
            $relation->getCustomReturnType() ?? $typeHint,
            // getter methods always have nullable return values
            // unless this has been customized explicitly
            !$relation->getCustomReturnType() || $relation->isCustomReturnTypeNullable()
        );

        if ($relation->shouldAvoidSetter()) {
            return;
        }

        $setterNodeBuilder = $this->createSetterNodeBuilder(
            $relation->getPropertyName(),
            $typeHint,
            // make the type-hint nullable always for ManyToOne to allow the owning
            // side to be set to null, which is needed for orphanRemoval
            // (specifically: when you set the inverse side, the generated
            // code will *also* set the owning side to null - so it needs to be allowed)
            // e.g. $userAvatarPhoto->setUser(null);
            $relation instanceof RelationOneToOne ? $relation->isNullable() : true
        );

        // set the *owning* side of the relation
        // OneToOne is the only "singular" relation type that
        // may be the inverse side
        if ($relation instanceof RelationOneToOne && !$relation->isOwning()) {
            $this->addNodesToSetOtherSideOfOneToOne($relation, $setterNodeBuilder);
        }

        $setterNodeBuilder->addStmt(
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $relation->getPropertyName()),
                    new Node\Expr\Variable($relation->getPropertyName())
                )
            )
        );
        $this->makeMethodVoid($setterNodeBuilder);
        $this->addMethod($setterNodeBuilder->getNode());
    }

    private function addCollectionRelation(BaseCollectionRelation $relation): void
    {
        $typeHint = $relation->isSelfReferencing() ? 'self' : $this->addUseStatementIfNecessary(
            $relation->getTargetClassName()
        );

        $arrayCollectionTypeHint = $this->addUseStatementIfNecessary(ObjectStorage::class);
        $collectionTypeHint = $this->addUseStatementIfNecessary(ObjectStorage::class);

        $this->addProperty(
            name: $relation->getPropertyName(),
            propertyType: $collectionTypeHint,
        );

        // logic to avoid re-adding the same ArrayCollection line
        $addArrayCollection = true;
        if ($this->getConstructorNode()) {
            // We print the constructor to a string, then
            // look for "$this->propertyName = "
            $constructorString = $this->printer->prettyPrint([$this->getConstructorNode()]);
            if (str_contains($constructorString, sprintf('$this->%s = ', $relation->getPropertyName()))) {
                $addArrayCollection = false;
            }
        }

        if ($addArrayCollection) {
            $this->addStatementToConstructor(
                new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $relation->getPropertyName()),
                        new Node\Expr\New_(new Node\Name($arrayCollectionTypeHint))
                    )
                )
            );
        }

        $this->addGetter(
            $relation->getPropertyName(),
            $collectionTypeHint,
            false,
            // add @return that advertises this as a collection of specific objects
            [sprintf('@return %s<%s>', $collectionTypeHint, $typeHint)]
        );

        $this->addSetter(
            $relation->getPropertyName(),
            $collectionTypeHint,
            false,
            [sprintf('@param %s<%s> $%s', $collectionTypeHint, $typeHint, $relation->getPropertyName())]
        );
    }

    private function addStatementToConstructor(Node\Stmt $stmt): void
    {
        if (!$this->getConstructorNode()) {
            $constructorNode = (new Builder\Method('__construct'))->makePublic()->getNode();

            // add call to parent::__construct() if there is a need to
            try {
                $ref = new \ReflectionClass($this->getThisFullClassName());

                if ($ref->getParentClass() && $ref->getParentClass()->getConstructor()) {
                    $constructorNode->stmts[] = new Node\Stmt\Expression(
                        new Node\Expr\StaticCall(new Node\Name('parent'), new Node\Identifier('__construct'))
                    );
                }
            } catch (\ReflectionException $e) {
            }

            $this->addNodeAfterProperties($constructorNode);
        }

        $constructorNode = $this->getConstructorNode();
        $constructorNode->stmts[] = $stmt;
        $this->updateSourceCodeFromNewStmts();
    }

    /**
     * @throws \Exception
     */
    private function getConstructorNode(): ?Node\Stmt\ClassMethod
    {
        foreach ($this->getClassNode()->stmts as $classNode) {
            if ($classNode instanceof Node\Stmt\ClassMethod && $classNode->name == '__construct') {
                return $classNode;
            }
        }

        return null;
    }

    /**
     * @return string The alias to use when referencing this class
     */
    public function addUseStatementIfNecessary(string $class): string
    {
        $shortClassName = StringUtility::getShortClassName($class);
        if ($this->isInSameNamespace($class)) {
            return $shortClassName;
        }

        $namespaceNode = $this->getNamespaceNode();

        $targetIndex = null;
        $addLineBreak = false;
        $lastUseStmtIndex = null;
        foreach ($namespaceNode->stmts as $index => $stmt) {
            if ($stmt instanceof Node\Stmt\Use_) {
                // I believe this is an array to account for use statements with {}
                foreach ($stmt->uses as $use) {
                    $alias = $use->alias->name ?? $use->name->getLast();

                    // the use statement already exists? Don't add it again
                    if ($class === (string)$use->name) {
                        return $alias;
                    }

                    if ($alias === $shortClassName) {
                        // we have a conflicting alias!
                        // to be safe, use the fully-qualified class name
                        // everywhere and do not add another use statement
                        return '\\' . $class;
                    }
                }

                // if $class is alphabetically before this use statement, place it before
                // only set $targetIndex the first time you find it
                if ($targetIndex === null && StringUtility::areClassesAlphabetical(
                    $class,
                    (string)$stmt->uses[0]->name
                )) {
                    $targetIndex = $index;
                }

                $lastUseStmtIndex = $index;
            } elseif ($stmt instanceof Node\Stmt\Class_) {
                if ($targetIndex !== null) {
                    // we already found where to place the use statement

                    break;
                }

                // we hit the class! If there were any use statements,
                // then put this at the bottom of the use statement list
                if ($lastUseStmtIndex !== null) {
                    $targetIndex = $lastUseStmtIndex + 1;
                } else {
                    $targetIndex = $index;
                    $addLineBreak = true;
                }

                break;
            }
        }

        if ($targetIndex === null) {
            throw new \Exception('Could not find a class!');
        }

        $newUseNode = (new Builder\Use_($class, Node\Stmt\Use_::TYPE_NORMAL))->getNode();
        array_splice(
            $namespaceNode->stmts,
            $targetIndex,
            0,
            $addLineBreak ? [$newUseNode, $this->createBlankLineNode(self::CONTEXT_OUTSIDE_CLASS)] : [$newUseNode]
        );

        $this->updateSourceCodeFromNewStmts();

        return $shortClassName;
    }

    /**
     * Builds a PHPParser attribute node.
     *
     * @param string $attributeClass The attribute class which should be used for the attribute E.g. #[Column()]
     * @param array $options The named arguments for the attribute ($key = argument name, $value = argument value)
     * @param ?string $attributePrefix If a prefix is provided, the node is built using the prefix. E.g. #[ORM\Column()]
     */
    public function buildAttributeNode(
        string $attributeClass,
        array $options,
        ?string $attributePrefix = null
    ): Node\Attribute {
        $options = $this->sortOptionsByClassConstructorParameters($options, $attributeClass);

        $context = $this;
        $nodeArguments = array_map(
            static function (string $option, mixed $value) use ($context) {
                if ($value === null) {
                    return new Node\NullableType($option);
                }

                // Use the Doctrine Types constant
                if ($option === 'type' && str_starts_with($value, 'Types::')) {
                    return new Node\Arg(
                        new Node\Expr\ConstFetch(new Node\Name($value)),
                        false,
                        false,
                        [],
                        new Node\Identifier($option)
                    );
                }

                return new Node\Arg(
                    $context->buildNodeExprByValue($value),
                    false,
                    false,
                    [],
                    new Node\Identifier($option)
                );
            },
            array_keys($options),
            array_values($options)
        );

        $class = $attributePrefix ? sprintf(
            '%s\\%s',
            $attributePrefix,
            StringUtility::getShortClassName($attributeClass)
        ) : StringUtility::getShortClassName($attributeClass);

        return new Node\Attribute(
            new Node\Name($class),
            $nodeArguments
        );
    }

    private function updateSourceCodeFromNewStmts(): void
    {
        $newCode = $this->printer->printFormatPreserving(
            $this->newStmts,
            $this->oldStmts,
            $this->oldTokens
        );

        // replace the 3 "fake" items that may be in the code (allowing for different indentation)
        $newCode = preg_replace('/(\ |\t)*private\ \$__EXTRA__LINE;/', '', $newCode);
        $newCode = preg_replace('/use __EXTRA__LINE;/', '', $newCode);
        $newCode = preg_replace('/(\ |\t)*\$__EXTRA__LINE;/', '', $newCode);

        // process comment lines
        foreach ($this->pendingComments as $i => $comment) {
            // sanity check
            $placeholder = sprintf('$__COMMENT__VAR_%d;', $i);
            if (!str_contains($newCode, $placeholder)) {
                // this can happen if a comment is createSingleLineCommentNode()
                // is called, but then that generated code is ultimately not added
                continue;
            }

            $newCode = str_replace($placeholder, '// ' . $comment, $newCode);
        }
        $this->pendingComments = [];

        $this->setSourceCode($newCode);
    }

    private function setSourceCode(string $sourceCode): void
    {
        $this->sourceCode = $sourceCode;
        $this->oldStmts = $this->parser->parse($sourceCode);
        $this->oldTokens = $this->lexer->getTokens();

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NodeVisitor\CloningVisitor());
        $traverser->addVisitor(
            new NodeVisitor\NameResolver(null, ['replaceNodes' => false])
        );
        $this->newStmts = $traverser->traverse($this->oldStmts);
    }

    private function getClassNode(): Node
    {
        $node = $this->findFirstNode(
            function ($node) {
                return $node instanceof Node\Stmt\Class_;
            }
        );

        if (!$node) {
            throw new \Exception('Could not find class node');
        }

        return $node;
    }

    private function getNamespaceNode(): Node
    {
        $node = $this->findFirstNode(
            function ($node) {
                return $node instanceof Node\Stmt\Namespace_;
            }
        );

        if (!$node) {
            throw new \Exception('Could not find namespace node');
        }

        return $node;
    }

    private function findFirstNode(callable $filterCallback): ?Node
    {
        $traverser = new NodeTraverser();
        $visitor = new NodeVisitor\FirstFindingVisitor($filterCallback);
        $traverser->addVisitor($visitor);
        $traverser->traverse($this->newStmts);

        return $visitor->getFoundNode();
    }

    private function findLastNode(callable $filterCallback, array $ast): ?Node
    {
        $traverser = new NodeTraverser();
        $visitor = new NodeVisitor\FindingVisitor($filterCallback);
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        $nodes = $visitor->getFoundNodes();
        $node = end($nodes);

        return $node === false ? null : $node;
    }

    private function createBlankLineNode(string $context): Node\Stmt\Use_|Node|Node\Stmt\Property|Node\Expr\Variable
    {
        return match ($context) {
            self::CONTEXT_OUTSIDE_CLASS => (new Builder\Use_(
                '__EXTRA__LINE',
                Node\Stmt\Use_::TYPE_NORMAL
            ))
                ->getNode(),
            self::CONTEXT_CLASS => (new Builder\Property('__EXTRA__LINE'))
                ->makePrivate()
                ->getNode(),
            self::CONTEXT_CLASS_METHOD => new Node\Expr\Variable(
                '__EXTRA__LINE'
            ),
            default => throw new \Exception('Unknown context: ' . $context),
        };
    }

    private function createSingleLineCommentNode(string $comment, string $context): Node\Stmt
    {
        $this->pendingComments[] = $comment;
        switch ($context) {
            case self::CONTEXT_OUTSIDE_CLASS:
                // just not needed yet
                throw new \Exception('not supported');
            case self::CONTEXT_CLASS:
                // just not needed yet
                throw new \Exception('not supported');
            case self::CONTEXT_CLASS_METHOD:
                return BuilderHelpers::normalizeStmt(
                    new Node\Expr\Variable(sprintf('__COMMENT__VAR_%d', \count($this->pendingComments) - 1))
                );
            default:
                throw new \Exception('Unknown context: ' . $context);
        }
    }

    private function createDocBlock(array $commentLines): string
    {
        $docBlock = "/**\n";
        foreach ($commentLines as $commentLine) {
            if ($commentLine) {
                $docBlock .= " * $commentLine\n";
            } else {
                // avoid the empty, extra space on blank lines
                $docBlock .= " *\n";
            }
        }
        $docBlock .= "\n */";

        return $docBlock;
    }

    private function addMethod(Node\Stmt\ClassMethod $methodNode): void
    {
        $classNode = $this->getClassNode();
        $methodName = $methodNode->name;
        $existingIndex = null;
        if ($this->methodExists($methodName)) {
            if (!$this->overwrite) {
                $this->writeNote(
                    sprintf(
                        'Not generating <info>%s::%s()</info>: method already exists',
                        StringUtility::getShortClassName($this->getThisFullClassName()),
                        $methodName
                    )
                );

                return;
            }

            // record, so we can overwrite in the same place
            $existingIndex = $this->getMethodIndex($methodName);
        }

        $newStatements = [];

        // put new method always at the bottom
        if (!empty($classNode->stmts)) {
            $newStatements[] = $this->createBlankLineNode(self::CONTEXT_CLASS);
        }

        $newStatements[] = $methodNode;

        if ($existingIndex === null) {
            // add them to the end!

            $classNode->stmts = array_merge($classNode->stmts, $newStatements);
        } else {
            array_splice(
                $classNode->stmts,
                $existingIndex,
                1,
                $newStatements
            );
        }

        $this->updateSourceCodeFromNewStmts();
    }

    private function makeMethodVoid(Builder\Method $methodBuilder): void
    {
        $methodBuilder->setReturnType('void');
    }

    private function isInSameNamespace(string $class): bool
    {
        $namespace = substr($class, 0, strrpos($class, '\\'));

        return $this->getNamespaceNode()->name->toCodeString() === $namespace;
    }

    private function getThisFullClassName(): string
    {
        return (string)$this->getClassNode()->namespacedName;
    }

    /**
     * Adds this new node where a new property should go.
     *
     * Useful for adding properties, or adding a constructor.
     */
    private function addNodeAfterProperties(Node $newNode): void
    {
        $classNode = $this->getClassNode();

        // try to add after last property
        $targetNode = $this->findLastNode(
            function ($node) {
                return $node instanceof Node\Stmt\Property;
            },
            [$classNode]
        );

        // otherwise, try to add after the last constant
        if (!$targetNode) {
            $targetNode = $this->findLastNode(
                function ($node) {
                    return $node instanceof Node\Stmt\ClassConst;
                },
                [$classNode]
            );
        }

        // otherwise, try to add after the last trait
        if (!$targetNode) {
            $targetNode = $this->findLastNode(
                function ($node) {
                    return $node instanceof Node\Stmt\TraitUse;
                },
                [$classNode]
            );
        }

        // add the new property after this node
        if ($targetNode) {
            $index = array_search($targetNode, $classNode->stmts);

            array_splice(
                $classNode->stmts,
                $index + 1,
                0,
                [$this->createBlankLineNode(self::CONTEXT_CLASS), $newNode]
            );

            $this->updateSourceCodeFromNewStmts();

            return;
        }

        // put right at the beginning of the class
        // add an empty line, unless the class is totally empty
        if (!empty($classNode->stmts)) {
            array_unshift($classNode->stmts, $this->createBlankLineNode(self::CONTEXT_CLASS));
        }
        array_unshift($classNode->stmts, $newNode);
        $this->updateSourceCodeFromNewStmts();
    }

    private function createNullConstant(): Node\Expr\ConstFetch
    {
        return new Node\Expr\ConstFetch(new Node\Name('null'));
    }

    private function addNodesToSetOtherSideOfOneToOne(
        RelationOneToOne $relation,
        Builder\Method $setterNodeBuilder
    ): void {
        if (!$relation->isNullable()) {
            $setterNodeBuilder->addStmt(
                $this->createSingleLineCommentNode(
                    'set the owning side of the relation if necessary',
                    self::CONTEXT_CLASS_METHOD
                )
            );

            $ifNode = new Node\Stmt\If_(
                new Node\Expr\BinaryOp\NotIdentical(
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable($relation->getPropertyName()),
                        $relation->getTargetGetterMethodName()
                    ),
                    new Node\Expr\Variable('this')
                )
            );

            $ifNode->stmts = [
                new Node\Stmt\Expression(
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable($relation->getPropertyName()),
                        $relation->getTargetSetterMethodName(),
                        [new Node\Arg(new Node\Expr\Variable('this'))]
                    )
                ),
            ];
            $setterNodeBuilder->addStmt($ifNode);
            $setterNodeBuilder->addStmt($this->createBlankLineNode(self::CONTEXT_CLASS_METHOD));

            return;
        }

        // at this point, we know the relation is nullable
        $setterNodeBuilder->addStmt(
            $this->createSingleLineCommentNode(
                'unset the owning side of the relation if necessary',
                self::CONTEXT_CLASS_METHOD
            )
        );

        $ifNode = new Node\Stmt\If_(
            new Node\Expr\BinaryOp\BooleanAnd(
                new Node\Expr\BinaryOp\Identical(
                    new Node\Expr\Variable($relation->getPropertyName()),
                    $this->createNullConstant()
                ),
                new Node\Expr\BinaryOp\NotIdentical(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        $relation->getPropertyName()
                    ),
                    $this->createNullConstant()
                )
            )
        );
        $ifNode->stmts = [
            new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        $relation->getPropertyName()
                    ),
                    $relation->getTargetSetterMethodName(),
                    [new Node\Arg($this->createNullConstant())]
                )
            ),
        ];
        $setterNodeBuilder->addStmt($ifNode);

        $setterNodeBuilder->addStmt($this->createBlankLineNode(self::CONTEXT_CLASS_METHOD));
        $setterNodeBuilder->addStmt(
            $this->createSingleLineCommentNode(
                'set the owning side of the relation if necessary',
                self::CONTEXT_CLASS_METHOD
            )
        );

        $ifNode = new Node\Stmt\If_(
            new Node\Expr\BinaryOp\BooleanAnd(
                new Node\Expr\BinaryOp\NotIdentical(
                    new Node\Expr\Variable($relation->getPropertyName()),
                    $this->createNullConstant()
                ),
                new Node\Expr\BinaryOp\NotIdentical(
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable($relation->getPropertyName()),
                        $relation->getTargetGetterMethodName()
                    ),
                    new Node\Expr\Variable('this')
                )
            )
        );
        $ifNode->stmts = [
            new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    new Node\Expr\Variable($relation->getPropertyName()),
                    $relation->getTargetSetterMethodName(),
                    [new Node\Arg(new Node\Expr\Variable('this'))]
                )
            ),
        ];
        $setterNodeBuilder->addStmt($ifNode);

        $setterNodeBuilder->addStmt($this->createBlankLineNode(self::CONTEXT_CLASS_METHOD));
    }

    private function methodExists(string $methodName): bool
    {
        return $this->getMethodIndex($methodName) !== false;
    }

    private function getMethodIndex(string $methodName)
    {
        foreach ($this->getClassNode()->stmts as $i => $node) {
            if ($node instanceof Node\Stmt\ClassMethod && strtolower($node->name->toString()) === strtolower(
                $methodName
            )) {
                return $i;
            }
        }

        return false;
    }

    private function propertyExists(string $propertyName): bool
    {
        foreach ($this->getClassNode()->stmts as $i => $node) {
            if ($node instanceof Node\Stmt\Property && $node->props[0]->name->toString() === $propertyName) {
                return true;
            }
        }

        return false;
    }

    private function writeNote(string $note): void
    {
        if ($this->io !== null) {
            $this->io->text($note);
        }
    }

    /**
     * builds a PHPParser Expr Node based on the value given in $value
     * throws an Exception when the given $value is not resolvable by this method.
     *
     * @throws \Exception
     */
    private function buildNodeExprByValue(mixed $value): Node\Expr
    {
        switch (\gettype($value)) {
            case 'string':
                $nodeValue = new Node\Scalar\String_($value);
                break;
            case 'integer':
                $nodeValue = new Node\Scalar\LNumber($value);
                break;
            case 'double':
                $nodeValue = new Node\Scalar\DNumber($value);
                break;
            case 'boolean':
                $nodeValue = new Node\Expr\ConstFetch(new Node\Name($value ? 'true' : 'false'));
                break;
            case 'array':
                $context = $this;
                $arrayItems = array_map(
                    static function ($key, $value) use ($context) {
                        return new Node\Expr\ArrayItem(
                            $context->buildNodeExprByValue($value),
                            !\is_int($key) ? $context->buildNodeExprByValue($key) : null
                        );
                    },
                    array_keys($value),
                    array_values($value)
                );
                $nodeValue = new Node\Expr\Array_($arrayItems, ['kind' => Node\Expr\Array_::KIND_SHORT]);
                break;
            default:
                $nodeValue = null;
        }

        if ($nodeValue === null) {
            if ($value instanceof ClassNameValue) {
                $nodeValue = new Node\Expr\ConstFetch(
                    new Node\Name(
                        sprintf('%s::class', $value->isSelf() ? 'self' : $value->getShortName())
                    )
                );
            } else {
                throw new \Exception(sprintf('Cannot build a node expr for value of type "%s"', \gettype($value)));
            }
        }

        return $nodeValue;
    }

    /**
     * sort the given options based on the constructor parameters for the given $classString
     * this prevents code inspections warnings for IDEs like intellij/phpstorm.
     *
     * option keys that are not found in the constructor will be added at the end of the sorted array
     */
    private function sortOptionsByClassConstructorParameters(array $options, string $classString): array
    {
        if (str_starts_with($classString, 'ORM\\')) {
            $classString = sprintf('Doctrine\\ORM\\Mapping\\%s', substr($classString, 4));
        }

        $constructorParameterNames = array_map(
            static function (\ReflectionParameter $reflectionParameter) {
                return $reflectionParameter->getName();
            },
            (new \ReflectionClass($classString))->getConstructor()->getParameters()
        );

        $sorted = [];
        foreach ($constructorParameterNames as $name) {
            if (\array_key_exists($name, $options)) {
                $sorted[$name] = $options[$name];
                unset($options[$name]);
            }
        }

        return array_merge($sorted, $options);
    }
}
