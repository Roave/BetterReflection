--TEST--
ReflectionObject::IsInstantiable() - variation - constructors
--FILE--
<?php require 'vendor/autoload.php';

class noCtor {
	public static function reflectionObjectFactory() {
		return \BetterReflection\Reflection\ReflectionObject::createFromInstance(new self);
	}	
}

class publicCtorNew {
	public function __construct() {}
	public static function reflectionObjectFactory() {
		return \BetterReflection\Reflection\ReflectionObject::createFromInstance(new self);
	}	
}

class protectedCtorNew {
	protected function __construct() {}
	public static function reflectionObjectFactory() {
		return \BetterReflection\Reflection\ReflectionObject::createFromInstance(new self);
	}	
}

class privateCtorNew {
	private function __construct() {}
	public static function reflectionObjectFactory() {
		return \BetterReflection\Reflection\ReflectionObject::createFromInstance(new self);
	}	
}

class publicCtorOld {
	public function publicCtorOld() {}
	public static function reflectionObjectFactory() {
		return \BetterReflection\Reflection\ReflectionObject::createFromInstance(new self);
	}	
}

class protectedCtorOld {
	protected function protectedCtorOld() {}
	public static function reflectionObjectFactory() {
		return \BetterReflection\Reflection\ReflectionObject::createFromInstance(new self);
	}	
}

class privateCtorOld {
	private function privateCtorOld() {}
	public static function reflectionObjectFactory() {
		return \BetterReflection\Reflection\ReflectionObject::createFromInstance(new self);
	}	
}


$reflectionObjects = array(
		noCtor::reflectionObjectFactory(),
		publicCtorNew::reflectionObjectFactory(),
		protectedCtorNew::reflectionObjectFactory(),
		privateCtorNew::reflectionObjectFactory(),
		publicCtorOld::reflectionObjectFactory(), 
		protectedCtorOld::reflectionObjectFactory(),
		privateCtorOld::reflectionObjectFactory()
	);

foreach($reflectionObjects  as $reflectionObject ) {
	$name = $reflectionObject->getName();
	echo "Is $name instantiable? ";
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($reflectionObject->IsInstantiable());
}
?>
--EXPECTF--
Deprecated: Methods with the same name as their class will not be constructors in a future version of PHP; publicCtorOld has a deprecated constructor in %s on line %d

Deprecated: Methods with the same name as their class will not be constructors in a future version of PHP; protectedCtorOld has a deprecated constructor in %s on line %d

Deprecated: Methods with the same name as their class will not be constructors in a future version of PHP; privateCtorOld has a deprecated constructor in %s on line %d
Is noCtor instantiable? bool(true)
Is publicCtorNew instantiable? bool(true)
Is protectedCtorNew instantiable? bool(false)
Is privateCtorNew instantiable? bool(false)
Is publicCtorOld instantiable? bool(true)
Is protectedCtorOld instantiable? bool(false)
Is privateCtorOld instantiable? bool(false)
