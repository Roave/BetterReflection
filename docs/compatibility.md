# Compatibility with native reflection API

## ReflectionClass

| Method | Supported |
|--------|-----------|
| getConstant | :heavy_check_mark: Yes |
| getConstants | :heavy_check_mark: Yes |
| getConstructor | todo |
| getDefaultProperties | todo |
| getDocComment | todo |
| getEndLine | todo |
| getExtension | :x: No |
| getFileName | todo |
| getInterfaceNames | todo |
| getInterfaces | todo |
| getMethod | :heavy_check_mark: Yes |
| getMethods | :heavy_check_mark: Yes |
| getModifiers | todo |
| getName | :heavy_check_mark: Yes |
| getNamespaceName | :heavy_check_mark: Yes |
| getParentClass | todo |
| getProperties | todo |
| getProperty | todo |
| getShortName | :heavy_check_mark: Yes |
| getStartLine | todo |
| getStaticProperties | todo |
| getStaticPropertyValue | :x: No |
| getTraitAliases | todo |
| getTraitNames | todo |
| getTraits | todo |
| hasConstant | todo |
| hasMethod | todo |
| hasProperty | todo |
| implementsInterface | :x: No |
| inNamespace | :heavy_check_mark: Yes |
| isAbstract | todo |
| isCloneable | todo |
| isFinal | todo |
| isInstance | todo |
| isInstantiable | todo |
| isInterface | todo |
| isInternal | todo |
| isIterateable | todo |
| isSubclassOf | :x: No |
| isTrait | todo |
| isUserDefined | todo |
| newInstance | todo |
| newInstanceArgs | todo |
| newInstanceWithoutConstructor | todo |
| setStaticPropertyValue | :x: No |

## ReflectionFunctionAbstract

| Method | Supported |
|--------|-----------|
| getClosureScopeClass | todo |
| getClosureThis | todo |
| getDocComment | todo |
| getEndLine | todo |
| getExtension | todo |
| getExtensionName | todo |
| getFileName | todo |
| getName | :heavy_check_mark: Yes |
| getNamespaceName | todo |
| getNumberOfParameters | todo |
| getNumberOfRequiredParameters | todo |
| getParameters | todo |
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
| getClosure | todo |
| getDeclaringClass | :heavy_check_mark: Yes |
| getModifiers | todo |
| getPrototype | todo |
| invoke | todo |
| invokeArgs | todo |
| isAbstract | :heavy_check_mark: Yes |
| isConstructor | :heavy_check_mark: Yes |
| isDestructor | :heavy_check_mark: Yes |
| isFinal | :heavy_check_mark: Yes |
| isPrivate | :heavy_check_mark: Yes |
| isProtected | :heavy_check_mark: Yes |
| isPublic | :heavy_check_mark: Yes |
| isStatic | :heavy_check_mark: Yes |
| setAccessible | todo |

## ReflectionParameter

| Method | Supported |
|--------|-----------|
| allowsNull | :heavy_check_mark: Yes |
| canBePassedByValue | todo |
| getClass | todo |
| getDeclaringClass | :heavy_check_mark: Yes |
| getDeclaringFunction | :heavy_check_mark: Yes |
| getDefaultValue | :heavy_check_mark: Yes (*some assumptions are made) |
| getDefaultValueConstantName | todo |
| getName | :heavy_check_mark: Yes |
| getPosition | todo |
| isArray | todo |
| isCallable | todo |
| isDefaultValueAvailable | todo |
| isDefaultValueConstant | todo |
| isOptional | :heavy_check_mark: Yes |
| isPassedByReference | todo |
| isVariadic | todo |

## ReflectionFunction

| Method | Supported |
|--------|-----------|
| getClosure | todo |
| invoke | todo |
| invokeArgs | todo |
| isDisabled | todo |

## ReflectionProperty

| Method | Supported |
|--------|-----------|
| getDeclaringClass | todo |
| getDocComment | todo |
| getModifiers | todo |
| getName | todo |
| getValue | todo |
| isDefault | todo |
| isPrivate | todo |
| isProtected | todo |
| isPublic | todo |
| isStatic | todo |
| setAccessible | todo |
| setValue | todo |

## ReflectionExtension

:x: Will not be implemented

## ReflectionZendExtension

:x: Will not be implemented

## ReflectionObject

:x: Will not be implemented