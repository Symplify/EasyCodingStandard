<?php

namespace ECSPrefix20210507\Nette\Utils;

use ECSPrefix20210507\Nette;
/**
 * PHP reflection helpers.
 */
final class Reflection
{
    use Nette\StaticClass;
    const BUILTIN_TYPES = ['string' => 1, 'int' => 1, 'float' => 1, 'bool' => 1, 'array' => 1, 'object' => 1, 'callable' => 1, 'iterable' => 1, 'void' => 1, 'null' => 1, 'mixed' => 1, 'false' => 1];
    /**
     * Determines if type is PHP built-in type. Otherwise, it is the class name.
     * @param string $type
     * @return bool
     */
    public static function isBuiltinType($type)
    {
        return isset(self::BUILTIN_TYPES[\strtolower($type)]);
    }
    /**
     * Returns the type of return value of given function or method and normalizes `self`, `static`, and `parent` to actual class names.
     * If the function does not have a return type, it returns null.
     * If the function has union type, it throws Nette\InvalidStateException.
     * @return string|null
     * @param \ReflectionFunctionAbstract $func
     */
    public static function getReturnType($func)
    {
        return self::getType($func, $func->getReturnType());
    }
    /**
     * Returns the types of return value of given function or method and normalizes `self`, `static`, and `parent` to actual class names.
     * @param \ReflectionFunctionAbstract $func
     * @return mixed[]
     */
    public static function getReturnTypes($func)
    {
        return self::getType($func, $func->getReturnType(), \true);
    }
    /**
     * Returns the type of given parameter and normalizes `self` and `parent` to the actual class names.
     * If the parameter does not have a type, it returns null.
     * If the parameter has union type, it throws Nette\InvalidStateException.
     * @return string|null
     * @param \ReflectionParameter $param
     */
    public static function getParameterType($param)
    {
        return self::getType($param, $param->getType());
    }
    /**
     * Returns the types of given parameter and normalizes `self` and `parent` to the actual class names.
     * @param \ReflectionParameter $param
     * @return mixed[]
     */
    public static function getParameterTypes($param)
    {
        return self::getType($param, $param->getType(), \true);
    }
    /**
     * Returns the type of given property and normalizes `self` and `parent` to the actual class names.
     * If the property does not have a type, it returns null.
     * If the property has union type, it throws Nette\InvalidStateException.
     * @return string|null
     * @param \ReflectionProperty $prop
     */
    public static function getPropertyType($prop)
    {
        return self::getType($prop, \PHP_VERSION_ID >= 70400 ? $prop->getType() : null);
    }
    /**
     * Returns the types of given property and normalizes `self` and `parent` to the actual class names.
     * @param \ReflectionProperty $prop
     * @return mixed[]
     */
    public static function getPropertyTypes($prop)
    {
        return self::getType($prop, \PHP_VERSION_ID >= 70400 ? $prop->getType() : null, \true);
    }
    /**
     * @param  \ReflectionFunction|\ReflectionMethod|\ReflectionParameter|\ReflectionProperty  $reflection
     * @return string|array|null
     * @param \ReflectionType|null $type
     * @param bool $asArray
     */
    private static function getType($reflection, $type, $asArray = \false)
    {
        if ($type === null) {
            return $asArray ? [] : null;
        } elseif ($type instanceof \ReflectionNamedType) {
            $name = self::normalizeType($type->getName(), $reflection);
            if ($asArray) {
                return $type->allowsNull() && $type->getName() !== 'mixed' ? [$name, 'null'] : [$name];
            }
            return $name;
        } elseif ($type instanceof \ReflectionUnionType) {
            if ($asArray) {
                $types = [];
                foreach ($type->getTypes() as $type) {
                    $types[] = self::normalizeType($type->getName(), $reflection);
                }
                return $types;
            }
            throw new \ECSPrefix20210507\Nette\InvalidStateException('The ' . self::toString($reflection) . ' is not expected to have a union type.');
        } else {
            throw new \ECSPrefix20210507\Nette\InvalidStateException('Unexpected type of ' . self::toString($reflection));
        }
    }
    /**
     * @param  \ReflectionFunction|\ReflectionMethod|\ReflectionParameter|\ReflectionProperty  $reflection
     * @param string $type
     * @return string
     */
    private static function normalizeType($type, $reflection)
    {
        $lower = \strtolower($type);
        if ($reflection instanceof \ReflectionFunction) {
            return $type;
        } elseif ($lower === 'self' || $lower === 'static') {
            return $reflection->getDeclaringClass()->name;
        } elseif ($lower === 'parent' && $reflection->getDeclaringClass()->getParentClass()) {
            return $reflection->getDeclaringClass()->getParentClass()->name;
        } else {
            return $type;
        }
    }
    /**
     * Returns the default value of parameter. If it is a constant, it returns its value.
     * @return mixed
     * @throws \ReflectionException  If the parameter does not have a default value or the constant cannot be resolved
     * @param \ReflectionParameter $param
     */
    public static function getParameterDefaultValue($param)
    {
        if ($param->isDefaultValueConstant()) {
            $const = $orig = $param->getDefaultValueConstantName();
            $pair = \explode('::', $const);
            if (isset($pair[1])) {
                $pair[0] = self::normalizeType($pair[0], $param);
                try {
                    $rcc = new \ReflectionClassConstant($pair[0], $pair[1]);
                } catch (\ReflectionException $e) {
                    $name = self::toString($param);
                    throw new \ReflectionException("Unable to resolve constant {$orig} used as default value of {$name}.", 0, $e);
                }
                return $rcc->getValue();
            } elseif (!\defined($const)) {
                $const = \substr((string) \strrchr($const, '\\'), 1);
                if (!\defined($const)) {
                    $name = self::toString($param);
                    throw new \ReflectionException("Unable to resolve constant {$orig} used as default value of {$name}.");
                }
            }
            return \constant($const);
        }
        return $param->getDefaultValue();
    }
    /**
     * Returns a reflection of a class or trait that contains a declaration of given property. Property can also be declared in the trait.
     * @param \ReflectionProperty $prop
     * @return \ReflectionClass
     */
    public static function getPropertyDeclaringClass($prop)
    {
        foreach ($prop->getDeclaringClass()->getTraits() as $trait) {
            if ($trait->hasProperty($prop->name) && $trait->getProperty($prop->name)->getDocComment() === $prop->getDocComment()) {
                return self::getPropertyDeclaringClass($trait->getProperty($prop->name));
            }
        }
        return $prop->getDeclaringClass();
    }
    /**
     * Returns a reflection of a method that contains a declaration of $method.
     * Usually, each method is its own declaration, but the body of the method can also be in the trait and under a different name.
     * @param \ReflectionMethod $method
     * @return \ReflectionMethod
     */
    public static function getMethodDeclaringMethod($method)
    {
        // file & line guessing as workaround for insufficient PHP reflection
        $decl = $method->getDeclaringClass();
        if ($decl->getFileName() === $method->getFileName() && $decl->getStartLine() <= $method->getStartLine() && $decl->getEndLine() >= $method->getEndLine()) {
            return $method;
        }
        $hash = [$method->getFileName(), $method->getStartLine(), $method->getEndLine()];
        if (($alias = isset($decl->getTraitAliases()[$method->name]) ? $decl->getTraitAliases()[$method->name] : null) && ($m = new \ReflectionMethod($alias)) && $hash === [$m->getFileName(), $m->getStartLine(), $m->getEndLine()]) {
            return self::getMethodDeclaringMethod($m);
        }
        foreach ($decl->getTraits() as $trait) {
            if ($trait->hasMethod($method->name) && ($m = $trait->getMethod($method->name)) && $hash === [$m->getFileName(), $m->getStartLine(), $m->getEndLine()]) {
                return self::getMethodDeclaringMethod($m);
            }
        }
        return $method;
    }
    /**
     * Finds out if reflection has access to PHPdoc comments. Comments may not be available due to the opcode cache.
     * @return bool
     */
    public static function areCommentsAvailable()
    {
        static $res;
        return isset($res) ? $res : ($res = (bool) (new \ReflectionMethod(__METHOD__))->getDocComment());
    }
    /**
     * @param \Reflector $ref
     * @return string
     */
    public static function toString($ref)
    {
        if ($ref instanceof \ReflectionClass) {
            return $ref->name;
        } elseif ($ref instanceof \ReflectionMethod) {
            return $ref->getDeclaringClass()->name . '::' . $ref->name . '()';
        } elseif ($ref instanceof \ReflectionFunction) {
            return $ref->name . '()';
        } elseif ($ref instanceof \ReflectionProperty) {
            return self::getPropertyDeclaringClass($ref)->name . '::$' . $ref->name;
        } elseif ($ref instanceof \ReflectionParameter) {
            return '$' . $ref->name . ' in ' . self::toString($ref->getDeclaringFunction());
        } else {
            throw new \ECSPrefix20210507\Nette\InvalidArgumentException();
        }
    }
    /**
     * Expands the name of the class to full name in the given context of given class.
     * Thus, it returns how the PHP parser would understand $name if it were written in the body of the class $context.
     * @throws Nette\InvalidArgumentException
     * @param string $name
     * @param \ReflectionClass $context
     * @return string
     */
    public static function expandClassName($name, $context)
    {
        $lower = \strtolower($name);
        if (empty($name)) {
            throw new \ECSPrefix20210507\Nette\InvalidArgumentException('Class name must not be empty.');
        } elseif (isset(self::BUILTIN_TYPES[$lower])) {
            return $lower;
        } elseif ($lower === 'self' || $lower === 'static') {
            return $context->name;
        } elseif ($name[0] === '\\') {
            // fully qualified name
            return \ltrim($name, '\\');
        }
        $uses = self::getUseStatements($context);
        $parts = \explode('\\', $name, 2);
        if (isset($uses[$parts[0]])) {
            $parts[0] = $uses[$parts[0]];
            return \implode('\\', $parts);
        } elseif ($context->inNamespace()) {
            return $context->getNamespaceName() . '\\' . $name;
        } else {
            return $name;
        }
    }
    /** @return array of [alias => class]
     * @param \ReflectionClass $class */
    public static function getUseStatements($class)
    {
        if ($class->isAnonymous()) {
            throw new \ECSPrefix20210507\Nette\NotImplementedException('Anonymous classes are not supported.');
        }
        static $cache = [];
        if (!isset($cache[$name = $class->name])) {
            if ($class->isInternal()) {
                $cache[$name] = [];
            } else {
                $code = \file_get_contents($class->getFileName());
                $cache = self::parseUseStatements($code, $name) + $cache;
            }
        }
        return $cache[$name];
    }
    /**
     * Parses PHP code to [class => [alias => class, ...]]
     * @param string $code
     * @param string $forClass
     * @return mixed[]
     */
    private static function parseUseStatements($code, $forClass = null)
    {
        try {
            $tokens = \token_get_all($code, \TOKEN_PARSE);
        } catch (\ParseError $e) {
            \trigger_error($e->getMessage(), \E_USER_NOTICE);
            $tokens = [];
        }
        $namespace = $class = $classLevel = $level = null;
        $res = $uses = [];
        $nameTokens = \PHP_VERSION_ID < 80000 ? [\T_STRING, \T_NS_SEPARATOR] : [\T_STRING, \T_NS_SEPARATOR, \T_NAME_QUALIFIED, \T_NAME_FULLY_QUALIFIED];
        while ($token = \current($tokens)) {
            \next($tokens);
            switch (\is_array($token) ? $token[0] : $token) {
                case \T_NAMESPACE:
                    $namespace = \ltrim(self::fetch($tokens, $nameTokens) . '\\', '\\');
                    $uses = [];
                    break;
                case \T_CLASS:
                case \T_INTERFACE:
                case \T_TRAIT:
                    if ($name = self::fetch($tokens, \T_STRING)) {
                        $class = $namespace . $name;
                        $classLevel = $level + 1;
                        $res[$class] = $uses;
                        if ($class === $forClass) {
                            return $res;
                        }
                    }
                    break;
                case \T_USE:
                    while (!$class && ($name = self::fetch($tokens, $nameTokens))) {
                        $name = \ltrim($name, '\\');
                        if (self::fetch($tokens, '{')) {
                            while ($suffix = self::fetch($tokens, $nameTokens)) {
                                if (self::fetch($tokens, \T_AS)) {
                                    $uses[self::fetch($tokens, \T_STRING)] = $name . $suffix;
                                } else {
                                    $tmp = \explode('\\', $suffix);
                                    $uses[\end($tmp)] = $name . $suffix;
                                }
                                if (!self::fetch($tokens, ',')) {
                                    break;
                                }
                            }
                        } elseif (self::fetch($tokens, \T_AS)) {
                            $uses[self::fetch($tokens, \T_STRING)] = $name;
                        } else {
                            $tmp = \explode('\\', $name);
                            $uses[\end($tmp)] = $name;
                        }
                        if (!self::fetch($tokens, ',')) {
                            break;
                        }
                    }
                    break;
                case \T_CURLY_OPEN:
                case \T_DOLLAR_OPEN_CURLY_BRACES:
                case '{':
                    $level++;
                    break;
                case '}':
                    if ($level === $classLevel) {
                        $class = $classLevel = null;
                    }
                    $level--;
            }
        }
        return $res;
    }
    /**
     * @return string|null
     */
    private static function fetch(array &$tokens, $take)
    {
        $res = null;
        while ($token = \current($tokens)) {
            list($token, $s) = \is_array($token) ? $token : [$token, $token];
            if (\in_array($token, (array) $take, \true)) {
                $res .= $s;
            } elseif (!\in_array($token, [\T_DOC_COMMENT, \T_WHITESPACE, \T_COMMENT], \true)) {
                break;
            }
            \next($tokens);
        }
        return $res;
    }
}
