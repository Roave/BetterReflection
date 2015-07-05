# Compatibility with native reflection API

The progress of compatibility can also be tracked in issue [#7](https://github.com/Roave/BetterReflection/issues/7).

## ReflectionClass

| Method | Supported |
|--------|-----------|
| getConstant | :heavy_check_mark: Yes |
| getConstants | :heavy_check_mark: Yes |
| getConstructor | :heavy_check_mark: Yes |
| getDefaultProperties | todo |
| getDocComment | todo |
| getEndLine | todo |
| getExtension | :x: No - extensions are not supported (#15) |
| getFileName | :heavy_check_mark: Yes |
| getInterfaceNames | todo |
| getInterfaces | todo |
| getMethod | :heavy_check_mark: Yes |
| getMethods | :heavy_check_mark: Yes |
| getModifiers | todo |
| getName | :heavy_check_mark: Yes |
| getNamespaceName | :heavy_check_mark: Yes |
| getParentClass | todo |
| getProperties | :heavy_check_mark: Yes |
| getProperty | :heavy_check_mark: Yes |
| getShortName | :heavy_check_mark: Yes |
| getStartLine | todo |
| getStaticProperties | todo |
| getStaticPropertyValue | :x: No - would require an instance (#14) |
| getTraitAliases | todo |
| getTraitNames | todo |
| getTraits | todo |
| hasConstant | todo |
| hasMethod | todo |
| hasProperty | todo |
| implementsInterface | todo |
| inNamespace | :heavy_check_mark: Yes |
| isAbstract | todo |
| isCloneable | todo |
| isFinal | todo |
| isInstance | todo |
| isInstantiable | todo |
| isInterface | todo |
| isInternal | todo |
| isIterateable | todo |
| isSubclassOf | todo |
| isTrait | todo |
| isUserDefined | todo |
| newInstance | todo |
| newInstanceArgs | todo |
| newInstanceWithoutConstructor | todo |
| setStaticPropertyValue | :x: No - would require an instance (#14) |

## ReflectionFunctionAbstract

| Method | Supported |
|--------|-----------|
| getClosureScopeClass | :x: No - would require loading of the method itself (#14) |
| getClosureThis | :x: No - would require loading of the method itself (#14) |
| getDocComment | :heavy_check_mark: Yes |
| getEndLine | todo |
| getExtension | :x: No - extensions are not supported (#15) |
| getExtensionName | :x: No - extensions are not supported (#15) |
| getFileName | :heavy_check_mark: Yes |
| getName | :heavy_check_mark: Yes |
| getNamespaceName | todo |
| getNumberOfParameters | :heavy_check_mark: Yes |
| getNumberOfRequiredParameters | :heavy_check_mark: Yes |
| getParameters | :heavy_check_mark: Yes |
| getShortName | todo |
| getStartLine | todo |
| getStaticVariables | todo |
| inNamespace | todo |
| isClosure | todo |
| isDeprecated | todo |
| isGenerator | todo |
| isInternal | todo |
| isUserDefined | todo |
| isVariadic | todo |
| returnsReference | todo |

## ReflectionMethod

| Method | Supported |
|--------|-----------|
| getClosure | :x: No - would require loading of the method itself (#14) |
| getDeclaringClass | :heavy_check_mark: Yes |
| getModifiers | todo |
| getPrototype | todo |
| invoke | :x: No - would require loading of the method itself (#14) |
| invokeArgs | :x: No - would require loading of the method itself (#14) |
| isAbstract | :heavy_check_mark: Yes |
| isConstructor | :heavy_check_mark: Yes |
| isDestructor | :heavy_check_mark: Yes |
| isFinal | :heavy_check_mark: Yes |
| isPrivate | :heavy_check_mark: Yes |
| isProtected | :heavy_check_mark: Yes |
| isPublic | :heavy_check_mark: Yes |
| isStatic | :heavy_check_mark: Yes |
| setAccessible | :x: No - would require loading of the method itself (#14) |
| _inherited methods_ | see `ReflectionFunctionAbstract` |

## ReflectionParameter

| Method | Supported |
|--------|-----------|
| allowsNull | :heavy_check_mark: Yes |
| canBePassedByValue | :heavy_check_mark: Yes |
| getClass | :x: No - could currently cause recursion |
| getDeclaringClass | :heavy_check_mark: Yes |
| getDeclaringFunction | :heavy_check_mark: Yes |
| getDefaultValue | :heavy_check_mark: Yes (*some assumptions are made) |
| getDefaultValueConstantName | :heavy_check_mark: Yes |
| getName | :heavy_check_mark: Yes |
| getPosition | :heavy_check_mark: Yes |
| isArray | :heavy_check_mark: Yes |
| isCallable | :heavy_check_mark: Yes |
| isDefaultValueAvailable | :heavy_check_mark: Yes |
| isDefaultValueConstant | :heavy_check_mark: Yes |
| isOptional | :heavy_check_mark: Yes |
| isPassedByReference | :heavy_check_mark: Yes |
| isVariadic | :heavy_check_mark: Yes |

## ReflectionFunction

| Method | Supported |
|--------|-----------|
| getClosure | :x: No - would require actual compilation of the AST (#14) |
| invoke | :x: No - would require loading of the function itself (#14) |
| invokeArgs | :x: No - would require loading of the function itself (#14) |
| isDisabled | todo |
| _inherited methods_ | see `ReflectionFunctionAbstract` |

## ReflectionProperty

| Method | Supported |
|--------|-----------|
| getDeclaringClass | :heavy_check_mark: Yes |
| getDocComment | todo |
| getModifiers | todo |
| getName | :heavy_check_mark: Yes |
| getValue | :x: No - would require an instance of an object (#14) |
| isDefault | todo |
| isPrivate | :heavy_check_mark: Yes |
| isProtected | :heavy_check_mark: Yes |
| isPublic | :heavy_check_mark: Yes |
| isStatic | :heavy_check_mark: Yes |
| setAccessible | :x: No - would require an instance of an object (#14) |
| setValue | :x: No - would require an instance of an object (#14) |

## ReflectionExtension

:x: Will not be implemented

## ReflectionZendExtension

:x: Will not be implemented

## ReflectionObject

:x: Will not be implemented
